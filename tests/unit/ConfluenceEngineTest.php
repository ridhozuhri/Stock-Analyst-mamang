<?php

use App\Libraries\ConfluenceEngine;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ConfluenceEngineTest extends CIUnitTestCase
{
    public function testProducesRecommendationAndConfidence(): void
    {
        $bars = [];
        $start = new DateTimeImmutable('2025-01-01');

        $close = 150.0;
        for ($i = 0; $i < 120; $i++) {
            $date = $start->modify('+' . $i . ' day')->format('Y-m-d');

            // slow downtrend
            $close -= 0.4;
            if ($i === 119) {
                // final drop to push Bollinger lower-band touch
                $close -= 12.0;
            }

            $bars[] = [
                'date' => $date,
                'open' => $close,
                'high' => $close + 1.0,
                'low' => $close - 1.0,
                'close' => $close,
                'adjClose' => $close,
                'volume' => 1000,
            ];
        }

        $engine = new ConfluenceEngine();
        $analysis = $engine->analyze($bars);

        $this->assertArrayHasKey('recommendation', $analysis);
        $this->assertContains($analysis['recommendation'], ['BUY', 'SELL', 'HOLD']);

        $this->assertArrayHasKey('pillars', $analysis);
        $this->assertArrayHasKey('pilar1', $analysis['pillars']);
        $this->assertArrayHasKey('pilar2', $analysis['pillars']);
        $this->assertArrayHasKey('pilar3', $analysis['pillars']);

        // With RSI oversold + BB lower-band touch, should lean BUY (2 bullish pillars).
        $this->assertSame('BUY', $analysis['recommendation']);
        $this->assertContains($analysis['confidence'], ['WEAK', 'MODERATE', 'STRONG', null]);
    }
}
