<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

require 'db.php';
require_once 'job_guard.php';

/* ===============================
   QUALIFICATION RANKING
   =============================== */
$rank = [
    "ssceolevel" => 1,
    "nd" => 2, "nce" => 2,
    "hnd" => 3,
    "bsc" => 4, "ba" => 4, "bed" => 4, "beng" => 4, "llb" => 4,
    "mbbs" => 5,
    "msc" => 6, "ma" => 6, "med" => 6, "mba" => 6, "llm" => 6,
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

/* ===============================
   LOAD APPLICATIONS
   =============================== */
$applications = $pdo->query("SELECT * FROM applications")->fetchAll(PDO::FETCH_ASSOC);

if (!$applications) {
    $_SESSION['message'] = "No applications found for evaluation.";
    header("Location: view_applications.php");
    exit();
}

/* ===============================
   COUNT APPLICATIONS PER APPLICANT
   =============================== */
$applicantJobCount = [];
foreach ($applications as $a) {
    $email = strtolower(trim($a['email']));
    $applicantJobCount[$email] = ($applicantJobCount[$email] ?? 0) + 1;
}

/* ===============================
   START EVALUATION
   =============================== */
foreach ($applications as $app) {

    $qualified = true;
    $reasons   = [];

    if (empty($app['email']) || empty($app['job_title'])) {
        $qualified = false;
        $reasons[] = "Incomplete application data";
    }

    $email    = strtolower(trim($app['email']));
    $jobTitle = strtolower(trim($app['job_title']));

    /* RULE: MAX 2 APPLICATIONS */
    if (($applicantJobCount[$email] ?? 0) > 2) {
        $qualified = false;
        $reasons[] = "Applied for more than 2 job positions";
    }

    /* FIND JOB */
    $stmt = $pdo->prepare("
        SELECT * FROM jobs
        WHERE LOWER(title) = ? OR LOWER(position) = ?
        LIMIT 1
    ");
    $stmt->execute([$jobTitle, $jobTitle]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        $qualified = false;
        $reasons[] = "Job does not exist";
    } elseif (!isJobOpen($job)) {
        $qualified = false;
        $reasons[] = "Job application is closed";
    }

    if ($job) {

        $appQual   = $app['academic_qualification'];
        $appExp    = normalize_number($app['experience_years']);
        $appPub    = normalize_number($app['publications']);
        $appBodies = normalize_list($app['professional_body']);

        $reqQual   = trim($job['requirement_qualification']);
        $reqExp    = normalize_number($job['requirement_experience']);
        $reqPub    = normalize_number($job['requirement_publications']);
        $reqBodies = normalize_list($job['requirement_body']);
        $category  = $job['category'] ?? 'Academic';

        /* ACADEMIC QUALIFICATION */
        $appRank = $rank[norm_qual($appQual)] ?? 0;
        $reqRank = $rank[norm_qual($reqQual)] ?? 0;

        if ($reqQual && $appRank < $reqRank) {
            $qualified = false;
            $reasons[] = "Academic qualification mismatch (Required: $reqQual, Applicant: $appQual)";
        }

        /* EXPERIENCE */
        if ($appExp < $reqExp) {
            $qualified = false;
            $reasons[] = "Insufficient experience (Required: {$reqExp} yrs, Applicant: {$appExp} yrs)";
        }

        /* PUBLICATIONS */
        if ($category === 'Academic' && $reqPub > 0 && $appPub < $reqPub) {
            $qualified = false;
            $reasons[] = "Insufficient publications (Required: $reqPub, Applicant: $appPub)";
        }

        /* PROFESSIONAL BODY */
        if ($reqBodies) {
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
                $reasons[] = "Professional body mismatch (Required: " .
                    strtoupper(implode(', ', $reqBodies)) .
                    ", Applicant: " .
                    strtoupper(implode(', ', $appBodies ?: ['NONE'])) . ")";
            }
        }
    }

    /* SAVE RESULT */
    if ($qualified) {
        $status = 'Qualified';
        $reason = 'All job requirements met';
    } else {
        $status = 'Not Qualified';
        $reason = implode('; ', array_unique($reasons));
    }

    $update = $pdo->prepare("
        UPDATE applications 
        SET status = ?, reason = ?
        WHERE id = ?
    ");
    $update->execute([$status, $reason, $app['id']]);
}

$_SESSION['message'] = "Application evaluation completed successfully.";
header("Location: view_applications.php");
exit();
