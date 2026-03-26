Berikut adalah **7 phase implementasi** backend dan frontend secara lengkap dan detail, sesuai dengan blueprint yang telah ditetapkan. Setiap phase menjelaskan langkah-langkah konkret yang harus dilakukan, komponen yang dibuat, serta kriteria keberhasilan. Semua penjelasan dalam bentuk **full teks tanpa kode**, namun mencakup detail teknis yang cukup untuk diimplementasikan oleh agent AI.

---

## Phase 1: Persiapan dan Fondasi (Setup)

**Tujuan:** Membangun fondasi proyek CodeIgniter 4, mengintegrasikan library data source, dan menyiapkan struktur folder serta konfigurasi dasar.

### Langkah-langkah:

1. **Instalasi CI4 dan Dependensi**  
   - Jalankan perintah `composer create-project codeigniter4/appstarter .` di folder proyek.  
   - Instal library `scheb/yahoo-finance-api:^5`, `symfony/cache`, `kenshodigital/chart`, dan (opsional) `geoffroy-pradier/php-trader` melalui Composer.  
   - Pastikan PHP 8.3 berjalan dengan ekstensi curl, json, mbstring, intl.

2. **Struktur Folder**  
   - Buat folder `app/Libraries/` untuk menyimpan kelas wrapper dan engine.  
   - Buat folder `writable/cache/` untuk file cache CI4.  
   - Pastikan folder `writable/` memiliki izin tulis.

3. **Konfigurasi Environment**  
   - Salin `env` menjadi `.env` dan atur `app.baseURL` sesuai lingkungan pengembangan.  
   - Tambahkan baris konfigurasi untuk Yahoo Finance di `.env`:
     - `YAHOO_CACHE_TTL = 3600`
     - `YAHOO_RETRY_ATTEMPTS = 3`
     - `YAHOO_RETRY_DELAY_MS = 1000`
   - Jika menggunakan database, konfigurasi koneksi MySQL di `.env`.

4. **Konfigurasi Caching CI4**  
   - Di `app/Config/Cache.php`, pastikan handler default adalah `file` dan path storage mengarah ke `WRITEPATH . 'cache/'`.

5. **Verifikasi**  
   - Jalankan `php spark serve` dan akses `http://localhost:8080` untuk melihat halaman welcome CI4.  
   - Cek bahwa folder `writable/cache` terisi dengan file cache setelah aplikasi dijalankan.

---

## Phase 2: Implementasi Data Source & Caching

**Tujuan:** Membangun wrapper untuk `scheb/yahoo-finance-api` dengan mekanisme caching bertingkat (file cache CI4 untuk data historis, PSR-6 cache untuk context API).

### Langkah-langkah:

1. **Buat Wrapper Library `YahooFinanceClient`**  
   - Letakkan di `app/Libraries/YahooFinanceClient.php`.  
   - Kelas ini akan menginisialisasi client `scheb\YahooFinanceApi\ApiClient` dengan konfigurasi retry dan cache context PSR-6 (menggunakan `Symfony\Component\Cache\Adapter\FilesystemAdapter`).  
   - Sediakan metode publik:  
     - `getHistoricalData(string $symbol, string $interval = '1d', string $range = '6mo')` – mengambil data historis dengan cache CI4.  
     - `getQuote(string $symbol)` – mengambil quote real-time.  
     - `getQuotes(array $symbols)` – mengambil multiple quotes untuk dashboard.  
   - Di dalam metode `getHistoricalData`, lakukan pengecekan cache CI4 terlebih dahulu menggunakan `\Config\Services::cache()`. Jika ada, kembalikan data. Jika tidak, panggil API melalui library, simpan ke cache CI4, lalu kembalikan.

2. **Implementasi Cache Context PSR-6**  
   - Di konstruktor wrapper, buat instance `FilesystemAdapter` dengan direktori `writable/cache/psr6` (buat folder tersebut).  
   - Gunakan adapter tersebut sebagai cache PSR-6 saat membuat client melalui `ApiClientFactory::createApiClient()` dengan parameter `cache` dan `cacheTtl`.

3. **Penanganan Error dan Retry**  
   - Manfaatkan parameter `retries` dan `retryDelay` pada factory untuk menangani kegagalan API sementara.  
   - Jika API gagal setelah retry, kembalikan data dari cache CI4 yang mungkin sudah expired tetapi tetap valid dengan menampilkan peringatan.

4. **Verifikasi**  
   - Buat controller sementara untuk memanggil wrapper dan mencetak data. Pastikan data historis BBCA.JK berhasil diambil dan di-cache.  
   - Periksa bahwa cache file CI4 terisi dan context PSR-6 menyimpan file di `writable/cache/psr6`.

---

## Phase 3: Implementasi Library Indikator Teknikal

**Tujuan:** Membuat library yang dapat menghitung indikator teknikal (EMA, MACD, RSI, Stochastic, Bollinger Bands, Volume Spike) secara akurat.

### Langkah-langkah:

