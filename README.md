# Stock Analyst CI4 (Indonesia) — Confluence System (3 Pilar)

Aplikasi CodeIgniter 4 + PHP 8.3 untuk analisis teknikal saham Indonesia (suffix `.JK`) menggunakan metode **Confluence System** dengan 3 candle indikator:

- **P 1 (Trend & Momentum):** EMA20/EMA50 + MACD(12,26,9)
- **P 2 (Overbought/Oversold):** RSI(14) + Stochastic(14,3)
- **P 3 (Volume & Volatilitas):** Volume Spike + Bollinger Bands(20,2)

Output: rekomendasi **BUY / SELL / HOLD** + **confidence (STRONG/MODERATE/WEAK)** + skor per pilar.


## Disclaimer
Analisis ini bersifat edukatif dan bukan ajakan membeli/menjual. Keputusan investasi sepenuhnya tanggung jawab pengguna.

## Requirements
- PHP **8.3+**
- Extensions: `curl`, `json`, `mbstring`, `intl`
- Composer

## Instalasi (Local)

```bash
composer install
```

Copy `env` menjadi `.env`, lalu set minimal:

```dotenv
app.baseURL = 'http://localhost/ci4-stock-mangido/public/'
```

Jalankan server dev:

```bash
php spark serve
```

## Routes / Halaman
- Dashboard multi-saham: `GET /dashboard`
- Detail saham: `GET /stock/{SYMBOL}` contoh: `/stock/ACES.JK`

Catatan: jika symbol tidak memakai `.JK`, controller akan menambahkan otomatis.

## Konsep Output (Singkat)
- `Chg%`: perubahan persen harga (dari Yahoo Finance `regularMarketChangePercent`)
- `Reco`: rekomendasi final dari confluence 3 pilar
- `P1/P2/P3`: status masing-masing pilar (`BULLISH/BEARISH/NETRAL`) — hover (tooltip) menampilkan detail sinyal indikator

## Caching (Sesuai Blueprint)
- CI4 File Cache: data historis & hasil analisis
- PSR-6 Symfony Cache: context Yahoo (cookie/crumb) di `writable/cache/psr6`

Variabel `.env` yang dipakai (default sudah disediakan):
- `YAHOO_CACHE_TTL` (default 3600)
- `YAHOO_QUOTES_CACHE_TTL` (default 600)
- `YAHOO_STALE_CACHE_TTL` (default 86400)
- `YAHOO_RETRY_ATTEMPTS` (default 3)
- `YAHOO_RETRY_DELAY_MS` (default 1000)
- `ANALYSIS_CACHE_TTL` (default 3600)
- `ANALYSIS_STALE_CACHE_TTL` (default 86400)

## Database (Opsional)
Database tidak wajib. Jika ingin menyimpan histori analisis, gunakan `app/Models/AnalysisModel.php` dan buat tabel `analysis_results` (belum ada migrasi).

## Menambah Daftar Saham Dashboard
Edit daftar symbol di:
- `app/Controllers/Dashboard.php`

Pastikan format `.JK`, contoh:

```php
'CENT.JK',
'ACES.JK',
```

## Testing
Jalankan unit test:

```bash
composer test
```

## Troubleshooting
- Jika muncul halaman **Whoops**, cek log CI4 di `writable/logs/`.
- Jika garis EMA/Bollinger tidak muncul di chart, pastikan data historis cukup dan halaman tidak memakai cache lama yang kosong; refresh ulang setelah TTL.
