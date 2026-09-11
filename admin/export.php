<?php
/**
 * Wazambi GPS — CSV Export
 * Exports filtered applications as a downloadable CSV file.
 */
require_once __DIR__ . '/config.php';
require_login();

$status = $_GET['status'] ?? '';
$search  = trim($_GET['q']   ?? '');

$where  = [];
$params = [];

if ($status && in_array($status, ['pending','shortlisted','contacted','accepted','rejected'])) {
    $where[]   = 'status = :status';
    $params[':status'] = $status;
}
if ($search !== '') {
    $where[]   = '(fullname LIKE :q OR email LIKE :q2 OR whatsapp LIKE :q3 OR town LIKE :q4)';
    $params[':q']  = "%$search%";
    $params[':q2'] = "%$search%";
    $params[':q3'] = "%$search%";
    $params[':q4'] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id, fullname, whatsapp, email, town, age_18, smartphone,
        sales_experience, experience_detail, sales_methods, knows_vehicles,
        weekly_customers, first_five, why_you, attend_both, travel_own_cost,
        understands_commission, status, notes, created_at
        FROM applications $whereSql ORDER BY created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'wazambi_agents_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$fp = fopen('php://output', 'w');

// BOM for Excel UTF-8
fwrite($fp, "\xEF\xBB\xBF");

$fheaders = ['ID','Full Name','WhatsApp','Email','Town','18+','Smartphone',
             'Sales Experience','Experience Detail','Sales Methods','Knows Vehicle Owners',
             'Weekly Customers','First 5 Customers','Why Select You','Attend Both',
             'Travel Own Cost','Understands Commission','Status','Notes','Applied'];
fputcsv($fp, $fheaders);

foreach ($rows as $r) {
    fputcsv($fp, [
        $r['id'],
        $r['fullname'],
        $r['whatsapp'],
        $r['email'],
        $r['town'],
        $r['age_18'],
        $r['smartphone'],
        $r['sales_experience'],
        $r['experience_detail'],
        $r['sales_methods'],
        $r['knows_vehicles'],
        $r['weekly_customers'],
        $r['first_five'],
        $r['why_you'],
        $r['attend_both'],
        $r['travel_own_cost'],
        $r['understands_commission'],
        $r['status'],
        $r['notes'],
        $r['created_at'],
    ]);
}

fclose($fp);
exit;
