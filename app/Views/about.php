<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
  $name = 'Mamang.ido';
?>
<style>
  .about-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1rem;
    background:
      radial-gradient(900px circle at 15% 10%, rgba(13,110,253,.22), transparent 50%),
      radial-gradient(800px circle at 80% 40%, rgba(32,201,151,.18), transparent 55%),
      radial-gradient(700px circle at 55% 90%, rgba(111,66,193,.18), transparent 55%),
      linear-gradient(180deg, rgba(33,37,41,.9), rgba(33,37,41,1));
  }

  .about-grid {
    position: absolute;
    inset: 0;
    opacity: .08;
    background-image:
      linear-gradient(rgba(255,255,255,.22) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,.22) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(circle at 30% 20%, black 25%, transparent 70%);
    pointer-events: none;
  }

  .about-gradient-text {
    background: linear-gradient(90deg, #0d6efd, #20c997, #6f42c1, #0d6efd);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: aboutGradient 7s linear infinite;
  }

  @keyframes aboutGradient {
    0% { background-position: 0% 50%; }
    100% { background-position: 300% 50%; }
  }

  .about-caret {
    display: inline-block;
    width: .65ch;
    margin-left: .1ch;
    transform: translateY(1px);
    border-left: 2px solid rgba(255,255,255,.85);
    animation: caretBlink 1s step-end infinite;
  }

  @keyframes caretBlink {
    50% { opacity: 0; }
  }

  .about-marquee {
    border-top: 1px solid rgba(255,255,255,.12);
    border-bottom: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.03);
    overflow: hidden;
    white-space: nowrap;
  }
  .about-marquee-track {
    display: inline-flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem 0;
    animation: marquee 22s linear infinite;
  }
  @keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
  }

  .about-chip {
    font-size: .85rem;
    padding: .25rem .6rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.92);
  }
</style>

<div class="about-hero text-white p-4 p-md-5 mb-3">
  <div class="about-grid"></div>
  <div class="position-relative">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
      <div>
        <div class="text-uppercase small text-white-50 mb-2">About</div>
        <h1 class="display-6 fw-semibold mb-2">
          Created by <span class="about-gradient-text"><?= esc($name) ?></span>
        </h1>
        <div class="lead mb-3">
          <span class="text-white-50">I build</span>
          <span id="about-typing" class="fw-semibold"></span><span class="about-caret"></span>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="about-chip">CodeIgniter 4</span>
          <span class="about-chip">PHP 8.3</span>
          <span class="about-chip">Yahoo Finance</span>
          <span class="about-chip">Technical Analysis</span>
          <span class="about-chip">ApexCharts</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary" href="<?= site_url('dashboard') ?>">Open Dashboard</a>
          <a class="btn btn-outline-light" href="<?= site_url('stock/ACES.JK') ?>">View Sample (ACES.JK)</a>
        </div>
      </div>
      <div class="text-lg-end">
        <div class="small text-white-50">Project</div>
        <div class="fs-4 fw-semibold">Stock Analyst CI4</div>
        <div class="small text-white-50">Confluence System</div>
      </div>
    </div>
  </div>
</div>

<div class="about-marquee rounded-3 mb-4" aria-hidden="true">
  <div class="about-marquee-track">
    <span class="about-chip">EMA20/50</span>
    <span class="about-chip">MACD 12-26-9</span>
    <span class="about-chip">RSI 14</span>
    <span class="about-chip">Stochastic 14-3</span>
    <span class="about-chip">Bollinger 20,2</span>
    <span class="about-chip">Volume Spike 1.5x</span>
    <span class="about-chip">Caching (CI4 + PSR-6)</span>
    <span class="about-chip">BUY / SELL / HOLD</span>
    <span class="about-chip">Confidence</span>
    <span class="about-chip">EMA20/50</span>
    <span class="about-chip">MACD 12-26-9</span>
    <span class="about-chip">RSI 14</span>
    <span class="about-chip">Stochastic 14-3</span>
    <span class="about-chip">Bollinger 20,2</span>
    <span class="about-chip">Volume Spike 1.5x</span>
    <span class="about-chip">Caching (CI4 + PSR-6)</span>
    <span class="about-chip">BUY / SELL / HOLD</span>
    <span class="about-chip">Confidence</span>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">What you get</div>
        <ul class="mb-0">
          <li>Dashboard multi-saham + ringkasan P1/P2/P3.</li>
          <li>Detail saham: chart candlestick + overlay EMA/Bollinger, RSI, MACD.</li>
          <li>Scoring transparan per pilar + confidence.</li>
          <li>Caching untuk stabilitas dan performa.</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold mb-2">Notes</div>
        <div class="text-muted">
          Analisis teknikal tidak menjamin profit. Gunakan sebagai referensi dan tetap lakukan manajemen risiko.
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (() => {
    const target = document.getElementById('about-typing');
    if (!target) return;

    const phrases = [
      'fast, clean dashboards.',
      'technical indicators in PHP.',
      'confluence-based trade signals.',
      'tools that feel like a product.',
    ];

    let phraseIndex = 0;
    let charIndex = 0;
    let deleting = false;

    const tick = () => {
      const phrase = phrases[phraseIndex];

      if (!deleting) {
        charIndex++;
        target.textContent = phrase.slice(0, charIndex);
        if (charIndex >= phrase.length) {
          deleting = true;
          setTimeout(tick, 1100);
          return;
        }
      } else {
        charIndex--;
        target.textContent = phrase.slice(0, Math.max(0, charIndex));
        if (charIndex <= 0) {
          deleting = false;
          phraseIndex = (phraseIndex + 1) % phrases.length;
        }
      }

      const speed = deleting ? 26 : 34;
      setTimeout(tick, speed);
    };

    tick();
  })();
</script>
<?= $this->endSection() ?>
