<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$token = sessionToken();

// ── Ambil data dari API ───────────────────────────────────────────────────
$profilRes = apiCall('profil',  'GET', [], $token);
$rekapRes  = apiCall('rekap',   'GET', [], $token);
$lahanRes  = apiCall('lahan',   'GET', [], $token);

// Profil: { data: { id, kode_petani, nama, no_hp, desa, kecamatan, kabupaten, aktif } }
$profil = $profilRes['data'] ?? [];

// Rekap: { data: { bulanan: [...], total: { total_kg, total_harga, jumlah_setor, jumlah_anomali } } }
$rekapData = $rekapRes['data'] ?? [];
$total     = $rekapData['total']   ?? [];
$bulanan   = $rekapData['bulanan'] ?? [];

// Lahan: { data: [{ id, kode_lahan, nama_lahan, pohon_di_deres, ... }] }
$lahans = $lahanRes['data'] ?? [];
// Urutkan bulanan: terlama → terbaru untuk grafik
$bulananAsc = array_reverse($bulanan);

// ── Siapkan data grafik Chart.js ──────────────────────────────────────────
$chartLabels = [];
$chartKg     = [];
$chartHarga  = [];
foreach ($bulananAsc as $b) {
    // Tampilkan bulan dalam format singkat (Sep 24)
    [$y, $m] = explode('-', $b['bulan']);
    $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $chartLabels[] = $monthNames[(int)$m - 1] . " '" . substr($y, 2);
    $chartKg[]     = (float) $b['total_kg'];
    $chartHarga[]  = (float) $b['total_harga'];
}
$chartJson = json_encode([
    'labels' => $chartLabels,
    'kg'     => $chartKg,
    'harga'  => $chartHarga,
]);
?>
<?php renderHead('Dashboard — ' . APP_NAME, '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>'); ?>
<?php renderTopBar('dashboard'); ?>

