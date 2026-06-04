<?php
// ─── CariGalon — Database Connection ───────────────────────────────────────
// Ubah kredensial sesuai setup lokal kamu (default: XAMPP)

define('DB_HOST', 'localhost');
define('DB_NAME', 'carigalon');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode([
        'error'   => true,
        'message' => 'Koneksi database gagal: ' . $e->getMessage(),
    ]));
}

// ─── Flash Message Helpers ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash(): void {
    $flash = get_flash();
    if (!$flash) return;
    $color = $flash['type'] === 'success'
        ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
        : 'bg-red-50 border-red-300 text-red-800';
    $icon  = $flash['type'] === 'success' ? 'check_circle' : 'error';
    echo <<<HTML
    <div class="flex items-center gap-3 px-5 py-4 mb-6 rounded-2xl border {$color} text-sm font-medium">
        <span class="material-symbols-outlined text-[20px]">{$icon}</span>
        <span>{$flash['message']}</span>
    </div>
    HTML;
}

// ─── Sanitize Helper ────────────────────────────────────────────────────────
function h(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// ─── Photo URL Path Resolver Helper ─────────────────────────────────────────
function get_foto_url(?string $path): string {
    if (!$path) {
        return '';
    }
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    $rootDir = realpath(__DIR__);
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    
    $relativePrefix = '';
    if ($scriptDir !== $rootDir) {
        $relativePrefix = '../';
    } else {
        $relativePrefix = './';
    }
    
    return $relativePrefix . ltrim($path, '/\\');
}
