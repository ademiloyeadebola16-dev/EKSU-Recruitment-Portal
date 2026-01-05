<?php
// export_pdf_filtered.php (DB VERSION)
require_once __DIR__ . '/vendor/autoload.php'; // TCPDF
require_once __DIR__ . '/db.php';

session_start();
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

/* ============================================================
   1) SELECTED ROWS (POST → application IDs)
   ============================================================ */
$selected = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rows'])) {
    $ids = json_decode($_POST['rows'], true);

    if (is_array($ids) && count($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.status,
                a.reason,
                a.created_at,
                j.position AS job_title
            FROM applications a
            LEFT JOIN jobs j ON j.id = a.job_id
            WHERE a.id IN ($placeholders)
            ORDER BY a.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $selected = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ============================================================
   2) FILTERING VIA GET (fallback)
   ============================================================ */
if (empty($selected)) {

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $job    = trim($_GET['job'] ?? '');

    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = "
            (a.first_name LIKE ? OR 
             a.middle_name LIKE ? OR 
             a.last_name LIKE ? OR 
             a.email LIKE ? OR 
             j.position LIKE ?)
        ";
        $params = array_merge($params, array_fill(0, 5, "%$search%"));
    }

    if ($status !== '') {
        $conditions[] = "a.status = ?";
        $params[] = $status;
    }

    if ($job !== '') {
        $conditions[] = "j.position = ?";
        $params[] = $job;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT 
            a.id,
            a.first_name,
            a.middle_name,
            a.last_name,
            a.email,
            a.status,
            a.reason,
            a.created_at,
            j.position AS job_title
        FROM applications a
        LEFT JOIN jobs j ON j.id = a.job_id
        $where
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $selected = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   PDF CLASS
   ============================================================ */
class CustomPDF extends TCPDF {
    public function Header() {
        $logo = __DIR__ . '/logo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 25);
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
        $this->Cell(
            0,
            10,
            'Generated on: ' . date('d M Y - h:i A') .
            ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
            0,
            0,
            'C'
        );
    }
}

/* ============================================================
   PDF SETUP
   ============================================================ */
$pdf = new CustomPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(10, 30, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

/* ============================================================
   TABLE
   ============================================================ */
$tbl = '
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
<tbody>';

foreach ($selected as $i => $app) {

    $fullName = trim(
        ($app['first_name'] ?? '') . ' ' .
        ($app['middle_name'] ?? '') . ' ' .
        ($app['last_name'] ?? '')
    );

    $status = ucfirst(strtolower($app['status'] ?? 'Pending'));
    $bg = ($i % 2 === 0) ? 'background-color:#f7f7f7;' : 'background-color:#ffffff;';

    $tbl .= "
    <tr style='{$bg}'>
        <td style='text-align:center;'>" . ($i + 1) . "</td>
        <td>" . htmlspecialchars($fullName) . "</td>
        <td>" . htmlspecialchars($app['email'] ?? '') . "</td>
        <td>" . htmlspecialchars($app['job_title'] ?? '') . "</td>
        <td style='text-align:center;'>" . htmlspecialchars($status) . "</td>
        <td>" . htmlspecialchars(strip_tags($app['reason'] ?? '')) . "</td>
        <td style='text-align:center;'>" . htmlspecialchars($app['created_at'] ?? '') . "</td>
    </tr>";
}

$tbl .= '</tbody></table>';

$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->lastPage();
$pdf->Output('filtered_applicants.pdf', 'I');
exit;