<main class="max-w-xl mx-auto px-4 py-5 space-y-5 fade-in">

  <!-- ── Info Petani ──────────────────────────────────────────────────── -->
  <?php if ($profil): ?>
  <div class="bg-amber-600 text-white rounded-2xl p-5 shadow">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xl font-bold"><?= htmlspecialchars($profil['nama'] ?? '-') ?></p>
        <p class="text-sm opacity-80 mt-0.5">Kode: <?= htmlspecialchars($profil['kode_petani'] ?? '-') ?></p>
        <?php if (!empty($profil['desa'])): ?>
        <p class="text-xs opacity-70 mt-1">
          <?= htmlspecialchars($profil['desa']) ?>
          <?= !empty($profil['kecamatan']) ? ', ' . htmlspecialchars($profil['kecamatan']) : '' ?>
        </p>
        <?php endif; ?>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $profil['aktif'] ? 'bg-green-500' : 'bg-gray-400' ?>">
        <?= $profil['aktif'] ? 'Aktif' : 'Non-Aktif' ?>
      </span>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Stat Cards ───────────────────────────────────────────────────── -->
  <?php if ($total): ?>
  <section>
    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Ringkasan Total</h2>
    <div class="grid grid-cols-2 gap-3">

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 border-l-4" style="border-left-color:#f59e0b">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total Setoran</p>
        <p class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($total['total_kg'] ?? 0, 1, ',', '.') ?></p>
        <p class="text-xs text-gray-400">kilogram</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 border-l-4" style="border-left-color:#10b981">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Hasil Penjualan</p>
        <p class="text-base font-bold text-gray-800 mt-1"><?= idr($total['total_harga'] ?? 0) ?></p>
        <p class="text-xs text-gray-400">total diterima</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 border-l-4" style="border-left-color:#3b82f6">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Jml. Transaksi</p>
        <p class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($total['jumlah_setor'] ?? 0) ?></p>
        <p class="text-xs text-gray-400">setoran</p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 border-l-4" style="border-left-color:#14b8a6">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Rata-rata/Setor</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">
          <?= ($total['jumlah_setor'] ?? 0) > 0
              ? number_format(($total['total_kg'] ?? 0) / $total['jumlah_setor'], 1, ',', '.')
              : '0' ?>
        </p>
        <p class="text-xs text-gray-400">kg per setoran</p>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- ── Data Lahan ────────────────────────────────────────────────────── -->
  <?php if (!empty($lahans)): ?>
  <section>
    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Data Lahan</h2>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <?php foreach ($lahans as $i => $l): ?>
      <div class="flex items-center justify-between px-4 py-3 <?= $i > 0 ? 'border-t border-gray-50' : '' ?>">
        <div>
          <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($l['kode_lahan'] ?? '-') ?></p>
          <?php if (!empty($l['nama_lahan'])): ?>
          <p class="text-xs text-gray-400"><?= htmlspecialchars($l['nama_lahan']) ?></p>
          <?php endif; ?>
        </div>
        <span class="text-sm font-semibold text-amber-600">
          <?= number_format($l['pohon_di_deres'] ?? 0) ?> pohon
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Grafik Rekap Bulanan ──────────────────────────────────────────── -->
  <?php if (!empty($chartLabels)): ?>
  <section>
    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Setoran per Bulan (kg)</h2>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
      <div style="position:relative; height:220px;">
        <canvas id="chartBulanan"></canvas>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Tabel Rekap Bulanan ───────────────────────────────────────────── -->
  <?php if (!empty($bulanan)): ?>
  <section>
    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
      Rincian per Bulan
      <span class="ml-1 normal-case font-normal">(<?= count($bulanan) ?> bulan terakhir)</span>
    </h2>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-gray-400 text-[11px] uppercase tracking-wide">
              <th class="text-left px-4 py-2.5 font-semibold">Bulan</th>
              <th class="text-right px-4 py-2.5 font-semibold">Total (kg)</th>
              <th class="text-right px-4 py-2.5 font-semibold">Penjualan</th>
              <th class="text-center px-3 py-2.5 font-semibold">Anomali</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bulanan as $i => $b): ?>
            <tr class="border-t border-gray-50 hover:bg-amber-50 transition-colors">
              <td class="px-4 py-2.5 font-medium text-gray-800"><?= htmlspecialchars($b['bulan'] ?? '-') ?></td>
              <td class="px-4 py-2.5 text-right font-semibold text-gray-800">
                <?= number_format($b['total_kg'] ?? 0, 1, ',', '.') ?> kg
              </td>
              <td class="px-4 py-2.5 text-right font-semibold text-green-600">
                <?= idr($b['total_harga'] ?? 0) ?>
              </td>
              <td class="px-3 py-2.5 text-center">
                <?php if (($b['anomali'] ?? 0) > 0): ?>
                  <span class="inline-block px-2 py-0.5 bg-red-100 text-red-600 text-[10px] font-semibold rounded-full">
                    ⚠ <?= $b['anomali'] ?>
                  </span>
                <?php else: ?>
                  <span class="text-gray-300 text-xs">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if ($total): ?>
          <tfoot>
            <tr class="bg-amber-50 border-t border-amber-100">
              <td class="px-4 py-2.5 font-bold text-gray-700">Total</td>
              <td class="px-4 py-2.5 text-right font-bold text-gray-800">
                <?= number_format($total['total_kg'] ?? 0, 1, ',', '.') ?> kg
              </td>
              <td class="px-4 py-2.5 text-right font-bold text-green-600">
                <?= idr($total['total_harga'] ?? 0) ?>
              </td>
              <td class="px-3 py-2.5 text-center">—</td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Spacer -->
  <div class="h-2"></div>

</main>

<?php renderBottomNav('dashboard'); ?>

<?php if (!empty($chartLabels)): ?>
<script>
(function() {
  const raw = <?= $chartJson ?>;
  const ctx = document.getElementById('chartBulanan');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: raw.labels,
      datasets: [
        {
          label: 'Total (kg)',
          data: raw.kg,
          backgroundColor: 'rgba(245,158,11,0.85)',
          borderColor: 'rgba(217,119,6,1)',
          borderWidth: 1,
          borderRadius: 4,
          yAxisID: 'yKg',
        },
        {
          label: 'Penjualan (Rp)',
          data: raw.harga,
          type: 'line',
          borderColor: 'rgba(16,185,129,0.9)',
          backgroundColor: 'rgba(16,185,129,0.1)',
          borderWidth: 2,
          pointRadius: 3,
          pointBackgroundColor: 'rgba(16,185,129,1)',
          fill: false,
          tension: 0.3,
          yAxisID: 'yRp',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 14 } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              if (ctx.datasetIndex === 0) return ' ' + ctx.parsed.y.toFixed(1) + ' kg';
              return ' Rp ' + ctx.parsed.y.toLocaleString('id-ID');
            }
          }
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 10 }, maxRotation: 45 },
        },
        yKg: {
          position: 'left',
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.06)' },
          ticks: {
            font: { size: 10 },
            callback: v => v + ' kg',
          },
        },
        yRp: {
          position: 'right',
          beginAtZero: true,
          grid: { drawOnChartArea: false },
          ticks: {
            font: { size: 10 },
            callback: v => 'Rp ' + (v / 1000).toFixed(0) + 'k',
          },
        },
      },
    },
  });
})();
</script>
<?php endif; ?>

<style>
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
  .fade-in { animation: fadeIn .25s ease; }
</style>
<?php renderFoot(); ?>
