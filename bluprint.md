# Blueprint Final: Sistem Analisis Teknikal Saham Indonesia dengan PHP 8.3 dan CodeIgniter 4

---

## 1. Pendahuluan

### 1.1 Tujuan Proyek
Membangun sistem analisis teknikal otomatis untuk saham-saham yang terdaftar di Bursa Efek Indonesia (BEI) menggunakan **CodeIgniter 4** dan **PHP 8.3**. Sistem ini akan mengolah data harga historis dari Yahoo Finance melalui library **scheb/yahoo-finance-api** (versi 5.x) dan menghasilkan rekomendasi **BUY**, **SELL**, atau **HOLD** berdasarkan metode **confluence** — yaitu menggabungkan beberapa indikator teknikal dari kategori berbeda untuk meningkatkan akurasi dan meminimalkan sinyal palsu.

### 1.2 Prinsip Dasar yang Dianut
- **Tidak menggunakan AI/ML**: Semua logika berbasis rumus matematis statistik yang telah teruji di dunia trading profesional.
- **Confluence System**: Minimal 2 dari 3 kategori indikator harus sepakat sebelum sinyal dikeluarkan.
- **Data Gratis dan Stabil**: Menggunakan `scheb/yahoo-finance-api` yang merupakan klien PHP non‑official namun telah terbukti handal dengan fitur retry dan cache context.
- **Transparansi Penuh**: Setiap rekomendasi disertai rincian skor dari setiap indikator beserta nilai aktualnya.

---

## 2. Arsitektur Sistem

### 2.1 Stack Teknologi (FIX)

| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| **Backend Framework** | CodeIgniter 4 | PHP framework dengan arsitektur MVC |
| **PHP Version** | 8.3 (minimal) | Mendukung typed properties dan performance improvement |
| **Data Source** | `scheb/yahoo-finance-api` versi 5.x | Library PHP resmi untuk mengakses data Yahoo Finance secara stabil |
| **Library Indikator** | `kenshodigital/chart` + implementasi manual untuk RSI, Stochastic, MACD, Bollinger Bands | Pure PHP, mendukung PHP 8.3, tanpa ekstensi PECL |
| **Caching** | File Cache (built-in CI4) untuk data historis + PSR‑6 cache (Symfony Cache) untuk context API | Mengurangi request API, mencegah rate limiting |
| **Database** | MySQL 8.0+ | Menyimpan hasil analisis dan log sinyal (opsional) |
| **Frontend** | Bootstrap 5 + ApexCharts | Chart interaktif dan tampilan responsive |

### 2.2 Struktur Folder (FIX)

```
ci4-stock-analyst/
├── app/
│   ├── Config/
│   │   ├── Database.php          # Konfigurasi database
│   │   ├── Routes.php            # Routing aplikasi
│   │   ├── Cache.php             # Konfigurasi file cache CI4
│   │   └── YahooFinance.php      # Konfigurasi khusus untuk API client
│   ├── Controllers/
│   │   ├── Stock.php             # Controller detail satu saham
│   │   └── Dashboard.php         # Controller multi-saham monitoring
│   ├── Models/
│   │   ├── StockModel.php        # Interaksi dengan Yahoo Finance API (via wrapper)
│   │   └── AnalysisModel.php     # CRUD hasil analisis ke database
│   ├── Libraries/
│   │   ├── YahooFinanceClient.php # Wrapper untuk scheb/yahoo-finance-api
│   │   ├── Technical.php         # Wrapper untuk kenshodigital/chart dan manual indicators
│   │   └── ConfluenceEngine.php  # Scoring system multi-indikator
│   └── Views/
│       └── stock/
│           ├── detail.php        # Tampilan detail satu saham
│           └── dashboard.php     # Tampilan monitoring
├── writable/
│   └── cache/                    # Tempat penyimpanan cache file CI4
├── .env                          # Environment variables (konfigurasi API, database)
└── composer.json                 # Daftar dependency
```

---

## 3. Data Source: scheb/yahoo-finance-api

