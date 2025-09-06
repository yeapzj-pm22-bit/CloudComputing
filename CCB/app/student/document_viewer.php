<?php
// student/document_viewer.php 

session_start();
require_once '../includes/bootstrap.php';

// Same security checks as before
if (!isset($_SESSION['user_id']) || 
    !in_array($_SESSION['account_type'], ['Student', 'Admin'])) {
    http_response_code(403);
    exit('Access denied');
}

$documentId = $_GET['id'] ?? 0;

if (!$documentId) {
    error_log("Document viewer: No document ID provided from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    exit('Document ID required');
}

error_log("Document viewer: Serving S3 document $documentId for user " . $_SESSION['user_id']);





$isDownload = isset($_GET['download']) && $_GET['download'] == '1';
error_log("Document viewer: Serving S3 document $documentId for user " . $_SESSION['user_id'] . 
          ($isDownload ? ' (download)' : ' (preview)'));
S3FileUploadHelper::serveFile($documentId, $_SESSION['user_id'], $isDownload);
?>
