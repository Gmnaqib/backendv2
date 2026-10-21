<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPengajuan\StoreDaftarPengajuanRequest;
use App\Http\Requests\DaftarPengajuan\StoreFullDaftarPengajuanRequest;
use App\Http\Requests\DaftarPengajuan\UpdateDaftarPengajuanRequest;
use App\Http\Resources\DaftarPengajuanResource;
use App\Models\AktivasiPengajuan;
use App\Models\DaftarPengajuan;
use App\Support\CacheGuard;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarPengajuanController extends Controller
{
    private const RELASI = [
        'user.jabatan',
        'user.unit',
        'aktivasi.pengajuan',
        'barang.barang.vendor',
        'barangLainnya',
    ];


    public function storeFull(StoreFullDaftarPengajuanRequest $request)
    {
        $data = $request->validated();

        $user = auth()->user()->load('jabatan');

        $jabatan = strtolower($user->jabatan->nama ?? '');

        $jabatan = trim(preg_replace('/\s+/', ' ', str_replace('.', ' ', $jabatan)));

        $allowedTipe = ['tahunan'];

        $kaprodiAlias = [
            'kaprodi',
            'ka prodi',
            'ketua prodi',
            'ketua program studi',
        ];

        $isKaprodi = false;

        foreach ($kaprodiAlias as $alias) {

            if (str_contains($jabatan, $alias)) {

                $isKaprodi = true;

                break;
            }
        }

        if (
            str_contains($jabatan, 'dekan') ||
            $isKaprodi
        ) {
            $allowedTipe = [
                'tahunan',
                'kelas',
                'ujian',
            ];
        }

        if (! in_array($data['tipe'], $allowedTipe)) {

            return ApiResponse::error(
                'Anda tidak memiliki akses untuk tipe pengajuan ini',
                403
            );
        }

        $semester = $data['semester'] ?? null;

        $ujian = $data['ujian'] ?? null;

        if ($ujian !== null && strtolower($ujian) === 'default') {
            $ujian = 'Default';
        }

        $query = AktivasiPengajuan::with('pengajuan')
            ->whereHas('pengajuan', function ($q) use ($data, $semester, $ujian) {

                $q->where('tipe', $data['tipe']);

                if ($semester) {
                    $q->where('semester', $semester);
                }

                if ($ujian) {
                    $q->where('ujian', $ujian);
                }
            })
            ->whereDate('aktif_mulai', '<=', now())
            ->whereDate('aktif_selesai', '>=', now());

        if (! empty($data['id_aktivasi'])) {
            $query->where('id', $data['id_aktivasi']);
        }

        $kandidat = $query->orderByDesc('id')->get();

        if ($kandidat->isEmpty()) {

            return ApiResponse::error(
                'Tidak ada periode pengajuan aktif',
                422
            );
        }

        if ($kandidat->count() > 1) {

            return ApiResponse::error(
                'Terdapat lebih dari satu periode aktif untuk tipe ini: '
                . $kandidat->map(fn ($a) => $a->pengajuan?->semester)->filter()->unique()->implode(', ')
                . '. Pilih semester yang dimaksud.',
                422
            );
        }

        $aktivasi = $kandidat->first();

        $sudahMengajukan = DaftarPengajuan::where('user_id', auth()->id())
            ->where('id_aktivasi', $aktivasi->id)
            ->exists();

        if ($sudahMengajukan) {

            return ApiResponse::error(
                'Anda sudah melakukan pengajuan pada periode ini',
                422
            );
        }

        DB::beginTransaction();

        try {
            $pengajuan = DaftarPengajuan::create([
                'id_aktivasi' => $aktivasi->id,
                'user_id' => auth()->id(),
                'date' => now(),
                'surat_pengajuan' => $request->hasFile('surat_pengajuan')
                    ? $request->file('surat_pengajuan')
                        ->store('surat_pengajuan', 'public')
                    : '',
            ]);

            foreach ($data['barang'] ?? [] as $item) {

                if ($item['jumlah'] > 0) {

                    $pengajuan->barang()->create([
                        'id_barang' => $item['id_barang'],
                        'jumlah' => $item['jumlah'],
                        'jumlah_disetujui' => 0,
                        'status' => false,
                    ]);
                }
            }

            $vendorPerlengkapan = \App\Models\Vendor::firstOrCreate(
                ['nama' => 'Perlengkapan'],
                ['kontak' => '-']
            );

            foreach ($data['barang_lainnya'] ?? [] as $index => $item) {

                $buktiFotoPath = null;
                
                if (isset($item['bukti_foto']) && $item['bukti_foto'] instanceof \Illuminate\Http\UploadedFile) {
                    $buktiFotoPath = $item['bukti_foto']->store('bukti_foto_pengajuan', 'public');
                } elseif ($request->hasFile("barang_lainnya.{$index}.bukti_foto")) {
                    $buktiFotoPath = $request->file("barang_lainnya.{$index}.bukti_foto")->store('bukti_foto_pengajuan', 'public');
                } elseif ($request->hasFile("bukti_foto_{$index}")) {
                    $buktiFotoPath = $request->file("bukti_foto_{$index}")->store('bukti_foto_pengajuan', 'public');
                }

                $pengajuan->barangLainnya()->create([
                    'nama' => $item['nama'],
                    'jumlah' => $item['jumlah'],
                    'kategori' => $item['kategori'],
                    'satuan' => $item['satuan'],
                    'alasan' => $item['alasan'] ?? $item['catatan'] ?? $item['note'] ?? null,
                    'bukti_foto' => $buktiFotoPath,
                    'jumlah_disetujui' => 0,
                    'status' => false,
                    'vendor_id' => $vendorPerlengkapan->id,
                ]);
            }

            DB::commit();

            PengajuanCache::flush();

            return ApiResponse::success(
                new DaftarPengajuanResource(
                    $pengajuan->load(
                        'barang.barang.vendor',
                        'barangLainnya'
                    )
                ),
                'Pengajuan berhasil dibuat'
            );

        } catch (\Throwable $e) {

            DB::rollBack();

            if (isset($pengajuan) && $pengajuan->exists) {
                try {
                    $pengajuan->delete();
                } catch (\Throwable $deleteEx) {

                }
            }

            try {
                report($e);
            } catch (\Throwable $reportEx) {

            }

            return ApiResponse::error(
                'Pengajuan gagal disimpan. Silakan coba lagi atau hubungi administrator.',
                500
            );
        }
    }

    public function index()
    {
        $data = PengajuanCache::remember('daftar-index', [], function () {

            return CacheGuard::polos(DaftarPengajuanResource::collection(
                DaftarPengajuan::with(self::RELASI)->latest()->get()
            ));

        });

        return ApiResponse::success($data);
    }

    public function store(StoreDaftarPengajuanRequest $request)
    {
        $data = $request->validated();

        $aktivasi = AktivasiPengajuan::where('id', $data['aktivasi_pengajuan_id'])
            ->whereDate('aktif_mulai', '<=', now())
            ->whereDate('aktif_selesai', '>=', now())
            ->first();

        if (! $aktivasi) {
            return ApiResponse::error(
                'Periode pengajuan tidak aktif',
                422
            );
        }

        $data['user_id'] = auth()->id();

        $data['id_aktivasi'] = $data['aktivasi_pengajuan_id'];

        unset($data['aktivasi_pengajuan_id']);

        if ($request->hasFile('surat_pengajuan')) {

            $data['surat_pengajuan'] =
                $request->file('surat_pengajuan')
                    ->store('surat_pengajuan', 'public');
        }

        $pengajuan = DaftarPengajuan::create($data);

        PengajuanCache::flush();

        $pengajuan->load([
            'user',
            'aktivasi',
            'barang',
            'barangLainnya',
        ]);

        return ApiResponse::success(
            new DaftarPengajuanResource($pengajuan),
            'Pengajuan berhasil dibuat'
        );
    }

    private function boleh(DaftarPengajuan $daftarPengajuan): bool
    {
        return auth()->user()->role === 'admin'
            || $daftarPengajuan->user_id === auth()->id();
    }

    public function show(DaftarPengajuan $daftarPengajuan)
    {
        if (! $this->boleh($daftarPengajuan)) {

            return ApiResponse::error(
                'Anda tidak memiliki akses untuk melihat pengajuan ini.',
                403
            );
        }

        return ApiResponse::success(

            new DaftarPengajuanResource(

                $daftarPengajuan->load(self::RELASI)

            )

        );
    }

    public function update(
        UpdateDaftarPengajuanRequest $request,
        DaftarPengajuan $daftarPengajuan
    ) {
        if (! $this->boleh($daftarPengajuan)) {

            return ApiResponse::error(
                'Anda tidak memiliki akses untuk mengubah pengajuan ini.',
                403
            );
        }

        $daftarPengajuan->update(

            $request->validated()

        );

        PengajuanCache::flush();

        return ApiResponse::success(

            new DaftarPengajuanResource($daftarPengajuan)

        );
    }

    public function destroy(DaftarPengajuan $daftarPengajuan)
    {
        if (! $this->boleh($daftarPengajuan)) {

            return ApiResponse::error(
                'Anda tidak memiliki akses untuk menghapus pengajuan ini.',
                403
            );
        }

        $daftarPengajuan->delete();

        PengajuanCache::flush();

        return ApiResponse::deleted();
    }

    public function adminIndex(Request $request)
    {
        $aktivasi = $request->id_aktivasi;
        $jabatan = $request->jabatan_id;
        $bagian = $request->bagian_id;
        $status = $request->status;
        $tipe = $request->tipe;

        $data = PengajuanCache::remember('daftar-admin', [
            'aktivasi' => $aktivasi,
            'jabatan'  => $jabatan,
            'bagian'   => $bagian,
            'status'   => $status,
            'tipe'     => $tipe,
        ], function () use ($aktivasi, $jabatan, $bagian, $status, $tipe) {

        $query = DaftarPengajuan::with(self::RELASI);

        if ($aktivasi) {

            $query->where('id_aktivasi', $aktivasi);
        }

        if ($jabatan) {

            $query->whereHas('user.jabatan', function ($q) use ($jabatan) {

                $q->where('id', $jabatan);

            });
        }

        if ($bagian) {

            $query->whereHas('user.unit', function ($q) use ($bagian) {

                $q->where('id', $bagian);

            });
        }

        if ($tipe) {

            if (in_array($tipe, ['rutin', 'nonrutin'])) {
                $query->whereHas('aktivasi', function ($q) use ($tipe) {
                    $q->where('tipe', $tipe);
                });
            } else {
                $query->whereHas('aktivasi.pengajuan', function ($q) use ($tipe) {
                    $q->where('tipe', $tipe);
                });
            }
        }

        if ($status !== null) {

            $query->where(function ($q) use ($status) {

                $q->whereHas('barang', function ($sub) use ($status) {

                    $sub->where('status', $status);

                })->orWhereHas('barangLainnya', function ($sub) use ($status) {

                    $sub->where('status', $status);

                });

            });
        }

            return CacheGuard::polos(
                DaftarPengajuanResource::collection($query->latest()->get())
            );

        });

        return ApiResponse::success($data, 'List pengajuan admin');
    }

    public function adminAktivasi(Request $request)
    {
        $jabatan = $request->jabatan_id;
        $bagian = $request->bagian_id;
        $tipe = $request->tipe;

        $aktivasiList = PengajuanCache::remember('daftar-admin-aktivasi', [
            'jabatan' => $jabatan,
            'bagian'  => $bagian,
            'tipe'    => $tipe,
        ], function () use ($jabatan, $bagian, $tipe) {

        $query = DaftarPengajuan::query();

        if ($jabatan) {
            $query->whereHas('user.jabatan', function ($q) use ($jabatan) {
                $q->where('id', $jabatan);
            });
        }

        if ($bagian) {
            $query->whereHas('user.unit', function ($q) use ($bagian) {
                $q->where('id', $bagian);
            });
        }

        if ($tipe) {
            if (in_array($tipe, ['rutin', 'nonrutin'])) {
                $query->whereHas('aktivasi', function ($q) use ($tipe) {
                    $q->where('tipe', $tipe);
                });
            } else {
                $query->whereHas('aktivasi.pengajuan', function ($q) use ($tipe) {
                    $q->where('tipe', $tipe);
                });
            }
        }

        $aktivasiIds = $query->distinct()->pluck('id_aktivasi')->filter();

            return CacheGuard::polos(
                \App\Http\Resources\AktivasiPengajuanResource::collection(
                    AktivasiPengajuan::with('pengajuan')
                        ->whereIn('id', $aktivasiIds)
                        ->latest()
                        ->get()
                )
            );

        });

        // Bentuk respons sama dengan saat resource dikembalikan langsung:
        // Laravel membungkusnya dalam "data".
        return response()->json(['data' => $aktivasiList]);
    }
}
