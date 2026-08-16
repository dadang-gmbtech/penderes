<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$token = sessionToken();

// ── Parameter filter ─────────────────────────────────────────────────────
$filterBulan = trim($_GET['bulan'] ?? '');

$qs = http_build_query(array_filter([
    'bulan'    => $filterBulan ?: null,
    'per_page' => 100,
]));
$endpoint = 'setoran' . ($qs ? "?$qs" : '');

// Response: { data: { data: [...], current_page, last_page, total } }
$res       = apiCall($endpoint, 'GET', [], $token);
$paginated = $res['data']['data']         ?? [];
$setorans  = $paginated['data']           ?? [];
$total     = (int) ($paginated['total']   ?? 0);

// ── Kelompokkan per tanggal ──────────────────────────────────────────────
$grouped = [];
foreach ($setorans as $s) {
    $tgl = $s['tanggal_setor'] ?? '';
    if (!$tgl) continue;
    if (!isset($grouped[$tgl])) {
        $grouped[$tgl] = ['count' => 0, 'total_kg' => 0.0, 'total_harga' => 0.0, 'anomali' => 0];
    }
    $grouped[$tgl]['count']++;
    $grouped[$tgl]['total_kg']    += (float) ($s['berat_kg']    ?? 0);
    $grouped[$tgl]['total_harga'] += (float) ($s['total_harga'] ?? 0);
    if (!empty($s['is_anomali'])) $grouped[$tgl]['anomali']++;
}
krsort($grouped); // terbaru di atas

// ── Buat opsi filter 12 bulan terakhir ───────────────────────────────────
$monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
$dayNames   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

$monthOpts = [];
for ($i = 0; $i <= 11; $i++) {
    $ts          = strtotime("-$i months");
    $monthOpts[] = ['key' => date('Y-m', $ts), 'label' => $monthNames[(int)date('n', $ts)-1] . ' ' . date('Y', $ts)];
}

function fmtTgl(string $tgl, array $dayNames, array $monthNames): string {
    $ts = strtotime($tgl);
    return $dayNames[(int)date('w', $ts)]
         . ', ' . date('d', $ts)
         . ' ' . $monthNames[(int)date('n', $ts)-1]
         . ' ' . date('Y', $ts);
}
?>
<?php renderHead('Riwayat Setoran — ' . APP_NAME); ?>
<?php renderTopBar('setoran'); ?>

<main class="max-w-xl mx-auto px-4 py-5 fade-in">

  <!-- ── Filter Bar ────────────────────────────────────────────────────── -->
  <div class="mb-4 flex items-center gap-2">
    <div class="flex-1 relative">
      <select onchange="window.location='setoran.php'+(this.value?'?bulan='+encodeURIComponent(this.value):'')"
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

  <!-- ── Tabel Setoran Per Hari ─────────────────────────────────────────── -->
  <?php if (empty($grouped)): ?>
  <div class="text-center py-16 text-gray-400">
    <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <p class="text-sm">Belum ada setoran<?= $filterBulan ? ' di bulan ini' : '' ?>.</p>
  </div>

  <?php else:
    $grandKg = 0; $grandHarga = 0; $grandCount = 0;
    foreach ($grouped as $g) {
        $grandKg    += $g['total_kg'];
        $grandHarga += $g['total_harga'];
        $grandCount += $g['count'];
    }
  ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-400 text-[11px] uppercase tracking-wide border-b border-gray-100">
            <th class="text-left px-4 py-2.5 font-semibold">Tanggal</th>
            <th class="text-center px-3 py-2.5 font-semibold">Setor</th>
            <th class="text-right px-3 py-2.5 font-semibold">Total (kg)</th>
            <th class="text-right px-4 py-2.5 font-semibold">Penjualan</th>
            <th class="text-center px-3 py-2.5 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grouped as $tgl => $g): ?>
          <tr class="border-t border-gray-50 hover:bg-amber-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap text-xs">
              <?= fmtTgl($tgl, $dayNames, $monthNames) ?>
            </td>
            <td class="px-3 py-3 text-center text-gray-500 text-xs"><?= $g['count'] ?>×</td>
            <td class="px-3 py-3 text-right font-semibold text-gray-800">
              <?= number_format($g['total_kg'], 2, ',', '.') ?> <span class="text-gray-400 font-normal">kg</span>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-green-600">
              <?= idr($g['total_harga']) ?>
            </td>
            <td class="px-3 py-3 text-center">
              <?php if ($g['anomali'] > 0): ?>
                <span class="inline-block px-2 py-0.5 bg-red-100 text-red-600 text-[10px] font-semibold rounded-full">
                  ⚠ <?= $g['anomali'] ?>
                </span>
              <?php else: ?>
                <span class="text-green-500 text-xs font-bold">✓</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="bg-amber-50 border-t border-amber-100">
            <td class="px-4 py-2.5 font-bold text-gray-700 text-xs">Total</td>
            <td class="px-3 py-2.5 text-center font-bold text-gray-600 text-xs"><?= $grandCount ?>×</td>
            <td class="px-3 py-2.5 text-right font-bold text-gray-800">
              <?= number_format($grandKg, 2, ',', '.') ?> <span class="text-gray-400 font-normal">kg</span>
            </td>
            <td class="px-4 py-2.5 text-right font-bold text-green-600"><?= idr($grandHarga) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php endif; ?>

  <div class="h-4"></div>
</main>

<?php renderBottomNav('setoran'); ?>

<style>
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
  .fade-in { animation: fadeIn .25s ease; }
</style>
<?php renderFoot(); ?>
