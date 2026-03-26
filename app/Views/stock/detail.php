<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
  $rec = $analysis['recommendation'] ?? 'HOLD';
  $conf = $analysis['confidence'] ?? null;
  $badge = match ($rec) {
    'BUY' => 'bg-success',
    'SELL' => 'bg-danger',
    default => 'bg-warning text-dark',
  };

  $warnings = [];
  if (!empty($historicalMeta['warning'])) {
    $warnings[] = $historicalMeta['warning'];
  }
  if (!empty($quoteMeta['warning'])) {
    $warnings[] = $quoteMeta['warning'];
  }
  if (!empty($analysisMeta['warning'])) {
    $warnings[] = $analysisMeta['warning'];
  }
  foreach (($analysis['warnings'] ?? []) as $w) {
    $warnings[] = $w;
  }

  $pillars = $analysis['pillars'] ?? [];
  $ind = $analysis['indicators'] ?? [];
  $series = $analysis['series'] ?? [];
  $sum = $analysis['summary'] ?? [];
  $bullishPillars = $sum['bullishPillars'] ?? null;
  $bearishPillars = $sum['bearishPillars'] ?? null;
  $totalBuy = $sum['totalBuy'] ?? null;
  $totalSell = $sum['totalSell'] ?? null;
?>

<h1 class="h3 mb-1"><?= esc($symbol) ?></h1>
<div class="text-muted small mb-3">As of: <?= esc($asOf) ?> | Historical cache: <?= esc($historicalMeta['cache'] ?? 'n/a') ?> | Quote cache: <?= esc($quoteMeta['cache'] ?? 'n/a') ?> | Analysis cache: <?= esc($analysisMeta['cache'] ?? 'n/a') ?></div>

