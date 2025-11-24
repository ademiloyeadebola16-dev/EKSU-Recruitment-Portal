<?php
// export_pdf_filtered.php
require_once __DIR__ . '/vendor/autoload.php'; // TCPDF via Composer
session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

$applications_file = __DIR__ . '/applications.json';
$applications = file_exists($applications_file)
    ? json_decode(file_get_contents($applications_file), true)
    : [];

$selected = [];

/* ============================================================
   1) Preferred – selected table rows via POST (JSON array)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rows'])) {
    $indices = json_decode($_POST['rows'], true);
    if (is_array($indices)) {
        foreach ($indices as $idx) {
            if (isset($applications[intval($idx)])) {
                $selected[] = $applications[intval($idx)];
            }
        }
    }
}

/* ============================================================
   2) Fallback – filtering via GET
   ============================================================ */
if (empty($selected)) {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $statusFilter = strtolower(trim($_GET['status'] ?? ''));
    $jobFilter = strtolower(trim($_GET['job'] ?? ''));

    foreach ($applications as $app) {
        $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
        $email = strtolower($app['email'] ?? '');
        $job = strtolower($app['job_title'] ?? '');
        $status = strtolower($app['status'] ?? 'Pending');

        $match = true;

        if ($search) {
            if (
                strpos(strtolower($fullName), $search) === false &&
                strpos($email, $search) === false &&
                strpos($job, $search) === false
            ) {
                $match = false;
            }
        }

        if ($statusFilter && $statusFilter !== $status) $match = false;
        if ($jobFilter && $jobFilter !== $job) $match = false;

        if ($match) $selected[] = $app;
    }
}

if (empty($selected)) $selected = $applications;

/* ============================================================
   Create PDF with Logo + Footer + Styling
   ============================================================ */
class CustomPDF extends TCPDF {
    public function Header() {
        $image = __DIR__ . '/logo.png';
        if (file_exists($image)) {
            $this->Image($image, 10, 5, 25);
        }
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 12, 'EKITI STATE UNIVERSITY - RECRUITMENT PORTAL', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Filtered Applicants Report', 0, 1, 'C');
        $this->Ln(2);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Generated on: ' . date('d M Y - h:i A') . ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new CustomPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(10, 30, 10);
$pdf->SetAutoPageBreak(TRUE, 18);
$pdf->AddPage();

/* ============================================================
   Build Table (FINAL 7 COLUMNS)
   ============================================================ */
$tbl  = '
<style>
table { border-collapse: collapse; width: 100%; }
th { background-color:#800000; color:#fff; font-weight:bold; text-align:center; }
td { vertical-align:middle; }
</style>

<table border="1" cellpadding="4">
<thead>
<tr>
    <th width="15%">S/N</th>
    <th width="14%">Name</th>
    <th width="14%">Email</th>
    <th width="14%">Job</th>
    <th width="14%">Status</th>
    <th width="14%">Remarks</th>
    <th width="15%">Date</th>
</tr>
</thead>
<tbody>
';

foreach ($selected as $i => $app) {
    $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $email = $app['email'] ?? '';
    $job   = $app['job_title'] ?? '';
    $status = ucfirst(strtolower($app['status'] ?? 'Pending'));
    $remarks = strip_tags($app['reason'] ?? '');
    $date = $app['date'] ?? '';

    // row background color
    $bg = ($i % 2 == 0) ? 'background-color:#f7f7f7;' : 'background-color:#ffffff;';

    $tbl .= "
    <tr style='{$bg}'>
        <td style='text-align:center;'>" . ($i + 1) . "</td>
        <td>" . htmlspecialchars($fullName) . "</td>
        <td>" . htmlspecialchars($email) . "</td>
        <td>" . htmlspecialchars($job) . "</td>
        <td style='text-align:center;'>" . htmlspecialchars($status) . "</td>
        <td>" . htmlspecialchars($remarks) . "</td>
        <td style='text-align:center;'>" . htmlspecialchars($date) . "</td>
    </tr>";
}

$tbl .= '</tbody></table>';

$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->lastPage();
$pdf->Output('filtered_applicants.pdf', 'I');
exit;
