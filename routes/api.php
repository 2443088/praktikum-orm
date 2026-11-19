<?php

use App\Http\Controllers\Api\JenisSuratController;
use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\Api\SuratController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('jenis-surat', JenisSuratController::class);

    Route::apiResource('mahasiswa', MahasiswaController::class);

    // Route statis WAJIB sebelum surat/{surat}, kalau tidak "trashed" dianggap id.
    Route::get('surat/trashed', [SuratController::class, 'trashed']);
    Route::patch('surat/{id}/restore', [SuratController::class, 'restore'])->whereNumber('id');
    Route::apiResource('surat', SuratController::class)->except('update');
    Route::patch('surat/{surat}/status', [SuratController::class, 'ubahStatus']);

    Route::get('statistik', StatistikController::class);
});
