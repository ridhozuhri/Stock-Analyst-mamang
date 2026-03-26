<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
  /**
   * Tooltip (native browser) untuk pilar.
   *
   * @param array{name?:string,status?:string,buy?:int,sell?:int,score?:int,signals?:array<string,string>}|null $pillar
   */
  $pillarTitle = static function (?array $pillar): string {
    if (empty($pillar)) {
      return 'Tidak ada data.';
    }

    $lines = [];
    $lines[] = 'Status: ' . ($pillar['status'] ?? 'N/A');
    $lines[] = 'Buy/Sell/Score: ' . ($pillar['buy'] ?? '-') . ' / ' . ($pillar['sell'] ?? '-') . ' / ' . ($pillar['score'] ?? '-');

    $signals = $pillar['signals'] ?? [];
    foreach ($signals as $k => $v) {
      $label = strtoupper((string) $k);
      $lines[] = $label . ': ' . (string) $v;
    }

    return implode("\n", $lines);
  };
?>

<h1 class="h3 mb-3">Dashboard</h1>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold">Ringkasan</div>
        <div class="small text-muted">BUY: <?= (int) ($counts['BUY'] ?? 0) ?> | SELL: <?= (int) ($counts['SELL'] ?? 0) ?> | HOLD: <?= (int) ($counts['HOLD'] ?? 0) ?></div>
        <div class="small text-muted">Quotes cache: <?= esc($quotesMeta['cache'] ?? 'n/a') ?></div>
        <div class="small text-muted">Last quotes fetch: <?= esc($quotesMeta['fetchedAt'] ?? '-') ?></div>
      </div>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>Symbol</th>
        <th class="text-end">Price</th>
        <th>Conf</th>
        <th>P1</th>
        <th>P2</th>
        <th>P3</th>
        <th class="text-end">Chg%</th>
        <th>Reco</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $symbol = $row['symbol'];
        $quote = $row['quote'] ?? null;
        $analysis = $row['analysis'] ?? [];

        $rec = $analysis['recommendation'] ?? 'HOLD';
        $conf = $analysis['confidence'] ?? null;
        $pillars = $analysis['pillars'] ?? [];
        $sum = $analysis['summary'] ?? [];

        $bullishPillars = $sum['bullishPillars'] ?? null;
        $bearishPillars = $sum['bearishPillars'] ?? null;
        $totalBuy = $sum['totalBuy'] ?? null;
        $totalSell = $sum['totalSell'] ?? null;

        $badge = match ($rec) {
          'BUY' => 'bg-success',
          'SELL' => 'bg-danger',
          default => 'bg-warning text-dark',
        };

        $p1 = $pillars['pilar1']['status'] ?? 'N/A';
        $p2 = $pillars['pilar2']['status'] ?? 'N/A';
        $p3 = $pillars['pilar3']['status'] ?? 'N/A';
      ?>
      <tr>
        <td><a href="<?= site_url('stock/' . $symbol) ?>"><?= esc($symbol) ?></a></td>
        <td class="text-end"><?= esc($quote['regularMarketPrice'] ?? '-') ?></td>
        <td><?= esc($conf ?? '-') ?></td>
        <td>
          <span class="text-decoration-underline" style="cursor: help"
                title="<?= esc($pillarTitle($pillars['pilar1'] ?? null), 'attr') ?>">
            <?= esc($p1) ?>
          </span>
        </td>
        <td>
          <span class="text-decoration-underline" style="cursor: help"
                title="<?= esc($pillarTitle($pillars['pilar2'] ?? null), 'attr') ?>">
            <?= esc($p2) ?>
          </span>
        </td>
        <td>
          <span class="text-decoration-underline" style="cursor: help"
                title="<?= esc($pillarTitle($pillars['pilar3'] ?? null), 'attr') ?>">
            <?= esc($p3) ?>
          </span>
        </td>
        <td class="text-end"><?= esc(isset($quote['regularMarketChangePercent']) ? number_format((float) $quote['regularMarketChangePercent'], 2) : '-') ?></td>
        <td>
          <div><span class="badge <?= $badge ?>"><?= esc($rec) ?></span></div>
          <div class="small text-muted">Bull: <?= esc($bullishPillars ?? '-') ?> | Bear: <?= esc($bearishPillars ?? '-') ?></div>
          <div class="small text-muted">Buy: <?= esc($totalBuy ?? '-') ?> | Sell: <?= esc($totalSell ?? '-') ?></div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
