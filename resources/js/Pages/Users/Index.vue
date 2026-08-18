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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-3 font-heading drop-shadow-md">
                        <svg class="w-7 h-7 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Manajemen User &amp; Hak Akses Role
                    </h2>
                    <p class="text-sm font-semibold text-white/90 mt-1">Pengaturan akun petugas dan Matriks Hak Akses Modul per Role &amp; User</p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="showCreateModal = true"
                        class="px-5 py-3 rounded-full bg-[#FFD400] hover:bg-yellow-400 text-[#0B2A8A] font-extrabold text-sm uppercase tracking-wider shadow-lg transition flex items-center gap-2 hover:scale-105"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Tambah User Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full space-y-6">
            <!-- TAB SWITCHER NAV -->
            <div class="flex border-b border-white/20 space-x-4">
                <button
                    @click="activeTab = 'users'"
                    :class="[
                        'pb-3 px-5 text-sm font-extrabold border-b-4 transition flex items-center gap-2 font-heading',
                        activeTab === 'users'
                            ? 'border-yellow-300 text-white'
                            : 'border-transparent text-white/70 hover:text-white'
                    ]"
                >
                    <span>👥 DAFTAR USER &amp; PETUGAS</span>
                </button>

                <button
                    @click="activeTab = 'matrix'"
                    :class="[
                        'pb-3 px-5 text-sm font-extrabold border-b-4 transition flex items-center gap-2 font-heading',
                        activeTab === 'matrix'
                            ? 'border-yellow-300 text-white'
                            : 'border-transparent text-white/70 hover:text-white'
                    ]"
                >
                    <span>🔐 MATRIKS HAK AKSES ROLE</span>
                </button>
            </div>

            <!-- TAB 1: DAFTAR USER & PETUGAS (Full Width) -->
            <div v-if="activeTab === 'users'" class="space-y-6">
                <!-- Filter Bar -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-5 border-2 border-white shadow-xl flex flex-col sm:flex-row gap-3 items-center justify-between text-slate-900">
                    <div class="flex flex-1 gap-3 w-full sm:w-auto">
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari nama, email, atau loket..."
                            class="w-full sm:w-72 px-4 py-2.5 rounded-2xl bg-white border-2 border-slate-300 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#0E7BDC] focus:ring-0 shadow-sm"
                        />
                        <select
                            v-model="selectedRole"
                            @change="handleFilter"
                            class="px-4 py-2.5 rounded-2xl bg-white border-2 border-slate-300 text-sm font-semibold text-slate-900 focus:outline-none focus:border-[#0E7BDC] focus:ring-0 shadow-sm"
                        >
                            <option value="">Semua Role</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ r.toUpperCase() }}</option>
                        </select>
                    </div>
                    <button @click="handleFilter" class="px-5 py-2.5 bg-[#0E7BDC] hover:bg-blue-600 text-white font-extrabold text-xs uppercase tracking-wider rounded-full shadow-md transition">
                        Filter Data
                    </button>
                </div>

                <!-- Users Table -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl border-2 border-white overflow-hidden shadow-xl text-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-800">
                            <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3.5 rounded-l-xl">Nama Petugas</th>
                                    <th class="px-4 py-3.5">Email</th>
                                    <th class="px-4 py-3.5">Role Hak Akses</th>
                                    <th class="px-4 py-3.5">Identitas Counter/Loket</th>
                                    <th class="px-4 py-3.5">Status</th>
                                    <th class="px-4 py-3.5 text-right rounded-r-xl">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in users.data" :key="u.id" class="hover:bg-blue-50/60 transition">
                                    <td class="px-4 py-3.5 font-bold text-slate-900 flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-2xl bg-blue-100 text-[#0B2A8A] font-black flex items-center justify-center uppercase font-heading text-sm">
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
                                        <span v-if="u.is_active" class="inline-flex items-center gap-1 text-emerald-400 font-semibold">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Aktif
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-rose-400 font-semibold">
                                            <span class="w-2 h-2 rounded-full bg-rose-400"></span> Non-aktif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button
                                            @click="openEditModal(u)"
                                            class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 border border-indigo-500/30 text-xs font-bold transition"
                                        >
                                            Edit Role / Data
                                        </button>
                                        <button
                                            v-if="u.id !== currentUser.id"
                                            @click="deleteUser(u)"
                                            class="px-3 py-1.5 rounded-lg bg-rose-600/20 hover:bg-rose-600/40 text-rose-300 border border-rose-500/30 text-xs font-bold transition"
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
            <div v-else-if="activeTab === 'matrix'" class="space-y-6">
                <div class="glass-panel rounded-2xl p-5 border border-indigo-500/30 space-y-2">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>🔐</span> Pengaturan Izin Akses Per Role (Modul Access Control)
                    </h3>
                    <p class="text-xs text-slate-400">
                        Atur modul mana saja yang dapat diakses oleh setiap tingkatan Role (ADMIN, LOKET, UNDIAN, VIEWER). Centang kotak izin untuk memberikan hak akses.
                    </p>
                </div>

                <form @submit.prevent="submitMatrix" class="space-y-6">
                    <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-800">
                                    <tr>
                                        <th class="px-5 py-4 w-2/5">Fitur / Modul Sistem</th>
                                        <th v-for="r in roles" :key="r" class="px-4 py-4 text-center">
                                            <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getRoleBadgeClass(r)]">
                                                {{ r }}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr v-for="perm in availablePermissions" :key="perm.code" class="hover:bg-slate-800/30 transition">
                                        <td class="px-5 py-4">
                                            <div class="font-bold text-white text-sm">{{ perm.name }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">{{ perm.description }}</div>
                                            <code class="text-[10px] text-indigo-400 font-mono mt-1 inline-block">{{ perm.code }}</code>
                                        </td>

                                        <!-- Checkbox untuk tiap Role -->
                                        <td v-for="r in roles" :key="r" class="px-4 py-4 text-center">
                                            <!-- Role Admin selalu checked penuh -->
                                            <input
                                                v-if="r === 'admin'"
                                                type="checkbox"
                                                checked
                                                disabled
                                                class="rounded bg-slate-900 border-slate-700 text-indigo-600 w-5 h-5 cursor-not-allowed opacity-80"
                                                title="Role ADMIN selalu memiliki akses penuh"
                                            />
                                            <input
                                                v-else
                                                type="checkbox"
                                                :checked="isRolePermChecked(r, perm.code)"
                                                @change="toggleRolePerm(r, perm.code)"
                                                class="rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer"
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
                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-lg shadow-indigo-600/30 transition flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ matrixForm.processing ? 'Menyimpan...' : 'Simpan Matriks Hak Akses Role' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-2xl w-full max-w-md p-6 border border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Tambah User / Petugas Baru</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Nama Lengkap</label>
                        <input v-model="createForm.name" type="text" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="Nama Petugas" />
                        <p v-if="createForm.errors.name" class="text-rose-400 text-xs mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Alamat Email</label>
                        <input v-model="createForm.email" type="email" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="email@event.com" />
                        <p v-if="createForm.errors.email" class="text-rose-400 text-xs mt-1">{{ createForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Password</label>
                        <input v-model="createForm.password" type="password" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="Maksimal 8 karakter" />
                        <p v-if="createForm.errors.password" class="text-rose-400 text-xs mt-1">{{ createForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Hak Akses Role Utama</label>
                        <select v-model="createForm.role" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            <option value="admin">ADMIN (Akses Penuh Seluruh Modul)</option>
                            <option value="loket">LOKET (Operator Penukaran BIB)</option>
                            <option value="undian">UNDIAN (Operator Modul Lottery)</option>
                            <option value="viewer">VIEWER (Read Only / Kiosk Check)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Identitas Counter Loket (Opsional)</label>
                        <input v-model="createForm.counter_number" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="Misal: Loket-01" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" v-model="createForm.is_active" id="create_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500" />
                        <label for="create_active" class="text-xs text-slate-300 font-semibold cursor-pointer">Akun Aktif</label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="createForm.processing" class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan User Baru' }}
                        </button>
                        <button type="button" @click="showCreateModal = false" class="px-4 py-3 bg-slate-800 text-slate-300 font-bold text-xs rounded-xl border border-slate-700">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal (With Custom Permissions Option) -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-2xl w-full max-w-lg p-6 border border-slate-800 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit User & Pengaturan Izin Khusus</h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-white font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Nama Lengkap</label>
                        <input v-model="editForm.name" type="text" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" />
                        <p v-if="editForm.errors.name" class="text-rose-400 text-xs mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Alamat Email</label>
                        <input v-model="editForm.email" type="email" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" />
                        <p v-if="editForm.errors.email" class="text-rose-400 text-xs mt-1">{{ editForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Password Baru (Opsional)</label>
                        <input v-model="editForm.password" type="password" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="Biarkan kosong jika tidak diubah" />
                        <p v-if="editForm.errors.password" class="text-rose-400 text-xs mt-1">{{ editForm.errors.password }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Hak Akses Role</label>
                            <select v-model="editForm.role" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                                <option value="admin">ADMIN</option>
                                <option value="loket">LOKET</option>
                                <option value="undian">UNDIAN</option>
                                <option value="viewer">VIEWER</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Identitas Counter Loket</label>
                            <input v-model="editForm.counter_number" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50" placeholder="Misal: Loket-01" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="editForm.is_active" id="edit_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500" />
                        <label for="edit_active" class="text-xs text-slate-300 font-semibold cursor-pointer">Akun Aktif</label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="editForm.processing" class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30">
                            {{ editForm.processing ? 'Menyimpan...' : 'Update Data User' }}
                        </button>
                        <button type="button" @click="showEditModal = false" class="px-4 py-3 bg-slate-800 text-slate-300 font-bold text-xs rounded-xl border border-slate-700">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