### 3.1 Mengapa Library Ini Dipilih
- **Versi 5.x** didukung secara aktif dan kompatibel dengan PHP 8.1 – 8.3.
- Menyediakan metode lengkap: `getHistoricalQuoteData()`, `getQuote()`, `getQuotes()` untuk multi‑saham.
- **Built‑in retry mechanism** (dapat dikonfigurasi jumlah percobaan dan jeda) untuk menangani kegagalan sementara.
- **Dukungan cache context PSR‑6** yang menyimpan cookie dan crumb, sehingga mengurangi overhead request.
- **Dukungan curl‑impersonate** untuk meniru browser nyata dan menghindari blokir.
- Tidak memerlukan API key; semua akses melalui endpoint internal Yahoo Finance yang masih tersedia.

### 3.2 Konfigurasi yang Direkomendasikan
- **Retry attempts**: 3 kali dengan jeda 1000 ms.
- **Cache context**: Gunakan PSR‑6 cache (misal Symfony Cache) dengan TTL 3600 detik.
- **User‑Agent**: Atur melalui Guzzle client options untuk meniru browser modern (misal Chrome 120).
- **Timeout**: Atur timeout request 30 detik untuk menghindari hanging.

### 3.3 Data yang Dapat Diambil per Saham
- **Historical Data** (daily): open, high, low, close, volume, adjusted close.
- **Quote Real‑time**: harga terbaru, perubahan persen, volume hari ini, bid, ask, dll.
- **Dividend & Split History**: untuk analisis fundamental tambahan (opsional).

### 3.4 Penanganan Simbol Saham Indonesia
Semua saham Indonesia harus menggunakan suffix **.JK** (contoh: `BBCA.JK`, `ASII.JK`). Library ini mendukung simbol dengan suffix apapun.

---

## 4. Metode Analisis: Confluence System (3 Pilar)

### 4.1 Konsep Dasar
Confluence adalah kondisi di mana dua atau lebih sinyal teknikal independen dari kategori berbeda mengarah ke kesimpulan yang sama. Pendekatan ini mengurangi false signal karena setiap indikator memiliki kelemahan bawaan yang dapat saling meniadakan.

**Aturan Emas**:
- **BUY** dikeluarkan jika minimal 2 dari 3 pilar memberikan sinyal bullish.
- **SELL** dikeluarkan jika minimal 2 dari 3 pilar memberikan sinyal bearish.
- **HOLD** dikeluarkan jika hanya 0–1 pilar yang sepakat.

### 4.2 Tiga Pilar Analisis (FIX)

| Pilar | Kategori | Indikator | Fungsi |
|-------|----------|-----------|--------|
| **Pilar 1** | Trend & Momentum | EMA 20 & 50 + MACD | Menentukan arah pasar dan kekuatan tren |
| **Pilar 2** | Overbought/Oversold | RSI (14) + Stochastic (14,3) | Mengukur kondisi jenuh beli/jual |
| **Pilar 3** | Volume & Volatilitas | Volume Spike + Bollinger Bands (20,2) | Konfirmasi akumulasi dan level support/resistance dinamis |

Setiap pilar memiliki bobot maksimum 50 poin (25 per indikator). Penjumlahan skor bullish dan bearish per pilar menentukan status pilar.

### 4.3 Detail Indikator dan Parameter

#### Pilar 1: Trend & Momentum
- **EMA 20 dan EMA 50**: Exponential Moving Average dengan period 20 dan 50.
  - Bullish: EMA20 > EMA50 (tren naik).
  - Bearish: EMA20 < EMA50 (tren turun).
- **MACD**: Fast=12, Slow=26, Signal=9.
  - Bullish: MACD line memotong signal line dari bawah (crossover up).
  - Bearish: MACD line memotong signal line dari atas (crossover down).

#### Pilar 2: Overbought/Oversold
- **RSI 14**:
  - Bullish: RSI < 30 (oversold).
  - Bearish: RSI > 70 (overbought).
