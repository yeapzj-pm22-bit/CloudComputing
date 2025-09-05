<?php
// includes/bootstrap.php - Application Bootstrap File

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Define application constants
define('APP_ROOT', dirname(__DIR__));
define('APP_URL', 'http://localhost/university-enrollment-system');
define('UPLOAD_DIR', APP_ROOT . '/public/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Include helper classes
require_once APP_ROOT . '/helpers/DatabaseConnection.php';
require_once APP_ROOT . '/helpers/ValidationHelper.php';
require_once APP_ROOT . '/helpers/ResponseHelper.php';
require_once APP_ROOT . '/helpers/SessionHelper.php';
require_once APP_ROOT . '/helpers/FileUploadHelper.php';

// Include model classes (BaseModel must be first since others extend it)
require_once APP_ROOT . '/models/BaseModel.php';
require_once APP_ROOT . '/models/User.php';
require_once APP_ROOT . '/models/Application.php';
require_once APP_ROOT . '/models/Notification.php';
require_once APP_ROOT . '/models/Document.php';

// Only include files that exist
$optional_models = [
    'Academic.php',
    'Educational.php',
    'StatusHistory.php',
    'SystemSettings.php'
];

foreach ($optional_models as $model) {
    $file_path = APP_ROOT . '/models/' . $model;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Include services if they exist
$optional_services = [
    'ApplicationService.php'
];

foreach ($optional_services as $service) {
    $file_path = APP_ROOT . '/services/' . $service;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Start session
SessionHelper::start();
?>