<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';

const searchInput = ref(null);
const bibInput = ref(null);
const searchKeyword = ref('');
const searchType = ref('all'); // 'all', 'pin', 'phone', 'id_card'

const participant = ref(null);
const searchResults = ref([]);
const suggestedBib = ref('');
const bibNumber = ref('');
const identityConfirmed = ref(true);
const errorMessage = ref('');
const successFlash = ref('');
const loading = ref(false);

const isClaimed = ref(false);
const claimedByName = ref('');
const claimedAtFormatted = ref('');
const claimedDevice = ref('');

// Modal Reset / Sengketa PIN
const showResetModal = ref(false);
const adminPassword = ref('');
const resetReason = ref('Pemeriksaan fisik KTP/SIM cocok, klaim awal tidak sah / salah input');
const resetError = ref('');
const resetSubmitting = ref(false);

const searchTypePills = [
    { key: 'all', label: 'Semua (Auto)', icon: '🔍' },
    { key: 'pin', label: 'Kode PIN', icon: '🎫' },
    { key: 'phone', label: 'No. Telp / WA', icon: '📱' },
    { key: 'id_card', label: 'NIK / ID Card', icon: '🪪' },
];

const dynamicPlaceholder = computed(() => {
    switch (searchType.value) {
        case 'pin':
            return 'SCAN PIN ATAU INPUT KODE PIN...';
        case 'phone':
            return 'INPUT NOMOR TELEPON / WA PESERTA (CONTOH: 0812...)...';
        case 'id_card':
            return 'INPUT NIK / NO. KTP PESERTA (16 DIGIT)...';
        default:
            return 'SCAN PIN / NO. HP (08xx) / NIK KTP (16 DIGIT)...';
    }
});

function focusInput() {
    nextTick(() => searchInput.value?.focus());
}

function setSearchType(type) {
    searchType.value = type;
    focusInput();
}

onMounted(() => {
    focusInput();
});

async function handleSearch(keywordOverride = null, typeOverride = null) {
    const keyword = (keywordOverride !== null ? keywordOverride : searchKeyword.value).trim();
    const type = typeOverride !== null ? typeOverride : searchType.value;

    if (!keyword) return;

    loading.value = true;
    errorMessage.value = '';
    successFlash.value = '';
    participant.value = null;
    searchResults.value = [];
    isClaimed.value = false;

    try {
        const url = `/loket/lookup?q=${encodeURIComponent(keyword)}&type=${encodeURIComponent(type)}`;
        const res = await fetch(url);
        const data = await res.json();

        if (!res.ok) {
            errorMessage.value = data.message || 'Data tidak ditemukan di database.';
            focusInput();
            return;
        }

        if (data.matched_count === 1 && data.participant) {
            participant.value = data.participant;
            isClaimed.value = data.is_claimed || false;
            claimedByName.value = data.claimed_by_name || 'Petugas Loket';
            claimedAtFormatted.value = data.claimed_at_formatted || '-';
            claimedDevice.value = data.claimed_device || '-';

            suggestedBib.value = data.suggested_bib || '';
            bibNumber.value = data.participant?.bib_number || data.suggested_bib || '';
            identityConfirmed.value = true;

            if (!isClaimed.value) {
                nextTick(() => bibInput.value?.focus());
            }
        } else if (data.matched_count > 1 && Array.isArray(data.participants)) {
            searchResults.value = data.participants;
        }
    } catch {
        errorMessage.value = 'Terjadi kesalahan koneksi ke server.';
        focusInput();
    } finally {
        loading.value = false;
    }
}

function selectParticipant(p) {
    searchKeyword.value = p.pin_code;
    handleSearch(p.pin_code, 'pin');
}

function submitAssign() {
    if (!participant.value || isClaimed.value || !identityConfirmed.value || !bibNumber.value) return;

    router.post('/loket/assign', {
        pin_code: participant.value.pin_code,
        bib_number: bibNumber.value,
        identity_confirmed: identityConfirmed.value,
    }, {
        onSuccess: () => {
            successFlash.value = `BIB #${bibNumber.value} &rarr; ${participant.value?.full_name.toUpperCase()} BERHASIL DIASIGN!`;
            resetForm();
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0];
        },
    });
}