1. **Buat Library `Technical`**  
   - Letakkan di `app/Libraries/Technical.php`.  
   - Kelas ini berisi metode statis untuk masing-masing indikator. Setiap metode menerima array data (harga close, high, low, volume) dan mengembalikan array nilai indikator sesuai periode.

2. **Implementasi Indikator**  
   - **EMA 20 & 50**: Gunakan formula EMA standar. Pastikan data minimal 50 periode.  
   - **MACD**: Hitung EMA12 dan EMA26 dari close, kemudian hitung MACD line (EMA12 – EMA26), signal line (EMA9 dari MACD line), dan histogram.  
   - **RSI**: Hitung rata-rata gain dan loss selama 14 periode. Formula: `RSI = 100 – (100 / (1 + RS))`.  
   - **Stochastic**: Hitung %K = (close – lowest low) / (highest high – lowest low) * 100 selama 14 periode, lalu %D = SMA3 dari %K.  
   - **Bollinger Bands**: Hitung SMA20, standar deviasi, upper = SMA20 + (2 * std), lower = SMA20 – (2 * std).  
   - **Volume Spike**: Hitung rata-rata volume 20 hari terakhir, bandingkan dengan volume hari ini. Kembalikan rasio.

3. **Validasi dan Unit Testing**  
   - Buat data dummy dengan pola yang diketahui (misal tren naik, oversold) dan pastikan indikator menghasilkan nilai yang sesuai.  
   - Gunakan library `kenshodigital/chart` sebagai pembanding jika diperlukan.

4. **Verifikasi**  
   - Panggil metode dari controller sementara dengan data historis dari Yahoo Finance, lalu cetak nilai terkini. Pastikan semua indikator terhitung tanpa error.

---

## Phase 4: Implementasi Confluence Engine

**Tujuan:** Membangun engine scoring yang menggabungkan hasil dari library indikator dan menghasilkan rekomendasi BUY/SELL/HOLD beserta confidence.

### Langkah-langkah:

1. **Buat Library `ConfluenceEngine`**  
   - Letakkan di `app/Libraries/ConfluenceEngine.php`.  
   - Kelas ini menerima array harga (close, high, low, volume) dan memiliki metode `analyze()` yang mengembalikan array hasil.

2. **Evaluasi Setiap Pilar**  
   - Panggil metode dari `Technical` untuk mendapatkan nilai terkini indikator.  
   - Untuk setiap indikator, tentukan apakah kondisi bullish atau bearish terpenuhi, beri poin +25 pada kolom BUY atau SELL.  
   - Kumpulkan poin per pilar (Pilar 1: EMA20/50 + MACD; Pilar 2: RSI + Stochastic; Pilar 3: Volume Spike + Bollinger).  
   - Hitung skor pilar = total poin BUY – total poin SELL. Status pilar ditentukan berdasarkan threshold ±25.

3. **Rekomendasi Final**  
   - Hitung jumlah pilar bullish dan bearish.  
   - Tentukan rekomendasi: BUY jika bullish ≥ 2, SELL jika bearish ≥ 2, HOLD lainnya.  
   - Confidence: ambil total skor BUY atau SELL absolut (mana yang lebih besar) dan petakan: ≥100 STRONG, 75–99 MODERATE, 50–74 WEAK.

4. **Kembalikan Data Lengkap**  
   - Hasil analisis mencakup rekomendasi, confidence, skor per pilar, detail setiap indikator (nilai aktual, poin), dan status per pilar.

5. **Verifikasi**  
   - Uji dengan data historis yang sudah diketahui sinyalnya (misal oversold + golden cross) dan pastikan engine menghasilkan BUY yang tepat.  
   - Uji dengan data acak dan pastikan tidak ada exception.

---

## Phase 5: Implementasi Backend Controller & Model

**Tujuan:** Membangun model untuk mengakses data saham dan controller untuk melayani request dari frontend.

### Langkah-langkah:

1. **Buat Model `StockModel`**  
   - Letakkan di `app/Models/StockModel.php`.  
   - Model ini bergantung pada library `YahooFinanceClient`.  
   - Sediakan metode:  
     - `getHistoricalData(string $symbol, int $days = 180)`: mengambil data historis dalam jumlah hari tertentu.  
     - `getQuote(string $symbol)`: mengambil quote real-time.  
     - `getQuotes(array $symbols)`: mengambil multiple quotes.

2. **Buat Controller `Stock`**  
   - Letakkan di `app/Controllers/Stock.php`.  
   - Metode `index($symbol)` akan:  
     - Memanggil `StockModel` untuk mengambil data historis dan quote.  
     - Memanggil `ConfluenceEngine` untuk analisis.  
     - Menggabungkan data historis, analisis, dan quote ke dalam array untuk dikirim ke view.  
   - Gunakan caching untuk hasil analisis (misal 1 jam) agar tidak perlu menghitung ulang setiap request.

3. **Buat Controller `Dashboard`**  
   - Letakkan di `app/Controllers/Dashboard.php`.  
   - Metode `index()` akan:  
     - Menentukan daftar simbol (bisa diambil dari database atau array statis 15 saham LQ45).  
     - Memanggil `StockModel::getQuotes()` untuk mendapatkan data real-time semua saham.  
     - Untuk setiap saham, lakukan analisis cepat tanpa perlu data historis panjang? Sesuai blueprint, dashboard bisa menggunakan analisis singkat (misal hanya EMA dan RSI).  
     - Menyusun data ke dalam array untuk view dashboard.

