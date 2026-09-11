<?php
require_once __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

// ── Save notes ──────────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'save_notes') {
    $notes = filter_input(INPUT_POST, 'notes', FILTER_UNSAFE_RAW);
    $stmt = db()->prepare('UPDATE applications SET notes = ? WHERE id = ?');
    $stmt->execute([$notes, $id]);
    set_flash('success', 'Notes saved.');
    header("Location: view.php?id=$id");
    exit;
}

// ── Update status ───────────────────────────────────────────────
$allowed = ['pending','shortlisted','contacted','accepted','rejected'];
$newStatus = $_POST['status'] ?? '';

if (!in_array($newStatus, $allowed)) {
    set_flash('error', 'Invalid status.');
    header("Location: index.php");
    exit;
}

$stmt = db()->prepare('UPDATE applications SET status = ? WHERE id = ?');
$stmt->execute([$newStatus, $id]);

$from = $_POST['from'] ?? 'index';
if ($from === 'view') {
    set_flash('success', 'Status changed to ' . ucfirst($newStatus) . '.');
    header("Location: view.php?id=$id");
} else {
    set_flash('success', 'Application #' . $id . ' updated to ' . ucfirst($newStatus) . '.');
    header('Location: index.php');
}
exit;
