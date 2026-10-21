<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with([
            'jabatan',
            'unit',
        ]);

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('username', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('nip', 'like', "%{$request->search}%");
            });
        }

        $data = $query->paginate(10);

        return ApiResponse::success($data);
    }

    public function show($id)
    {
        $data = User::with([
            'jabatan',
            'unit',
        ])->findOrFail($id);

        return ApiResponse::success($data);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'username' => 'required',
            'nama' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nip' => 'nullable',
            'jenis_kelamin' => 'required|in:Pria,Wanita',
            'jabatan_id' => 'required|exists:jabatans,id',
            'unit_id' => 'required|exists:unit_types,id',
            'role' => 'required|in:admin,user',
        ]);

        $validated['password'] = Hash::make(
            $validated['password']
        );

        $data = User::create($validated);

        return ApiResponse::success(
            $data->load(['jabatan', 'unit']),
            'user berhasil dibuat'
        );
    }

    public function update(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes',
            'nama' => 'sometimes|string',
            'email' => "sometimes|email|unique:users,email,$id",
            'nip' => 'sometimes',
            'jenis_kelamin' => 'sometimes|in:Pria,Wanita',
            'jabatan_id' => 'sometimes|exists:jabatans,id',
            'unit_id' => 'sometimes|exists:unit_types,id',
            'role' => 'sometimes|in:admin,user',
            'password' => 'sometimes|min:6',
        ]);

        if (isset($validated['password'])) {

            $validated['password'] = bcrypt(
                $validated['password']
            );
        }

        $user->update($validated);

        return ApiResponse::success(
            $user->load(['jabatan', 'unit']),
            'user berhasil diupdate'
        );
    }

    public function destroy($id)
    {

        User::destroy($id);

        return ApiResponse::success(
            null,
            'user berhasil dihapus'
        );
    }

    public function resetPassword(Request $request, $id)
    {

        $request->validate([
            'password' => 'required|min:6',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make(
                $request->password
            ),
        ]);

        return ApiResponse::success(null, 'password berhasil direset');
    }

    public function updateProfile(Request $request)
    {

        $user = auth()->user();

        $validated = $request->validate([
            'username' => 'sometimes',
            'foto' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Foto profil harus berformat JPG, JPEG, atau PNG.',
            'foto.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        if ($request->hasFile('foto')) {

            if ($user->foto) {

                Storage::disk('public')->delete(
                    $user->foto
                );
            }

            $path = $request->file('foto')
                ->store('profile', 'public');

            $user->foto = $path;
        }

        if (isset($validated['username'])) {

            $user->username = $validated['username'];
        }

        $user->save();

        return ApiResponse::success(
            $user,
            'profile berhasil diupdate'
        );
    }

    public function syncFromApi()
    {
        set_time_limit(0);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('users')->truncate();
        DB::table('unit_types')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $response = Http::get('https://api.unikom.ac.id/v1/structural');

        if (! $response->successful()) {
            return ApiResponse::error('Gagal ambil data API');
        }

        $result = $response->json();

        $groups = $result['data'] ?? [];

        $hashedPassword = bcrypt('default123');

        $unitCache = [];
        $jabatanCache = [];

        $usersToInsert = [];

        foreach ($groups as $groupName => $items) {

            
            $rootName = strtoupper($groupName);

            if (! isset($unitCache[$rootName])) {

                $unitCache[$rootName] = UnitType::create([
                    'nama' => $rootName,
                    'parent_id' => null,
                ])->id;
            }

            $rootId = $unitCache[$rootName];

            foreach ($items as $item) {

                $nama = $item['nama'] ?? null;
                $nip = $item['nip'] ?? null;
                $email = $item['email'] ?? null;

                
                $namaJabatan = $item['nama_jabatan'] ?? '-';

                if (! $nama || ! $nip) {
                    continue;
                }

                $username = trim(
                    ($item['gelar_depan'] ?? '').' '.
                    $nama.' '.
                    ($item['gelar_belakang'] ?? '')
                );

                
                $unitName =
                    $item['unit']
                    ?? $item['program_studi']
                    ?? $item['fakultas']
                    ?? 'LAINNYA';

                
                $secondKey = $rootId.'_'.$unitName;

                if (! isset($unitCache[$secondKey])) {

                    $unitCache[$secondKey] = UnitType::create([
                        'nama' => $unitName,
                        'parent_id' => $rootId,
                    ])->id;
                }

                $unitId = $unitCache[$secondKey];

                
                if (! isset($jabatanCache[$namaJabatan])) {

                    $jabatanCache[$namaJabatan] = Jabatan::create([
                        'nama' => $namaJabatan,
                    ])->id;
                }

                
                $usersToInsert[] = [
                    'username' => substr($username, 0, 255),
                    'nip' => $nip,

                    
                    'email' => $email
                        ?: strtolower(str_replace(' ', '', $nip.'_'.md5($namaJabatan))).'@null.unikom',

                    'jenis_kelamin' => 'Pria',

                    
                    'jabatan_id' => $jabatanCache[$namaJabatan],

                    
                    'unit_id' => $unitId,

                    'role' => 'user',
                    'password' => $hashedPassword,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        
        foreach (array_chunk($usersToInsert, 500) as $chunk) {

            DB::table('users')->insert($chunk);
        }

        \App\Support\SafeCache::forget('dropdown_units');

        return ApiResponse::success([
            'total' => count($usersToInsert),
        ], 'Sync berhasil');
    }

    public function dropdown()
    {
        // formatTree sudah menghasilkan array biasa, jadi itulah yang disimpan;
        // model UnitType tidak bisa dibaca ulang dari cache.
        $units = \App\Support\SafeCache::remember('dropdown_units', 3600, function () {
            return $this->formatTree(
                UnitType::with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->get()
            );
        });

        return ApiResponse::success($units);
    }

    private function formatTree($units)
    {
        $result = [];

        foreach ($units as $unit) {

            $item = [
                'name' => $unit->nama,
                'unit_id' => $unit->id,
            ];

            if ($unit->childrenRecursive->count()) {

                $item['children'] = $this->formatTree(
                    $unit->childrenRecursive
                );
            }

            $result[] = $item;
        }

        return $result;
    }
}
