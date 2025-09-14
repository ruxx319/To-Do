<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function getStatusBadge($status) {
    if ($status === 'completed') {
        return '<span class="badge completed">Completed</span>';
    } else {
        return '<span class="badge pending">Pending</span>';
    }
}
?>