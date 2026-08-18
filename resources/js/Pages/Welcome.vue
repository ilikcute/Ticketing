<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Indomaret Fun Run 2026 - RacePack & BIB System" />

    <div
        class="min-h-screen text-slate-900 selection:bg-yellow-400 selection:text-blue-900 font-sans antialiased relative overflow-hidden"
        :style="{
            background:
                'radial-gradient(circle at 18% 10%, rgba(255,255,255,.30) 0%, rgba(255,255,255,.10) 18%, transparent 40%), radial-gradient(circle at 84% 14%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.08) 16%, transparent 38%), radial-gradient(circle at 50% 56%, rgba(255,255,255,.20) 0%, rgba(255,255,255,.08) 18%, transparent 42%), linear-gradient(180deg, #5bc0f2 0%, #51baf0 35%, #4bb5ef 65%, #46b0ec 100%)',
        }"
    >
        <!-- Background SVG Waves & Soft Glow Lines -->
        <svg
            class="pointer-events-none absolute inset-0 z-0 h-full w-full"
            viewBox="0 0 1600 900"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <defs>
                <filter id="softGlowWelcome" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feMerge><feMergeNode in="blur" /><feMergeNode in="SourceGraphic" /></feMerge>
                </filter>
            </defs>
            <path d="M 0 110 C 180 40, 310 190, 430 120 S 700 60, 840 130 S 1120 180, 1320 115 S 1490 70, 1600 140" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2" filter="url(#softGlowWelcome)" />
            <path d="M 0 700 C 200 620, 320 760, 520 690 S 860 610, 1060 700 S 1360 760, 1600 670" fill="none" stroke="rgba(255,255,255,0.26)" stroke-width="1.7" filter="url(#softGlowWelcome)" />
        </svg>

        <div
            class="pointer-events-none absolute inset-0 z-0 opacity-[0.08]"
            style="background-image: radial-gradient(#ffffff 1.2px, transparent 1.2px); background-size: 24px 24px;"
        ></div>

        <div class="relative z-10 w-full px-4 sm:px-6 lg:px-10 py-6">
            <!-- Header Nav (Full Width Clean Blue Bar) -->
            <header class="flex justify-between items-center py-4 border-b border-white/20">
                <div class="flex items-center gap-3">
                    <img
                        src="/images/logo-indomaret-funrun.png"
                        alt="Indomaret Fun Run 2026"
                        class="h-12 w-auto object-contain filter drop-shadow-lg"
                    />
                    <div class="hidden sm:flex flex-col leading-none">
                        <h1 class="font-black text-xl tracking-tight text-white uppercase font-heading drop-shadow-md">
                            Indomaret Fun Run 2026
                        </h1>
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-yellow-300 mt-1 font-heading">
                            RacePack &amp; BIB Ticketing System
                        </p>
                    </div>
                </div>

                <div v-if="canLogin" class="flex items-center space-x-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="px-5 py-2.5 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-extrabold text-xs uppercase tracking-wider shadow-lg transition flex items-center gap-2 font-heading hover:scale-105"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Ke Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-5 py-2.5 rounded-full bg-white/20 hover:bg-white/30 text-white font-extrabold text-xs uppercase tracking-wider border border-white/30 backdrop-blur-md transition font-heading"
                        >
                            Masuk Petugas
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-5 py-2.5 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-extrabold text-xs uppercase tracking-wider shadow-lg transition font-heading hover:scale-105"
                        >
                            Daftar Akun Baru
                        </Link>
                    </template>
                </div>
            </header>

            <!-- Hero Section (Full Width Spacious Layout) -->
            <main class="py-12 lg:py-20">
                <div class="text-center max-w-4xl mx-auto space-y-6">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/20 border border-white/30 text-white text-xs font-extrabold uppercase tracking-wider backdrop-blur-md">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shadow-sm shadow-emerald-400"></span>
                        OFFICIAL EVENT SYSTEM &bull; READY LOKET &amp; KIOSK
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tight leading-tight text-white font-heading drop-shadow-md">
                        Sistem Penukaran Race Pack <br />
                        <span class="text-yellow-300 font-extrabold">&amp; BIB Check Kiosk</span>
                    </h1>

                    <p class="text-white/95 text-base sm:text-lg leading-relaxed font-semibold max-w-3xl mx-auto">
                        Platform resmi verifikasi tiket peserta, alokasi nomor BIB serba cepat di loket penukaran, serta layar Kiosk mandiri untuk pemeriksaan nama &amp; status peserta event lari secara real-time.
                    </p>

                    <div class="pt-4 flex flex-wrap justify-center gap-4">
                        <Link
                            href="/bib-check"
                            class="px-7 py-4 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-black text-base uppercase tracking-wider shadow-xl transition flex items-center gap-3 font-heading hover:scale-105"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Buka BIB Check Kiosk
                        </Link>

                        <Link
                            href="/loket"
                            class="px-7 py-4 rounded-full bg-black/30 hover:bg-black/50 text-white font-extrabold text-base uppercase tracking-wider border border-white/30 backdrop-blur-md transition flex items-center gap-3 font-heading hover:scale-105"
                        >
                            <svg class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Buka Loket Penukaran
                        </Link>
                    </div>
                </div>

                <!-- Feature Grid (Clean White Glass Cards - Full Width Grid) -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Feature 1 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-7 border-2 border-white shadow-xl text-slate-900 transition-all hover:scale-[1.02]">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 text-[#0E7BDC] flex items-center justify-center mb-4 font-black">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2 font-heading">Penukaran Race Pack Cepat</h3>
                        <p class="text-xs text-slate-600 leading-relaxed font-semibold">
                            Scan barcode / input PIN Struk menggunakan USB Barcode Scanner. Alokasi nomor BIB langsung tersimpan dengan fitur pencegahan duplikasi race condition multi-loket.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-7 border-2 border-white shadow-xl text-slate-900 transition-all hover:scale-[1.02]">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center mb-4 font-black">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2 font-heading">BIB Check Kiosk Mandiri</h3>
                        <p class="text-xs text-slate-600 leading-relaxed font-semibold">
                            Tampilan Kiosk luar ruangan dengan typography Outfit ExtraBold &amp; angka berukuran besar untuk memudahkan peserta mengecek verifikasi status secara mandiri.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-7 border-2 border-white shadow-xl text-slate-900 transition-all hover:scale-[1.02]">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mb-4 font-black">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-2 font-heading">Import Data &amp; Template CSV</h3>
                        <p class="text-xs text-slate-600 leading-relaxed font-semibold">
                            Upload ribuan data peserta sekaligus, dilengkapi format template standar CSV/Excel dan deteksi duplikat instan berkecepatan tinggi.
                        </p>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="py-8 border-t border-white/20 text-center text-xs text-white/80 font-semibold tracking-wider">
                &copy; 2026 Indomaret Fun Run Ticketing System &bull; Framework Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
            </footer>
        </div>
    </div>
</template>
