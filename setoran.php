<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$token = sessionToken();

// ── Parameter filter & pagination ────────────────────────────────────────
$filterBulan = trim($_GET['bulan'] ?? '');
$page        = max(1, (int) ($_GET['page'] ?? 1));

// Build query string untuk API
$qs = http_build_query(array_filter([
    'bulan' => $filterBulan ?: null,
    'page'  => $page > 1 ? $page : null,
]));
$endpoint = 'setoran' . ($qs ? "?$qs" : '');

// Response: { data: [...], meta: { total, per_page, current_page, last_page } }
$res         = apiCall($endpoint, 'GET', [], $token);
$resBody     = $res['data'] ?? [];
$setorans    = $resBody['data']              ?? [];
$meta        = $resBody['meta']              ?? [];
$currentPage = (int) ($meta['current_page'] ?? 1);
$lastPage    = (int) ($meta['last_page']    ?? 1);
$total       = (int) ($meta['total']        ?? 0);

// ── Buat opsi filter 12 bulan terakhir ───────────────────────────────────
$monthOpts = [];
for ($i = 0; $i <= 11; $i++) {
    $ts   = strtotime("-$i months");
    $key  = date('Y-m', $ts);
    $disp = strftime('%B %Y', $ts); // mis: Agustus 2025
    $monthOpts[] = ['key' => $key, 'label' => $disp];
}

// ── Produk config ─────────────────────────────────────────────────────────
$produkConfig = [
    'gula_semut' => ['label' => 'Gula Semut', 'bg' => '#d1fae5', 'color' => '#065f46', 'dot' => '#10b981'],
    'raw_sugar'  => ['label' => 'Raw Sugar',  'bg' => '#dbeafe', 'color' => '#1e40af', 'dot' => '#3b82f6'],
    'nira'       => ['label' => 'Nira',        'bg' => '#ede9fe', 'color' => '#4c1d95', 'dot' => '#8b5cf6'],
    'gula_cair'  => ['label' => 'Gula Cair',  'bg' => '#ffedd5', 'color' => '#9a3412', 'dot' => '#f97316'],
];

function produkLabel(string $key, array $cfg): string {
    return $cfg[$key]['label'] ?? $key;
}

// Helper: URL halaman dengan parameter
function pageUrl(int $p, string $bulan): string {
    $qs = http_build_query(array_filter(['bulan' => $bulan ?: null, 'page' => $p > 1 ? $p : null]));
    return 'setoran.php' . ($qs ? "?$qs" : '');
}
?>
<?php renderHead('Riwayat Setoran — ' . APP_NAME); ?>
<?php renderTopBar('setoran'); ?>