function submitResetClaim() {
    if (!participant.value || !adminPassword.value || !resetReason.value) {
        resetError.value = 'Password Admin dan Alasan Reset wajib diisi.';
        return;
    }

    resetSubmitting.value = true;
    resetError.value = '';

    router.post('/loket/reset-claim', {
        pin_code: participant.value.pin_code,
        admin_password: adminPassword.value,
        reason: resetReason.value,
    }, {
        onSuccess: () => {
            showResetModal.value = false;
            adminPassword.value = '';
            successFlash.value = `SENGKETA DISETUJUI: PIN ${participant.value?.pin_code} berhasil di-reset oleh Admin! Silakan assign Nomor BIB baru.`;
            handleSearch(participant.value?.pin_code, 'pin');
        },
        onError: (errors) => {
            resetError.value = Object.values(errors)[0] || 'Password Admin tidak valid.';
        },
        onFinish: () => {
            resetSubmitting.value = false;
        }
    });
}

function openResetModal() {
    adminPassword.value = '';
    resetError.value = '';
    showResetModal.value = true;
}

function resetForm() {
    searchKeyword.value = '';
    participant.value = null;
    searchResults.value = [];
    bibNumber.value = '';
    isClaimed.value = false;
    identityConfirmed.value = true;
    errorMessage.value = '';
    showResetModal.value = false;
    focusInput();
}
</script>