- **Stochastic Oscillator** (%K period 14, %D smoothing 3):
  - Bullish: %K < 20 dan %K memotong %D dari bawah.
  - Bearish: %K > 80 dan %K memotong %D dari atas.

#### Pilar 3: Volume & Volatilitas
- **Volume Spike**:
  - Bullish: Volume hari ini > 1.5 × rata‑rata volume 20 hari terakhir **dan** harga naik.
  - Bearish: Volume hari ini > 1.5 × rata‑rata volume 20 hari **dan** harga turun.
- **Bollinger Bands** (period 20, deviasi 2):
  - Bullish: Harga ≤ lower band (oversold extreme).
  - Bearish: Harga ≥ upper band (overbought extreme).

### 4.4 Mekanisme Scoring dan Konfirmasi

**Langkah‑langkah evaluasi**:
1. Hitung nilai terkini untuk setiap indikator.
2. Beri poin +25 pada kolom BUY jika kondisi bullish terpenuhi, atau +25 pada kolom SELL jika kondisi bearish terpenuhi. Jika tidak memenuhi, poin 0.
3. Jumlahkan poin per pilar (maks 50 per pilar). Skor pilar = total poin BUY – total poin SELL.
4. Status pilar:
   - Bullish jika skor pilar ≥ 25.
   - Bearish jika skor pilar ≤ –25.
   - Netral jika di antara –25 dan 25.
5. Hitung jumlah pilar bullish dan bearish.
6. Rekomendasi final:
   - BUY jika jumlah pilar bullish ≥ 2.
   - SELL jika jumlah pilar bearish ≥ 2.
   - HOLD jika lainnya.

**Tingkat Keyakinan (Confidence)** ditentukan dari total skor BUY atau SELL absolut:
- ≥ 100 : STRONG
- 75–99 : MODERATE
- 50–74 : WEAK
- < 50  : (tidak digunakan karena hanya muncul pada HOLD)

---

## 5. Template Output Sinyal

Sistem harus menampilkan output yang terstruktur dan informatif. Berikut adalah format baku untuk halaman detail satu saham:

```
========================================
ANALISIS TEKNIKAL: [SYMBOL]
Tanggal: [YYYY-MM-DD HH:MM WIB]
Harga Terkini: Rp [harga]
========================================

REKOMENDASI: [BUY/SELL/HOLD] (CONFIDENCE: [STRONG/MODERATE/WEAK])
----------------------------------------
Alasan: [Jumlah pilar bullish/bearish, ringkasan konfirmasi]

DETAIL SCORING:
┌─────────────────────────────────────────────────────────┐
│ PILAR 1: TREND & MOMENTUM      │ STATUS: [BULLISH/BEARISH/NETRAL] │
├─────────────────────────────────────────────────────────┤
│ • EMA20 ([nilai]) [> / <] EMA50 ([nilai])   │ [poin +25/-25/0] │
│ • MACD: [crossover up/down/tidak]           │ [poin +25/-25/0] │
├─────────────────────────────────────────────────────────┤
│ PILAR 2: OVERBOUGHT/OVERSOLD   │ STATUS: [BULLISH/BEARISH/NETRAL] │
├─────────────────────────────────────────────────────────┤
│ • RSI (14): [nilai] ([oversold/overbought/netral]) │ [poin +25/-25/0] │
│ • Stochastic %K ([nilai]) / %D ([nilai])    │ [poin +25/-25/0] │
├─────────────────────────────────────────────────────────┤
│ PILAR 3: VOLUME & VOLATILITAS  │ STATUS: [BULLISH/BEARISH/NETRAL] │
├─────────────────────────────────────────────────────────┤
│ • Volume: [nilai] ([x] × avg) + harga [naik/turun] │ [poin +25/-25/0] │
│ • Bollinger: Harga di [upper/lower/middle] band │ [poin +25/-25/0] │
└─────────────────────────────────────────────────────────┘

INDIKATOR LENGKAP:
• EMA20: [nilai] | EMA50: [nilai]
• RSI (14): [nilai]
• Stochastic %K: [nilai] | %D: [nilai]
• MACD: [nilai] | Signal: [nilai] | Histogram: [nilai]
• Bollinger Upper: [nilai] | Middle: [nilai] | Lower: [nilai]
• Volume: [nilai] ([x] × rata‑rata 20 hari)

RISK MANAGEMENT (referensi):
• Entry Range: [rentang harga]
• Stop Loss: [nilai] (dasar: lower band / support terdekat)
• Take Profit 1: [nilai] (upper band)
• Take Profit 2: [nilai] (resistance berikutnya)
• Risk/Reward: [rasio]

========================================
Disclaimer: Analisis ini bersifat edukatif.
Keputusan investasi sepenuhnya adalah tanggung jawab Anda.
========================================
```

