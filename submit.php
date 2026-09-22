<?php
/**
 * Wazambi GPS — Form submission handler
 * The landing page form POSTs here.
 * After saving, redirects back with a success/ error message.
 */
require_once __DIR__ . '/admin/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}

// ── Collect fields ──────────────────────────────────────────────
$fields = [
    'fullname'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'whatsapp'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'email'        => FILTER_VALIDATE_EMAIL,
    'town'         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'age_18'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'smartphone'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'sales_experience' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'experience_detail' => FILTER_UNSAFE_RAW,
    'sales_methods'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'reachable_businesses' => FILTER_UNSAFE_RAW,
    'weekly_customers'  => FILTER_VALIDATE_INT,
    'first_five'  => FILTER_UNSAFE_RAW,
    'areas_covered' => FILTER_UNSAFE_RAW,
    'first_seven_days' => FILTER_UNSAFE_RAW,
    'why_you'     => FILTER_UNSAFE_RAW,
    'complete_onboarding' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'understands_commission' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'approved_info_prices' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'info_accurate' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'agree_declaration' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
];

$data = [];
foreach ($fields as $key => $filter) {
    // Multi-value checkbox fields (e.g. sales_methods) arrive as arrays
    if (isset($_POST[$key]) && is_array($_POST[$key])) {
        $vals = array_map(function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        }, $_POST[$key]);
        $vals = array_values(array_filter($vals, function ($v) {
            return $v !== '';
        }));
        $data[$key] = implode(', ', $vals);
        continue;
    }
    $data[$key] = filter_input(INPUT_POST, $key, $filter);
}

// ── Validate required fields ────────────────────────────────────
$required = ['fullname','whatsapp','email','town','age_18','smartphone',
             'sales_experience','sales_methods','reachable_businesses',
             'weekly_customers','first_five','areas_covered','first_seven_days',
             'why_you','complete_onboarding','understands_commission',
             'approved_info_prices','info_accurate','agree_declaration'];

$errors = [];
foreach ($required as $f) {
    if (empty($data[$f]) || $data[$f] === '' || $data[$f] === false) {
        $errors[] = ucfirst(str_replace('_', ' ', $f)) . ' is required.';
    }
}
if ($data['email'] === false) {
    $errors[] = 'Please enter a valid email address.';
}
if ($data['age_18'] !== 'Yes') {
    $errors[] = 'You must be 18 years or older.';
}
if ($data['complete_onboarding'] !== 'Yes') {
    $errors[] = 'You must be able to complete the Agent Guide and onboarding videos.';
}
if ($data['understands_commission'] !== 'Yes') {
    $errors[] = 'You must confirm you understand this is commission-based.';
}
if ($data['approved_info_prices'] !== 'Yes') {
    $errors[] = 'You must agree to use Wazambi\'s approved information and prices.';
}
if ($data['info_accurate'] !== 'Yes') {
    $errors[] = 'You must confirm your information is accurate.';
}
if ($data['agree_declaration'] !== 'Yes') {
    $errors[] = 'You must accept the application declaration.';
}

// ── Handle CV upload ────────────────────────────────────────────
$cv_filename = null;
if (!empty($_FILES['cv']['tmp_name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['cv']['tmp_name'];
    $size = $_FILES['cv']['size'];
    $type = $_FILES['cv']['type'];
    $name = $_FILES['cv']['name'];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        $errors[] = 'CV must be a PDF file.';
    }
    if ($size > MAX_CV_SIZE) {
        $errors[] = 'CV file must be under 5 MB.';
    }
    if ($type !== 'application/pdf' && $type !== '') {
        $errors[] = 'CV file type not accepted.';
    }
} else {
    $errors[] = 'Please upload your CV in PDF format.';
}

// ── Return errors if any ────────────────────────────────────────
if ($errors) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'errors' => $errors]));
}

// ── Move CV and insert record ───────────────────────────────────
if (!is_dir(CV_UPLOAD_DIR)) {
    mkdir(CV_UPLOAD_DIR, 0755, true);
}

$cv_filename = 'cv_' . time() . '_' . bin2hex(random_bytes(6)) . '.pdf';
move_uploaded_file($tmp, CV_UPLOAD_DIR . $cv_filename);

$sql = "INSERT INTO applications
    (fullname, whatsapp, email, town, age_18, smartphone, sales_experience,
     experience_detail, sales_methods, reachable_businesses, weekly_customers,
     first_five, areas_covered, first_seven_days, why_you,
     complete_onboarding, understands_commission, approved_info_prices,
     info_accurate, cv_filename, agree_declaration)
    VALUES
    (:fullname,:whatsapp,:email,:town,:age_18,:smartphone,:sales_experience,
     :experience_detail,:sales_methods,:reachable_businesses,:weekly_customers,
     :first_five,:areas_covered,:first_seven_days,:why_you,
     :complete_onboarding,:understands_commission,:approved_info_prices,
     :info_accurate,:cv_filename,:agree_declaration)";

$stmt = db()->prepare($sql);
$stmt->execute([
    ':fullname'              => $data['fullname'],
    ':whatsapp'              => $data['whatsapp'],
    ':email'                 => $data['email'],
    ':town'                  => $data['town'],
    ':age_18'                => $data['age_18'],
    ':smartphone'            => $data['smartphone'],
    ':sales_experience'      => $data['sales_experience'],
    ':experience_detail'     => $data['experience_detail'],
    ':sales_methods'         => $data['sales_methods'],
    ':reachable_businesses'  => $data['reachable_businesses'],
    ':weekly_customers'      => $data['weekly_customers'],
    ':first_five'            => $data['first_five'],
    ':areas_covered'         => $data['areas_covered'],
    ':first_seven_days'      => $data['first_seven_days'],
    ':why_you'               => $data['why_you'],
    ':complete_onboarding'   => $data['complete_onboarding'],
    ':understands_commission'=> $data['understands_commission'],
    ':approved_info_prices'  => $data['approved_info_prices'],
    ':info_accurate'         => $data['info_accurate'],
    ':cv_filename'           => $cv_filename,
    ':agree_declaration'     => $data['agree_declaration'],
]);

echo json_encode(['ok' => true, 'message' => 'Application received']);
