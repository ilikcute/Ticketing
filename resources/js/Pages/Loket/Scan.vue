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
let successFlashTimer = null;

function showSuccessFlash(msg) {
    if (successFlashTimer) clearTimeout(successFlashTimer);
    successFlash.value = msg;
    successFlashTimer = setTimeout(() => {
        successFlash.value = '';
    }, 4000);
}

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
            showSuccessFlash(`BIB #${bibNumber.value} &rarr; ${participant.value?.full_name.toUpperCase()} BERHASIL DIASIGN!`);
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
            showSuccessFlash(`SENGKETA DISETUJUI: PIN ${participant.value?.pin_code} berhasil di-reset oleh Admin! Silakan assign Nomor BIB baru.`);
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
    showEditBibNameModal.value = false;
    focusInput();
}

// State & fungsi Edit Nama Tampil di BIB (Loket Desk)
const showEditBibNameModal = ref(false);
const editBibNameVal = ref('');
const editBibNameSubmitting = ref(false);
const editBibNameError = ref('');

function openEditBibNameModal() {
    if (!participant.value) return;
    editBibNameVal.value = participant.value.bib_name || participant.value.full_name || '';
    editBibNameError.value = '';
    showEditBibNameModal.value = true;
}

async function submitUpdateBibName() {
    const newName = editBibNameVal.value.trim();
    if (!newName) {
        editBibNameError.value = 'Nama tidak boleh kosong.';
        return;
    }

    editBibNameSubmitting.value = true;
    editBibNameError.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/loket/update-bib-name', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                pin_code: participant.value.pin_code,
                id: participant.value.id,
                bib_name: newName,
            }),
        });

        const data = await res.json();
        if (!res.ok) {
            editBibNameError.value = data.message || 'Gagal mengubah nama di BIB.';
            return;
        }

        participant.value.bib_name = data.bib_name;
        showEditBibNameModal.value = false;
        showSuccessFlash(`NAMA DI BIB BERHASIL DIUBAH: "${data.bib_name}"`);
    } catch {
        editBibNameError.value = 'Terjadi gangguan jaringan saat menyimpan perubahan nama.';
    } finally {
        editBibNameSubmitting.value = false;
    }
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

        <!-- Container Utama (Zero-Scroll Compact POS Layout) -->
        <div class="w-full space-y-3.5">
            <!-- Toast Flash Notification -->
            <div v-if="successFlash" class="p-3 rounded-2xl bg-emerald-500 text-white flex items-center justify-between shadow-lg text-xs font-bold animate-fade-in">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span class="font-extrabold tracking-wide uppercase" v-html="successFlash"></span>
                </div>
                <button @click="successFlash = ''" class="text-white hover:text-emerald-200 font-bold text-xs">✕</button>
            </div>

            <div v-if="errorMessage" class="p-3 rounded-2xl bg-rose-600 text-white flex items-center justify-between shadow-lg text-xs font-bold animate-fade-in">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span class="font-extrabold">{{ errorMessage }}</span>
                </div>
                <button @click="errorMessage = ''" class="text-white hover:text-rose-200 font-bold text-xs">✕</button>
            </div>

            <!-- GRID 2-KOLOM COMPACT POS -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                
                <!-- KOLOM KIRI (5 cols): SCANNER & FORM ASSIGN -->
                <div class="lg:col-span-5 flex flex-col gap-3.5">
                    
                    <!-- Panel Scanner PIN / Telepon / Nama -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-4 sm:p-5 border-2 border-white shadow-xl text-slate-900 space-y-2.5">
                        
                        <!-- Search Filter Tabs -->
                        <div class="flex items-center justify-between gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                            <button
                                v-for="pill in searchTypePills"
                                :key="pill.key"
                                type="button"
                                @click="setSearchType(pill.key)"
                                :class="[
                                    'flex-1 py-1 px-1.5 rounded-lg text-[10px] sm:text-[11px] font-extrabold transition flex items-center justify-center gap-1 font-heading',
                                    searchType === pill.key
                                        ? 'bg-[#0E7BDC] text-white shadow-sm'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                                ]"
                            >
                                <span>{{ pill.icon }}</span>
                                <span class="truncate">{{ pill.label }}</span>
                            </button>
                        </div>

                        <form @submit.prevent="handleSearch()" class="relative">
                            <input
                                ref="searchInput"
                                v-model="searchKeyword"
                                type="text"
                                autofocus
                                class="w-full text-base font-bib tracking-wider bg-white border-2 border-slate-300 focus:border-[#0E7BDC] text-slate-900 rounded-2xl py-2.5 pl-10 pr-20 shadow-sm focus:outline-none focus:ring-0 uppercase placeholder-slate-400"
                                :placeholder="dynamicPlaceholder"
                                autocomplete="off"
                            />
                            <svg class="w-4 h-4 text-[#0E7BDC] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button
                                type="submit"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 px-3.5 py-1.5 bg-[#0E7BDC] hover:bg-blue-600 text-white text-xs font-extrabold rounded-xl transition shadow uppercase font-heading flex items-center gap-1"
                                :disabled="loading"
                            >
                                <span v-if="loading" class="animate-spin">⏳</span>
                                <span>{{ loading ? '...' : 'Cari' }}</span>
                            </button>
                        </form>

                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-semibold pt-0.5">
                            <span class="flex items-center gap-1">
                                <span class="bg-blue-100 text-[#0B2A8A] px-1.5 py-0.2 rounded font-bold">Tips</span>
                                <span>Scan PIN / ketik HP / Nama</span>
                            </span>
                            <span class="text-slate-400 font-mono">ENTER &crarr;</span>
                        </div>
                    </div>

                    <!-- Panel Multi-Match Results -->
                    <div v-if="searchResults.length > 0 && !participant" class="bg-white/95 backdrop-blur-xl rounded-3xl p-4 border-2 border-amber-300 shadow-xl text-slate-900 space-y-2 flex-1">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                            <div class="flex items-center gap-1.5 text-amber-700 font-black text-xs uppercase tracking-wider font-heading">
                                <span>📋</span>
                                <span>Ditemukan {{ searchResults.length }} Peserta</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500">Pilih peserta:</span>
                        </div>

                        <div class="space-y-2 max-h-[260px] overflow-y-auto pr-1">
                            <div
                                v-for="res in searchResults"
                                :key="res.id"
                                class="p-3 rounded-2xl border transition bg-slate-50 hover:bg-blue-50/70 border-slate-200 hover:border-[#0E7BDC] space-y-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-extrabold text-sm text-slate-900 font-heading leading-tight">
                                            {{ res.bib_name || res.full_name }}
                                        </div>
                                        <div v-if="res.bib_name && res.full_name && res.bib_name !== res.full_name" class="text-[10px] text-slate-500 font-semibold">
                                            Pemesan: <strong class="text-slate-700">{{ res.full_name }}</strong>
                                        </div>
                                        <div v-if="res.jersey_size" class="mt-0.5">
                                            <span class="px-2 py-0.2 rounded-full bg-yellow-100 text-yellow-900 border border-yellow-300 font-black text-[9px] font-mono">
                                                👕 JERSEY: {{ res.jersey_size }}
                                            </span>
                                        </div>
                                    </div>
                                    <span
                                        :class="res.is_claimed ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300'"
                                        class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border shrink-0"
                                    >
                                        {{ res.is_claimed ? 'SUDAH KLAIM' : 'SIAP ASSIGN' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5 text-[10px] font-mono text-slate-600 bg-white p-2 rounded-xl border border-slate-200">
                                    <div>
                                        <span class="text-[8px] block text-slate-400 font-sans font-bold uppercase">📱 No. HP:</span>
                                        <strong class="text-slate-800">{{ res.phone || '-' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-[8px] block text-slate-400 font-sans font-bold uppercase">✉️ Email:</span>
                                        <strong class="text-slate-800 truncate block">{{ res.email || '-' }}</strong>
                                    </div>
                                </div>

                                <button
                                    @click="selectParticipant(res)"
                                    class="w-full py-1.5 px-3 bg-[#0E7BDC] hover:bg-blue-600 text-white rounded-xl font-extrabold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 font-heading"
                                >
                                    <span>Pilih Peserta Ini &rarr;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Assign BIB (Jika peserta siap assign) -->
                    <div v-if="participant && !isClaimed" class="bg-white/95 backdrop-blur-xl rounded-3xl p-4 sm:p-5 border-2 border-emerald-400 shadow-xl text-slate-900 space-y-2.5">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                            <span class="text-xs font-black uppercase text-emerald-700 tracking-wider font-heading flex items-center gap-1">
                                <span>⚡</span> Quick Assign BIB
                            </span>
                            <span class="text-xs text-slate-500 font-semibold">Saran: <strong class="text-emerald-700 font-bib text-sm">{{ suggestedBib }}</strong></span>
                        </div>

                        <div>
                            <input
                                ref="bibInput"
                                v-model="bibNumber"
                                type="text"
                                @keyup.enter="submitAssign"
                                class="w-full text-3xl font-bib font-black text-center tracking-widest bg-slate-50 border-2 border-emerald-500 text-emerald-700 rounded-2xl py-2 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                placeholder="Nomor BIB..."
                            />
                        </div>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                v-model="identityConfirmed"
                                class="rounded bg-white border-emerald-500 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                            />
                            <span class="font-bold text-xs">Data Peserta Sesuai &amp; Terverifikasi</span>
                        </label>

                        <button
                            @click="submitAssign"
                            :disabled="!identityConfirmed || !bibNumber"
                            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs sm:text-sm uppercase tracking-wider shadow-md shadow-emerald-500/25 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center gap-2 font-heading"
                        >
                            <span>✓ Konfirmasi &amp; Cetak Struk</span>
                        </button>
                    </div>

                    <!-- Tombol Reset Sengketa (Jika PIN Sudah Claimed) -->
                    <div v-else-if="participant && isClaimed" class="bg-white/95 backdrop-blur-xl rounded-3xl p-4 sm:p-5 border-2 border-amber-400 shadow-xl text-slate-900 space-y-2.5">
                        <div class="text-xs font-black text-amber-800 flex items-center gap-1.5 font-heading">
                            <span>⚠️</span> PIN Ini Terdaftar Sudah Ditukar
                        </div>
                        <p class="text-xs font-medium text-slate-600 leading-relaxed">
                            Jika peserta di hadapan Anda memiliki bukti fisik &amp; nota bayar asli (sengketa/salah klaim awal), lakukan otorisasi reset di bawah:
                        </p>
                        <button
                            @click="openResetModal"
                            class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs uppercase rounded-2xl transition shadow flex items-center justify-center gap-1.5 tracking-wider font-heading"
                        >
                            <span>🔐 Otorisasi Admin: Reset Sengketa PIN</span>
                        </button>
                    </div>

                    <!-- Tips Petugas (Jika Idle) -->
                    <div v-else-if="searchResults.length === 0 && !participant" class="p-4 rounded-3xl bg-white/90 border-2 border-white text-slate-700 text-xs space-y-1.5 shadow-md">
                        <div class="font-extrabold text-slate-900 font-heading text-xs">💡 Tips Pencarian:</div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] font-medium text-slate-600">
                            <li><strong>Kode PIN:</strong> Scan barcode struk atau ketik PIN.</li>
                            <li><strong>Nomor HP:</strong> Ketik nomor telepon peserta.</li>
                            <li><strong>Nama:</strong> Ketik nama pelari atau pembeli tiket.</li>
                        </ul>
                    </div>
                </div>

                <!-- KOLOM KANAN (7 cols): RINCIAN DATA PESERTA -->
                <div class="lg:col-span-7 flex flex-col">
                    
                    <!-- Kartu Rincian Peserta -->
                    <div v-if="participant" class="bg-white/95 backdrop-blur-xl rounded-3xl p-5 sm:p-6 border-2 border-white shadow-xl text-slate-900 space-y-4 h-full flex flex-col justify-between">
                        
                        <div>
                            <!-- Alert Banner Sengketa / Already Claimed -->
                            <div v-if="isClaimed" class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1.5 mb-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">⚠️</span>
                                    <div>
                                        <div class="font-extrabold text-xs text-amber-800 uppercase tracking-wider font-heading">STATUS: SUDAH DITUKAR (CLAIMED)</div>
                                        <div class="text-[11px] text-amber-700 font-medium">
                                            Oleh: <strong>{{ claimedByName }}</strong> &bull; Waktu: <span class="font-mono font-bold text-slate-900">{{ claimedAtFormatted }}</span> ({{ claimedDevice }})
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs bg-white p-2 rounded-xl border border-amber-200 font-mono text-[#0B2A8A] font-bold flex justify-between items-center">
                                    <span>BIB Terdaftar: #{{ participant.bib_number }}</span>
                                    <span class="text-amber-700 text-[10px] uppercase font-mono font-bold">Dapat Di-reset dengan Password Admin</span>
                                </div>
                            </div>

                            <!-- Header Info Peserta (Nama Besar & Status) -->
                            <div class="flex items-start justify-between border-b border-slate-200 pb-3.5">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-[#0B2A8A] to-[#0E7BDC] text-white font-black flex items-center justify-center text-2xl sm:text-3xl font-heading shadow-md shrink-0">
                                        {{ (participant.bib_name || participant.full_name).charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-[#0E7BDC] font-heading flex items-center gap-2">
                                            <span>Identitas Peserta Terverifikasi</span>
                                            <button
                                                @click="openEditBibNameModal"
                                                class="px-2 py-0.5 rounded-full bg-yellow-100 hover:bg-[#FFD400] text-[#0B2A8A] border border-yellow-300 font-extrabold text-[10px] transition flex items-center gap-1 shadow-xs"
                                                title="Edit Nama Tampil di BIB"
                                            >
                                                <span>✏️</span>
                                                <span>Ubah Nama BIB</span>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight font-heading">
                                                {{ participant.bib_name || participant.full_name }}
                                            </h3>
                                        </div>
                                        <div v-if="participant.bib_name && participant.full_name && participant.bib_name !== participant.full_name" class="text-xs text-slate-500 font-semibold mt-0.5">
                                            Pemesan / Pembeli Tiket: <strong class="text-slate-800">{{ participant.full_name }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <span
                                    :class="isClaimed ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300'"
                                    class="px-3.5 py-1 rounded-full text-xs font-black border shrink-0 font-heading"
                                >
                                    {{ isClaimed ? 'SUDAH DITUKAR' : 'SIAP ASSIGN' }}
                                </span>
                            </div>
                        </div>

                        <!-- 3 Kartu Rincian Jelas & Besar (100% Zero-Scroll) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 my-auto">
                            <!-- 1. Hero Card: Ukuran Jersey -->
                            <div class="bg-gradient-to-br from-yellow-50 to-amber-100/70 p-4 rounded-2xl border-2 border-yellow-400 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="block text-[11px] uppercase font-black text-amber-900 tracking-wider font-heading">👕 Ukuran Jersey</span>
                                    <span class="text-[9px] bg-yellow-300 text-yellow-950 font-black px-2 py-0.5 rounded-full font-mono shadow-xs">SERAHKAN</span>
                                </div>
                                <div class="text-4xl sm:text-5xl font-black text-amber-950 font-mono tracking-wider my-1">
                                    {{ participant.jersey_size || '-' }}
                                </div>
                            </div>

                            <!-- 2. Card: Nomor HP / WhatsApp -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex flex-col justify-between">
                                <span class="block text-[10px] uppercase font-extrabold text-slate-500 tracking-wider font-heading mb-1">📱 Nomor HP / WA</span>
                                <div class="text-lg sm:text-xl font-black text-slate-900 font-mono my-1">
                                    {{ participant.phone || '-' }}
                                </div>
                            </div>

                            <!-- 3. Card: Alamat Email -->
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex flex-col justify-between">
                                <span class="block text-[10px] uppercase font-extrabold text-slate-500 tracking-wider font-heading mb-1">✉️ Alamat Email</span>
                                <div class="text-sm sm:text-base font-bold text-slate-800 font-mono truncate my-1" :title="participant.email">
                                    {{ participant.email || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Footer Info Singkat PIN -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-mono text-slate-400">
                            <span>Kode PIN: <strong class="text-slate-700">{{ participant.pin_code }}</strong></span>
                            <span>Status: <strong class="text-emerald-600 font-bold uppercase">{{ participant.status }}</strong></span>
                        </div>
                    </div>

                    <!-- State Menunggu / Multiple Results Hint -->
                    <div v-else-if="searchResults.length > 0" class="glass-card rounded-3xl p-8 border border-white/20 text-center text-white space-y-2 h-full flex flex-col items-center justify-center">
                        <div class="text-4xl">👥</div>
                        <div class="text-base font-bold">Silakan Pilih Salah Satu Peserta</div>
                        <p class="text-xs max-w-sm mx-auto text-white/80">
                            Pencarian menghasilkan {{ searchResults.length }} peserta. Klik tombol <strong>Pilih Peserta Ini</strong> pada kolom sebelah kiri.
                        </p>
                    </div>

                    <!-- State Idle (Menunggu Scan) -->
                    <div v-else class="glass-card rounded-3xl p-8 border border-white/20 text-center text-white space-y-2 h-full flex flex-col items-center justify-center">
                        <div class="text-5xl drop-shadow-md">🏷️</div>
                        <div class="text-lg font-black tracking-tight font-heading">POS Loket Siap</div>
                        <p class="text-xs max-w-sm mx-auto text-white/90 font-medium leading-relaxed">
                            Silakan posisikan barcode di depan scanner atau ketikkan kode PIN / No. Telepon / Nama pada kolom sebelah kiri.
                        </p>
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

        <!-- ========================================= -->
        <!-- MODAL EDIT NAMA TAMPIL DI BIB (LOKET)     -->
        <!-- ========================================= -->
        <div v-if="showEditBibNameModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 text-slate-900">
                
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-[#FFD400]">
                        <span class="text-xl">✏️</span>
                        <h3 class="font-extrabold text-base text-white font-heading">Ubah Nama Tampil di BIB</h3>
                    </div>
                    <button @click="showEditBibNameModal = false" class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                </div>

                <div v-if="editBibNameError" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold">
                    ⚠️ {{ editBibNameError }}
                </div>

                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-xs space-y-1">
                    <div class="text-slate-400 font-bold uppercase text-[10px]">Data Pembeli Tiket:</div>
                    <div class="text-white font-extrabold text-sm">{{ participant?.full_name }}</div>
                    <div class="text-slate-300 font-mono">PIN: {{ participant?.pin_code }} &bull; Jersey: {{ participant?.jersey_size || '-' }}</div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Untuk pembelian tiket kolektif/pool 1 nama, ubah <strong>Nama Pelari</strong> di bawah agar tercetak/tampil sesuai nama pemilik BIB:
                </p>

                <form @submit.prevent="submitUpdateBibName" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Tampil di BIB (Runner Name)</label>
                        <input
                            v-model="editBibNameVal"
                            type="text"
                            required
                            autofocus
                            placeholder="Ketik nama pelari..."
                            class="w-full bg-slate-950 border border-slate-700 focus:border-[#FFD400] text-white rounded-xl px-4 py-2.5 text-base font-bold focus:outline-none focus:ring-2 focus:ring-yellow-400/30 uppercase font-heading"
                        />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="submit"
                            :disabled="editBibNameSubmitting || !editBibNameVal.trim()"
                            class="flex-1 py-3 px-4 bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-black text-xs uppercase tracking-wider rounded-xl shadow-lg disabled:opacity-40 transition font-heading"
                        >
                            {{ editBibNameSubmitting ? 'Menyimpan...' : '✓ Simpan Nama Baru' }}
                        </button>

                        <button
                            type="button"
                            @click="showEditBibNameModal = false"
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
