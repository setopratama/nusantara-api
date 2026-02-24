# Nusantara API Documentation (Enterprise Edition)

Dokumentasi API modern berbasis web yang mendukung manajemen koleksi, pengujian endpoint secara real-time, dan audit log lengkap. Didesain untuk memberikan pengalaman pengguna yang premium, serupa dengan Postman namun dalam format web yang ringan dan self-hosted.

## 🌟 Fitur Unggulan

- **Collection Management**: Kelola dokumentasi API dalam berbagai koleksi khusus. Mendukung pengubahan nama dan penghapusan (khusus Superadmin).
- **Authentication Support**: Dukungan penuh untuk **Bearer Token** dan **Basic Auth** pada setiap endpoint, termasuk dukungan variabel lingkungan.
- **Import from Postman**: Migrasi data super cepat! Impor koleksi Postman Anda (format v2.0 atau v2.1) langsung melalui file JSON.
- **Advanced Request Builder**: 
  - Mendukung metode GET, POST, PUT, DELETE, dll.
  - Editor dinamis untuk URL Params, Headers, dan Request Body.
  - Dukungan Request Body format **Raw JSON** dan **Form Data**.
- **Environment & Variables**:
  - **Dynamic Substitution**: Gunakan variabel seperti `{{base_url}}` atau `{{api_key}}` yang otomatis diganti di URL, Header, Body, dan Auth.
  - **Multi-Profile**: Kelola profil **Staging**, **Production**, atau **Local** dengan mudah.
- **Improved UI/UX**:
  - **Response Word Wrap**: Fitur lipat teks untuk pembacaan respon JSON yang panjang.
  - **Premium Dark Mode**: Antarmuka berbasis Glassmorphism menggunakan CSS Variables.
- **Integrated Audit Logs**: Pencatatan aktivitas sistem yang aman (SQL Injection Protected) untuk memantau setiap aksi user.

## 🛠 Tech Stack

- **Backend**: PHP 8.x (Native) dengan ekstensi cURL aktif.
- **Database**: MySQL / MariaDB (Mendukung kolom tipe JSON).
- **Frontend**: Vanilla JavaScript (ES6+), Modern CSS (Variables & Flexbox).
- **Typography**: Inter (UI) & IBM Plex Mono (Code).

## 📊 Struktur Data & Keamanan

- **Kolom JSON**: Tabel `endpoints` menggunakan tipe data JSON untuk kolom `params`, `headers`, dan `auth` guna fleksibilitas struktur data.
- **Audit Logging**: Gunakan fungsi helper `log_action($conn, $user_id, $action, $details, $endpoint_id)` di `api.php`. Fungsi ini memastikan data di-*escape* dengan benar untuk mencegah SQL Injection.
- **Auto-Migration**: Sistem melakukan pengecekan kolom/tabel secara otomatis di awal `api.php` menggunakan pola `SHOW COLUMNS` yang aman bagi berbagai versi database.

## 🤖 Agentic AI Development (AI Handoff Guide)

Software ini dirancang untuk "Seamless AI Handoff". Berikut adalah konteks teknis untuk AI Agent:

- **State Management**: Seluruh state diatur dalam `app.js` menggunakan variabel global seperti `projects` dan `endpoints`. Sinkronisasi UI dilakukan melalui fungsi `renderSidebar()` dan `loadEndpointToUI()`.
- **Router Logic**: `api.php` bertindak sebagai controller tunggal dengan parameter `?action=`. Pastikan setiap action mengembalikan respon JSON dan menangani `http_response_code` dengan benar.
- **Security Check**: Cek fungsi `is_logged_in()` dan `require_login()` di `auth.php`. Seluruh request API wajib melewati pengecekan sesi di baris awal `api.php`.
- **Variable Engine**: Logika penggantian variabel `{{var}}` berada di `api.php` dalam aksi `send_request`. Logika ini bersifat rekursif untuk string, array, dan objek JSON.

## 🧠 Pedoman Pengembangan

1. **DB Updates**: Jika menambah kolom, gunakan pengecekan manual (`SHOW COLUMNS`) di `api.php` agar kompatibel dengan target server (VPS/Shared Hosting).
2. **Logging**: Selalu panggil `log_action()` setelah operasi `INSERT/UPDATE/DELETE` yang krusial.
3. **Styling**: Gunakan CSS Variables (`--accent-primary`, dll) di `modern-ui.css`. Hindari *hardcoded colors*.
4. **JSON Ops**: Selalu gunakan `json_encode` di PHP dan `JSON.stringify` di JS saat bertukar data kompleks melalui API.

---
**Antigravity Agentic AI**
*Inovasi Tanpa Batas untuk Nusantara API Documentation.*
