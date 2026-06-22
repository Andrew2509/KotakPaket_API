<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PesananController extends Controller
{
    // GET /api/pesanan - ambil semua pesanan
    public function index()
    {
        $pesanan = Pesanan::latest()->get();

        return response()->json($pesanan);
    }

    // POST /api/pesanan - tambah pesanan baru (dari frontend admin)
    public function store(Request $request)
    {
        $request->validate([
            'nomor_resi' => 'required|string|unique:pesanans,nomor_resi',
            'harga_cod'  => 'required|integer|min:0',
            'kotak'      => 'required|in:A,B,C',
        ]);

        $pesanan = Pesanan::create([
            'nomor_resi' => $request->nomor_resi,
            'harga_cod'  => $request->harga_cod,
            'kotak'      => $request->kotak,
            'status'     => 'menunggu',
        ]);

        return response()->json($pesanan, 201);
    }

    // GET /api/pesanan/{id} - detail pesanan
    public function show(string $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        return response()->json($pesanan);
    }

    // PUT /api/pesanan/{id}/status - update status (diambil)
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:menunggu,diambil',
        ]);

        $pesanan = Pesanan::findOrFail($id);
        $pesanan->status = $request->status;

        if ($request->status === 'diambil') {
            $pesanan->diambil_at = now();
        }

        $pesanan->save();

        return response()->json($pesanan);
    }

    // POST /api/pesanan/buka-pintu - kurir menginput resi di halaman utama
    public function bukaPintu(Request $request)
    {
        $request->validate([
            'nomor_resi' => 'required|string|exists:pesanans,nomor_resi',
        ]);

        $pesanan = Pesanan::where('nomor_resi', $request->nomor_resi)->firstOrFail();

        if ($pesanan->status !== 'menunggu') {
            return response()->json(['message' => 'Resi sudah tidak aktif.'], 400);
        }

        // Create perintah for esp32 to open door
        \App\Models\Perintah::create([
            'tipe' => 'buka_pintu',
            'pesanan_id' => $pesanan->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Pintu berhasil dibuka.']);
    }

    // POST /api/pesanan/{id}/buka-laci - kurir/user menekan tombol Ambil Uang
    public function bukaLaci(string $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        if ($pesanan->status !== 'menunggu') {
            return response()->json(['message' => 'Pesanan sudah diambil.'], 400);
        }

        // Create perintah for esp32 to open slot/drawer
        \App\Models\Perintah::create([
            'tipe' => 'buka_slot',
            'kotak' => $pesanan->kotak,
            'pesanan_id' => $pesanan->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Laci berhasil dibuka.']);
    }

    // POST /api/pesanan/upload-image - terima foto dari ESP32-CAM
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        // Cari pesanan terbaru yang berstatus diambil dan fotonya masih kosong
        $pesanan = Pesanan::where('status', 'diambil')
            ->whereNull('image')
            ->latest('diambil_at')
            ->first();

        // Jika tidak ada, fallback ke pesanan terbaru apa saja
        if (!$pesanan) {
            $pesanan = Pesanan::latest('updated_at')->first();
        }

        if (!$pesanan) {
            return response()->json(['message' => 'Tidak ada pesanan aktif ditemukan.'], 404);
        }

        $path = $request->file('image')->store('paket', 'public');
        $pesanan->image = $path;
        $pesanan->save();

        return response()->json([
            'message'    => 'Gambar berhasil diupload',
            'image'      => $path,
            'nomor_resi' => $pesanan->nomor_resi,
        ]);
    }

    // DELETE /api/pesanan/{id} - hapus pesanan
    public function destroy(string $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        if ($pesanan->image) {
            Storage::disk('public')->delete($pesanan->image);
        }

        $pesanan->delete();

        return response()->json(['message' => 'Pesanan berhasil dihapus']);
    }
}
