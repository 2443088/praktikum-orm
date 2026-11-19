<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisSuratController extends Controller
{
    /** GET /api/v1/jenis-surat */
    public function index()
    {
        return ['data' => JenisSurat::orderBy('nama')->get()];
    }

    /** POST /api/v1/jenis-surat */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:10', 'unique:jenis_surat,kode'],
            'nama' => ['required', 'string', 'max:100'],
        ]);

        return response()->json(['data' => JenisSurat::create($data)], 201);
    }

    /** GET /api/v1/jenis-surat/{jenis_surat} */
    public function show(JenisSurat $jenisSurat)
    {
        return ['data' => $jenisSurat->loadCount('surat')];
    }

    /** PUT/PATCH /api/v1/jenis-surat/{jenis_surat} */
    public function update(Request $request, JenisSurat $jenisSurat)
    {
        $data = $request->validate([
            'kode' => ['sometimes', 'string', 'max:10',
                       Rule::unique('jenis_surat', 'kode')->ignore($jenisSurat->id)],
            'nama' => ['sometimes', 'string', 'max:100'],
        ]);

        $jenisSurat->update($data);

        return ['data' => $jenisSurat];
    }

    /** DELETE /api/v1/jenis-surat/{jenis_surat} */
    public function destroy(JenisSurat $jenisSurat)
    {
        // withTrashed(): surat yang di-soft-delete masih menahan FK (restrictOnDelete) di database.
        if ($jenisSurat->surat()->withTrashed()->exists()) {
            return response()->json([
                'message' => "Jenis surat '{$jenisSurat->nama}' masih dipakai oleh surat dan tidak dapat dihapus.",
            ], 409);
        }

        $jenisSurat->delete();

        return response()->noContent();
    }
}