Untuk halaman dashboard multi‑saham, format tabel ringkas dengan kolom: Kode, Harga, Perubahan %, Rekomendasi, Confidence, dan ringkasan pilar.

---

## 6. Daftar Saham Indonesia yang Didukung

Semua saham yang terdaftar di BEI dapat diakses dengan suffix **.JK**. Berikut adalah 15 saham large cap (LQ45) yang direkomendasikan untuk dimonitor:

| Kode | Nama Perusahaan | Sektor |
|------|-----------------|--------|
| BBCA.JK | Bank Central Asia Tbk | Financial |
| BBRI.JK | Bank Rakyat Indonesia Tbk | Financial |
| BMRI.JK | Bank Mandiri Tbk | Financial |
| BBNI.JK | Bank Negara Indonesia Tbk | Financial |
| TLKM.JK | Telkom Indonesia Tbk | Telecommunication |
| ASII.JK | Astra International Tbk | Automotive |
| ADRO.JK | Adaro Energy Tbk | Energy |
| ICBP.JK | Indofood CBP Sukses Makmur Tbk | Consumer |
| UNVR.JK | Unilever Indonesia Tbk | Consumer |
| INDF.JK | Indofood Sukses Makmur Tbk | Consumer |
| CPIN.JK | Charoen Pokphand Indonesia Tbk | Consumer |
| PGAS.JK | Perusahaan Gas Negara Tbk | Energy |
| SMGR.JK | Semen Indonesia Tbk | Basic Industry |
| ANTM.JK | Aneka Tambang Tbk | Mining |
| INCO.JK | Vale Indonesia Tbk | Mining |

---

## 7. Implementasi Teknis (Tanpa Kode)

### 7.1 Integrasi scheb/yahoo-finance-api
- Buat wrapper library `YahooFinanceClient` yang menginisialisasi client dengan konfigurasi retry, cache context, dan user‑agent.
- Simpan konfigurasi di file `.env` (retry attempts, retry delay, cache TTL).
- Gunakan dependency injection untuk menyuntikkan wrapper ke dalam model.

### 7.2 Caching Strategy
- **Level 1 – File Cache CI4**: Untuk data historis (6 bulan) dan hasil analisis, simpan dengan TTL 3600 detik.
- **Level 2 – PSR‑6 Cache (Symfony Cache)**: Digunakan oleh library untuk menyimpan context (cookie & crumb) dengan TTL 3600 detik.
- **Multi‑quote caching**: Untuk dashboard, simpan hasil `getQuotes()` selama 600 detik (10 menit) karena data real‑time lebih cepat berubah.

### 7.3 Perhitungan Indikator
- Gunakan `kenshodigital/chart` untuk SMA, EMA, dan ADX (jika diperlukan).
- Implementasikan fungsi manual untuk RSI, Stochastic, MACD, dan Bollinger Bands di `Technical` library. Semua fungsi harus menerima array harga dan mengembalikan array nilai per periode.
- Pastikan panjang data mencukupi (minimal 100 bar) sebelum perhitungan.

### 7.4 ConfluenceEngine
- Kelas ini akan menerima array harga close, high, low, volume.
- Memanggil fungsi indikator dari `Technical`, lalu mengevaluasi kondisi bullish/bearish.
- Mengembalikan array hasil yang berisi rekomendasi, confidence, skor per pilar, dan nilai indikator aktual.

