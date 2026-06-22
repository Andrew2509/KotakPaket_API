<?php

use App\Http\Controllers\Api\Esp32Controller;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PesananController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Pesanan routes (tanpa prefix /api)
Route::get('/pesanan', [PesananController::class, 'index']);
Route::post('/pesanan', [PesananController::class, 'store']);
Route::post('/pesanan/buka-pintu', [PesananController::class, 'bukaPintu']);
Route::post('/pesanan/{id}/buka-laci', [PesananController::class, 'bukaLaci']);
Route::get('/pesanan/{id}', [PesananController::class, 'show']);
Route::put('/pesanan/{id}/status', [PesananController::class, 'updateStatus']);
Route::post('/pesanan/upload-image', [PesananController::class, 'uploadImage']);
Route::delete('/pesanan/{id}', [PesananController::class, 'destroy']);

// Notifikasi routes (tanpa prefix /api)
Route::get('/notifikasi', [NotifikasiController::class, 'index']);
Route::get('/notifikasi/unread', [NotifikasiController::class, 'unread']);
Route::post('/notifikasi', [NotifikasiController::class, 'store']);
Route::put('/notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);
Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markRead']);

// ESP32 routes (tanpa prefix /api)
Route::prefix('esp32')->group(function () {
    Route::get('/perintah', [Esp32Controller::class, 'pollPerintah']);
    Route::put('/perintah/{id}/selesai', [Esp32Controller::class, 'selesaikanPerintah']);
    Route::post('/paket-masuk', [Esp32Controller::class, 'laporPaketMasuk']);
    Route::post('/tangan-terdeteksi', [Esp32Controller::class, 'laporTanganTerdeteksi']);
    Route::get('/kamera/trigger', [Esp32Controller::class, 'checkCameraTrigger']);
});
