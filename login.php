<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';

startSession();

// Sudah login → langsung ke dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error   = '';
$success = isset($_GET['out']) ? 'Anda telah berhasil keluar.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan password tidak boleh kosong.';
    } else {
        $res = apiCall('login', 'POST', ['email' => $email, 'password' => $password]);

        if ($res['ok'] && !empty($res['data']['token'])) {
            $d = $res['data'];
            saveSession(
                $d['token'],
                $d['nama']        ?? 'Petani',
                $d['kode_petani'] ?? '-',
                (int) ($d['petani_id'] ?? 0),
            );
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            $error = match ($res['code']) {
                401     => 'Email atau password salah.',
                403     => 'Akun belum disetujui atau bukan akun petani.',
                422     => 'Email atau password tidak valid.',
                0       => 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
                default => 'Login gagal (kode: ' . $res['code'] . '). Silakan coba lagi.',
            };
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="theme-color" content="#D97706">
<title>Masuk — <?= APP_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { min-height: 100dvh; }
  input:focus { outline: none; }
</style>
</head>
<body class="bg-gradient-to-b from-amber-50 to-white flex flex-col items-center justify-center min-h-screen px-4">

<div class="w-full max-w-sm fade-in">

  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-600 rounded-3xl shadow-lg mb-4">
      <span class="text-5xl">🌴</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-800"><?= APP_NAME ?></h1>
    <p class="text-sm text-gray-500 mt-1">Portal Rekap Setoran Petani</p>
  </div>

  <?php if ($success): ?>
  <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
    <h2 class="text-base font-semibold text-gray-700 mb-5">Masuk ke Akun Anda</h2>

    <form method="POST" autocomplete="on">
      <div class="mb-4">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </span>
          <input type="email" name="email" required autocomplete="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            placeholder="petani@email.com"
            class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition"
          >
        </div>
      </div>

      <div class="mb-6">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </span>
          <input type="password" name="password" id="pwdInput" required autocomplete="current-password"
            placeholder="••••••••"
            class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-100 transition"
          >
          <button type="button" onclick="togglePwd()" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
            <svg id="eyeIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit"
        class="w-full py-3 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-semibold rounded-xl transition shadow-sm">
        Masuk
      </button>
    </form>
  </div>

  <p class="text-center text-xs text-gray-400 mt-6">
    Lupa password? Hubungi administrator.
  </p>
</div>

<script>
function togglePwd() {
  const inp  = document.getElementById('pwdInput');
  const icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
  }
}
</script>
<style>
  @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
  .fade-in { animation: fadeIn .3s ease; }
</style>
</body>
</html>
