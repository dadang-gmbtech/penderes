<?php
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('penderes_sess');
        session_start();
    }
}

function requireLogin(): void
{
    startSession();
    if (empty($_SESSION['token'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function isLoggedIn(): bool
{
    startSession();
    return !empty($_SESSION['token']);
}

function sessionToken(): ?string  { return $_SESSION['token']       ?? null; }
function sessionNama(): string    { return $_SESSION['nama']         ?? 'Petani'; }
function sessionKode(): string    { return $_SESSION['kode_petani']  ?? '-'; }

function saveSession(string $token, string $nama, string $kode, int $petaniId): void
{
    startSession();
    $_SESSION['token']      = $token;
    $_SESSION['nama']       = $nama;
    $_SESSION['kode_petani']= $kode;
    $_SESSION['petani_id']  = $petaniId;
}

function doLogout(): void
{
    startSession();
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/login.php?out=1');
    exit;
}
