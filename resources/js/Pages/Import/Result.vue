<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    batch: { type: Object, required: true },
    errorsList: { type: Array, default: () => [] },
    duplicateCount: { type: Number, default: 0 },
    missingCount: { type: Number, default: 0 },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const activeTab = ref('all'); // 'all', 'duplicate', 'missing'

const filteredErrors = computed(() => {
    if (activeTab.value === 'duplicate') {
        return props.errorsList.filter(e => e.reason === 'duplicate_pin');
    }
    if (activeTab.value === 'missing') {
        return props.errorsList.filter(e => e.reason !== 'duplicate_pin');
    }
    return props.errorsList;
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    }) + ' WIB';
}
</script>

<template>
    <Head title="Hasil Import &amp; Laporan Rekap Peserta" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-3 font-heading drop-shadow-md">
                        <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Hasil Import &amp; Laporan Rekap Tiket
                    </h2>
                    <p class="text-sm font-semibold text-white/90 mt-1">
                        File: <strong class="text-yellow-300 font-mono">{{ batch.file_name }}</strong> &bull; Waktu: {{ formatDate(batch.created_at) }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/import"
                        class="px-5 py-3 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-extrabold text-sm uppercase tracking-wider shadow-lg transition flex items-center gap-2 hover:scale-105 font-heading"
                    >
                        &larr; Upload File Lain
                    </Link>
                    <Link
                        href="/dashboard"
                        class="px-5 py-3 rounded-full bg-white/20 hover:bg-white/30 text-white font-extrabold text-sm uppercase tracking-wider border border-white/30 backdrop-blur-md transition flex items-center gap-2 hover:scale-105 font-heading"
                    >
                        Lihat Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <div class="w-full space-y-6">
            <!-- Flash Notification Banner -->
            <div v-if="flashSuccess" class="bg-emerald-50 border-2 border-emerald-300 rounded-3xl p-5 shadow-xl flex items-center gap-4 text-emerald-900 font-heading">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center font-black text-2xl shrink-0 shadow-md">
                    ✓
                </div>
                <div>
                    <h3 class="text-base font-black uppercase text-emerald-800">Proses Import Selesai!</h3>
                    <p class="text-xs font-bold text-emerald-700 mt-0.5">{{ flashSuccess }}</p>
                </div>
            </div>

            <!-- Summary Metric Cards (White Glass Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
                <!-- Total Rows -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 transition-all">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 font-heading">Total Baris File</span>
                    <div class="text-4xl font-extrabold text-[#0B2A8A] font-bib mt-2">{{ batch.total_rows }}</div>
                    <p class="text-xs font-bold text-slate-400 mt-1">Total data di file spreadsheet</p>
                </div>

                <!-- Success Count -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 transition-all">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 font-heading">Berhasil Masuk DB</span>
                    <div class="text-4xl font-extrabold text-emerald-600 font-bib mt-2">{{ batch.success_count }}</div>
                    <p class="text-xs font-bold text-emerald-600 mt-1">Tiket siap diclaim di Loket</p>
                </div>

                <!-- Skipped Duplicate -->
                <div
                    @click="activeTab = 'duplicate'"
                    :class="[
                        'bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 shadow-xl text-slate-900 transition-all cursor-pointer hover:scale-105',
                        duplicateCount > 0 ? 'border-amber-400 ring-2 ring-amber-300/50' : 'border-white'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-700 font-heading">Duplikat PIN (Dilewati)</span>
                        <span v-if="duplicateCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 uppercase">Perlu Laporan</span>
                    </div>
                    <div class="text-4xl font-extrabold text-amber-600 font-bib mt-2">{{ batch.skipped_duplicate_count || duplicateCount }}</div>
                    <p class="text-xs font-bold text-amber-600 mt-1">Klik untuk lihat rincian PIN ganda</p>
                </div>

                <!-- Failed Count -->
                <div
                    @click="activeTab = 'missing'"
                    :class="[
                        'bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 shadow-xl text-slate-900 transition-all cursor-pointer hover:scale-105',
                        missingCount > 0 ? 'border-rose-400 ring-2 ring-rose-300/50' : 'border-white'
                    ]"
                >
                    <span class="text-xs font-extrabold uppercase tracking-wider text-rose-700 font-heading">Gagal / Format Salah</span>
                    <div class="text-4xl font-extrabold text-rose-600 font-bib mt-2">{{ batch.failed_count }}</div>
                    <p class="text-xs font-bold text-rose-600 mt-1">Data tidak lengkap / format salah</p>
                </div>
            </div>

            <!-- Detailed Error Log & Reporting Panel -->
            <div v-if="errorsList.length" class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 font-heading flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Laporan Rincian Masalah &amp; PIN Duplikat ({{ errorsList.length }} Record)
                        </h3>
                        <p class="text-xs font-semibold text-slate-500 mt-0.5">Daftar kode PIN ganda atau data yang memerlukan tindakan / pelaporan.</p>
                    </div>

                    <!-- Action Export Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a
                            v-if="duplicateCount > 0"
                            :href="`/import/${batch.id}/duplicates/download`"
                            download
                            class="px-4 py-2.5 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-black text-xs uppercase tracking-wider shadow-md transition flex items-center gap-2 font-heading hover:scale-105"
                            title="Download Excel/CSV khusus berisi rekap data PIN yang ganda / duplikat"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download Rekap PIN Duplikat ({{ duplicateCount }})
                        </a>

                        <a
                            :href="`/import/${batch.id}/errors/download`"
                            download
                            class="px-4 py-2.5 rounded-full bg-slate-800 hover:bg-slate-900 text-white font-extrabold text-xs uppercase tracking-wider shadow-md transition flex items-center gap-2 font-heading"
                            title="Download semua rincian error log dalam format CSV"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Semua Log Error (.CSV)
                        </a>
                    </div>
                </div>

                <!-- Tabs Filter -->
                <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                    <button
                        @click="activeTab = 'all'"
                        :class="[
                            'px-4 py-2 rounded-2xl text-xs font-extrabold transition font-heading',
                            activeTab === 'all' ? 'bg-[#0E7BDC] text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        Semua Masalah ({{ errorsList.length }})
                    </button>
                    <button
                        @click="activeTab = 'duplicate'"
                        :class="[
                            'px-4 py-2 rounded-2xl text-xs font-extrabold transition font-heading flex items-center gap-1.5',
                            activeTab === 'duplicate' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        <span>⚠️ Khusus PIN Duplikat</span>
                        <span class="px-2 py-0.5 rounded-full bg-white/20 text-xs font-black">{{ duplicateCount }}</span>
                    </button>
                    <button
                        @click="activeTab = 'missing'"
                        :class="[
                            'px-4 py-2 rounded-2xl text-xs font-extrabold transition font-heading flex items-center gap-1.5',
                            activeTab === 'missing' ? 'bg-rose-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        <span>❌ Data Gagal / Tidak Lengkap</span>
                        <span class="px-2 py-0.5 rounded-full bg-white/20 text-xs font-black">{{ missingCount }}</span>
                    </button>
                </div>

                <!-- Interactive Table -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-xs text-slate-800">
                        <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-3">No. Baris</th>
                                <th class="px-3 py-3">Kode PIN</th>
                                <th class="px-3 py-3">Nama di BIB (Pelari)</th>
                                <th class="px-3 py-3">Nama Pemesan</th>
                                <th class="px-3 py-3">Jersey</th>
                                <th class="px-3 py-3">No. HP / WA</th>
                                <th class="px-3 py-3">No. Booking / Trx</th>
                                <th class="px-3 py-3">Jenis Masalah</th>
                                <th class="px-3 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="err in filteredErrors" :key="err.id" class="hover:bg-slate-50 transition">
                                <td class="px-3 py-3 font-mono font-bold text-slate-600">
                                    {{ err.row_number > 0 ? '#' + err.row_number : '-' }}
                                </td>
                                <td class="px-3 py-3 font-mono font-black text-[#0B2A8A]">
                                    <span class="px-2 py-1 rounded bg-blue-50 border border-blue-200">
                                        {{ err.pin_code }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 font-extrabold text-slate-900">
                                    {{ err.bib_name || err.full_name }}
                                </td>
                                <td class="px-3 py-3 text-slate-600 font-semibold">
                                    {{ err.full_name }}
                                </td>
                                <td class="px-3 py-3 font-mono font-bold">
                                    <span v-if="err.jersey_size && err.jersey_size !== '-'" class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-900 border border-yellow-300 text-[10px]">
                                        {{ err.jersey_size }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-3 py-3 font-mono text-slate-600">
                                    {{ err.phone }}
                                </td>
                                <td class="px-3 py-3 font-mono text-slate-600">
                                    {{ err.transaction_id }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border',
                                            err.reason === 'duplicate_pin'
                                                ? 'bg-amber-100 text-amber-800 border-amber-300'
                                                : 'bg-rose-100 text-rose-800 border-rose-300'
                                        ]"
                                    >
                                        {{ err.reason_label || err.reason }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-600 text-[11px]">
                                    {{ err.message }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="bg-emerald-500/10 border-2 border-emerald-500/30 rounded-3xl p-6 text-center text-emerald-800 font-extrabold text-sm font-heading">
                🎉 Luar Biasa! Semua <strong>{{ batch.success_count }} record</strong> peserta dalam file ini telah berhasil diimport ke dalam sistem tanpa ada duplikat atau error!
            </div>
        </div>
    </AuthenticatedLayout>
</template>
