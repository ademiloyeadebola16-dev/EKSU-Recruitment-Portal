<?php
function isJobOpen(array $job): bool
{
    /* ===============================
       JOB ACTIVE FLAG
       =============================== */
    if (isset($job['is_active'])) {
        // MySQL may return 0/1 or '0'/'1'
        if ((int)$job['is_active'] !== 1) {
            return false;
        }
    }

    /* ===============================
       DEADLINE CHECK
       =============================== */
    if (!empty($job['deadline'])) {
        if (strtotime($job['deadline']) < time()) {
            return false;
        }
    }

    return true;
}
