<?php
session_start();
// require admin check in your real site
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

/**
 * Load jobs.json safely and auto-fix common problems.
 * Returns an array of jobs.
 */
function load_jobs_json($file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }

    $raw = file_get_contents($file);
    $data = json_decode($raw, true);

    // If decode failed or returned non-array, try to repair:
    if ($data === null) {
        // If raw contains a single object (not in array), wrap it.
        $try = @json_decode('[' . trim($raw) . ']', true);
        if (is_array($try)) {
            $data = $try;
        } else {
            $data = [];
        }
    } elseif (!is_array($data)) {
        $data = [$data];
    }

    // Ensure element keys exist to avoid notices elsewhere
    foreach ($data as $i => $job) {
        if (!is_array($job)) $data[$i] = [];
        $defaults = [
            'category' => $job['category'] ?? ($job['faculty'] && $job['faculty'] !== '---' ? 'Academic' : 'Non-Academic'),
            'title' => $job['title'] ?? $job['position'] ?? '',
            'position' => $job['position'] ?? $job['title'] ?? '',
            'faculty' => $job['faculty'] ?? '---',
            'department' => $job['department'] ?? '',
            'qualification' => $job['qualification'] ?? $job['qualification_display'] ?? '',
            'description' => $job['description'] ?? '',
            'requirement_qualification' => $job['requirement_qualification'] ?? '',
            'required_experience' => isset($job['required_experience']) ? (int)$job['required_experience'] : (int)($job['requirement_experience'] ?? 0),
            'required_publications' => isset($job['required_publications']) ? (int)$job['required_publications'] : (int)($job['requirement_publications'] ?? 0),
            'required_body' => $job['required_body'] ?? '',
            'research_expected' => (bool)($job['research_expected'] ?? false),
            'custom_fields' => is_array($job['custom_fields'] ?? null) ? $job['custom_fields'] : []
        ];
        foreach ($defaults as $k => $v) {
            if (!isset($data[$i][$k])) $data[$i][$k] = $v;
        }
    }

    // Save back a canonical JSON array (avoid leaving corrupt JSON)
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
    return $data;
}

