<?php
/**
 * Wazambi GPS — Image & Video Manager
 * Upload / replace the media files used on the landing page.
 */
require_once __DIR__ . '/includes/header.php';

$baseDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR;   // project root
$imgDir  = $baseDir . 'images' . DIRECTORY_SEPARATOR;
$vidDir  = $baseDir . 'media'  . DIRECTORY_SEPARATOR;

foreach ([$imgDir, $vidDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

define('MAX_IMG', 15 * 1024 * 1024);  // 15 MB images
define('MAX_VID', 300 * 1024 * 1024); // 300 MB video

function sAfx(string $s): string {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $s);
}

$imgExts  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$vidExts  = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];

$imageSlots = [
    'hero-video-poster.jpg' => 'Video poster (shows before play)',
    'presenting.jpg'        => 'Sec 3 — Someone presenting Wazambi GPS',
    'agents-learning.jpg'   => 'Sec 3 — Agents learning together',
    'gps-installation.jpg'  => 'Sec 3 — A GPS installation',
    'mobile-platform.jpg'   => 'Sec 3 — Wazambi mobile platform',
    'shop-office.jpg'       => 'Sec 9 — Your shop or office',
    'technician-install.jpg'=> 'Sec 9 — Technicians installing trackers',
    'fleet-vehicles.jpg'    => 'Sec 9 — Fleet vehicles',
    'gps-app.jpg'           => 'Sec 9 — Your GPS application',
    'team.jpg'              => 'Sec 9 — Your team',
];

$videoSlots = [
    'wazambi-promo.mp4' => 'Hero promotional video',
];

// ── Handle uploads ──────────────────────────────────────────────
$msg = null; $msgType = 'ok'; // 'ok' | 'err'
$targetFile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slot_key'])) {
    $slotKey = basename($_POST['slot_key']);            // strip any path
    $file    = $_FILES['upload'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Upload failed (no file received or it exceeded server limits).';
        $msgType = 'err';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isImage  = isset($imageSlots[$slotKey]);
        $isVideo  = isset($videoSlots[$slotKey]);

        if (!$isImage && !$isVideo) {
            $msg = 'Unknown media slot.';
            $msgType = 'err';
        } elseif ($isImage && !isset($imgExts[$ext])) {
            $msg = 'Images must be JPG, PNG or WEBP.';
            $msgType = 'err';
        } elseif ($isVideo && !isset($vidExts[$ext])) {
            $msg = 'Video must be MP4, WEBM or MOV.';
            $msgType = 'err';
        } elseif ($isImage && $file['size'] > MAX_IMG) {
            $msg = 'Image too large — max 15 MB.';
            $msgType = 'err';
        } elseif ($isVideo && $file['size'] > MAX_VID) {
            $msg = 'Video too large — max 300 MB.';
            $msgType = 'err';
        } else {
            // All image slots in the landing page are referenced as .jpg — normalise
            // whatever format we receive into the canonical .jpg filename so the page
            // shows the upload without any HTML changes.
            $canonical = $slotKey;
            if ($isImage) {
                $canonical = pathinfo($slotKey, PATHINFO_FILENAME) . '.jpg';
            }

            $dest = ($isImage ? $imgDir : $vidDir) . $canonical;

            // Atomic replace: write temp then rename
            $tmp = $baseDir . '.media_tmp_' . bin2hex(random_bytes(6));

            $saved = false;
            if ($isImage) {
                // Convert to JPG via GD
                $img = null;
                switch ($ext) {
                    case 'png':  $img = @imagecreatefrompng($file['tmp_name']); break;
                    case 'webp': $img = @imagecreatefromwebp($file['tmp_name']); break;
                    case 'jpg':
                    case 'jpeg': $img = @imagecreatefromjpeg($file['tmp_name']); break;
                }
                if ($img) {
                    imagejpeg($img, $tmp, 90);
                    imagedestroy($img);
                    $saved = is_file($tmp);
                }
            } else {
                $saved = move_uploaded_file($file['tmp_name'], $tmp);
            }

            if ($saved) {
                @unlink($dest);                 // remove old
                rename($tmp, $dest);            // move into place
                $msg = 'Uploaded successfully: <strong>' . $canonical . '</strong>';
                $msgType = 'ok';
                $targetFile = $canonical;
            } else {
                @unlink($tmp);
                $msg = 'Could not process the file — upload a valid image/video.';
                $msgType = 'err';
            }
        }
    }
}

