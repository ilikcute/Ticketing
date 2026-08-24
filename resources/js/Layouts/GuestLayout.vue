<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-center items-center text-slate-900 selection:bg-yellow-400 selection:text-blue-900 px-4 py-12 relative overflow-hidden font-sans antialiased bg-cover bg-center bg-no-repeat bg-fixed"
        style="background-image: url('/images/bg-indomaret-run.jpg');"
    >
        <!-- Subtle Overlay -->
        <div class="pointer-events-none absolute inset-0 z-0 bg-blue-950/10 backdrop-blur-[0.5px]"></div>

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
