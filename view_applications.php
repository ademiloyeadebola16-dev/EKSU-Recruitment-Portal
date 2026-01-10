<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->query("
    SELECT 
        id,
        first_name,
        middle_name,
        last_name,
        email,
        job_title,
        status,
        internal_status,
        reason,
        created_at
    FROM applications
    ORDER BY created_at DESC
");

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Applications</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; }
nav { background: #800000; color: white; padding: 15px 6%; display: flex; justify-content: space-between; align-items: center; }
.nav-container { display: flex; align-items: center; gap: 15px; }
.nav-container img { width: 70px; height: 70px; border-radius: 5px; }
.nav-text h1 { font-size: 20px; margin: 0; }
.nav-text h5 { margin: 0; font-size: 13px; font-weight: normal; color: #ddd; }
.container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
h2 { color: #800000; border-bottom: 2px solid #800000; padding-bottom: 6px; margin-bottom: 18px; }
.filter-container { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.filter-container input, .filter-container select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
.toolbar { display:flex; gap:10px; align-items:center; margin-bottom:12px; flex-wrap:wrap }
.btn { padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
.btn-primary { background:#007bff; color:#fff }
.btn-success { background:#28a745; color:#fff }
.btn-ghost { background:#f0f0f0; color:#222 }
.table-wrap { overflow-x:auto }
table { width:100%; border-collapse: collapse; margin-top: 8px; }
table th, table td { border:1px solid #ddd; padding:10px; text-align:left; }
table th { background:#800000; color:#fff }
.status-qualified { color: green; font-weight: bold; }
.status-notqualified { color: red; font-weight: bold; }
.status-disqualified { color: orange; font-weight: bold; }
.status-pending { color: gray; font-weight: bold; }
.action-links a { display:inline-block; margin-bottom:6px; }
.message { padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px; }
footer { background: #800000; color: white; text-align: center; padding: 16px; margin-top: 24px; }
@media (max-width:700px){ .filter-container{flex-direction:column} .toolbar{flex-direction:column; align-items:stretch} }
</style>
</head>
<body>

<!-- Header -->
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Recruitment Portal</h5>
      </div>
   </div>

   <div>
    <a href="admin.php" class="btn" style="background:#008000;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;margin-right:6px;">Home</a>
    <a href="update_application_status.php" class="btn" style="background:#800000;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;margin-right:6px;">Run Qualification Check</a>
    <a href="admin_logout.php" class="btn" style="background:#cc0000;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;">Logout</a>
   </div>
</nav>

<div class="container">
    <h2>All Applications</h2>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="message"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filter-container">
        <input type="text" id="searchInput" placeholder="Search by name, email or job..." onkeyup="applyFilters()">
        <select id="statusFilter" onchange="applyFilters()">
            <option value="">All Statuses</option>
            <option value="Qualified">Qualified</option>
            <option value="Not Qualified">Not Qualified</option>
            <option value="Disqualified">Disqualified</option>
            <option value="Pending">Pending</option>
        </select>
        <select id="jobFilter" onchange="applyFilters()">
            <option value="">All Jobs</option>
            <?php
            $jobsList = [];
            foreach ($applications as $app) {
                $title = $app['job_title'] ?? '';
                if ($title && !in_array($title, $jobsList)) $jobsList[] = $title;
            }
            sort($jobsList);
            foreach ($jobsList as $jobTitle) {
                echo '<option value="'.htmlspecialchars($jobTitle).'">'.htmlspecialchars($jobTitle).'</option>';
            }
            ?>
        </select>

    </div>

    <!-- Toolbar with exports -->
    <div class="toolbar">
        <button class="btn btn-success" onclick="exportFilteredRows()">⬇️ Export Filtered to PDF</button>
        <button class="btn btn-success" onclick="window.open('export_pdf_all.php','_blank')">⬇️ Export All to PDF</button>
        <button class="btn btn-ghost" onclick="exportCSV('filtered')">⬇️ Export Filtered to CSV</button>
        <button class="btn btn-ghost" onclick="window.open('export_csv_all.php','_blank')">⬇️ Export All to CSV</button>
        <div style="margin-left:auto;font-size:13px;color:#666">Export: All vs Filtered • Color-coded output</div>
    </div>

    <?php if (!empty($applications)): ?>
    <div class="table-wrap" id="printArea">
        <table id="applicationsTable">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Applicant Name</th>
                    <th>Email</th>
                    <th>Job Applied</th>
                    <th>Status</th>
                    <th>Internal Status</th>
                    <th>Reason / Remarks</th>
                    <th>Date Applied</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <?php 
                        $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                        $status = ucfirst(strtolower($app['status'] ?? 'Pending'));
                        $internal = $app['internal_status'] ?? '';
                        $reason = trim($app['reason'] ?? 'No remarks available.');
                    ?>
                    <tr data-index="<?= $app['id'] ?>" data-job="<?= htmlspecialchars($app['job_title'] ?? '') ?>">
                        <?php static $sn = 0; $sn++; ?>
                        <td><?= $sn ?></td>
                        <td><?= htmlspecialchars($fullName) ?></td>
                        <td><?= htmlspecialchars($app['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($app['job_title'] ?? '') ?></td>
                        <td class="status-<?= strtolower(str_replace(' ', '', $status)) ?>"><?= htmlspecialchars($status) ?></td>
                        <td><?= htmlspecialchars($internal) ?></td>
                        <td><?= nl2br(htmlspecialchars($reason)) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($app['created_at']))) ?></td>

                        <!-- UPDATED ACTION COLUMN -->
                        <td class="action-links">
                            <a class="btn" href="view_applicant_detail.php?index=<?= $app['id'] ?>">View Details</a>

                            <a class="btn" style="background:#cc0000;color:#fff;margin-top:6px;display:inline-block"
                            href="delete_application.php?index=<?= $app['id'] ?>"
                            onclick="return confirm('Are you sure you want to delete this application?');">
                            Delete
                            </a>

                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>No applications found.</p>
    <?php endif; ?>
</div>

<script>
/* ---------- Filtering ---------- */
function applyFilters() {
    var searchInput = document.getElementById("searchInput").value.toLowerCase();
    var statusFilter = document.getElementById("statusFilter").value.toLowerCase();
    var jobFilter = document.getElementById("jobFilter").value.toLowerCase();
    var table = document.getElementById("applicationsTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var tdName = tr[i].getElementsByTagName("td")[1].textContent.toLowerCase();
        var tdEmail = tr[i].getElementsByTagName("td")[2].textContent.toLowerCase();
        var tdJob = tr[i].getElementsByTagName("td")[3].textContent.toLowerCase();
        var tdStatus = tr[i].getElementsByTagName("td")[4].textContent.toLowerCase();

        var show = true;

        if (searchInput && !(tdName.includes(searchInput) || tdEmail.includes(searchInput) || tdJob.includes(searchInput))) {
            show = false;
        }

        if (statusFilter && tdStatus !== statusFilter) {
            show = false;
        }

        if (jobFilter && tdJob !== jobFilter) {
            show = false;
        }

        tr[i].style.display = show ? "" : "none";
    }
}

/* ---------- Collect visible indices ---------- */
function collectVisibleIndices() {
    var rows = document.querySelectorAll('#applicationsTable tbody tr');
    var indices = [];
    rows.forEach(function(r) {
        if (r.style.display !== 'none') {
            var idx = r.getAttribute('data-index');
            if (idx !== null) indices.push(parseInt(idx));
        }
    });
    return indices;
}

/* ---------- Export filtered rows to PDF (POST rows) ---------- */
function exportFilteredRows() {
    var indices = collectVisibleIndices();
    if (indices.length === 0) {
        if (!confirm('No visible rows found. Do you want to export ALL applicants instead?')) return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_pdf_filtered.php';
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'rows';
    input.value = JSON.stringify(indices);
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/* ---------- Export CSV (filtered via GET rows or all) ---------- */
function exportCSV(which) {
    if (which === 'filtered') {
        var indices = collectVisibleIndices();
        if (indices.length === 0) {
            if (!confirm('No visible rows found. Do you want to export ALL applicants instead?')) return;
        }
        var q = indices.length ? ('?rows=' + encodeURIComponent(JSON.stringify(indices))) : '';
        window.open('export_csv_filtered.php' + q, '_blank');
    }
}

/* ---------- Print filtered (client-side, color-coded) ---------- */
function printFiltered() {
    var table = document.getElementById('applicationsTable');
    var rows = table.getElementsByTagName('tr');
    var printContent = "<h2 style='text-align:center;'>Filtered Applicants List</h2>";
    printContent += "<table style='width:100%;border-collapse:collapse;' border='1'>";
    // add header
    printContent += rows[0].outerHTML;

    for (var i = 1; i < rows.length; i++) {
        if (rows[i].style.display !== 'none') {
            var statusText = rows[i].getElementsByTagName('td')[4].textContent.trim().toLowerCase();
            var rowClass = '';
            if (statusText === 'qualified') rowClass = 'print-status-qualified';
            else if (statusText === 'not qualified') rowClass = 'print-status-notqualified';
            else if (statusText === 'disqualified') rowClass = 'print-status-disqualified';
            else rowClass = 'print-status-pending';

            printContent += "<tr class='" + rowClass + "'>" + rows[i].innerHTML + "</tr>";
        }
    }

    printContent += "</table>";

    var newWindow = window.open("", "", "width=1000,height=800");
    newWindow.document.write(`
        <html>
        <head>
            <title>Print Applicants</title>
            <style>
                body { font-family: Arial, sans-serif; padding:20px; }
                table, td, th { border: 1px solid #000; padding:8px; }
                th { background: #800000; color: #fff; }
                .print-status-qualified { background: #e6ffea; }
                .print-status-notqualified { background: #ffe6e6; }
                .print-status-disqualified { background: #fff4e5; }
                .print-status-pending { background: #f2f2f2; }
            </style>
        </head>
        <body>
            " + printContent + "
        </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
}

/* ---------- Print by Job (print only rows for selected job) ---------- */
function printByJob() {
    var job = document.getElementById('printJobSelect').value;
    if (!job) { alert('Select a Job from the "Print by Job" dropdown first.'); return; }
    var rows = document.querySelectorAll('#applicationsTable tbody tr');
    var printContent = "<h2 style='text-align:center;'>Applicants for: " + job + "</h2>";
    printContent += "<table style='width:100%;border-collapse:collapse;' border='1'>";
    printContent += document.querySelector('#applicationsTable thead').outerHTML;

    rows.forEach(function(r) {
        if (r.getAttribute('data-job') === job) {
            printContent += r.outerHTML;
        }
    });

    printContent += "</table>";
    var newWindow = window.open("", "", "width=1000,height=800");
    newWindow.document.write(`
        <html>
        <head>
            <title>Print Applicants - ${job}</title>
            <style>
                body { font-family: Arial, sans-serif; padding:20px; }
                table, td, th { border: 1px solid #000; padding:8px; }
                th { background:#800000; color:#fff; }
            </style>
        </head>
        <body>
            " + printContent + "
        </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
}
</script>

<!-- Footer -->
<footer>
    <p>&copy; <?= date('Y') ?> EKSU Recruitment. All rights reserved.</p>
</footer>

</body>
</html>
