# Stock Analyst CI4  — Confluence System (3 Pillars)

A CodeIgniter 4 + PHP 8.3 application for technical analysis of Indonesian / etc stocks (suffix `.JK`) using the **Confluence System** with 3 candle indicators:

- **P 1 (Trend & Momentum):** EMA20/EMA50 + MACD(12,26,9)
- **P 2 (Overbought/Oversold):** RSI(14) + Stochastic(14,3)
- **P 3 (Volume & Volatility):** Volume Spike + Bollinger Bands(20,2)

Output: **BUY / SELL / HOLD** recommendation + **confidence (STRONG/MODERATE/WEAK)** + score per pillar.

## Disclaimer
This analysis is for educational purposes only and is not a solicitation to buy or sell. Investment decisions are entirely the responsibility of the user.

## Requirements
- PHP **8.3+**
- Extensions: `curl`, `json`, `mbstring`, `intl`
- Composer

## Installation (Local)

```bash
composer install
```

Copy `env` to `.env`, then set at least:

```dotenv
app.baseURL = 'http://localhost/ci4-stock-mangido/public/'
```

Run the dev server:

```bash
php spark serve
```

## Routes / Pages
- Multi‑stock dashboard: `GET /dashboard`
- Stock detail: `GET /stock/{SYMBOL}` e.g. `/stock/ACES.JK`

Note: if the symbol does not include `.JK`, the controller will add it automatically.

## Output Concept (Brief)
- `Chg%`: percent price change (from Yahoo Finance `regularMarketChangePercent`)
- `Reco`: final recommendation based on the confluence of the 3 pillars
- `P1/P2/P3`: status of each pillar (`BULLISH/BEARISH/NEUTRAL`) — hover (tooltip) shows detailed indicator signals

## Caching (As per Blueprint)
- CI4 File Cache: historical data & analysis results
- PSR-6 Symfony Cache: Yahoo context (cookie/crumb) stored in `writable/cache/psr6`

Environment variables used (defaults are already provided):
- `YAHOO_CACHE_TTL` (default 3600)
- `YAHOO_QUOTES_CACHE_TTL` (default 600)
- `YAHOO_STALE_CACHE_TTL` (default 86400)
- `YAHOO_RETRY_ATTEMPTS` (default 3)
- `YAHOO_RETRY_DELAY_MS` (default 1000)
- `ANALYSIS_CACHE_TTL` (default 3600)
- `ANALYSIS_STALE_CACHE_TTL` (default 86400)

## Database (Optional)
A database is not required. If you want to store analysis history, use `app/Models/AnalysisModel.php` and create the `analysis_results` table (no migration provided yet).

## Adding Stocks to the Dashboard
Edit the list of symbols in:
- `app/Controllers/Dashboard.php`

Make sure to use the `.JK` format, e.g.:

```php
'CENT.JK',
'ACES.JK',
```

## Testing
Run unit tests:

```bash
composer test
```

## Troubleshooting
- If a **Whoops** page appears, check the CI4 logs in `writable/logs/`.
- If EMA/Bollinger lines do not appear on the chart, ensure sufficient historical data is available and the page is not using an old empty cache; refresh after the TTL has passed.
