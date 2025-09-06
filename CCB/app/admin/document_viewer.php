<?php
// admin/document_viewer.php

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
    error_log("Staff document viewer: No document ID provided from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    exit('Document ID required');
}

// Check if this is a download request
$isDownload = isset($_GET['download']) && $_GET['download'] == '1';

error_log("Staff document viewer: Serving S3 document $documentId for staff user " . $_SESSION['user_id'] . 
          ($isDownload ? ' (download)' : ' (preview)'));

// Use S3FileUploadHelper instead of FileUploadHelper to serve the file securely
S3FileUploadHelper::serveFile($documentId, $_SESSION['user_id'], $isDownload);
?>
