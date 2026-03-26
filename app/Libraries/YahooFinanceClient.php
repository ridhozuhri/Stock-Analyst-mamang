<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\YahooFinance as YahooFinanceConfig;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use Scheb\YahooFinanceApi\Results\HistoricalData;
use Scheb\YahooFinanceApi\Results\Quote;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class YahooFinanceClient
{
    private readonly YahooFinanceConfig $config;
    private readonly CacheInterface $cache;
    private readonly ApiClient $api;

    public function __construct(?YahooFinanceConfig $config = null, ?CacheInterface $cache = null)
    {
        $this->config = $config ?? config('YahooFinance');
        $this->cache = $cache ?? \Config\Services::cache();

        $dir = $this->config->psr6CacheDirectory;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $psr6 = new FilesystemAdapter(
            $this->config->psr6CacheNamespace,
            $this->config->cacheTtl,
            $dir,
        );

        $clientOptions = [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0 Safari/537.36',
            ],
            'timeout' => 20,
        ];

        $this->api = ApiClientFactory::createApiClient(
            $clientOptions,
            $this->config->retryAttempts,
            $this->config->retryDelayMs,
            $psr6,
            $this->config->cacheTtl,
            $this->config->psr6CacheKey,
        );
    }

    /**
     * @return array{bars:list<array{date:string, open:float|null, high:float|null, low:float|null, close:float|null, adjClose:float|null, volume:int|null}>, meta:array{cache:string, warning:?string}}
     */
    public function getHistoricalData(string $symbol, string $interval = '1d', string $range = '6mo'): array
    {
        $cacheKeyFresh = $this->cacheKey("hist:$symbol:$interval:$range:fresh");
        $cacheKeyStale = $this->cacheKey("hist:$symbol:$interval:$range:stale");

        $cached = $this->cache->get($cacheKeyFresh);
        if (is_array($cached) && isset($cached['bars']) && is_array($cached['bars'])) {
            return ['bars' => $cached['bars'], 'meta' => ['cache' => 'fresh', 'warning' => null]];
        }

        $warning = null;
        try {
            $end = new \DateTimeImmutable('now');
            $start = $this->rangeToStartDate($end, $range);

            $raw = $this->api->getHistoricalQuoteData($symbol, $interval, $start, $end);

            $bars = $this->normalizeHistorical($raw);

            $payload = ['bars' => $bars];
            $this->cache->save($cacheKeyFresh, $payload, $this->config->cacheTtl);
            $this->cache->save($cacheKeyStale, $payload, $this->config->staleCacheTtl);

            return ['bars' => $bars, 'meta' => ['cache' => 'miss', 'warning' => null]];
        } catch (\Throwable $e) {
            $stale = $this->cache->get($cacheKeyStale);
            if (is_array($stale) && isset($stale['bars']) && is_array($stale['bars'])) {
                $warning = 'Yahoo API error, memakai cache lama.';

                return ['bars' => $stale['bars'], 'meta' => ['cache' => 'stale', 'warning' => $warning]];
            }

            throw $e;
        }
    }

    /**
     * @return array{quote:?array, meta:array{cache:string, warning:?string}}
     */
    public function getQuote(string $symbol): array
    {
        $ttl = $this->config->quotesCacheTtl;
        $cacheKeyFresh = $this->cacheKey("quote:$symbol:fresh");
        $cacheKeyStale = $this->cacheKey("quote:$symbol:stale");

        $cached = $this->cache->get($cacheKeyFresh);
        if (is_array($cached) && array_key_exists('quote', $cached)) {
            // Backward-compat: older cache payloads didn't store fetchedAt.
            // If missing, treat as cache miss so we can fetch and populate metadata.
            if (!array_key_exists('fetchedAt', $cached) || empty($cached['fetchedAt'])) {
                $cached = null;
            } else {
            return [
                'quote' => $cached['quote'],
                'meta' => [
                    'cache' => 'fresh',
                    'warning' => null,
                    'fetchedAt' => $cached['fetchedAt'] ?? null,
                ],
            ];
            }
        }

        try {
            $q = $this->api->getQuote($symbol);
            $normalized = null;
            if ($q instanceof Quote) {
                $normalized = $this->normalizeQuote($q);
            }

            $fetchedAt = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s') . ' WIB';
            $payload = ['quote' => $normalized, 'fetchedAt' => $fetchedAt];
            $this->cache->save($cacheKeyFresh, $payload, $ttl);
            $this->cache->save($cacheKeyStale, $payload, $this->config->staleCacheTtl);

            return [
                'quote' => $normalized,
                'meta' => [
                    'cache' => 'miss',
                    'warning' => null,
                    'fetchedAt' => $fetchedAt,
                ],
            ];
        } catch (\Throwable $e) {
            $stale = $this->cache->get($cacheKeyStale);
            if (is_array($stale) && array_key_exists('quote', $stale)) {
                return [
                    'quote' => $stale['quote'],
                    'meta' => [
                        'cache' => 'stale',
                        'warning' => 'Yahoo API error, memakai quote cache lama.',
                        'fetchedAt' => $stale['fetchedAt'] ?? null,
                    ],
                ];
            }

            throw $e;
        }
    }

    /**
     * @param list<string> $symbols
     * @return array{quotes:list<array>, meta:array{cache:string, warning:?string, fetchedAt:?string}}
     */
    public function getQuotes(array $symbols): array
    {
        $ttl = $this->config->quotesCacheTtl;
        sort($symbols);
        $hash = md5(implode(',', $symbols));
        $cacheKeyFresh = $this->cacheKey("quotes:$hash:fresh");
        $cacheKeyStale = $this->cacheKey("quotes:$hash:stale");

        $cached = $this->cache->get($cacheKeyFresh);
        if (is_array($cached) && isset($cached['quotes']) && is_array($cached['quotes'])) {
            // Backward-compat: older cache payloads didn't store fetchedAt.
            // If missing, treat as cache miss so we can fetch and populate metadata.
            if (!array_key_exists('fetchedAt', $cached) || empty($cached['fetchedAt'])) {
                $cached = null;
            } else {
            return [
                'quotes' => $cached['quotes'],
                'meta' => [
                    'cache' => 'fresh',
                    'warning' => null,
                    'fetchedAt' => $cached['fetchedAt'] ?? null,
                ],
            ];
            }
        }

        try {
            $quotes = [];
            foreach ($this->api->getQuotes($symbols) as $q) {
                if ($q instanceof Quote) {
                    $quotes[] = $this->normalizeQuote($q);
                }
            }

            $fetchedAt = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s') . ' WIB';
            $payload = ['quotes' => $quotes, 'fetchedAt' => $fetchedAt];
            $this->cache->save($cacheKeyFresh, $payload, $ttl);
            $this->cache->save($cacheKeyStale, $payload, $this->config->staleCacheTtl);

            return [
                'quotes' => $quotes,
                'meta' => [
                    'cache' => 'miss',
                    'warning' => null,
                    'fetchedAt' => $fetchedAt,
                ],
            ];
        } catch (\Throwable $e) {
            $stale = $this->cache->get($cacheKeyStale);
            if (is_array($stale) && isset($stale['quotes']) && is_array($stale['quotes'])) {
                return [
                    'quotes' => $stale['quotes'],
                    'meta' => [
                        'cache' => 'stale',
                        'warning' => 'Yahoo API error, memakai quotes cache lama.',
                        'fetchedAt' => $stale['fetchedAt'] ?? null,
                    ],
                ];
            }

            throw $e;
        }
    }

    private function cacheKey(string $rawKey): string
    {
        return 'yahoo.' . md5($rawKey);
    }

    /**
     * @param list<HistoricalData> $raw
     * @return list<array{date:string, open:float|null, high:float|null, low:float|null, close:float|null, adjClose:float|null, volume:int|null}>
     */
    private function normalizeHistorical(array $raw): array
    {
        $bars = [];
        foreach ($raw as $item) {
            if (!$item instanceof HistoricalData) {
                continue;
            }

            $bars[] = [
                'date' => $item->getDate()->format('Y-m-d'),
                'open' => $item->getOpen(),
                'high' => $item->getHigh(),
                'low' => $item->getLow(),
                'close' => $item->getClose(),
                'adjClose' => $item->getAdjClose(),
                'volume' => $item->getVolume(),
            ];
        }

        usort($bars, static fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return $bars;
    }

    private function normalizeQuote(Quote $quote): array
    {
        return [
            'symbol' => $quote->getSymbol(),
            'shortName' => $quote->getShortName(),
            'currency' => $quote->getCurrency(),
            'regularMarketPrice' => $quote->getRegularMarketPrice(),
            'regularMarketChange' => $quote->getRegularMarketChange(),
            'regularMarketChangePercent' => $quote->getRegularMarketChangePercent(),
            'regularMarketTime' => $quote->getRegularMarketTime()?->format('Y-m-d H:i:s'),
            'regularMarketOpen' => $quote->getRegularMarketOpen(),
            'regularMarketPreviousClose' => $quote->getRegularMarketPreviousClose(),
            'regularMarketDayHigh' => $quote->getRegularMarketDayHigh(),
            'regularMarketDayLow' => $quote->getRegularMarketDayLow(),
            'regularMarketVolume' => $quote->getRegularMarketVolume(),
        ];
    }

    private function rangeToStartDate(\DateTimeImmutable $end, string $range): \DateTimeImmutable
    {
        $range = trim(strtolower($range));
        if ($range === 'max') {
            return $end->sub(new \DateInterval('P10Y'));
        }

        if (!preg_match('/^(\d+)(d|mo|y)$/', $range, $m)) {
            return $end->sub(new \DateInterval('P6M'));
        }

        $n = (int) $m[1];
        $unit = $m[2];

        return match ($unit) {
            'd' => $end->sub(new \DateInterval('P' . $n . 'D')),
            'mo' => $end->sub(new \DateInterval('P' . $n . 'M')),
            'y' => $end->sub(new \DateInterval('P' . $n . 'Y')),
            default => $end->sub(new \DateInterval('P6M')),
        };
    }
}
