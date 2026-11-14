# CLAUDE.md — Panduan Pengembangan Website Apotek

Dokumen ini adalah konteks proyek dan aturan kerja untuk Claude Code. Baca seluruh dokumen ini sebelum mengubah kode. Tujuan pengembangan adalah meningkatkan website apotek menjadi aplikasi yang lebih matang, aman, konsisten, responsif, dan mudah dikembangkan tanpa mengganti teknologi inti secara sembarangan.

## 1. Ringkasan proyek saat ini

Proyek pada ZIP yang dianalisis adalah aplikasi Laravel dengan:

- **Backend:** PHP `^8.3`, Laravel `^13.8`.
- **Frontend:** Blade templates, Tailwind CSS `^4.3`, Vite `^8`.
- **JavaScript:** ES modules sederhana melalui `resources/js/app.js`.
- **Database:** Laravel migrations dan Eloquent ORM. Konfigurasi mendukung database Laravel; jangan mengasumsikan database produksi tertentu sebelum memeriksa `.env`.
- **Autentikasi:** Laravel session authentication buatan sendiri melalui `AuthController`.
- **UI:** Blade components untuk navbar dan footer.

Struktur penting:

```text
app/
  Http/Controllers/
    AuthController.php
    FirstController.php
    MedicineController.php
  Models/
    Category.php
    Medicine.php
    Obat.php
    User.php
database/
  migrations/
    create_users_table
    create_categories_table
    create_medicines_table
resources/
  css/app.css
  js/app.js
  views/
    auth/
    components/
    medicine/
    index.blade.php
routes/web.php
```

Fitur yang sudah terlihat di kode:

- Halaman publik beranda yang menampilkan daftar obat.
- Halaman About dan halaman contoh Page 2.
- Registrasi, login, dan logout.
- CRUD obat: daftar, tambah, detail, edit, dan hapus.
- Upload gambar obat.
- Kategori obat sudah memiliki migration dan model awal.

**Penting:** Ini adalah kondisi yang teramati dari source code, bukan jaminan bahwa semua fitur berjalan benar. Jalankan aplikasi dan tes sebelum menyatakan fitur berfungsi.

## 2. Temuan audit awal yang wajib ditangani

Gunakan temuan ini sebagai prioritas audit. Verifikasi ulang pada kode terkini sebelum mengubahnya.

### P0 — Bug, keamanan, dan integritas data

1. **Autentikasi logout berpotensi error**
   - `AuthController` memanggil `Auth::logout()` tetapi file yang dianalisis belum mengimpor facade `Auth`.
   - Form logout di navbar menggunakan `POST`, sedangkan route `/logout` terdaftar sebagai `GET`.
   - Ubah menjadi route bernama dengan metode `POST`, gunakan `Auth` atau helper `auth()`, invalidate session, regenerate CSRF token, lalu redirect.
   - Jangan sediakan logout melalui GET.

2. **Akses detail obat tidak konsisten**
   - Route detail `/obat/{id}` berada di dalam middleware `auth`, sedangkan kartu obat ditampilkan di beranda publik dan mengarah ke route tersebut.
   - Tentukan kebijakan: detail obat boleh publik atau harus login. Terapkan konsisten pada route, UI, dan authorization.
   - Jangan menghapus proteksi halaman admin hanya demi membuat link publik berfungsi.

3. **Operasi hapus menggunakan GET**
   - Route delete saat ini memakai GET. Ubah menjadi `DELETE` dengan `@method('DELETE')`, CSRF, dan konfirmasi UI.
   - Gunakan route model binding atau pencarian yang menangani data tidak ditemukan secara benar.

4. **Validasi input CRUD belum memadai**
   - `MedicineController::store()` dan `update()` mengambil field `a`, `b`, `c`, `d` tanpa validasi.
   - Ganti dengan nama field bermakna: `name`, `description`, `price`, `expired`, `category_id`, dan `image`.
   - Validasi server-side untuk semua field; validasi client-side hanya pelengkap.
   - Harga harus numerik dan tidak negatif; tanggal harus valid; kategori harus ada; gambar harus dibatasi tipe MIME dan ukuran.

