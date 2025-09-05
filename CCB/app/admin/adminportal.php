<?php
//admin/adminportal.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

// Include bootstrap file for database connection and classes
require_once '../includes/bootstrap.php';

// Initialize classes
$user = new User();
$notification = new Notification();
$application = new Application();

// Get admin info
$adminInfo = $user->getUserById($_SESSION['user_id']);
$unreadNotifications = $notification->getUnreadCount($_SESSION['user_id']);

function checkAndNotifyNewApplications()
{
    global $application, $notification, $user;

    try {
        // Get all admin users
        $admins = $user->getAllAdminUsers();
        if (!$admins) {
            return;
        }

        // Check for applications submitted in the last 5 minutes that don't have admin notifications yet
        $recentApplications = $application->getRecentApplicationsWithoutAdminNotifications(5);

        if (empty($recentApplications)) {
            return;
        }

        foreach ($recentApplications as $app) {
            foreach ($admins as $admin) {
                // Create notification for each admin about the new application
                $notification->createNotification(
                    $admin['user_id'],
                    'New Application Submitted',
                    "A new application #{$app['application_number']} has been submitted by {$app['student_name']} for {$app['program']}.",
                    'info',
                    $app['personal_id']
                );
            }

            // Mark this application as having admin notifications created
            $application->markAdminNotificationsCreated($app['personal_id']);
        }
    } catch (Exception $e) {
        error_log("Error checking new applications: " . $e->getMessage());
    }
}

// Call the function to check for new applications
checkAndNotifyNewApplications();


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'update_student_status':
                echo json_encode(updateStudentStatus($_POST['student_id'], $_POST['is_active']));
                break;
            case 'get_dashboard_stats':
                echo json_encode(getDashboardStats());
                break;
            case 'get_applications':
                echo json_encode(getApplications());
                break;
            case 'get_application_details':
                echo json_encode(getApplicationDetails($_POST['application_id']));
                break;
            case 'update_application_status':
                echo json_encode(updateApplicationStatus($_POST['application_id'], $_POST['status'], $_POST['notes'] ?? ''));
                break;
            case 'get_students':
                echo json_encode(getStudents());
                break;
            case 'get_notifications':
                echo json_encode(getNotifications());
                break;
            case 'get_student_details':
                echo json_encode(getStudentDetails($_POST['student_id']));
                break;
            case 'mark_notification_read':
                echo json_encode(markNotificationRead($_POST['notification_id']));
                break;
            case 'mark_all_notifications_read':
                echo json_encode(markAllNotificationsRead());
                break;
            case 'delete_notification':
                echo json_encode(deleteNotification($_POST['notification_id']));
                break;
            case 'send_bulk_notification':
                echo json_encode(sendBulkNotification($_POST['user_ids'], $_POST['title'], $_POST['message'], $_POST['type'] ?? 'info'));
                break;
            case 'check_new_applications':
                checkAndNotifyNewApplications();
                echo json_encode(['success' => true, 'message' => 'Checked for new applications']);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Admin Portal Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

