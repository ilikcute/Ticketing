<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    recentBatches: { type: Array, default: () => [] },
});

const form = useForm({
    file: null,
    event_id: props.events[0]?.id ?? '',
    mode: 'insert_only',
});

const fileName = ref('');
const fileSize = ref('');
const isDragging = ref(false);

function handleFileChange(file) {
    if (!file) return;
    form.file = file;
    fileName.value = file.name;
    fileSize.value = (file.size / 1024).toFixed(1) + ' KB';
}

function handleDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file) handleFileChange(file);
}

function submit() {
    form.post('/import', {
        forceFormData: true,
        onSuccess: () => {
            form.reset('file');
            fileName.value = '';
            fileSize.value = '';
        },
    });
}

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
    <Head title="Import Peserta &amp; Download Template" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-3 font-heading drop-shadow-md">
                        <svg class="w-7 h-7 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Import Peserta &amp; File Template
                    </h2>
                    <p class="text-sm font-semibold text-white/90 mt-1">Upload data registrasi tiket peserta dari file CSV / Excel</p>
                </div>

                <!-- Download Template CSV/Excel Action -->
                <a
                    href="/import/template"
                    download
                    class="px-5 py-3 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-extrabold text-sm uppercase tracking-wider shadow-lg transition flex items-center gap-2 hover:scale-105"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Template CSV/Excel
                </a>
            </div>
        </template>

        <div class="w-full space-y-8">
            <!-- Instructions Card -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-3">
                <div class="flex items-center gap-3 text-[#0E7BDC] font-extrabold text-base font-heading">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Dukungan File Registrasi Peserta (CSV &amp; Excel XLSX)
                </div>
                <p class="text-xs font-semibold text-slate-600 leading-relaxed">
                    Sistem secara otomatis mendeteksi dan mendukung file <strong>Penjualan Tiket BRImo</strong> (<code>.xlsx</code>), file <strong>Penjualan Tiket Indomaret POS</strong> (<code>.xlsx</code>), serta file template standar <strong>CSV / Excel</strong>.
                </p>
                <div class="flex flex-wrap gap-2 text-xs font-mono font-bold">
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0B2A8A] border border-blue-200" title="BRImo / IDM / Standar">🎫 Kode PIN (Ticket Number / KodePIN / pin_code)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0B2A8A] border border-blue-200" title="Nama Lengkap">👤 Nama Peserta (First/Last Name / nama / full_name)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0B2A8A] border border-blue-200" title="No. Telepon">📱 No. HP / WhatsApp (noHP / Phone / phone_number)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0B2A8A] border border-blue-200" title="NIK ID Card">🪪 NIK KTP / SIM (ID Number / identity_number / nik)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0B2A8A] border border-blue-200" title="Kategori Lari">🏃 Kategori (Ticket Type / NamaPertunjukan / category_name)</span>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 sm:p-8 border-2 border-white shadow-xl text-slate-900">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2 font-heading">Pilih Event Lari</label>
                        <select
                            v-model="form.event_id"
                            class="w-full px-4 py-3 rounded-2xl bg-white border-2 border-slate-300 text-slate-900 text-base font-medium focus:outline-none focus:border-[#0E7BDC] focus:ring-0 shadow-sm"
                        >
                            <option v-for="evt in events" :key="evt.id" :value="evt.id">{{ evt.name }}</option>
                        </select>
                    </div>

                    <!-- Drag &amp; Drop Zone -->
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2 font-heading">File CSV / Spreadsheet</label>
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'relative border-2 border-dashed rounded-3xl p-8 text-center transition cursor-pointer',
                                isDragging ? 'border-[#0E7BDC] bg-blue-50' : 'border-slate-300 hover:border-[#0E7BDC] bg-slate-50'
                            ]"
                            @click="$refs.fileInput.click()"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".csv,.xlsx,.xls"
                                class="hidden"
                                @change="handleFileChange($event.target.files[0])"
                            />

                            <div v-if="!fileName" class="space-y-2">
                                <div class="w-14 h-14 rounded-2xl bg-blue-100 border border-blue-200 text-[#0E7BDC] flex items-center justify-center mx-auto">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                </div>
                                <div class="text-base font-extrabold text-slate-900 font-heading">Klik untuk upload atau drag &amp; drop file ke sini</div>
                                <div class="text-xs font-semibold text-slate-500">Mendukung format .CSV, .XLSX, atau .XLS (Maks 10MB)</div>
                            </div>

                            <div v-else class="flex items-center justify-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-sm">CSV</div>
                                <div class="text-left">
                                    <div class="text-base font-extrabold text-slate-900 font-heading">{{ fileName }}</div>
                                    <div class="text-xs font-bold text-slate-500">{{ fileSize }}</div>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.file" class="text-rose-600 text-xs font-bold mt-1.5">{{ form.errors.file }}</p>
                    </div>

                    <!-- Mode Radio -->
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-2 font-heading">Mode Import Data</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                :class="[
                                    'p-4 rounded-2xl border-2 cursor-pointer transition flex items-start gap-3',
                                    form.mode === 'insert_only' ? 'bg-blue-50 border-[#0E7BDC] text-slate-900' : 'bg-slate-50 border-slate-200 text-slate-600'
                                ]"
                            >
                                <input type="radio" v-model="form.mode" value="insert_only" class="mt-0.5 text-[#0E7BDC] focus:ring-[#0E7BDC]" />
                                <div>
                                    <div class="text-xs font-extrabold text-slate-900 font-heading">Insert Only (Aman)</div>
                                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Lewati baris peserta jika kode PIN sudah ada di database.</div>
                                </div>
                            </label>

                            <label
                                :class="[
                                    'p-4 rounded-2xl border-2 cursor-pointer transition flex items-start gap-3',
                                    form.mode === 'update_existing' ? 'bg-blue-50 border-[#0E7BDC] text-slate-900' : 'bg-slate-50 border-slate-200 text-slate-600'
                                ]"
                            >
                                <input type="radio" v-model="form.mode" value="update_existing" class="mt-0.5 text-[#0E7BDC] focus:ring-[#0E7BDC]" />
                                <div>
                                    <div class="text-xs font-extrabold text-slate-900 font-heading">Update Existing</div>
                                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">Timpa data nama/kategori jika kode PIN sudah ada sebelumnya.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.file"
                        class="w-full py-4 px-6 rounded-full bg-gradient-to-r from-[#0E7BDC] via-[#27A9F6] to-[#0B2A8A] hover:from-[#0B2A8A] hover:to-[#0E7BDC] text-white font-extrabold text-sm uppercase tracking-wider shadow-xl shadow-blue-500/25 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center gap-2 font-heading"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Memproses File Import...' : 'Mulai Process Import Peserta' }}</span>
                    </button>
                </form>
            </div>

            <!-- Recent Batches Table -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900">
                <h3 class="text-base font-extrabold text-slate-900 mb-4 font-heading">Riwayat Batch Import Terakhir</h3>
                <div v-if="recentBatches.length" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-800">
                        <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-200">
                            <tr>
                                <th class="px-3.5 py-3 rounded-l-xl">Waktu</th>
                                <th class="px-3.5 py-3">Nama File</th>
                                <th class="px-3.5 py-3">Sukses</th>
                                <th class="px-3.5 py-3">Duplikat</th>
                                <th class="px-3.5 py-3">Gagal</th>
                                <th class="px-3.5 py-3 rounded-r-xl">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="batch in recentBatches" :key="batch.id" class="hover:bg-blue-50/60 transition">
                                <td class="px-3.5 py-3 font-mono font-semibold text-slate-500">{{ formatDate(batch.created_at) }}</td>
                                <td class="px-3.5 py-3 font-bold text-slate-900">{{ batch.file_name }}</td>
                                <td class="px-3.5 py-3 text-emerald-600 font-extrabold">{{ batch.success_count }}</td>
                                <td class="px-3.5 py-3 text-amber-600 font-extrabold">{{ batch.skipped_duplicate_count }}</td>
                                <td class="px-3.5 py-3 text-rose-600 font-extrabold">{{ batch.failed_count }}</td>
                                <td class="px-3.5 py-3">
                                    <Link :href="`/import/${batch.id}/result`" class="text-[#0E7BDC] hover:text-[#0B2A8A] font-extrabold underline">
                                        Lihat Hasil
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-6 text-slate-400 text-xs font-medium">Belum ada riwayat batch import.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
