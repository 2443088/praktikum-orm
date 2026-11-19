<div align="center">

# Praktikum Pertemuan 9: Database Integration & ORM

**Implementasi Integrasi Database & Eloquent ORM pada Sistem Pengajuan Surat Mahasiswa (`/api/v1`)**

![PHP](https://shields.io)
![Laravel](https://shields.io)
![SQLite](https://shields.io)
![API](https://shields.io)
![REST Client](https://shields.io)
[![License: MIT](https://shields.io)](./LICENSE)

</div>

---

## Daftar Isi
- [Identitas Mata Kuliah](#-identitas-mata-kuliah--penugasan)
- [Anggota Kelompok](#-anggota-kelompok)
- [Instalasi & Menjalankan Project](#-1-spesifikasi--instalasi-project)
- [Dokumentasi Pengujian ORM](#-2-dokumentasi-pengujian-orm-php-artisan-tinker)
- [Daftar Endpoint REST API](#-3-daftar-endpoint-rest-api-apiv1)
- [Jawaban Evaluasi Laporan](#-4-jawaban-pertanyaan-evaluasi-laporan)
- [Panduan Git & Troubleshooting](#-5-panduan-git--troubleshooting)
- [Lisensi](#-lisensi)

---

## Identitas Mata Kuliah & Penugasan

| Atribut | Keterangan |
| :--- | :--- |
| **Mata Kuliah** | `IFT5251P` — Pemrograman Berbasis Kerangka Kerja (Pertemuan 9) |
| **Institusi** | S1 Teknik Informatika, **STMIK Widya Cipta Dharma** |
| **Materi** | Database Integration & Object-Relational Mapping (ORM) |
| **CPL10** | Memiliki kemampuan mengimplementasikan, mengevaluasi, dan mengembangkan solusi berbasis *computing multi-platform* yang memenuhi kebutuhan organisasi. |
| **CPMK101 [C3]** | Mengimplementasikan integrasi database dan Object-Relational Mapping pada sisi server. |
| **Indikator** | Keberhasilan melakukan operasi CRUD ke database menggunakan ORM framework. |

---

## Anggota Kelompok

| No | NIM | Nama Mahasiswa |
| :---: | :---: | :--- |
| 1 | `2443002` | Ainun Nisa |
| 2 | `2443013` | Ihwan Farid |
| 3 | `2443038` | Fitri Annisya |
| 4 | `2443050` | Athifah Najwa Nurshabrina |
| 5 | `2443088` | Angel Pink Wong |

---

## 1. Spesifikasi & Instalasi Project

### Kebutuhan Lingkungan
* **PHP** ≥ 8.2 (ekstensi aktif: `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`)
* **Composer** 2.x
* **Git**
* **VS Code** dengan ekstensi **REST Client** (`humao.rest-client`) untuk pengujian API

### Langkah Menjalankan Project

```bash
# 1. Masuk ke direktori project
cd praktikum-orm

# 2. Install dependensi PHP
composer install

# 3. Salin konfigurasi environment & generate app key
cp .env.example .env && php artisan key:generate

# 4. Buat file database SQLite
touch database/database.sqlite
# Catatan Windows CMD: type nul > database\database.sqlite

# 5. Jalankan migrasi & seeder data awal
php artisan migrate:fresh --seed

# 6. Jalankan server lokal
php artisan serve
```

> **Info Seeding Awal & Verifikasi:**
> * **Data Awal:** `4` jenis surat (`SKAK`, `PKL`, `SIP`, `SRB`), `2` dosen, `22` mahasiswa, dan `22` data surat.
> * **Cek Status DB & Route:** Jalankan `php artisan db:show` dan `php artisan route:list --path=api/v1`.

---

## 2. Dokumentasi Pengujian ORM (`php artisan tinker`)

Pengujian dilakukan langsung pada konsol interaktif Laravel (`Psy Shell v0.12.24`, PHP `8.2.12`) untuk memverifikasi operasi CRUD, proteksi *Foreign Key*, perbandingan query *N+1*, dan *Database Transaction Rollback*.

### A. Checkpoint 5 — Operasi CRUD & Foreign Key Constraint
1. **Create:** Menambahkan data mahasiswa baru (`Rina Wulandari`, NIM `2301099`) menggunakan `Mahasiswa::create(...)` berhasil disimpan dengan `id: 25`.
2. **Update:** Mengubah atribut `prodi` dari `"Sistem Informasi"` menjadi `"Teknik Informatika"` via `$m->update(...)` mengembalikan nilai `true`.
3. **Read:** Pencarian menggunakan `Mahasiswa::where('nama', 'like', '%rina%')->get()` berhasil menampilkan data terbaru.
4. **Delete & Foreign Key:**
   * Menghapus record mahasiswa yang baru dibuat (`$m->delete()`) berhasil (`true`).
   * Mencoba menghapus data master jenis surat yang masih berelasi dengan tabel `surat` (`JenisSurat::find(1)->delete()`) ditolak oleh database dengan eksepsi:
     ```text
     Illuminate\Database\QueryException: SQLSTATE: Integrity constraint violation: 19 FOREIGN KEY constraint failed
     ```
     Hal ini membuktikan aturan `restrictOnDelete()` pada skema migration berjalan sesuai rancangan.

### B. Checkpoint 6 — Analisis Masalah N+1 Query vs Eager Loading
Pengukuran jumlah query SQL dilakukan menggunakan `DB::enableQueryLog()` terhadap **22 data surat**:

| Metode Pengambilan Data | Perintah Eloquent | Jumlah Query | Keterangan |
| :--- | :--- | :---: | :--- |
| **Tanpa Eager Loading (Lazy)** | `Surat::all()->each(fn ($s) => $s->mahasiswa->nama);` | **23 query** | 1 query mengambil seluruh surat + 22 query tambahan untuk mencari mahasiswa di setiap iterasi ($1 + N$). |
| **Dengan Eager Loading** | `Surat::with('mahasiswa')->get()->each(fn ($s) => $s->mahasiswa->nama);` | **2 query** | 1 query mengambil seluruh surat + 1 query mengambil seluruh mahasiswa terkait dengan klausa `WHERE id IN (...)`. |

### C. Checkpoint 7 — Konsistensi Data dengan Transaction Rollback
1. Jumlah data surat awal sebelum transaksi: **`Surat::count()` = 22**.
2. Dijalankan blok `DB::transaction()` yang melakukan `Surat::create(...)` lalu memicu `throw new Exception('Gagal di tengah jalan!')`.
3. Eksepsi tertangkap (`Gagal di tengah jalan!`), dan saat dicek kembali nilai **`Surat::count()` tetap 22**. Operasi `INSERT` otomatis dibatalkan (*rollback*) sehingga tidak terjadi data yatim (*orphan data*) tanpa riwayat status.

---

## 3. Daftar Endpoint REST API (`/api/v1`)

### Cara Pengujian dengan VS Code REST Client
Pengujian seluruh *endpoint* API tidak menggunakan Postman, melainkan langsung di dalam editor menggunakan ekstensi **REST Client** (`humao.rest-client`) dari Visual Studio Code:
1. Pastikan server lokal sudah berjalan (`php artisan serve` di `http://127.0.0.1:8000`).
2. Buka file skenario pengujian **`uji-api.http`** atau **`uji-api-lengkap.http`** di VS Code.
3. Klik tombol **`Send Request`** yang muncul tepat di atas setiap blok metode HTTP (wajib menyertakan header `Accept: application/json`).

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` / `POST` | `/api/v1/mahasiswa` | Daftar mahasiswa (`?q=&per_page=`) / tambah mahasiswa baru (`201` / `422`) |
| `GET` / `PUT` / `PATCH` / `DELETE` | `/api/v1/mahasiswa/{id}` | Detail mahasiswa (+ relasi surat) / ubah / hapus (`cascade` ke surat) |
| `GET` / `POST` | `/api/v1/surat` | Daftar surat (`?status=&per_page=`) / ajukan surat baru (transaksi + riwayat awal) |
| `GET` / `DELETE` | `/api/v1/surat/{id}` | Detail surat (+ relasi dosen) / *soft delete* surat |
| `PATCH` | `/api/v1/surat/{id}/status` | Ubah status pengajuan (`diajukan` → `diproses` → `selesai`/`ditolak`) |
| `GET` | `/api/v1/surat/trashed` | Melihat daftar surat yang telah dihapus (*soft deleted*) |
| `PATCH` | `/api/v1/surat/{id}/restore` | Memulihkan surat dari tempat sampah (*restore*) |
| `apiResource` | `/api/v1/jenis-surat` | CRUD jenis surat (mengembalikan `409 Conflict` jika masih dipakai) |
| `GET` | `/api/v1/statistik` | Rekapitulasi jumlah surat berdasarkan status dan jenis surat |

---

## 4. Jawaban Pertanyaan Evaluasi Laporan

<details>
<summary><strong>Klik untuk melihat jawaban lengkap evaluasi laporan (4 Pertanyaan)</strong></summary>
<br>

1. **Dampak menghapus `protected $table = 'mahasiswa';` pada model:**
   Laravel secara otomatis menerapkan konvensi penamaan jamak bahasa Inggris (*pluralization*) sehingga mencari tabel bernama `mahasiswas`. Karena tabel di database bernama `mahasiswa`, sistem akan memunculkan error `SQLSTATE[HY000]: General error: 1 no such table: mahasiswas`.

2. **Alasan `status` dan `nomor` tidak dimasukkan ke `$fillable` model `Surat`:**
   Untuk mencegah kerentanan *Mass Assignment*. Kedua kolom tersebut merupakan aturan bisnis yang dikendalikan sepenuhnya oleh sistem (nomor digenerate otomatis setelah ID terbentuk, dan status awal wajib `diajukan` serta hanya boleh berubah melalui transisi valid pada method `ubahStatus()`).

3. **Efisiensi Eager Loading berdasarkan hasil percobaan:**
   Pada pengujian 22 data surat, akses relasi tanpa *eager loading* menghasilkan **23 query** ($1 + 22$), sedangkan menggunakan `Surat::with('mahasiswa')` hanya menjalankan **2 query**. Semakin besar jumlah data, *eager loading* menjaga jumlah query tetap konstan (2 query) dan menghindari beban latensi database yang berulang.

4. **Perbedaan `cascadeOnDelete()` dan `restrictOnDelete()`:**
   * `cascadeOnDelete()` menghapus otomatis seluruh data anak (*child*) ketika data induk (*parent*) dihapus (contoh: menghapus data mahasiswa akan ikut menghapus seluruh surat miliknya).
   * `restrictOnDelete()` menolak penghapusan data induk selama masih ada data anak yang merujuk kepadanya (contoh: master `jenis_surat` tidak dapat dihapus apabila sudah pernah digunakan pada tabel `surat`).

</details>

---

## 5. Panduan Git & Troubleshooting

> **Catatan Keamanan Git:** File `.env` dan `database/*.sqlite` sudah dikecualikan melalui `.gitignore` agar kredensial dan file database lokal tidak ikut terunggah ke repositori GitHub.

| Kendala | Solusi |
| :--- | :--- |
| `could not find driver` | Aktifkan `extension=pdo_sqlite` pada file konfigurasi `php.ini`. |
| `Database file ... does not exist` | Buat file kosong `database/database.sqlite` lalu jalankan `php artisan migrate:fresh --seed`. |
| Respons berupa HTML, bukan JSON | Pastikan menyertakan header `Accept: application/json` pada setiap request di file `.http`. |
| Perubahan `.env` tidak terbaca | Jalankan perintah `php artisan config:clear`. |

---

## Lisensi

Proyek praktikum ini dilisensikan dan didistribusikan di bawah ketentuan yang tercantum dalam file **[LICENSE](./LICENSE)**.