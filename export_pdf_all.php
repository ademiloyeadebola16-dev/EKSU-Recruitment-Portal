<?php
// export_pdf_all.php
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload (TCPDF)
session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$applications_file = __DIR__ . '/applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

// create new PDF
$pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('EKSU Recruitment');
$pdf->SetAuthor('EKSU Recruitment');
$pdf->SetTitle('All Applicants');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Build HTML table with strict 100% column widths
$tbl  = '<h2 style="text-align:center;">All Applicants</h2>';
$tbl .= '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%;">';

$tbl .= '
<thead>
<tr style="background-color:#800000;color:#fff; text-align:center; vertical-align:middle;">
    <th width="12%">#</th>
    <th width="12%">Name</th>
    <th width="13%">Email</th>
    <th width="13%">Job</th>
    <th width="13%">Status</th>
    <th width="12%">Internal</th>
    <th width="13%">Remarks</th>
    <th width="12%">Date</th>
</tr>
</thead>
<tbody>';

foreach ($applications as $i => $app) {

    $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $email = $app['email'] ?? '';
    $job = $app['job_title'] ?? '';
    $status = ucfirst(strtolower($app['status'] ?? 'Pending'));
    $internal = $app['internal_status'] ?? '';
    $reason = strip_tags($app['reason'] ?? '');

    // Row background coloring
    if (strtolower($status) === 'qualified') {
        $bg = ' style="background-color:#e6ffea;"';
    } elseif (strtolower($status) === 'not qualified') {
        $bg = ' style="background-color:#ffe6e6;"';
    } elseif (strtolower($status) === 'disqualified') {
        $bg = ' style="background-color:#fff4e5;"';
    } else {
        $bg = ' style="background-color:#f2f2f2;"';
    }

    // Each <td> width matches <th>
    $tbl .= "<tr{$bg} style='vertical-align:middle;'>
        <td width='15%' style='text-align:center;'>" . ($i + 1) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($fullName) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($email) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($job) . "</td>
        <td width='14%' style='text-align:center;'>" . htmlspecialchars($status) . "</td>
        <td width='14%' style='text-align:center;'>" . htmlspecialchars($internal) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($reason) . "</td>
        <td width='15%' style='text-align:center;'>" . htmlspecialchars($app['date'] ?? '') . "</td>
    </tr>";
}

$tbl .= '</tbody></table>';

$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->lastPage();
$pdf->Output('all_applicants.pdf', 'I');
exit;