5. **Upload gambar belum aman dan belum dikelola**
   - Kode memindahkan file menggunakan nama asli ke folder publik `gambars`.
   - Gunakan Laravel Storage, nama file unik, validasi MIME/ukuran, dan disk yang sesuai.
   - Jangan percaya ekstensi atau nama file dari pengguna.
   - Saat gambar diganti atau data obat dihapus, hapus file lama dengan aman setelah memastikan file tersebut memang milik record itu.
   - Sediakan fallback image jika gambar kosong atau file tidak tersedia.
   - Pertimbangkan kompatibilitas gambar lama di `public/gambars`; jangan memutus URL lama tanpa migrasi yang terencana.

6. **Relasi kategori dan mass assignment belum sinkron**
   - Migration `medicines` memiliki `category_id` foreign key yang wajib, tetapi `$fillable` pada model `Medicine` yang dianalisis belum memasukkan `category_id`.
   - Model `Category` belum mendefinisikan relasi.
   - Implementasikan relasi `Medicine belongsTo Category` dan `Category hasMany Medicine`, lalu tentukan apakah kategori wajib atau nullable berdasarkan kebutuhan aplikasi.
   - Buat migration tambahan bila perlu; jangan mengedit migration lama yang sudah mungkin dijalankan di database pengguna.

7. **Not-found dan otorisasi**
   - Penggunaan `find()` dapat menghasilkan `null`, lalu view mencoba mengakses propertinya.
   - Gunakan `findOrFail()` atau route model binding.
   - Pastikan hanya role/akun yang berwenang dapat mengelola data. Middleware `auth` saja tidak otomatis berarti pengguna adalah admin.
   - Jangan menganggap field `role` yang ada di tabel users sudah menjadi sistem role/permission yang aman.

8. **Logout dan session security**
   - Pastikan session diregenerasi setelah login dan di-invalidate saat logout.
   - Jangan pernah menyimpan password dalam bentuk plaintext.
   - Pertahankan hashing Laravel dan jangan mengekspos password/token di view, log, atau response.

### P1 — Kualitas aplikasi dan pengalaman pengguna

1. **Beranda masih berupa landing page sederhana**
   - Title masih `Document`, bahasa dokumen belum konsisten, ada Lorem Ipsum dan CTA placeholder.
   - Bangun landing page apotek yang informatif: hero, pencarian/katalog obat, kategori, informasi apotek, dan footer yang relevan.
   - Jangan membuat klaim medis, klaim “obat terbaik”, diskon, atau informasi operasional yang tidak didukung data nyata.

2. **Halaman admin CRUD belum menjadi dashboard**
   - `medicine/medicine.blade.php`, `create.blade.php`, dan `edit.blade.php` masih sangat dasar.
   - Buat layout dashboard yang konsisten: sidebar/topbar, breadcrumb, judul halaman, action utama, tabel/list responsif, empty state, loading/feedback, dan form yang jelas.
   - Tambahkan ringkasan statistik hanya jika datanya benar-benar tersedia dan perhitungannya akurat.

3. **Form tidak konsisten**
   - Gunakan `old()` untuk mempertahankan input saat validasi gagal.
   - Tampilkan error per field dengan `@error`.
   - Gunakan `@csrf` dan method spoofing sesuai HTTP method.
   - Tambahkan label, helper text, required state, disabled/loading state jika ada proses asynchronous, serta konfirmasi sebelum aksi destruktif.
   - Form edit sebaiknya mendukung preview gambar saat ini dan upload gambar pengganti.

4. **Daftar obat perlu fitur pengelolaan**
   - Tambahkan pencarian, filter kategori, filter status kedaluwarsa, sorting, dan pagination.
   - Gunakan query Eloquent yang efisien dan whitelist kolom sorting.
   - Hindari mengambil seluruh tabel dengan `Medicine::all()` untuk data yang dapat bertambah besar.
   - Gunakan eager loading untuk relasi kategori agar tidak terjadi N+1 query.

