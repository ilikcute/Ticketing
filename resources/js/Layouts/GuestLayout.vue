<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-center items-center text-slate-900 selection:bg-yellow-400 selection:text-blue-900 px-4 py-12 relative overflow-hidden font-sans antialiased"
        :style="{
            background:
                'radial-gradient(circle at 18% 10%, rgba(255,255,255,.30) 0%, rgba(255,255,255,.10) 18%, transparent 40%), radial-gradient(circle at 84% 14%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.08) 16%, transparent 38%), radial-gradient(circle at 50% 56%, rgba(255,255,255,.20) 0%, rgba(255,255,255,.08) 18%, transparent 42%), linear-gradient(180deg, #5bc0f2 0%, #51baf0 35%, #4bb5ef 65%, #46b0ec 100%)',
        }"
    >
        <!-- Background SVG Waves & Dot Grid -->
        <svg
            class="pointer-events-none absolute inset-0 z-0 h-full w-full"
            viewBox="0 0 1600 900"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <defs>
                <filter id="softGlowGuest" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feMerge><feMergeNode in="blur" /><feMergeNode in="SourceGraphic" /></feMerge>
                </filter>
            </defs>
            <path d="M 0 110 C 180 40, 310 190, 430 120 S 700 60, 840 130 S 1120 180, 1320 115 S 1490 70, 1600 140" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="2" filter="url(#softGlowGuest)" />
            <path d="M 0 700 C 200 620, 320 760, 520 690 S 860 610, 1060 700 S 1360 760, 1600 670" fill="none" stroke="rgba(255,255,255,0.26)" stroke-width="1.7" filter="url(#softGlowGuest)" />
        </svg>

        <div
            class="pointer-events-none absolute inset-0 z-0 opacity-[0.09]"
            style="background-image: radial-gradient(#ffffff 1.2px, transparent 1.2px); background-size: 24px 24px;"
        ></div>

        <!-- Ambient Ornaments -->
        <div class="pointer-events-none absolute left-0 top-0 z-0 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 bottom-0 z-0 h-[460px] w-[460px] rounded-full bg-white/8 blur-3xl"></div>

        <!-- Logo & App Header -->
        <div class="mb-6 text-center relative z-10">
            <Link href="/" class="inline-flex flex-col items-center group">
                <img
                    src="/images/logo-indomaret-funrun.png"
                    alt="Indomaret Fun Run 2026"
                    class="h-20 w-auto object-contain filter drop-shadow-xl group-hover:scale-105 transition duration-300"
                />
                <h1 class="mt-3 text-3xl font-black uppercase tracking-tight text-white font-heading drop-shadow-md">
                    RacePack Pro
                </h1>
                <p class="text-xs font-bold uppercase tracking-widest text-yellow-300 mt-0.5 font-heading">
                    Indomaret Fun Run System
                </p>
            </Link>
        </div>

        <!-- Main Card Container (Chunky White Glass Card) -->
        <div class="w-full sm:max-w-md relative z-10">
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-7 sm:p-8 border-2 border-white shadow-2xl text-slate-900">
                <slot />
            </div>
        </div>

        <div class="mt-8 text-xs font-semibold text-white/90 relative z-10 text-center tracking-wider flex flex-col sm:flex-row items-center justify-center gap-2 font-heading">
            <span>&copy; 2026 Indomaret Fun Run Ticket &amp; BIB System</span>
            <span class="hidden sm:inline text-white/40">&bull;</span>
            <a
                href="https://wa.me/6282243311441?text=Halo%20ES%20Software%2C%20saya%20butuh%20bantuan%20sistem%20RacePack"
                target="_blank"
                class="font-extrabold text-[#FFD400] hover:underline flex items-center gap-1 bg-black/20 px-3 py-1 rounded-full border border-white/20 transition hover:bg-black/30"
            >
                <span>Created by MR R.x Likin / ES Software</span>
            </a>
        </div>
    </div>
</template>
