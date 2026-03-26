<?php

declare(strict_types=1);

namespace App\Libraries;

final class ConfluenceEngine
{
    /**
     * @param list<array{date:string, open:float|null, high:float|null, low:float|null, close:float|null, adjClose:float|null, volume:int|null}> $bars
     */
    public function analyze(array $bars): array
    {
        $warnings = [];

        if (count($bars) < 100) {
            $warnings[] = 'Data historis < 100 bar; akurasi indikator bisa menurun.';
        }

        $closes = [];
        $highs = [];
        $lows = [];
        $volumes = [];
        $dates = [];

        foreach ($bars as $bar) {
            $dates[] = $bar['date'];
            $closes[] = $bar['close'];
            $highs[] = $bar['high'];
            $lows[] = $bar['low'];
            $volumes[] = $bar['volume'];
        }

        $count = count($closes);
        if ($count < 2) {
            return [
                'recommendation' => 'HOLD',
                'confidence' => null,
                'pillars' => [],
                'indicators' => [],
                'series' => [],
                'warnings' => array_merge($warnings, ['Data historis tidak cukup untuk analisis.']),
            ];
        }

        $ema20 = Technical::ema($closes, 20);
        $ema50 = Technical::ema($closes, 50);
        $macd = Technical::macd($closes, 12, 26, 9);
        $rsi14 = Technical::rsi($closes, 14);
        $stoch = Technical::stochastic($highs, $lows, $closes, 14, 3);
        $bb = Technical::bollingerBands($closes, 20, 2.0);
        $volSpike = Technical::volumeSpike($volumes, 20, 1.5);
        $i = $this->lastNonNullIndex($closes);
        if (null === $i || $i < 1) {
            return [
                'recommendation' => 'HOLD',
                'confidence' => null,
                'summary' => [
                    'bullishPillars' => 0,
                    'bearishPillars' => 0,
                    'totalBuy' => 0,
                    'totalSell' => 0,
                ],
                'pillars' => [],
                'indicators' => [],
                'series' => [],
                'warnings' => array_merge($warnings, ['Tidak ada bar close valid untuk dianalisis.']),
            ];
        }

        $p = $this->prevNonNullIndex($closes, $i);
        if (null === $p) {
            return [
                'recommendation' => 'HOLD',
                'confidence' => null,
                'summary' => [
                    'bullishPillars' => 0,
                    'bearishPillars' => 0,
                    'totalBuy' => 0,
                    'totalSell' => 0,
                ],
                'pillars' => [],
                'indicators' => [],
                'series' => [],
                'warnings' => array_merge($warnings, ['Data historis tidak cukup untuk analisis.']),
            ];
        }

        $closeNow = $closes[$i];
        $closePrev = $closes[$p];
        $priceUp = (null !== $closeNow && null !== $closePrev) ? ($closeNow > $closePrev) : null;        $pillars = [];

        // Pilar 1: Trend & Momentum
        $p1Buy = 0;
        $p1Sell = 0;

        $ema20Now = $ema20[$i] ?? null;
        $ema50Now = $ema50[$i] ?? null;
        $emaSignal = 'N/A';
        if (null !== $ema20Now && null !== $ema50Now) {
            if ($ema20Now > $ema50Now) {
                $p1Buy += 25;
                $emaSignal = 'BULLISH (EMA20 > EMA50)';
            } elseif ($ema20Now < $ema50Now) {
                $p1Sell += 25;
                $emaSignal = 'BEARISH (EMA20 < EMA50)';
            } else {
                $emaSignal = 'NETRAL (EMA20 = EMA50)';
            }
        } else {
            $warnings[] = 'EMA20/EMA50 belum tersedia (data kurang).';
        }

        $macdLine = $macd['macd'];
        $macdSignalLine = $macd['signal'];
        $macdNow = $macdLine[$i] ?? null;
        $signalNow = $macdSignalLine[$i] ?? null;
        $macdPrev = $macdLine[$p] ?? null;
        $signalPrev = $macdSignalLine[$p] ?? null;

        $macdSignal = 'N/A';
        if (null !== $macdNow && null !== $signalNow && null !== $macdPrev && null !== $signalPrev) {
            if ($macdPrev < $signalPrev && $macdNow > $signalNow) {
                $p1Buy += 25;
                $macdSignal = 'BULLISH (MACD crossover up)';
            } elseif ($macdPrev > $signalPrev && $macdNow < $signalNow) {
                $p1Sell += 25;
                $macdSignal = 'BEARISH (MACD crossover down)';
            } else {
                $macdSignal = 'NETRAL (no crossover)';
            }
        } else {
            $warnings[] = 'MACD belum tersedia (data kurang).';
        }

        $pillars['pilar1'] = $this->pillarResult('TREND & MOMENTUM', $p1Buy, $p1Sell, [
            'ema' => $emaSignal,
            'macd' => $macdSignal,
        ]);

        // Pilar 2: Overbought/Oversold
        $p2Buy = 0;
        $p2Sell = 0;

        $rsiNow = $rsi14[$i] ?? null;
        $rsiSignal = 'N/A';
        if (null !== $rsiNow) {
            if ($rsiNow < 30.0) {
                $p2Buy += 25;
                $rsiSignal = 'BULLISH (RSI < 30 oversold)';
            } elseif ($rsiNow > 70.0) {
                $p2Sell += 25;
                $rsiSignal = 'BEARISH (RSI > 70 overbought)';
            } else {
                $rsiSignal = 'NETRAL';
            }
        } else {
            $warnings[] = 'RSI belum tersedia (data kurang).';
        }

        $k = $stoch['k'];
        $d = $stoch['d'];
        $kNow = $k[$i] ?? null;
        $dNow = $d[$i] ?? null;
        $kPrev = $k[$p] ?? null;
        $dPrev = $d[$p] ?? null;

        $stochSignal = 'N/A';
        if (null !== $kNow && null !== $dNow && null !== $kPrev && null !== $dPrev) {
            if ($kNow < 20.0 && $kPrev < $dPrev && $kNow > $dNow) {
                $p2Buy += 25;
                $stochSignal = 'BULLISH (%K < 20 dan crossover up)';
            } elseif ($kNow > 80.0 && $kPrev > $dPrev && $kNow < $dNow) {
                $p2Sell += 25;
                $stochSignal = 'BEARISH (%K > 80 dan crossover down)';
            } else {
                $stochSignal = 'NETRAL';
            }
        } else {
            $warnings[] = 'Stochastic belum tersedia (data kurang).';
        }

        $pillars['pilar2'] = $this->pillarResult('OVERBOUGHT/OVERSOLD', $p2Buy, $p2Sell, [
            'rsi' => $rsiSignal,
            'stochastic' => $stochSignal,
        ]);

        // Pilar 3: Volume & Volatilitas
        $p3Buy = 0;
        $p3Sell = 0;

        $volNow = $volumes[$i] ?? null;
        $volRatioNow = $volSpike['ratio'][$i] ?? null;
        $spikeNow = $volSpike['spike'][$i] ?? null;

        $volSignal = 'N/A';
        if (null !== $spikeNow && null !== $volRatioNow && null !== $priceUp) {
            if ($spikeNow === true && $priceUp === true) {
                $p3Buy += 25;
                $volSignal = 'BULLISH (volume spike + harga naik)';
            } elseif ($spikeNow === true && $priceUp === false) {
                $p3Sell += 25;
                $volSignal = 'BEARISH (volume spike + harga turun)';
            } else {
                $volSignal = 'NETRAL';
            }
        } else {
            $warnings[] = 'Volume spike belum tersedia (data kurang).';
        }

        $bbUpperNow = $bb['upper'][$i] ?? null;
        $bbMiddleNow = $bb['middle'][$i] ?? null;
        $bbLowerNow = $bb['lower'][$i] ?? null;

        $bbSignal = 'N/A';
        if (null !== $closeNow && null !== $bbUpperNow && null !== $bbLowerNow) {
            if ($closeNow <= $bbLowerNow) {
                $p3Buy += 25;
                $bbSignal = 'BULLISH (harga <= lower band)';
            } elseif ($closeNow >= $bbUpperNow) {
                $p3Sell += 25;
                $bbSignal = 'BEARISH (harga >= upper band)';
            } else {
                $bbSignal = 'NETRAL';
            }
        } else {
            $warnings[] = 'Bollinger Bands belum tersedia (data kurang).';
        }

        $pillars['pilar3'] = $this->pillarResult('VOLUME & VOLATILITAS', $p3Buy, $p3Sell, [
            'volume' => $volSignal,
            'bollinger' => $bbSignal,
        ]);

        $bullish = 0;
        $bearish = 0;
        $totalBuy = 0;
        $totalSell = 0;

        foreach ($pillars as $pillar) {
            $totalBuy += $pillar['buy'];
            $totalSell += $pillar['sell'];
            if ($pillar['status'] === 'BULLISH') {
                $bullish++;
            } elseif ($pillar['status'] === 'BEARISH') {
                $bearish++;
            }
        }

        $recommendation = 'HOLD';
        if ($bullish >= 2) {
            $recommendation = 'BUY';
        } elseif ($bearish >= 2) {
            $recommendation = 'SELL';
        }

        $confidence = null;
        if ($recommendation === 'BUY') {
            $confidence = $this->confidenceFromScore($totalBuy);
        } elseif ($recommendation === 'SELL') {
            $confidence = $this->confidenceFromScore($totalSell);
        }

        return [
            'recommendation' => $recommendation,
            'confidence' => $confidence,
            'summary' => [
                'bullishPillars' => $bullish,
                'bearishPillars' => $bearish,
                'totalBuy' => $totalBuy,
                'totalSell' => $totalSell,
            ],
            'pillars' => $pillars,
            'indicators' => [
                'ema20' => $ema20Now,
                'ema50' => $ema50Now,
                'rsi14' => $rsiNow,
                'stochK' => $kNow,
                'stochD' => $dNow,
                'macd' => $macdNow,
                'macdSignal' => $signalNow,
                'macdHistogram' => $macd['histogram'][$i] ?? null,
                'bbUpper' => $bbUpperNow,
                'bbMiddle' => $bbMiddleNow,
                'bbLower' => $bbLowerNow,
                'volume' => $volNow,
                'volumeRatio' => $volRatioNow,
            ],
            'series' => [
                'dates' => $dates,
                'close' => $closes,
                'ema20' => $ema20,
                'ema50' => $ema50,
                'bbUpper' => $bb['upper'],
                'bbMiddle' => $bb['middle'],
                'bbLower' => $bb['lower'],
                'rsi14' => $rsi14,
                'macd' => $macd['macd'],
                'macdSignal' => $macd['signal'],
                'macdHistogram' => $macd['histogram'],
                'stochK' => $k,
                'stochD' => $d,
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<string, string> $signals
     */
    private function pillarResult(string $name, int $buy, int $sell, array $signals): array
    {
        $score = $buy - $sell;
        $status = 'NETRAL';
        if ($score >= 25) {
            $status = 'BULLISH';
        } elseif ($score <= -25) {
            $status = 'BEARISH';
        }

        return [
            'name' => $name,
            'buy' => $buy,
            'sell' => $sell,
            'score' => $score,
            'status' => $status,
            'signals' => $signals,
        ];
    }

    private function confidenceFromScore(int $score): ?string
    {
        if ($score >= 100) {
            return 'STRONG';
        }
        if ($score >= 75) {
            return 'MODERATE';
        }
        if ($score >= 50) {
            return 'WEAK';
        }

        return null;
    }
    /**
     * @param list<float|int|null> $values
     */
    private function lastNonNullIndex(array $values): ?int
    {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if (null !== $values[$i]) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<float|int|null> $values
     */
    private function prevNonNullIndex(array $values, int $fromIndex): ?int
    {
        for ($i = $fromIndex - 1; $i >= 0; $i--) {
            if (null !== $values[$i]) {
                return $i;
            }
        }

        return null;
    }
}
