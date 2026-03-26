<?php

declare(strict_types=1);

namespace App\Models;

use App\Libraries\ConfluenceEngine;
use App\Libraries\YahooFinanceClient;

final class StockModel
{
    public function __construct(
        private readonly YahooFinanceClient $client = new YahooFinanceClient(),
        private readonly ConfluenceEngine $engine = new ConfluenceEngine(),
    ) {
    }

    /**
     * @return array{bars:list<array{date:string, open:float|null, high:float|null, low:float|null, close:float|null, adjClose:float|null, volume:int|null}>, meta:array{cache:string, warning:?string}}
     */
    public function getHistoricalData(string $symbol, string $interval = '1d', string $range = '6mo'): array
    {
        return $this->client->getHistoricalData($symbol, $interval, $range);
    }

    /**
     * @return array{quote:?array, meta:array{cache:string, warning:?string}}
     */
    public function getQuote(string $symbol): array
    {
        return $this->client->getQuote($symbol);
    }

    /**
     * @param list<string> $symbols
     * @return array{quotes:list<array>, meta:array{cache:string, warning:?string}}
     */
    public function getQuotes(array $symbols): array
    {
        return $this->client->getQuotes($symbols);
    }

    public function analyzeFromBars(array $bars): array
    {
        return $this->engine->analyze($bars);
    }
}