5. **Detail produk**
   - Hilangkan kontrol quantity/cart yang saat ini hanya tampilan dan belum terhubung ke proses apa pun, kecuali memang akan dibangun sebagai fitur transaksi yang nyata.
   - Tampilkan informasi yang benar-benar tersedia: nama, harga, deskripsi, kategori, gambar, dan tanggal kedaluwarsa.
   - Tampilkan status kedaluwarsa berdasarkan tanggal saat ini dengan penanganan zona waktu yang konsisten. Jangan menampilkan obat kedaluwarsa sebagai produk yang tersedia untuk dibeli.

6. **Navigasi**
   - Link Login pada navbar saat ini mengarah ke `#`; arahkan ke route login.
   - Tombol menu mobile belum memiliki interaksi yang lengkap. Implementasikan toggle yang accessible atau hilangkan tombol jika tidak diperlukan.
   - Gunakan named routes (`route('...')`) alih-alih hard-coded URL pada Blade.

7. **Bahasa dan aksesibilitas**
   - Gunakan `lang="id"` untuk halaman berbahasa Indonesia.
   - Gunakan heading hierarchy yang benar, label form, alt text gambar yang bermakna, focus state, kontras memadai, dan navigasi keyboard.
   - Pastikan UI dapat digunakan pada layar ponsel, tablet, dan desktop.

### P2 — Struktur dan maintainability

1. Konsolidasikan layout Blade menggunakan `resources/views/layouts/app.blade.php` (atau nama konsisten yang dipilih), `@yield`/Blade components, dan partial reusable.
2. Buat komponen UI reusable untuk button, input, select, alert, badge/status, modal konfirmasi, dan pagination bila memang dipakai berulang.
3. Gunakan Form Request untuk validasi yang mulai kompleks.
4. Pindahkan query dan aturan bisnis yang kompleks dari controller ke service/action class hanya ketika kompleksitasnya memang membenarkan. Hindari overengineering.
5. Gunakan enum/constant untuk status atau role jika membantu konsistensi, bukan menambah abstraksi tanpa kebutuhan.
6. Rapikan model dan file dummy/eksperimen setelah memastikan tidak ada dependency. Jangan menghapus file hanya karena namanya terlihat tidak terpakai.
7. Tambahkan test untuk autentikasi, authorization, validasi, CRUD, upload gambar, kategori, dan filter katalog.

## 3. Arah produk yang disarankan

Bangun aplikasi sebagai **website katalog apotek dengan area pengelolaan internal** terlebih dahulu. Jangan langsung menganggap proyek ini harus menjadi marketplace atau sistem kasir lengkap.

### Area publik

- Beranda dengan identitas apotek yang jelas.
- Katalog obat dengan pencarian, kategori, dan pagination.
- Detail obat.
- Informasi apotek, jam operasional, lokasi, dan kontak hanya jika datanya diberikan pemilik.
- State ketika katalog kosong atau pencarian tidak menemukan hasil.
- Informasi ketersediaan dan kedaluwarsa hanya jika model data mendukungnya.

### Area internal/admin

- Login dan logout yang aman.
- Dashboard ringkas.
- CRUD obat.
- CRUD kategori.
- Pencarian, filter, sorting, pagination.
- Pengelolaan gambar.
- Penanda obat mendekati kedaluwarsa/kedaluwarsa.
- Profil akun dan pengaturan dasar jika diperlukan.

### Fitur lanjutan — jangan dibuat tanpa keputusan produk

Fitur berikut dapat dipertimbangkan setelah fitur inti stabil:

- Stok dan mutasi inventaris.
- Supplier dan pembelian.
- Penjualan/POS dan invoice.
- Riwayat aktivitas admin.
- Multi-role/permission.
- Export laporan.
- Notifikasi stok minimum dan kedaluwarsa.

Sebelum membangun fitur lanjutan yang mengubah alur bisnis, jelaskan asumsi dan minta konfirmasi jika kebutuhannya belum jelas. Jangan membuat checkout, pembayaran, resep digital, atau klaim konsultasi medis hanya karena dianggap “lebih advance”.

