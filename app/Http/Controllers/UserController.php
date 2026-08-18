<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Daftar definisi modul & hak akses di sistem
     */
    protected array $availablePermissions = [
        [
            'code' => 'access-dashboard',
            'name' => 'Dashboard & Statistik',
            'description' => 'Melihat statistik realtime, chart penukaran, & batch import',
        ],
        [
            'code' => 'access-loket',
            'name' => 'Loket POS (Scan & Assign BIB)',
            'description' => 'Melakukan pencarian PIN, penukaran racepack, & assign nomor BIB',
        ],
        [
            'code' => 'access-bib-check',
            'name' => 'Kiosk BIB Check',
            'description' => 'Akses layar Kiosk pencarian nomor BIB peserta',
        ],
        [
            'code' => 'access-import',
            'name' => 'Import Data Peserta (CSV/Excel)',
            'description' => 'Mengunggah file CSV data peserta baru & mendownload template',
        ],
        [
            'code' => 'access-users',
            'name' => 'Manajemen User & Role Matrix',
            'description' => 'Mengatur akun pengguna, role, dan matriks hak akses modul',
        ],
        [
            'code' => 'access-reset-claim',
            'name' => 'Otorisasi Reset Sengketa PIN',
            'description' => 'Wewenang menyetujui reset klaim PIN sengketa di loket',
        ],
    ];

    public function index(Request $request)
    {
        $search = $request->query('search');
        $role = $request->query('role');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('counter_number', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Ambil matriks role permissions saat ini
        $rolePermissionsMap = RolePermission::all()->pluck('permissions', 'role')->toArray();

        return inertia('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'roles' => ['admin', 'loket', 'undian', 'viewer'],
            'availablePermissions' => $this->availablePermissions,
            'rolePermissionsMap' => $rolePermissionsMap,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(['admin', 'loket', 'undian', 'viewer'])],
            'counter_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // Normalisasi role agar selalu tersimpan lowercase
            'role' => Str::lower($validated['role']),
            'counter_number' => $validated['counter_number'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', 'User/Petugas baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(['admin', 'loket', 'undian', 'viewer'])],
            'counter_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'custom_permissions' => ['nullable', 'array'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Normalisasi role agar konsisten
            'role' => Str::lower($validated['role']),
            'counter_number' => $validated['counter_number'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'custom_permissions' => $request->has('custom_permissions') ? $validated['custom_permissions'] : $user->custom_permissions,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()->back()->with('success', "Data user & hak akses {$user->name} berhasil diperbarui.");
    }

    /**
     * Memperbarui Matriks Hak Akses Per Role
     */
    public function updateRolePermissions(Request $request)
    {
        $validated = $request->validate([
            'role_permissions' => ['required', 'array'],
            'role_permissions.*' => ['array'],
        ]);

        foreach ($validated['role_permissions'] as $role => $permissions) {
            RolePermission::updateOrCreate(
                ['role' => $role],
                ['permissions' => array_values($permissions)]
            );
        }

        return redirect()->back()->with('success', 'Matriks Hak Akses Role berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
