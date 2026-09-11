<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === '' || $pass === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, username, password FROM admins WHERE username = ?');
            $stmt->execute([$user]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($pass, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database not found. Run the installer first: <a href="install.php">install.php</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Wazambi GPS — Admin Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Inter,-apple-system,system-ui,sans-serif;
    background:radial-gradient(800px 400px at 40% -100px,rgba(23,168,255,.15),transparent 55%),#0A1E3C;
    color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .box{width:100%;max-width:420px;background:#fff;border-radius:18px;padding:44px 36px;
    box-shadow:0 24px 60px rgba(0,0,0,.4);text-align:center}
  .logo{font-size:26px;font-weight:900;margin-bottom:4px;color:#0A1E3C}
  .logo span{color:#FFB400}
  .sub{color:#8FA2BC;font-size:14px;margin-bottom:28px}
  label{display:block;text-align:left;font-size:13px;font-weight:700;margin-bottom:6px;color:#0A1E3C}
  input[type="text"],input[type="password"]{
    width:100%;padding:13px 14px;border:1.5px solid #D8E0EE;border-radius:10px;
    font-size:15px;font-family:inherit;margin-bottom:18px;background:#F7F9FC;color:#0A1E3C}
  input:focus{outline:none;border-color:#FFB400;box-shadow:0 0 0 4px rgba(255,180,0,.18);background:#fff}
  .btn{width:100%;padding:14px;font-size:15px;font-weight:900;border:none;border-radius:10px;
    background:#FFB400;color:#0A1E3C;cursor:pointer;transition:background .15s}
  .btn:hover{background:#FFC93C}
  .err{background:#FEE2E2;color:#991B1B;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;text-align:left}
  .err a{font-weight:800;text-decoration:underline}
</style>
</head>
<body>
<div class="box">
  <div class="logo">WAZAMBI<span>GPS</span></div>
  <p class="sub">Admin Panel</p>

  <?php if ($error): ?>
    <div class="err"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="on">
    <label for="u">Username</label>
    <input type="text" id="u" name="username" required autofocus>

    <label for="p">Password</label>
    <input type="password" id="p" name="password" required>

    <button type="submit" class="btn">Sign in →</button>
  </form>
</div>
</body>
</html>
