<?php
// apply_academic.php (STEP 2)
session_start();
if (!isset($_SESSION['applicant_email'])) { header("Location: applicant_login.php"); exit(); }
$job_id = $_REQUEST['job_id'] ?? ($_SESSION['apply_step1']['job_id'] ?? null);
if ($job_id === null) {
    header("Location: index.php");
    exit();
}

// load job (for display)
$jobs_file = __DIR__ . '/jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];
$job = $jobs[$job_id] ?? [];

$step1 = $_SESSION['apply_step1'] ?? [];
$academics = $_SESSION['apply_academics'] ?? [];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // posts come as arrays for records
    $names = $_POST['inst_name'] ?? [];
    $quals = $_POST['qualification'] ?? [];
    $froms = $_POST['from'] ?? [];
    $tos   = $_POST['to'] ?? [];
    $saved = [];

    // If upload certificates, process them into temp folder
    $token = $_SESSION['apply_token'] ?? bin2hex(random_bytes(8));
    $_SESSION['apply_token'] = $token;
    $tmpDir = __DIR__ . "/uploads/tmp/{$token}/";
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

    // Handle certificate files: input name certificates[] (aligned with records)
    $cert_files = $_FILES['certificate'] ?? null;

    for ($i = 0; $i < max(count($names), count($quals)); $i++) {
        $n = trim($names[$i] ?? '');
        $q = trim($quals[$i] ?? '');
        $f = trim($froms[$i] ?? '');
        $t = trim($tos[$i] ?? '');
        $cert_path = '';

        if ($cert_files && isset($cert_files['error'][$i]) && $cert_files['error'][$i] === UPLOAD_ERR_OK) {
            $ext = pathinfo($cert_files['name'][$i], PATHINFO_EXTENSION);
            $safe = 'cert_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/i','', $ext);
            $target = $tmpDir . $safe;
            if (move_uploaded_file($cert_files['tmp_name'][$i], $target)) {
                $cert_path = "uploads/tmp/{$token}/{$safe}";
            }
        }

        if ($n === '' && $q === '') continue; // skip blank rows

        $saved[] = [
            'institution' => $n,
            'qualification' => $q,
            'from' => $f,
            'to' => $t,
            'certificate' => $cert_path
        ];
    }

    // save to session
    $_SESSION['apply_academics'] = $saved;

    // proceed to final step
    header("Location: apply_final.php?job_id=" . urlencode($job_id));
    exit();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Apply - Step 2</title>
<style>
body{font-family:Arial;background:#eef1f5;margin:0}
.container{max-width:900px;margin:30px auto;padding:20px;background:#fff;border-radius:8px}
input,select {width:100%;padding:8px;margin-top:6px;border:1px solid #ccc;border-radius:4px}
.row{display:flex;gap:10px}
.row > div{flex:1}
.add-btn{background:#800000;color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer}
.small{font-size:0.9rem;color:#555}
.delete-row{background:#cc0000;color:#fff;border:none;padding:6px 8px;border-radius:4px;cursor:pointer}
</style>
</head>
<body>
<div class="container">
  <h2>Step 2 of 3 — Academic Records</h2>
  <p class="small">Add all academic institutions, qualifications and optionally upload certificate for each.</p>

  <form method="POST" enctype="multipart/form-data">
    <div id="records">
      <?php
        if (!empty($academics)) {
            foreach ($academics as $rec) {
                ?>
                <div class="record">
                  <div class="row">
                    <div><label>Institution<input type="text" name="inst_name[]" value="<?=htmlspecialchars($rec['institution'])?>"></label></div>
                    <div><label>Qualification<input type="text" name="qualification[]" value="<?=htmlspecialchars($rec['qualification'])?>"></label></div>
                    <div><label>From<input type="text" name="from[]" value="<?=htmlspecialchars($rec['from'])?>" placeholder="YYYY"></label></div>
                    <div><label>To<input type="text" name="to[]" value="<?=htmlspecialchars($rec['to'])?>" placeholder="YYYY or present"></label></div>
                  </div>
                  <div style="margin-top:6px">
                    <label>Certificate (optional) <?php if(!empty($rec['certificate'])) echo " — uploaded";?>
                      <input type="file" name="certificate[]">
                    </label>
                    <button type="button" class="delete-row" onclick="this.closest('.record').remove()">Remove</button>
                  </div>
                  <hr>
                </div>
                <?php
            }
        } else {
            // initial single blank row
            ?>
            <div class="record">
              <div class="row">
                <div><label>Institution<input type="text" name="inst_name[]"></label></div>
                <div><label>Qualification<input type="text" name="qualification[]"></label></div>
                <div><label>From<input type="text" name="from[]" placeholder="YYYY"></label></div>
                <div><label>To<input type="text" name="to[]" placeholder="YYYY or present"></label></div>
              </div>
              <div style="margin-top:6px">
                <label>Certificate (optional)<input type="file" name="certificate[]"></label>
                <button type="button" class="delete-row" onclick="this.closest('.record').remove()">Remove</button>
              </div>
              <hr>
            </div>
            <?php
        }
      ?>
    </div>

    <p>
      <button type="button" class="add-btn" id="addMore">+ Add more</button>
    </p>

    <p>
      <button type="submit" class="add-btn">Continue to Final Step</button>
      <a href="apply.php?job_id=<?=urlencode($job_id)?>" style="margin-left:10px;">← Back</a>
    </p>
  </form>
</div>

<script>
document.getElementById('addMore').addEventListener('click', function(){
    const container = document.getElementById('records');
    const html = `
    <div class="record">
      <div class="row">
        <div><label>Institution<input type="text" name="inst_name[]"></label></div>
        <div><label>Qualification<input type="text" name="qualification[]"></label></div>
        <div><label>From<input type="text" name="from[]" placeholder="YYYY"></label></div>
        <div><label>To<input type="text" name="to[]" placeholder="YYYY or present"></label></div>
      </div>
      <div style="margin-top:6px">
        <label>Certificate (optional)<input type="file" name="certificate[]"></label>
        <button type="button" class="delete-row" onclick="this.closest('.record').remove()">Remove</button>
      </div>
      <hr>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
});
</script>
</body>
</html>
