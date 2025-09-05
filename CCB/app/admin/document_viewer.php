<?php
// staff/document_viewer.php

session_start();
require_once '../includes/bootstrap.php';

error_log("Staff document viewer - Account type: " . ($_SESSION['account_type'] ?? 'NOT SET'));
error_log("Staff document viewer - Raw account type: '" . $_SESSION['account_type'] . "'");

// Check if user is logged in and is staff (Admin or other staff roles)
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['account_type'], ['Admin', 'Staff'])
) {
    error_log("Staff document viewer: Access denied for user " . ($_SESSION['user_id'] ?? 'NOT SET') . 
              " with account type " . ($_SESSION['account_type'] ?? 'NOT SET'));
    http_response_code(403);
    exit('Access denied');
}

// Get document ID from URL
$documentId = $_GET['id'] ?? 0;

if (!$documentId) {
    error_log("Staff document viewer: No document ID provided");
    http_response_code(400);
    exit('Document ID required');
}

error_log("Staff document viewer: Attempting to serve document $documentId for staff user " . $_SESSION['user_id']);

// Use FileUploadHelper to serve the file securely
FileUploadHelper::serveFile($documentId, $_SESSION['user_id']);
?>