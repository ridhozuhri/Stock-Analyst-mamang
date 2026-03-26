<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\StockModel;

final class Dashboard extends BaseController
{
    /**
     * @var list<string>
     */
    private array $symbols = [
        'BBCA.JK',
        'BBRI.JK',
        'BMRI.JK',
        'BBNI.JK',
        'TLKM.JK',
        'ASII.JK',
        'ADRO.JK',
        'ICBP.JK',
        'UNVR.JK',
        'INDF.JK',
        'CPIN.JK',
        'PGAS.JK',
        'SMGR.JK',
        'ANTM.JK',
        'INCO.JK',
        'CENT.JK',
        'ACES.JK',
        'ISAT.JK',
        'JPFA.JK',
        'MNCN.JK',
    ];

    public function index()
    {
        $interval = '1d';
        $range = '6mo';

        $cache = \Config\Services::cache();
        $analysisTtl = (int) env('ANALYSIS_CACHE_TTL', 3600);

        $stock = new StockModel();

        $quotesResp = $stock->getQuotes($this->symbols);
        $quotesBySymbol = [];
        foreach ($quotesResp['quotes'] as $q) {
            if (isset($q['symbol'])) {
                $quotesBySymbol[$q['symbol']] = $q;
            }
        }

        $rows = [];
        $counts = ['BUY' => 0, 'SELL' => 0, 'HOLD' => 0];

        foreach ($this->symbols as $symbol) {
            $summaryKey = 'analysis.summary.' . md5($symbol . ':' . $interval . ':' . $range);
            $summary = $cache->get($summaryKey);

            if (!is_array($summary)) {
                try {
                    $historical = $stock->getHistoricalData($symbol, $interval, $range);
                    $analysis = $stock->analyzeFromBars($historical['bars']);

                    $summary = [
                        'recommendation' => $analysis['recommendation'] ?? 'HOLD',
                        'confidence' => $analysis['confidence'] ?? null,
                        'pillars' => $analysis['pillars'] ?? [],
                        'summary' => $analysis['summary'] ?? [],
                        'warnings' => $analysis['warnings'] ?? [],
                    ];

                    $cache->save($summaryKey, $summary, $analysisTtl);
                } catch (\Throwable $e) {
                    $summary = [
                        'recommendation' => 'HOLD',
                        'confidence' => null,
                        'pillars' => [],
                        'summary' => [],
                        'warnings' => ['Gagal analisis: ' . $e->getMessage()],
                    ];
                }
            }

            $rec = $summary['recommendation'] ?? 'HOLD';
            if (isset($counts[$rec])) {
                $counts[$rec]++;
            }

            $rows[] = [
                'symbol' => $symbol,
                'quote' => $quotesBySymbol[$symbol] ?? null,
                'analysis' => $summary,
            ];
        }

        return view('stock/dashboard', [
            'symbols' => $this->symbols,
            'quotesMeta' => $quotesResp['meta'],
            'rows' => $rows,
            'counts' => $counts,
        ]);
    }
}
