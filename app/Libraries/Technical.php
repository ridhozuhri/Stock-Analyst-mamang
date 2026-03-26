<?php

declare(strict_types=1);

namespace App\Libraries;

final class Technical
{
    /**
     * @param list<float|int|null> $values
     * @return list<float|null>
     */
    public static function sma(array $values, int $period): array
    {
        $count = count($values);
        $out = array_fill(0, $count, null);
        if ($period <= 0 || $count === 0) {
            return $out;
        }

        $sum = 0.0;
        $window = [];

        for ($i = 0; $i < $count; $i++) {
            $v = $values[$i];
            $window[] = $v;
            if (null !== $v) {
                $sum += (float) $v;
            }

            if (count($window) > $period) {
                $old = array_shift($window);
                if (null !== $old) {
                    $sum -= (float) $old;
                }
            }

            if ($i >= $period - 1) {
                $valid = 0;
                foreach ($window as $w) {
                    if (null !== $w) {
                        $valid++;
                    }
                }
                if ($valid === $period) {
                    $out[$i] = $sum / $period;
                }
            }
        }

        return $out;
    }

    /**
     * @param list<float|int|null> $values
     * @return list<float|null>
     */
    public static function ema(array $values, int $period): array
    {
        $count = count($values);
        $out = array_fill(0, $count, null);
        if ($period <= 0 || $count === 0) {
            return $out;
        }

        $k = 2.0 / ($period + 1);
        $window = [];
        $sum = 0.0;
        $seeded = false;

        for ($i = 0; $i < $count; $i++) {
            $v = $values[$i];

            $window[] = $v;
            if (null !== $v) {
                $sum += (float) $v;
            }

            if (count($window) > $period) {
                $old = array_shift($window);
                if (null !== $old) {
                    $sum -= (float) $old;
                }
            }

            if (!$seeded) {
                if (count($window) === $period) {
                    $allValid = true;
                    foreach ($window as $w) {
                        if (null === $w) {
                            $allValid = false;
                            break;
                        }
                    }

                    if ($allValid) {
                        $out[$i] = $sum / $period;
                        $seeded = true;
                    }
                }

                continue;
            }

            $prev = $out[$i - 1];
            if (null === $prev) {
                $out[$i] = null;
                continue;
            }

            if (null === $v) {
                $out[$i] = $prev;
                continue;
            }

            $out[$i] = ((float) $v * $k) + ($prev * (1.0 - $k));
        }

        return $out;
    }

    /**
     * @param list<float|int|null> $closes
     * @return array{macd:list<float|null>, signal:list<float|null>, histogram:list<float|null>}
     */
    public static function macd(array $closes, int $fast = 12, int $slow = 26, int $signalPeriod = 9): array
    {
        $count = count($closes);
        $macd = array_fill(0, $count, null);
        $signal = array_fill(0, $count, null);
        $hist = array_fill(0, $count, null);

        $emaFast = self::ema($closes, $fast);
        $emaSlow = self::ema($closes, $slow);

        for ($i = 0; $i < $count; $i++) {
            if (null === $emaFast[$i] || null === $emaSlow[$i]) {
                continue;
            }
            $macd[$i] = $emaFast[$i] - $emaSlow[$i];
        }

        $signal = self::ema($macd, $signalPeriod);

        for ($i = 0; $i < $count; $i++) {
            if (null === $macd[$i] || null === $signal[$i]) {
                continue;
            }
            $hist[$i] = $macd[$i] - $signal[$i];
        }

        return ['macd' => $macd, 'signal' => $signal, 'histogram' => $hist];
    }

    /**
     * Wilder RSI.
     *
     * @param list<float|int|null> $closes
     * @return list<float|null>
     */
    public static function rsi(array $closes, int $period = 14): array
    {
        $count = count($closes);
        $out = array_fill(0, $count, null);
        if ($period <= 0 || $count < $period + 1) {
            return $out;
        }

        $gainSum = 0.0;
        $lossSum = 0.0;

        for ($i = 1; $i <= $period; $i++) {
            if (null === $closes[$i] || null === $closes[$i - 1]) {
                return $out;
            }
            $change = (float) $closes[$i] - (float) $closes[$i - 1];
            if ($change >= 0) {
                $gainSum += $change;
            } else {
                $lossSum += abs($change);
            }
        }

        $avgGain = $gainSum / $period;
        $avgLoss = $lossSum / $period;

        $out[$period] = self::rsiFromAverages($avgGain, $avgLoss);

        for ($i = $period + 1; $i < $count; $i++) {
            if (null === $closes[$i] || null === $closes[$i - 1]) {
                continue;
            }

            $change = (float) $closes[$i] - (float) $closes[$i - 1];
            $gain = $change > 0 ? $change : 0.0;
            $loss = $change < 0 ? abs($change) : 0.0;

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;

            $out[$i] = self::rsiFromAverages($avgGain, $avgLoss);
        }

        return $out;
    }

