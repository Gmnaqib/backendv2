<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AktivasiPengajuan\StoreAktivasiPengajuanRequest;
use App\Http\Requests\AktivasiPengajuan\UpdateAktivasiPengajuanRequest;
use App\Http\Resources\AktivasiPengajuanResource;
use App\Http\Resources\BarangPengajuanLainnyaResource;
use App\Models\AktivasiPengajuan;
use App\Models\DaftarPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use App\Support\PengajuanCache;
use Illuminate\Http\Request;


//sudah pake Cache
class AktivasiController extends Controller
{
    public function index(Request $request)
    {

        $query = AktivasiPengajuan::with('pengajuan');

        if ($request->tipe) {

            $query->whereHas('pengajuan', function ($q) use ($request) {

                $q->where('tipe', $request->tipe);
            });
        }

        return AktivasiPengajuanResource::collection(

            $query->latest()->get()
        );
    }

    private function selaraskanPengajuan(array $data, $idPengajuanAsal = null): array
    {
        $semester = $data['semester'] ?? null;

        $ujian = $data['ujian'] ?? null;

        unset($data['semester'], $data['ujian']);

        if ($semester === null && $ujian === null) {
            return ['data' => $data];
        }

        $idPengajuan = $data['id_pengajuan'] ?? $idPengajuanAsal;

        $asal = $idPengajuan ? Pengajuan::find($idPengajuan) : null;

        if (! $asal) {
            return ['data' => $data];
        }

        if ($ujian !== null && strtolower($ujian) === 'default') {
            $ujian = 'Default';
        }

        $semesterTarget = $semester ?? $asal->semester;

        $ujianTarget = $ujian ?? $asal->ujian;

        $target = Pengajuan::where('tipe', $asal->tipe)
            ->where('semester', $semesterTarget)
            ->where('ujian', $ujianTarget)
            ->first();

        if (! $target) {

            $keterangan = $asal->tipe . ' semester ' . $semesterTarget;

            if ($ujianTarget && strtolower($ujianTarget) !== 'default') {
                $keterangan .= ' ' . strtoupper($ujianTarget);
            }

            return ['error' => 'Tidak ada jenis pengajuan ' . $keterangan . '.'];
        }

        $data['id_pengajuan'] = $target->id;

        return ['data' => $data];
    }

    public function store(StoreAktivasiPengajuanRequest $request)
    {
        $hasil = $this->selaraskanPengajuan($request->validated());

        if (isset($hasil['error'])) {
            return ApiResponse::error($hasil['error'], 422);
        }

        $aktivasi = AktivasiPengajuan::create(

            $hasil['data']
        );

        PengajuanCache::flush();

        return new AktivasiPengajuanResource(

            $aktivasi->load('pengajuan')
        );
    }

    public function show(AktivasiPengajuan $aktivasiPengajuan)
    {

        return new AktivasiPengajuanResource(

            $aktivasiPengajuan->load('pengajuan')
        );
    }

    public function update(
        UpdateAktivasiPengajuanRequest $request,
        AktivasiPengajuan $aktivasiPengajuan
    ) {

        $hasil = $this->selaraskanPengajuan(
            $request->validated(),
            $aktivasiPengajuan->id_pengajuan
        );

        if (isset($hasil['error'])) {
            return ApiResponse::error($hasil['error'], 422);
        }

        $aktivasiPengajuan->update(

            $hasil['data']
        );

        PengajuanCache::flush();

        return new AktivasiPengajuanResource(

            $aktivasiPengajuan->load('pengajuan')
        );
    }

    public function destroy(AktivasiPengajuan $aktivasiPengajuan)
    {

        $aktivasiPengajuan->delete();

        PengajuanCache::flush();

        return response()->json([

            'message' => 'Aktivasi berhasil dihapus',
        ]);
    }

    public function current(Request $request)
    {

        $query = AktivasiPengajuan::with('pengajuan')

            ->whereDate('aktif_mulai', '<=', now())
            ->whereDate('aktif_selesai', '>=', now());

        if ($request->tipe) {

            $query->whereHas('pengajuan', function ($q) use ($request) {

                $q->where('tipe', $request->tipe);
            });
        }

        return AktivasiPengajuanResource::collection(

            $query->get()
        );
    }

    public function activate(AktivasiPengajuan $aktivasiPengajuan)
    {

        return response()->json([

            'message' => 'Periode aktivasi siap digunakan',

            'data' => new AktivasiPengajuanResource(

                $aktivasiPengajuan->load('pengajuan')
            ),
        ]);
    }

    public function summary($id)
    {
        $aktivasi = AktivasiPengajuan::with('pengajuan')
            ->findOrFail($id);

        $totalPengaju = User::count();

        $sudahPengajuan = DaftarPengajuan::where(
            'id_aktivasi',
            $id
        )
            ->distinct('user_id')
            ->count();

        $units = User::with([
            'unit',
            'jabatan',
        ])
            ->get()
            ->map(function ($user) use ($id) {

                $pengajuan = DaftarPengajuan::with([
                    'barang.barang.vendor',
                    'barangLainnya',
                ])
                    ->where('id_aktivasi', $id)
                    ->where('user_id', $user->id)
                    ->first();

                return [

                    
                    'nama' => $user->nama,

                    'unit' => $user->unit?->nama ?? '-',

                    'jabatan' => $user->jabatan?->nama ?? '-',

                    'status' => $pengajuan
                        ? 'Sudah Pengajuan'
                        : 'Belum Pengajuan',

                    'barang' => $pengajuan
                        ? $pengajuan->barang->map(function ($item) {

                            return [

                                
                                'nama_barang' => $item->barang?->nama ?? '-',

                                
                                'qty' => $item->jumlah ?? 0,

                                'jumlah_disetujui' => $item->jumlah_disetujui ?? 0,

                                'status' => $item->status ?? '-',

                                'vendor' => $item->barang?->vendor?->nama ?? '-',
                            ];
                        })
                        : [],

                    'barang_lainnya' => $pengajuan
                        ? BarangPengajuanLainnyaResource::collection(
                            $pengajuan->barangLainnya
                        )
                        : [],
                ];
            });

        return ApiResponse::success([
            'aktivasi' => $aktivasi,

            'statistik' => [
                'jumlah_pengajuan_masuk' => $sudahPengajuan,
                'total_pengaju' => $totalPengaju,
            ],

            'detail_pengajuan' => $units,
        ]);
    }
}
