<?php
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = db()->prepare('SELECT * FROM applications WHERE id = ?');
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) { header('Location: index.php'); exit; }

$success = flash('success');
?>
<style>
  .back{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#8FA2BC;margin-bottom:20px}
  .back:hover{color:var(--ink)}
</style>

<a class="back" href="index.php">← Back to list</a>

<?php if ($success): ?>
  <div class="flash flash-ok"><?= h($success) ?></div>
<?php endif; ?>

<!-- ── Header card ───────────────────────────────────────────── -->
<div class="panel" style="margin-bottom:24px;">
  <div style="padding:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
      <h2 style="font-size:22px;font-weight:900;"><?= h($app['fullname']) ?></h2>
      <p style="color:#8FA2BC;font-size:14px;margin-top:4px;">
        <?= h($app['town']) ?> · Applied <?= date('d M Y, g:i a', strtotime($app['created_at'])) ?>
      </p>
    </div>
    <?= status_badge($app['status']) ?>
  </div>

  <!-- Status actions -->
  <div class="actions-bar">
    <form method="POST" action="update_status.php" style="display:flex;gap:10px;flex-wrap:wrap;">
      <input type="hidden" name="id" value="<?= $app['id'] ?>">
      <input type="hidden" name="from" value="view">

      <button type="submit" name="status" value="shortlisted"
        class="btn btn-b" <?= $app['status'] === 'shortlisted' ? 'disabled' : '' ?>>
        ⭐ Shortlist
      </button>
      <button type="submit" name="status" value="contacted"
        class="btn btn-p" <?= $app['status'] === 'contacted' ? 'disabled' : '' ?>>
        📞 Mark contacted
      </button>
      <button type="submit" name="status" value="accepted"
        class="btn btn-g" <?= $app['status'] === 'accepted' ? 'disabled' : '' ?>>
        ✅ Accept
      </button>
      <button type="submit" name="status" value="rejected"
        class="btn btn-r" <?= $app['status'] === 'rejected' ? 'disabled' : '' ?>>
        ❌ Reject
      </button>
      <button type="submit" name="status" value="pending"
        class="btn btn-o" <?= $app['status'] === 'pending' ? 'disabled' : '' ?>>
        ↺ Reset to pending
      </button>
    </form>
  </div>
</div>

<!-- ── Details ───────────────────────────────────────────────── -->
<div class="panel" style="margin-bottom:24px;">
  <div style="padding:18px 24px;border-bottom:1px solid #E5E8F1;">
    <h3 style="font-size:16px;font-weight:800;">Application Details</h3>
  </div>
  <dl class="detail-grid">
    <dt>Full name</dt>
    <dd><strong><?= h($app['fullname']) ?></strong></dd>

    <dt>WhatsApp</dt>
    <dd><?= h($app['whatsapp']) ?></dd>

    <dt>Email</dt>
    <dd><a href="mailto:<?= h($app['email']) ?>"><?= h($app['email']) ?></a></dd>

    <dt>Town &amp; Province</dt>
    <dd><?= h($app['town']) ?></dd>

    <dt>18+ years old?</dt>
    <dd><?= $app['age_18'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Smartphone + internet?</dt>
    <dd><?= $app['smartphone'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Sales experience?</dt>
    <dd><?= $app['sales_experience'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Experience detail</dt>
    <dd><?= nl2br(h($app['experience_detail'])) ?: '—' ?></dd>

    <dt>Sales methods</dt>
    <dd><?= h($app['sales_methods']) ?></dd>

    <dt>Knows vehicle owners?</dt>
    <dd><?= $app['knows_vehicles'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Potential customers / week</dt>
    <dd><strong><?= (int)$app['weekly_customers'] ?></strong></dd>

    <dt>How to find first 5 customers</dt>
    <dd><?= nl2br(h($app['first_five'])) ?></dd>

    <dt>Why should Wazambi select you?</dt>
    <dd><?= nl2br(h($app['why_you'])) ?></dd>

    <dt>Can attend both days?</dt>
    <dd><?= $app['attend_both'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Travel at own cost?</dt>
    <dd><?= $app['travel_own_cost'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Understands commission-based?</dt>
    <dd><?= $app['understands_commission'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>Declaration accepted?</dt>
    <dd><?= $app['agree_declaration'] === 'Yes' ? '✅ Yes' : '❌ No' ?></dd>

    <dt>CV download</dt>
    <dd>
      <?php if ($app['cv_filename']): ?>
        <a href="../cv_uploads/<?= h($app['cv_filename']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;color:var(--blue);font-weight:700;">
          📄 Download CV (PDF)
        </a>
      <?php else: ?>
        —
      <?php endif; ?>
    </dd>

    <dt>Applied on</dt>
    <dd><?= date('d M Y, g:i a', strtotime($app['created_at'])) ?></dd>

    <dt>Last updated</dt>
    <dd><?= date('d M Y, g:i a', strtotime($app['updated_at'])) ?></dd>
  </dl>
</div>

<!-- ── Admin notes ───────────────────────────────────────────── -->
<div class="note-box">
  <h3>📝 Internal notes</h3>
  <form method="POST" action="update_status.php">
    <input type="hidden" name="id" value="<?= $app['id'] ?>">
    <textarea name="notes" placeholder="Add a note about this applicant…"><?= h($app['notes']) ?></textarea>
    <button type="submit" name="action" value="save_notes" class="btn btn-y">Save notes</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
