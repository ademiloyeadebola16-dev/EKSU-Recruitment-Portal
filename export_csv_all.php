<?php
// export_csv_all.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$applications_file = __DIR__ . '/applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=all_applicants.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['#', 'First Name', 'Middle Name', 'Last Name', 'Email', 'Phone', 'Job Title', 'Department', 'Qualification', 'Internal Status', 'Status', 'Reason', 'Date']);

foreach ($applications as $i => $a) {
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
