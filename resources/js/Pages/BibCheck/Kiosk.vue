<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    event: { type: Object, default: null },
});

const code = ref("");
const input = ref(null);
const result = ref(null);
const errorMessage = ref("");
const successMessage = ref("");
const loading = ref(false);
const autoResetEnabled = ref(false); // Default nonaktif agar peserta bisa foto & upload sosmed
const autoResetSeconds = ref(15);
let autoResetTimer = null;

// State Modal Edit Nama BIB
const showEditModal = ref(false);
const editingBibName = ref("");
const editSubmitting = ref(false);
const editError = ref("");
const editInputRef = ref(null);

function focusInput() {
    if (!showEditModal.value) {
        nextTick(() => input.value?.focus());
    }
}

onMounted(() => {
    focusInput();
});

onBeforeUnmount(() => {
    if (autoResetTimer) clearTimeout(autoResetTimer);
});

async function handleScan() {
    const trimmedCode = code.value.trim();
    if (!trimmedCode) return;

    loading.value = true;
    errorMessage.value = "";
    successMessage.value = "";
    result.value = null;

    if (autoResetTimer) {
        clearTimeout(autoResetTimer);
        autoResetTimer = null;
    }

    try {
        const res = await fetch(
            `/bib-check/${encodeURIComponent(trimmedCode)}`,
        );
        const data = await res.json();

        if (!res.ok) {
            errorMessage.value =
                data.message || "Nomor BIB / PIN tidak terdaftar";
            return;
        }

        result.value = data;

        // Auto reset hanya jika opsi aktif
        if (autoResetEnabled.value) {
            autoResetTimer = setTimeout(
                resetToIdle,
                autoResetSeconds.value * 1000,
            );
        }
    } catch {
        errorMessage.value = "Koneksi bermasalah, silakan coba lagi.";
    } finally {
        loading.value = false;
        code.value = "";
        focusInput();
    }
}

function resetToIdle() {
    if (autoResetTimer) clearTimeout(autoResetTimer);
    result.value = null;
    errorMessage.value = "";
    successMessage.value = "";
    showEditModal.value = false;
    focusInput();
}

function toggleAutoReset() {
    autoResetEnabled.value = !autoResetEnabled.value;
    if (!autoResetEnabled.value && autoResetTimer) {
        clearTimeout(autoResetTimer);
        autoResetTimer = null;
    }
}

// Fungsi Buka Modal Edit Nama BIB
function openEditNameModal() {
    if (!result.value) return;
    if (autoResetTimer) {
        clearTimeout(autoResetTimer);
        autoResetTimer = null;
    }
    editingBibName.value = result.value.bib_name || result.value.full_name || "";
    editError.value = "";
    showEditModal.value = true;
    nextTick(() => editInputRef.value?.focus());
}

// Fungsi Simpan Perubahan Nama BIB
async function submitEditName() {
    const newName = editingBibName.value.trim();
    if (!newName) {
        editError.value = "Nama tidak boleh kosong.";
        return;
    }

    editSubmitting.value = true;
    editError.value = "";

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/bib-check/update-name', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code: result.value.pin_code || result.value.bib_number,
                id: result.value.id,
                bib_name: newName,
            }),
        });

        const data = await res.json();
        if (!res.ok) {
            editError.value = data.message || "Gagal menyimpan perubahan nama.";
            return;
        }

        // Perbarui data lokal secara instan
        result.value.bib_name = data.bib_name;
        showEditModal.value = false;
        successMessage.value = `Nama di BIB berhasil diubah menjadi "${data.bib_name}"!`;
    } catch {
        editError.value = "Terjadi kesalahan koneksi ke server.";
    } finally {
        editSubmitting.value = false;
    }
}
</script>