$jobs_file = 'jobs.json';
$jobs = load_jobs_json($jobs_file);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form uses: title (category), position (actual job title), faculty, department ...
    $category = trim($_POST['title'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // admin-only requirements:
    $requirement_qualification = trim($_POST['requirement_qualification'] ?? '');
    if ($requirement_qualification === 'Other') {
        $requirement_qualification = trim($_POST['requirement_qualification_other'] ?? '');
    }
    $required_experience = (int)($_POST['required_experience'] ?? 0);

    // publications auto 0 for Non-Academic
    if ($category === 'Non-Academic') {
        $required_publications = 0;
    } else {
        $required_publications = (int)($_POST['required_publications'] ?? 0);
    }

    $required_body = trim($_POST['required_body'] ?? '');
    $research_expected = isset($_POST['research_expected']) ? true : false;

    // custom fields
    $custom_fields = $_POST['custom_fields'] ?? [];
    $custom_fields = array_values(array_filter(array_map('trim', (array)$custom_fields)));

    $deadline_raw = $_POST['deadline'] ?? '';
$deadline = $deadline_raw ? date('Y-m-d H:i:s', strtotime($deadline_raw)) : null;

$is_active = isset($_POST['is_active']) ? true : false;
$now = time();

$visible_jobs = array_filter($jobs, function ($job) use ($now) {
    // Hide disabled jobs
    if (empty($job['is_active'])) return false;

    return true;
});


    // Build job object using canonical keys
    $new_job = [
        'category' => $category,
        'title' => $position,
        'position' => $position,
        'faculty' => $faculty !== '' ? $faculty : ($category === 'Non-Academic' ? '---' : ''),
        'department' => $department,
        'qualification' => $qualification,
        'description' => $description,
        'requirement_qualification' => $requirement_qualification,
        'required_experience' => (int)$required_experience,
        'required_publications' => (int)$required_publications,
        'required_body' => $required_body,
        'research_expected' => (bool)$research_expected,
        'custom_fields' => $custom_fields,
        'deadline' => $deadline,
        'is_active' => $is_active,
    ];

    // reload in case changed concurrently
    $jobs = load_jobs_json($jobs_file);
    $jobs[] = $new_job;

    // safe write: encode + check
    $encoded = json_encode(array_values($jobs), JSON_PRETTY_PRINT);
    if ($encoded === false) {
        $_SESSION['message'] = "Failed to encode jobs JSON. Job not saved.";
    } else {
        file_put_contents($jobs_file, $encoded);
        $_SESSION['message'] = "Job added successfully!";
    }

    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Job — Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  nav {
    background: #800000;
    color: #fff;
    padding: 15px 10%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-container {
    display: flex;
    align-items: center;
    gap: 15px;
}
.nav-container img {
    width: 70px;
    height: 70px;
    border-radius: 5px;
}
nav h1 {
    font-size: 22px;
    margin: 0;
}
.nav-text h5 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
    color: #ddd;
}
/* (small styling omitted for brevity — keep your existing styles) */
body
{font-family:Arial,Helvetica,sans-serif;margin:20px}
label{display:block;margin:8px 0 4px} 
input,select,textarea{width:100%;padding:8px;border-radius:6px;border:1px solid #ccc}
.row{display:flex;gap:20px;flex-wrap:wrap}
.col{flex:1;min-width:220px}
.half{flex:1 1 calc(50% - 12px)}
button{padding:10px 16px;background:#800000;color:#fff;border:0;border-radius:8px;cursor:pointer}
.badge {
    display:inline-block;
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
    margin-top:6px;
}
.badge.closed {
    background:#b00000;
    color:#fff;
}
.countdown {
    font-weight:bold;
    color:#004080;
    margin-top:6px;
}
</style>
</head>
<body>
  <nav>
    <div class="nav-container">
        <img src="logo.jfif" alt="EKSU Logo">

        <div class="nav-text">
            <h1>Ekiti State University, Ado-Ekiti</h1>
            <h5>Admin Recruitment Panel</h5>
        </div>
    </div>

    <div>
        <a href="admin.php" style="background-color: #004080; color:#fff; padding:8px 15px; border-radius:5px; text-decoration:none;">Dashboard</a>
        <a href="admin_logout.php" style="background-color: #008026ff; color:#fff; padding:8px 15px; border-radius:5px; text-decoration:none;">Logout</a>
    </div>
</nav>

<h2>Add Job Posting</h2>
<?php if (!empty($_SESSION['message'])): ?>
    <div style="padding:10px;background:#e6f7e6;border:1px solid #b3e6b3;margin-bottom:12px;">
        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>
<?php
$isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
?>


<form method="post" id="addJobForm">
  <div>
    <label for="title">Job Category</label>
    <select id="title" name="title" required>
      <option value="">-- Select Category --</option>
      <option value="Academic">Academic</option>
      <option value="Non-Academic">Non-Academic</option>
    </select>
  </div>

  <div class="row">
    <div class="col">
      <label for="position">Position (Job Title)</label>
      <select id="position" name="position" required>
        <option value="">-- Select Position --</option>
      </select>
    </div>
    <div class="col" id="facultyBlock">
      <label for="faculty">Faculty</label>
      <input id="faculty" name="faculty" type="text" placeholder="e.g. Science">
    </div>
  </div>

  <div class="row">
    <div class="half">
      <label for="department">Department</label>
      <input id="department" name="department" type="text" required>
    </div>
    <div class="half">
      <label for="qualification">Minimum Qualification (info)</label>
      <select id="qualification" name="qualification" required>
        <option value="">-- Select Qualification --</option>
        <option>SSCE / O-Level</option>
        <option>ND</option>
        <option>NCE</option>
        <option>HND</option>
        <option>B.Sc</option>
        <option>B.A</option>
        <option>B.Ed</option>
        <option>B.Eng</option>
        <option>LL.B</option>
        <option>MBBS</option>
        <option>M.Sc</option>
        <option>M.A</option>
        <option>M.Ed</option>
        <option>MBA</option>
        <option>M.Eng</option>
        <option>PhD</option>
      </select>
    </div>
  </div>

  <div>
    <label for="description">Job Description</label>
    <textarea id="description" name="description" rows="4"></textarea>
  </div>

  <hr>
  <h3>Admin-only requirements</h3>

  <div class="row">
    <div class="half">
      <label for="requirement_qualification">Academic Qualification Requirement</label>
      <select id="requirement_qualification" name="requirement_qualification" required>
        <option value="">-- Select Required Qualification --</option>
        <option>SSCE / O-Level</option>
        <option>ND</option>
        <option>NCE</option>
        <option>HND</option>
        <option>B.Sc</option>
        <option>M.Sc</option>
        <option>PhD</option>
        <option value="Other">Other</option>
      </select>
      <input type="text" id="requirement_qualification_other" name="requirement_qualification_other" placeholder="Specify other" style="display:none;margin-top:6px">
    </div>

    <div class="half">
      <label for="required_experience">Years of Teaching Experience</label>
      <input id="required_experience" name="required_experience" type="number" min="0" value="0" required>
    </div>
  </div>

  <div class="row">
    <div class="half" id="publicationsBlock">
      <label for="required_publications">Required Number of Publications</label>
      <input id="required_publications" name="required_publications" type="number" min="0" value="0" required>
    </div>

    <div class="half">
      <label for="required_body">Professional Body Registration (if any)</label>
      <input id="required_body" name="required_body" type="text" placeholder="e.g. COREN">
    </div>
  </div>

  <div style="margin-top:8px;">
    <label>Custom Job Requirements (will appear on application form)</label>
    <div id="customFieldsContainer">
      <div><input type="text" name="custom_fields[]" placeholder="e.g. Number of citations"></div>
    </div>
    <button type="button" id="addFieldBtn" style="background:#800000;margin-top:8px">+ Add More</button>
  </div>
<hr>
<h3>Job Availability</h3>

<div class="row">
  <div class="half">
    <label for="deadline">Application Deadline</label>
    <input type="datetime-local" id="deadline" name="deadline" required>
  </div>

  <div class="half">
    <label>
      <input type="checkbox" name="is_active" checked>
      Job is Active (Visible to applicants)
    </label>
  </div>
</div>

  <div style="margin-top:14px;">
    <button type="submit">Add Job</button>
  </div>
</form>

<script>
// positions lists (same as earlier)
const academicPositions = [
    "Professor","Associate Professor","Senior Lecturer","Lecturer I","Lecturer II",
    "Assistant Lecturer","Graduate Assistant"
];
const nonAcademicPositions = [
    "Administrative Officer","Director","Deputy Registrar","Chief Executive Officer",
    "Deputy Bursar","Deputy Director","Accountant","Library Officer","Technologist",
    "Lab Assistant","ICT Support","Clerical Officer","Driver","Security Personnel",
    "Works & Maintenance Staff"
];

const categoryEl = document.getElementById('title');
const positionEl = document.getElementById('position');
const publicationsBlock = document.getElementById('publicationsBlock');
const pubInput = document.getElementById('required_publications');
const reqQual = document.getElementById('requirement_qualification');
const reqQualOther = document.getElementById('requirement_qualification_other');

function populatePositions() {
    const cat = categoryEl.value;
    positionEl.innerHTML = '<option value="">-- Select Job Position --</option>';
    const list = cat === 'Academic' ? academicPositions : (cat === 'Non-Academic' ? nonAcademicPositions : []);
    list.forEach(p => {
        const o = document.createElement('option');
        o.value = p; o.textContent = p;
        positionEl.appendChild(o);
    });
    // toggle faculty/publications
    if (cat === 'Academic') {
        document.getElementById('facultyBlock').style.display = 'block';
        publicationsBlock.style.display = 'block';
        if (pubInput.value === '0') pubInput.value = '';
    } else if (cat === 'Non-Academic') {
        document.getElementById('facultyBlock').style.display = 'none';
        publicationsBlock.style.display = 'none';
        pubInput.value = 0;
    } else {
        document.getElementById('facultyBlock').style.display = 'block';
        publicationsBlock.style.display = 'block';
    }
}

categoryEl.addEventListener('change', populatePositions);
reqQual.addEventListener('change', function(){
    if (this.value === 'Other') { reqQualOther.style.display='block'; reqQualOther.required=true; }
    else { reqQualOther.style.display='none'; reqQualOther.required=false; reqQualOther.value=''; }
});

// add custom fields
document.getElementById('addFieldBtn').addEventListener('click', function(){
    const div = document.createElement('div');
    div.innerHTML = '<input type="text" name="custom_fields[]" placeholder="Enter custom requirement">';
    document.getElementById('customFieldsContainer').appendChild(div);
});
document.querySelectorAll('.countdown').forEach(el => {
    const deadline = new Date(el.dataset.deadline).getTime();

    function update() {
        const now = Date.now();
        const diff = deadline - now;

        if (diff <= 0) {
            el.innerHTML = "<span class='badge closed'>Closed</span>";
            return;
        }

        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
        const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const m = Math.floor((diff / (1000 * 60)) % 60);

        el.textContent = `Closes in ${d}d ${h}h ${m}m`;
        setTimeout(update, 60000);
    }
    update();
});

// init
populatePositions();
</script>
</body>
</html>
