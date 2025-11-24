<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$applications_file = 'applications.json';
$jobs_file = 'jobs.json';

// Load JSON data
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];

if (empty($applications) || empty($jobs)) {
    $_SESSION['message'] = "No data found for evaluation.";
    header("Location: view_applications.php");
    exit();
}

// Count how many jobs each applicant applied for
$applicantJobCount = [];
foreach ($applications as $app) {
    $email = strtolower(trim($app['email'] ?? ''));
    if ($email !== '') {
        $applicantJobCount[$email] = ($applicantJobCount[$email] ?? 0) + 1;
    }
}

// Evaluate each application
foreach ($applications as &$app) {

    // clear old reason
    $app['reason'] = '';

    if (empty($app['job_title']) || empty($app['email'])) {
        $app['status'] = 'Pending';
        $app['reason'] = "Application incomplete — missing job or email.";
        continue;
    }

    $email = strtolower(trim($app['email']));
    $jobTitle = strtolower(trim($app['job_title']));

    // RULE 1: Applicant applied for more than 2 jobs
    if (($applicantJobCount[$email] ?? 0) > 2) {
        $app['status'] = 'Disqualified';
        $app['reason'] = "Disqualified: You applied for more than 2 job positions.";
        continue;
    }

    // Find matching job
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

    // If job not found — keep status as Pending
    if (!$matchedJob) {
        $app['status'] = 'Pending';
        $app['reason'] = "This job no longer exists or was removed.";
        continue;
    }

    // Applicant data
    $appQualification = strtolower(trim($app['academic_qualification'] ?? ''));
    $appExperience = (int)($app['experience_years'] ?? 0);
    $appPublications = (int)($app['publications'] ?? 0);
    $appBody = strtolower(trim($app['professional_body'] ?? ''));

    // Job requirements
    $reqQualification = strtolower(trim($matchedJob['requirement_qualification'] ?? ''));
    $reqExperience = (int)($matchedJob['requirement_experience'] ?? 0);
    $reqPublications = (int)($matchedJob['requirement_publications'] ?? 0);
    $reqBody = strtolower(trim($matchedJob['requirement_body'] ?? ''));

    $reasons = [];
    $qualified = true;

    // Compare qualification
    if ($reqQualification && strpos($appQualification, $reqQualification) === false) {
        $qualified = false;
        $reasons[] = "Required qualification: {$matchedJob['requirement_qualification']} | Applicant has: {$app['academic_qualification']}.";
    }

    // Compare experience
    if ($appExperience < $reqExperience) {
        $qualified = false;
        $reasons[] = "Required experience: {$reqExperience} years | Applicant has: {$appExperience} years.";
    }

    // Compare publications
    if ($appPublications < $reqPublications) {
        $qualified = false;
        $reasons[] = "Required publications: {$reqPublications} | Applicant has: {$appPublications}.";
    }

    // Compare professional body
    if ($reqBody && strpos($appBody, $reqBody) === false) {
        $qualified = false;
        $reasons[] = "Must belong to: {$matchedJob['requirement_body']} | Applicant: {$app['professional_body']}.";
    }

    // Final status assignment
    if ($qualified) {
        $app['status'] = 'Qualified';
        $reasons[] = "All job requirements were successfully met.";
    } else {
        // Prevent overwriting Disqualified from earlier rules
        if ($app['status'] !== 'Disqualified') {
            $app['status'] = 'Not Qualified';
        }
    }

    $app['reason'] = implode("\n", $reasons);
}

unset($app);

// Save updated result
file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

$_SESSION['message'] = "Qualification check completed successfully!";
header("Location: view_applications.php");
exit();
