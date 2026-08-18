<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const role = computed(() => user.value.role || 'guest');
const userPermissions = computed(() => user.value.permissions || []);

function hasPerm(permCode) {
    if (role.value === 'admin') return true;
    return userPermissions.value.includes(permCode);
}

const showingNavigationDropdown = ref(false);

const currentTimeWIB = ref('');
let clockTimer = null;

function updateClock() {
    const now = new Date();
    currentTimeWIB.value = now.toLocaleTimeString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }) + ' WIB';
}

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
});
</script>

<template>
    <div
        class="min-h-screen text-slate-900 selection:bg-yellow-400 selection:text-blue-900 font-sans antialiased relative"
        :style="{
            background:
                'radial-gradient(circle at 18% 10%, rgba(255,255,255,.30) 0%, rgba(255,255,255,.10) 18%, transparent 40%), radial-gradient(circle at 84% 14%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.08) 16%, transparent 38%), radial-gradient(circle at 50% 56%, rgba(255,255,255,.20) 0%, rgba(255,255,255,.08) 18%, transparent 42%), linear-gradient(180deg, #5bc0f2 0%, #51baf0 35%, #4bb5ef 65%, #46b0ec 100%)',
        }"
    >
        <!-- Background SVG Waves & Soft Glow Lines -->
        <svg
            class="pointer-events-none fixed inset-0 z-0 h-full w-full"
            viewBox="0 0 1600 900"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <defs>
                <filter
                    id="softGlowLayout"
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
                filter="url(#softGlowLayout)"
            />
            <path
                d="M 0 205 C 180 135, 320 270, 470 205 S 780 135, 940 210 S 1235 265, 1415 195 S 1540 150, 1600 220"
                fill="none"
                stroke="rgba(255,255,255,0.28)"
                stroke-width="1.5"
                filter="url(#softGlowLayout)"
            />
            <path
                d="M 0 700 C 200 620, 320 760, 520 690 S 860 610, 1060 700 S 1360 760, 1600 670"
                fill="none"
                stroke="rgba(255,255,255,0.26)"
                stroke-width="1.7"
                filter="url(#softGlowLayout)"
            />
        </svg>

        <!-- Dot Grid Pattern -->
        <div
            class="pointer-events-none fixed inset-0 z-0 opacity-[0.06]"
            style="
                background-image: radial-gradient(
                    #ffffff 1.2px,
                    transparent 1.2px
                );
                background-size: 24px 24px;
            "
        ></div>

        <!-- Ergonomic Ambient Ornaments -->
        <div
            class="pointer-events-none fixed left-8 top-16 z-0 h-64 w-64 rounded-full border border-sky-500/10 bg-sky-500/[0.02]"
        ></div>
        <div
            class="pointer-events-none fixed right-[12%] top-[36%] z-0 h-60 w-60 rounded-full border border-amber-400/10 bg-amber-400/[0.02]"
        ></div>
        <div
            class="pointer-events-none fixed left-0 top-0 z-0 h-[420px] w-[420px] rounded-full bg-blue-600/10 blur-3xl"
        ></div>
        <div
            class="pointer-events-none fixed right-0 bottom-0 z-0 h-[460px] w-[460px] rounded-full bg-indigo-600/10 blur-3xl"
        ></div>

        <div class="relative z-10">
            <!-- Navigation (Full Width Clean Navbar) -->
            <nav class="sticky top-0 z-50 border-b border-white/20 bg-[#0E7BDC]/85 backdrop-blur-xl">
                <div class="w-full px-4 sm:px-6 lg:px-10">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center space-x-8">
                            <!-- Brand / Logo Header -->
                            <Link href="/" class="flex items-center gap-3 group">
                                <img
                                    src="/images/logo-indomaret-funrun.png"
                                    alt="Indomaret Fun Run 2026"
                                    class="h-10 w-auto object-contain filter drop-shadow-md group-hover:scale-105 transition duration-300"
                                />
                            </Link>

                            <!-- Dynamic Navigation Links Based on Permissions -->
                            <div class="hidden space-x-1 sm:flex items-center">
                                <Link
                                    v-if="hasPerm('access-dashboard')"
                                    :href="route('dashboard')"
                                    :class="[
                                        'px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-wider transition flex items-center gap-2 font-heading',
                                        route().current('dashboard')
                                            ? 'bg-white text-[#0B2A8A] shadow-lg font-black'
                                            : 'text-white/90 hover:text-white hover:bg-white/10'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                    Dashboard
                                </Link>

                                <Link
                                    v-if="hasPerm('access-loket')"
                                    href="/loket"
                                    :class="[
                                        'px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-wider transition flex items-center gap-2 font-heading',
                                        route().current('loket.*')
                                            ? 'bg-white text-[#0B2A8A] shadow-lg font-black'
                                            : 'text-white/90 hover:text-white hover:bg-white/10'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Loket Penukaran
                                </Link>

                                <Link
                                    v-if="hasPerm('access-import')"
                                    href="/import"
                                    :class="[
                                        'px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-wider transition flex items-center gap-2 font-heading',
                                        route().current('import.*')
                                            ? 'bg-white text-[#0B2A8A] shadow-lg font-black'
                                            : 'text-white/90 hover:text-white hover:bg-white/10'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    Import Peserta
                                </Link>

                                <Link
                                    v-if="hasPerm('access-users')"
                                    href="/users"
                                    :class="[
                                        'px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-wider transition flex items-center gap-2 font-heading',
                                        route().current('users.*')
                                            ? 'bg-white text-[#0B2A8A] shadow-lg font-black'
                                            : 'text-white/90 hover:text-white hover:bg-white/10'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Manajemen User &amp; Role
                                </Link>

                                <Link
                                    v-if="hasPerm('access-bib-check')"
                                    href="/bib-check"
                                    :class="[
                                        'px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-wider transition flex items-center gap-2 font-heading',
                                        route().current('bib-check.*')
                                            ? 'bg-white text-[#0B2A8A] shadow-lg font-black'
                                            : 'text-white/90 hover:text-white hover:bg-white/10'
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    BIB Check Kiosk
                                </Link>
                            </div>
                        </div>

                        <!-- User Controls -->
                        <div class="hidden sm:flex sm:items-center sm:space-x-3">
                            <!-- Real-time Live WIB Clock -->
                            <div class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/25 text-white font-mono text-xs font-bold backdrop-blur-md shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>{{ currentTimeWIB }}</span>
                            </div>

                            <!-- Role Pill Badge -->
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-white/20 text-white border border-white/30 backdrop-blur-md">
                                {{ role }}
                            </span>

                            <!-- User Profile Dropdown -->
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-black/20 hover:bg-black/30 border border-white/20 text-white text-xs font-extrabold transition">
                                            <div class="w-6 h-6 rounded-full bg-[#FFD400] text-[#0B2A8A] font-black flex items-center justify-center text-xs uppercase font-heading">
                                                {{ user.name ? user.name.charAt(0) : 'U' }}
                                            </div>
                                            <span>{{ user.name }}</span>
                                            <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')" class="text-xs font-semibold">Profile Akun</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-xs font-bold text-rose-600">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Mobile Hamburger Button -->
                        <div class="flex items-center sm:hidden">
                            <button
                                class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white/10 focus:outline-none transition"
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-b border-white/20 bg-[#0E7BDC]">
                    <div class="space-y-1 px-4 pt-2 pb-3">
                        <Link v-if="hasPerm('access-dashboard')" :href="route('dashboard')" class="block px-3 py-2 rounded-md text-base font-bold text-white hover:bg-white/10">Dashboard</Link>
                        <Link v-if="hasPerm('access-loket')" href="/loket" class="block px-3 py-2 rounded-md text-base font-bold text-white hover:bg-white/10">Loket Penukaran</Link>
                        <Link v-if="hasPerm('access-import')" href="/import" class="block px-3 py-2 rounded-md text-base font-bold text-white hover:bg-white/10">Import Peserta</Link>
                        <Link v-if="hasPerm('access-users')" href="/users" class="block px-3 py-2 rounded-md text-base font-bold text-white hover:bg-white/10">Manajemen User &amp; Role</Link>
                        <Link v-if="hasPerm('access-bib-check')" href="/bib-check" class="block px-3 py-2 rounded-md text-base font-bold text-white hover:bg-white/10">BIB Check Kiosk</Link>
                    </div>

                    <div class="border-t border-white/20 pt-4 pb-3 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-[#FFD400] text-[#0B2A8A] font-black flex items-center justify-center uppercase font-heading">
                                {{ user.name ? user.name.charAt(0) : 'U' }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">{{ user.name }}</div>
                                <div class="text-xs text-white/80">{{ user.email }}</div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <Link :href="route('profile.edit')" class="block px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-white/10">Profile</Link>
                            <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-3 py-2 rounded-md text-sm font-bold text-yellow-300 hover:bg-white/10">Log Out</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Header (Full Width) -->
            <header v-if="$slots.header" class="border-b border-white/20 bg-black/10 backdrop-blur-md">
                <div class="w-full px-4 py-5 sm:px-6 lg:px-10">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main Content Area (Full Width) -->
            <main class="w-full px-4 sm:px-6 lg:px-10 py-8 min-h-[calc(100vh-180px)]">
                <slot />
            </main>

            <!-- Main Footer with Developer Attribution & WA Link -->
            <footer class="w-full border-t border-white/20 bg-black/20 backdrop-blur-md py-4 text-center text-xs text-white/90">
                <div class="w-full px-4 sm:px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-2 font-heading">
                    <div>
                        <strong>RacePack Pro System v1.0</strong> &bull; Indomaret Fun Run 2026
                    </div>
                    <div class="flex items-center gap-1.5 font-semibold">
                        <span>Designed &amp; Developed with ❤️ by</span>
                        <a
                            href="https://wa.me/6282243311441?text=Halo%20ES%20Software%2C%20saya%20butuh%20bantuan%20sistem%20RacePack"
                            target="_blank"
                            class="font-black text-[#FFD400] hover:underline flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full border border-white/30 transition hover:bg-white/20 shadow-sm"
                        >
                            <span>MR R.x Likin / ES Software</span>
                            <span class="text-[10px] bg-emerald-500 text-white px-1.5 py-0.2 rounded-full font-extrabold uppercase">WA Contact</span>
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>
