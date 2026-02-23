# Nusantara API Documentation (Enterprise Edition)

Dokumentasi API modern berbasis web yang mendukung manajemen koleksi, pengujian endpoint secara real-time, dan audit log lengkap. Didesain untuk memberikan pengalaman pengguna yang premium, serupa dengan Postman namun dalam format web yang ringan dan self-hosted.

## 🌟 Fitur Unggulan

- **Collection Management**: Kelola dokumentasi API dalam berbagai koleksi khusus. Mendukung pengubahan nama dan penghapusan (khusus Superadmin).
- **Import from Postman**: Migrasi data super cepat! Impor koleksi Postman Anda (format v2.0 atau v2.1) langsung melalui file JSON.
- **Advanced Request Builder**: 
  - Mendukung metode GET, POST, PUT, DELETE, dll.
  - Editor dinamis untuk URL Params, Headers, dan Request Body.
  - Dukungan Request Body format **Raw JSON** dan **Form Data**.
- **Backend Proxy (CORS Bypass)**: Tidak perlu lagi khawatir dengan masalah CORS saat mencoba API. Seluruh request diproses melalui server backend menggunakan PHP cURL.
- **Beautiful Response Viewer**:
  - **JSON Syntax Highlighting**: Pewarnaan otomatis untuk respon JSON agar mudah dibaca.
  - Statistik Response: Menampilkan HTTP Status, waktu respon (latency), dan ukuran data secara real-time.
- **Smart Sidebar**:
  - Pengelompokan berdasarkan Kategori/Group.
  - **Batch Rename Category**: Ubah nama grup/folder secara massal dalam sekali klik.
  - Indikator Active State: Penanda endpoint yang sedang dibuka berbasis ID unik.
- **Role-Based Access Control (RBAC)**:
  - `Superadmin`: Kendali penuh, termasuk menghapus Koleksi dan Endpoint.
  - `Admin`: Dapat mengelola data Koleksi dan Endpoint.
  - `Viewer`: Hanya dapat melihat dan mencoba API tanpa izin modifikasi data.
- **Audit Logging**: Rekam jejak aktivitas user untuk setiap aksi (Create, Update, Delete, Import).

## 🛠 Tech Stack

- **Backend**: PHP 8.x (Native) dengan ekstensi cURL aktif.
- **Database**: MySQL / MariaDB.
- **Frontend**: Vanilla JavaScript (ES6+), Modern CSS.
- **Icons**: Font Awesome 6.
- **Typography**: Inter (UI) & Fira Code (Editor/JSON).

## 🚀 Instalasi & Persiapan

1. **Konfigurasi Database**:
   - Buat database baru bernama `nusantara_apidoc`.
   - Import file `database.sql` ke dalam database tersebut.
   
2. **Konfigurasi Aplikasi**:
   - Pastikan konfigurasi database di `config.php` sudah benar sesuai dengan environment server Anda.
   - Pastikan PHP memiliki izin untuk melakukan `exec` atau `curl`.

3. **Jalankan Aplikasi**:
   - Akses root folder aplikasi melalui web server Anda.
   - Login menggunakan akun yang sudah terdaftar di tabel `users`.

## 📊 Struktur Database Utama

- `users`: Mengelola data pengguna, password (hash), dan role.
- `projects` (Collections): Master data grup koleksi utama.
- `endpoints`: Detail instruksi API termasuk payload JSON yang terenkripsi di baris params/headers.
- `audit_logs`: Log keamanan dan aktivitas sistem.

## 🧠 Pedoman Pengembangan (Untuk Developer/AI Agent)

1. **Interaksi API**: Gunakan `api.php?action=[nama_action]` untuk seluruh operasional data.
2. **CORS Safe**: Selalu gunakan proxy backend jika ingin menguji endpoint dari domain eksternal.
3. **Styling**: Variabel warna dan spacing tersentralisasi di `:root` pada file `modern-ui.css`.
4. **Keamanan**: Seluruh query database wajib menggunakan **Prepared Statements** untuk mencegah SQL Injection.

---
*Dikembangkan oleh Antigravity untuk Nusantara PHP VPS Project - Memberdayakan Pengembang Indonesia.*
