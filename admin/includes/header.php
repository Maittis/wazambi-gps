<?php
/**
 * Wazambi GPS — Admin header (included in every admin page)
 */
require_once __DIR__ . '/../config.php';
require_login();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Wazambi GPS — Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">WAZAMBI<span>GPS</span></div>
    <nav>
      <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <span class="icon">📊</span> Dashboard
      </a>
      <a href="index.php?status=pending" class="<?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : '' ?>">
        <span class="icon">⏳</span> Pending
      </a>
      <a href="index.php?status=shortlisted" class="<?= isset($_GET['status']) && $_GET['status'] === 'shortlisted' ? 'active' : '' ?>">
        <span class="icon">⭐</span> Shortlisted
      </a>
      <a href="index.php?status=contacted" class="<?= isset($_GET['status']) && $_GET['status'] === 'contacted' ? 'active' : '' ?>">
        <span class="icon">📞</span> Contacted
      </a>
      <a href="index.php?status=accepted" class="<?= isset($_GET['status']) && $_GET['status'] === 'accepted' ? 'active' : '' ?>">
        <span class="icon">✅</span> Accepted
      </a>
      <a href="index.php?status=rejected" class="<?= isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'active' : '' ?>">
        <span class="icon">❌</span> Rejected
      </a>
      <a href="export.php" style="margin-top:8px;">
        <span class="icon">📥</span> Export CSV
      </a>
      <a href="media.php">
        <span class="icon">🖼️</span> Media manager
      </a>
      <a href="logout.php" style="margin-top:auto;">
        <span class="icon">🚪</span> Logout
      </a>
    </nav>
    <div class="side-foot">Wazambi GPS Agent Panel</div>
  </aside>

  <!-- Mobile toggle -->
  <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <h1>Agent Applications</h1>
      <div class="topbar-right">
        <a href="../index.html" target="_blank">View landing page ↗</a>
        <a href="logout.php" style="color:#EF4444;">Logout</a>
      </div>
    </div>
    <div class="content">