## 4. Prinsip desain UI/UX

Target visual: **modern, bersih, profesional, ramah, dan terasa seperti produk apotek sungguhan**. Hindari desain generik hasil template AI.

### Design direction

- Gunakan warna utama bernuansa hijau/teal yang berasosiasi dengan kesehatan, dipadukan dengan putih/off-white dan neutral gray.
- Gunakan satu warna aksen secara konsisten; jangan memakai terlalu banyak gradient.
- Gunakan tipografi sans-serif yang mudah dibaca dan hierarki ukuran yang jelas.
- Beri ruang antar elemen yang cukup; hindari layout terlalu padat atau terlalu banyak card.
- Gunakan border, shadow, radius, dan hover effect secara halus dan konsisten.
- Gunakan icon set yang konsisten. Jangan mencampur emoji dengan icon profesional di area dashboard.
- Hindari dekorasi berlebihan, glassmorphism yang tidak perlu, gradient acak, teks placeholder, dan animasi yang mengganggu.
- Gunakan gambar produk yang konsisten rasionya, object-fit yang benar, dan fallback.
- Jangan memakai foto stok yang tidak relevan atau mengarang identitas visual apotek.

### Responsive behavior

- Mobile-first.
- Navbar mobile harus berfungsi.
- Tabel admin harus berubah menjadi layout yang tetap terbaca pada layar kecil (misalnya horizontal scroll terkontrol atau card list).
- Form satu kolom di mobile; gunakan grid pada layar lebih besar jika meningkatkan usability.
- Hindari fixed width yang menyebabkan horizontal overflow.
- Semua tombol dan input memiliki ukuran target sentuh yang nyaman.

### Interaksi

- Setiap tombol/link harus memiliki aksi yang benar. Jangan membuat CTA dekoratif yang tidak bekerja.
- Berikan feedback sukses/gagal yang konsisten.
- Gunakan modal konfirmasi untuk penghapusan.
- Berikan empty state, error state, dan state gambar gagal.
- Animasi singkat dan fungsional; hormati `prefers-reduced-motion`.

## 5. Aturan arsitektur dan coding

### Laravel/PHP

- Ikuti konvensi Laravel dan PSR-12; gunakan Laravel Pint jika tersedia.
- Gunakan named routes dan route model binding.
- Gunakan middleware untuk autentikasi dan authorization.
- Gunakan Policies/Gates untuk otorisasi resource jika diperlukan.
- Gunakan `$request->validate()` atau Form Request.
- Gunakan Eloquent relationship; hindari query mentah tanpa alasan.
- Gunakan pagination untuk daftar yang berkembang.
- Jangan menerima field sensitif/role dari input publik tanpa authorization.
- Gunakan database transaction untuk operasi multi-tabel yang harus atomik.
- Jangan mengubah schema production secara destruktif.
- Jangan memasukkan `.env`, credential, API key, atau data pengguna ke Git.

### Blade

- Escape output dengan `{{ }}` secara default.
- Jangan gunakan `{!! !!}` untuk input pengguna.
- Hindari duplikasi layout.
- Gunakan `route()` untuk URL internal.
- Gunakan `@csrf`, `@method`, `@error`, `old()`, dan `@auth/@guest` dengan benar.
- Hindari JavaScript inline yang panjang; pindahkan interaksi reusable ke `resources/js`.

### Frontend

- Pertahankan Blade + Tailwind + Vite kecuali ada alasan teknis kuat dan persetujuan eksplisit untuk migrasi.
- Gunakan Tailwind CSS v4 sesuai konfigurasi proyek.
- Jangan menambahkan framework frontend besar hanya untuk interaksi sederhana.
- Jika menambahkan dependency, jelaskan manfaat, ukuran/kompleksitas, dan alternatif native.
- Pastikan `npm run build` berhasil.

### Database

