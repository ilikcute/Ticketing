<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    activeEvent: Object,
    stats: {
        type: Object,
        default: () => ({
            totalParticipants: 0,
            totalClaimed: 0,
            totalUnclaimed: 0,
            claimPercentage: 0,
            claimedOnlyCount: 0,
            checkedInCount: 0,
            disputedCount: 0,
            peakHourFormatted: 'Belum Ada',
            peakHourCount: 0,
        })
    },
    topCounters: {
        type: Array,
        default: () => []
    },
    counterPerformance: {
        type: Array,
        default: () => []
    },
    hourlyChart: {
        type: Array,
        default: () => []
    },
    maxHourlyCount: {
        type: Number,
        default: 1
    },
    categories: {
        type: Array,
        default: () => []
    },
    recentActivity: {
        type: Array,
        default: () => []
    },
    recentBatches: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const role = computed(() => user.value.role || 'guest');
const isAutoRefresh = ref(true);
let pollTimer = null;

// Modal & Reporting State
const showReportModal = ref(false);
const activeReportTab = ref('whatsapp'); // 'whatsapp', 'markdown', 'plaintext'
const copySuccess = ref(false);
const selectedHourlyItem = ref(null);

onMounted(() => {
    pollTimer = setInterval(() => {
        if (isAutoRefresh.value) {
            router.reload({ preserveScroll: true });
        }
    }, 5000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});

function toggleAutoRefresh() {
    isAutoRefresh.value = !isAutoRefresh.value;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
}

const currentWibTime = computed(() => {
    return new Date().toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
});

// Format 1: WhatsApp (Dengan format bold *teks*, italic _teks_, emoji)
const reportWhatsAppText = computed(() => {
    const evName = props.activeEvent?.name || 'Indomaret Fun Run 2026';
    const total = props.stats.totalParticipants || 0;
    const claimed = props.stats.totalClaimed || 0;
    const unclaimed = props.stats.totalUnclaimed || 0;
    const pct = props.stats.claimPercentage || 0;
    const unclaimPct = total > 0 ? (100 - pct).toFixed(1) : 0;
    const disputed = props.stats.disputedCount || 0;
    const peak = props.stats.peakHourFormatted || 'Belum Ada';
    const peakCount = props.stats.peakHourCount || 0;

    let text = `🏃‍♂️ *LAPORAN UPDATE RACE PACK COLLECTION (RPC)*\n`;
    text += `📌 *Event:* ${evName}\n`;
    text += `⏰ *Waktu Update:* ${currentWibTime.value}\n\n`;

    text += `📊 *RINGKASAN TOTAL:*\n`;
    text += `• Total Peserta : *${total.toLocaleString('id-ID')}* Peserta\n`;
    text += `• Sudah Ambil (Claimed) : *${claimed.toLocaleString('id-ID')}* Peserta (*${pct}%*)\n`;
    text += `• Belum Ambil : *${unclaimed.toLocaleString('id-ID')}* Peserta (*${unclaimPct}%*)\n`;
    text += `• Sengketa / Reset : *${disputed}* Kasus\n\n`;

    if (props.categories && props.categories.length > 0) {
        text += `🏷️ *PROGRES PER KATEGORI:*\n`;
        props.categories.forEach(cat => {
            text += `• *${cat.name}* : ${cat.claimed_count || 0} / ${cat.total_count || 0} (*${cat.percentage || 0}%*) • Sisa: ${(cat.unclaimed_count || 0)}\n`;
        });
        text += `\n`;
    }

    if (props.topCounters && props.topCounters.length > 0) {
        text += `🏢 *PERFORMA LOKET TERATAS:*\n`;
        props.topCounters.forEach((c, idx) => {
            text += `${idx + 1}. *${c.counter_name}* (${c.staff_name}) : *${c.total}* pengambilan\n`;
        });
        text += `\n`;
    }

    text += `⚡ *Jam Tersibuk (Peak):* ${peak} (${peakCount} pengambilan)\n\n`;
    text += `_Laporan otomatis digenerate dari Sistem Ticketing & Loket RPC._`;

    return text;
});

// Format 2: Markdown (GitHub Flavored Markdown dengan Heading & Tabel)
const reportMarkdownText = computed(() => {
    const evName = props.activeEvent?.name || 'Indomaret Fun Run 2026';
    const total = props.stats.totalParticipants || 0;
    const claimed = props.stats.totalClaimed || 0;
    const unclaimed = props.stats.totalUnclaimed || 0;
    const pct = props.stats.claimPercentage || 0;
    const unclaimPct = total > 0 ? (100 - pct).toFixed(1) : 0;
    const disputed = props.stats.disputedCount || 0;
    const peak = props.stats.peakHourFormatted || 'Belum Ada';
    const peakCount = props.stats.peakHourCount || 0;

    let md = `# 🏃‍♂️ Laporan Update Race Pack Collection (RPC)\n\n`;
    md += `**Event:** ${evName}  \n`;
    md += `**Waktu Update:** ${currentWibTime.value}  \n\n`;

    md += `## 📊 Ringkasan Total\n\n`;
    md += `| Indikator | Jumlah | Persentase |\n`;
    md += `| :--- | :--- | :--- |\n`;
    md += `| **Total Peserta** | ${total.toLocaleString('id-ID')} | 100% |\n`;
    md += `| **Sudah Ambil (Claimed)** | ${claimed.toLocaleString('id-ID')} | ${pct}% |\n`;
    md += `| **Belum Ambil (Unclaimed)** | ${unclaimed.toLocaleString('id-ID')} | ${unclaimPct}% |\n`;
    md += `| **Status Sengketa / Reset** | ${disputed} | - |\n\n`;

    if (props.categories && props.categories.length > 0) {
        md += `## 🏷️ Rincian Per Kategori\n\n`;
        md += `| Kategori | Diambil | Total | Persentase | Sisa |\n`;
        md += `| :--- | :--- | :--- | :--- | :--- |\n`;
        props.categories.forEach(cat => {
            md += `| **${cat.name}** | ${cat.claimed_count || 0} | ${cat.total_count || 0} | ${cat.percentage || 0}% | ${cat.unclaimed_count || 0} |\n`;
        });
        md += `\n`;
    }

    if (props.topCounters && props.topCounters.length > 0) {
        md += `## 🏢 Performa Loket\n\n`;
        props.topCounters.forEach((c, idx) => {
            md += `${idx + 1}. **${c.counter_name}** (${c.staff_name}): ${c.total} pengambilan\n`;
        });
        md += `\n`;
    }

    md += `**Jam Tersibuk (Peak):** ${peak} (${peakCount} scan)\n\n`;
    md += `---\n*Laporan otomatis digenerate dari Sistem Ticketing & Loket RPC.*`;

    return md;
});

// Format 3: Plain Text (SMS / Email format sederhana)
const reportPlainText = computed(() => {
    const evName = props.activeEvent?.name || 'Indomaret Fun Run 2026';
    const total = props.stats.totalParticipants || 0;
    const claimed = props.stats.totalClaimed || 0;
    const unclaimed = props.stats.totalUnclaimed || 0;
    const pct = props.stats.claimPercentage || 0;
    const unclaimPct = total > 0 ? (100 - pct).toFixed(1) : 0;
    const disputed = props.stats.disputedCount || 0;
    const peak = props.stats.peakHourFormatted || 'Belum Ada';
    const peakCount = props.stats.peakHourCount || 0;

    let txt = `LAPORAN UPDATE RACE PACK COLLECTION (RPC)\n`;
    txt += `Event: ${evName}\n`;
    txt += `Waktu Update: ${currentWibTime.value}\n\n`;

    txt += `RINGKASAN TOTAL:\n`;
    txt += `- Total Peserta: ${total.toLocaleString('id-ID')}\n`;
    txt += `- Sudah Ambil: ${claimed.toLocaleString('id-ID')} (${pct}%)\n`;
    txt += `- Belum Ambil: ${unclaimed.toLocaleString('id-ID')} (${unclaimPct}%)\n`;
    txt += `- Sengketa/Reset: ${disputed}\n\n`;

    if (props.categories && props.categories.length > 0) {
        txt += `PROGRES PER KATEGORI:\n`;
        props.categories.forEach(cat => {
            txt += `- ${cat.name}: ${cat.claimed_count || 0}/${cat.total_count || 0} (${cat.percentage || 0}%) - Sisa: ${cat.unclaimed_count || 0}\n`;
        });
        txt += `\n`;
    }

    if (props.topCounters && props.topCounters.length > 0) {
        txt += `PERFORMA LOKET:\n`;
        props.topCounters.forEach((c, idx) => {
            txt += `${idx + 1}. ${c.counter_name} (${c.staff_name}): ${c.total} pengambilan\n`;
        });
        txt += `\n`;
    }

    txt += `Jam Tersibuk: ${peak} (${peakCount} scan)\n`;
    return txt;
});

const currentReportContent = computed(() => {
    if (activeReportTab.value === 'markdown') return reportMarkdownText.value;
    if (activeReportTab.value === 'plaintext') return reportPlainText.value;
    return reportWhatsAppText.value;
});

function copyToClipboard() {
    navigator.clipboard.writeText(currentReportContent.value).then(() => {
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 2500);
    });
}