### 7.5 Controller dan View
- `StockController::index($symbol)`: mengambil data historis dan quote, memanggil ConfluenceEngine, menyajikan ke view detail.
- `DashboardController::index()`: mengambil daftar simbol (misal dari database atau array fixed), memanggil `getQuotes()` dan analisis singkat (tanpa historical mendalam) untuk setiap simbol, menampilkan tabel ringkas.
- View menggunakan Bootstrap 5 untuk layout, dan ApexCharts untuk menampilkan candlestick dengan overlay indikator (EMA, Bollinger) dan sub‑chart RSI, MACD.

### 7.6 Error Handling dan Monitoring
- Tangani kegagalan API dengan menampilkan data dari cache terakhir dan notifikasi.
- Catat error ke log file CI4.
- Siapkan mekanisme fallback: jika library gagal mengambil data, gunakan data cache yang masih ada (meskipun expired) dengan tanda peringatan.

---

## 8. Roadmap Implementasi (FIX)

| Fase | Durasi | Output |
|------|--------|--------|
| **Fase 1: Setup & Foundation** | 2 hari | CI4 terinstall, `scheb/yahoo-finance-api` terintegrasi, caching PSR‑6 dan file cache berfungsi |
| **Fase 2: Library Indikator** | 3 hari | Implementasi semua indikator (EMA, MACD, RSI, Stochastic, Bollinger, Volume Spike) dan unit testing |
| **Fase 3: Confluence Engine** | 2 hari | Logika scoring, perhitungan rekomendasi, pengujian dengan data historis |
| **Fase 4: Model & Wrapper** | 2 hari | YahooFinanceClient, StockModel, AnalysisModel selesai |
| **Fase 5: Controller & View** | 2 hari | StockController, DashboardController, template detail dan dashboard |
| **Fase 6: Chart & Interaksi** | 2 hari | Integrasi ApexCharts, menampilkan candlestick dan indikator overlay |
| **Fase 7: Testing & Validasi** | 2 hari | Uji coba dengan 10 saham LQ45, validasi sinyal dengan data historis |
| **Fase 8: Deployment** | 1 hari | Konfigurasi production, cron job untuk update data berkala |

**Total estimasi:** 16 hari (3 minggu) untuk versi 1.0 yang stabil.

---

## 9. Kesimpulan dan Catatan Akhir

### 9.1 Keunggulan Blueprint Ini
- **Data source stabil**: `scheb/yahoo-finance-api` versi 5 memberikan jaminan pemeliharaan dan fitur modern.
- **Metode analisis teruji**: Confluence system dengan 3 pilar indikator yang saling independen mengurangi false signal.
- **Kode bersih dan terstruktur**: Pemisahan yang jelas antara wrapper API, perhitungan indikator, dan engine scoring.
- **Performa optimal**: Caching bertingkat (data historis, context API, multi‑quote) mengurangi beban dan risiko blokir.

### 9.2 Batasan yang Harus Diketahui
- Data Yahoo Finance bersifat non‑official dan dapat berubah sewaktu‑waktu; diperlukan pemantauan.
- Analisis hanya berdasarkan data teknikal; faktor fundamental dan sentimen pasar tidak dipertimbangkan.
- Rekomendasi bersifat edukatif; tidak menjamin keuntungan.

### 9.3 Pengembangan Masa Depan (Post Version 1.0)
- Tambahkan indikator ADX untuk mengukur kekuatan tren.
- Implementasikan backtesting engine untuk mengukur akurasi strategi.
- Integrasikan notifikasi real‑time (email, Telegram) ketika sinyal kuat muncul.
- Sediakan API endpoint untuk mengakses hasil analisis secara programatik.

---

**Dokumen ini adalah blueprint final yang harus diikuti oleh agent AI untuk membangun sistem analisis saham dari nol. Semua komponen telah ditetapkan secara fixed, tanpa opsi. Gunakan blueprint ini sebagai panduan tunggal dalam implementasi.**