4. **Konfigurasi Routes**  
   - Di `app/Config/Routes.php`, tambahkan:  
     - `$routes->get('stock/(:any)', 'Stock::index/$1');`  
     - `$routes->get('stock', 'Stock::index');`  
     - `$routes->get('dashboard', 'Dashboard::index');`

5. **Verifikasi**  
   - Akses `/stock/BBCA.JK` dan pastikan data historis, analisis, dan quote berhasil diambil tanpa error.  
   - Akses `/dashboard` dan pastikan daftar saham muncul dengan quote dan rekomendasi dasar.

---

## Phase 6: Implementasi Frontend (View & Chart)

**Tujuan:** Membangun tampilan pengguna yang informatif, responsif, dan interaktif menggunakan Bootstrap 5 dan ApexCharts.

### Langkah-langkah:

1. **Buat Template Base**  
   - Buat file `app/Views/layouts/main.php` yang berisi struktur HTML dasar dengan head (Bootstrap, ApexCharts) dan footer.  
   - Gunakan `<?= $this->renderSection('content') ?>` untuk konten dinamis.

2. **Buat View Detail Saham**  
   - File: `app/Views/stock/detail.php`.  
   - Ekstensi layout utama.  
   - Tampilkan rekomendasi dalam card dengan warna latar sesuai (hijau untuk BUY, merah untuk SELL, kuning untuk HOLD).  
   - Tampilkan tabel skor per pilar dengan status dan poin.  
   - Tampilkan tabel nilai indikator lengkap.  
   - Tampilkan grafik candlestick dengan overlay EMA20, EMA50, Bollinger Bands menggunakan ApexCharts.  
   - Tampilkan sub-chart RSI dan MACD di bawah.  
   - Sertakan tabel data historis (terakhir 10 hari) untuk referensi.

3. **Buat View Dashboard**  
   - File: `app/Views/stock/dashboard.php`.  
   - Tampilkan tabel responsif dengan kolom: Kode, Harga, Perubahan (%), Rekomendasi, Confidence.  
   - Beri warna baris sesuai rekomendasi.  
   - Tambahkan filter atau search (opsional).  
   - Tampilkan ringkasan statistik: jumlah BUY, SELL, HOLD.

4. **Integrasi Data dari Controller**  
   - Di controller, siapkan data yang sesuai dengan kebutuhan view.  
   - Untuk chart, kirim data historis dalam format JSON agar dapat diproses ApexCharts.

5. **Verifikasi**  
   - Buka halaman detail saham dan pastikan semua elemen muncul, chart tergambar, dan data sesuai.  
   - Buka dashboard dan pastikan tabel menampilkan data yang benar.

---

## Phase 7: Integrasi, Testing, dan Deployment

**Tujuan:** Memastikan seluruh komponen bekerja bersama, melakukan pengujian menyeluruh, dan menyiapkan lingkungan produksi.

### Langkah-langkah:

1. **Integrasi End-to-End**  
   - Pastikan alur dari request user hingga tampilan chart berjalan tanpa error.  
   - Uji caching: muat halaman detail, ubah data, refresh, pastikan data tidak berubah sebelum TTL berakhir.  
   - Uji error handling: matikan internet sementara, pastikan aplikasi menampilkan data cache dengan peringatan.

2. **Pengujian Akurasi Analisis**  
   - Pilih 10 saham dengan kondisi historis yang diketahui (misal golden cross pada tanggal tertentu). Jalankan analisis dengan data historis dan bandingkan sinyal yang dihasilkan dengan kejadian nyata. Catat win rate sebagai dokumentasi.

3. **Optimasi Performa**  
   - Pastikan caching berjalan efisien. Gunakan cache untuk hasil analisis di controller.  
   - Batasi jumlah request API dengan menggunakan multi-quote untuk dashboard.  
   - Pastikan file cache tidak membengkak; set TTL yang wajar.

4. **Konfigurasi Production**  
   - Ubah `.env` untuk lingkungan production: `CI_ENVIRONMENT = production`, sesuaikan `app.baseURL`.  
   - Nonaktifkan debugging.  
   - Atur izin folder `writable/` agar hanya dapat ditulis oleh web server.

5. **Deployment**  
   - Upload kode ke server.  
   - Jalankan `composer install --no-dev` untuk mengoptimalkan.  
   - Pastikan ekstensi PHP yang diperlukan aktif.  
   - Set cron job (jika diperlukan) untuk memperbarui data cache secara berkala (opsional).

6. **Verifikasi Final**  
   - Akses aplikasi dari browser di lingkungan production, pastikan semua fitur berjalan.  
   - Uji kembali error handling dan caching.

---

Dengan menyelesaikan ketujuh phase ini, Anda akan memiliki sistem analisis teknikal saham berbasis CI4 yang stabil, akurat, dan siap digunakan.