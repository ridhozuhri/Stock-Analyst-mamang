<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class YahooFinance extends BaseConfig
{
    public int $cacheTtl = 3600;
    public int $quotesCacheTtl = 600;
    public int $staleCacheTtl = 86400;

    public int $retryAttempts = 3;
    public int $retryDelayMs = 1000;

    public string $psr6CacheNamespace = 'yahoo_finance';
    public string $psr6CacheKey = 'yahoo_finance_session_context';
    public string $psr6CacheDirectory = '';

    public function __construct()
    {
        parent::__construct();

        $this->cacheTtl = (int) env('YAHOO_CACHE_TTL', $this->cacheTtl);
        $this->quotesCacheTtl = (int) env('YAHOO_QUOTES_CACHE_TTL', $this->quotesCacheTtl);
        $this->staleCacheTtl = (int) env('YAHOO_STALE_CACHE_TTL', $this->staleCacheTtl);

        $this->retryAttempts = (int) env('YAHOO_RETRY_ATTEMPTS', $this->retryAttempts);
        $this->retryDelayMs = (int) env('YAHOO_RETRY_DELAY_MS', $this->retryDelayMs);

        $this->psr6CacheDirectory = WRITEPATH . 'cache/psr6';
    }
}