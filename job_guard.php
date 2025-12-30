<?php
function isJobOpen(array $job): bool
{
    // Inactive job
    if (isset($job['is_active']) && $job['is_active'] === false) {
        return false;
    }

    // Deadline passed
    if (!empty($job['deadline']) && strtotime($job['deadline']) < time()) {
        return false;
    }

    return true;
}