<?php if (!empty($warnings)): ?>
  <div class="alert alert-warning">
    <div class="fw-semibold">Peringatan</div>
    <ul class="mb-0">
      <?php foreach ($warnings as $w): ?>
        <li><?= esc($w) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small">Harga Terkini</div>
            <div class="fs-3 fw-semibold"><?= esc($quote['regularMarketPrice'] ?? '-') ?></div>
            <div class="small text-muted">Chg: <?= esc($quote['regularMarketChange'] ?? '-') ?> (<?= esc(isset($quote['regularMarketChangePercent']) ? number_format((float) $quote['regularMarketChangePercent'], 2) : '-') ?>%)</div>
          </div>
          <div class="text-end">
            <div><span class="badge <?= $badge ?> fs-6"><?= esc($rec) ?></span></div>
            <div class="small text-muted">Confidence: <?= esc($conf ?? '-') ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="fw-semibold mb-2">Rangkuman</div>
        <div class="small text-muted">
          Alasan: Candle bullish <span class="fw-semibold"><?= esc($bullishPillars ?? '-') ?></span>, bearish <span class="fw-semibold"><?= esc($bearishPillars ?? '-') ?></span>;
          total BUY <span class="fw-semibold"><?= esc($totalBuy ?? '-') ?></span>, total SELL <span class="fw-semibold"><?= esc($totalSell ?? '-') ?></span>.
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="fw-semibold mb-2">Pilar</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Pilar</th>
                <th>Status</th>
                <th class="text-end">Buy</th>
                <th class="text-end">Sell</th>
                <th class="text-end">Score</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (['pilar1','pilar2','pilar3'] as $k): ?>
                <?php $p = $pillars[$k] ?? null; ?>
                <tr>
                  <td><?= esc($p['name'] ?? $k) ?></td>
                  <td><?= esc($p['status'] ?? 'N/A') ?></td>
                  <td class="text-end"><?= esc($p['buy'] ?? '-') ?></td>
                  <td class="text-end"><?= esc($p['sell'] ?? '-') ?></td>
                  <td class="text-end"><?= esc($p['score'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Candlestick + EMA + Bollinger</div>
        <div id="chart-main" style="height: 360px"></div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold mb-2">RSI (14)</div>
            <div id="chart-rsi" style="height: 220px"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold mb-2">MACD</div>
            <div id="chart-macd" style="height: 220px"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <div class="fw-semibold mb-2">Indikator (Last)</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <tbody>
              <tr><td>EMA20</td><td class="text-end"><?= esc(isset($ind['ema20']) ? number_format((float) $ind['ema20'], 4) : '-') ?></td></tr>
              <tr><td>EMA50</td><td class="text-end"><?= esc(isset($ind['ema50']) ? number_format((float) $ind['ema50'], 4) : '-') ?></td></tr>
              <tr><td>RSI14</td><td class="text-end"><?= esc(isset($ind['rsi14']) ? number_format((float) $ind['rsi14'], 2) : '-') ?></td></tr>
              <tr><td>Stoch %K / %D</td><td class="text-end"><?= esc(isset($ind['stochK']) ? number_format((float) $ind['stochK'], 2) : '-') ?> / <?= esc(isset($ind['stochD']) ? number_format((float) $ind['stochD'], 2) : '-') ?></td></tr>
              <tr><td>MACD / Signal / Hist</td><td class="text-end"><?= esc(isset($ind['macd']) ? number_format((float) $ind['macd'], 4) : '-') ?> / <?= esc(isset($ind['macdSignal']) ? number_format((float) $ind['macdSignal'], 4) : '-') ?> / <?= esc(isset($ind['macdHistogram']) ? number_format((float) $ind['macdHistogram'], 4) : '-') ?></td></tr>
              <tr><td>BB Upper / Mid / Lower</td><td class="text-end"><?= esc(isset($ind['bbUpper']) ? number_format((float) $ind['bbUpper'], 4) : '-') ?> / <?= esc(isset($ind['bbMiddle']) ? number_format((float) $ind['bbMiddle'], 4) : '-') ?> / <?= esc(isset($ind['bbLower']) ? number_format((float) $ind['bbLower'], 4) : '-') ?></td></tr>
              <tr><td>Volume / Ratio</td><td class="text-end"><?= esc($ind['volume'] ?? '-') ?> / <?= esc(isset($ind['volumeRatio']) ? number_format((float) $ind['volumeRatio'], 2) : '-') ?>x</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
  $bars = $historicalBars ?? [];
  $toTsMs = static function (string $date): int {
    try {
      $dt = new DateTimeImmutable($date, new DateTimeZone('UTC'));

      return (int) ($dt->getTimestamp() * 1000);
    } catch (Throwable) {
      $ts = strtotime($date . ' 00:00:00 UTC');

      return $ts === false ? 0 : (int) ($ts * 1000);
    }
  };

  $candle = [];
  foreach ($bars as $b) {
    if (!isset($b['date'])) {
      continue;
    }
    $o = $b['open']; $h = $b['high']; $l = $b['low']; $c = $b['close'];
    if ($o === null || $h === null || $l === null || $c === null) {
      continue;
    }
    $candle[] = [
      'x' => $toTsMs($b['date']),
      'y' => [(float) $o, (float) $h, (float) $l, (float) $c],
    ];
  }

  $dates = $series['dates'] ?? [];
  $line = static function (array $values) use ($dates, $toTsMs): array {
    $out = [];
    $n = min(count($dates), count($values));
    for ($i=0; $i<$n; $i++) {
      $out[] = ['x' => $toTsMs((string) $dates[$i]), 'y' => $values[$i] === null ? null : (float) $values[$i]];
    }
    return $out;
  };

  $ema20 = $line($series['ema20'] ?? []);
  $ema50 = $line($series['ema50'] ?? []);
  $bbU = $line($series['bbUpper'] ?? []);
  $bbM = $line($series['bbMiddle'] ?? []);
  $bbL = $line($series['bbLower'] ?? []);
  $rsi = $line($series['rsi14'] ?? []);
  $macd = $line($series['macd'] ?? []);
  $macdSig = $line($series['macdSignal'] ?? []);
  $macdHist = $line($series['macdHistogram'] ?? []);
?>
<script>
  const candleSeries = <?= json_encode($candle, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const ema20Series = <?= json_encode($ema20, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const ema50Series = <?= json_encode($ema50, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const bbUSeries = <?= json_encode($bbU, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const bbMSeries = <?= json_encode($bbM, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const bbLSeries = <?= json_encode($bbL, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;

  const mainOptions = {
    chart: { type: 'candlestick', height: 360, toolbar: { show: false } },
    stroke: { width: [1, 2, 2, 1, 1, 1] },
    series: [
      { name: 'OHLC', type: 'candlestick', data: candleSeries },
      { name: 'EMA20', type: 'line', data: ema20Series },
      { name: 'EMA50', type: 'line', data: ema50Series },
      { name: 'BB Upper', type: 'line', data: bbUSeries },
      { name: 'BB Middle', type: 'line', data: bbMSeries },
      { name: 'BB Lower', type: 'line', data: bbLSeries },
    ],
    xaxis: { type: 'datetime' },
    tooltip: { x: { format: 'yyyy-MM-dd' } },
    yaxis: { tooltip: { enabled: true } },
  };

  new ApexCharts(document.querySelector('#chart-main'), mainOptions).render();

  const rsiSeries = <?= json_encode($rsi, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  new ApexCharts(document.querySelector('#chart-rsi'), {
    chart: { type: 'line', height: 220, toolbar: { show: false } },
    series: [{ name: 'RSI14', data: rsiSeries }],
    xaxis: { type: 'datetime' },
    yaxis: { min: 0, max: 100 },
    annotations: {
      yaxis: [
        { y: 70, borderColor: '#dc3545', label: { text: '70', style: { color: '#fff', background: '#dc3545' } } },
        { y: 30, borderColor: '#198754', label: { text: '30', style: { color: '#fff', background: '#198754' } } },
      ]
    }
  }).render();

  const macdSeries = <?= json_encode($macd, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const macdSigSeries = <?= json_encode($macdSig, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  const macdHistSeries = <?= json_encode($macdHist, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
  new ApexCharts(document.querySelector('#chart-macd'), {
    chart: { type: 'line', height: 220, toolbar: { show: false } },
    series: [
      { name: 'MACD', data: macdSeries },
      { name: 'Signal', data: macdSigSeries },
      { name: 'Histogram', data: macdHistSeries },
    ],
    xaxis: { type: 'datetime' },
  }).render();
</script>
<?= $this->endSection() ?>
