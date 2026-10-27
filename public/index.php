<?php
require __DIR__ . '/../src/env.php';
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$root = dirname(__DIR__);
$envPath = $root . '/.env';
$env = muat_env($envPath);
$tim = $env['TEAM_NAME'] ?? '';
$anggota = array_filter(array_map('trim', explode(',', $env['TEAM_MEMBERS'] ?? '')));

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$lokal = (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/i', $host);
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

if ($lokal) {
    $status = 'lokal';
    $judul = 'Baru terlihat di laptopmu.';
    $ket = 'Alamat ini hanya berlaku di komputermu. Deploy ke hosting supaya temanmu bisa membukanya.';
} elseif (!$https) {
    $status = 'http';
    $judul = 'Sudah online, belum aman.';
    $ket = 'Orang lain sudah bisa membuka situsmu, tetapi browser menandainya tidak aman. Aktifkan SSL di cPanel.';
} else {
    $status = 'live';
    $judul = 'Sudah bisa dibuka siapa saja.';
    $ket = 'Kirim alamat di atas ke temanmu. Kalau mereka bisa membukanya, tahap deploy-mu selesai.';
}

$cek = [
    [is_file($envPath), 'File .env terbaca', 'Buat file .env di folder repo, sejajar dengan folder public. Contoh isinya ada di .env.example.'],
    [$tim !== '', 'Nama tim sudah diisi', 'Isi TEAM_NAME di file .env.'],
    [!$lokal, 'Alamat bisa dibuka dari luar laptopmu', 'Deploy ke hosting, lalu buka lewat subdomain timmu.'],
    [$https, 'Dibuka lewat HTTPS', 'Jalankan AutoSSL di cPanel, lalu buka lagi lewat https://.'],
];

// Daftar tim dari tim/*.md. Hanya URL http(s) yang dijadikan tautan.
$galeri = [];
foreach (array_slice(glob($root . '/tim/*.md') ?: [], 0, 100) as $f) {
    $b = basename($f);
    if ($b === 'README.md' || $b[0] === '_') continue;
    $d = [];
    foreach (file($f, FILE_IGNORE_NEW_LINES) as $l) {
        if (strpos($l, ':') === false) continue;
        [$k, $v] = explode(':', $l, 2);
        $d[strtolower(trim($k))] = trim($v);
    }
    $url = $d['url'] ?? '';
    $aman = preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL);
    $galeri[] = [
        'nama' => $d['nama'] ?? basename($f, '.md'),
        'anggota' => $d['anggota'] ?? '',
        'url' => $aman ? $url : '',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($tim !== '' ? $tim : 'Tim tanpa nama') ?> di web terbuka</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
  <div class="atas">
    <span>Hacktoberfest 2026 di Polinema, bersama Jagoan Hosting</span>
    <span><?= h($tim !== '' ? $tim : 'Tim belum diberi nama') ?></span>
  </div>

  <h1><?= h($judul) ?></h1>
  <div class="url <?= h($status) ?>">
    <span class="skema"><?= $https ? 'https://' : 'http://' ?></span><strong><?= h($host) ?></strong>
  </div>
  <p class="ket"><?= h($ket) ?></p>

  <div class="grid">
    <section class="cek">
      <h2>Cek deploy</h2>
      <ul>
      <?php foreach ($cek as [$ok, $label, $hint]): ?>
        <li class="<?= $ok ? 'sudah' : 'belum' ?>">
          <span class="st"><?= $ok ? 'Sudah' : 'Belum' ?></span>
          <span><?= h($label) ?></span>
          <?php if (!$ok): ?><p class="hint"><?= h($hint) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>

    <section>
      <h2>Tim</h2>
      <dl>
        <dt>Nama</dt><dd><?= $tim !== '' ? h($tim) : 'Belum diisi' ?></dd>
        <dt>Anggota</dt><dd><?= $anggota ? h(implode(', ', $anggota)) : 'Belum diisi' ?></dd>
        <dt>PHP di server</dt><dd><?= h(PHP_VERSION) ?></dd>
      </dl>
    </section>
  </div>

  <section class="daftar">
    <h2>Tim yang sudah live</h2>
    <?php if ($galeri): ?>
    <ul class="gal">
      <?php foreach ($galeri as $g): ?>
      <li>
        <span><b><?= h($g['nama']) ?></b><?= $g['anggota'] !== '' ? '<br><small>' . h($g['anggota']) . '</small>' : '' ?></span>
        <?php if ($g['url'] !== ''): ?><a href="<?= h($g['url']) ?>" rel="noopener nofollow"><?= h($g['url']) ?></a><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>Belum ada tim di daftar ini. Kirim pull request berisi satu file di folder <code>tim/</code> supaya timmu muncul di sini.</p>
    <?php endif; ?>
  </section>

  <footer>Template workshop Hacktoberfest 2026: From Localhost To Open Web.</footer>
</div>
</body>
</html>