<template>
    <Head :title="`BIB Check - ${event?.name || 'Indomaret Fun Run 2026'}`" />

    <div
        class="relative flex min-h-screen h-screen select-none flex-col justify-between overflow-y-auto lg:overflow-hidden p-2 sm:p-3 md:p-4 text-white font-sans"
        :style="{
            background:
                'radial-gradient(circle at 18% 10%, rgba(255,255,255,.30) 0%, rgba(255,255,255,.10) 18%, transparent 40%), radial-gradient(circle at 84% 14%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.08) 16%, transparent 38%), radial-gradient(circle at 50% 56%, rgba(255,255,255,.20) 0%, rgba(255,255,255,.08) 18%, transparent 42%), linear-gradient(180deg, #5bc0f2 0%, #51baf0 35%, #4bb5ef 65%, #46b0ec 100%)',
        }"
        @click="focusInput"
    >
        <!-- Background layers -->
        <svg
            class="pointer-events-none absolute inset-0 z-0 h-full w-full"
            viewBox="0 0 1600 900"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <defs>
                <filter
                    id="softGlow"
                    x="-20%"
                    y="-20%"
                    width="140%"
                    height="140%"
                >
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>

            <path
                d="M 0 110 C 180 40, 310 190, 430 120 S 700 60, 840 130 S 1120 180, 1320 115 S 1490 70, 1600 140"
                fill="none"
                stroke="rgba(255,255,255,0.35)"
                stroke-width="2"
                filter="url(#softGlow)"
            />
            <path
                d="M 0 205 C 180 135, 320 270, 470 205 S 780 135, 940 210 S 1235 265, 1415 195 S 1540 150, 1600 220"
                fill="none"
                stroke="rgba(255,255,255,0.28)"
                stroke-width="1.5"
                filter="url(#softGlow)"
            />
            <path
                d="M 0 700 C 200 620, 320 760, 520 690 S 860 610, 1060 700 S 1360 760, 1600 670"
                fill="none"
                stroke="rgba(255,255,255,0.26)"
                stroke-width="1.7"
                filter="url(#softGlow)"
            />
        </svg>

        <div
            class="pointer-events-none absolute inset-0 z-0 opacity-[0.09]"
            style="
                background-image: radial-gradient(
                    #ffffff 1.2px,
                    transparent 1.2px
                );
                background-size: 24px 24px;
            "
        ></div>

        <div
            class="pointer-events-none absolute left-8 top-16 z-0 h-64 w-64 rounded-full border border-white/18 bg-white/[0.03]"
        ></div>
        <div
            class="pointer-events-none absolute left-[26%] top-[48%] z-0 h-72 w-72 rounded-full border border-white/12 bg-white/[0.02]"
        ></div>
        <div
            class="pointer-events-none absolute right-[12%] top-[36%] z-0 h-60 w-60 rounded-full border border-white/12 bg-white/[0.02]"
        ></div>
        <div
            class="pointer-events-none absolute right-14 bottom-20 z-0 h-80 w-80 rounded-full border border-white/10 bg-white/[0.02]"
        ></div>
        <div
            class="pointer-events-none absolute left-0 top-0 z-0 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute right-0 bottom-0 z-0 h-[460px] w-[460px] rounded-full bg-white/8 blur-3xl"
        ></div>

        <!-- Staff controls -->
        <div
            class="absolute left-2 top-1 z-50 flex items-center gap-1.5 opacity-40 transition-opacity duration-200 hover:opacity-100"
        >
            <a
                href="/dashboard"
                class="rounded-full border border-white/10 bg-black/60 px-2 py-0.5 text-[10px] font-bold text-white/90 backdrop-blur-md transition hover:bg-black/90 flex items-center gap-1"
                title="Kembali ke Dashboard Staff"
            >
                ⚡ Staff
            </a>

            <button
                @click.stop="toggleAutoReset"
                class="rounded-full border border-white/10 px-2 py-0.5 text-[10px] font-bold backdrop-blur-md transition flex items-center gap-1"
                :class="
                    autoResetEnabled
                        ? 'bg-emerald-600/90 text-white'
                        : 'bg-black/60 text-white/80 hover:bg-black/90'
                "
                :title="
                    autoResetEnabled
                        ? 'Auto Refresh: ON (15 detik)'
                        : 'Auto Refresh: OFF'
                "
            >
                <span>⚙</span>
                <span>Reset:</span>
                <strong class="font-extrabold uppercase">{{
                    autoResetEnabled ? "ON" : "OFF"
                }}</strong>
            </button>
        </div>

        <!-- Header -->
        <header
            class="relative z-10 mx-auto flex w-full max-w-7xl items-start justify-between gap-2 pt-2 sm:pt-3"
        >
            <div
                class="w-36 shrink-0 flex items-center justify-start transition duration-300 hover:scale-105 sm:w-48 md:w-56 drop-shadow-[0_12px_20px_rgba(0,0,0,0.18)]"
            >
                <img
                    src="/images/logo-indomaret-funrun.png"
                    alt="Indomaret Fun Run 2026"
                    class="w-auto max-h-18 object-contain sm:max-h-22 md:max-h-28 lg:max-h-32"
                />
            </div>

            <div
                class="flex-1 shrink-0 z-20 flex items-center justify-center -mt-1 transition duration-300 hover:scale-105 sm:-mt-2 md:-mt-4"
            >
                <img
                    src="/images/header-event-yogyakarta.png"
                    alt="Indomaret Fun Run 2026 Yogyakarta"
                    class="w-auto object-contain max-h-28 sm:max-h-36 md:max-h-48 lg:max-h-56 drop-shadow-[0_0_18px_rgba(255,255,255,0.76)]"
                />
            </div>

            <div
                class="w-36 shrink-0 flex items-center justify-end transition duration-300 hover:scale-105 sm:w-48 md:w-56 drop-shadow-[0_12px_20px_rgba(0,0,0,0.18)]"
            >
                <img
                    src="/images/logo-indomaret.png"
                    alt="Indomaret"
                    class="w-auto max-h-18 object-contain sm:max-h-22 md:max-h-28 lg:max-h-32"
                />
            </div>
        </header>

        <!-- Main Display Content -->
        <main
            class="relative z-10 mx-auto flex w-full max-w-4xl flex-1 flex-col items-center justify-center py-2 sm:py-3"
        >
            <!-- Toast Flash Sukses -->
            <div
                v-if="successMessage"
                class="mb-3 rounded-full bg-emerald-500 px-6 py-2 text-xs sm:text-sm font-bold text-white shadow-xl flex items-center gap-2 animate-bounce"
            >
                <span>✅</span>
                <span>{{ successMessage }}</span>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="my-auto space-y-3 text-center">
                <div
                    class="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-white/20 border-t-[#FFCC00]"
                ></div>
                <p
                    class="text-sm font-semibold tracking-wider text-white/90 sm:text-base"
                >
                    Mencari data peserta...
                </p>
            </div>

            <!-- Error State -->
            <div
                v-else-if="errorMessage"
                class="my-auto w-full max-w-xl space-y-3 rounded-3xl border border-white/30 bg-rose-500/20 p-5 text-center shadow-2xl backdrop-blur-xl animate-shake"
            >
                <div class="text-4xl">⚠️</div>
                <h3 class="text-lg font-black text-white sm:text-xl">
                    {{ errorMessage }}
                </h3>
                <p class="text-xs text-white/80">
                    Pastikan nomor BIB atau PIN struk yang diinput sudah benar.
                </p>
                <button
                    @click="resetToIdle"
                    class="mt-2 rounded-full bg-white px-6 py-2 text-xs font-black uppercase text-[#0B2A8A] shadow-lg transition hover:bg-yellow-300"
                >
                    Coba Lagi
                </button>
            </div>

            <!-- Result State -->
            <div
                v-else-if="result"
                class="w-full space-y-2.5 animate-[fadeInUp_0.4s_ease-out]"
            >
                <!-- Nomor BIB Display -->
                <div
                    class="relative overflow-hidden rounded-[24px] border-4 border-[#0B34AA]/20 bg-white p-3.5 text-center text-[#0B2A8A] shadow-2xl sm:p-5"
                >
                    <div class="mb-0.5 flex items-center justify-center gap-2">
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                        <span
                            class="text-xs font-extrabold uppercase tracking-[0.28em] text-slate-500 sm:text-sm"
                            >NOMOR BIB</span
                        >
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                    </div>

                    <div
                        class="font-bib my-0.5 text-6xl font-black leading-none tracking-wider sm:text-7xl md:text-8xl"
                        style="
                            color: #0b2a8a;
                            text-shadow:
                                0 0 20px rgba(11, 42, 138, 0.2),
                                0 4px 8px rgba(0, 0, 0, 0.1);
                        "
                    >
                        {{ result.bib_number ?? "—" }}
                    </div>
                </div>

                <!-- Kartu Identitas Peserta / BIB (Dengan Fitur Edit Nama) -->
                <div
                    class="rounded-[24px] border-4 border-[#0B34AA]/20 bg-white p-3.5 text-center text-[#0B2A8A] shadow-2xl sm:p-4 relative group"
                >
                    <div class="mb-0.5 flex items-center justify-center gap-2">
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                        <span
                            class="text-xs font-extrabold uppercase tracking-[0.28em] text-slate-500 sm:text-sm"
                            >NAMA TAMPIL DI BIB</span
                        >
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                    </div>

                    <!-- Nama Tampil di BIB & Tombol Edit -->
                    <div class="flex items-center justify-center gap-2 my-0.5">
                        <div
                            class="truncate text-xl font-extrabold tracking-tight text-[#0B2A8A] sm:text-2xl md:text-3xl font-heading"
                        >
                            {{ result.bib_name || result.full_name || "—" }}
                        </div>
                        <button
                            @click.stop="openEditNameModal"
                            class="p-1.5 rounded-full bg-yellow-100 hover:bg-[#FFD400] text-[#0B2A8A] border border-yellow-300 shadow-sm transition hover:scale-110 shrink-0"
                            title="Edit / Sesuaikan Nama Tampil di BIB"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Subtext Info Jika Beda Dengan Pembeli Asli -->
                    <div
                        v-if="result.full_name && result.bib_name && result.bib_name !== result.full_name"
                        class="text-xs text-slate-500 font-semibold mb-1"
                    >
                        Pembeli / Pemesan: <strong class="text-slate-800">{{ result.full_name }}</strong>
                    </div>

                    <!-- Badges -->
                    <div class="mt-1 flex items-center justify-center gap-2 flex-wrap text-xs font-bold uppercase tracking-wider">
                        <span v-if="result?.category" class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0B2A8A] border border-blue-200">
                            {{ result.category }}
                        </span>
                        <span v-if="result?.jersey_size && result.jersey_size !== '-'" class="px-2.5 py-0.5 rounded-full bg-yellow-100 text-yellow-900 border border-yellow-300 font-black">
                            JERSEY: {{ result.jersey_size }}
                        </span>
                        <span v-if="result?.gender" class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                            {{ ['L', 'M'].includes(result.gender) ? 'Pria' : (['P', 'F'].includes(result.gender) ? 'Wanita' : result.gender) }}
                        </span>
                        <button
                            @click.stop="openEditNameModal"
                            class="px-2.5 py-0.5 rounded-full bg-[#0E7BDC] hover:bg-blue-600 text-white font-extrabold text-[10px] shadow-sm transition flex items-center gap-1"
                        >
                            <span>✏️ Ubah Nama BIB</span>
                        </button>
                    </div>
                </div>

                <div class="pt-0.5 text-center">
                    <button
                        @click="resetToIdle"
                        class="rounded-full bg-black/30 px-5 py-1.5 text-xs font-extrabold text-white/70 backdrop-blur-md transition hover:bg-black/50 hover:text-white"
                    >
                        ✕ Reset / Scan Peserta Berikutnya
                    </button>
                </div>
            </div>

            <!-- State Awal / Idle -->
            <div v-else class="my-auto space-y-3 text-center">
                <div
                    class="inline-block rounded-full border-4 border-white bg-[#FFCC00] px-14 py-2.5 text-3xl font-black tracking-[0.22em] text-[#2C2C2C] shadow-[0_16px_35px_rgba(0,0,0,0.18),0_0_35px_rgba(255,255,255,0.22)] transition duration-300 hover:scale-105 sm:text-4xl md:text-[3.35rem]"
                    style="
                        font-family:
                            &quot;Montserrat&quot;, &quot;Poppins&quot;,
                            sans-serif;
                        font-weight: 800;
                    "
                >
                    BIB CHECK
                </div>
                <p
                    class="text-xs font-medium tracking-[0.18em] text-white/82 sm:text-sm"
                >
                    Silakan ketik nomor BIB atau scan barcode di bawah
                </p>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="relative z-10 mx-auto grid w-full max-w-7xl grid-cols-1 items-center justify-between gap-3 border-t border-white/10 pt-3 md:grid-cols-12"
        >
            <div
                class="flex items-center justify-center md:col-span-3 md:justify-start"
            >
                <img
                    src="/images/tagline-catch-the-fun.png"
                    alt="Catch The Fun Run The City"
                    class="h-14 w-auto object-contain transition duration-300 hover:scale-105 md:h-18 lg:h-20 drop-shadow-[0_10px_18px_rgba(0,0,0,0.18)]"
                />
            </div>

            <div class="flex w-full justify-center md:col-span-6">
                <div
                    class="flex w-full items-center justify-center rounded-3xl border border-white/60 bg-white px-5 py-2.5 shadow-2xl transition duration-300 hover:scale-[1.01] sm:px-6 sm:py-3"
                >
                    <img
                        src="/images/media-partner.png"
                        alt="Official Mobile Banking Partner & Media Partners"
                        class="max-h-16 w-auto max-w-full object-contain md:max-h-20 lg:max-h-24"
                    />
                </div>
            </div>

            <div
                class="flex items-center justify-center md:col-span-3 md:justify-end"
            >
                <div
                    class="flex w-full max-w-md items-center gap-2 overflow-hidden rounded-full border border-white/20 bg-white/10 px-2 py-1.5 shadow-2xl backdrop-blur-md"
                >
                    <form
                        @submit.prevent="handleScan"
                        class="flex min-w-0 flex-1 items-center rounded-full bg-white p-1 shadow-inner"
                    >
                        <input
                            ref="input"
                            v-model="code"
                            type="text"
                            placeholder="Input BIB Manual..."
                            class="min-w-0 flex-1 border-none bg-transparent px-3 py-1 text-base font-semibold text-slate-800 outline-none ring-0 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                            aria-label="Masukkan Nomor BIB"
                            autocomplete="off"
                        />
                        <button
                            type="submit"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#FFD400] font-bold text-[#0B2A8A] shadow-md transition-all duration-200 hover:bg-yellow-400 active:scale-95 sm:h-8 sm:w-8"
                            aria-label="Cari BIB"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </button>
                    </form>

                    <div
                        class="hidden shrink-0 items-center gap-1 rounded-full border border-dashed border-white/40 bg-white/5 px-3 py-1.5 text-[9px] font-extrabold uppercase tracking-wider text-white/90 xl:flex"
                    >
                        <span>KETIK BIB</span>
                        <span class="text-xs font-bold">↙</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- ========================================= -->
        <!-- MODAL EDIT NAMA TAMPIL DI BIB             -->
        <!-- ========================================= -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
            @click.stop
        >
            <div
                class="bg-slate-900 border-2 border-slate-700 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 text-slate-900"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-yellow-400">
                        <span class="text-xl">✏️</span>
                        <h3 class="font-extrabold text-base text-white font-heading">
                            Ubah Nama Tampil di BIB
                        </h3>
                    </div>
                    <button
                        @click="showEditModal = false"
                        class="text-slate-400 hover:text-white font-bold text-sm"
                    >
                        ✕
                    </button>
                </div>

                <div
                    v-if="editError"
                    class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold"
                >
                    ⚠️ {{ editError }}
                </div>

                <div class="text-xs text-slate-300 leading-relaxed">
                    Tiket ini terdaftar atas pembelian pool / kolektif. Masukkan <strong>Nama Pelari</strong> yang sebenarnya yang akan dicetak/tampil pada nomor BIB:
                </div>

                <form @submit.prevent="submitEditName" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">
                            Nama Tampil di BIB (Runner Name)
                        </label>
                        <input
                            ref="editInputRef"
                            v-model="editingBibName"
                            type="text"
                            required
                            placeholder="Ketik nama pelari..."
                            class="w-full bg-slate-950 border-2 border-slate-700 focus:border-[#FFD400] text-white rounded-xl px-4 py-2.5 text-base font-bold focus:outline-none focus:ring-2 focus:ring-yellow-400/30 font-heading uppercase"
                        />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="submit"
                            :disabled="editSubmitting || !editingBibName.trim()"
                            class="flex-1 py-3 px-4 bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-black text-xs uppercase tracking-wider rounded-xl shadow-lg disabled:opacity-40 transition font-heading"
                        >
                            {{ editSubmitting ? 'Menyimpan...' : '✓ Simpan Nama Baru' }}
                        </button>

                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl border border-slate-700 transition"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@800;900&family=Outfit:wght@700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap");

.font-bib {
    font-family: "JetBrains Mono", monospace;
}

.font-heading {
    font-family: "Outfit", sans-serif;
}

.font-sans {
    font-family: "Plus Jakarta Sans", sans-serif;
}

.glass-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }
    20%,
    60% {
        transform: translateX(-6px);
    }
    40%,
    80% {
        transform: translateX(6px);
    }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
