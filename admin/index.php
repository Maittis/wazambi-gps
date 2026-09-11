<?php
require_once __DIR__ . '/includes/header.php';

// ── Filters ─────────────────────────────────────────────────────
$statusFilter = $_GET['status']  ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

$allowedStatuses = ['','pending','shortlisted','contacted','accepted','rejected'];
if (!in_array($statusFilter, $allowedStatuses)) $statusFilter = '';

// ── Stats ───────────────────────────────────────────────────────
$countAll        = db()->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$countPending    = db()->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$countShortlist  = db()->query("SELECT COUNT(*) FROM applications WHERE status='shortlisted'")->fetchColumn();
$countContacted  = db()->query("SELECT COUNT(*) FROM applications WHERE status='contacted'")->fetchColumn();
$countAccepted   = db()->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn();
$countRejected   = db()->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();

// ── Build query ─────────────────────────────────────────────────
$where  = [];
$params = [];

if ($statusFilter) {
    $where[]   = 'status = :status';
    $params[':status'] = $statusFilter;
}
if ($search !== '') {
    $where[]   = '(fullname LIKE :q OR email LIKE :q2 OR whatsapp LIKE :q3 OR town LIKE :q4)';
    $params[':q']  = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total rows (for pagination)
$countSql = "SELECT COUNT(*) FROM applications $whereSql";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch rows
$sql = "SELECT id, fullname, whatsapp, email, town, status, weekly_customers, created_at
        FROM applications $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$flash = flash('msg');
?>
<?php if ($flash): ?>
  <div class="flash flash-ok"><?= h($flash) ?></div>
<?php endif; ?>

<!-- ── Stats ─────────────────────────────────────────────────── -->
<div class="stats">
  <div class="stat-card"><div class="stat-num"><?= $countAll ?></div><div class="stat-label">Total</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#FFB400"><?= $countPending ?></div><div class="stat-label">Pending</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#17A8FF"><?= $countShortlist ?></div><div class="stat-label">Shortlisted</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#8B5CF6"><?= $countContacted ?></div><div class="stat-label">Contacted</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#10B981"><?= $countAccepted ?></div><div class="stat-label">Accepted</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#EF4444"><?= $countRejected ?></div><div class="stat-label">Rejected</div></div>
</div>

<!-- ── Table ─────────────────────────────────────────────────── -->
<div class="panel">
  <div class="panel-head">
    <h2>
      <?php if ($statusFilter): ?>
        <?= ucfirst($statusFilter) ?> applications
      <?php else: ?>
        All applications
      <?php endif; ?>
      <span style="font-weight:500;color:#8FA2BC;font-size:14px;margin-left:8px;">
        (<?= $totalRows ?>)
      </span>
    </h2>
    <div class="filters">
      <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <?php if ($statusFilter): ?>
          <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search name, email, town…">
        <button class="btn btn-y" style="padding:9px 16px;font-size:13px">Search</button>
        <?php if ($search): ?>
          <a href="?<?= $statusFilter ? 'status='.$statusFilter : '' ?>" style="font-size:13px;color:#EF4444">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div style="padding:48px;text-align:center;color:#8FA2BC;font-size:15px;">No applications found.</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email / WhatsApp</th>
        <th>Town</th>
        <th>Vehicles/wk</th>
        <th>Status</th>
        <th>Applied</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="view.php?id=<?= $r['id'] ?>"><?= $r['id'] ?></a></td>
        <td><a href="view.php?id=<?= $r['id'] ?>"><?= h($r['fullname']) ?></a></td>
        <td>
          <?= h($r['email']) ?><br>
          <span style="color:#8FA2BC;font-size:12.5px"><?= h($r['whatsapp']) ?></span>
        </td>
        <td><?= h($r['town']) ?></td>
        <td style="text-align:center"><?= (int)$r['weekly_customers'] ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td style="white-space:nowrap;color:#8FA2BC;font-size:13px">
          <?= date('d M Y', strtotime($r['created_at'])) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>&status=<?= h($statusFilter) ?>&q=<?= h($search) ?>">← Prev</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++): ?>
      <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
      <?php else: ?>
        <a href="?page=<?= $i ?>&status=<?= h($statusFilter) ?>&q=<?= h($search) ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>&status=<?= h($statusFilter) ?>&q=<?= h($search) ?>">Next →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
