<?php
require_once __DIR__ . '/config.php';

/**
 * Panggil API backend e-Traceability.
 *
 * @param string      $endpoint  Bagian setelah API_BASE, misal 'profil', 'setoran?bulan=2025-08'
 * @param string      $method    GET | POST
 * @param array       $body      Body untuk POST (akan di-encode ke JSON)
 * @param string|null $token     Bearer token; null untuk endpoint publik
 * @return array{ ok: bool, code: int, data: mixed, message: string }
 */
function apiCall(string $endpoint, string $method = 'GET', array $body = [], ?string $token = null): array
{
    $url = API_BASE . $endpoint;
    $ch  = curl_init();

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    if ($token !== null) {
        $headers[] = "Authorization: Bearer $token";
    }

    $opts = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    curl_setopt_array($ch, $opts);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['ok' => false, 'code' => 0, 'data' => null, 'message' => "cURL error: $curlErr"];
    }

    $decoded = json_decode($raw, true);

    return [
        'ok'      => $httpCode >= 200 && $httpCode < 300,
        'code'    => $httpCode,
        'data'    => $decoded,
        'message' => $decoded['message'] ?? '',
    ];
}

/** Ambil nilai dalam array bersarang secara aman. */
function arr(array $a, string ...$keys)
{
    $cur = $a;
    foreach ($keys as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) return null;
        $cur = $cur[$k];
    }
    return $cur;
}
