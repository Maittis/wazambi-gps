<?php
/**
 * Wazambi GPS — One-time installer
 * Visit /admin/install.php once to set up the database and admin account.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wazambi_agents');

$step   = $_GET['step']   ?? 'start';
$result = '';

// ── Run installation ─────────────────────────────────────────────
if ($step === 'run') {
    try {
        // 1. Create database and tables
        $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');

        $sql = file_get_contents(__DIR__ . '/setup.sql');
        // Split on semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (strpos($stmt, 'CREATE DATABASE') !== false || strpos($stmt, 'USE ') !== false) {
                continue; // Already handled
            }
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        // 2. Create admin user (default: admin / wazambi2026)
        $user = 'admin';
        $pass = 'wazambi2026';
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $check = $pdo->query("SELECT COUNT(*) FROM admins WHERE username = '$user'")->fetchColumn();
        if ($check == 0) {
            $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)")->execute([$user, $hash]);
        }

        $result = '<div class="card ok">
            <h2>✓ Installation Complete</h2>
            <p>Database <strong>' . DB_NAME . '</strong> created with default tables.</p>
            <p><strong>Admin login:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>wazambi2026</code>
            </p>
            <p class="warn">⚠ Change this password immediately after your first login.</p>
            <a class="btn" href="login.php">Open Admin Login →</a>
        </div>';

    } catch (PDOException $e) {
        $result = '<div class="card err"><h2>✗ Installation failed</h2><pre>' . h($e->getMessage()) . '</pre></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Wazambi GPS — Installer</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Inter,-apple-system,system-ui,sans-serif;background:#0A1E3C;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .wrap{max-width:560px;width:100%}
  h1{font-size:28px;font-weight:900;margin-bottom:8px}
  p.sub{color:#8FA2BC;margin-bottom:32px}
  .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:32px;margin-bottom:16px}
  .card h2{font-size:22px;margin-bottom:12px}
  .card p{color:#B8C6DA;margin-bottom:12px;line-height:1.6}
  code{background:rgba(255,180,0,.15);color:#FFB400;padding:2px 8px;border-radius:6px;font-size:14px}
  .warn{color:#FFB400;font-weight:700}
  .btn{display:inline-block;background:#FFB400;color:#0A1E3C;font-weight:800;font-size:14px;padding:14px 28px;border-radius:10px;text-decoration:none;margin-top:12px}
  .btn:hover{background:#FFC93C}
  .ok{border-color:#10B981}
  .ok h2{color:#10B981}
  .err{border-color:#EF4444}
  .err h2{color:#EF4444}
  pre{background:rgba(0,0,0,.3);padding:12px;border-radius:8px;font-size:13px;overflow-x:auto;color:#F87171}
</style>
</head>
<body>
<div class="wrap">
  <h1>Wazambi GPS — Installer</h1>
  <p class="sub">Set up the application database and admin account.</p>

  <?php if ($step === 'start'): ?>
    <div class="card">
      <h2>Ready to install</h2>
      <p>This will create the <code>wazambi_agents</code> database, build all tables, and create a default admin account:<br>
        <code>admin</code> / <code>wazambi2026</code>
      </p>
      <p class="warn">Only run this once. Running it again is safe (tables are dropped and recreated).</p>
      <a class="btn" href="install.php?step=run">Run Installer →</a>
    </div>

  <?php else: ?>
    <?= $result ?>
    <a class="btn" href="install.php" style="background:rgba(255,255,255,.1);color:#fff">Run Again</a>
  <?php endif; ?>
</div>
</body>
</html>