<main class="max-w-xl mx-auto px-4 py-5 fade-in">

  <!-- ── Filter Bar ────────────────────────────────────────────────────── -->
  <div class="mb-4 flex items-center gap-2">
    <div class="flex-1 relative">
      <select onchange="applyFilter(this.value)"
        class="w-full appearance-none pl-4 pr-8 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm focus:outline-none focus:border-amber-400">
        <option value="">Semua Bulan (<?= $total ?> setoran)</option>
        <?php foreach ($monthOpts as $mo): ?>
        <option value="<?= $mo['key'] ?>" <?= $filterBulan === $mo['key'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($mo['label']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </span>
    </div>
    <?php if ($filterBulan): ?>
    <a href="setoran.php"
       class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-xs text-gray-500 font-medium transition whitespace-nowrap">
      Semua
    </a>
    <?php endif; ?>
  </div>

  <?php if ($filterBulan): ?>
  <p class="text-xs text-gray-400 mb-3">
    Menampilkan <?= $total ?> setoran untuk
    <span class="font-semibold text-amber-600"><?= htmlspecialchars($filterBulan) ?></span>
    · Hal. <?= $currentPage ?>/<?= $lastPage ?>
  </p>
  <?php else: ?>
  <p class="text-xs text-gray-400 mb-3">
    Total <?= $total ?> setoran · Hal. <?= $currentPage ?>/<?= $lastPage ?>
  </p>
  <?php endif; ?>

  <!-- ── Daftar Setoran ─────────────────────────────────────────────────── -->
  <?php if (empty($setorans)): ?>
  <div class="text-center py-16 text-gray-400">
    <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <p class="text-sm">Belum ada setoran<?= $filterBulan ? ' di bulan ini' : '' ?>.</p>
  </div>

  <?php else: ?>
  <div class="space-y-3">
    <?php foreach ($setorans as $s):
      $jp  = $s['jenis_produk'] ?? 'lain';
      $cfg = $produkConfig[$jp] ?? ['label' => $jp, 'bg' => '#f3f4f6', 'color' => '#374151', 'dot' => '#9ca3af'];
      $isAnoali = !empty($s['is_anomali']);
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
      <!-- Row 1: produk + tanggal -->
      <div class="flex items-center justify-between mb-2.5">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
              style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
          <span class="w-2 h-2 rounded-full inline-block" style="background:<?= $cfg['dot'] ?>"></span>
          <?= htmlspecialchars($cfg['label']) ?>
        </span>
        <span class="text-xs text-gray-400">
          <?= htmlspecialchars($s['tanggal'] ?? '-') ?>
        </span>
      </div>

      <!-- Row 2: berat + harga -->
      <div class="flex items-end justify-between">
        <div>
          <p class="text-2xl font-bold text-gray-800 leading-none">
            <?= number_format($s['berat_kg'] ?? 0, 2, ',', '.') ?>
            <span class="text-sm font-normal text-gray-400">kg</span>
          </p>
          <?php if (!empty($s['trace_id'])): ?>
          <p class="text-xs text-gray-400 mt-0.5">Batch: <?= htmlspecialchars($s['trace_id']) ?></p>
          <?php endif; ?>
        </div>
        <div class="text-right">
          <p class="text-base font-bold text-green-600">
            <?= idr($s['total_harga'] ?? 0) ?>
          </p>
        </div>
      </div>

      <!-- Anomali warning -->
      <?php if ($isAnoali): ?>
      <div class="mt-3 flex items-start gap-2 bg-red-50 border border-red-100 rounded-xl px-3 py-2">
        <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <p class="text-xs text-red-600">Terdeteksi anomali pada setoran ini.</p>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Pagination ─────────────────────────────────────────────────────── -->
  <?php if ($lastPage > 1): ?>
  <div class="mt-5 flex items-center justify-between gap-2">
    <?php if ($currentPage > 1): ?>
    <a href="<?= pageUrl($currentPage - 1, $filterBulan) ?>"
       class="flex-1 py-2.5 text-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm text-gray-700">
      ← Sebelumnya
    </a>
    <?php else: ?>
    <span class="flex-1 py-2.5 text-center text-sm text-gray-300 bg-white border border-gray-100 rounded-xl">
      ← Sebelumnya
    </span>
    <?php endif; ?>

    <span class="text-xs text-gray-400 whitespace-nowrap px-2">
      <?= $currentPage ?> / <?= $lastPage ?>
    </span>

    <?php if ($currentPage < $lastPage): ?>
    <a href="<?= pageUrl($currentPage + 1, $filterBulan) ?>"
       class="flex-1 py-2.5 text-center text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm text-gray-700">
      Berikutnya →
    </a>
    <?php else: ?>
    <span class="flex-1 py-2.5 text-center text-sm text-gray-300 bg-white border border-gray-100 rounded-xl">
      Berikutnya →
    </span>
    <?php endif; ?>
  </div>

  <!-- Nomor halaman (jika banyak halaman) -->
  <?php if ($lastPage <= 7): ?>
  <div class="mt-3 flex justify-center gap-1.5 flex-wrap">
    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
    <a href="<?= pageUrl($p, $filterBulan) ?>"
       class="w-8 h-8 flex items-center justify-center text-xs rounded-lg font-medium transition
              <?= $p === $currentPage
                  ? 'bg-amber-600 text-white shadow'
                  : 'bg-white border border-gray-200 text-gray-600 hover:bg-amber-50 hover:border-amber-200' ?>">
      <?= $p ?>
    </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>

  <?php endif; ?>

  <div class="h-4"></div>
</main>

<?php renderBottomNav('setoran'); ?>

<script>
function applyFilter(val) {
  const url = 'setoran.php' + (val ? '?bulan=' + encodeURIComponent(val) : '');
  window.location = url;
}
</script>
<style>
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
  .fade-in { animation: fadeIn .25s ease; }
</style>
<?php renderFoot(); ?>
