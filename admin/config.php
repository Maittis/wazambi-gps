<?php
/**
 * Wazambi GPS — Database configuration
 * Update these values if your MySQL credentials differ.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'wazambi_agents');
define('DB_USER', 'root');          // XAMPP default
define('DB_PASS', '');              // XAMPP default = empty
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Wazambi GPS Agent Program');
define('CV_UPLOAD_DIR', __DIR__ . '/../cv_uploads/');
define('MAX_CV_SIZE', 5 * 1024 * 1024); // 5 MB

session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function flash(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function set_flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function status_badge(string $status): string {
    $colors = [
        'pending'     => ['#FFB400', '#0A1E3C'],
        'shortlisted' => ['#17A8FF', '#fff'],
        'contacted'   => ['#8B5CF6', '#fff'],
        'accepted'    => ['#10B981', '#fff'],
        'rejected'    => ['#EF4444', '#fff'],
    ];
    [$bg, $fg] = $colors[$status] ?? ['#6B7280', '#fff'];
    return '<span class="badge" style="background:' . $bg . ';color:' . $fg . ';">'
         . ucfirst($status) . '</span>';
}
