<?php
function renderHead(string $title = '', string $extraHead = ''): void
{
    global $_pageTitle;
    $t = $title ?: APP_NAME;
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="#D97706">
<title>{$t}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        amber: {
          50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',
          400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',
          800:'#92400e',900:'#78350f',
        }
      }
    }
  }
}
</script>
<style>
  body { -webkit-tap-highlight-color: transparent; }
  .fade-in { animation: fadeIn .2s ease; }
  @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
  .card { @apply bg-white rounded-2xl shadow-sm border border-gray-100; }
</style>
{$extraHead}
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
HTML;
}

function renderTopBar(string $activePage = ''): void
{
    $nama = htmlspecialchars(sessionNama());
    $kode = htmlspecialchars(sessionKode());
    echo <<<HTML
<header class="fixed top-0 inset-x-0 z-40 bg-amber-600 text-white shadow-md">
  <div class="max-w-xl mx-auto flex items-center justify-between px-4 h-14">
    <div class="flex items-center gap-2.5">
      <span class="text-2xl leading-none">🌴</span>
      <div>
        <p class="font-bold text-sm leading-tight">{$nama}</p>
        <p class="text-xs opacity-75 leading-tight">Kode: {$kode}</p>
      </div>
    </div>
    <span class="text-xs opacity-80">e-Traceability</span>
  </div>
</header>
<div class="h-14"></div>
HTML;
}

function renderBottomNav(string $activePage = ''): void
{
    $pages = [
        ['href' => 'dashboard.php', 'key' => 'dashboard', 'label' => 'Dashboard', 'icon' => <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
</svg>
SVG],
        ['href' => 'setoran.php', 'key' => 'setoran', 'label' => 'Setoran', 'icon' => <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
</svg>
SVG],
        ['href' => 'logout.php', 'key' => 'logout', 'label' => 'Keluar', 'icon' => <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
</svg>
SVG],
    ];

    echo '<nav class="fixed bottom-0 inset-x-0 z-40 bg-amber-600 safe-area-pb shadow-[0_-2px_10px_rgba(0,0,0,.15)]">';
    echo '<div class="max-w-xl mx-auto flex">';
    foreach ($pages as $p) {
        $isActive = ($activePage === $p['key']);
        $cls = $isActive
            ? 'flex-1 flex flex-col items-center py-2 gap-0.5 text-white bg-amber-700'
            : 'flex-1 flex flex-col items-center py-2 gap-0.5 text-amber-100 hover:bg-amber-700 transition-colors';
        $confirmAttr = $p['key'] === 'logout' ? 'onclick="return confirm(\'Keluar dari akun ini?\')"' : '';
        echo <<<HTML
  <a href="{$p['href']}" class="{$cls}" {$confirmAttr}>
    {$p['icon']}
    <span class="text-[10px] font-medium">{$p['label']}</span>
  </a>
HTML;
    }
    echo '</div></nav>';
    echo '<div class="h-16 pb-safe"></div>';
}

function renderFoot(): void
{
    echo '</body></html>';
}

function idr(float $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function kg(float $n): string
{
    return number_format($n, 2, ',', '.') . ' kg';
}
