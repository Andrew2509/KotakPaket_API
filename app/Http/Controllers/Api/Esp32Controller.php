<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perintah;
use App\Models\Pesanan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;

class Esp32Controller extends Controller
{
    // GET /api/esp32/perintah
    public function pollPerintah()
    {
        $perintah = Perintah::where('status', 'pending')->oldest()->first();

        if (!$perintah) {
            return response()->noContent(); // 204 No Content
        }

        return response()->json([
            'id'         => $perintah->id,
            'tipe'       => $perintah->tipe,
            'kotak'      => $perintah->kotak,
            'pesanan_id' => $perintah->pesanan_id,
        ]);
    }

    // PUT /api/esp32/perintah/{id}/selesai
    public function selesaikanPerintah(string $id)
    {
        $perintah = Perintah::findOrFail($id);
        $perintah->status = 'selesai';
        $perintah->save();

        return response()->json(['message' => 'Perintah ditandai selesai']);
    }

    // POST /api/esp32/paket-masuk
    public function laporPaketMasuk(Request $request)
    {
        $request->validate([
            'pesanan_id' => 'required|integer|exists:pesanans,id',
        ]);

        $pesanan = Pesanan::findOrFail($request->pesanan_id);

        // Tambah notifikasi
        Notifikasi::create([
            'kotak' => $pesanan->kotak,
            'pesan' => "Paket untuk Resi {$pesanan->nomor_resi} telah dimasukkan ke Kotak {$pesanan->kotak}",
        ]);

        return response()->json(['message' => 'Laporan paket masuk berhasil disimpan']);
    }

    // POST /api/esp32/tangan-terdeteksi
    public function laporTanganTerdeteksi(Request $request)
    {
        $request->validate([
            'pesanan_id' => 'required|integer|exists:pesanans,id',
            'kotak'      => 'required|string|in:A,B,C',
        ]);

        $pesanan = Pesanan::findOrFail($request->pesanan_id);
        $pesanan->status = 'diambil';
        $pesanan->diambil_at = now();
        $pesanan->save();

        // Tambah notifikasi
        Notifikasi::create([
            'kotak' => $request->kotak,
            'pesan' => "Uang COD untuk Resi {$pesanan->nomor_resi} telah diambil dari Laci {$request->kotak}",
        ]);

        return response()->json(['message' => 'Laporan tangan terdeteksi berhasil disimpan']);
    }

    // GET /api/esp32/kamera/trigger
    public function checkCameraTrigger()
    {
        // Cari pesanan yang statusnya 'diambil', image-nya masih null, dan diambil dalam 3 menit terakhir
        $pesanan = Pesanan::where('status', 'diambil')
            ->whereNull('image')
            ->where('diambil_at', '>=', now()->subMinutes(3))
            ->latest('diambil_at')
            ->first();

        if ($pesanan) {
            return response()->json([
                'trigger'    => true,
                'pesanan_id' => $pesanan->id,
                'nomor_resi' => $pesanan->nomor_resi,
            ]);
        }

        return response()->json([
            'trigger' => false,
        ]);
    }
}
