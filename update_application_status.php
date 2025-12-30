<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

require_once 'job_guard.php';

$applications_file = 'applications.json';
$jobs_file         = 'jobs.json';

/* ===============================
   LOAD DATA
   =============================== */
$applications = file_exists($applications_file)
    ? json_decode(file_get_contents($applications_file), true)
    : [];

$jobs = file_exists($jobs_file)
    ? json_decode(file_get_contents($jobs_file), true)
    : [];

if (!$applications || !$jobs) {
    $_SESSION['message'] = "No data found for evaluation.";
    header("Location: view_applications.php");
    exit();
}

/* ===============================
   QUALIFICATION RANKING
   =============================== */
$rank = [
    "ssceolevel" => 1,
    "nd" => 2, "nce" => 2,
    "hnd" => 3,
    "bsc" => 4, "ba" => 4, "bed" => 4, "beng" => 4, "llb" => 4,
    "mbbs" => 5,
    "msc" => 6, "ma" => 6, "med" => 6, "mba" => 6,
    "mphil" => 7,
    "phd" => 8
];

/* ===============================
   HELPER FUNCTIONS
   =============================== */
function norm_qual($q) {
    return strtolower(trim(str_replace(['.', ' ', '/', '-'], '', $q)));
}

function normalize_list($value) {
    if (!$value) return [];
    return array_filter(array_map('trim', explode(',', strtolower($value))));
}

function normalize_number($value) {
    if (is_numeric($value)) return (int)$value;
    preg_match('/\d+/', (string)$value, $m);
    return isset($m[0]) ? (int)$m[0] : 0;
}

function normalize_qualification_list($value) {
    if (!$value) return [];
    return array_map('norm_qual', explode(',', strtolower($value)));
}

/* ===============================
   COUNT APPLICATIONS PER APPLICANT
   =============================== */
$applicantJobCount = [];
foreach ($applications as $a) {
    $email = strtolower(trim($a['email'] ?? ''));
    if ($email) {
        $applicantJobCount[$email] = ($applicantJobCount[$email] ?? 0) + 1;
    }
}

/* ===============================
   START EVALUATION
   =============================== */
foreach ($applications as &$app) {

    /* HARD RESET */
    $app['status'] = 'Pending';
    $app['reason'] = '';

    $qualified = true;
    $reasons   = [];

    if (empty($app['email']) || empty($app['job_title'])) {
        $app['status'] = 'Not Qualified';
        $app['reason'] = "Incomplete application data.";
        continue;
    }

    $email    = strtolower(trim($app['email']));
    $jobTitle = strtolower(trim($app['job_title']));

    /* RULE: MAX 2 APPLICATIONS */
    if (($applicantJobCount[$email] ?? 0) > 2) {
        $qualified = false;
        $reasons[] = "Applied for more than 2 job positions";
    }

    /* FIND MATCHING JOB */
    $matchedJob = null;
    foreach ($jobs as $job) {
        if (
            strtolower(trim($job['title'] ?? '')) === $jobTitle ||
            strtolower(trim($job['position'] ?? '')) === $jobTitle
        ) {
            $matchedJob = $job;
            break;
        }
    }

    if (!$matchedJob) {
        $qualified = false;
        $reasons[] = "Job does not exist";
    } elseif (!isJobOpen($matchedJob)) {
        $qualified = false;
        $reasons[] = "Job application is closed";
    }

    $appQual   = $app['academic_qualification'] ?? '';
$appExp    = normalize_number($app['experience_years'] ?? 0);
$appPub    = normalize_number($app['publications'] ?? 0);
$appBodies = normalize_list($app['professional_body'] ?? '');

$jobCategory = $matchedJob['category'] ?? 'Academic';

$reqQual   = trim($matchedJob['requirement_qualification'] ?? '');
$reqExp    = normalize_number($matchedJob['required_experience'] ?? 0);
$reqPub    = normalize_number($matchedJob['required_publications'] ?? 0);
$reqBodies = normalize_list($matchedJob['required_body'] ?? '');

/* ===== ACADEMIC QUALIFICATION ===== */
$appRank = $rank[norm_qual($appQual)] ?? 0;
$reqRank = $rank[norm_qual($reqQual)] ?? 0;

if ($reqQual !== '' && $appRank < $reqRank) {
    $qualified = false;
    $reasons[] =
        "Academic qualification mismatch (Required: {$reqQual}, Applicant: {$appQual})";
}

/* ===== EXPERIENCE (MANDATORY FOR ALL) ===== */
if ($appExp < $reqExp) {
    $qualified = false;
    $reasons[] =
        "Insufficient experience (Required: {$reqExp} yrs, Applicant: {$appExp} yrs)";
}

/* ===== PUBLICATIONS (ACADEMIC ONLY) ===== */
if ($jobCategory === 'Academic' && $reqPub > 0) {
    if ($appPub < $reqPub) {
        $qualified = false;
        $reasons[] =
            "Insufficient publications (Required: {$reqPub}, Applicant: {$appPub})";
    }
}

/* ===== PROFESSIONAL BODY (AT LEAST ONE MATCH) ===== */
if (!empty($reqBodies)) {
    $match = false;
    foreach ($reqBodies as $rb) {
        foreach ($appBodies as $ab) {
            if (stripos($ab, $rb) !== false || stripos($rb, $ab) !== false) {
                $match = true;
                break 2;
            }
        }
    }

    if (!$match) {
        $qualified = false;
        $reasons[] =
            "Professional body mismatch (Required: " .
            strtoupper(implode(', ', $reqBodies)) .
            ", Applicant: " .
            strtoupper(implode(', ', $appBodies ?: ['NONE'])) . ")";
    }
}

    /* ===============================
       FINAL RESULT
       =============================== */
    if ($qualified) {
        $app['status'] = 'Qualified';
        $app['reason'] = "All job requirements met";
    } else {
        $app['status'] = 'Not Qualified';
        $app['reason'] = implode('; ', array_unique($reasons));
    }
}

unset($app);

/* SAVE RESULTS */
file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

$_SESSION['message'] = "Application evaluation completed successfully.";
header("Location: view_applications.php");
exit();
