<?php
// export_csv_filtered.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$applications_file = __DIR__ . '/applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

$selected = [];
if (isset($_GET['rows'])) {
    $rows = json_decode($_GET['rows'], true);
    if (is_array($rows)) {
        foreach ($rows as $r) { $i = intval($r); if (isset($applications[$i])) $selected[] = $applications[$i]; }
    }
} else {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $jobFilter = isset($_GET['job']) ? trim($_GET['job']) : '';

    foreach ($applications as $a) {
        $full = trim(($a['first_name'] ?? '') . ' ' . ($a['middle_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
        $email = $a['email'] ?? '';
        $job = $a['job_title'] ?? '';
        $status = $a['status'] ?? '';
        $match = true;
        if ($search) {
            $s = strtolower($search);
            if (strpos(strtolower($full), $s) === false &&
                strpos(strtolower($email), $s) === false &&
                strpos(strtolower($job), $s) === false) $match = false;
        }
        if ($statusFilter && strtolower($status) !== strtolower($statusFilter)) $match = false;
        if ($jobFilter && strtolower($job) !== strtolower($jobFilter)) $match = false;
        if ($match) $selected[] = $a;
    }
}

if (empty($selected)) $selected = $applications;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=filtered_applicants.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['#', 'First Name', 'Middle Name', 'Last Name', 'Email', 'Phone', 'Job Title', 'Department', 'Qualification', 'Internal Status', 'Status', 'Reason', 'Date']);

foreach ($selected as $i => $a) {
    fputcsv($out, [
        $i + 1,
        $a['first_name'] ?? '',
        $a['middle_name'] ?? '',
        $a['last_name'] ?? '',
        $a['email'] ?? '',
        $a['phone'] ?? '',
        $a['job_title'] ?? '',
        $a['department'] ?? '',
        $a['academic_qualification'] ?? '',
        $a['internal_status'] ?? '',
        $a['status'] ?? '',
        str_replace(["\r", "\n"], [' ', ' '], $a['reason'] ?? ''),
        $a['date'] ?? ''
    ]);
}

fclose($out);
exit;
