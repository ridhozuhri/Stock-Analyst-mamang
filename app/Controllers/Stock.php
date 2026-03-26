<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\StockModel;

final class Stock extends BaseController
{
    public function index(string $symbol = 'BBCA.JK')
    {
        $symbol = strtoupper(trim($symbol));
        if ($symbol !== '' && !str_ends_with($symbol, '.JK')) {
            $symbol .= '.JK';
        }

        $interval = '1d';
        $range = '6mo';

        $cache = \Config\Services::cache();
        $analysisTtl = (int) env('ANALYSIS_CACHE_TTL', 3600);
        $analysisStaleTtl = (int) env('ANALYSIS_STALE_CACHE_TTL', 86400);

        $analysisKeyFresh = 'analysis.' . md5($symbol . ':' . $interval . ':' . $range . ':fresh');
        $analysisKeyStale = 'analysis.' . md5($symbol . ':' . $interval . ':' . $range . ':stale');

        $stock = new StockModel();

        $historical = $stock->getHistoricalData($symbol, $interval, $range);

        $analysis = $cache->get($analysisKeyFresh);
        $analysisMeta = ['cache' => 'fresh', 'warning' => null];

        if (!is_array($analysis)) {
            try {
                $analysis = $stock->analyzeFromBars($historical['bars']);
                $cache->save($analysisKeyFresh, $analysis, $analysisTtl);
                $cache->save($analysisKeyStale, $analysis, $analysisStaleTtl);
                $analysisMeta = ['cache' => 'miss', 'warning' => null];
            } catch (\Throwable $e) {
                $stale = $cache->get($analysisKeyStale);
                if (is_array($stale)) {
                    $analysis = $stale;
                    $analysisMeta = ['cache' => 'stale', 'warning' => 'Analisis gagal dihitung ulang; memakai cache lama.'];
                } else {
                    throw $e;
                }
            }
        }

        $quote = $stock->getQuote($symbol);

        $asOf = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s') . ' WIB';

        return view('stock/detail', [
            'symbol' => $symbol,
            'asOf' => $asOf,
            'quote' => $quote['quote'],
            'quoteMeta' => $quote['meta'],
            'historicalBars' => $historical['bars'],
            'historicalMeta' => $historical['meta'],
            'analysis' => $analysis,
            'analysisMeta' => $analysisMeta,
        ]);
    }
}
