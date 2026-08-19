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
let successTimer = null;

function showSuccessToast(msg) {
    if (successTimer) clearTimeout(successTimer);
    successMessage.value = msg;
    successTimer = setTimeout(() => {
        successMessage.value = "";
    }, 3500);
}

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
    if (successTimer) clearTimeout(successTimer);
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
    editingBibName.value =
        result.value.bib_name || result.value.full_name || "";
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
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        const res = await fetch("/bib-check/update-name", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken || "",
                Accept: "application/json",
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
        showSuccessToast(
            `Nama di BIB berhasil diubah menjadi "${data.bib_name}"!`,
        );
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
        class="relative flex min-h-screen select-none flex-col justify-between overflow-y-auto lg:overflow-hidden p-2 sm:p-3 md:p-4 text-white font-sans"
        :style="{
            background:
                'radial-gradient(circle at 18% 10%, rgba(255,255,255,.30) 0%, rgba(255,255,255,.10) 18%, transparent 40%), radial-gradient(circle at 84% 14%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.08) 16%, transparent 38%), radial-gradient(circle at 50% 56%, rgba(255,255,255,.20) 0%, rgba(255,255,255,.08) 18%, transparent 42%), linear-gradient(180deg, #5bc0f2 0%, #51baf0 35%, #4bb5ef 65%, #46b0ec 100%)',
        }"
        @click="focusInput"
    >
        <!-- Background layers -->
        <svg
            class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-60 sm:opacity-100"
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

        <!-- Staff controls -->
        <div
            class="absolute left-2 top-1 z-50 flex items-center gap-1.5 opacity-60 sm:opacity-40 transition-opacity duration-200 hover:opacity-100"
        >
            <a
                href="/dashboard"
                class="rounded-full border border-white/20 bg-black/60 px-2.5 py-0.5 text-[10px] font-bold text-white/90 backdrop-blur-md transition hover:bg-black/90 flex items-center gap-1"
                title="Kembali ke Dashboard Staff"
            >
                ⚡ Staff
            </a>

            <button
                @click.stop="toggleAutoReset"
                class="rounded-full border border-white/20 px-2.5 py-0.5 text-[10px] font-bold backdrop-blur-md transition flex items-center gap-1"
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
            class="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between gap-1.5 sm:gap-2 pt-6 sm:pt-1"
        >
            <div
                class="w-20 sm:w-36 md:w-48 shrink-0 flex items-center justify-start transition duration-300 hover:scale-105 drop-shadow-[0_8px_14px_rgba(0,0,0,0.18)]"
            >
                <img
                    src="/images/logo-indomaret-funrun.png"
                    alt="Indomaret Fun Run 2026"
                    class="w-auto max-h-10 sm:max-h-16 md:max-h-20 lg:max-h-22 object-contain"
                />
            </div>

            <div
                class="flex-1 shrink-0 z-20 flex items-center justify-center transition duration-300 hover:scale-105"
            >
                <img
                    src="/images/header-event-yogyakarta.png"
                    alt="Indomaret Fun Run 2026 Yogyakarta"
                    class="w-auto object-contain max-h-14 sm:max-h-24 md:max-h-28 lg:max-h-32 drop-shadow-[0_0_18px_rgba(255,255,255,0.76)]"
                />
            </div>

            <div
                class="w-20 sm:w-36 md:w-48 shrink-0 flex items-center justify-end transition duration-300 hover:scale-105 drop-shadow-[0_8px_14px_rgba(0,0,0,0.18)]"
            >
                <img
                    src="/images/logo-indomaret.png"
                    alt="Indomaret"
                    class="w-auto max-h-10 sm:max-h-16 md:max-h-20 lg:max-h-22 object-contain"
                />
            </div>
        </header>

        <!-- Main Display Content (Single Unified Marathon Race BIB Card Element) -->
        <main
            class="relative z-10 mx-auto flex w-full max-w-3xl lg:max-w-4xl flex-1 flex-col items-center justify-center py-2 sm:py-3 space-y-2.5 my-auto"
        >
            <!-- Toast Flash Sukses (Auto-close 3.5s) -->
            <div
                v-if="successMessage"
                class="rounded-full bg-emerald-500/95 border-2 border-white/40 px-4 sm:px-6 py-1.5 sm:py-2 text-xs sm:text-sm font-bold text-white shadow-2xl flex items-center gap-2 backdrop-blur-md transition-all duration-300 animate-fade-in text-center"
            >
                <span>✅</span>
                <span>{{ successMessage }}</span>
                <button
                    @click="successMessage = ''"
                    class="ml-2 text-white/80 hover:text-white font-black text-xs transition"
                >
                    ✕
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="my-auto space-y-3 text-center py-6">
                <div
                    class="mx-auto h-12 w-12 sm:h-14 sm:w-14 animate-spin rounded-full border-4 border-white/20 border-t-[#FFCC00]"
                ></div>
                <p
                    class="text-xs sm:text-sm font-semibold tracking-wider text-white/90"
                >
                    Mencari data peserta...
                </p>
            </div>

            <!-- Error State -->
            <div
                v-else-if="errorMessage"
                class="my-auto w-full max-w-lg space-y-2.5 rounded-3xl border border-white/30 bg-rose-500/25 p-5 text-center shadow-2xl backdrop-blur-xl animate-shake my-3"
            >
                <div class="text-3xl">⚠️</div>
                <h3 class="text-base sm:text-lg font-black text-white">
                    {{ errorMessage }}
                </h3>
                <p class="text-xs text-white/80">
                    Pastikan nomor BIB atau PIN struk yang diinput sudah benar.
                </p>
                <button
                    @click="resetToIdle"
                    class="mt-1 rounded-full bg-white px-5 py-2 text-xs font-black uppercase text-[#0B2A8A] shadow-lg transition hover:bg-yellow-300 active:scale-95"
                >
                    Coba Lagi
                </button>
            </div>

            <!-- Result State (Single Unified Marathon Race BIB Card) -->
            <div
                v-else-if="result"
                class="w-full space-y-2 animate-[fadeInUp_0.4s_ease-out]"
            >
                <!-- KARTU BIB TUNGGAL (1 UNIFIED MARATHON RACE BIB CARD - INDOMARET BLUE & YELLOW THEME) -->
                <div
                    class="w-full bg-white rounded-2xl sm:rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.30)] border-4 border-white overflow-hidden relative select-all"
                >
                    <!-- Top Header Bar (Indomaret Blue Ribbon dengan 2 Lubang Peniti) -->
                    <div
                        class="relative bg-gradient-to-r from-[#0B2A8A] via-[#0E7BDC] to-[#0B2A8A] text-white py-2 sm:py-2.5 px-3 sm:px-8 flex items-center justify-between shadow-md"
                    >
                        <!-- Lubang Peniti Kiri Atas -->
                        <div
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5 rounded-full bg-slate-100/90 border-2 border-white/60 shadow-inner shrink-0"
                        ></div>

                        <!-- Header Event Title -->
                        <div class="text-center flex-1 px-2">
                            <h2
                                class="text-xs sm:text-lg md:text-2xl font-black uppercase tracking-[0.14em] sm:tracking-[0.16em] font-heading drop-shadow-sm text-white"
                            >
                                {{ event?.name || "INDOMARET FUN RUN 2026" }}
                            </h2>
                            <div
                                class="text-[8px] sm:text-[11px] font-extrabold uppercase tracking-wider text-[#FFD400]"
                            >
                                {{ event?.date || "30 AGUSTUS 2026" }} &bull;
                                {{ event?.location || "YOGYAKARTA" }}
                            </div>
                        </div>

                        <!-- Lubang Peniti Kanan Atas -->
                        <div
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5 rounded-full bg-slate-100/90 border-2 border-white/60 shadow-inner shrink-0"
                        ></div>
                    </div>

                    <!-- Center Body: Nomor BIB, Nama Runners, & Detail Kategori -->
                    <div
                        class="bg-white py-2 sm:py-3.5 px-3 sm:px-8 text-center flex flex-col items-center justify-center space-y-1"
                    >
                        <!-- NOMOR BIB RAKSASA -->
                        <div
                            class="font-bib-display my-0 text-6xl sm:text-8xl md:text-9xl lg:text-[10.5rem] font-black leading-none tracking-widest text-[#0B2A8A] drop-shadow-[0_4px_12px_rgba(11,42,138,0.22)] select-all"
                        >
                            {{ result.bib_number ?? "—" }}
                        </div>

                        <!-- NAMA RUNNER -->
                        <div class="w-full">
                            <div
                                class="font-runner-name truncate text-lg sm:text-3xl md:text-4xl lg:text-[2.75rem] font-black tracking-tight text-[#0B2A8A] uppercase leading-tight select-all drop-shadow-xs"
                            >
                                {{
                                    result.bib_name ||
                                    result.full_name ||
                                    "—"
                                }}
                            </div>

                            <!-- Subtext Info Jika Beda Dengan Pembeli Asli -->
                            <div
                                v-if="
                                    result.full_name &&
                                    result.bib_name &&
                                    result.bib_name !== result.full_name
                                "
                                class="text-[10px] sm:text-xs text-slate-500 font-semibold mt-0.5"
                            >
                                Pembeli / Pemesan:
                                <strong class="text-slate-800">{{
                                    result.full_name
                                }}</strong>
                            </div>
                        </div>

                        <!-- Kategori & Detail Runner (Badges) -->
                        <div
                            class="flex items-center justify-center gap-1.5 sm:gap-2.5 flex-wrap pt-1"
                        >
                            <span
                                class="px-2.5 sm:px-3 py-0.5 rounded-full bg-blue-50 text-[#0B2A8A] border-2 border-[#0B2A8A]/30 font-black text-[10px] sm:text-xs uppercase tracking-wider font-heading shadow-xs"
                            >
                                {{ result.category || "5K FUN RUN" }}
                            </span>
                            <span
                                v-if="
                                    result?.jersey_size &&
                                    result.jersey_size !== '-'
                                "
                                class="px-2.5 sm:px-3 py-0.5 rounded-full bg-gradient-to-r from-yellow-200 to-amber-300 text-yellow-950 border-2 border-yellow-400 font-black text-[10px] sm:text-xs uppercase font-mono shadow-xs"
                            >
                                JERSEY: {{ result.jersey_size }}
                            </span>
                            <span
                                v-if="result?.gender"
                                class="px-2.5 sm:px-3 py-0.5 rounded-full bg-slate-100 text-slate-700 border-2 border-slate-200 font-bold text-[10px] sm:text-xs uppercase shadow-xs"
                            >
                                {{
                                    ["L", "M"].includes(result.gender)
                                        ? "PRIA"
                                        : ["P", "F"].includes(result.gender)
                                          ? "WANITA"
                                          : result.gender
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Footer Bar (Indomaret Yellow Ribbon dengan 2 Lubang Peniti) -->
                    <div
                        class="relative bg-gradient-to-r from-[#FFD400] via-[#FFE259] to-[#FFD400] text-[#0B2A8A] py-1.5 sm:py-2.5 px-3 sm:px-8 flex items-center justify-between border-t-2 border-yellow-300 shadow-sm"
                    >
                        <!-- Lubang Peniti Kiri Bawah -->
                        <div
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5 rounded-full bg-white/90 border-2 border-yellow-500/40 shadow-inner shrink-0"
                        ></div>

                        <!-- Timing Badges & Tombol Ubah Nama BIB -->
                        <div
                            class="flex items-center justify-center gap-1.5 sm:gap-3 flex-wrap"
                        >
                            <span
                                class="hidden xs:inline px-2 py-0.5 rounded bg-[#0B2A8A] text-white font-black text-[9px] sm:text-xs tracking-wider uppercase font-mono shadow-xs"
                            >
                                Indomaret Fun Run
                            </span>
                            <span
                                class="hidden sm:inline px-2 py-0.5 rounded bg-white text-[#0B2A8A] border border-[#0B2A8A]/20 font-bold text-[9px] sm:text-xs tracking-wider uppercase font-mono shadow-xs"
                            >
                                BibTag Timing
                            </span>
                            <button
                                @click.stop="openEditNameModal"
                                class="px-3 sm:px-3.5 py-1 rounded-full bg-[#0B2A8A] hover:bg-[#0E7BDC] text-white font-black text-[10px] sm:text-xs uppercase shadow-md transition flex items-center gap-1 font-heading hover:scale-105 active:scale-95"
                            >
                                <span>✏️ Ubah Nama BIB</span>
                            </button>
                        </div>

                        <!-- Lubang Peniti Kanan Bawah -->
                        <div
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5 rounded-full bg-white/90 border-2 border-yellow-500/40 shadow-inner shrink-0"
                        ></div>
                    </div>
                </div>

                <!-- Tombol Reset Sederhana -->
                <div class="pt-1 text-center">
                    <button
                        @click="resetToIdle"
                        class="rounded-full bg-black/40 hover:bg-black/60 px-5 py-1.5 text-xs font-black text-white backdrop-blur-md transition shadow-md hover:scale-105 active:scale-95 uppercase tracking-wider font-heading"
                    >
                        ✕ Reset / Scan Peserta Berikutnya
                    </button>
                </div>
            </div>

            <!-- State Awal / Idle -->
            <div v-else class="my-auto space-y-2.5 text-center py-4">
                <div
                    class="inline-block rounded-full border-4 border-white bg-[#FFCC00] px-8 sm:px-12 py-2 text-xl sm:text-3xl md:text-4xl font-black tracking-[0.2em] text-[#2C2C2C] shadow-[0_16px_35px_rgba(0,0,0,0.18),0_0_35px_rgba(255,255,255,0.22)] transition duration-300 hover:scale-105"
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
                    class="text-xs font-medium tracking-[0.16em] text-white/85 sm:text-sm px-4"
                >
                    Silakan ketik nomor BIB atau scan barcode di bawah
                </p>
            </div>
        </main>

        <!-- Footer (Responsive for Mobile, Tablet, and Desktop) -->
        <footer
            class="relative z-10 mx-auto flex flex-col md:grid md:grid-cols-12 w-full max-w-7xl items-center justify-between gap-2.5 sm:gap-2 border-t border-white/15 pt-2 md:pt-2 shrink-0 pb-1"
        >
            <!-- Kolom Input Search (On Mobile: Placed at top of footer for easy thumb access) -->
            <div
                class="w-full flex items-center justify-center order-1 md:order-3 md:col-span-3 md:justify-end"
            >
                <div
                    class="flex w-full max-w-md items-center gap-1.5 overflow-hidden rounded-full border border-white/20 bg-white/10 px-2 py-1 shadow-xl backdrop-blur-md"
                >
                    <form
                        @submit.prevent="handleScan"
                        class="flex min-w-0 flex-1 items-center rounded-full bg-white p-0.5 shadow-inner"
                    >
                        <input
                            ref="input"
                            v-model="code"
                            type="text"
                            placeholder="Input BIB / PIN Manual..."
                            class="min-w-0 flex-1 border-none bg-transparent px-3 py-1 text-xs sm:text-base font-semibold text-slate-800 outline-none ring-0 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                            aria-label="Masukkan Nomor BIB"
                            autocomplete="off"
                        />
                        <button
                            type="submit"
                            class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full bg-[#FFD400] font-bold text-[#0B2A8A] shadow-md transition-all duration-200 hover:bg-yellow-400 active:scale-95"
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
                        class="hidden shrink-0 items-center gap-1 rounded-full border border-dashed border-white/40 bg-white/5 px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wider text-white/90 xl:flex"
                    >
                        <span>KETIK BIB</span>
                        <span class="text-xs font-bold">↙</span>
                    </div>
                </div>
            </div>

            <!-- Kolom Kiri: Gambar Tagline Event -->
            <div
                class="flex items-center justify-center order-3 md:order-1 md:col-span-3 md:justify-start"
            >
                <img
                    src="/images/tagline-catch-the-fun.png"
                    alt="Catch The Fun Run The City"
                    class="h-7 sm:h-12 md:h-14 lg:h-16 w-auto object-contain transition duration-300 hover:scale-105 drop-shadow-[0_8px_16px_rgba(0,0,0,0.18)]"
                />
            </div>

            <!-- Kolom Tengah: Gambar Sponsor & Media Partner Resmi -->
            <div class="flex w-full justify-center order-2 md:order-2 md:col-span-6">
                <div
                    class="flex w-full items-center justify-center rounded-2xl sm:rounded-3xl border border-white/60 bg-white px-3 py-1 sm:px-6 sm:py-2 shadow-xl transition duration-300 hover:scale-[1.01]"
                >
                    <img
                        src="/images/media-partner.png"
                        alt="Official Mobile Banking Partner & Media Partners"
                        class="max-h-7 sm:max-h-12 md:max-h-14 lg:max-h-16 w-auto max-w-full object-contain"
                    />
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
                <div
                    class="flex items-center justify-between border-b border-slate-800 pb-3"
                >
                    <div class="flex items-center gap-2 text-yellow-400">
                        <span class="text-xl">✏️</span>
                        <h3
                            class="font-extrabold text-base text-white font-heading"
                        >
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
                    Tiket ini terdaftar atas pembelian pool / kolektif. Masukkan
                    <strong>Nama Pelari</strong> yang sebenarnya yang akan
                    dicetak/tampil pada nomor BIB:
                </div>

                <form @submit.prevent="submitEditName" class="space-y-3.5">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-300 uppercase mb-1"
                        >
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
                            {{
                                editSubmitting
                                    ? "Menyimpan..."
                                    : "✓ Simpan Nama Baru"
                            }}
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
@import url("https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@800;900&family=Montserrat:wght@800;900&family=JetBrains+Mono:wght@800;900&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap");

.font-bib-display {
    font-family: "Bebas Neue", "JetBrains Mono", cursive, sans-serif;
    letter-spacing: 0.05em;
}

.font-runner-name {
    font-family: "Outfit", "Montserrat", sans-serif;
    text-transform: uppercase;
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
