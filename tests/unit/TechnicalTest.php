<?php

use App\Libraries\Technical;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TechnicalTest extends CIUnitTestCase
{
    public function testEmaConstantSeries(): void
    {
        $values = array_fill(0, 60, 100.0);
        $ema20 = Technical::ema($values, 20);

        $this->assertCount(60, $ema20);
        $this->assertNull($ema20[0]);
        $this->assertNull($ema20[18]);
        $this->assertSame(100.0, $ema20[19]);
        $this->assertSame(100.0, $ema20[59]);
    }

    public function testEmaSeedsAfterLeadingNull(): void
    {
        $values = array_merge([null], array_fill(0, 40, 100.0));
        $ema20 = Technical::ema($values, 20);

        // First EMA value should appear once we have 20 non-null values in the window.
        $this->assertSame(100.0, $ema20[20]);
        $this->assertSame(100.0, $ema20[count($ema20) - 1]);
    }

    public function testRsiFlatSeriesIs50(): void
    {
        $values = array_fill(0, 40, 100.0);
        $rsi = Technical::rsi($values, 14);

        $this->assertCount(40, $rsi);
        $this->assertSame(50.0, $rsi[14]);
    }

    public function testBollingerOnConstantSeries(): void
    {
        $values = array_fill(0, 30, 100.0);
        $bb = Technical::bollingerBands($values, 20, 2.0);

        $this->assertSame(100.0, $bb['middle'][19]);
        $this->assertSame(100.0, $bb['upper'][19]);
        $this->assertSame(100.0, $bb['lower'][19]);
    }
}