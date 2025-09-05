<?php
// student/document_viewer.php - Create this new file

session_start();
require_once '../includes/bootstrap.php';

error_log("Account type: " . ($_SESSION['account_type'] ?? 'NOT SET'));
error_log("Raw account type: '" . $_SESSION['account_type'] . "'");
// Check if user is logged in and is a student
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['account_type'], ['Student', 'Admin'])
) {
    http_response_code(403);
    exit('Access denied');
}

// Get document ID from URL
$documentId = $_GET['id'] ?? 0;

if (!$documentId) {
    error_log("Document viewer: No document ID provided");
    http_response_code(400);
    exit('Document ID required');
}

error_log("Document viewer: Attempting to serve document $documentId for user " . $_SESSION['user_id']);

if (!$documentId) {
    http_response_code(400);
    exit('Document ID required');
}

// Use FileUploadHelper to serve the file securely
FileUploadHelper::serveFile($documentId, $_SESSION['user_id']);
?>