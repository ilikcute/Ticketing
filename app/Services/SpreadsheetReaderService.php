<?php

namespace App\Services;

use Generator;
use Illuminate\Support\LazyCollection;
use RuntimeException;
use ZipArchive;

class SpreadsheetReaderService
{
    /**
     * Membaca file CSV atau XLSX dan mengembalikan LazyCollection berisi row yang sudah dinormalisasi.
     *
     * @param string $filePath Path file di filesystem
     * @param string|null $originalExtension Ekstensi asli jika file temporary (contoh: 'xlsx', 'csv')
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function read(string $filePath, ?string $originalExtension = null): LazyCollection
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls'])) {
            return LazyCollection::make(function () use ($filePath) {
                foreach ($this->readXlsx($filePath) as $rawRow) {
                    yield $this->normalizeRow($rawRow);
                }
            });
        }

        return LazyCollection::make(function () use ($filePath) {
            foreach ($this->readCsv($filePath) as $rawRow) {
                yield $this->normalizeRow($rawRow);
            }
        });
    }

    /**
     * Generator untuk membaca file XLSX secara cerdas (mendukung multi-sheet dan mengabaikan sheet Pivot/Rekap).
     */
    public function readXlsx(string $filePath): Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Gagal membuka file XLSX: {$filePath}");
        }

        // 1. Baca shared strings
        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xmlString = $zip->getFromName('xl/sharedStrings.xml');
            if ($xmlString) {
                $xml = simplexml_load_string($xmlString);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string) $si->t;
                        } elseif (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $r) {
                                $text .= (string) $r->t;
                            }
                            $sharedStrings[] = $text;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }
        }

        // 2. Baca daftar seluruh sheets dari workbook.xml dan rels
        $sheetsInfo = [];
        $rels = [];
        if ($zip->locateName('xl/_rels/workbook.xml.rels') !== false) {
            $relsXml = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
            if ($relsXml) {
                foreach ($relsXml->Relationship as $rel) {
                    $rels[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        if ($zip->locateName('xl/workbook.xml') !== false) {
            $wbXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
            if ($wbXml && isset($wbXml->sheets->sheet)) {
                foreach ($wbXml->sheets->sheet as $s) {
                    $sheetName = (string) $s['name'];
                    $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = $rels[$rId] ?? '';
                    if ($target && !str_starts_with($target, 'xl/')) {
                        $target = 'xl/' . ltrim($target, '/');
                    }
                    if ($target) {
                        $sheetsInfo[] = [
                            'name' => $sheetName,
                            'target' => $target,
                        ];
                    }
                }
            }
        }

        // Fallback jika rels tidak lengkap
        if (empty($sheetsInfo)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat && str_starts_with($stat['name'], 'xl/worksheets/sheet') && str_ends_with($stat['name'], '.xml')) {
                    $sheetsInfo[] = [
                        'name' => basename($stat['name'], '.xml'),
                        'target' => $stat['name'],
                    ];
                }
            }
        }

        // 3. Evaluasi setiap sheet untuk memprioritaskan sheet data peserta dan mengabaikan sheet Pivot/Rekap
        $candidateSheets = [];
        $keywords = ['pin', 'kodepin', 'ticket', 'nama', 'name', 'phone', 'hp', 'nik', 'ktp', 'cabang', 'toko', 'trx'];

        foreach ($sheetsInfo as $info) {
            $xmlContent = $zip->getFromName($info['target']);
            if (!$xmlContent) continue;

            $sheetXml = simplexml_load_string($xmlContent);
            if (!$sheetXml || !isset($sheetXml->sheetData->row)) continue;

            $totalRows = count($sheetXml->sheetData->row);
            if ($totalRows === 0) continue;

            // Baca baris header (row 1)
            $firstRow = $sheetXml->sheetData->row[0];
            $headerWords = [];
            foreach ($firstRow->c as $c) {
                $type = (string) $c['t'];
                $val = isset($c->v) ? (string) $c->v : '';
                if ($type === 's' && $val !== '') {
                    $val = $sharedStrings[(int) $val] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                }
                $headerWords[] = strtolower(trim($val));
            }

            $score = 0;
            foreach ($headerWords as $hw) {
                $clean = preg_replace('/[^a-z0-9]/', '', $hw);
                foreach ($keywords as $kw) {
                    if (str_contains($clean, $kw)) {
                        $score += 10;
                    }
                }
            }

            $candidateSheets[] = [
                'name' => $info['name'],
                'target' => $info['target'],
                'score' => $score,
                'total_rows' => $totalRows,
                'xml' => $sheetXml,
            ];
        }

        $zip->close();

        if (empty($candidateSheets)) {
            throw new RuntimeException("Tidak ada worksheet yang valid di file Excel.");
        }

        // Urutkan candidate: score tertinggi, lalu total_rows terbanyak
        usort($candidateSheets, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            return $b['total_rows'] <=> $a['total_rows'];
        });

        // Filter hanya sheet data yang valid (score >= 20)
        $validSheets = array_filter($candidateSheets, fn ($s) => $s['score'] >= 20);
        if (empty($validSheets)) {
            $validSheets = [$candidateSheets[0]];
        }

        foreach ($validSheets as $sheetData) {
            $sheetXml = $sheetData['xml'];
            $headers = [];
            $headerRowProcessed = false;

            foreach ($sheetXml->sheetData->row as $row) {
                $rowValues = [];
                foreach ($row->c as $c) {
                    $cellRef = (string) $c['r'];
                    preg_match('/([A-Z]+)/', $cellRef, $matches);
                    $col = $matches[1] ?? '';

                    $type = (string) $c['t'];
                    $val = isset($c->v) ? (string) $c->v : '';

                    if ($type === 's' && $val !== '') {
                        $val = $sharedStrings[(int) $val] ?? '';
                    } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                        $val = (string) $c->is->t;
                    }

                    $rowValues[$col] = $val;
                }

                if (!$headerRowProcessed) {
                    $headers = $rowValues;
                    $headerRowProcessed = true;
                    continue;
                }

                $assoc = [];
                foreach ($headers as $col => $headerName) {
                    $hName = trim($headerName);
                    if ($hName !== '') {
                        $assoc[$hName] = trim($rowValues[$col] ?? '');
                    }
                }

                if (!empty(array_filter($assoc))) {
                    yield $assoc;
                }
            }
        }
    }

    /**
     * Generator untuk membaca file CSV.
     */
    public function readCsv(string $filePath): Generator
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException("Gagal membuka file CSV: {$filePath}");
        }

        $firstLine = fgets($handle);
        if (!$firstLine) {
            fclose($handle);
            return;
        }

        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeader) {
            fclose($handle);
            return;
        }

        // Hapus UTF-8 BOM jika ada
        if (isset($rawHeader[0])) {
            $rawHeader[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/u', '', $rawHeader[0]);
            $rawHeader[0] = str_replace("\xEF\xBB\xBF", '', $rawHeader[0]);
        }

        $header = array_map('trim', $rawHeader);

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($line) === count($header)) {
                $assoc = array_combine($header, array_map('trim', $line));
                if (!empty(array_filter($assoc))) {
                    yield $assoc;
                }
            }
        }

        fclose($handle);
    }

    /**
     * Normalisasi baris mentah dari berbagai format (BRImo, IDM POS, template standar).
     */
    public function normalizeRow(array $rawRow): array
    {
        $getValue = function (array $keys) use ($rawRow): ?string {
            foreach ($keys as $candidate) {
                $cleanCand = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($candidate)));
                foreach ($rawRow as $k => $v) {
                    $cleanK = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($k)));
                    if ($cleanK === $cleanCand) {
                        $val = trim((string) $v);
                        if ($val !== '') {
                            return $val;
                        }
                    }
                }
            }
            return null;
        };

        // 1. PIN Code (Wajib unik untuk assign & claim)
        $pin = $getValue([
            'pin_code', 'kodepin', 'pin', 'ticket number', 'ticketnumber',
            'no tiket', 'nomor tiket', 'kode_pin', 'kode',
        ]);

        // 2. Transaction ID / Booking Code
        $trxId = $getValue([
            'transaction_id', 'kodebooking', 'kode booking', 'trxidtoko',
            'order id', 'order at wita', 'order at wib', 'order_id',
        ]);

        // 3. Nama Pemesan / Pembeli Utama (Buyer Name) vs Nama Tampil di BIB (BIB Name / Runner Name)
        $buyerName = $getValue([
            'buyer name', 'buyer_name', 'nama pembeli', 'nama pemesan',
            'pemesan', 'nama_buyer', 'buyer',
        ]);

        $firstName = $getValue(['first name', 'firstname', 'nama depan']);
        $lastName = $getValue(['last name', 'lastname', 'nama belakang']);
        $runnerName = null;
        if ($firstName || $lastName) {
            $runnerName = trim("{$firstName} {$lastName}");
        }

        $explicitBibName = $getValue([
            'name on bib', 'bib name', 'nama bib', 'nama di bib',
            'nama pelari', 'runner name', 'nama_bib', 'bib_name',
        ]);

        // Resolusi Nama Tampil BIB (bib_name)
        $bibName = $explicitBibName ?: ($runnerName ?: $getValue([
            'nama peserta', 'participant_name', 'nama_peserta',
        ]));

        // Resolusi Nama Pemesan / Pembeli (full_name)
        $fullName = $buyerName ?: $getValue([
            'full_name', 'nama', 'nama_lengkap', 'nama lengkap', 'name',
        ]);

        if (!$fullName) {
            $fullName = $runnerName ?: $bibName;
        }

        if (!$bibName) {
            $bibName = $fullName;
        }

        // Fallback jika nama tidak diisi oleh kasir toko Indomaret
        if (empty($fullName) && !empty($pin)) {
            $fullName = $trxId ? "Peserta Toko ({$trxId})" : "Peserta ({$pin})";
            $bibName = $fullName;
        }

        // 4. ID Card / NIK (Jika tidak ada pada POS IDM, default ke '-')
        $idCard = $getValue([
            'id_card_number', 'nik', 'no_ktp', 'ktp', 'identity_number',
            'id number nik ktp kitas or passport', 'id number', 'no identitas', 'passport',
        ]);
        if (empty($idCard)) {
            $idCard = '-';
        }

        // 5. Nomor Telepon / HP
        $phone = $getValue([
            'phone', 'phone_number', 'no_hp', 'nohp', 'hp', 'telepon',
            'notelp', 'no_telp', 'buyer mobile number', 'no handphone',
        ]);
        if ($phone) {
            $phone = ltrim($phone, "'");
            if (preg_match('/^8[0-9]{7,12}$/', $phone)) {
                $phone = '0' . $phone;
            }
        }

        // 6. Email
        $email = $getValue(['email', 'buyer email', 'email peserta', 'e-mail']);

        // 7. Jenis Kelamin (Gender: L / P)
        $rawGender = mb_strtolower((string) $getValue([
            'gender', 'gender / jenis kelamin', 'gender jenis kelamin',
            'jenis kelamin', 'jk', 'sex', 'jenis_kelamin',
        ]));
        $gender = null;
        if (in_array($rawGender, ['male', 'pria', 'l', 'laki-laki', 'm', 'laki'])) {
            $gender = 'L';
        } elseif (in_array($rawGender, ['female', 'wanita', 'p', 'perempuan', 'f'])) {
            $gender = 'P';
        } elseif ($rawGender !== '') {
            $gender = strtoupper(substr($rawGender, 0, 1));
        }

        // 8. Kategori Lari / Event Class
        $category = $getValue([
            'category', 'category_name', 'kategori', 'ticket type', 'tickettype',
            'namapertunjukan', 'nama pertunjukan', 'ticket class', 'ticket segmentation',
        ]);

        $rawKelas = $getValue(['kelas', 'ticket class']);

        // 9. Ukuran Jersey (Apparel Size / Ukuran Baju)
        $rawJersey = $getValue([
            'apparel size / ukuran baju atau jersey', 'ukuran baju atau jersey',
            'apparel size', 'ukuran jersey', 'ukuran baju', 'jersey size',
            'jersey', 'size', 'ukuran', 'baju', 'ukuran_jersey', 'jersey_size',
        ]);

        $jerseySize = null;
        $candidates = [$rawJersey, $rawKelas, $category, $rawRow['NamaPertunjukan'] ?? null];
        foreach ($candidates as $cand) {
            if (!$cand) continue;
            if (preg_match('/(?:jersey|baju|size|ukuran)\s*[-:]?\s*([A-Z0-9]+)/i', $cand, $m)) {
                $jerseySize = strtoupper(trim($m[1]));
                break;
            }
            $cleanCand = strtoupper(trim($cand));
            if (in_array($cleanCand, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '3XL', '2XL', '4XL', 'ALL SIZE'])) {
                $jerseySize = $cleanCand;
                break;
            }
        }
        if (!$jerseySize && $rawJersey) {
            $jerseySize = strtoupper(trim($rawJersey));
        }

        return [
            'pin_code' => $pin,
            'full_name' => $fullName,
            'bib_name' => $bibName,
            'id_card_number' => $idCard,
            'phone' => $phone,
            'email' => $email,
            'gender' => $gender,
            'jersey_size' => $jerseySize,
            'category' => $category,
            'transaction_id' => $trxId,
            'raw' => $rawRow,
        ];
    }
}
