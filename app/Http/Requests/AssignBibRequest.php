<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignBibRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' || $this->user()?->role === 'loket';
    }

    public function rules(): array
    {
        return [
            'pin_code' => ['required', 'string', 'exists:participants,pin_code'],
            'bib_number' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'identity_confirmed' => ['required', 'accepted'], // checkbox FR-07: identitas sudah dicocokkan manual
        ];
    }

    public function messages(): array
    {
        return [
            'pin_code.required' => 'Kode PIN wajib diisi.',
            'pin_code.exists' => 'PIN/barcode tidak ditemukan di database.',
            'bib_number.required' => 'Nomor BIB wajib diisi dan tidak boleh kosong.',
            'bib_number.regex' => 'Nomor BIB hanya boleh berisi karakter angka (digit 0-9), tidak boleh mengandung huruf atau karakter khusus.',
            'bib_number.max' => 'Nomor BIB maksimal 20 digit.',
            'identity_confirmed.accepted' => 'Wajib konfirmasi kartu identitas sudah dicocokkan secara manual.',
        ];
    }
}