function shareToWhatsApp() {
    const encoded = encodeURIComponent(reportWhatsAppText.value);
    window.open(`https://api.whatsapp.com/send?text=${encoded}`, '_blank');
}

function selectHourBar(item) {
    if (selectedHourlyItem.value?.hour === item.hour) {
        selectedHourlyItem.value = null;
    } else {
        selectedHourlyItem.value = item;
    }
}
</script>

<template>
    <Head title="Executive Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <!-- Top Row: Page Title, Event Name, Subtitle -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white flex flex-wrap items-center gap-2 font-heading drop-shadow-md">
                            <span>Dashboard Penukaran BIB</span>
                            <span v-if="activeEvent" class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-white/20 text-white border border-white/30 backdrop-blur-md">
                                {{ activeEvent.name }}
                            </span>
                        </h2>
                        <p class="text-xs font-semibold text-white/90 mt-0.5">
                            Ringkasan statistik penukaran race pack &amp; analitik loket real-time.
                        </p>
                    </div>

                    <!-- Auto Refresh Status Indicator (Desktop/Tablet) -->
                    <div class="hidden sm:flex items-center gap-2">
                        <button
                            @click="toggleAutoRefresh"
                            :class="[
                                'px-3 py-1.5 rounded-full text-xs font-extrabold flex items-center gap-1.5 transition border backdrop-blur-md font-heading active:scale-95',
                                isAutoRefresh
                                    ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40 shadow-sm'
                                    : 'bg-slate-800/80 text-slate-400 border-slate-700'
                            ]"
                            title="Klik untuk aktif/nonaktifkan auto refresh real-time"
                        >
                            <span :class="isAutoRefresh ? 'w-2 h-2 rounded-full bg-emerald-400 animate-ping' : 'w-2 h-2 rounded-full bg-slate-500'"></span>
                            <span>{{ isAutoRefresh ? 'REALTIME (5s)' : 'PAUSED' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Header Actions Pill Ribbon (Mobile Horizontal Scrollable / Desktop Flex) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none -mx-1 px-1">
                    <!-- Tombol Resume Laporan WhatsApp / Markdown -->
                    <button
                        @click="showReportModal = true"
                        class="shrink-0 px-3.5 py-2 rounded-full bg-[#25D366] hover:bg-emerald-600 active:scale-95 text-white font-black text-xs uppercase tracking-wider shadow-md transition flex items-center gap-1.5 font-heading"
                        title="Buka Resume Laporan Siap Kirim ke WhatsApp / Markdown"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        <span class="whitespace-nowrap">Resume Laporan</span>
                    </button>

                    <!-- Auto Refresh (Mobile Only) -->
                    <button
                        @click="toggleAutoRefresh"
                        :class="[
                            'sm:hidden shrink-0 px-3 py-2 rounded-full text-xs font-extrabold flex items-center gap-1.5 transition border backdrop-blur-md font-heading active:scale-95',
                            isAutoRefresh
                                ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40 shadow-sm'
                                : 'bg-slate-800/80 text-slate-400 border-slate-700'
                        ]"
                    >
                        <span :class="isAutoRefresh ? 'w-2 h-2 rounded-full bg-emerald-400 animate-ping' : 'w-2 h-2 rounded-full bg-slate-500'"></span>
                        <span class="whitespace-nowrap">{{ isAutoRefresh ? 'LIVE (5s)' : 'PAUSED' }}</span>
                    </button>

                    <!-- Loket POS Shortcut -->
                    <Link
                        v-if="['admin', 'loket'].includes(role)"
                        href="/loket"
                        class="shrink-0 px-3.5 py-2 rounded-full bg-[#FFD400] hover:bg-yellow-400 active:scale-95 text-[#0B2A8A] font-extrabold text-xs uppercase tracking-wider shadow-md transition flex items-center gap-1.5 font-heading"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span class="whitespace-nowrap">Loket POS</span>
                    </Link>

                    <!-- Kiosk Cek Shortcut -->
                    <Link
                        href="/bib-check"
                        class="shrink-0 px-3.5 py-2 rounded-full bg-white/20 hover:bg-white/30 active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider border border-white/30 backdrop-blur-md transition flex items-center gap-1.5 font-heading"
                    >
                        <svg class="w-3.5 h-3.5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="whitespace-nowrap">Kiosk Cek</span>
                    </Link>

                    <!-- Import CSV Shortcut -->
                    <Link
                        v-if="role === 'admin'"
                        href="/import"
                        class="shrink-0 px-3.5 py-2 rounded-full bg-black/30 hover:bg-black/50 active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider border border-white/20 backdrop-blur-md transition flex items-center gap-1.5 font-heading"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="whitespace-nowrap">Import CSV</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-3.5 sm:space-y-4 w-full">
            <!-- Metric KPI Summary Cards (Mobile 2-Cols / Desktop 4-Cols) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <!-- Card 1: Total Peserta -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900 transition-all hover:scale-[1.01] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 font-heading">Total Peserta</span>
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 border border-blue-200 text-[#0E7BDC] flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                        </div>
                        <div class="mt-1.5 sm:mt-2 flex items-baseline gap-1">
                            <span class="text-2xl sm:text-3xl font-black text-[#0B2A8A] tracking-tight font-bib">{{ stats.totalParticipants.toLocaleString('id-ID') }}</span>
                            <span class="text-[10px] sm:text-[11px] font-bold text-slate-500">org</span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-bold text-slate-400 font-heading">
                        <span>KUOTA EVENT</span>
                        <span class="text-[#0E7BDC]">100%</span>
                    </div>
                </div>

                <!-- Card 2: Sudah Ditukar (Claimed) -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900 transition-all hover:scale-[1.01] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 font-heading">Sudah Ambil</span>
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-1.5 sm:mt-2 flex flex-wrap items-baseline gap-1">
                            <span class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight font-bib">{{ stats.totalClaimed.toLocaleString('id-ID') }}</span>
                            <span class="text-[10px] sm:text-[11px] font-extrabold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.2 rounded-md">({{ stats.claimPercentage }}%)</span>
                        </div>
                    </div>

                    <!-- Mini Progress Bar & Breakdown Detail -->
                    <div class="mt-2">
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.min(100, stats.claimPercentage || 0)}%` }"
                            ></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-[9px] font-extrabold text-slate-500 font-mono">
                            <span>{{ stats.claimedOnlyCount || 0 }} Klaim</span>
                            <span>{{ stats.checkedInCount || 0 }} Checkin</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Belum Ditukar (Unclaimed) -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900 transition-all hover:scale-[1.01] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 font-heading">Belum Ambil</span>
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-1.5 sm:mt-2 flex flex-wrap items-baseline gap-1">
                            <span class="text-2xl sm:text-3xl font-black text-amber-600 tracking-tight font-bib">{{ stats.totalUnclaimed.toLocaleString('id-ID') }}</span>
                            <span class="text-[10px] sm:text-[11px] font-bold text-amber-700">peserta</span>
                        </div>
                    </div>

                    <!-- Mini Progress Bar & Sisa Indicator -->
                    <div class="mt-2">
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-amber-400 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${stats.totalParticipants > 0 ? Math.max(0, Math.min(100, (stats.totalUnclaimed / stats.totalParticipants) * 100)) : 0}%` }"
                            ></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-[9px] font-extrabold text-amber-700 font-heading">
                            <span>SISA RACE PACK</span>
                            <span>{{ stats.totalParticipants > 0 ? (100 - (stats.claimPercentage || 0)).toFixed(1) : 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Sengketa & Loket Teramai (Mobile-Optimized Layout) -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900 transition-all hover:scale-[1.01] flex flex-col justify-between">
                    <!-- Sengketa Section -->
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 font-heading">Sengketa / Reset</span>
                            <div class="text-xl sm:text-2xl font-black text-rose-600 font-bib leading-tight mt-0.5">
                                {{ stats.disputedCount }} <span class="text-[10px] text-slate-500 font-sans font-bold">kasus</span>
                            </div>
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>

                    <!-- Top Loket Section -->
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                        <div class="min-w-0 flex-1">
                            <span class="font-extrabold text-purple-700 uppercase tracking-wider text-[9px] block font-heading">Top Loket:</span>
                            <span class="font-black text-slate-900 font-heading truncate block">{{ topCounters[0]?.counter_name || '-' }}</span>
                        </div>
                        <span class="font-extrabold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200 shrink-0 font-mono text-[9px]">
                            {{ topCounters[0]?.total || 0 }} scan
                        </span>
                    </div>
                </div>
            </div>

            <!-- PROGRES PER KATEGORI LOMBA (Responsive Grid Cards) -->
            <div v-if="categories && categories.length > 0" class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs sm:text-sm">🏷️</span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 font-heading leading-tight">Progres Penukaran Per Kategori</h3>
                            <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500">Status penukaran race pack berdasarkan kategori peserta.</p>
                        </div>
                    </div>
                    <span class="text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full border border-indigo-200 shrink-0">
                        {{ categories.length }} Kategori
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    <div
                        v-for="cat in categories"
                        :key="cat.id"
                        class="bg-slate-50/90 rounded-xl p-3 border border-slate-200 hover:border-[#0E7BDC]/50 transition"
                    >
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-black text-slate-900 font-heading truncate">{{ cat.name }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-blue-100 text-[#0B2A8A] font-mono shrink-0">
                                {{ cat.percentage || 0 }}%
                            </span>
                        </div>

                        <div class="mt-1.5 flex items-baseline justify-between text-[11px]">
                            <span class="font-extrabold text-emerald-600 font-bib text-sm">
                                {{ (cat.claimed_count || 0).toLocaleString('id-ID') }}
                                <span class="text-[10px] font-sans font-semibold text-slate-500">/ {{ (cat.total_count || 0).toLocaleString('id-ID') }}</span>
                            </span>
                            <span class="text-[10px] font-bold text-amber-600">
                                Sisa: {{ (cat.unclaimed_count || 0).toLocaleString('id-ID') }}
                            </span>
                        </div>

                        <!-- Category Progress Bar -->
                        <div class="mt-1.5 w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-blue-500 to-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.min(100, cat.percentage || 0)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAFIK JAM PEAK PENUKARAN (06:00 - 20:00 WIB - Responsive Touch & Scroll) -->
            <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs sm:text-sm">📊</span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 font-heading leading-tight">Grafik Volume Penukaran Per Jam (Peak Hours)</h3>
                            <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500">Distribusi jam ramai penukaran race pack di loket.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <span class="text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-200">
                            ⚡ Peak: {{ stats.peakHourFormatted }} ({{ stats.peakHourCount }} scan)
                        </span>
                    </div>
                </div>

                <!-- Selected Hour Mobile Banner (If bar tapped) -->
                <div v-if="selectedHourlyItem" class="mb-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between text-xs animate-in fade-in duration-150">
                    <span class="font-bold text-blue-900 font-heading">
                        ⏰ Jam {{ selectedHourlyItem.hour }}: <strong class="text-[#0E7BDC] font-mono">{{ selectedHourlyItem.count }} scan penukaran</strong>
                    </span>
                    <button @click="selectedHourlyItem = null" class="text-slate-400 hover:text-slate-700 text-xs font-black">✕</button>
                </div>

                <!-- Interactive Bar Chart with Mobile Smooth Scroll Container -->
                <div class="w-full overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
                    <div class="min-w-[480px] sm:min-w-0">
                        <div class="h-28 w-full flex items-end gap-1 sm:gap-1.5 pt-4 pb-1 border-b border-slate-200 px-1">
                            <div
                                v-for="item in hourlyChart"
                                :key="item.hour"
                                @click="selectHourBar(item)"
                                class="flex-1 flex flex-col items-center h-full justify-end group relative cursor-pointer"
                            >
                                <!-- Tooltip Hover / Tap -->
                                <div
                                    :class="[
                                        'absolute -top-7 transition text-white text-[10px] font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap z-20 font-mono',
                                        selectedHourlyItem?.hour === item.hour ? 'opacity-100 bg-[#0E7BDC]' : 'opacity-0 group-hover:opacity-100 bg-slate-900'
                                    ]"
                                >
                                    {{ item.hour }}: {{ item.count }} scan
                                </div>

                                <!-- Bar Container -->
                                <div
                                    :class="[
                                        'w-full rounded-t-md h-full flex items-end overflow-hidden border-x border-t transition-all',
                                        selectedHourlyItem?.hour === item.hour ? 'border-[#0E7BDC] ring-2 ring-[#0E7BDC]/30 bg-blue-50' : 'border-slate-200 bg-slate-100'
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'w-full transition-all duration-700 rounded-t-sm',
                                            item.count === maxHourlyCount && maxHourlyCount > 0
                                                ? 'bg-gradient-to-t from-[#0E7BDC] to-[#FFD400]'
                                                : item.count > 0 ? 'bg-gradient-to-t from-[#0E7BDC] to-sky-400' : 'bg-slate-200'
                                        ]"
                                        :style="{ height: maxHourlyCount > 0 ? `${Math.max(item.count > 0 ? 12 : 4, (item.count / maxHourlyCount) * 100)}%` : '4%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart X-Axis Labels -->
                        <div class="flex justify-between w-full pt-1.5 px-1 text-[9px] sm:text-[10px] font-bold text-slate-500 font-mono">
                            <span
                                v-for="item in hourlyChart"
                                :key="item.hour"
                                @click="selectHourBar(item)"
                                :class="[
                                    'flex-1 text-center truncate cursor-pointer transition',
                                    selectedHourlyItem?.hour === item.hour ? 'text-[#0E7BDC] font-black scale-110' : ''
                                ]"
                            >
                                {{ item.hour.substring(0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="sm:hidden text-center text-[9px] font-semibold text-slate-400 mt-1">
                    👆 Ketuk batang grafik untuk melihat rincian jam
                </div>
            </div>

            <!-- Live Counter Performance Breakdown & Recent Activity Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4">
                <!-- Left Column (7 cols): Kinerja Loket Counter -->
                <div class="lg:col-span-7 bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-1.5 font-heading">
                                <svg class="w-4 h-4 text-[#0E7BDC] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Produktivitas &amp; Kinerja Loket
                            </h3>
                            <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500">Perbandingan transaksi penukaran tiap meja loket.</p>
                        </div>
                        <span class="text-[9px] sm:text-[10px] font-extrabold text-blue-700 bg-blue-50 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full border border-blue-200 shrink-0">
                            {{ counterPerformance.length }} Loket
                        </span>
                    </div>

                    <div v-if="counterPerformance.length === 0" class="text-center py-6 text-slate-400 text-xs font-medium">
                        Belum ada data aktivitas loket counter.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="(c, idx) in counterPerformance"
                            :key="c.id"
                            class="bg-slate-50/90 rounded-xl p-3 border border-slate-200 hover:border-[#0E7BDC]/60 transition"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-900 font-heading flex items-center gap-1.5 truncate">
                                    <span v-if="idx === 0" class="text-xs">🥇</span>
                                    <span v-else-if="idx === 1" class="text-xs">🥈</span>
                                    <span v-else-if="idx === 2" class="text-xs">🥉</span>
                                    <span v-else class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="truncate">{{ c.counter_name }}</span>
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-blue-100 text-[#0B2A8A] font-mono shrink-0">
                                    {{ c.total_scans }} Scan
                                </span>
                            </div>

                            <div class="flex items-baseline justify-between mt-2 text-[11px] font-semibold">
                                <span class="text-slate-500 truncate max-w-[130px]">Petugas: <strong class="text-slate-800">{{ c.staff_name }}</strong></span>
                                <span class="font-extrabold text-[#0E7BDC] text-[10px] font-mono">{{ c.percentage }}%</span>
                            </div>

                            <div class="mt-1.5 w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-emerald-500 to-[#0E7BDC] h-2 rounded-full transition-all duration-500"
                                    :style="{ width: `${Math.min(100, c.percentage || 0)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (5 cols): Log Penukaran Terkini (Compact Table / Feed) -->
                <div class="lg:col-span-5 bg-white/95 backdrop-blur-xl rounded-2xl p-3.5 sm:p-4 border-2 border-white shadow-lg text-slate-900">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-1.5 font-heading">
                            <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Log Penukaran Terkini
                        </h3>
                        <span class="text-[9px] sm:text-[10px] font-extrabold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full shrink-0">5 Terakhir</span>
                    </div>

                    <div v-if="recentActivity.length === 0" class="text-center py-6 text-slate-400 text-xs font-medium">
                        Belum ada transaksi penukaran.
                    </div>

                    <div v-else class="overflow-hidden">
                        <div class="divide-y divide-slate-100">
                            <div
                                v-for="act in recentActivity.slice(0, 5)"
                                :key="act.id"
                                class="py-2.5 flex items-center justify-between gap-2 text-xs hover:bg-blue-50/50 px-1 rounded-lg transition"
                            >
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="font-extrabold font-bib text-[#0B2A8A] text-sm shrink-0 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">
                                        #{{ act.bib_number }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 truncate text-xs">{{ act.participant?.full_name || '-' }}</div>
                                        <div v-if="act.participant?.category" class="text-[10px] text-slate-400 font-semibold truncate">
                                            {{ act.participant.category.name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-[10px] font-semibold text-slate-500 font-mono">{{ formatDate(act.created_at) }}</div>
                                    <div class="text-[10px] text-[#0E7BDC] font-extrabold">{{ act.performed_by?.name || 'Loket System' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL RESUME LAPORAN (WHATSAPP / MARKDOWN / PLAIN TEXT - Mobile Optimized) -->
        <div
            v-if="showReportModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/80 backdrop-blur-md transition-all"
            @click.self="showReportModal = false"
        >
            <div class="bg-white rounded-3xl shadow-2xl border-2 border-white w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-[#0B2A8A] to-[#0E7BDC] p-4 sm:p-5 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#25D366] text-white flex items-center justify-center shadow-lg shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm sm:text-base font-black font-heading leading-tight truncate">Resume Laporan Real-Time</h3>
                            <p class="text-[10px] sm:text-xs text-white/80 font-medium truncate">Format siap kirim ke WhatsApp Panitia / Manajemen</p>
                        </div>
                    </div>
                    <button
                        @click="showReportModal = false"
                        class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition shrink-0 active:scale-90"
                    >
                        ✕
                    </button>
                </div>

                <!-- Format Selection Tabs (Responsive) -->
                <div class="px-3 sm:px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <button
                            @click="activeReportTab = 'whatsapp'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1 font-heading active:scale-95',
                                activeReportTab === 'whatsapp'
                                    ? 'bg-[#25D366] text-white shadow-md'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
                            ]"
                        >
                            <span>📱 WhatsApp</span>
                        </button>
                        <button
                            @click="activeReportTab = 'markdown'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1 font-heading active:scale-95',
                                activeReportTab === 'markdown'
                                    ? 'bg-[#0E7BDC] text-white shadow-md'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
                            ]"
                        >
                            <span>📝 Markdown</span>
                        </button>
                        <button
                            @click="activeReportTab = 'plaintext'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1 font-heading active:scale-95',
                                activeReportTab === 'plaintext'
                                    ? 'bg-slate-800 text-white shadow-md'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
                            ]"
                        >
                            <span>📄 Text</span>
                        </button>
                    </div>

                    <span class="text-[10px] font-bold text-slate-400 font-mono hidden sm:inline-block">
                        {{ currentWibTime }}
                    </span>
                </div>

                <!-- Content Textarea Preview Box -->
                <div class="p-3 sm:p-5 flex-1 overflow-y-auto">
                    <div class="relative h-full">
                        <textarea
                            :value="currentReportContent"
                            readonly
                            rows="12"
                            class="w-full font-mono text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-2xl p-3 sm:p-4 focus:ring-2 focus:ring-[#0E7BDC] focus:outline-none resize-none leading-relaxed shadow-inner"
                        ></textarea>

                        <!-- Copy Feedback Overlay Banner -->
                        <div
                            v-if="copySuccess"
                            class="absolute inset-0 bg-emerald-900/85 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center text-white font-heading animate-in fade-in duration-200 p-4 text-center"
                        >
                            <div class="w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center text-2xl font-black mb-2 shadow-lg">
                                ✓
                            </div>
                            <span class="text-sm font-black uppercase tracking-wider">Berhasil Disalin!</span>
                            <span class="text-xs text-emerald-200 mt-0.5">Silakan paste (Ctrl+V) di WhatsApp atau dokumen panitia.</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer & Action Buttons (Mobile-Optimized Full-Width Stacking) -->
                <div class="p-3 sm:p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 shrink-0">
                    <div class="text-[10px] font-semibold text-slate-400 hidden sm:block">
                        ⚡ Data selalu sinkron otomatis sesuai transaksi penukaran.
                    </div>

                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <button
                            @click="copyToClipboard"
                            :class="[
                                'px-3.5 py-2.5 rounded-full font-extrabold text-xs uppercase tracking-wider shadow-md transition flex items-center justify-center gap-1.5 font-heading active:scale-95',
                                copySuccess
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-[#0B2A8A] hover:bg-blue-900 text-white'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>{{ copySuccess ? 'Tersalin!' : 'Salin' }}</span>
                        </button>

                        <button
                            v-if="activeReportTab === 'whatsapp'"
                            @click="shareToWhatsApp"
                            class="px-3.5 py-2.5 rounded-full bg-[#25D366] hover:bg-emerald-600 active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider shadow-md transition flex items-center justify-center gap-1.5 font-heading"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            <span class="whitespace-nowrap">WhatsApp</span>
                        </button>

                        <button
                            @click="showReportModal = false"
                            class="col-span-2 sm:col-auto px-4 py-2.5 rounded-full bg-slate-200 hover:bg-slate-300 active:scale-95 text-slate-700 font-extrabold text-xs uppercase tracking-wider transition font-heading text-center"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
