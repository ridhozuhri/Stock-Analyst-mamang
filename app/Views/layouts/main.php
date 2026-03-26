<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Stock Analyst CI4') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= site_url('dashboard') ?>">Stock Analyst CI4</a>
    <div class="navbar-nav">
      <a class="nav-link" href="<?= site_url('dashboard') ?>">Dashboard</a>
      <a class="nav-link" href="<?= site_url('about') ?>">About</a>
    </div>
  </div>
</nav>

<main class="container my-4">
  <?= $this->renderSection('content') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
