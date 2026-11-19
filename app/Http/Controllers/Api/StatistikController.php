<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\Surat;

class StatistikController extends Controller
{
    /** GET /api/v1/statistik */
    public function __invoke()
    {
        return ['data' => [
            'per_status'      => Surat::selectRaw('status, count(*) as jumlah')
                ->groupBy('status')->pluck('jumlah', 'status'),
            'per_jenis_surat' => JenisSurat::withCount('surat')->orderBy('nama')->get(),
        ]];
    }
}
