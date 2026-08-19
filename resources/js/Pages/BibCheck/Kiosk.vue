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
const loading = ref(false);
const autoResetEnabled = ref(false); // Default nonaktif agar peserta bisa foto & upload sosmed
const autoResetSeconds = ref(15);
let autoResetTimer = null;

function focusInput() {
    nextTick(() => input.value?.focus());
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
    focusInput();
}

function toggleAutoReset() {
    autoResetEnabled.value = !autoResetEnabled.value;
    if (!autoResetEnabled.value && autoResetTimer) {
        clearTimeout(autoResetTimer);
        autoResetTimer = null;
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
            <path
                d="M 0 760 C 180 690, 360 860, 560 790 S 920 700, 1120 800 S 1400 850, 1600 760"
                fill="none"
                stroke="rgba(255,255,255,0.16)"
                stroke-width="1.4"
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

        <div
            class="pointer-events-none absolute right-6 top-1/2 z-0 h-4 w-4 -translate-y-1/2 rounded-full border border-white/20 bg-white/20 shadow-[0_0_18px_rgba(255,255,255,0.35)]"
        ></div>
        <div
            class="pointer-events-none absolute bottom-24 right-10 z-0 h-3.5 w-3.5 rounded-full border border-white/20 bg-white/20 shadow-[0_0_14px_rgba(255,255,255,0.35)]"
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

        <!-- Header (Fluid Scaling) -->
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
                    alt="Indomaret Official Logo"
                    class="w-auto object-contain max-h-12 rounded-md sm:max-h-16 sm:rounded-lg md:max-h-18 lg:max-h-20"
                />
            </div>
        </header>

        <!-- Main (Sensitif Terhadap Tinggi Layar Windowed & Fullscreen) -->
        <main
            class="relative z-10 mx-auto flex w-full max-w-2xl flex-1 flex-col items-center justify-center px-2 my-auto -mt-2 sm:-mt-4 md:-mt-6"
        >
            <div v-if="loading" class="py-6 text-center animate-pulse">
                <div
                    class="mx-auto mb-3 h-14 w-14 animate-spin rounded-full border-4 border-[#FFD400] border-t-transparent"
                ></div>
                <p class="text-lg font-extrabold tracking-wider text-[#FFD400]">
                    🔍 MENCARI DATA PESERTA...
                </p>
            </div>

            <div
                v-else-if="errorMessage"
                class="w-full max-w-md space-y-3 rounded-3xl border-4 border-white/20 bg-red-600 p-5 text-center text-white shadow-2xl animate-[fadeInUp_0.3s_ease-out]"
            >
                <div class="text-xl font-extrabold">
                    ⚠️ DATA TIDAK DITEMUKAN
                </div>
                <p class="text-xs font-semibold leading-relaxed">
                    {{ errorMessage }}
                </p>
                <p class="text-xs font-extrabold text-[#FFD400]">
                    📞 Silakan hubungi Panitia / Petugas Loket untuk memastikan
                    Nomor BIB Anda.
                </p>
                <button
                    @click="resetToIdle"
                    class="mt-1 rounded-full bg-[#FFD400] px-6 py-2 text-xs font-extrabold uppercase tracking-wider text-[#0B2A8A] shadow-lg transition hover:scale-105"
                >
                    Coba Lagi
                </button>
            </div>

            <div
                v-else-if="result"
                class="w-full space-y-2.5 animate-[fadeInUp_0.4s_ease-out]"
            >
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

                <div
                    class="rounded-[24px] border-4 border-[#0B34AA]/20 bg-white p-3.5 text-center text-[#0B2A8A] shadow-2xl sm:p-4"
                >
                    <div class="mb-0.5 flex items-center justify-center gap-2">
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                        <span
                            class="text-xs font-extrabold uppercase tracking-[0.28em] text-slate-500 sm:text-sm"
                            >NAMA PESERTA / BIB</span
                        >
                        <span class="h-0.5 w-6 bg-[#FFD400] sm:w-8"></span>
                    </div>

                    <div
                        class="my-0.5 truncate text-xl font-extrabold tracking-tight text-[#0B2A8A] sm:text-2xl md:text-3xl font-heading"
                    >
                        {{ result.bib_name || result.full_name || "—" }}
                    </div>

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
                    Silakan ketik nomor BIB di bawah
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
    </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@800;900&family=Outfit:wght@700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap");

:root {
    color-scheme: only light;
}

.font-heading {
    font-family: "Outfit", "Plus Jakarta Sans", sans-serif;
    font-weight: 800;
}

.font-bib {
    font-family: "JetBrains Mono", "Impact", monospace;
    letter-spacing: 0.04em;
    font-weight: 900;
}

* {
    font-family:
        "Plus Jakarta Sans",
        system-ui,
        -apple-system,
        sans-serif;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes shimmer {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@media (max-width: 640px) {
    .font-bib {
        letter-spacing: 0.02em;
    }
}
</style>