function getStudentDetails($studentId)
{
    global $user;

    try {
        $student = $user->getUserById($studentId);

        if ($student) {
            return [
                'success' => true,
                'student' => $student
            ];
        } else {
            return ['success' => false, 'message' => 'Student not found'];
        }
    } catch (Exception $e) {
        error_log("Get student details error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function updateStudentStatus($studentId, $isActive)
{
    global $user;

    try {
        // Only allow admins to update student status
        if ($_SESSION['account_type'] !== 'Admin') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Convert string to boolean
        $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);

        $result = $user->updateStudentStatus($studentId, $isActive, $_SESSION['user_id']);

        return $result;
    } catch (Exception $e) {
        error_log("Update student status error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getNotifications()
{
    global $notification;

    try {
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $unreadOnly = isset($_POST['unread_only']) ? (bool)$_POST['unread_only'] : false;

        $notifications = $notification->getUserNotifications($_SESSION['user_id'], $limit);

        if ($notifications !== false) {
            return [
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $notification->getUnreadCount($_SESSION['user_id'])
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to load notifications'];
        }
    } catch (Exception $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function markNotificationRead($notificationId)
{
    global $notification;

    try {
        $result = $notification->markNotificationRead($notificationId, $_SESSION['user_id']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $notification->getUnreadCount($_SESSION['user_id'])
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to mark notification as read'];
        }
    } catch (Exception $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function markAllNotificationsRead()
{
    global $notification;

    try {
        $result = $notification->markAllAsReadForUser($_SESSION['user_id']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to mark all notifications as read'];
        }
    } catch (Exception $e) {
        error_log("Mark all notifications read error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function deleteNotification($notificationId)
{
    global $notification;

    try {
        $result = $notification->deleteNotification($notificationId, $_SESSION['user_id']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Notification deleted',
                'unread_count' => $notification->getUnreadCount($_SESSION['user_id'])
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to delete notification'];
        }
    } catch (Exception $e) {
        error_log("Delete notification error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function sendBulkNotification($userIds, $title, $message, $type = 'info')
{
    global $notification;

    try {
        // Only allow admins to send bulk notifications
        if ($_SESSION['account_type'] !== 'Admin') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $result = $notification->sendBulkNotification($userIds, $title, $message, $type);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Bulk notification sent successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to send bulk notification'];
        }
    } catch (Exception $e) {
        error_log("Send bulk notification error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function createApplicationStatusNotification($applicationId, $newStatus, $adminUserId)
{
    global $application, $notification;

    try {
        // Get application details
        $appDetails = $application->getApplicationDetailsForAdmin($applicationId);
        if (!$appDetails || !isset($appDetails['application']['user_id'])) {
            return false;
        }

        $studentUserId = $appDetails['application']['user_id'];
        $applicationNumber = $appDetails['application']['application_number'] ?? 'N/A';

        // Create different notification messages based on status
        $notifications = getStatusNotificationData($newStatus, $applicationNumber);

        // Create the notification
        $result = $notification->createNotification(
            $studentUserId,
            $notifications['title'],
            $notifications['message'],
            $notifications['type'],
            $applicationId
        );

        return $result !== false;
    } catch (Exception $e) {
        error_log("Create status notification error: " . $e->getMessage());
        return false;
    }
}

function getStatusNotificationData($status, $applicationNumber)
{
    $statusMessages = [
        'submitted' => [
            'title' => 'Application Received',
            'message' => "Your application #{$applicationNumber} has been successfully submitted and is being processed.",
            'type' => 'info'
        ],
        'under-review' => [
            'title' => 'Application Under Review',
            'message' => "Your application #{$applicationNumber} is now under review by our admissions team.",
            'type' => 'info'
        ],
        'interview-scheduled' => [
            'title' => 'Interview Scheduled',
            'message' => "Congratulations! An interview has been scheduled for your application #{$applicationNumber}. Please check your email for details.",
            'type' => 'success'
        ],
        'approved' => [
            'title' => 'Application Approved!',
            'message' => "Excellent news! Your application #{$applicationNumber} has been approved. Check your email for next steps.",
            'type' => 'success'
        ],
        'rejected' => [
            'title' => 'Application Decision',
            'message' => "We regret to inform you that your application #{$applicationNumber} was not approved at this time.",
            'type' => 'warning'
        ],
        'waitlisted' => [
            'title' => 'Application Waitlisted',
            'message' => "Your application #{$applicationNumber} has been placed on our waitlist. We will notify you of any updates.",
            'type' => 'info'
        ],
        'enrolled' => [
            'title' => 'Welcome to Our University!',
            'message' => "Congratulations! You are now officially enrolled. Your application #{$applicationNumber} process is complete.",
            'type' => 'success'
        ]
    ];

    return $statusMessages[$status] ?? [
        'title' => 'Application Status Updated',
        'message' => "Your application #{$applicationNumber} status has been updated to: " . ucfirst(str_replace('-', ' ', $status)),
        'type' => 'info'
    ];
}

function getApplications()
{
    global $application;

    try {
        // FIXED: Use $_POST instead of $_GET for AJAX requests
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = 10;

        // Build filters - FIXED: Get from $_POST
        $filters = [
            'search' => $_POST['search'] ?? '',
            'status' => $_POST['status'] ?? '',
            'program' => $_POST['program'] ?? '',
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? ''
        ];

        // Get all applications for admin
        $result = $application->getAllApplicationsForAdmin(null, $page, $limit, $filters);

        if ($result) {
            return [
                'success' => true,
                'applications' => $result
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to load applications'];
        }
    } catch (Exception $e) {
        error_log("Get applications error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getApplicationDetails($applicationId)
{
    global $application;

    try {
        // Get application details for admin view
        $appDetails = $application->getApplicationDetailsForAdmin($applicationId);

        if ($appDetails) {
            return [
                'success' => true,
                'application' => $appDetails['application'],
                'documents' => $appDetails['documents'] ?? [],
                'status_history' => $appDetails['status_history'] ?? []
            ];
        } else {
            return ['success' => false, 'message' => 'Application not found'];
        }
    } catch (Exception $e) {
        error_log("Get application details error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function updateApplicationStatus($applicationId, $newStatus, $notes = '')
{
    global $application, $notification;

    try {
        // Update application status
        $result = $application->updateApplicationStatusByAdmin($applicationId, $newStatus, $_SESSION['user_id'], $notes);

        if ($result['success']) {
            // Get application details to send notification to applicant
            $appDetails = $application->getApplicationDetailsForAdmin($applicationId);
            if ($appDetails && isset($appDetails['application']['user_id'])) {
                $notification->createNotification(
                    $appDetails['application']['user_id'],
                    'Application Status Updated',
                    "Your application status has been changed to: " . ucfirst(str_replace('-', ' ', $newStatus)),
                    'info',
                    $applicationId
                );
            }

            return ['success' => true, 'message' => 'Application status updated successfully'];
        } else {
            return ['success' => false, 'message' => $result['message'] ?? 'Failed to update status'];
        }
    } catch (Exception $e) {
        error_log("Update status error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getStudents()
{
    global $user;

    try {
        // Get filter parameters
        $filters = [
            'search' => $_POST['search'] ?? '',
            'status' => $_POST['status'] ?? '',
            'program' => $_POST['program'] ?? ''
        ];

        // ✅ Fix: Pass filters in correct position (4th parameter)
        $students = $user->getAllStudentsForAdmin(null, null, 20, $filters);

        if ($students !== false) {
            return ['success' => true, 'students' => $students];
        } else {
            return ['success' => false, 'message' => 'Failed to load students'];
        }
    } catch (Exception $e) {
        error_log("Get students error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getDashboardStats()
{
    global $application, $user;

    try {
        // Get application statistics
        $stats = $application->getAdminDashboardStats();

        if ($stats) {
            // Get recent applications (last 5) - FIXED: Pass null for $page
            $recentApplications = $application->getAllApplicationsForAdmin(5, null);

            // FIXED: Ensure recentApplications is always an array
            if ($recentApplications === false || !is_array($recentApplications)) {
                $recentApplications = [];
            }

            return [
                'success' => true,
                'stats' => $stats,
                'recent_applications' => $recentApplications
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to load dashboard stats'];
        }
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #16a34a;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1e293b;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        /* ===== SIDEBAR STYLES ===== */
        .sidebar {
            background: linear-gradient(180deg, var(--dark-color) 0%, #334155 100%);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            transform: translateX(4px);
        }

        /* ===== MAIN CONTENT STYLES ===== */
        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background-color: #f8fafc;
            border: none;
            color: var(--dark-color);
            font-weight: 600;
            padding: 16px;
        }

        .table td {
            padding: 16px;
            border-color: #e2e8f0;
            vertical-align: middle;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* ===== LOADING & ANIMATIONS ===== */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-spinner {
            border: 2px solid #f3f3f3 !important;
            border-top: 2px solid var(--primary-color) !important;
            border-radius: 50% !important;
            width: 16px !important;
            height: 16px !important;
            animation: spin 1s linear infinite !important;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* ===== SECTION MANAGEMENT ===== */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* ===== MODAL BASE STYLES ===== */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        /* ===== MODAL Z-INDEX HIERARCHY ===== */
        /* Bootstrap backdrop */
        .modal-backdrop {
            z-index: 1040 !important;
        }

        /* Application Details Modal - Base layer */
        #applicationDetailsModal {
            z-index: 1050 !important;
        }

        #applicationDetailsModal .modal-dialog {
            z-index: 1051 !important;
        }

        /* Document Preview Modal - HIGHER layer (appears on top) */
        #documentPreviewModal {
            z-index: 1060 !important;
        }

        #documentPreviewModal .modal-dialog {
            z-index: 1061 !important;
        }

        #documentPreviewModal .modal-content {
            overflow: hidden !important;
        }

        /* Status Update Modal - HIGHEST layer */
        #statusUpdateModal {
            z-index: 1070 !important;
        }

        #statusUpdateModal .modal-dialog {
            z-index: 1071 !important;
        }

        /* Fullscreen Document Preview - MAXIMUM layer */
        #documentPreviewModal.fullscreen {
            z-index: 9999 !important;
        }

        #documentPreviewModal.fullscreen .modal-dialog {
            z-index: 10000 !important;
        }

        #documentPreviewModal.fullscreen .modal-content {
            z-index: 10001 !important;
        }

        /* ===== DOCUMENT PREVIEW MODAL STYLES ===== */
        #documentPreviewModal .modal-body {
            overflow: hidden !important;
            height: 80vh !important;
            min-height: 400px !important;
            padding: 0 !important;
            margin: 0 !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
        }

        #documentPreviewModal .modal-header,
        #documentPreviewModal .modal-footer {
            flex-shrink: 0 !important;
        }

        #documentPreviewModal .modal-xl {
            max-width: 95vw !important;
        }

        /* Force visibility when state is active */
        #documentPreviewModal #previewContainer[style*="display: block"] {
            display: flex !important;
            flex-direction: column !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #documentPreviewModal #previewContainer {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* ===== PDF PREVIEW STYLES ===== */
        #documentPreviewModal #pdfPreview {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #f8f9fa !important;
            overflow: hidden !important;
        }

        #documentPreviewModal #pdfPreview[style*="display: block"] {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #documentPreviewModal #pdfPreview object,
        #documentPreviewModal #pdfPreview embed,
        #documentPreviewModal #pdfPreview iframe {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: white !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* ===== IMAGE PREVIEW STYLES ===== */
        #documentPreviewModal #imagePreview[style*="display: flex"] {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #documentPreviewModal #previewImage {
            max-height: 100% !important;
            max-width: 100% !important;
            object-fit: contain !important;
        }

        /* ===== LOADING & ERROR STATES ===== */
        #documentPreviewModal #previewLoading[style*="display: none"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            top: -9999px !important;
        }

        #documentPreviewModal #previewLoading {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: white !important;
            z-index: 10 !important;
        }

        #documentPreviewModal #previewError[style*="display: none"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        #documentPreviewModal #previewError {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: white !important;
        }

        /* ===== FULLSCREEN MODAL STYLES ===== */
        #documentPreviewModal.fullscreen .modal-dialog {
            max-width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
        }

        #documentPreviewModal.fullscreen .modal-content {
            height: 100vh !important;
            border-radius: 0 !important;
        }

        #documentPreviewModal.fullscreen .modal-body {
            height: calc(100vh - 120px) !important;
        }

        /* ===== DOCUMENT CARD STYLES ===== */
        .document-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        #documentPreviewModal.fullscreen #previewContainer,
        #documentPreviewModal.fullscreen #pdfPreview {
            height: calc(100vh - 120px) !important;
        }

        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .document-icon {
            min-width: 40px;
        }

        .document-meta .badge-sm {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }

        .document-actions .btn {
            transition: all 0.2s ease;
        }

        .document-actions .btn:hover {
            transform: translateY(-1px);
        }

        /* ===== UTILITY CLASSES ===== */
        .min-width-0 {
            min-width: 0;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Override any conflicting admin styles for preview modal */
        #documentPreviewModal * {
            transition: none !important;
        }

        /* Ensure the preview content appears above everything */
        #documentPreviewModal .modal-body,
        #documentPreviewModal #previewContainer,
        #documentPreviewModal #pdfPreview,
        #documentPreviewModal #imagePreview {
            position: relative !important;
            z-index: 1 !important;
        }

        #documentPreviewModal #imagePreview {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
            margin: 0 !important;
        }

        /* ===== RESPONSIVE STYLES ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            #documentPreviewModal .modal-xl {
                max-width: 98vw !important;
                margin: 1rem !important;
            }

            #documentPreviewModal .modal-body {
                height: 60vh !important;
            }
        }

        /* Notification-specific styles */
        .notification-dropdown .dropdown-item {
            white-space: normal;
            padding: 0.75rem 1rem;
        }

        .notification-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .notification-badge {
            font-size: 0.7rem;
            min-width: 1.2rem;
            height: 1.2rem;
            line-height: 1.2rem;
        }

        .notification-toast {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #notificationCenterContent {
            min-height: 200px;
        }

        #notificationCenterContent .border-bottom:last-child {
            border-bottom: none !important;
        }

        /* Animation for new notifications */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-dropdown .dropdown-item {
            animation: fadeInDown 0.3s ease;
        }

        .table-secondary {
            opacity: 0.7;
        }

        .table-secondary .status-badge {
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-graduation-cap me-2"></i>
                Admin Portal
            </h4>
            <div class="text-light small mb-3">
                Welcome, <?php echo htmlspecialchars($adminInfo['first_name'] . ' ' . $adminInfo['last_name']); ?>
            </div>
        </div>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-section="applications">
                    <i class="fas fa-file-alt me-2"></i>Applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-section="students">
                    <i class="fas fa-users me-2"></i>Students
                </a>
            </li>

        </ul>

        <div class="mt-auto p-4">
            <a href="../index.php?logout=1" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" id="pageTitle">Admin Dashboard</h2>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle position-relative"
                        id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell me-2"></i>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill notification-badge"
                            style="display: none;">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                        <li>
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" id="markAllNotificationsRead" title="Mark all as read">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary me-1" id="refreshNotifications" title="Refresh">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>

                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <div class="notification-dropdown">
                            <li><span class="dropdown-item-text text-muted text-center py-3">Loading notifications...</span></li>
                        </div>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <div class="text-center p-2">
                                <button class="btn btn-sm btn-primary" onclick="showNotificationCenter()">
                                    <i class="fas fa-list me-1"></i>View All
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- 2. ADD NOTIFICATION CENTER MODAL -->
                <!-- Add this modal after your existing modals -->

                <div class="modal fade" id="notificationCenterModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-bell me-2"></i>Notification Center
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <!-- Notification Filters -->
                                <div class="p-3 border-bottom bg-light">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <select class="form-select form-select-sm" id="notificationTypeFilter">
                                                <option value="">All Types</option>
                                                <option value="info">Info</option>
                                                <option value="success">Success</option>
                                                <option value="warning">Warning</option>
                                                <option value="error">Error</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select form-select-sm" id="notificationReadFilter">
                                                <option value="">All</option>
                                                <option value="unread">Unread Only</option>
                                                <option value="read">Read Only</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notification List -->
                                <div id="notificationCenterContent" style="max-height: 60vh; overflow-y: auto;">
                                    <div class="text-center py-4">
                                        <div class="loading-spinner mb-3"></div>
                                        <span>Loading notifications...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" id="clearAllNotifications">
                                    <i class="fas fa-trash me-2"></i>Clear All Read
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ADD BULK NOTIFICATION MODAL (Admin Only) -->
                <div class="modal fade" id="bulkNotificationModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-bullhorn me-2"></i>Send Bulk Notification
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="bulkNotificationMessages" style="display: none;"></div>

                                <form id="bulkNotificationForm">
                                    <div class="mb-3">
                                        <label class="form-label">Recipients <span class="text-danger">*</span></label>
                                        <select class="form-select" id="recipientType" required>
                                            <option value="">Select recipient group</option>
                                            <option value="all_students">All Students</option>
                                            <option value="all_users">All Users</option>
                                            <option value="pending_applications">Students with Pending Applications</option>
                                            <option value="approved_applications">Students with Approved Applications</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="notificationTitle" required
                                            placeholder="Enter notification title">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Message <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="notificationMessage" rows="4" required
                                            placeholder="Enter your message..."></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" id="notificationType">
                                            <option value="info">Info</option>
                                            <option value="success">Success</option>
                                            <option value="warning">Warning</option>
                                            <option value="error">Error</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="sendBulkNotificationBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Send Notification
                                </button>
                            </div>
                        </div>
                    </div>
                </div>






                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($adminInfo['first_name']); ?>
                    </button>
                    <ul class="dropdown-menu">


                        <li><a class="dropdown-item" href="../index.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="section active">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-file-alt fa-3x text-primary"></i>
                        </div>
                        <h3 class="mb-1" id="totalApplications">-</h3>
                        <p class="text-muted mb-0">Total Applications</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-clock fa-3x text-warning"></i>
                        </div>
                        <h3 class="mb-1" id="pendingApplications">-</h3>
                        <p class="text-muted mb-0">Pending Review</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h3 class="mb-1" id="approvedApplications">-</h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="mb-3">
                            <i class="fas fa-users fa-3x text-info"></i>
                        </div>
                        <h3 class="mb-1" id="enrolledStudents">-</h3>
                        <p class="text-muted mb-0">Enrolled Students</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="data-table">
                        <div class="p-4">
                            <h5 class="mb-3">Recent Applications</h5>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Student Name</th>
                                            <th>Program</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentApplicationsTable">
                                        <tr>
                                            <td colspan="6" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="data-table">
                        <div class="p-4">
                            <h5 class="mb-3">Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="showSection('applications')">
                                    <i class="fas fa-file-alt me-2"></i>Manage Applications
                                </button>
                                <button class="btn btn-warning" onclick="showSection('students')">
                                    <i class="fas fa-users me-2"></i>Manage Students
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div id="applications-section" class="section">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="applicationSearch" placeholder="Search applications...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="submitted">Submitted</option>
                                <option value="under-review">Under Review</option>
                                <option value="interview-scheduled">Interview Scheduled</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="waitlisted">Waitlisted</option>
                                <option value="enrolled">Enrolled</option>

                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Program</label>
                            <select class="form-select" id="programFilter">
                                <option value="">All Programs</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Law">Law</option>
                                <option value="Education">Education</option>
                                <option value="Arts and Humanities">Arts and Humanities</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" id="dateFromFilter">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" id="dateToFilter">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Application ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsTable">
                            <tr>
                                <td colspan="7" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    <nav id="applicationsPagination">
                        <!-- Pagination will be loaded here -->
                    </nav>
                </div>
            </div>
        </div>

        <!-- Students Section -->
        <div id="students-section" class="section">
            <div class="data-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Program</th>
                                <th>Status</th>
                                <th>Registered Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTable">
                            <tr>
                                <td colspan="8" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="section">
            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Application Reports</h5>
                            <p class="card-text">Generate comprehensive reports on applications, enrollments, and student data.</p>
                            <button class="btn btn-primary">Generate Report</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="settings-section" class="section">
            <div class="row g-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">System Settings</h5>
                            <p class="card-text">Configure system-wide settings and preferences.</p>
                            <button class="btn btn-primary">Manage Settings</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal - Following student portal pattern -->
    <div class="modal fade" id="applicationDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Application Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="applicationDetailsContent">
                        <div class="text-center py-4">
                            <div class="loading-spinner"></div>
                            <span class="ms-2">Loading application details...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateStatusBtn">
                        <i class="fas fa-edit me-2"></i>Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Preview Modal - Same as student portal -->
    <div class="modal fade" id="documentPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        <span id="previewFileName">Document Preview</span>
                    </h5>
                    <div class="ms-auto d-flex align-items-center me-3">
                        <button type="button" class="btn btn-outline-light btn-sm me-2" id="downloadDocBtn" title="Download">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" id="fullscreenBtn" title="Toggle Fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh; overflow: hidden;">
                    <!-- Loading state -->
                    <div id="previewLoading" class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center">
                            <div class="loading-spinner mb-3"></div>
                            <span>Loading preview...</span>
                        </div>
                    </div>

                    <!-- Error state -->
                    <div id="previewError" class="d-flex justify-content-center align-items-center h-100" style="display: none;">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>Preview Not Available</h5>
                            <p class="text-muted mb-3">This file type cannot be previewed in the browser.</p>
                            <button class="btn btn-primary" id="downloadFallbackBtn">
                                <i class="fas fa-download me-2"></i>Download File
                            </button>
                        </div>
                    </div>

                    <!-- Preview container -->
                    <div id="previewContainer" class="h-100" style="display: none;">
                        <!-- For images -->
                        <div id="imagePreview" class="h-100 d-flex justify-content-center align-items-center p-3" style="display: none;">
                            <img id="previewImage" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>

                        <!-- For PDFs -->
                        <div id="pdfPreview" class="h-100" style="display: none;">
                            <iframe id="pdfFrame" class="w-100 h-100" style="border: none;"></iframe>
                        </div>

                        <!-- For text/unsupported files -->
                        <div id="textPreview" class="h-100 p-4" style="display: none; overflow-y: auto;">
                            <div class="bg-light p-4 rounded">
                                <h6>File Information</h6>
                                <div id="fileInfo">
                                    <!-- File details will be populated here -->
                                </div>
                                <hr>
                                <div class="text-center">
                                    <p class="text-muted">This file type requires downloading to view.</p>
                                    <button class="btn btn-primary" id="downloadTextBtn">
                                        <i class="fas fa-download me-2"></i>Download File
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <div>
                            <span class="badge bg-secondary" id="fileSizeBadge">-</span>
                            <span class="badge bg-info ms-1" id="fileTypeBadge">-</span>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Application Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Error/Success Messages -->
                    <div id="statusUpdateMessages" style="display: none;">
                        <!-- Messages will be inserted here -->
                    </div>

                    <form id="statusUpdateForm">
                        <input type="hidden" id="updateApplicationId">
                        <input type="hidden" id="currentApplicationStatus">

                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <div class="form-control-plaintext">
                                <span id="currentStatusDisplay" class="badge bg-secondary">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="newStatus" required>
                                <option value="">Select Status</option>
                                <!-- Options will be populated dynamically based on current status -->
                            </select>
                            <div class="form-text" id="statusHelpText">
                                Available status changes are limited based on the current status.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="statusNotes" rows="3"
                                placeholder="Add any notes about this status change..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn" onclick="saveStatusUpdate()">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const studentFilterStyles = `
    .searching {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
    }
    
    .filter-active {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
    }
    
    .table-loading-overlay {
        transition: opacity 0.3s ease;
    }
    
    #studentsTable {
        transition: opacity 0.3s ease;
    }
    
    .student-filter-toast {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .form-text {
        font-size: 0.8rem;
        color: #6c757d;
    }
`;
        // Define valid status transitions
        const STATUS_TRANSITIONS = {
            'submitted': ['under-review'],
            'under-review': ['interview-scheduled', 'rejected'],
            'interview-scheduled': ['approved', 'rejected'],
            'approved': ['waitlisted', 'enrolled'],
            'waitlisted': ['approved', 'rejected', 'enrolled'],
            'rejected': [], // Cannot change from rejected
            'enrolled': [] // Cannot change from enrolled
        };

        // Status display names
        const STATUS_DISPLAY_NAMES = {
            'all': 'All',
            'submitted': 'Submitted',
            'under-review': 'Under Review',
            'interview-scheduled': 'Interview Scheduled',
            'approved': 'Approved',
            'rejected': 'Rejected',
            'waitlisted': 'Waitlisted',
            'enrolled': 'Enrolled'
        };
        // Global variables
        let currentPage = 1;
        let currentFilters = {};
        let currentApplicationId = null;
        let currentViewingApplication = null;
        let previewState = {
            currentDocument: null,
            isLoading: false,
            loadTimeout: null,
            modalInstance: null
        };

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initializeSidebar();
            loadDashboardData();
            setupEventListeners();

            if (!window.adminNotificationManager) {
                window.adminNotificationManager = new AdminNotificationManager();
            }

            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
                const now = new Date();
                dateElement.textContent = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            // Initialize the smooth filter system
            window.applicationFilter = new SmoothApplicationFilter();

            // Load filters from URL if any
            applicationFilter.loadFiltersFromUrl();
            const style = document.createElement('style');
            style.textContent = `
        .table-loading-overlay {
            transition: opacity 0.3s ease;
        }
        
        #applicationsTable {
            transition: opacity 0.3s ease;
        }
        
        .searching {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
        }
        
        .filter-active {
            transition: all 0.2s ease;
        }
        
        .page-link {
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            transform: translateY(-1px);
        }
        
        .btn-group .btn {
            transition: all 0.2s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-1px);
            z-index: 2;
        }
    `;
            document.head.appendChild(style);

            const originalShowSection = window.showSection;
            window.showSection = function(sectionName) {
                originalShowSection(sectionName);

                if (sectionName === 'students') {
                    // Initialize enhanced student management
                    setTimeout(() => {
                        initializeStudentManagement();
                        applyStudentFilters(); // Load students with filters
                    }, 100);
                }
            };

        });

        // Sidebar navigation
        function initializeSidebar() {
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.dataset.section;
                    if (section) {
                        showSection(section);
                        setActiveNavLink(this);
                    }
                });
            });
        }

        function showSection(sectionName) {
            // Update page title
            const titles = {
                'dashboard': 'Admin Dashboard',
                'applications': 'Application Management',
                'students': 'Student Management',
                'reports': 'Reports & Analytics',
                'settings': 'System Settings'
            };
            document.getElementById('pageTitle').textContent = titles[sectionName] || 'Admin Dashboard';

            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');

                // Load section-specific data
                switch (sectionName) {
                    case 'applications':
                        loadApplications();
                        break;
                    case 'students':
                        loadStudents();
                        break;
                }
            }

            const originalShowSection = window.showSection;
            window.showSection = function(sectionName) {
                originalShowSection(sectionName);

                if (sectionName === 'students') {
                    // Initialize enhanced student management with real-time filtering
                    setTimeout(() => {
                        initializeStudentManagement(); // This now includes the real-time filter
                    }, 100);
                }
            };

        }

        function setActiveNavLink(activeLink) {
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            activeLink.classList.add('active');
        }

        // Dashboard functions
        function loadDashboardData() {
            showLoading(true);

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_dashboard_stats'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Dashboard data received:', data);
                    if (data.success) {
                        updateDashboardStats(data.stats);

                        // FIXED: Better handling of array vs paginated object
                        let applications = data.recent_applications;

                        // Check if it's a paginated object (not an array) with a data property
                        if (applications && !Array.isArray(applications) && applications.data) {
                            // It's a paginated response, extract the data array
                            applications = applications.data;
                        }

                        // Ensure we have an array (fallback to empty array if not)
                        if (!Array.isArray(applications)) {
                            console.warn('Expected applications array, got:', typeof applications, applications);
                            applications = [];
                        }

                        populateRecentApplicationsTable(applications);
                    } else {
                        showAlert('Error loading dashboard data: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error loading dashboard data', 'danger');
                })
                .finally(() => {
                    showLoading(false);
                });
        }

        function updateDashboardStats(stats) {
            document.getElementById('totalApplications').textContent = stats.total_applications || 0;
            document.getElementById('pendingApplications').textContent = stats.pending_applications || 0;
            document.getElementById('approvedApplications').textContent = stats.approved_applications || 0;
            document.getElementById('enrolledStudents').textContent = stats.enrolled_students || 0;
        }

        function populateRecentApplicationsTable(applications) {
            const tbody = document.getElementById('recentApplicationsTable');
            if (!tbody) {
                console.error('Recent applications table body not found');
                return;
            }

            console.log('Populating table with applications:', applications);

            // FIXED: Better validation and error handling
            if (!applications) {
                console.warn('Applications is null or undefined');
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No recent applications</td></tr>';
                return;
            }

            if (!Array.isArray(applications)) {
                console.error('Applications is not an array:', typeof applications, applications);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error: Invalid data format</td></tr>';
                return;
            }

            if (applications.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No recent applications</td></tr>';
                return;
            }

            try {
                tbody.innerHTML = applications.map(app => {
                    // Validate each application object
                    if (!app || typeof app !== 'object') {
                        console.warn('Invalid application object:', app);
                        return '';
                    }

                    return `
                <tr>
                    <td><strong>${app.application_number || 'N/A'}</strong></td>
                    <td>${(app.first_name || '') + ' ' + (app.last_name || '')}</td>
                    <td>${app.program || 'N/A'}</td>
                    <td><span class="status-badge bg-${getStatusColor(app.status)}">${app.status || 'N/A'}</span></td>
                    <td>${formatDate(app.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="viewApplication(${app.personal_id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success" onclick="showStatusUpdate(${app.personal_id})" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
                }).filter(row => row !== '').join(''); // Filter out any empty rows

            } catch (error) {
                console.error('Error generating table HTML:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error displaying applications</td></tr>';
            }
        }

        // Application management functions
        function loadApplications(page = 1) {
            if (window.applicationFilter) {
                applicationFilter.loadApplicationsSmooth(page);
            }
        }

        function populateApplicationsTable(applications) {
            const tbody = document.getElementById('applicationsTable');
            if (!tbody) return;

            if (!applications || applications.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No applications found</td></tr>';
                return;
            }

            tbody.innerHTML = applications.map(app => `
                <tr>
                    <td><strong>${app.application_number || 'N/A'}</strong></td>
                    <td>${app.first_name || ''} ${app.last_name || ''}</td>
                    <td>${app.email || 'N/A'}</td>
                    <td>${app.program || 'N/A'}</td>
                    <td><span class="status-badge bg-${getStatusColor(app.status)}">${app.status}</span></td>
                    <td>${formatDate(app.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="viewApplication(${app.personal_id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success" onclick="showStatusUpdate(${app.personal_id})" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function applyFilters() {
            if (window.applicationFilter) {
                applicationFilter.applyFiltersSmooth();
            }
        }

        function viewApplication(applicationId) {
            console.log('Viewing application:', applicationId);

            const modal = new bootstrap.Modal(document.getElementById('applicationDetailsModal'));
            modal.show();

            // Load application details
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=get_application_details&application_id=${applicationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentViewingApplication = data.application;
                        renderApplicationDetails(data.application, data.documents, data.status_history);

                        // Setup update status button
                        const updateBtn = document.getElementById('updateStatusBtn');
                        updateBtn.onclick = () => showStatusUpdate(applicationId);
                    } else {
                        showAlert(data.message || 'Failed to load application details', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error loading application details:', error);
                    showAlert('Error loading application details', 'danger');
                });
        }

        function renderApplicationDetails(appData, documents, statusHistory) {
            const container = document.getElementById('applicationDetailsContent');

            // Document display with preview functionality
            let documentsHtml = '';
            if (documents && documents.length > 0) {
                documentsHtml = `
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-upload me-2"></i>Documents (${documents.length})</h6>
                        <div class="row">
                            ${documents.map(doc => `
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card document-card h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start mb-2">
                                                <div class="document-icon me-2">
                                                    <i class="fas ${getDocumentIcon(doc.document_type)} fa-2x text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 min-width-0">
                                                    <h6 class="card-title mb-1 text-truncate" title="${doc.original_filename}">
                                                        ${doc.original_filename || 'Unknown File'}
                                                    </h6>
                                                    <small class="text-muted">${formatDocumentType(doc.document_type)}</small>
                                                </div>
                                            </div>
                                            
                                            <div class="document-meta mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="badge bg-${getVerificationStatusColor(doc.verification_status)} badge-sm">
                                                        ${(doc.verification_status || 'pending').toUpperCase()}
                                                    </span>
                                                    <small class="text-muted">${formatFileSize(doc.file_size)}</small>
                                                </div>
                                                <small class="text-info d-block">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ${formatDate(doc.upload_date)}
                                                </small>
                                            </div>
                                            
                                            <div class="document-actions">
                                                <button class="btn btn-primary btn-sm w-100" 
                                                        onclick="viewDocument(${doc.document_id}, '${doc.original_filename}', '${doc.document_type}', '${doc.mime_type}', ${doc.file_size})"
                                                        title="Preview Document">
                                                    <i class="fas fa-eye me-1"></i> Preview
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>`;
            } else {
                documentsHtml = `
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-upload me-2"></i>Documents</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No documents have been uploaded for this application.
                        </div>
                    </div>
                </div>`;
            }

            const html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Application Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Application Number:</strong></td><td>${appData.application_number || 'N/A'}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge bg-${getStatusColor(appData.status)}">${(appData.status || 'submitted').replace('-', ' ').toUpperCase()}</span></td></tr>
                            <tr><td><strong>Submitted:</strong></td><td>${formatDate(appData.created_at)}</td></tr>
                            <tr><td><strong>Last Updated:</strong></td><td>${formatDate(appData.updated_at)}</td></tr>
                            ${appData.notes ? `<tr><td><strong>Notes:</strong></td><td>${appData.notes}</td></tr>` : ''}
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Personal Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Name:</strong></td><td>${appData.first_name || ''} ${appData.last_name || ''}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${appData.email || 'N/A'}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${appData.phone_number || 'N/A'}</td></tr>
                            <tr><td><strong>Gender:</strong></td><td>${appData.gender || 'N/A'}</td></tr>
                            <tr><td><strong>Nationality:</strong></td><td>${appData.nationality || 'N/A'}</td></tr>
                            <tr><td><strong>Address:</strong></td><td>${appData.address || 'N/A'}</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Program:</strong></td><td>${appData.program || 'N/A'}</td></tr>
                            <tr><td><strong>Level:</strong></td><td>${appData.program_level || 'N/A'}</td></tr>
                            <tr><td><strong>Enrollment:</strong></td><td>${appData.enrollment_type || 'N/A'}</td></tr>
                            <tr><td><strong>Start Term:</strong></td><td>${appData.start_term || 'N/A'}</td></tr>
                            ${appData.expected_graduation_year ? `<tr><td><strong>Expected Graduation:</strong></td><td>${appData.expected_graduation_year}</td></tr>` : ''}
                            ${appData.scholarship_applied ? `<tr><td><strong>Scholarship:</strong></td><td>${appData.scholarship_type || 'Applied'}</td></tr>` : ''}
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-school me-2"></i>Educational Background</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td><strong>Education Level:</strong></td><td>${appData.education_level || 'N/A'}</td></tr>
                            <tr><td><strong>Institution:</strong></td><td>${appData.institution_name || 'N/A'}</td></tr>
                            <tr><td><strong>Graduation Year:</strong></td><td>${appData.graduation_year || 'N/A'}</td></tr>
                            ${appData.grade_value ? `<tr><td><strong>Grade:</strong></td><td>${appData.grade_value} ${appData.grade_type ? '(' + appData.grade_type + ')' : ''}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                
                ${documentsHtml}
                
                ${statusHistory && statusHistory.length > 0 ? `
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-primary mb-3"><i class="fas fa-history me-2"></i>Status History</h6>
                        <div class="timeline">
                            ${statusHistory.map(history => `
                                <div class="border-bottom pb-2 mb-2">
                                    <small class="text-muted">${formatDateTime(history.change_date)}</small><br>
                                    <strong>${history.old_status || 'New'} → ${history.new_status}</strong><br>
                                    <small>by ${history.changed_by_name || 'System'}</small>
                                    ${history.change_reason ? `<br><em>${history.change_reason}</em>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>` : ''}
            `;

            container.innerHTML = html;
        }

        function showStatusUpdate(applicationId) {
            // First get the current application status
            if (currentViewingApplication && currentViewingApplication.personal_id == applicationId) {
                // Use the current viewing application data
                openStatusUpdateModal(applicationId, currentViewingApplication.status);
            } else {
                // Fetch the application details to get current status
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=get_application_details&application_id=${applicationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            openStatusUpdateModal(applicationId, data.application.status);
                        } else {
                            showModalError('Failed to load application details');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading application details:', error);
                        showModalError('Error loading application details');
                    });
            }
        }

        function openStatusUpdateModal(applicationId, currentStatus) {
            // Set hidden fields
            document.getElementById('updateApplicationId').value = applicationId;
            document.getElementById('currentApplicationStatus').value = currentStatus;

            // Display current status
            const statusBadge = document.getElementById('currentStatusDisplay');
            statusBadge.textContent = STATUS_DISPLAY_NAMES[currentStatus] || currentStatus;
            statusBadge.className = `badge bg-${getStatusColor(currentStatus)}`;

            // Clear previous messages and form
            clearModalMessages();
            document.getElementById('newStatus').value = '';
            document.getElementById('statusNotes').value = '';

            // Populate available status options
            populateStatusOptions(currentStatus);

            // Show the modal
            new bootstrap.Modal(document.getElementById('statusUpdateModal')).show();
        }

        function populateStatusOptions(currentStatus) {
            const selectElement = document.getElementById('newStatus');
            const saveButton = document.getElementById('saveStatusBtn');
            const helpText = document.getElementById('statusHelpText');

            // Clear existing options
            selectElement.innerHTML = '<option value="">Select Status</option>';

            // Get valid transitions for current status
            const validTransitions = STATUS_TRANSITIONS[currentStatus] || [];

            if (validTransitions.length === 0) {
                // No valid transitions - disable the form
                selectElement.disabled = true;
                saveButton.disabled = true;
                helpText.textContent = 'This application status cannot be changed further.';
                helpText.className = 'form-text text-warning';

                showModalError(`Applications with status "${STATUS_DISPLAY_NAMES[currentStatus]}" cannot be modified.`, 'warning');
                return;
            }

            // Enable the form
            selectElement.disabled = false;
            saveButton.disabled = false;
            helpText.textContent = `Available transitions from "${STATUS_DISPLAY_NAMES[currentStatus]}".`;
            helpText.className = 'form-text text-muted';

            // Add valid options
            validTransitions.forEach(status => {
                const option = document.createElement('option');
                option.value = status;
                option.textContent = STATUS_DISPLAY_NAMES[status] || status;
                selectElement.appendChild(option);
            });
        }

        function validateStatusTransition(currentStatus, newStatus) {
            const validTransitions = STATUS_TRANSITIONS[currentStatus] || [];
            return validTransitions.includes(newStatus);
        }

        function saveStatusUpdate() {
            const applicationId = document.getElementById('updateApplicationId').value;
            const currentStatus = document.getElementById('currentApplicationStatus').value;
            const newStatus = document.getElementById('newStatus').value;
            const notes = document.getElementById('statusNotes').value;

            // Clear previous messages
            clearModalMessages();

            // Validate required fields
            if (!newStatus) {
                showModalError('Please select a new status.');
                return;
            }

            // Validate status transition
            if (!validateStatusTransition(currentStatus, newStatus)) {
                showModalError(`Invalid status transition from "${STATUS_DISPLAY_NAMES[currentStatus]}" to "${STATUS_DISPLAY_NAMES[newStatus]}".`);
                return;
            }

            // Disable save button to prevent double submission
            const saveButton = document.getElementById('saveStatusBtn');
            const originalText = saveButton.innerHTML;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';

            // Send update request
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=update_application_status&application_id=${applicationId}&status=${newStatus}&notes=${encodeURIComponent(notes)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModalSuccess(data.message || 'Application status updated successfully');

                        // Close modal after short delay
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('statusUpdateModal')).hide();

                            // Refresh data
                            loadApplications(currentPage);
                            loadDashboardData();

                            // Close details modal if open
                            const detailsModal = bootstrap.Modal.getInstance(document.getElementById('applicationDetailsModal'));
                            if (detailsModal) {
                                detailsModal.hide();
                            }
                        }, 1500);

                    } else {
                        showModalError(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showModalError('Network error. Please try again.');
                })
                .finally(() => {
                    // Re-enable save button
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalText;
                });
        }

        function showModalError(message, type = 'danger') {
            const messagesContainer = document.getElementById('statusUpdateMessages');
            messagesContainer.style.display = 'block';
            messagesContainer.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
        }

        function showModalSuccess(message) {
            const messagesContainer = document.getElementById('statusUpdateMessages');
            messagesContainer.style.display = 'block';
            messagesContainer.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
        }

        function clearModalMessages() {
            const messagesContainer = document.getElementById('statusUpdateMessages');
            messagesContainer.style.display = 'none';
            messagesContainer.innerHTML = '';
        }

        function loadStudents(filters = {}) {
            showLoading(true);

            // Build request body with filters
            let requestBody = 'action=get_students';
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    requestBody += `&${key}=${encodeURIComponent(filters[key])}`;
                }
            });

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: requestBody
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateStudentsTableEnhanced(data.students, filters);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error loading students', 'danger');
                })
                .finally(() => {
                    showLoading(false);
                });
        }

        function populateStudentsTable(students) {
            const tbody = document.getElementById('studentsTable');
            if (!tbody) return;

            if (!students || students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No students found</td></tr>';
                return;
            }

            tbody.innerHTML = students.map(student => `
        <tr class="${!student.is_active ? 'table-secondary' : ''}">
            <td><strong>${student.user_id}</strong></td>
            <td>${student.first_name || ''} ${student.last_name || ''}</td>
            <td>${student.email || 'N/A'}</td>
            <td>${student.phone_number || 'N/A'}</td>
            <td>${student.program || 'N/A'}</td>
            <td>
                <span class="status-badge bg-${student.is_active ? 'success' : 'secondary'}">
                    ${student.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${formatDate(student.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    
                    <button class="btn btn-outline-${student.is_active ? 'warning' : 'success'}" 
                            onclick="toggleStudentStatus(${student.user_id}, ${!student.is_active})" 
                            title="${student.is_active ? 'Deactivate' : 'Activate'} Student">
                        <i class="fas fa-${student.is_active ? 'user-slash' : 'user-check'}"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
        }

        // Document preview functions - Same as student portal
        function viewDocument(documentId, filename, documentType, mimeType, fileSize) {
            console.log('=== VIEWING DOCUMENT ===');
            console.log('ID:', documentId, 'File:', filename, 'MIME:', mimeType);
            try {
                // Clear any existing state
                clearPreviewState();

                previewState.currentDocument = {
                    id: documentId,
                    filename: filename,
                    type: documentType,
                    mimeType: mimeType,
                    fileSize: fileSize
                };

                // Show modal first, then setup preview
                const modal = document.getElementById('documentPreviewModal');
                previewState.modalInstance = new bootstrap.Modal(modal);

                // Wait for modal to be fully shown before loading preview
                modal.addEventListener('shown.bs.modal', function onModalShown() {
                    modal.removeEventListener('shown.bs.modal', onModalShown);
                    try {
                        setupDocumentPreview(documentId, filename, mimeType, fileSize);
                    } catch (error) {
                        console.error('Error in setupDocumentPreview:', error);
                        showPreviewState('error');
                    }
                }, {
                    once: true
                });

                previewState.modalInstance.show();
            } catch (error) {
                console.error('Error in viewDocument:', error);
            }
        }

        function clearPreviewState() {
            if (previewState.loadTimeout) {
                clearTimeout(previewState.loadTimeout);
                previewState.loadTimeout = null;
            }
            previewState.isLoading = false;

            const pdfFrame = document.getElementById('pdfFrame');
            if (pdfFrame) {
                pdfFrame.onload = null;
                pdfFrame.onerror = null;
                pdfFrame.onabort = null;
                pdfFrame.src = 'about:blank';
            }

            const img = document.getElementById('previewImage');
            if (img) {
                img.onload = null;
                img.onerror = null;
                img.src = '';
                img.style.transform = 'scale(1)';
                img.onclick = null;
            }
        }

        function setupDocumentPreview(documentId, filename, mimeType, fileSize) {
            console.log('Setting up preview for document:', documentId);

            // Prevent multiple simultaneous loads
            if (previewState.isLoading) {
                console.log('Preview already loading, skipping...');
                return;
            }
            previewState.isLoading = true;

            // Update modal info
            updateModalInfo(filename, mimeType, fileSize);
            setupDownloadButtons(documentId, filename);

            // Show loading state immediately
            showPreviewState('loading');

            // Determine preview type
            const previewType = getPreviewType(mimeType, filename);
            console.log('Preview type:', previewType);

            // Set loading timeout
            previewState.loadTimeout = setTimeout(() => {
                if (previewState.isLoading) {
                    console.warn('Preview loading timeout');
                    previewState.isLoading = false;
                    showPreviewState('error');
                }
            }, 15000); // 15 second timeout

            // Load content based on type
            switch (previewType) {
                case 'image':
                    loadImagePreview(documentId);
                    break;
                case 'pdf':
                    loadPdfPreview(documentId);
                    break;
                case 'unsupported':
                default:
                    showUnsupportedPreview(filename, mimeType, fileSize);
                    break;
            }
        }

        function updateModalInfo(filename, mimeType, fileSize) {
            document.getElementById('previewFileName').textContent = filename;
            document.getElementById('fileSizeBadge').textContent = formatFileSize(fileSize);
            document.getElementById('fileTypeBadge').textContent = getFileTypeLabel(mimeType);
        }

        function getPreviewType(mimeType, filename) {
            const extension = filename.split('.').pop().toLowerCase();
            const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if (imageTypes.includes(mimeType) || imageExtensions.includes(extension)) {
                return 'image';
            }
            if (mimeType === 'application/pdf' || extension === 'pdf') {
                return 'pdf';
            }
            return 'unsupported';
        }

        function loadImagePreview(documentId) {
            console.log('Loading image preview...');
            const img = document.getElementById('previewImage');
            const imageUrl = `document_viewer.php?id=${documentId}&t=${Date.now()}`;
            console.log('Image URL:', imageUrl);

            const preloadImg = new Image();
            preloadImg.onload = function() {
                img.src = preloadImg.src;
                showPreviewState('image');
                if (preloadImg.naturalWidth > 800 || preloadImg.naturalHeight > 600) {
                    img.onclick = function() {
                        toggleImageZoom(img);
                    };
                }
            };
            preloadImg.onerror = function() {
                showPreviewState('error');
            };
            preloadImg.src = imageUrl;
        }

        function loadPdfPreview(documentId) {
            console.log('Loading PDF preview for document:', documentId);
            const pdfUrl = `document_viewer.php?id=${documentId}&t=${Date.now()}`;
            showWorkingPdfPreview(pdfUrl);
        }

        function showWorkingPdfPreview(pdfUrl) {
            console.log('showWorkingPdfPreview called with URL:', pdfUrl);

            const pdfPreview = document.getElementById('pdfPreview');
            console.log('pdfPreview element found:', !!pdfPreview);

            if (!pdfPreview) {
                console.error('pdfPreview element not found!');
                return;
            }
            pdfPreview.innerHTML = `
        <iframe src="${pdfUrl}" 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; margin: 0; padding: 0;"
                onload="console.log('PDF iframe loaded successfully')"
                onerror="console.log('PDF iframe failed to load')">
        </iframe>
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    text-align: center; z-index: 1; display: none;" id="pdfFallback">
            <i class="fas fa-file-pdf fa-4x text-primary mb-3"></i>
            <h4>PDF Ready to View</h4>
            <p class="text-muted mb-4">
                Your browser cannot display PDFs inline.<br>
                Click below to view in a new tab where it will work perfectly.
            </p>
            <div class="d-grid gap-2" style="max-width: 300px; margin: 0 auto;">
                <a href="${pdfUrl}" target="_blank" rel="noopener" 
                   class="btn btn-primary btn-lg">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Open PDF in New Tab
                </a>
                <a href="${pdfUrl}&download=1" 
                   class="btn btn-outline-secondary">
                    <i class="fas fa-download me-2"></i>
                    Download PDF
                </a>
            </div>
        </div>
    `;

            console.log('PDF HTML content set');

            // Show the container
            setTimeout(() => {
                const iframe = pdfPreview.querySelector('iframe');
                const fallback = document.getElementById('pdfFallback');

                // Check if iframe loaded content
                try {
                    if (!iframe.contentDocument || iframe.contentDocument.body.children.length === 0) {
                        fallback.style.display = 'block';
                    }
                } catch (e) {
                    // Cross-origin restrictions - assume PDF is working
                }
            }, 3000);

            // Show the container
            console.log('Calling showPreviewState(pdf)');
            showPreviewState('pdf');
        }

        function showUnsupportedPreview(filename, mimeType, fileSize) {
            const fileInfo = document.getElementById('fileInfo');
            fileInfo.innerHTML = `
                <div class="row">
                    <div class="col-md-6"><strong>Filename:</strong><br><span class="text-muted">${filename}</span></div>
                    <div class="col-md-6"><strong>File Type:</strong><br><span class="text-muted">${getFileTypeLabel(mimeType)}</span></div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6"><strong>File Size:</strong><br><span class="text-muted">${formatFileSize(fileSize)}</span></div>
                    <div class="col-md-6"><strong>MIME Type:</strong><br><span class="text-muted">${mimeType}</span></div>
                </div>
            `;
            showPreviewState('text');
        }

        function showPreviewState(state) {
            console.log('Changing preview state to:', state);
            const elements = {
                loading: document.getElementById('previewLoading'),
                error: document.getElementById('previewError'),
                container: document.getElementById('previewContainer'),
                imagePreview: document.getElementById('imagePreview'),
                pdfPreview: document.getElementById('pdfPreview'),
                textPreview: document.getElementById('textPreview')
            };

            console.log('Found elements:', Object.keys(elements).filter(key => elements[key]));

            // Hide ALL elements first
            // Hide ALL elements first
            Object.values(elements).forEach(el => {
                if (el) {
                    el.style.display = 'none';
                    el.style.visibility = 'hidden';
                    el.style.opacity = '0';
                }
            });

            switch (state) {
                case 'loading':
                    console.log('Showing loading state');
                    if (elements.loading) {
                        elements.loading.style.display = 'flex';
                        elements.loading.style.visibility = 'visible';
                    }
                    break;
                case 'error':
                    if (elements.error) {
                        elements.error.style.display = 'flex';
                        elements.error.style.visibility = 'visible';
                    }
                    break;
                case 'image':
                    if (elements.container && elements.imagePreview) {
                        elements.container.style.display = 'block';
                        elements.container.style.visibility = 'visible';
                        elements.imagePreview.style.display = 'flex';
                        elements.imagePreview.style.visibility = 'visible';
                    }
                    break;
                case 'pdf':
                    if (elements.container && elements.pdfPreview) {
                        // Force container visibility
                        elements.container.style.display = 'block';
                        elements.container.style.visibility = 'visible';
                        elements.container.style.opacity = '1';
                        elements.container.style.height = '100%';
                        elements.container.style.width = '100%';

                        // Force PDF preview visibility
                        elements.pdfPreview.style.display = 'block';
                        elements.pdfPreview.style.visibility = 'visible';
                        elements.pdfPreview.style.opacity = '1';
                        elements.pdfPreview.style.height = '100%';
                        elements.pdfPreview.style.width = '100%';
                        elements.pdfPreview.style.minHeight = '400px';

                        // Debug
                        console.log('PDF container forced visible');
                        debugModalState(); // Call debug function
                    }
                    break;
                case 'text':
                    if (elements.container && elements.textPreview) {
                        elements.container.style.display = 'block';
                        elements.container.style.visibility = 'visible';
                        elements.textPreview.style.display = 'block';
                        elements.textPreview.style.visibility = 'visible';
                    }
                    break;
            }

            if (state !== 'loading') {
                previewState.isLoading = false;
                if (previewState.loadTimeout) {
                    clearTimeout(previewState.loadTimeout);
                    previewState.loadTimeout = null;
                }
            }

            return true;
        }

        function setupDownloadButtons(documentId, filename) {
            const downloadUrl = `document_viewer.php?id=${documentId}&download=1`;

            ['downloadDocBtn', 'downloadFallbackBtn', 'downloadTextBtn'].forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.onclick = function() {
                        const link = document.createElement('a');
                        link.href = downloadUrl;
                        link.download = filename;
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    };
                }
            });
        }

        function toggleImageZoom(img) {
            if (img.style.transform === 'scale(2)') {
                img.style.transform = 'scale(1)';
                img.style.cursor = 'zoom-in';
            } else {
                img.style.transform = 'scale(2)';
                img.style.cursor = 'zoom-out';
            }
        }

        // Event listeners
        function setupEventListeners() {
            let searchTimeout;

            const searchInput = document.getElementById('applicationSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        applyFilters();
                    }, 500);
                });
            }

            const filters = ['statusFilter', 'programFilter', 'dateFromFilter', 'dateToFilter'];
            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', applyFilters);
                }
            });

            // Document preview modal cleanup
            const previewModal = document.getElementById('documentPreviewModal');
            if (previewModal) {
                previewModal.addEventListener('hidden.bs.modal', function() {
                    clearPreviewState();
                    previewState.currentDocument = null;
                    previewState.modalInstance = null;
                });
            }

            setupFullscreenToggle();

            if (!window.adminNotificationManager) {
                window.adminNotificationManager = new AdminNotificationManager();
            }
        }

        // Utility functions
        function showLoading(show) {
            document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
        }

        function showAlert(message, type) {
            // Create alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            // Insert at top of main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function getStatusColor(status) {
            const colors = {
                'submitted': 'primary',
                'under-review': 'warning',
                'interview-scheduled': 'info',
                'approved': 'success',
                'rejected': 'danger',
                'waitlisted': 'secondary',
                'enrolled': 'success',
                'active': 'success'
            };
            return colors[status] || 'secondary';
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        }

        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleString();
        }

        function getDocumentIcon(documentType) {
            const icons = {
                'transcript': 'fa-file-alt',
                'certificate': 'fa-certificate',
                'identity': 'fa-id-card',
                'photo': 'fa-image',
                'personal_statement': 'fa-file-text',
                'recommendation_letter': 'fa-file-signature',
                'portfolio': 'fa-folder',
                'other': 'fa-file'
            };
            return icons[documentType] || 'fa-file';
        }

        function formatDocumentType(type) {
            const types = {
                'transcript': 'Academic Transcript',
                'certificate': 'Certificate/Diploma',
                'identity': 'Identity Document',
                'photo': 'Passport Photo',
                'personal_statement': 'Personal Statement',
                'recommendation_letter': 'Recommendation Letter',
                'portfolio': 'Portfolio',
                'other': 'Other Document'
            };
            return types[type] || type.replace('_', ' ').toUpperCase();
        }

        function formatStatusText(status) {
            return STATUS_DISPLAY_NAMES[status] || status.replace('-', ' ').toUpperCase();
        }

        function getVerificationStatusColor(status) {
            const colors = {
                'verified': 'success',
                'pending': 'warning',
                'rejected': 'danger'
            };
            return colors[status] || 'secondary';
        }

        function formatFileSize(bytes) {
            if (!bytes) return 'Unknown size';
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
        }

        function getFileTypeLabel(mimeType) {
            const typeMap = {
                'application/pdf': 'PDF Document',
                'image/jpeg': 'JPEG Image',
                'image/jpg': 'JPG Image',
                'image/png': 'PNG Image',
                'image/gif': 'GIF Image',
                'application/msword': 'Word Document',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'Word Document (DOCX)',
                'text/plain': 'Text File'
            };
            return typeMap[mimeType] || mimeType || 'Unknown';
        }

        function toggleStudentStatus(studentId, newStatus) {
            const action = newStatus ? 'activate' : 'deactivate';
            const message = `Are you sure you want to ${action} this student?`;

            if (!confirm(message)) {
                return;
            }

            // Show loading state
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=update_student_status&student_id=${studentId}&is_active=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Reload the students table
                        loadStudents();
                    } else {
                        showAlert(data.message || 'Failed to update student status', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error updating student status', 'danger');
                })
                .finally(() => {
                    // Restore button state
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
        }


        function viewStudent(studentId) {
            showAlert('Student details view will be implemented here', 'info');
        }

        function updatePagination(paginationData, containerId) {
            const container = document.getElementById(containerId);
            if (!container || !paginationData.last_page || paginationData.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHtml = '<ul class="pagination">';

            if (paginationData.current_page > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadApplications(${paginationData.current_page - 1})">Previous</a></li>`;
            }

            const startPage = Math.max(1, paginationData.current_page - 2);
            const endPage = Math.min(paginationData.last_page, paginationData.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const active = i === paginationData.current_page ? 'active' : '';
                paginationHtml += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadApplications(${i})">${i}</a></li>`;
            }

            if (paginationData.current_page < paginationData.last_page) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadApplications(${paginationData.current_page + 1})">Next</a></li>`;
            }

            paginationHtml += '</ul>';
            container.innerHTML = paginationHtml;
        }

        function debugModalState() {
            const modal = document.getElementById('documentPreviewModal');
            const container = document.getElementById('previewContainer');
            const pdfPreview = document.getElementById('pdfPreview');

            console.log('=== MODAL DEBUG ===');
            console.log('Modal computed z-index:', window.getComputedStyle(modal)?.zIndex);
            console.log('Container dimensions:', {
                width: container?.offsetWidth,
                height: container?.offsetHeight,
                display: container?.style.display
            });
            console.log('PDF Preview dimensions:', {
                width: pdfPreview?.offsetWidth,
                height: pdfPreview?.offsetHeight,
                display: pdfPreview?.style.display
            });
        }



        class SmoothApplicationFilter {
            constructor() {
                this.searchTimeout = null;
                this.currentFilters = {};
                this.currentPage = 1;
                this.isLoading = false;
                this.lastSearchTerm = '';

                this.setupEventListeners();
                this.createLoadingStates();
            }

            setupEventListeners() {
                // INSTANT SEARCH: Search as you type with debouncing
                const searchInput = document.getElementById('applicationSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.trim();

                        // Show instant feedback
                        this.showSearchFeedback(searchTerm);

                        // Debounced search
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(() => {
                            this.handleSearch(searchTerm);
                        }, 300); // 300ms delay - feels instant but prevents too many requests
                    });

                    // Clear search
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            searchInput.value = '';
                            this.handleSearch('');
                        }
                    });
                }

                // INSTANT FILTER CHANGES: Apply filters immediately
                const filters = ['statusFilter', 'programFilter', 'dateFromFilter', 'dateToFilter'];
                filters.forEach(filterId => {
                    const element = document.getElementById(filterId);
                    if (element) {
                        element.addEventListener('change', (e) => {
                            this.showFilterFeedback(filterId, e.target.value);
                            this.applyFiltersSmooth();
                        });
                    }
                });
            }

            createLoadingStates() {
                // Add subtle loading overlay just for the table
                const tableContainer = document.querySelector('.data-table');
                if (tableContainer) {
                    const overlay = document.createElement('div');
                    overlay.id = 'tableLoadingOverlay';
                    overlay.className = 'table-loading-overlay';
                    overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 10;
                border-radius: 12px;
            `;
                    overlay.innerHTML = `
                <div style="text-align: center;">
                    <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Searching...</div>
                </div>
            `;
                    tableContainer.style.position = 'relative';
                    tableContainer.appendChild(overlay);
                }
            }

            showTableLoading(show = true) {
                const overlay = document.getElementById('tableLoadingOverlay');
                if (overlay) {
                    overlay.style.display = show ? 'flex' : 'none';
                }
            }

            showSearchFeedback(searchTerm) {
                const searchInput = document.getElementById('applicationSearch');
                if (!searchInput) return;

                // Add visual feedback to search input
                if (searchTerm.length > 0) {
                    searchInput.classList.add('searching');

                } else {
                    searchInput.classList.remove('searching');

                }
            }

            showFilterFeedback(filterId, value) {
                const filterElement = document.getElementById(filterId);
                if (!filterElement) return;

                // Add visual feedback to show filter is active
                if (value) {
                    filterElement.classList.add('filter-active');
                    filterElement.style.borderColor = '#2563eb';
                    filterElement.style.boxShadow = '0 0 0 2px rgba(37, 99, 235, 0.1)';
                } else {
                    filterElement.classList.remove('filter-active');
                    filterElement.style.borderColor = '';
                    filterElement.style.boxShadow = '';
                }
            }

            handleSearch(searchTerm) {
                this.currentFilters.search = searchTerm;
                this.currentPage = 1; // Reset to first page for new search
                this.loadApplicationsSmooth();

                // Update URL without refresh (optional)
                this.updateUrlState();
            }

            applyFiltersSmooth() {
                // Collect all filter values
                this.currentFilters = {
                    search: document.getElementById('applicationSearch')?.value || '',
                    status: document.getElementById('statusFilter')?.value || '',
                    program: document.getElementById('programFilter')?.value || '',
                    date_from: document.getElementById('dateFromFilter')?.value || '',
                    date_to: document.getElementById('dateToFilter')?.value || ''
                };

                this.currentPage = 1; // Reset page for new filters
                this.loadApplicationsSmooth();
                this.updateUrlState();
            }

            async loadApplicationsSmooth(page = 1) {
                if (this.isLoading) return; // Prevent multiple simultaneous requests

                this.isLoading = true;
                this.currentPage = page;

                // Show subtle loading state
                this.showTableLoading(true);
                this.addTableLoadingClass();

                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'get_applications');
                    formData.append('page', page);

                    // Add filters to POST data
                    Object.keys(this.currentFilters).forEach(key => {
                        if (this.currentFilters[key]) {
                            formData.append(key, this.currentFilters[key]);
                        }
                    });

                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData.toString()
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Smooth table update instead of instant replace
                        await this.updateTableSmoothly(data.applications.data || data.applications);

                        if (data.applications.current_page) {
                            this.updatePaginationSmooth(data.applications);
                        }

                        // Show results feedback
                        this.showResultsFeedback(data.applications);

                    } else {
                        this.showError(data.message);
                    }

                } catch (error) {
                    console.error('Search error:', error);
                    this.showError('Failed to load applications');
                } finally {
                    this.isLoading = false;
                    this.showTableLoading(false);
                    this.removeTableLoadingClass();
                }
            }

            addTableLoadingClass() {
                const table = document.getElementById('applicationsTable');
                if (table) {
                    table.style.opacity = '0.6';
                    table.style.pointerEvents = 'none';
                }
            }

            removeTableLoadingClass() {
                const table = document.getElementById('applicationsTable');
                if (table) {
                    table.style.opacity = '1';
                    table.style.pointerEvents = 'auto';
                }
            }

            async updateTableSmoothly(applications) {
                const tbody = document.getElementById('applicationsTable');
                if (!tbody) return;

                // Fade out old content
                tbody.style.opacity = '0.3';

                // Small delay for smooth transition
                await new Promise(resolve => setTimeout(resolve, 150));

                if (!applications || applications.length === 0) {
                    tbody.innerHTML = this.getNoResultsHTML();
                } else {
                    tbody.innerHTML = applications.map(app => this.createApplicationRowHTML(app)).join('');
                }

                // Fade in new content
                tbody.style.opacity = '1';

                // Add subtle animation to new rows
                const rows = tbody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    row.style.transform = 'translateY(10px)';
                    row.style.opacity = '0';

                    setTimeout(() => {
                        row.style.transform = 'translateY(0)';
                        row.style.opacity = '1';
                        row.style.transition = 'all 0.3s ease';
                    }, index * 50); // Stagger animation
                });
            }

            createApplicationRowHTML(app) {
                return `
            <tr>
                <td><strong>${app.application_number || 'N/A'}</strong></td>
                <td>${app.first_name || ''} ${app.last_name || ''}</td>
                <td>${app.email || 'N/A'}</td>
                <td>${app.program || 'N/A'}</td>
                <td><span class="status-badge bg-${getStatusColor(app.status)}">${app.status}</span></td>
                <td>${formatDate(app.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewApplication(${app.personal_id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="showStatusUpdate(${app.personal_id})" title="Update Status">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
            }

            getNoResultsHTML() {
                const hasFilters = Object.values(this.currentFilters).some(value => value && value.trim());

                if (hasFilters) {
                    return `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <div>No applications found matching your criteria</div>
                            <button class="btn btn-link btn-sm mt-2" onclick="applicationFilter.clearAllFilters()">
                                <i class="fas fa-times me-1"></i>Clear filters
                            </button>
                        </div>
                    </td>
                </tr>
            `;
                } else {
                    return `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <div>No applications found</div>
                        </div>
                    </td>
                </tr>
            `;
                }
            }

            showResultsFeedback(applications) {
                const total = applications.total || (applications.data ? applications.data.length : applications.length);
                const hasFilters = Object.values(this.currentFilters).some(value => value && value.trim());

                if (hasFilters && total > 0) {
                    this.showToast(`Found ${total} applications`, 'success', 2000);
                }
            }

            showError(message) {
                this.showToast(message, 'error', 4000);
            }

            showToast(message, type = 'info', duration = 3000) {
                // Remove existing toasts
                document.querySelectorAll('.filter-toast').forEach(toast => toast.remove());

                const colors = {
                    success: '#16a34a',
                    error: '#dc2626',
                    info: '#2563eb'
                };

                const toast = document.createElement('div');
                toast.className = 'filter-toast';
                toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 12px 16px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            font-size: 14px;
            color: #374151;
        `;

                toast.textContent = message;
                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => toast.style.transform = 'translateX(0)', 100);

                // Auto remove
                setTimeout(() => {
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            updatePaginationSmooth(paginationData) {
                const container = document.getElementById('applicationsPagination');
                if (!container || !paginationData.last_page || paginationData.last_page <= 1) {
                    container.innerHTML = '';
                    return;
                }

                let paginationHtml = '<ul class="pagination pagination-sm">';

                // Previous button
                if (paginationData.current_page > 1) {
                    paginationHtml += `<li class="page-item">
                <a class="page-link" href="#" onclick="applicationFilter.loadApplicationsSmooth(${paginationData.current_page - 1}); return false;">
                    Previous
                </a>
            </li>`;
                }

                // Page numbers
                const startPage = Math.max(1, paginationData.current_page - 2);
                const endPage = Math.min(paginationData.last_page, paginationData.current_page + 2);

                for (let i = startPage; i <= endPage; i++) {
                    const active = i === paginationData.current_page ? 'active' : '';
                    paginationHtml += `<li class="page-item ${active}">
                <a class="page-link" href="#" onclick="applicationFilter.loadApplicationsSmooth(${i}); return false;">
                    ${i}
                </a>
            </li>`;
                }

                // Next button
                if (paginationData.current_page < paginationData.last_page) {
                    paginationHtml += `<li class="page-item">
                <a class="page-link" href="#" onclick="applicationFilter.loadApplicationsSmooth(${paginationData.current_page + 1}); return false;">
                    Next
                </a>
            </li>`;
                }

                paginationHtml += '</ul>';

                // Add result count
                const start = ((paginationData.current_page - 1) * paginationData.per_page) + 1;
                const end = Math.min(paginationData.current_page * paginationData.per_page, paginationData.total);

                const resultInfo = `
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing ${start}-${end} of ${paginationData.total} applications
                </small>
                ${paginationHtml}
            </div>
        `;

                container.innerHTML = resultInfo;
            }

            clearAllFilters() {
                // Clear all filter inputs
                document.getElementById('applicationSearch').value = '';
                document.getElementById('statusFilter').value = '';
                document.getElementById('programFilter').value = '';
                document.getElementById('dateFromFilter').value = '';
                document.getElementById('dateToFilter').value = '';

                // Remove visual feedback
                document.querySelectorAll('.filter-active').forEach(el => {
                    el.classList.remove('filter-active');
                    el.style.borderColor = '';
                    el.style.boxShadow = '';
                });

                // Clear search feedback
                const searchInput = document.getElementById('applicationSearch');
                searchInput.classList.remove('searching');
                const indicator = searchInput.parentElement.querySelector('.search-indicator');
                if (indicator) indicator.remove();

                // Reset filters and reload
                this.currentFilters = {};
                this.currentPage = 1;
                this.loadApplicationsSmooth();
                this.updateUrlState();

                this.showToast('Filters cleared', 'info', 2000);
            }

            updateUrlState() {
                // Update URL without refresh (for bookmarking/sharing)
                const params = new URLSearchParams();

                Object.keys(this.currentFilters).forEach(key => {
                    if (this.currentFilters[key]) {
                        params.set(key, this.currentFilters[key]);
                    }
                });

                if (this.currentPage > 1) {
                    params.set('page', this.currentPage);
                }

                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }

            // Load filters from URL on page load
            loadFiltersFromUrl() {
                const params = new URLSearchParams(window.location.search);

                params.forEach((value, key) => {
                    const element = document.getElementById(key === 'search' ? 'applicationSearch' : key + 'Filter');
                    if (element) {
                        element.value = value;
                        this.showFilterFeedback(element.id, value);
                    }

                    if (key === 'page') {
                        this.currentPage = parseInt(value) || 1;
                    } else {
                        this.currentFilters[key] = value;
                    }
                });

                // Load applications with URL filters
                if (Object.keys(this.currentFilters).length > 0) {
                    this.loadApplicationsSmooth(this.currentPage);
                }
            }
        }

        function setupFullscreenToggle() {
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const modal = document.getElementById('documentPreviewModal');
            const modalDialog = modal.querySelector('.modal-dialog');

            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', function() {
                    toggleFullscreen();
                });
            }

            // Also handle ESC key to exit fullscreen
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('fullscreen')) {
                    exitFullscreen();
                }
            });
        }

        function toggleFullscreen() {
            const modal = document.getElementById('documentPreviewModal');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const icon = fullscreenBtn.querySelector('i');

            if (modal.classList.contains('fullscreen')) {
                exitFullscreen();
            } else {
                enterFullscreen();
            }
        }

        function enterFullscreen() {
            const modal = document.getElementById('documentPreviewModal');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const icon = fullscreenBtn.querySelector('i');

            // Add fullscreen class
            modal.classList.add('fullscreen');

            // Update button icon and title
            icon.className = 'fas fa-compress';
            fullscreenBtn.title = 'Exit Fullscreen';

            // Force modal to recalculate dimensions
            setTimeout(() => {
                // Trigger any necessary resize events
                window.dispatchEvent(new Event('resize'));

                // If it's a PDF, refresh the iframe
                const pdfFrame = document.getElementById('pdfFrame');
                if (pdfFrame && pdfFrame.style.display !== 'none') {
                    const currentSrc = pdfFrame.src;
                    if (currentSrc) {
                        // Force refresh by adding timestamp
                        pdfFrame.src = currentSrc.split('&refresh=')[0] + '&refresh=' + Date.now();
                    }
                }
            }, 300);
        }

        function exitFullscreen() {
            const modal = document.getElementById('documentPreviewModal');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const icon = fullscreenBtn.querySelector('i');

            // Remove fullscreen class
            modal.classList.remove('fullscreen');

            // Update button icon and title
            icon.className = 'fas fa-expand';
            fullscreenBtn.title = 'Toggle Fullscreen';

            // Force modal to recalculate dimensions
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));

                // If it's a PDF, refresh the iframe
                const pdfFrame = document.getElementById('pdfFrame');
                if (pdfFrame && pdfFrame.style.display !== 'none') {
                    const currentSrc = pdfFrame.src;
                    if (currentSrc) {
                        pdfFrame.src = currentSrc.split('&refresh=')[0] + '&refresh=' + Date.now();
                    }
                }
            }, 300);
        }


        class AdminNotificationManager {
            constructor() {
                this.unreadCount = 0;
                this.notifications = [];
                this.allNotifications = [];
                this.pollInterval = 30000; // 30 seconds
                this.init();
            }

            init() {
                this.loadNotifications();
                this.startPolling();
                this.setupEventListeners();
                // Check for new applications on init
                this.checkNewApplicationsOnInit();
            }

            // ENHANCED: Check for new applications on initialization
            checkNewApplicationsOnInit() {
                setTimeout(() => {
                    checkForNewApplications();
                }, 2000); // Wait 2 seconds after init
            }

            async loadNotifications(limit = 10) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=get_notifications&limit=${limit}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        this.updateUI();
                    }
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            }

            updateUI() {
                // Update notification badge
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = this.unreadCount;
                    badge.style.display = this.unreadCount > 0 ? 'inline' : 'none';
                }

                // Update dropdown content
                this.updateNotificationDropdown();
            }

            updateNotificationDropdown() {
                const dropdown = document.querySelector('.notification-dropdown');
                if (!dropdown) return;

                if (this.notifications.length === 0) {
                    dropdown.innerHTML = '<li><span class="dropdown-item-text text-muted text-center py-3">No notifications</span></li>';
                    return;
                }

                dropdown.innerHTML = this.notifications.slice(0, 5).map(notification => {
                    // ENHANCED: Special styling for application notifications
                    const isApplicationNotification = notification.title.includes('Application');
                    const notificationClass = isApplicationNotification ? 'notification-type-application' : '';
                    const isNew = !notification.is_read;
                    const newClass = isNew && isApplicationNotification ? 'notification-new-application' : '';

                    return `
                <li>
                    <a class="dropdown-item ${notification.is_read ? '' : 'fw-bold bg-light'} ${notificationClass} ${newClass}" 
                       href="#" 
                       onclick="adminNotificationManager.markAsRead(${notification.notification_id}); return false;"
                       data-notification-id="${notification.notification_id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 me-2">
                                <h6 class="mb-1 fs-6">
                                    ${isApplicationNotification ? '<i class="fas fa-file-alt me-1 text-success"></i>' : ''}
                                    ${notification.title}
                                </h6>
                                <p class="small mb-1 text-muted">${this.truncateMessage(notification.message, 60)}</p>
                                <small class="text-info">
                                    <i class="fas fa-clock me-1"></i>${this.formatDate(notification.created_at)}
                                </small>
                            </div>
                            <span class="badge bg-${this.getTypeColor(notification.type)} ms-1">${notification.type}</span>
                        </div>
                    </a>
                </li>
            `;
                }).join('');
            }



            async markAsRead(notificationId) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=mark_notification_read&notification_id=${notificationId}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.unreadCount = data.unread_count;

                        // Update the notification in the array
                        const notification = this.notifications.find(n => n.notification_id == notificationId);
                        if (notification) {
                            notification.is_read = 1;
                        }

                        this.updateUI();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            async markAllAsRead() {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=mark_all_notifications_read'
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.unreadCount = 0;
                        this.notifications.forEach(n => n.is_read = 1);
                        this.updateUI();
                        this.showToast('All notifications marked as read', 'success');
                    }
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                }
            }

            async deleteNotification(notificationId) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_notification&notification_id=${notificationId}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Remove from arrays
                        this.notifications = this.notifications.filter(n => n.notification_id != notificationId);
                        this.allNotifications = this.allNotifications.filter(n => n.notification_id != notificationId);
                        this.unreadCount = data.unread_count;

                        this.updateUI();
                        this.updateNotificationCenter();
                        this.showToast('Notification deleted', 'success');
                    }
                } catch (error) {
                    console.error('Error deleting notification:', error);
                }
            }

            setupEventListeners() {
                // Mark all as read button
                const markAllBtn = document.getElementById('markAllNotificationsRead');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', () => this.markAllAsRead());
                }

                // Refresh notifications button
                const refreshBtn = document.getElementById('refreshNotifications');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => {
                        this.loadNotifications();
                        this.showToast('Notifications refreshed', 'info');
                    });
                }

                // ENHANCED: Check new applications button
                const checkBtn = document.getElementById('checkNewApplications');
                if (checkBtn) {
                    checkBtn.addEventListener('click', () => {
                        checkForNewApplications();
                    });
                }

                // Bulk notification form
                const sendBulkBtn = document.getElementById('sendBulkNotificationBtn');
                if (sendBulkBtn) {
                    sendBulkBtn.addEventListener('click', () => this.sendBulkNotification());
                }
            }

            async loadNotificationCenter() {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=get_notifications&limit=50'
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.allNotifications = data.notifications || [];
                        this.updateNotificationCenter();
                    }
                } catch (error) {
                    console.error('Error loading notification center:', error);
                }
            }

            updateNotificationCenter() {
                const container = document.getElementById('notificationCenterContent');
                if (!container) return;

                let filteredNotifications = this.allNotifications;

                // Apply filters
                const typeFilter = document.getElementById('notificationTypeFilter')?.value;
                const readFilter = document.getElementById('notificationReadFilter')?.value;

                if (typeFilter) {
                    filteredNotifications = filteredNotifications.filter(n => n.type === typeFilter);
                }

                if (readFilter === 'unread') {
                    filteredNotifications = filteredNotifications.filter(n => !n.is_read);
                } else if (readFilter === 'read') {
                    filteredNotifications = filteredNotifications.filter(n => n.is_read);
                }

                if (filteredNotifications.length === 0) {
                    container.innerHTML = '<div class="text-center py-4 text-muted">No notifications found</div>';
                    return;
                }

                container.innerHTML = filteredNotifications.map(notification => `
            <div class="border-bottom">
                <div class="p-3 ${notification.is_read ? '' : 'bg-light'}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <h6 class="mb-0 ${notification.is_read ? '' : 'fw-bold'}">${notification.title}</h6>
                                <span class="badge bg-${this.getTypeColor(notification.type)} ms-2">${notification.type}</span>
                                ${!notification.is_read ? '<span class="badge bg-primary ms-1">New</span>' : ''}
                            </div>
                            <p class="mb-2">${notification.message}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${this.formatDate(notification.created_at)}
                            </small>
                        </div>
                        <div class="ms-3">
                            <div class="btn-group btn-group-sm">
                                ${!notification.is_read ? `
                                    <button class="btn btn-outline-primary" 
                                            onclick="adminNotificationManager.markAsRead(${notification.notification_id})"
                                            title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-danger" 
                                        onclick="adminNotificationManager.deleteNotification(${notification.notification_id})"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
            }

            async sendBulkNotification() {
                const recipientType = document.getElementById('recipientType').value;
                const title = document.getElementById('notificationTitle').value;
                const message = document.getElementById('notificationMessage').value;
                const type = document.getElementById('notificationType').value;

                if (!recipientType || !title || !message) {
                    this.showBulkNotificationError('Please fill in all required fields.');
                    return;
                }

                const sendBtn = document.getElementById('sendBulkNotificationBtn');
                const originalText = sendBtn.innerHTML;
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

                try {
                    // First get the user IDs based on recipient type
                    const userIds = await this.getUserIdsByType(recipientType);

                    if (userIds.length === 0) {
                        this.showBulkNotificationError('No recipients found for the selected group.');
                        return;
                    }

                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=send_bulk_notification&user_ids=${JSON.stringify(userIds)}&title=${encodeURIComponent(title)}&message=${encodeURIComponent(message)}&type=${type}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showBulkNotificationSuccess(`Notification sent to ${userIds.length} recipients successfully!`);

                        // Reset form and close modal after delay
                        setTimeout(() => {
                            document.getElementById('bulkNotificationForm').reset();
                            bootstrap.Modal.getInstance(document.getElementById('bulkNotificationModal')).hide();
                        }, 2000);
                    } else {
                        this.showBulkNotificationError(data.message || 'Failed to send notification');
                    }
                } catch (error) {
                    console.error('Error sending bulk notification:', error);
                    this.showBulkNotificationError('Network error. Please try again.');
                } finally {
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = originalText;
                }
            }

            async getUserIdsByType(recipientType) {
                // This would typically be an API call, but for now we'll simulate it
                // You should implement this based on your user management system
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=get_users_by_type&type=${recipientType}`
                    });

                    const data = await response.json();
                    return data.success ? data.user_ids : [];
                } catch (error) {
                    console.error('Error getting user IDs:', error);
                    return [];
                }
            }

            showBulkNotificationError(message) {
                const container = document.getElementById('bulkNotificationMessages');
                container.style.display = 'block';
                container.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
            }

            showBulkNotificationSuccess(message) {
                const container = document.getElementById('bulkNotificationMessages');
                container.style.display = 'block';
                container.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
            }

            startPolling() {
                setInterval(() => {
                    this.loadNotifications();
                }, this.pollInterval);
            }

            truncateMessage(message, length = 50) {
                return message.length > length ? message.substring(0, length) + '...' : message;
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffInHours = (now - date) / (1000 * 60 * 60);

                if (diffInHours < 1) {
                    return 'Just now';
                } else if (diffInHours < 24) {
                    return `${Math.floor(diffInHours)} hours ago`;
                } else if (diffInHours < 48) {
                    return 'Yesterday';
                } else {
                    return date.toLocaleDateString();
                }
            }

            getTypeColor(type) {
                const colors = {
                    'info': 'primary',
                    'success': 'success',
                    'warning': 'warning',
                    'error': 'danger'
                };
                return colors[type] || 'secondary';
            }

            showToast(message, type = 'info', duration = 3000) {
                // Remove existing toasts
                document.querySelectorAll('.notification-toast').forEach(toast => toast.remove());

                const colors = {
                    success: 'bg-success',
                    error: 'bg-danger',
                    info: 'bg-primary',
                    warning: 'bg-warning'
                };

                const toast = document.createElement('div');
                toast.className = `toast notification-toast align-items-center text-white ${colors[type]} border-0 position-fixed`;
                toast.style.cssText = `
            top: 80px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
        `;

                toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();

                // Auto remove
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, duration);
            }

        }

        function showNotificationCenter() {
            const modal = new bootstrap.Modal(document.getElementById('notificationCenterModal'));
            modal.show();

            // Load notifications when modal opens
            if (window.adminNotificationManager) {
                adminNotificationManager.loadNotificationCenter();
            }

            // Setup filter listeners
            const typeFilter = document.getElementById('notificationTypeFilter');
            const readFilter = document.getElementById('notificationReadFilter');

            [typeFilter, readFilter].forEach(filter => {
                if (filter) {
                    filter.addEventListener('change', () => {
                        if (window.adminNotificationManager) {
                            adminNotificationManager.updateNotificationCenter();
                        }
                    });
                }
            });
        }

        function showBulkNotificationModal() {
            const modal = new bootstrap.Modal(document.getElementById('bulkNotificationModal'));
            modal.show();
        }

        function checkForNewApplications() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=check_new_applications'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Checked for new applications successfully', 'success');
                        // Refresh notifications
                        if (window.adminNotificationManager) {
                            adminNotificationManager.loadNotifications();
                        }
                    } else {
                        showToast('Failed to check for new applications', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error checking for new applications:', error);
                    showToast('Error checking for new applications', 'error');
                });
        }

        setInterval(function() {
            checkForNewApplications();
        }, 30000);

        function initializeStudentManagement() {
    // Create the filter section for students
    if (!document.getElementById('student-filter-styles')) {
        const style = document.createElement('style');
        style.id = 'student-filter-styles';
        style.textContent = studentFilterStyles;
        document.head.appendChild(style);
    }
    
    const studentsSection = document.getElementById('students-section');
    if (studentsSection) {
        // Check if filter section already exists
        const existingFilter = document.getElementById('studentFilterSection');
        if (existingFilter) {
            // Filter section already exists, just ensure filter system is initialized
            if (!window.studentFilter) {
                window.studentFilter = new SmoothStudentFilter();
            }
            return; // Exit early to prevent duplicate creation
        }
        
        // Add filter controls before the table
        const filterSection = document.createElement('div');
        filterSection.className = 'card mb-4';
        filterSection.id = 'studentFilterSection'; // Add unique ID
        filterSection.innerHTML = `
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Students</label>
                        <input type="text" class="form-control" id="studentSearch" 
                               placeholder="Search by name, email, or ID...">
                        <div class="form-text">Search updates in real-time as you type</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Filter</label>
                        <select class="form-select" id="studentStatusFilter">
                            <option value="">All Students</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Program Filter</label>
                        <select class="form-select" id="studentProgramFilter">
                            <option value="">All Programs</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Law">Law</option>
                            <option value="Education">Education</option>
                            <option value="Arts and Humanities">Arts and Humanities</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-outline-secondary" onclick="studentFilter.clearAllFilters()">
                                <i class="fas fa-times me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm" onclick="bulkActivateStudents()">
                                <i class="fas fa-user-check me-1"></i>Bulk Activate
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="bulkDeactivateStudents()">
                                <i class="fas fa-user-slash me-1"></i>Bulk Deactivate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert before the existing table
        const dataTable = studentsSection.querySelector('.data-table');
        studentsSection.insertBefore(filterSection, dataTable);

        // Initialize the smooth filter system only if it doesn't exist
        if (!window.studentFilter) {
            window.studentFilter = new SmoothStudentFilter();
        }

        // Load initial data
        setTimeout(() => {
            if (window.studentFilter) {
                studentFilter.loadStudentsSmooth();
            }
        }, 100);
    }
}

        function setupStudentFilterListeners() {
            // Search as you type
            const searchInput = document.getElementById('studentSearch');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        applyStudentFilters();
                    }, 500);
                });
            }

            // Filter change listeners
            ['studentStatusFilter', 'studentProgramFilter'].forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', applyStudentFilters);
                }
            });
        }

        // Enhanced student loading with filters
        function loadStudents(filters = {}) {
            showLoading(true);

            // Build request body with filters
            let requestBody = 'action=get_students';
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    requestBody += `&${key}=${encodeURIComponent(filters[key])}`;
                }
            });

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: requestBody
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateStudentsTableEnhanced(data.students, filters);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error loading students', 'danger');
                })
                .finally(() => {
                    showLoading(false);
                });
        }

        function applyStudentFilters() {
            if (window.studentFilter) {
                studentFilter.applyFiltersSmooth();
            }
        }

        function populateStudentsTableEnhanced(students, appliedFilters = {}) {
            const tbody = document.getElementById('studentsTable');
            if (!tbody) return;

            if (!students || students.length === 0) {
                const hasFilters = Object.values(appliedFilters).some(value => value && value.trim());
                const message = hasFilters ?
                    'No students found matching your criteria' :
                    'No students found';
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4">${message}</td></tr>`;
                return;
            }

            // Enhanced styling for inactive users
            tbody.innerHTML = students.map(student => `
        <tr class="${!student.is_active ? 'table-danger' : ''}" 
            style="${!student.is_active ? 'background-color: #f8d7da !important; border-color: #f5c6cb !important;' : ''}">
            <td>
                <input type="checkbox" class="form-check-input student-checkbox" 
                       value="${student.user_id}" data-active="${student.is_active}">
            </td>
            <td>
                <strong>${student.user_id}</strong>
                ${!student.is_active ? '<small class="text-danger d-block"><i class="fas fa-ban me-1"></i>DEACTIVATED</small>' : ''}
            </td>
            <td>
                ${student.first_name || ''} ${student.last_name || ''}
                ${!student.is_active ? '<small class="text-danger d-block"><i class="fas fa-exclamation-triangle me-1"></i>Account Inactive</small>' : ''}
            </td>
            <td>${student.email || 'N/A'}</td>
            <td>${student.phone_number || 'N/A'}</td>
            <td>${student.program || 'N/A'}</td>
            <td>
                <span class="status-badge bg-${student.is_active ? 'success' : 'danger'} ${!student.is_active ? 'text-white' : ''}">
                    <i class="fas fa-${student.is_active ? 'user-check' : 'user-times'} me-1"></i>
                    ${student.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${formatDate(student.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    
                    <button class="btn ${student.is_active ? 'btn-outline-warning' : 'btn-outline-success'}" 
                            onclick="toggleStudentStatusEnhanced(${student.user_id}, ${!student.is_active}, '${student.first_name} ${student.last_name}')" 
                            title="${student.is_active ? 'Deactivate' : 'Activate'} Student">
                        <i class="fas fa-${student.is_active ? 'user-slash' : 'user-check'}"></i>
                        ${student.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

            // Update table header to include checkbox
            updateStudentTableHeader();
            updateStudentStats(students);
        }

        function updateStudentTableHeader() {
            const thead = document.querySelector('#studentsTable').closest('table').querySelector('thead tr');
            if (thead && !thead.querySelector('.select-all-column')) {
                thead.innerHTML = `
            <th class="select-all-column">
                <input type="checkbox" class="form-check-input" id="selectAllStudents" 
                       onchange="toggleAllStudentSelection(this)">
            </th>
            <th>Student ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Program</th>
            <th>Status</th>
            <th>Registered Date</th>
            <th>Actions</th>
        `;
            }
        }

        function updateStudentStats(students) {
            const activeCount = students.filter(s => s.is_active).length;
            const inactiveCount = students.filter(s => !s.is_active).length;

            // Create or update stats display
            let statsDiv = document.getElementById('studentStats');
            if (!statsDiv) {
                statsDiv = document.createElement('div');
                statsDiv.id = 'studentStats';
                statsDiv.className = 'mb-3';

                const studentsSection = document.getElementById('students-section');
                const dataTable = studentsSection.querySelector('.data-table');
                studentsSection.insertBefore(statsDiv, dataTable);
            }

            statsDiv.innerHTML = `
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex gap-4 align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">${students.length}</span>
                        <span>Total Students</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">${activeCount}</span>
                        <span>Active</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-2">${inactiveCount}</span>
                        <span>Inactive</span>
                    </div>
                    ${inactiveCount > 0 ? `
                    <div class="ms-auto">
                        <button class="btn btn-sm btn-success" onclick="showInactiveUsersHelp()">
                            <i class="fas fa-question-circle me-1"></i>Reactivate Users
                        </button>
                    </div>` : ''}
                </div>
            </div>
        </div>
    `;
        }

        function showInactiveUsersHelp() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check me-2"></i>Reactivating Inactive Users
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>How to Reactivate Students:</h6>
                        <ol class="mb-0">
                            <li>Use the <strong>Status Filter</strong> to show "Inactive Only" or "All Students"</li>
                            <li>Inactive students are highlighted in <span class="badge bg-danger">red</span></li>
                            <li>Click the <button class="btn btn-success btn-sm"><i class="fas fa-user-check"></i> Activate</button> button</li>
                            <li>Or use bulk operations to activate multiple students at once</li>
                        </ol>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> Reactivated students will be able to log in and access their accounts again.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    `;

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
        }

        function toggleStudentStatusEnhanced(studentId, newStatus, studentName) {
            const action = newStatus ? 'activate' : 'deactivate';
            const message = `Are you sure you want to ${action} ${studentName}?`;

            if (!confirm(message)) {
                return;
            }

            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=update_student_status&student_id=${studentId}&is_active=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`Student ${action}d successfully!`, 'success');
                        // Reload with current filters
                        applyStudentFilters();
                    } else {
                        showAlert(data.message || `Failed to ${action} student`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert(`Error ${action}ing student`, 'danger');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
        }

        // Bulk operations
        function toggleAllStudentSelection(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        function getSelectedStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            return Array.from(checkboxes).map(cb => {
                // Handle both "true"/"false" strings and "1"/"0" numeric strings
                const activeValue = cb.dataset.active;
                const isActive = activeValue === 'true' || activeValue === '1' || activeValue === true;

                return {
                    id: cb.value,
                    isActive: isActive
                };
            });
        }

        function bulkActivateStudents() {
            const selected = getSelectedStudents();
            console.log('Selected students:', selected); // Debug log

            const inactive = selected.filter(s => !s.isActive);

            if (inactive.length === 0) {
                showAlert('Please select inactive students to activate', 'warning');
                return;
            }

            if (!confirm(`Activate ${inactive.length} selected students?`)) {
                return;
            }

            bulkUpdateStudentStatus(inactive.map(s => s.id), true, 'activate');
        }

        function bulkDeactivateStudents() {
            const selected = getSelectedStudents();
            console.log('Selected students:', selected); // Debug log

            const active = selected.filter(s => s.isActive);

            if (active.length === 0) {
                showAlert('Please select active students to deactivate', 'warning');
                return;
            }

            if (!confirm(`Deactivate ${active.length} selected students?`)) {
                return;
            }

            bulkUpdateStudentStatus(active.map(s => s.id), false, 'deactivate');
        }

        function bulkUpdateStudentStatus(studentIds, isActive, action) {
            if (studentIds.length === 0) {
                showAlert(`No students selected for ${action}`, 'warning');
                return;
            }

            showLoading(true);

            // Send requests for each student
            const promises = studentIds.map(studentId => {
                return fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=update_student_status&student_id=${studentId}&is_active=${isActive}`
                }).then(response => response.json());
            });

            Promise.all(promises)
                .then(results => {
                    const successful = results.filter(r => r.success).length;
                    const failed = results.length - successful;

                    console.log('Bulk operation results:', {
                        successful,
                        failed,
                        total: results.length
                    });

                    if (successful > 0) {
                        const message = failed > 0 ?
                            `Successfully ${action}d ${successful} students. ${failed} failed.` :
                            `Successfully ${action}d ${successful} students`;

                        showAlert(message, failed > 0 ? 'warning' : 'success');
                    } else {
                        showAlert(`Failed to ${action} students`, 'danger');
                    }

                    // Refresh the list with current filters
                    applyStudentFilters();

                    // Clear selections
                    const selectAllCheckbox = document.getElementById('selectAllStudents');
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                    }

                    // Clear individual checkboxes
                    document.querySelectorAll('.student-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                })
                .catch(error => {
                    console.error('Bulk update error:', error);
                    showAlert(`Error during bulk ${action}: ${error.message}`, 'danger');
                })
                .finally(() => {
                    showLoading(false);
                });
        }

        // Student details view
        function viewStudentDetails(studentId) {
            // Create and show student details modal
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Student Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4">
                        <div class="loading-spinner mb-3"></div>
                        <span>Loading student details...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            // Clean up modal when hidden
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });

            // Load student details (you would implement this endpoint)
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=get_student_details&student_id=${studentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderStudentDetails(modal, data.student);
                    } else {
                        modal.querySelector('.modal-body').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load student details: ${data.message}
                </div>
            `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modal.querySelector('.modal-body').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading student details
            </div>
        `;
                });
        }

        function renderStudentDetails(modal, student) {
            modal.querySelector('.modal-body').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary mb-3">Personal Information</h6>
                <table class="table table-sm table-borderless">
                    <tr><td><strong>Name:</strong></td><td>${student.first_name || ''} ${student.last_name || ''}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${student.email || 'N/A'}</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>${student.phone_number || 'N/A'}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        <span class="badge bg-${student.is_active ? 'success' : 'warning'}">
                            ${student.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary mb-3">Account Information</h6>
                <table class="table table-sm table-borderless">
                    <tr><td><strong>Student ID:</strong></td><td>${student.user_id}</td></tr>
                    <tr><td><strong>Program:</strong></td><td>${student.program || 'N/A'}</td></tr>
                    <tr><td><strong>Registered:</strong></td><td>${formatDate(student.created_at)}</td></tr>
                    <tr><td><strong>Last Updated:</strong></td><td>${formatDate(student.updated_at)}</td></tr>
                </table>
            </div>
        </div>
    `;
        }


        class SmoothStudentFilter {
            constructor() {
                this.searchTimeout = null;
                this.currentFilters = {};
                this.isLoading = false;
                this.lastSearchTerm = '';

                this.setupEventListeners();
                this.createLoadingStates();
            }

            setupEventListeners() {
                // INSTANT SEARCH: Search as you type with debouncing
                const searchInput = document.getElementById('studentSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.trim();

                        // Show instant feedback
                        this.showSearchFeedback(searchTerm);

                        // Debounced search
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(() => {
                            this.handleSearch(searchTerm);
                        }, 300); // 300ms delay - feels instant but prevents too many requests
                    });

                    // Clear search with Escape key
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            searchInput.value = '';
                            this.handleSearch('');
                        }
                    });
                }

                // INSTANT FILTER CHANGES: Apply filters immediately
                const filters = ['studentStatusFilter', 'studentProgramFilter'];
                filters.forEach(filterId => {
                    const element = document.getElementById(filterId);
                    if (element) {
                        element.addEventListener('change', (e) => {
                            this.showFilterFeedback(filterId, e.target.value);
                            this.applyFiltersSmooth();
                        });
                    }
                });
            }

            createLoadingStates() {
                // Add subtle loading overlay just for the student table
                const tableContainer = document.querySelector('#students-section .data-table');
                if (tableContainer && !document.getElementById('studentTableLoadingOverlay')) {
                    const overlay = document.createElement('div');
                    overlay.id = 'studentTableLoadingOverlay';
                    overlay.className = 'table-loading-overlay';
                    overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 10;
                border-radius: 12px;
            `;
                    overlay.innerHTML = `
                <div style="text-align: center;">
                    <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Searching students...</div>
                </div>
            `;
                    tableContainer.style.position = 'relative';
                    tableContainer.appendChild(overlay);
                }
            }

            showTableLoading(show = true) {
                const overlay = document.getElementById('studentTableLoadingOverlay');
                if (overlay) {
                    overlay.style.display = show ? 'flex' : 'none';
                }
            }

            showSearchFeedback(searchTerm) {
                const searchInput = document.getElementById('studentSearch');
                if (!searchInput) return;

                // Add visual feedback to search input
                if (searchTerm.length > 0) {
                    searchInput.classList.add('searching');
                    searchInput.style.borderColor = '#2563eb';
                    searchInput.style.boxShadow = '0 0 0 2px rgba(37, 99, 235, 0.1)';
                } else {
                    searchInput.classList.remove('searching');
                    searchInput.style.borderColor = '';
                    searchInput.style.boxShadow = '';
                }
            }

            showFilterFeedback(filterId, value) {
                const filterElement = document.getElementById(filterId);
                if (!filterElement) return;

                // Add visual feedback to show filter is active
                if (value) {
                    filterElement.classList.add('filter-active');
                    filterElement.style.borderColor = '#2563eb';
                    filterElement.style.boxShadow = '0 0 0 2px rgba(37, 99, 235, 0.1)';
                } else {
                    filterElement.classList.remove('filter-active');
                    filterElement.style.borderColor = '';
                    filterElement.style.boxShadow = '';
                }
            }

            handleSearch(searchTerm) {
                this.currentFilters.search = searchTerm;
                this.loadStudentsSmooth();
            }

            applyFiltersSmooth() {
                // Collect all filter values
                this.currentFilters = {
                    search: document.getElementById('studentSearch')?.value || '',
                    status: document.getElementById('studentStatusFilter')?.value || '',
                    program: document.getElementById('studentProgramFilter')?.value || ''
                };

                this.loadStudentsSmooth();
            }

            async loadStudentsSmooth() {
                if (this.isLoading) return; // Prevent multiple simultaneous requests

                this.isLoading = true;

                // Show subtle loading state
                this.showTableLoading(true);
                this.addTableLoadingClass();

                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'get_students');

                    // Add filters to POST data
                    Object.keys(this.currentFilters).forEach(key => {
                        if (this.currentFilters[key]) {
                            formData.append(key, this.currentFilters[key]);
                        }
                    });

                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData.toString()
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Smooth table update instead of instant replace
                        await this.updateTableSmoothly(data.students);

                        // Show results feedback
                        this.showResultsFeedback(data.students);

                    } else {
                        this.showError(data.message);
                    }

                } catch (error) {
                    console.error('Student search error:', error);
                    this.showError('Failed to load students');
                } finally {
                    this.isLoading = false;
                    this.showTableLoading(false);
                    this.removeTableLoadingClass();
                }
            }

            addTableLoadingClass() {
                const table = document.getElementById('studentsTable');
                if (table) {
                    table.style.opacity = '0.6';
                    table.style.pointerEvents = 'none';
                }
            }

            removeTableLoadingClass() {
                const table = document.getElementById('studentsTable');
                if (table) {
                    table.style.opacity = '1';
                    table.style.pointerEvents = 'auto';
                }
            }

            async updateTableSmoothly(students) {
                const tbody = document.getElementById('studentsTable');
                if (!tbody) return;

                // Fade out old content
                tbody.style.opacity = '0.3';

                // Small delay for smooth transition
                await new Promise(resolve => setTimeout(resolve, 150));

                if (!students || students.length === 0) {
                    tbody.innerHTML = this.getNoResultsHTML();
                } else {
                    tbody.innerHTML = students.map(student => this.createStudentRowHTML(student)).join('');
                }

                // Update stats and header
                this.updateStudentStats(students);
                this.updateStudentTableHeader();

                // Fade in new content
                tbody.style.opacity = '1';

                // Add subtle animation to new rows
                const rows = tbody.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    row.style.transform = 'translateY(10px)';
                    row.style.opacity = '0';

                    setTimeout(() => {
                        row.style.transform = 'translateY(0)';
                        row.style.opacity = '1';
                        row.style.transition = 'all 0.3s ease';
                    }, index * 30); // Stagger animation
                });
            }

            createStudentRowHTML(student) {
                return `
            <tr class="${!student.is_active ? 'table-danger' : ''}" 
                style="${!student.is_active ? 'background-color: #f8d7da !important; border-color: #f5c6cb !important;' : ''}">
                <td>
                    <input type="checkbox" class="form-check-input student-checkbox" 
                           value="${student.user_id}" data-active="${student.is_active}">
                </td>
                <td>
                    <strong>${student.user_id}</strong>
                    ${!student.is_active ? '<small class="text-danger d-block"><i class="fas fa-ban me-1"></i>DEACTIVATED</small>' : ''}
                </td>
                <td>
                    ${student.first_name || ''} ${student.last_name || ''}
                    ${!student.is_active ? '<small class="text-danger d-block"><i class="fas fa-exclamation-triangle me-1"></i>Account Inactive</small>' : ''}
                </td>
                <td>${student.email || 'N/A'}</td>
                <td>${student.phone_number || 'N/A'}</td>
                <td>${student.program || 'N/A'}</td>
                <td>
                    <span class="status-badge bg-${student.is_active ? 'success' : 'danger'} ${!student.is_active ? 'text-white' : ''}">
                        <i class="fas fa-${student.is_active ? 'user-check' : 'user-times'} me-1"></i>
                        ${student.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>${this.formatDate(student.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn ${student.is_active ? 'btn-outline-warning' : 'btn-outline-success'}" 
                                onclick="toggleStudentStatusEnhanced(${student.user_id}, ${!student.is_active}, '${student.first_name} ${student.last_name}')" 
                                title="${student.is_active ? 'Deactivate' : 'Activate'} Student">
                            <i class="fas fa-${student.is_active ? 'user-slash' : 'user-check'}"></i>
                            ${student.is_active ? 'Deactivate' : 'Activate'}
                        </button>
                    </div>
                </td>
            </tr>
        `;
            }

            getNoResultsHTML() {
                const hasFilters = Object.values(this.currentFilters).some(value => value && value.trim());

                if (hasFilters) {
                    return `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <div>No students found matching your criteria</div>
                            <button class="btn btn-link btn-sm mt-2" onclick="studentFilter.clearAllFilters()">
                                <i class="fas fa-times me-1"></i>Clear filters
                            </button>
                        </div>
                    </td>
                </tr>
            `;
                } else {
                    return `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <div>No students found</div>
                        </div>
                    </td>
                </tr>
            `;
                }
            }

            updateStudentStats(students) {
                const activeCount = students.filter(s => s.is_active).length;
                const inactiveCount = students.filter(s => !s.is_active).length;

                // Create or update stats display
                let statsDiv = document.getElementById('studentStats');
                if (!statsDiv) {
                    statsDiv = document.createElement('div');
                    statsDiv.id = 'studentStats';
                    statsDiv.className = 'mb-3';

                    const studentsSection = document.getElementById('students-section');
                    const dataTable = studentsSection.querySelector('.data-table');
                    studentsSection.insertBefore(statsDiv, dataTable);
                }

                statsDiv.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex gap-4 align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">${students.length}</span>
                            <span>Total Students</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">${activeCount}</span>
                            <span>Active</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger me-2">${inactiveCount}</span>
                            <span>Inactive</span>
                        </div>
                        ${this.currentFilters.search || this.currentFilters.status || this.currentFilters.program ? `
                        <div class="ms-auto">
                            <button class="btn btn-sm btn-outline-secondary" onclick="studentFilter.clearAllFilters()">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </button>
                        </div>` : ''}
                    </div>
                </div>
            </div>
        `;
            }

            updateStudentTableHeader() {
                const thead = document.querySelector('#studentsTable').closest('table').querySelector('thead tr');
                if (thead && !thead.querySelector('.select-all-column')) {
                    thead.innerHTML = `
                <th class="select-all-column">
                    <input type="checkbox" class="form-check-input" id="selectAllStudents" 
                           onchange="toggleAllStudentSelection(this)">
                </th>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Program</th>
                <th>Status</th>
                <th>Registered Date</th>
                <th>Actions</th>
            `;
                }
            }

            showResultsFeedback(students) {
                const total = students.length;
                const hasFilters = Object.values(this.currentFilters).some(value => value && value.trim());

                if (hasFilters && total > 0) {
                    this.showToast(`Found ${total} students`, 'success', 2000);
                } else if (hasFilters && total === 0) {
                    this.showToast('No students match your criteria', 'info', 2000);
                }
            }

            showError(message) {
                this.showToast(message, 'error', 4000);
            }

            showToast(message, type = 'info', duration = 3000) {
                // Remove existing toasts
                document.querySelectorAll('.student-filter-toast').forEach(toast => toast.remove());

                const colors = {
                    success: '#16a34a',
                    error: '#dc2626',
                    info: '#2563eb'
                };

                const toast = document.createElement('div');
                toast.className = 'student-filter-toast';
                toast.style.cssText = `
            position: fixed;
            top: 120px;
            right: 20px;
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 12px 16px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            font-size: 14px;
            color: #374151;
        `;

                toast.textContent = message;
                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => toast.style.transform = 'translateX(0)', 100);

                // Auto remove
                setTimeout(() => {
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            clearAllFilters() {
                // Clear all filter inputs
                document.getElementById('studentSearch').value = '';
                document.getElementById('studentStatusFilter').value = '';
                document.getElementById('studentProgramFilter').value = '';

                // Remove visual feedback
                document.querySelectorAll('.filter-active').forEach(el => {
                    el.classList.remove('filter-active');
                    el.style.borderColor = '';
                    el.style.boxShadow = '';
                });

                // Clear search feedback
                const searchInput = document.getElementById('studentSearch');
                searchInput.classList.remove('searching');
                searchInput.style.borderColor = '';
                searchInput.style.boxShadow = '';

                // Reset filters and reload
                this.currentFilters = {};
                this.loadStudentsSmooth();

                this.showToast('Filters cleared', 'info', 2000);
            }

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                return new Date(dateString).toLocaleDateString();
            }
        }
    </script>
</body>

</html>