- Migration baru harus dapat di-rollback.
- Jangan mengubah migration lama yang mungkin sudah pernah dijalankan pada database nyata; buat migration baru untuk perubahan schema.
- Tambahkan index pada kolom yang sering dipakai untuk pencarian/filter jika memang diperlukan.
- Jaga foreign key dan referential integrity.
- Gunakan tipe data sesuai kebutuhan. Evaluasi penggunaan integer untuk harga dan tentukan aturan penyimpanan rupiah secara konsisten.
- Seeder harus aman dijalankan ulang dan tidak merusak data pengguna.

### Keamanan

- Terapkan validasi input di server.
- Terapkan authorization per aksi, bukan hanya menyembunyikan tombol di UI.
- Lindungi seluruh mutasi dengan CSRF.
- Gunakan HTTP method yang sesuai: GET untuk membaca, POST untuk membuat, PUT/PATCH untuk memperbarui, DELETE untuk menghapus.
- Batasi dan validasi file upload.
- Jangan percaya nama file, MIME dari client, ID record, atau nilai role dari browser.
- Hindari menampilkan detail exception di production.
- Gunakan rate limiting untuk endpoint autentikasi jika sesuai.
- Jangan menyimpan informasi medis pribadi atau data sensitif tanpa kebutuhan, persetujuan, dan desain keamanan yang jelas.

## 6. Testing dan quality gate

Sebelum menyatakan pekerjaan selesai:

1. Jalankan `php artisan route:list` dan periksa route, middleware, nama route, serta HTTP method.
2. Jalankan `php artisan test`.
3. Jalankan `vendor/bin/pint --test` jika Laravel Pint tersedia.
4. Jalankan `npm run build`.
5. Periksa migration pada database development/test yang aman.
6. Uji manual:
   - Guest membuka beranda dan katalog.
   - Guest mencoba mengakses halaman internal.
   - Login valid dan invalid.
   - Logout melalui POST.
   - Admin membuat, melihat, mengedit, dan menghapus obat.
   - Validasi gagal menampilkan error yang tepat.
   - Upload file valid dan file tidak valid.
   - Kategori terhubung dengan obat.
   - Data tidak ditemukan menampilkan 404 yang layak.
   - Search/filter/pagination bekerja.
   - Tampilan mobile dan desktop tidak overflow.
7. Jangan menjalankan `migrate:fresh`, menghapus database, atau menghapus file upload pengguna tanpa izin eksplisit.
8. Jika command tidak dapat dijalankan karena dependency atau environment belum tersedia, laporkan dengan jelas. Jangan mengklaim test lulus.

## 7. Workflow Claude Code

Ikuti workflow berikut untuk setiap task:

### Tahap A — Pahami sebelum mengubah

- Baca `CLAUDE.md`, `composer.json`, `package.json`, `routes/web.php`, controller, model, migration, dan view terkait.
- Periksa status Git dan perubahan lokal. Jangan menimpa perubahan pengguna.
- Telusuri alur request dari route → middleware → controller → model/database → Blade.
- Identifikasi dependency dan efek samping perubahan.
- Jika ada bagian yang belum jelas, tulis asumsi secara eksplisit.

### Tahap B — Rencanakan

- Pecah pekerjaan menjadi langkah kecil.
- Dahulukan bug dan keamanan sebelum visual polish.
- Sebutkan file yang akan diubah dan alasan.
- Hindari refactor besar yang tidak dibutuhkan task.
- Untuk perubahan schema atau alur bisnis, jelaskan dampak dan risiko terlebih dahulu.

### Tahap C — Implementasikan

- Kerjakan fitur end-to-end, bukan hanya tampilan.
- Pertahankan kompatibilitas dengan data yang sudah ada.
- Jangan membuat tombol palsu, data statistik palsu, atau interaksi yang hanya terlihat bekerja.
- Jangan menghapus fitur lama tanpa alasan dan persetujuan.
- Tulis kode yang jelas, reusable secukupnya, dan sesuai konvensi stack.

### Tahap D — Verifikasi