// ── Helper: current file info ───────────────────────────────────
function slotInfo(string $dir, string $file): ?array {
    $path = $dir . $file;
    if (!is_file($path)) return null;
    $size = filesize($path);
    $unit = ['B','KB','MB','GB'];
    $i = 0;
    while ($size >= 1024 && $i < 3) { $size /= 1024; $i++; }
    return ['bytes' => filesize($path), 'size' => round($size, 1) . ' ' . $unit[$i]];
}

function isImage(string $file): bool {
    $e = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($e, ['jpg','jpeg','png','webp']);
}

function findExisting(string $dir, string $slotKey): ?string {
    // slotKey may be 'photo.jpg'; also look for 'photo.png' / 'photo.webp'
    $name = pathinfo($slotKey, PATHINFO_FILENAME);
    foreach (['jpg','jpeg','png','webp'] as $e) {
        if (is_file($dir . $name . '.' . $e)) return $name . '.' . $e;
    }
    foreach (['mp4','webm','mov'] as $e) {
        if (is_file($dir . $name . '.' . $e)) return $name . '.' . $e;
    }
    return null;
}

// Build actual slot list (resolve canonical vs found extension)
$imageSlotsList = [];
foreach ($imageSlots as $key => $label) {
    $found = findExisting($imgDir, $key);
    $imageSlotsList[] = ['key' => $key, 'label' => $label, 'found' => $found];
}
$videoSlotsList = [];
foreach ($videoSlots as $key => $label) {
    $found = findExisting($vidDir, $key);
    $videoSlotsList[] = ['key' => $key, 'label' => $label, 'found' => $found];
}
?>
<style>
  .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px}
  .media-card{background:#fff;border:1px solid #E5E8F1;border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
  .media-preview{height:150px;background:#F7F9FC;display:flex;align-items:center;justify-content:center;overflow:hidden;border-bottom:1px solid #E5E8F1;position:relative}
  .media-preview img{width:100%;height:100%;object-fit:cover}
  .media-preview .no-file{color:#B9C6DC;font-size:13px;font-weight:600;display:flex;flex-direction:column;align-items:center;gap:6px}
  .media-preview video{width:100%;height:100%;object-fit:cover}
  .media-body{padding:16px}
  .media-body h3{font-size:14px;font-weight:800;margin-bottom:2px}
  .media-body p{font-size:12px;color:#8FA2BC;margin-bottom:10px}
  .media-meta{font-size:11.5px;color:#52698F;margin-bottom:12px}
  .media-meta .tag{display:inline-block;padding:3px 9px;border-radius:12px;font-weight:800;font-size:10.5px;text-transform:uppercase;letter-spacing:.04em}
  .tag-ok{background:#D1FAE5;color:#065F46}
  .tag-no{background:#FEE2E2;color:#991B1B}
  .media-form label{display:block;font-size:12.5px;font-weight:700;margin-bottom:6px}
  .media-form input[type="file"]{width:100%;font-size:12px;padding:8px;border:1.5px dashed #D8E0EE;border-radius:8px;background:#F7F9FC;cursor:pointer}
  .media-form input[type="file"]:hover{border-color:var(--yellow)}
  .media-form button{margin-top:10px;width:100%;padding:10px;border:none;border-radius:8px;background:var(--yellow);color:var(--navy);font-weight:800;font-size:13px;cursor:pointer}
  .media-form button:hover{background:#FFC93C}
  .msg{padding:14px 20px;border-radius:10px;font-size:14px;font-weight:600;margin-bottom:20px}
  .msg-ok{background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7}
  .msg-err{background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5}
  .mut{color:#8FA2BC;font-size:12.5px;margin-bottom:24px}
</style>

<a href="index.php" class="back" style="display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#8FA2BC;margin-bottom:20px">← Back to dashboard</a>

<div class="panel" style="margin-bottom:24px;">
  <div class="panel-head">
    <h2>🖼️ Landing Page Media</h2>
    <span class="mut">Upload or replace each image / video. The landing page reads these automatically.</span>
  </div>
</div>

<?php if ($msg): ?>
  <div class="msg msg-<?= $msgType ?>"><?= $msg ?></div>
<?php endif; ?>

<!-- ══════════ IMAGES ══════════ -->
<h2 style="font-size:17px;font-weight:800;margin-bottom:6px">Images</h2>
<p class="mut">Recommended: 16:9, min 1200px wide, JPG or PNG. Max 15 MB each.</p>

<div class="media-grid" style="margin-bottom:36px;">
<?php foreach ($imageSlotsList as $slot): ?>
  <div class="media-card">
    <div class="media-preview">
      <?php if ($slot['found']): ?>
        <img src="../images/<?= h($slot['found']) ?>" alt="<?= h($slot['key']) ?>">
      <?php else: ?>
        <img src="https://images.unsplash.com/photo-1494173853739-c21f58b16055?auto=format&fit=crop&w=800&q=80" alt="Placeholder <?= h($slot['key']) ?>">
      <?php endif; ?>
    </div>
    <div class="media-body">
      <h3><code><?= h($slot['key']) ?></code></h3>
      <p><?= h($slot['label']) ?></p>
      <?php
        $info = $slot['found'] ? slotInfo($imgDir, $slot['found']) : null;
      ?>
      <div class="media-meta">
        <?php if ($info): ?>
          <span class="tag tag-ok">✔ uploaded</span> · <?= h($info['size']) ?>
        <?php else: ?>
          <span class="tag tag-no">✖ missing</span>
        <?php endif; ?>
      </div>
      <form class="media-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="slot_key" value="<?= h($slot['key']) ?>">
        <label for="f-<?= pathinfo($slot['key'], PATHINFO_FILENAME) ?>">Replace file</label>
        <input type="file" name="upload" id="f-<?= sAfx($slot['key']) ?>" accept=".jpg,.jpeg,.png,.webp" required>
        <button type="submit">Upload →</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- ══════════ VIDEO ══════════ -->
<h2 style="font-size:17px;font-weight:800;margin-bottom:6px">Hero video</h2>
<p class="mut">Recommended: MP4 (H.264), horizontal, max 300 MB. The browser streams it, so larger files may take long to load for visitors.</p>

<div class="media-grid">
<?php foreach ($videoSlotsList as $slot): ?>
  <div class="media-card">
    <div class="media-preview">
      <?php if ($slot['found']): ?>
        <video src="../media/<?= h($slot['found']) ?>" controls></video>
      <?php else: ?>
        <div class="no-file"><span>🎬</span> not uploaded yet</div>
      <?php endif; ?>
    </div>
    <div class="media-body">
      <h3><code><?= h($slot['key']) ?></code></h3>
      <p><?= h($slot['label']) ?></p>
      <?php
        $info = $slot['found'] ? slotInfo($vidDir, $slot['found']) : null;
      ?>
      <div class="media-meta">
        <?php if ($info): ?>
          <span class="tag tag-ok">✔ uploaded</span> · <?= h($info['size']) ?>
        <?php else: ?>
          <span class="tag tag-no">✖ missing</span>
        <?php endif; ?>
      </div>
      <form class="media-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="slot_key" value="<?= h($slot['key']) ?>">
        <label>Replace video</label>
        <input type="file" name="upload" accept=".mp4,.webm,.mov" required>
        <button type="submit">Upload →</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>