    private static function rsiFromAverages(float $avgGain, float $avgLoss): float
    {
        if ($avgLoss == 0.0 && $avgGain == 0.0) {
            return 50.0;
        }
        if ($avgLoss == 0.0) {
            return 100.0;
        }
        if ($avgGain == 0.0) {
            return 0.0;
        }

        $rs = $avgGain / $avgLoss;

        return 100.0 - (100.0 / (1.0 + $rs));
    }

    /**
     * @param list<float|int|null> $highs
     * @param list<float|int|null> $lows
     * @param list<float|int|null> $closes
     * @return array{k:list<float|null>, d:list<float|null>}
     */
    public static function stochastic(array $highs, array $lows, array $closes, int $kPeriod = 14, int $dPeriod = 3): array
    {
        $count = count($closes);
        $k = array_fill(0, $count, null);
        $d = array_fill(0, $count, null);

        if ($kPeriod <= 0 || $dPeriod <= 0 || $count === 0) {
            return ['k' => $k, 'd' => $d];
        }

        for ($i = $kPeriod - 1; $i < $count; $i++) {
            $windowHigh = null;
            $windowLow = null;

            for ($j = $i - ($kPeriod - 1); $j <= $i; $j++) {
                if (null === $highs[$j] || null === $lows[$j] || null === $closes[$j]) {
                    $windowHigh = null;
                    $windowLow = null;
                    break;
                }

                $h = (float) $highs[$j];
                $l = (float) $lows[$j];
                $windowHigh = null === $windowHigh ? $h : max($windowHigh, $h);
                $windowLow = null === $windowLow ? $l : min($windowLow, $l);
            }

            if (null === $windowHigh || null === $windowLow) {
                continue;
            }

            $den = $windowHigh - $windowLow;
            if ($den == 0.0) {
                $k[$i] = 50.0;
                continue;
            }

            $k[$i] = (((float) $closes[$i]) - $windowLow) / $den * 100.0;
        }

        $d = self::sma($k, $dPeriod);

        return ['k' => $k, 'd' => $d];
    }

    /**
     * @param list<float|int|null> $closes
     * @return array{upper:list<float|null>, middle:list<float|null>, lower:list<float|null>}
     */
    public static function bollingerBands(array $closes, int $period = 20, float $stdDev = 2.0): array
    {
        $count = count($closes);
        $upper = array_fill(0, $count, null);
        $middle = array_fill(0, $count, null);
        $lower = array_fill(0, $count, null);

        if ($period <= 0 || $count === 0) {
            return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower];
        }

        $sma = self::sma($closes, $period);

        for ($i = $period - 1; $i < $count; $i++) {
            if (null === $sma[$i]) {
                continue;
            }

            $mean = $sma[$i];
            $sumSq = 0.0;
            $valid = 0;

            for ($j = $i - ($period - 1); $j <= $i; $j++) {
                if (null === $closes[$j]) {
                    $valid = 0;
                    break;
                }
                $diff = ((float) $closes[$j]) - $mean;
                $sumSq += $diff * $diff;
                $valid++;
            }

            if ($valid !== $period) {
                continue;
            }

            $variance = $sumSq / $period;
            $sd = sqrt($variance);

            $middle[$i] = $mean;
            $upper[$i] = $mean + ($stdDev * $sd);
            $lower[$i] = $mean - ($stdDev * $sd);
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower];
    }

    /**
     * @param list<int|float|null> $volumes
     * @return array{ratio:list<float|null>, spike:list<bool|null>}
     */
    public static function volumeSpike(array $volumes, int $period = 20, float $multiplier = 1.5): array
    {
        $count = count($volumes);
        $ratio = array_fill(0, $count, null);
        $spike = array_fill(0, $count, null);

        if ($period <= 0 || $count === 0) {
            return ['ratio' => $ratio, 'spike' => $spike];
        }

        for ($i = $period; $i < $count; $i++) {
            $sum = 0.0;
            $valid = 0;

            for ($j = $i - $period; $j <= $i - 1; $j++) {
                $v = $volumes[$j];
                if (null === $v) {
                    $valid = 0;
                    break;
                }
                $sum += (float) $v;
                $valid++;
            }

            if ($valid !== $period) {
                continue;
            }

            $avg = $sum / $period;
            if ($avg <= 0.0 || null === $volumes[$i]) {
                continue;
            }

            $r = ((float) $volumes[$i]) / $avg;
            $ratio[$i] = $r;
            $spike[$i] = $r > $multiplier;
        }

        return ['ratio' => $ratio, 'spike' => $spike];
    }
}