- Jalankan test/build/lint yang relevan.
- Perbaiki error yang muncul akibat perubahan.
- Tinjau ulang keamanan, responsive behavior, empty/error states, dan accessibility.
- Periksa diff agar tidak ada file atau konfigurasi tidak relevan ikut berubah.

### Tahap E — Laporkan

Setiap selesai task, berikan:
- Ringkasan perubahan.
- Daftar file utama yang berubah.
- Fitur/bug yang selesai.
- Perintah test yang dijalankan dan hasil sebenarnya.
- Catatan risiko, asumsi, atau pekerjaan yang belum selesai.

## 8. Urutan roadmap implementasi

Kerjakan bertahap agar proyek tetap stabil.

### Phase 1 — Stabilization & security

- Perbaiki logout route/controller.
- Rapikan route naming dan HTTP methods.
- Perbaiki akses detail publik vs internal.
- Tambahkan validasi CRUD.
- Amankan upload gambar.
- Tambahkan `findOrFail`/route model binding.
- Implementasikan authorization yang sesuai.
- Perbaiki relasi kategori dan `category_id`.
- Tambahkan feature tests untuk alur kritis.

### Phase 2 — UI foundation

- Buat layout Blade utama.
- Buat design tokens/aturan warna, typography, spacing, radius, dan shadow.
- Rapikan navbar, footer, button, form field, alert, badge, dan modal.
- Pastikan responsive dan accessibility dasar.
- Gunakan satu pola notifikasi yang konsisten.

### Phase 3 — Public catalog

- Bangun landing page yang layak.
- Bangun katalog dengan search, category filter, sorting, dan pagination.
- Bangun detail obat yang informatif.
- Tambahkan status kedaluwarsa secara akurat.
- Tambahkan loading/empty/error states sesuai pendekatan server-rendered yang dipakai.

### Phase 4 — Admin experience

- Bangun dashboard pengelolaan.
- CRUD obat dan kategori dengan form yang proper.
- Tambahkan tabel responsif, filter, pagination, preview image, dan konfirmasi hapus.
- Tambahkan ringkasan yang bersumber dari database nyata.

### Phase 5 — Advanced capabilities

- Evaluasi kebutuhan stok/inventaris, supplier, transaksi, laporan, audit log, dan multi-role.
- Buat desain data dan alur bisnis sebelum implementasi.
- Implementasikan hanya fitur yang disetujui dan dibutuhkan.

## 9. Batasan dan keputusan penting

- Jangan mengganti Laravel, Blade, Tailwind, atau Vite hanya karena framework lain sedang populer.
- Jangan memigrasikan ke React, Vue, Inertia, Livewire, atau API-first architecture tanpa alasan yang terukur dan persetujuan.
- Jangan mengarang data apotek, alamat, jam buka, nomor telepon, harga, stok, diskon, sertifikasi, atau informasi obat.
- Jangan memberikan diagnosis atau rekomendasi dosis/penggunaan obat. Website ini adalah aplikasi katalog/pengelolaan, bukan pengganti tenaga kesehatan.
- Jangan menganggap semua akun terdaftar boleh mengelola katalog.
- Jangan mengimplementasikan cart/checkout/pembayaran sebelum requirement bisnis ditetapkan.
- Jangan menghapus model, migration, asset, atau route yang terlihat tidak digunakan sebelum menelusuri referensinya.
- Jangan menyatakan aplikasi production-ready hanya karena tampilannya sudah bagus.

## 10. Instruksi awal saat memulai sesi

Saat pertama kali membuka proyek, lakukan audit read-only terlebih dahulu. Tampilkan:

1. Ringkasan arsitektur aktual.
2. Daftar bug/risiko yang masih ada berdasarkan pemeriksaan terbaru.
3. Prioritas perbaikan P0/P1/P2.
4. Rencana implementasi singkat dan file yang terdampak.
5. Dependency atau environment yang perlu disiapkan.

Setelah audit, mulai dari Phase 1. Jangan langsung melakukan rewrite seluruh aplikasi dalam satu langkah. Jaga setiap perubahan kecil, teruji, dan dapat ditinjau.
