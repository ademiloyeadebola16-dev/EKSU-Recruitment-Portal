<?php
// export_pdf_all.php (DB VERSION)
require_once __DIR__ . '/vendor/autoload.php'; // TCPDF
require_once __DIR__ . '/db.php';

session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

/* ------------------ FETCH DATA FROM DB ------------------ */
$sql = "
    SELECT 
        a.first_name,
        a.middle_name,
        a.last_name,
        a.email,
        a.status,
        a.internal_status,
        a.reason,
        a.created_at,
        j.position AS job_title
    FROM applications a
    LEFT JOIN jobs j ON j.id = a.job_id
    ORDER BY a.created_at DESC
";

$stmt = $pdo->query($sql);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ------------------ CREATE PDF ------------------ */
$pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('EKSU Recruitment');
$pdf->SetAuthor('EKSU Recruitment');
$pdf->SetTitle('All Applicants');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

/* ------------------ BUILD TABLE ------------------ */
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

    $fullName = trim(
        ($app['first_name'] ?? '') . ' ' .
        ($app['middle_name'] ?? '') . ' ' .
        ($app['last_name'] ?? '')
    );

    $status = ucfirst(strtolower($app['status'] ?? 'Pending'));
    $internal = $app['internal_status'] ?? '';
    $reason = strip_tags($app['reason'] ?? '');

    /* ---------- ROW COLOR ---------- */
    if (strtolower($status) === 'qualified') {
        $bg = ' style="background-color:#e6ffea;"';
    } elseif (strtolower($status) === 'not qualified') {
        $bg = ' style="background-color:#ffe6e6;"';
    } elseif (strtolower($status) === 'disqualified') {
        $bg = ' style="background-color:#fff4e5;"';
    } else {
        $bg = ' style="background-color:#f2f2f2;"';
    }

    $tbl .= "<tr{$bg} style='vertical-align:middle;'>
        <td width='15%' style='text-align:center;'>" . ($i + 1) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($fullName) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($app['email'] ?? '') . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($app['job_title'] ?? '') . "</td>
        <td width='14%' style='text-align:center;'>" . htmlspecialchars($status) . "</td>
        <td width='14%' style='text-align:center;'>" . htmlspecialchars($internal) . "</td>
        <td width='14%' style='text-align:left;'>" . htmlspecialchars($reason) . "</td>
        <td width='15%' style='text-align:center;'>" . htmlspecialchars($app['created_at'] ?? '') . "</td>
    </tr>";
}

$tbl .= '</tbody></table>';

$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->lastPage();
$pdf->Output('all_applicants.pdf', 'I');
exit;
