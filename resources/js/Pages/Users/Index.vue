<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    availablePermissions: Array,
    rolePermissionsMap: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const activeTab = ref('users'); // 'users' | 'matrix'

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingUser = ref(null);

// State lokal untuk form Matriks Role Permissions
const matrixForm = useForm({
    role_permissions: props.rolePermissionsMap || {
        admin: ['access-dashboard', 'access-loket', 'access-bib-check', 'access-import', 'access-users', 'access-reset-claim'],
        loket: ['access-dashboard', 'access-loket', 'access-bib-check'],
        undian: ['access-dashboard', 'access-bib-check'],
        viewer: ['access-dashboard', 'access-bib-check'],
    },
});

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'loket',
    counter_number: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'loket',
    counter_number: '',
    is_active: true,
    custom_permissions: [],
});

function handleFilter() {
    router.get('/users', {
        search: search.value,
        role: selectedRole.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function submitCreate() {
    createForm.post('/users', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(user) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.role = user.role;
    editForm.counter_number = user.counter_number || '';
    editForm.is_active = Boolean(user.is_active);
    editForm.custom_permissions = user.custom_permissions || [];
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingUser.value) return;
    editForm.put(`/users/${editingUser.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
            editingUser.value = null;
        },
    });
}

function submitMatrix() {
    matrixForm.post('/users/role-permissions', {
        onSuccess: () => {
            alert('Matriks Hak Akses Role berhasil diperbarui!');
        },
    });
}

function isRolePermChecked(role, permCode) {
    const list = matrixForm.role_permissions[role] || [];
    return list.includes(permCode);
}

function toggleRolePerm(role, permCode) {
    if (!matrixForm.role_permissions[role]) {
        matrixForm.role_permissions[role] = [];
    }
    const index = matrixForm.role_permissions[role].indexOf(permCode);
    if (index > -1) {
        matrixForm.role_permissions[role].splice(index, 1);
    } else {
        matrixForm.role_permissions[role].push(permCode);
    }
}

function deleteUser(user) {
    if (user.id === currentUser.value.id) {
        alert('Anda tidak bisa menghapus akun Anda sendiri.');
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus user ${user.name}?`)) {
        router.delete(`/users/${user.id}`);
    }
}

function getRoleBadgeClass(role) {
    switch (role) {
        case 'admin':
            return 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
        case 'loket':
            return 'bg-purple-500/20 text-purple-300 border-purple-500/30';
        case 'undian':
            return 'bg-amber-500/20 text-amber-300 border-amber-500/30';
        default:
            return 'bg-slate-500/20 text-slate-300 border-slate-500/30';
    }
}
</script>

<template>
    <Head title="Manajemen User &amp; Hak Akses Role" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2 font-heading drop-shadow-md">
                        <svg class="w-6 h-6 text-yellow-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Manajemen User &amp; Hak Akses</span>
                    </h2>
                    <p class="text-xs sm:text-sm font-semibold text-white/90 mt-0.5">Pengaturan akun petugas dan Matriks Hak Akses Modul per Role</p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2 sm:px-5 sm:py-2.5 rounded-full bg-[#FFD400] hover:bg-yellow-400 active:scale-95 text-[#0B2A8A] font-black text-xs uppercase tracking-wider shadow-lg transition flex items-center gap-1.5 font-heading"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah User Baru</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full space-y-4">
            <!-- TAB SWITCHER NAV (Sleek Segmented Pill Switcher) -->
            <div class="grid grid-cols-2 p-1 rounded-2xl bg-black/25 border border-white/20 backdrop-blur-md">
                <button
                    @click="activeTab = 'users'"
                    :class="[
                        'py-2.5 px-3 text-xs sm:text-sm font-black rounded-xl transition flex items-center justify-center gap-1.5 font-heading active:scale-95',
                        activeTab === 'users'
                            ? 'bg-[#FFD400] text-[#0B2A8A] shadow-md'
                            : 'text-white/90 hover:text-white hover:bg-white/10'
                    ]"
                >
                    <span>👥</span>
                    <span class="truncate">Daftar Petugas</span>
                </button>

                <button
                    @click="activeTab = 'matrix'"
                    :class="[
                        'py-2.5 px-3 text-xs sm:text-sm font-black rounded-xl transition flex items-center justify-center gap-1.5 font-heading active:scale-95',
                        activeTab === 'matrix'
                            ? 'bg-[#FFD400] text-[#0B2A8A] shadow-md'
                            : 'text-white/90 hover:text-white hover:bg-white/10'
                    ]"
                >
                    <span>🔐</span>
                    <span class="truncate">Matriks Hak Akses</span>
                </button>
            </div>

            <!-- TAB 1: DAFTAR USER & PETUGAS -->
            <div v-if="activeTab === 'users'" class="space-y-4">
                <!-- Filter Bar -->
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border-2 border-white shadow-xl text-slate-900">
                    <form @submit.prevent="handleFilter" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                        <div class="sm:col-span-6">
                            <input
                                v-model="search"
                                @keyup.enter="handleFilter"
                                type="text"
                                placeholder="Cari nama, email, atau loket..."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border-2 border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#0E7BDC] focus:ring-0 shadow-sm"
                            />
                        </div>
                        <div class="sm:col-span-4">
                            <select
                                v-model="selectedRole"
                                @change="handleFilter"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border-2 border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:outline-none focus:border-[#0E7BDC] focus:ring-0 shadow-sm"
                            >
                                <option value="">Semua Role (All)</option>
                                <option v-for="r in roles" :key="r" :value="r">{{ r.toUpperCase() }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <button
                                type="submit"
                                class="w-full py-2.5 px-4 bg-[#0E7BDC] hover:bg-blue-600 active:scale-95 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md transition flex items-center justify-center gap-1 font-heading"
                            >
                                <span>Filter</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 1. MOBILE CARD VIEW (Tampil di Layar HP & Tablet Kecil) -->
                <div class="block md:hidden space-y-3">
                    <div
                        v-for="u in users.data"
                        :key="u.id"
                        class="bg-white/95 backdrop-blur-xl rounded-2xl p-4 border-2 border-white shadow-lg text-slate-900 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0B2A8A] to-[#0E7BDC] text-white font-black flex items-center justify-center uppercase font-heading text-base shadow-sm shrink-0">
                                    {{ u.name.charAt(0) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-extrabold text-sm text-slate-900 font-heading leading-tight truncate">
                                        {{ u.name }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono font-medium truncate mt-0.5">
                                        {{ u.email }}
                                    </div>
                                </div>
                            </div>
                            <span
                                :class="u.is_active ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-100 text-rose-800 border-rose-300'"
                                class="px-2 py-0.5 rounded-full text-[9px] font-extrabold border shrink-0 uppercase"
                            >
                                {{ u.is_active ? 'Aktif' : 'Non-aktif' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                            <div>
                                <span class="text-[9px] block text-slate-400 font-bold uppercase">Role Akses:</span>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-100 text-[#0B2A8A] border border-blue-200">
                                    {{ u.role }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] block text-slate-400 font-bold uppercase">Identitas Loket:</span>
                                <strong class="font-mono text-slate-800 text-[11px] block mt-0.5">{{ u.counter_number || '-' }}</strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button
                                @click="openEditModal(u)"
                                class="flex-1 py-2 px-3 bg-[#0E7BDC] hover:bg-blue-600 active:scale-95 text-white rounded-xl font-extrabold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1 font-heading"
                            >
                                <span>✏️ Edit Role / Data</span>
                            </button>
                            <button
                                v-if="u.id !== currentUser.id"
                                @click="deleteUser(u)"
                                class="py-2 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 active:scale-95 rounded-xl font-bold text-xs transition flex items-center justify-center"
                                title="Hapus User"
                            >
                                <span>🗑️</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="users.data.length === 0" class="bg-white/95 rounded-2xl p-6 text-center text-slate-500 text-xs font-semibold">
                        Tidak ada data user ditemukan.
                    </div>
                </div>

                <!-- 2. DESKTOP TABLE VIEW (Tampil di Layar Lebar >= md) -->
                <div class="hidden md:block bg-white/95 backdrop-blur-xl rounded-3xl border-2 border-white overflow-hidden shadow-xl text-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-800">
                            <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3.5">Nama Petugas</th>
                                    <th class="px-4 py-3.5">Email</th>
                                    <th class="px-4 py-3.5">Role Hak Akses</th>
                                    <th class="px-4 py-3.5">Identitas Counter/Loket</th>
                                    <th class="px-4 py-3.5">Status</th>
                                    <th class="px-4 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in users.data" :key="u.id" class="hover:bg-blue-50/60 transition">
                                    <td class="px-4 py-3.5 font-bold text-slate-900 flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-2xl bg-blue-100 text-[#0B2A8A] font-black flex items-center justify-center uppercase font-heading text-sm shrink-0">
                                            {{ u.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-sm text-slate-900 font-heading">{{ u.name }}</div>
                                            <span v-if="u.id === currentUser.id" class="text-[10px] text-[#0E7BDC] font-mono font-bold">(Akun Anda Saat Ini)</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-600 font-semibold font-mono">{{ u.email }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-blue-100 text-[#0B2A8A] border border-blue-200">
                                            {{ u.role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-slate-700 font-bold">
                                        {{ u.counter_number || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="u.is_active" class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-rose-500 font-semibold text-xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Non-aktif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button
                                            @click="openEditModal(u)"
                                            class="px-3 py-1.5 rounded-lg bg-[#0E7BDC] hover:bg-blue-600 text-white text-xs font-bold transition active:scale-95"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="u.id !== currentUser.id"
                                            @click="deleteUser(u)"
                                            class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition active:scale-95"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="users.data.length === 0" class="p-8 text-center text-slate-500 text-xs">
                        Tidak ada data user ditemukan.
                    </div>
                </div>
            </div>

            <!-- TAB 2: MATRIKS HAK AKSES ROLE (PERMISSIONS MATRIX) -->
            <div v-else-if="activeTab === 'matrix'" class="space-y-4">
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-4 sm:p-5 border-2 border-white shadow-xl text-slate-900 space-y-1.5">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-2 font-heading">
                        <span>🔐</span> Pengaturan Izin Akses Per Role (Modul Access Control)
                    </h3>
                    <p class="text-xs text-slate-600">
                        Atur modul mana saja yang dapat diakses oleh setiap tingkatan Role (ADMIN, LOKET, UNDIAN, VIEWER). Centang kotak izin untuk memberikan hak akses.
                    </p>
                    <div class="block sm:hidden text-[10px] font-bold text-[#0E7BDC] pt-1">
                        👉 Geser tabel ke samping untuk melihat semua role
                    </div>
                </div>

                <form @submit.prevent="submitMatrix" class="space-y-4">
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl border-2 border-white overflow-hidden shadow-xl text-slate-900">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-800 min-w-[560px]">
                                <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3.5 w-2/5">Fitur / Modul Sistem</th>
                                        <th v-for="r in roles" :key="r" class="px-3 py-3.5 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-blue-100 text-[#0B2A8A] border border-blue-200">
                                                {{ r }}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="perm in availablePermissions" :key="perm.code" class="hover:bg-blue-50/50 transition">
                                        <td class="px-4 py-3.5">
                                            <div class="font-extrabold text-slate-900 text-xs sm:text-sm">{{ perm.name }}</div>
                                            <div class="text-[10px] sm:text-[11px] text-slate-500 mt-0.5">{{ perm.description }}</div>
                                            <code class="text-[9px] sm:text-[10px] text-[#0E7BDC] font-mono mt-1 inline-block bg-blue-50 px-1.5 py-0.5 rounded">{{ perm.code }}</code>
                                        </td>

                                        <!-- Checkbox untuk tiap Role -->
                                        <td v-for="r in roles" :key="r" class="px-3 py-3.5 text-center">
                                            <!-- Role Admin selalu checked penuh -->
                                            <input
                                                v-if="r === 'admin'"
                                                type="checkbox"
                                                checked
                                                disabled
                                                class="rounded bg-slate-200 border-slate-300 text-[#0E7BDC] w-5 h-5 cursor-not-allowed opacity-80"
                                                title="Role ADMIN selalu memiliki akses penuh"
                                            />
                                            <input
                                                v-else
                                                type="checkbox"
                                                :checked="isRolePermChecked(r, perm.code)"
                                                @change="toggleRolePerm(r, perm.code)"
                                                class="rounded bg-white border-slate-400 text-[#0E7BDC] focus:ring-[#0E7BDC] w-5 h-5 cursor-pointer"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="matrixForm.processing"
                            class="w-full sm:w-auto px-6 py-3 rounded-full bg-gradient-to-r from-[#0E7BDC] to-[#0B2A8A] hover:from-[#0B2A8A] hover:to-[#0E7BDC] active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2 font-heading"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ matrixForm.processing ? 'Menyimpan...' : 'Simpan Matriks Hak Akses Role' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create User Modal (Clean Light Card Design) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl w-full max-w-md p-5 sm:p-6 border-2 border-white shadow-2xl space-y-4 text-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-base font-extrabold font-heading text-slate-900">Tambah User / Petugas Baru</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 font-black text-base">✕</button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Nama Lengkap</label>
                        <input v-model="createForm.name" type="text" required class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="Nama Petugas" />
                        <p v-if="createForm.errors.name" class="text-rose-600 text-xs mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Alamat Email</label>
                        <input v-model="createForm.email" type="email" required class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="email@event.com" />
                        <p v-if="createForm.errors.email" class="text-rose-600 text-xs mt-1">{{ createForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Password</label>
                        <input v-model="createForm.password" type="password" required class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="Minimal 8 karakter" />
                        <p v-if="createForm.errors.password" class="text-rose-600 text-xs mt-1">{{ createForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Hak Akses Role Utama</label>
                        <select v-model="createForm.role" class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]">
                            <option value="admin">ADMIN (Akses Penuh Seluruh Modul)</option>
                            <option value="loket">LOKET (Operator Penukaran BIB)</option>
                            <option value="undian">UNDIAN (Operator Modul Lottery)</option>
                            <option value="viewer">VIEWER (Read Only / Kiosk Check)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Identitas Counter Loket (Opsional)</label>
                        <input v-model="createForm.counter_number" type="text" class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="Misal: Loket-01" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" v-model="createForm.is_active" id="create_active" class="rounded bg-white border-slate-300 text-[#0E7BDC] focus:ring-[#0E7BDC]" />
                        <label for="create_active" class="text-xs text-slate-700 font-bold cursor-pointer">Akun Aktif</label>
                    </div>

                    <div class="flex gap-2.5 pt-2">
                        <button type="submit" :disabled="createForm.processing" class="flex-1 py-2.5 bg-[#0E7BDC] hover:bg-blue-600 active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md font-heading">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan User Baru' }}
                        </button>
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal (Clean Light Card Design) -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl w-full max-w-lg p-5 sm:p-6 border-2 border-white shadow-2xl space-y-4 text-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="text-base font-extrabold font-heading text-slate-900">Edit User &amp; Pengaturan Izin</h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 font-black text-base">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Nama Lengkap</label>
                        <input v-model="editForm.name" type="text" required class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" />
                        <p v-if="editForm.errors.name" class="text-rose-600 text-xs mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Alamat Email</label>
                        <input v-model="editForm.email" type="email" required class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" />
                        <p v-if="editForm.errors.email" class="text-rose-600 text-xs mt-1">{{ editForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Password Baru (Opsional)</label>
                        <input v-model="editForm.password" type="password" class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="Biarkan kosong jika tidak diubah" />
                        <p v-if="editForm.errors.password" class="text-rose-600 text-xs mt-1">{{ editForm.errors.password }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Hak Akses Role</label>
                            <select v-model="editForm.role" class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]">
                                <option value="admin">ADMIN</option>
                                <option value="loket">LOKET</option>
                                <option value="undian">UNDIAN</option>
                                <option value="viewer">VIEWER</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1 font-heading">Identitas Counter Loket</label>
                            <input v-model="editForm.counter_number" type="text" class="w-full px-3.5 py-2 rounded-xl bg-white border-2 border-slate-300 text-slate-900 text-sm focus:outline-none focus:border-[#0E7BDC]" placeholder="Misal: Loket-01" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="editForm.is_active" id="edit_active" class="rounded bg-white border-slate-300 text-[#0E7BDC] focus:ring-[#0E7BDC]" />
                        <label for="edit_active" class="text-xs text-slate-700 font-bold cursor-pointer">Akun Aktif</label>
                    </div>

                    <div class="flex gap-2.5 pt-2">
                        <button type="submit" :disabled="editForm.processing" class="flex-1 py-2.5 bg-[#0E7BDC] hover:bg-blue-600 active:scale-95 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md font-heading">
                            {{ editForm.processing ? 'Menyimpan...' : 'Update Data User' }}
                        </button>
                        <button type="button" @click="showEditModal = false" class="px-4 py-2.5 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
