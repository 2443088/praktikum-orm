<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusSurat;
use App\Http\Controllers\Controller;
use App\Models\Surat;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratController extends Controller
{
    /** GET /api/v1/surat?status=diajukan */
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::enum(StatusSurat::class)]]);

        return Surat::query()
            ->with(['mahasiswa:id,nim,nama', 'jenisSurat:id,nama'])  // eager loading, hanya kolom perlu
            ->when($request->status, fn ($q, $s) => $q->status(StatusSurat::from($s)))
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    /** POST /api/v1/surat */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mahasiswa_id'   => ['required', 'integer', 'exists:mahasiswa,id'],
            'jenis_surat_id' => ['required', 'integer', 'exists:jenis_surat,id'],
            'keperluan'      => ['required', 'string', 'min:10'],
            'disetujui_oleh' => ['nullable', 'integer', 'exists:dosen,id'],
        ]);

        $surat = Surat::ajukan($data);                            // transaksi: surat + riwayat

        return response()->json(
            ['data' => $surat->load('mahasiswa', 'jenisSurat', 'disetujuiOleh', 'riwayat')], 201
        );
    }

    /** GET /api/v1/surat/{surat} */
    public function show(Surat $surat)
    {
        return ['data' => $surat->load('mahasiswa', 'jenisSurat', 'disetujuiOleh', 'riwayat')];
    }

    /** PATCH /api/v1/surat/{surat}/status */
    public function ubahStatus(Request $request, Surat $surat)
    {
        $data = $request->validate([
            'status'         => ['required', Rule::enum(StatusSurat::class)],
            'catatan'        => ['nullable', 'string', 'max:255'],
            'disetujui_oleh' => ['nullable', 'integer', 'exists:dosen,id'],
        ]);

        try {
            $surat->ubahStatus(
                StatusSurat::from($data['status']),
                $data['catatan'] ?? null,
                $data['disetujui_oleh'] ?? null,
            );
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]); // 422
        }

        return ['data' => $surat->load('disetujuiOleh', 'riwayat')];
    }

    /** DELETE /api/v1/surat/{surat}  (soft delete) */
    public function destroy(Surat $surat)
    {
        $surat->delete();                                         // isi deleted_at, baris tetap ada

        return response()->noContent();
    }

    /** GET /api/v1/surat/trashed */
    public function trashed(Request $request)
    {
        return Surat::onlyTrashed()
            ->with(['mahasiswa:id,nim,nama', 'jenisSurat:id,nama'])
            ->latest('deleted_at')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    /** PATCH /api/v1/surat/{id}/restore */
    public function restore(int $id)
    {
        $surat = Surat::onlyTrashed()->findOrFail($id);           // 404 jika tidak ada di tempat sampah
        $surat->restore();

        return ['data' => $surat->load('mahasiswa', 'jenisSurat', 'disetujuiOleh', 'riwayat')];
    }
}