<template>
    <Head title="Loket POS Penukaran BIB" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between py-0.5">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-white leading-tight font-heading drop-shadow-md">
                            POS Loket Penukaran Race Pack
                        </h2>
                        <p class="text-xs font-semibold text-white/90">Pencarian Fleksibel via PIN, Nomor Telepon, atau NIK KTP</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-white font-mono bg-black/30 border border-white/20 px-3 py-1.5 rounded-full backdrop-blur-md">
                        STATUS: <span class="text-emerald-300 font-extrabold">ONLINE</span>
                    </span>
                    <button
                        v-if="participant || searchResults.length > 0"
                        @click="resetForm"
                        class="text-xs bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] px-4 py-1.5 rounded-full font-extrabold shadow-lg transition flex items-center gap-1.5"
                    >
                        <span>🔄</span> Reset / Cari Baru
                    </button>
                </div>
            </div>
        </template>

        <!-- Container Utama (Full Width Widescreen POS Layout) -->
        <div class="w-full space-y-5">
            <!-- Toast Flash Notification -->
            <div v-if="successFlash" class="p-4 rounded-2xl bg-emerald-500 text-white flex items-center justify-between shadow-xl text-xs font-bold">
                <div class="flex items-center gap-2">
                    <span class="text-lg">✅</span>
                    <span class="font-extrabold tracking-wide uppercase" v-html="successFlash"></span>
                </div>
                <button @click="successFlash = ''" class="text-white hover:text-emerald-200 font-bold text-sm">✕</button>
            </div>

            <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-600 text-white flex items-center justify-between shadow-xl text-xs font-bold">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⚠️</span>
                    <span class="font-extrabold">{{ errorMessage }}</span>
                </div>
                <button @click="errorMessage = ''" class="text-white hover:text-rose-200 font-bold text-sm">✕</button>
            </div>

            <!-- GRID 2-KOLOM COMPACT POS (Full Width) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- KOLOM KIRI (5 cols): SCANNER & FORM ASSIGN -->
                <div class="lg:col-span-5 space-y-5">
                    
                    <!-- Panel Scanner PIN / Telepon / NIK -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-3.5">
                        
                        <!-- Search Filter Tabs -->
                        <div class="flex items-center justify-between gap-1 bg-slate-100 p-1 rounded-2xl border border-slate-200">
                            <button
                                v-for="pill in searchTypePills"
                                :key="pill.key"
                                type="button"
                                @click="setSearchType(pill.key)"
                                :class="[
                                    'flex-1 py-1.5 px-2 rounded-xl text-[11px] font-extrabold transition flex items-center justify-center gap-1 font-heading',
                                    searchType === pill.key
                                        ? 'bg-[#0E7BDC] text-white shadow-md'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                                ]"
                            >
                                <span>{{ pill.icon }}</span>
                                <span class="hidden sm:inline">{{ pill.label }}</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-[#0E7BDC] font-heading flex items-center gap-1.5">
                                <span>🔍</span>
                                <span v-if="searchType === 'pin'">Input / Scan Barcode PIN</span>
                                <span v-else-if="searchType === 'phone'">Input Nomor Telepon (phone)</span>
                                <span v-else-if="searchType === 'id_card'">Input NIK ID Card (id_card_number)</span>
                                <span v-else>Cari via PIN, No. HP, atau NIK</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-mono font-bold">Tekan ENTER</span>
                        </div>

                        <form @submit.prevent="handleSearch()" class="relative">
                            <input
                                ref="searchInput"
                                v-model="searchKeyword"
                                type="text"
                                autofocus
                                class="w-full text-base sm:text-lg font-bib tracking-wider bg-white border-2 border-slate-300 focus:border-[#0E7BDC] text-slate-900 rounded-2xl px-4 py-3 pl-11 pr-24 shadow-sm focus:outline-none focus:ring-0 uppercase placeholder-slate-400"
                                :placeholder="dynamicPlaceholder"
                                autocomplete="off"
                            />
                            <svg class="w-5 h-5 text-[#0E7BDC] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button
                                type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2 bg-[#0E7BDC] hover:bg-blue-600 text-white text-xs font-extrabold rounded-xl transition shadow-md uppercase font-heading flex items-center gap-1"
                                :disabled="loading"
                            >
                                <span v-if="loading" class="animate-spin">⏳</span>
                                <span>{{ loading ? '...' : 'Cari' }}</span>
                            </button>
                        </form>

                        <div class="flex items-center gap-2 text-[10px] font-semibold text-slate-500 pt-0.5">
                            <span class="bg-blue-50 text-[#0E7BDC] px-2 py-0.5 rounded-md font-bold border border-blue-200">Tips</span>
                            <span>Bisa input kode PIN struk, Nomor HP (08xx), atau NIK KTP (16 digit).</span>
                        </div>
                    </div>

                    <!-- Panel Jika Ditemukan Beberapa Peserta (Multi-Match) -->
                    <div v-if="searchResults.length > 0 && !participant" class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-amber-300 shadow-xl text-slate-900 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <div class="flex items-center gap-1.5 text-amber-700 font-black text-xs uppercase tracking-wider font-heading">
                                <span>📋</span>
                                <span>Ditemukan {{ searchResults.length }} Peserta</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500">Pilih peserta yang sesuai:</span>
                        </div>

                        <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                            <div
                                v-for="res in searchResults"
                                :key="res.id"
                                class="p-3.5 rounded-2xl border transition bg-slate-50 hover:bg-blue-50/70 border-slate-200 hover:border-[#0E7BDC] space-y-2.5"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <!-- Nama Peserta -->
                                        <div class="font-extrabold text-sm text-slate-900 font-heading leading-tight">
                                            {{ res.bib_name || res.full_name }}
                                        </div>
                                        <!-- Info Pembeli jika berbeda -->
                                        <div v-if="res.bib_name && res.full_name && res.bib_name !== res.full_name" class="text-[10px] text-slate-500 font-semibold mt-0.5">
                                            Pemesan: <strong class="text-slate-700">{{ res.full_name }}</strong>
                                        </div>
                                        <div v-if="res.jersey_size" class="mt-1">
                                            <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-900 border border-yellow-300 font-black text-[10px] font-mono">
                                                👕 JERSEY: {{ res.jersey_size }}
                                            </span>
                                        </div>
                                    </div>
                                    <span
                                        :class="res.is_claimed ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border shrink-0"
                                    >
                                        {{ res.is_claimed ? 'SUDAH KLAIM' : 'SIAP ASSIGN' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200">
                                    <div>
                                        <span class="text-[9px] block text-slate-400 font-sans font-bold uppercase">📱 No. HP:</span>
                                        <strong class="text-slate-800">{{ res.phone || '-' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-[9px] block text-slate-400 font-sans font-bold uppercase">✉️ Email:</span>
                                        <strong class="text-slate-800 truncate block">{{ res.email || '-' }}</strong>
                                    </div>
                                </div>

                                <button
                                    @click="selectParticipant(res)"
                                    class="w-full py-2 px-3 bg-[#0E7BDC] hover:bg-blue-600 text-white rounded-xl font-extrabold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 font-heading"
                                >
                                    <span>Pilih &amp; Proses Peserta Ini</span>
                                    <span>&rarr;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Assign BIB (Jika peserta siap assign) -->
                    <div v-if="participant && !isClaimed" class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <span class="text-xs font-extrabold uppercase text-emerald-600 tracking-wider font-heading">⚡ Quick Assign BIB</span>
                            <span class="text-xs text-slate-500 font-semibold">Saran: <strong class="text-emerald-600 font-bib text-sm">{{ suggestedBib }}</strong></span>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Nomor BIB Yang Di-Assign</label>
                            <input
                                ref="bibInput"
                                v-model="bibNumber"
                                type="text"
                                @keyup.enter="submitAssign"
                                class="w-full text-3xl font-bib font-black text-center tracking-widest bg-slate-50 border-2 border-emerald-500 text-emerald-600 rounded-2xl px-4 py-2.5 focus:outline-none focus:ring-4 focus:ring-emerald-200"
                                placeholder="Nomor BIB..."
                            />
                        </div>

                        <label class="flex items-center gap-2 p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                v-model="identityConfirmed"
                                class="rounded bg-white border-emerald-500 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                            />
                            <span class="font-extrabold text-xs">Data Peserta Sesuai &amp; Terverifikasi</span>
                        </label>

                        <button
                            @click="submitAssign"
                            :disabled="!identityConfirmed || !bibNumber"
                            class="w-full py-3.5 px-4 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-extrabold text-sm uppercase tracking-wider shadow-lg shadow-emerald-500/25 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center gap-2 font-heading"
                        >
                            <span>✓ Konfirmasi &amp; Cetak Struk</span>
                        </button>
                    </div>

                    <!-- Tombol Reset Sengketa (Jika PIN Sudah Claimed) -->
                    <div v-else-if="participant && isClaimed" class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-3">
                        <div class="text-xs font-extrabold text-amber-700 flex items-center gap-1.5 font-heading">
                            <span>⚠️</span> PIN Ini Terdaftar Sudah Ditukar
                        </div>
                        <p class="text-xs font-semibold text-slate-600 leading-relaxed">
                            Jika peserta di hadapan Anda memiliki bukti fisik &amp; nota bayar asli (sengketa/salah klaim awal), lakukan otorisasi reset di bawah:
                        </p>
                        <button
                            @click="openResetModal"
                            class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs uppercase rounded-full transition shadow-lg flex items-center justify-center gap-1.5 tracking-wider font-heading"
                        >
                            <span>🔐 Otorisasi Admin: Reset Sengketa PIN</span>
                        </button>
                    </div>

                    <!-- Tips Petugas -->
                    <div v-else-if="searchResults.length === 0" class="p-6 rounded-3xl bg-white/90 border-2 border-white text-slate-700 text-xs space-y-2 shadow-xl">
                        <div class="font-extrabold text-slate-900 font-heading text-sm">💡 Tips Pencarian Cepat:</div>
                        <ul class="list-disc list-inside space-y-1 text-xs font-medium text-slate-600">
                            <li><strong>Kode PIN:</strong> Scan barcode struk atau ketik kode PIN (contoh: <code>TIX-...</code>).</li>
                            <li><strong>Nomor HP (phone):</strong> Ketik nomor telepon peserta (contoh: <code>08123456789</code>).</li>
                            <li><strong>Nama:</strong> Ketik nama pelari atau nama pembeli tiket.</li>
                            <li>Tekan <kbd class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded font-mono text-[10px] font-bold">ENTER</kbd> untuk pencarian instan.</li>
                        </ul>
                    </div>
                </div>

                <!-- KOLOM KANAN (7 cols): RINCIAN DATA PESERTA -->
                <div class="lg:col-span-7">
                    
                    <div v-if="participant" class="bg-white/95 backdrop-blur-xl rounded-3xl p-6 border-2 border-white shadow-xl text-slate-900 space-y-4">
                        
                        <!-- Alert Banner Sengketa / Already Claimed -->
                        <div v-if="isClaimed" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">⚠️</span>
                                <div>
                                    <div class="font-extrabold text-xs text-amber-800 uppercase tracking-wider font-heading">STATUS: SUDAH DITUKAR (CLAIMED)</div>
                                    <div class="text-xs text-amber-700 font-medium mt-0.5">
                                        Oleh: <strong>{{ claimedByName }}</strong> &bull; Waktu: <span class="font-mono font-bold text-slate-900">{{ claimedAtFormatted }}</span> ({{ claimedDevice }})
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs bg-white p-2.5 rounded-xl border border-amber-200 font-mono text-[#0B2A8A] font-bold flex justify-between items-center">
                                <span>BIB Terdaftar: #{{ participant.bib_number }}</span>
                                <span class="text-amber-700 text-[10px] uppercase font-mono font-bold">Dapat Di-reset dengan Password Admin</span>
                            </div>
                        </div>

                        <!-- Header Info Peserta (Nama & Ukuran) -->
                        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#0B2A8A] to-[#0E7BDC] text-white font-black flex items-center justify-center text-2xl font-heading shadow-md">
                                    {{ (participant.bib_name || participant.full_name).charAt(0) }}
                                </div>
                                <div>
                                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-[#0E7BDC] font-heading flex items-center gap-2">
                                        <span>Nama Peserta</span>
                                        <span v-if="participant.jersey_size" class="px-2.5 py-0.5 rounded-full bg-[#FFD400] text-[#0B2A8A] font-black text-[11px] font-mono shadow-sm">
                                            👕 UKURAN: {{ participant.jersey_size }}
                                        </span>
                                    </div>
                                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight font-heading mt-0.5">
                                        {{ participant.bib_name || participant.full_name }}
                                    </h3>
                                    <div v-if="participant.bib_name && participant.full_name && participant.bib_name !== participant.full_name" class="text-xs text-slate-500 font-semibold mt-1">
                                        Pemesan / Pembeli Tiket: <strong class="text-slate-800">{{ participant.full_name }}</strong>
                                    </div>
                                </div>
                            </div>
                            <span
                                :class="isClaimed ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300'"
                                class="px-4 py-1.5 rounded-full text-xs font-extrabold border shrink-0"
                            >
                                {{ isClaimed ? 'SUDAH DITUKAR' : 'SIAP ASSIGN' }}
                            </span>
                        </div>

                        <!-- Grid Rincian: Hanya Nama, Ukuran Jersey, Nomor HP, dan Email -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-1">
                            <!-- 1. Ukuran Jersey -->
                            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 p-4 rounded-2xl border-2 border-yellow-400 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="block text-[11px] uppercase font-black text-amber-900 tracking-wider font-heading">👕 Ukuran Jersey</span>
                                    <span class="text-[9px] bg-yellow-200 text-yellow-900 font-black px-2 py-0.5 rounded-full font-mono">SERAHKAN</span>
                                </div>
                                <div class="text-2xl font-black text-amber-950 font-mono tracking-wide mt-1">
                                    {{ participant.jersey_size || '-' }}
                                </div>
                            </div>

                            <!-- 2. Nomor HP / WhatsApp -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex flex-col justify-between">
                                <span class="block text-[10px] uppercase font-extrabold text-slate-500 tracking-wider font-heading mb-1">📱 Nomor HP / WA</span>
                                <div class="text-base font-extrabold text-slate-900 font-mono mt-1">
                                    {{ participant.phone || '-' }}
                                </div>
                            </div>

                            <!-- 3. Alamat Email -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex flex-col justify-between">
                                <span class="block text-[10px] uppercase font-extrabold text-slate-500 tracking-wider font-heading mb-1">✉️ Alamat Email</span>
                                <div class="text-sm font-bold text-slate-800 font-mono truncate mt-1" :title="participant.email">
                                    {{ participant.email || '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- State Menunggu / Multiple Results Hint -->
                    <div v-else-if="searchResults.length > 0" class="glass-card rounded-2xl p-8 border border-slate-800/80 text-center text-slate-300 space-y-2">
                        <div class="text-4xl">👥</div>
                        <div class="text-sm font-bold text-white">Silakan Pilih Salah Satu Peserta</div>
                        <p class="text-xs max-w-sm mx-auto text-slate-400">
                            Pencarian menghasilkan {{ searchResults.length }} peserta. Klik tombol <strong>Pilih &amp; Proses Peserta Ini</strong> pada kolom sebelah kiri untuk memproses nomor BIB.
                        </p>
                    </div>

                    <!-- State Idle -->
                    <div v-else class="glass-card rounded-2xl p-8 border border-slate-800/80 text-center text-slate-500 space-y-2">
                        <div class="text-4xl">🏷️</div>
                        <div class="text-sm font-bold text-slate-400">Menunggu Input / Scan</div>
                        <p class="text-xs max-w-xs mx-auto">Silakan posisikan barcode di depan scanner atau ketikkan kode PIN / No. Telepon / NIK pada kolom sebelah kiri.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================= -->
        <!-- MODAL OTORISASI ADMIN: RESET CLAIM PIN   -->
        <!-- ========================================= -->
        <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-amber-400">
                        <span class="text-xl">🔐</span>
                        <h3 class="font-extrabold text-base text-white">Otorisasi Admin - Reset Sengketa PIN</h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-white font-bold">✕</button>
                </div>

                <div v-if="resetError" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold">
                    ⚠️ {{ resetError }}
                </div>

                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 text-xs space-y-1">
                    <div class="text-slate-400 font-bold uppercase text-[10px]">Detail Peserta Sengketa:</div>
                    <div class="text-white font-extrabold text-sm">{{ participant?.full_name }}</div>
                    <div class="text-slate-300 font-mono">PIN: {{ participant?.pin_code }} &bull; NIK: {{ participant?.id_card_number }}</div>
                    <div class="text-slate-300 font-mono">HP: {{ participant?.phone || '-' }}</div>
                    <div class="text-amber-400 font-bold text-[11px] pt-1">BIB Lama yang Pernah Claim: #{{ participant?.bib_number || 'N/A' }}</div>
                </div>

                <form @submit.prevent="submitResetClaim" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Password Admin / Supervisor</label>
                        <input
                            v-model="adminPassword"
                            type="password"
                            required
                            autofocus
                            placeholder="Masukkan password admin..."
                            class="w-full bg-slate-950 border border-slate-700 focus:border-amber-500 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Alasan Reset Sengketa / Catatan Rekonsiliasi</label>
                        <textarea
                            v-model="resetReason"
                            rows="2"
                            required
                            placeholder="Catatan alasan sengketa..."
                            class="w-full bg-slate-950 border border-slate-700 focus:border-amber-500 text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                        ></textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="submit"
                            :disabled="resetSubmitting || !adminPassword || !resetReason"
                            class="flex-1 py-3 px-4 bg-amber-600 hover:bg-amber-500 text-white font-extrabold text-xs uppercase rounded-xl shadow-lg disabled:opacity-40 transition"
                        >
                            {{ resetSubmitting ? 'Verifikasi...' : '✓ Reset Claim & Buka BIB Baru' }}
                        </button>

                        <button
                            type="button"
                            @click="showResetModal = false"
                            class="px-4 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl border border-slate-700 transition"
                        >
                            Batal
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
