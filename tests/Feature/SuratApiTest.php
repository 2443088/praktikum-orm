<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_alur_surat_dan_aturan_hapus(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $jenis = JenisSurat::create(['kode' => 'SKAK', 'nama' => 'Surat Keterangan Aktif Kuliah']);

        $id = $this->postJson('/api/v1/surat', [
            'mahasiswa_id' => $mhs->id, 'jenis_surat_id' => $jenis->id,
            'keperluan' => 'Persyaratan beasiswa daerah.',
        ])->assertCreated()->assertJsonCount(1, 'data.riwayat')->json('data.id');

        $this->patchJson("/api/v1/surat/{$id}/status", ['status' => 'diproses'])->assertOk();
        $this->patchJson("/api/v1/surat/{$id}/status", ['status' => 'diajukan'])->assertStatus(422);

        $this->deleteJson("/api/v1/jenis-surat/{$jenis->id}")->assertStatus(409);

        $this->deleteJson("/api/v1/surat/{$id}")->assertNoContent();
        $this->getJson('/api/v1/surat/trashed')->assertOk()->assertJsonPath('total', 1);
        $this->patchJson("/api/v1/surat/{$id}/restore")->assertOk();
        $this->getJson('/api/v1/statistik')->assertOk()->assertJsonPath('data.per_status.diproses', 1);
    }
}
