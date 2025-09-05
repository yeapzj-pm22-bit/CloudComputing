<?php
//student/portal.php
// Start session and include bootstrap
session_start();
require_once '../includes/bootstrap.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'Student') {
    header('Location: ../index.php');
    exit;
}

// Initialize models
$user = new User();
$notification = new Notification();
$application = new Application();

// Get current user data
$currentUser = $user->getUserById($_SESSION['user_id']);
if (!$currentUser) {
    header('Location: ../index.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'get_application_for_edit':
                $applicationId = $_POST['application_id'] ?? 0;

                // Use the fixed method from Application class
                $applicationResult = $application->getApplicationForEdit($applicationId, $_SESSION['user_id']);

                if ($applicationResult && $applicationResult['data']) {
                    echo json_encode([
                        'success' => true,
                        'data' => $applicationResult['data'],
                        'editable' => $applicationResult['editable'],
                        'deletable' => $applicationResult['deletable'],
                        'status' => $applicationResult['status']
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Application not found or access denied'
                    ]);
                }
                break;

            case 'update_application_data':
                $applicationId = $_POST['application_id'] ?? 0;

                // Get current application to check status
                $currentApp = $application->getApplicationDetails($applicationId, $_SESSION['user_id']);
                if (!$currentApp) {
                    echo json_encode(['success' => false, 'message' => 'Application not found']);
                    break;
                }

                // Check if application can be edited
                $editableStatuses = ['submitted', 'under-review'];
                if (!in_array($currentApp['status'], $editableStatuses)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'This application cannot be edited in its current status: ' . ucfirst($currentApp['status'])
                    ]);
                    break;
                }

                // Prepare update data
                $updateData = [];

                // Personal information updates
                $personalFields = [
                    'gender',
                    'nationality',
                    'address',
                    'emergency_contact_name',
                    'emergency_contact_phone',
                    'emergency_contact_relationship'
                ];
                foreach ($personalFields as $field) {
                    if (isset($_POST[$field])) {
                        $updateData[$field] = $_POST[$field];
                    }
                }

                // Academic information updates
                $academicFields = [
                    'program',
                    'program_level',
                    'enrollment_type',
                    'start_term',
                    'expected_graduation_year',
                    'preferred_campus',
                    'scholarship_applied',
                    'scholarship_type'
                ];
                foreach ($academicFields as $field) {
                    if (isset($_POST[$field])) {
                        $updateData[$field] = $_POST[$field];
                    }
                }

                // Educational information updates
                $educationalFields = [
                    'education_level',
                    'institution_name',
                    'graduation_year',
                    'grade_type',
                    'grade_value',
                    'subjects_count',
                    'certificate_number'
                ];
                foreach ($educationalFields as $field) {
                    if (isset($_POST[$field])) {
                        $updateData[$field] = $_POST[$field];
                    }
                }

                // Add notes if provided
                if (isset($_POST['notes'])) {
                    $updateData['notes'] = $_POST['notes'];
                }

                // Perform the update
                $result = $application->updateApplication($applicationId, $_SESSION['user_id'], $updateData);

                if ($result['success']) {
                    // Log status change if application was under review
                    if ($currentApp['status'] === 'under-review') {
                        $statusHistory = new StatusHistory();
                        $statusHistory->logStatusChange(
                            $applicationId,
                            $currentApp['status'],
                            'under-review',
                            $_SESSION['user_id'],
                            'Application updated by student'
                        );
                    }

                    // Create notification
                    $notification->createNotification(
                        $_SESSION['user_id'],
                        'Application Updated',
                        "Your application #{$currentApp['application_number']} has been updated successfully.",
                        'success',
                        $applicationId
                    );
                }

                echo json_encode($result);
                break;

            case 'delete_application_request':
                $applicationId = $_POST['application_id'] ?? 0;

                // Get current application
                $currentApp = $application->getApplicationDetails($applicationId, $_SESSION['user_id']);
                if (!$currentApp) {
                    echo json_encode(['success' => false, 'message' => 'Application not found']);
                    break;
                }

                // Check if application can be deleted (only submitted status)
                $deletableStatuses = ['submitted'];
                if (!in_array($currentApp['status'], $deletableStatuses)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Applications in "' . ucfirst(str_replace('-', ' ', $currentApp['status'])) .
                            '" status cannot be deleted. Please contact support if you need assistance.'
                    ]);
                    break;
                }

                // Perform soft delete - just update status to 'deleted'
                $result = $application->update($applicationId, [
                    'status' => 'deleted'
                ]);

                if ($result) {
                    // Log status change
                    $statusHistory = new StatusHistory();
                    $statusHistory->logStatusChange(
                        $applicationId,
                        $currentApp['status'],
                        'deleted',
                        $_SESSION['user_id'],
                        'Application deleted by student'
                    );

                    // Create notification
                    $notification->createNotification(
                        $_SESSION['user_id'],
                        'Application Deleted',
                        "Your application #{$currentApp['application_number']} has been deleted successfully.",
                        'warning',
                        $applicationId
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => 'Application deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete application. Please try again.'
                    ]);
                }
                break;

            case 'get_recent_notifications':
                $limit = $_POST['limit'] ?? 10;
                $notifications = $notification->getUserNotifications($_SESSION['user_id'], $limit);

                // Format notifications for frontend
                $formattedNotifications = array_map(function ($notif) {
                    return [
                        'id' => $notif['notification_id'],
                        'title' => $notif['title'],
                        'message' => $notif['message'],
                        'type' => $notif['type'],
                        'isRead' => (bool)$notif['is_read'],
                        'time' => timeAgo($notif['created_at']),
                        'created_at' => $notif['created_at'],
                        'related_application_id' => $notif['related_application_id']
                    ];
                }, $notifications);

                echo json_encode([
                    'success' => true,
                    'data' => $formattedNotifications,
                    'unread_count' => $notification->getUnreadCount($_SESSION['user_id'])
                ]);
                break;

            case 'mark_all_notifications_read':
                $result = $notification->markAllAsReadForUser($_SESSION['user_id']);
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications as read'
                ]);
                break;
            case 'get_application_details':
                $applicationId = $_POST['application_id'] ?? 0;
                $details = $application->getApplicationDetails($applicationId, $_SESSION['user_id']);
                if ($details) {

                    $statusHistory = new StatusHistory();
                    $history = $statusHistory->getHistoryByApplication($applicationId);

                    echo json_encode([
                        'success' => true,
                        'data' => $details,
                        'status_history' => $history  // Add status history to response
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Application not found'
                    ]);
                }
                break;

            case 'update_application':
                $applicationId = $_POST['application_id'] ?? 0;
                $result = $application->updateApplication($applicationId, $_SESSION['user_id'], $_POST);
                echo json_encode($result);
                break;

            case 'delete_application':
                $applicationId = $_POST['application_id'] ?? 0;
                $result = $application->deleteApplication($applicationId, $_SESSION['user_id']);
                echo json_encode($result);
                break;

            case 'get_programs':
                $programs = $application->getAvailablePrograms();
                echo json_encode(['success' => true, 'data' => $programs]);
                break;
            case 'get_dashboard_stats':
                $stats = $application->getStudentStats($_SESSION['user_id']);
                echo json_encode(['success' => true, 'data' => $stats]);
                break;

            case 'get_applications':
                $limit = isset($_POST['limit']) ? intval($_POST['limit']) : null;
                $applications = $application->getStudentApplications($_SESSION['user_id'], $limit);
                echo json_encode(['success' => true, 'data' => $applications]);
                break;

            case 'get_notifications':
                $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                echo json_encode(['success' => true, 'data' => $notifications]);
                break;

            case 'mark_notification_read':
                $result = $notification->markNotificationRead($_POST['notification_id'], $_SESSION['user_id']);
                echo json_encode(['success' => $result]);
                break;

            case 'create_application':
                $result = $application->createApplication($_SESSION['user_id'], $_POST);
                echo json_encode($result);
                break;

            case 'update_profile':
                $result = $user->updateProfile($_SESSION['user_id'], $_POST);
                echo json_encode($result);
                break;

            case 'change_password':
                $result = $user->changeUserPassword(
                    $_SESSION['user_id'],
                    $_POST['current_password'],
                    $_POST['new_password']
                );
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Portal AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    }
    exit;
}

function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - University of Excellence</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        #documentPreviewModal #pdfPreview object,
        #documentPreviewModal #pdfPreview embed {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 500px;
            background: white;
        }

        #documentPreviewModal #pdfPreview {
            background: white;
            width: 100%;
            height: 100%;
        }

        #documentPreviewModal #pdfPreview iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: none;
            background: white;
            min-height: 400px;
        }

        #documentPreviewModal #previewLoading[style*="display: none"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            top: -9999px !important;
        }

        #documentPreviewModal #previewLoading {
            z-index: 1000;
        }

        /* Force show container when visible */
        #documentPreviewModal #previewContainer[style*="display: block"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
        }

        #documentPreviewModal.modal[style*="display: block"] #previewContainer[style*="display: block"] {
            display: block !important;
        }

        #documentPreviewModal #previewContainer {
            z-index: 1001;
        }

        /* Force show PDF preview when visible */
        #documentPreviewModal #pdfPreview[style*="display: block"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: 100% !important;
            width: 100% !important;
        }

        #documentPreviewModal #pdfPreview {
            background: #f8f9fa;
            position: relative;
        }

        #documentPreviewModal .modal-body * {
            transition: none !important;
        }

        /* Ensure iframe is properly sized */


        /* Force show image preview when visible */
        #documentPreviewModal #imagePreview[style*="display: flex"] {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #documentPreviewModal .modal-body {
            overflow: visible !important;
            height: 80vh !important;
            min-height: 400px !important;
            padding: 0 !important;
        }

        #documentPreviewModal .modal-xl {
            max-width: 95vw;
        }

        #documentPreviewModal.fullscreen {
            z-index: 9999;
        }

        #documentPreviewModal.fullscreen .modal-dialog {
            max-width: 100vw;
            height: 100vh;
            margin: 0;
        }

        #documentPreviewModal.fullscreen .modal-content {
            height: 100vh;
            border-radius: 0;
        }

        #documentPreviewModal.fullscreen .modal-body {
            height: calc(100vh - 140px);
        }

        .preview-zoom {
            cursor: zoom-in;
            transition: transform 0.2s;
        }

        .preview-zoom:hover {
            transform: scale(1.05);
        }

        .document-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            background: #fff;
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

        .min-width-0 {
            min-width: 0;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Modal customizations */
        .modal-xl {
            max-width: 95vw;
        }

        .document-preview-loading {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .document-card {
                margin-bottom: 1rem;
            }

            .modal-xl {
                max-width: 98vw;
                margin: 1rem;
            }

            #documentPreviewModal .modal-body {
                height: 60vh;
            }
        }

        /* Status badge colors */
        .bg-pending {
            background-color: #fbbf24 !important;
        }

        .bg-verified {
            background-color: #10b981 !important;
        }

        .bg-rejected {
            background-color: #ef4444 !important;
        }

        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f9fafb;
        }

        .notification-item.unread {
            background-color: #eff6ff;
            border-left: 3px solid var(--primary-color);
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 4px;
            color: #1f2937;
        }

        .notification-message {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        /* Application Action Buttons */
        .application-actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        /* Tab Styles */
        .nav-tabs .nav-link {
            border: none;
            background: transparent;
            color: #6b7280;
            margin-right: 0.5rem;
            border-radius: 6px;
        }

        .nav-tabs .nav-link.active {
            background: #f3f4f6;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'><circle cx='6' cy='6' r='4.5'/><path d='m5.8 3.6h.4L6 6.5z'/><circle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/></svg>");
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .form-control.is-invalid~.invalid-feedback,
        .form-select.is-invalid~.invalid-feedback {
            display: block;
        }

        .document-status-item {
            padding: 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .document-status-uploaded {
            background-color: #d1edff;
            border: 1px solid #0d6efd;
        }

        .document-status-required {
            background-color: #f8d7da;
            border: 1px solid #dc3545;
        }

        .document-status-optional {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step.active .step-number {
            background: var(--primary-color);
            color: white;
        }

        .step.completed .step-number {
            background: var(--success-color);
            color: white;
        }

        .step-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
        }

        .step.active .step-label {
            color: var(--primary-color);
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: -1;
        }

        .step:first-child::before {
            display: none;
        }

        .step.completed::before {
            background: var(--success-color);
        }

        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
        }

        body {
            background-color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1f2937;
        }

        .navbar {
            background-color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e5e7eb;
        }

        .main-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            text-transform: capitalize;
        }

        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .application-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .application-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .timeline-item {
            border-left: 2px solid #e5e7eb;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            position: absolute;
            left: -5px;
            top: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-tabs .nav-link {
            border: none;
            background: transparent;
            color: #6b7280;
            margin-right: 0.5rem;
            border-radius: 6px;
        }

        .nav-tabs .nav-link.active {
            background: #f3f4f6;
            color: var(--primary-color);
        }

        .alert {
            border: none;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .alert-info {
            border-left-color: var(--info-color);
            background-color: #ecfeff;
            color: #0e7490;
        }

        .alert-success {
            border-left-color: var(--success-color);
            background-color: #ecfdf5;
            color: #047857;
        }

        .alert-warning {
            border-left-color: var(--warning-color);
            background-color: #fffbeb;
            color: #92400e;
        }

        .alert-danger {
            border-left-color: var(--danger-color);
            background-color: #fef2f2;
            color: #b91c1c;
        }

        #documentPreviewModal #previewError[style*="display: none"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
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

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-top: 1rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        #documentPreviewModal #pdfPreview {
            min-height: 400px !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #documentPreviewModal #pdfPreview * {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #documentPreviewModal #imagePreview[style*="display: none"],
        #documentPreviewModal #textPreview[style*="display: none"] {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 0 0 1px var(--primary-color);
        }

        .timeline-item:last-child {
            border-color: transparent !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="bg-primary text-white rounded me-3 d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="fw-bold text-primary">Student Portal</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="showSection('dashboard')">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showSection('applications')">
                            <i class="fas fa-file-alt me-1"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showSection('profile')">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown me-3">
                        <div class="notification-bell dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg text-secondary"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </div>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;" id="notificationDropdown">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <h6 class="mb-0 fw-bold">Notifications</h6>
                                <button class="btn btn-sm btn-outline-primary" onclick="markAllNotificationsRead()">
                                    Mark All Read
                                </button>
                            </div>
                            <div id="notificationsList">
                                <div class="text-center py-3">
                                    <div class="loading-spinner"></div>
                                    <span class="ms-2">Loading...</span>
                                </div>
                            </div>
                            <div class="p-3 border-top bg-light">
                                <button class="btn btn-sm btn-primary w-100" onclick="viewAllNotifications()">
                                    View All Notifications
                                </button>
                            </div>
                        </div>
                    </li>


                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <div class="bg-secondary text-white rounded me-2 d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="showSection('profile')">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="changePassword()">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="section active">
            <!-- Welcome Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1">Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h4>
                            <p class="mb-0 text-muted">Here's your application overview</p>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">Today</div>
                            <div class="text-muted" id="currentDate"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="fw-bold mb-1" id="totalApplications">-</h3>
                        <p class="text-muted mb-0">Total Applications</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="fw-bold mb-1" id="pendingApplications">-</h3>
                        <p class="text-muted mb-0">Under Review</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="fw-bold mb-1" id="approvedApplications">-</h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h3 class="fw-bold mb-1" id="documentsCount">-</h3>
                        <p class="text-muted mb-0">Documents Uploaded</p>
                    </div>
                </div>
            </div>

            <!-- Recent Applications and Notifications -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentApplicationsList">
                                <div class="text-center py-3">
                                    <div class="loading-spinner"></div>
                                    <span class="ms-2">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Recent Updates</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentNotifications">
                                <div class="text-center py-3">
                                    <div class="loading-spinner"></div>
                                    <span class="ms-2">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div id="applications-section" class="section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">My Applications</h2>
                <button class="btn btn-primary" onclick="showNewApplicationModal()">
                    <i class="fas fa-plus me-2"></i>New Application
                </button>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search Applications</label>
                            <input type="text" class="form-control" id="applicationSearchInput"
                                placeholder="Search by program, status...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Status</label>
                            <select class="form-select" id="statusFilterSelect">
                                <option value="">All Status</option>
                                <option value="submitted">Submitted</option>
                                <option value="under-review">Under Review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="waitlisted">Waitlisted</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Program</label>
                            <select class="form-select" id="programFilterSelect">
                                <option value="">All Programs</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-outline-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter me-1"></i>Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications List -->
            <div id="applicationsList">
                <div class="text-center py-4">
                    <div class="loading-spinner"></div>
                    <span class="ms-2">Loading applications...</span>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile-section" class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="profileForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name"
                                            value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name"
                                            value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email"
                                            value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone_number"
                                            value="<?php echo htmlspecialchars($currentUser['phone_number']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth"
                                            value="<?php echo htmlspecialchars($currentUser['date_of_birth']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Account Type</label>
                                        <input type="text" class="form-control" value="Student" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetProfile()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Account Security</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <button class="btn btn-outline-primary" onclick="showChangePasswordModal()">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                                <button class="btn btn-outline-info" onclick="viewLoginHistory()">
                                    <i class="fas fa-history me-2"></i>Login History
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Create New Application
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newApplicationForm" novalidate>
                    <div class="modal-body">
                        <!-- Progress Steps -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <div class="step active" data-step="1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Personal Info</div>
                                </div>
                                <div class="step" data-step="2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Academic</div>
                                </div>
                                <div class="step" data-step="3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Education</div>
                                </div>
                                <div class="step" data-step="4">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Documents</div>
                                </div>
                                <div class="step" data-step="5">
                                    <div class="step-number">5</div>
                                    <div class="step-label">Review</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: Personal Information -->
                        <div class="form-step active" data-step="1">
                            <h6 class="mb-3">Personal Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" name="gender" data-required="true">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nationality <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nationality" data-required="true">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="address" rows="3" data-required="true"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" name="emergency_contact_name">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Phone</label>
                                    <input type="tel" class="form-control" name="emergency_contact_phone">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Relationship</label>
                                    <select class="form-select" name="emergency_contact_relationship">
                                        <option value="">Select Relationship</option>
                                        <option value="Parent">Parent</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Spouse">Spouse</option>
                                        <option value="Sibling">Sibling</option>
                                        <option value="Friend">Friend</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Academic Information -->
                        <div class="form-step" data-step="2">
                            <h6 class="mb-3">Academic Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Program <span class="text-danger">*</span></label>
                                    <select class="form-select" name="program" data-required="true">
                                        <option value="">Select Program</option>
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Business Administration">Business Administration</option>
                                        <option value="Engineering">Engineering</option>
                                        <option value="Medicine">Medicine</option>
                                        <option value="Law">Law</option>
                                        <option value="Education">Education</option>
                                        <option value="Arts and Humanities">Arts and Humanities</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Program Level <span class="text-danger">*</span></label>
                                    <select class="form-select" name="program_level" data-required="true">
                                        <option value="">Select Level</option>
                                        <option value="Certificate">Certificate</option>
                                        <option value="Diploma">Diploma</option>
                                        <option value="Bachelor">Bachelor</option>
                                        <option value="Master">Master</option>
                                        <option value="PhD">PhD</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Enrollment Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="enrollment_type" data-required="true">
                                        <option value="">Select Type</option>
                                        <option value="Full-Time">Full-Time</option>
                                        <option value="Part-Time">Part-Time</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Term <span class="text-danger">*</span></label>
                                    <select class="form-select" name="start_term" data-required="true">
                                        <option value="">Select Term</option>
                                        <option value="Fall 2024">Fall 2024</option>
                                        <option value="Spring 2025">Spring 2025</option>
                                        <option value="Summer 2025">Summer 2025</option>
                                        <option value="Fall 2025">Fall 2025</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expected Graduation Year</label>
                                    <input type="number" class="form-control" name="expected_graduation_year"
                                        min="2024" max="2030" placeholder="e.g. 2027">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Campus</label>
                                    <select class="form-select" name="preferred_campus">
                                        <option value="">Select Campus</option>
                                        <option value="Main Campus">Main Campus</option>
                                        <option value="North Campus">North Campus</option>
                                        <option value="South Campus">South Campus</option>
                                        <option value="Online">Online</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="scholarship_applied" id="scholarshipCheck">
                                        <label class="form-check-label" for="scholarshipCheck">
                                            I am applying for a scholarship
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="scholarshipTypeDiv" style="display: none;">
                                    <label class="form-label">Scholarship Type</label>
                                    <select class="form-select" name="scholarship_type">
                                        <option value="">Select Scholarship Type</option>
                                        <option value="Academic Merit">Academic Merit</option>
                                        <option value="Need-Based">Need-Based</option>
                                        <option value="Athletic">Athletic</option>
                                        <option value="Minority">Minority</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Educational Background -->
                        <div class="form-step" data-step="3">
                            <h6 class="mb-3">Educational Background</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Education Level <span class="text-danger">*</span></label>
                                    <select class="form-select" name="education_level" data-required="true">
                                        <option value="">Select Level</option>
                                        <option value="High School">High School</option>
                                        <option value="Foundation">Foundation</option>
                                        <option value="Diploma">Diploma</option>
                                        <option value="Bachelor">Bachelor</option>
                                        <option value="Master">Master</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Institution Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="institution_name" data-required="true">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Graduation Year <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="graduation_year" data-required="true"
                                        min="1990" max="2024">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grade Type</label>
                                    <select class="form-select" name="grade_type">
                                        <option value="">Select Grade Type</option>
                                        <option value="GPA">GPA</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Letter">Letter Grade</option>
                                        <option value="Pass/Fail">Pass/Fail</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grade Value</label>
                                    <input type="text" class="form-control" name="grade_value"
                                        placeholder="e.g. 3.5, 85%, A, Pass">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Number of Subjects</label>
                                    <input type="number" class="form-control" name="subjects_count" min="1">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Certificate Number</label>
                                    <input type="text" class="form-control" name="certificate_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Document Upload -->
                        <div class="form-step" data-step="4">
                            <h6 class="mb-3">Document Upload</h6>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Upload required documents for your application. You can upload additional documents after submission.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Academic Transcript <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="transcript" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" data-required="true">
                                    <div class="form-text">PDF, Image, or Word document (Max 5MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Certificate/Diploma <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="certificate" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" data-required="true">
                                    <div class="form-text">PDF, Image, or Word document (Max 5MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Identity Document <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="identity" accept=".pdf,.jpg,.jpeg,.png" data-required="true">
                                    <div class="form-text">ID Card, Passport, or Birth Certificate (Max 5MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Passport Photo <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="photo" accept=".jpg,.jpeg,.png" data-required="true">
                                    <div class="form-text">Professional photo (Max 2MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Personal Statement</label>
                                    <input type="file" class="form-control" name="personal_statement" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Optional: PDF or Word document (Max 5MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Recommendation Letter</label>
                                    <input type="file" class="form-control" name="recommendation_letter" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Optional: From teacher or employer (Max 5MB)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Document Upload Status</h6>
                                <div id="documentStatus" class="list-group">
                                    <!-- Status will be populated here -->
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Review -->
                        <div class="form-step" data-step="5">
                            <h6 class="mb-3">Review Your Application</h6>
                            <div id="applicationReview"></div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Please review all information carefully before submitting. You can edit your application after submission if needed.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="prevStepBtn" style="display: none;">
                            <i class="fas fa-arrow-left me-2"></i>Previous
                        </button>
                        <button type="button" class="btn btn-primary" id="nextStepBtn">
                            Next<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-success" id="submitApplicationBtn" style="display: none;">
                            <i class="fas fa-paper-plane me-2"></i>Submit Application
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Application Modal -->
    <div class="modal fade" id="editApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Application
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editApplicationForm">
                    <input type="hidden" id="editApplicationId" name="application_id">
                    <div class="modal-body">
                        <!-- Loading State -->
                        <div id="editApplicationLoading" class="text-center py-4">
                            <div class="loading-spinner"></div>
                            <span class="ms-2">Loading application data...</span>
                        </div>

                        <!-- Edit Form Content -->
                        <div id="editApplicationContent" style="display: none;">
                            <!-- Application Status Alert -->
                            <div id="editStatusAlert"></div>

                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#editPersonalTab">Personal Info</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#editAcademicTab">Academic Info</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#editEducationalTab">Educational Background</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Personal Information Tab -->
                                <div class="tab-pane fade show active" id="editPersonalTab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Gender</label>
                                            <select class="form-select" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nationality</label>
                                            <input type="text" class="form-control" name="nationality">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="3"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Emergency Contact Name</label>
                                            <input type="text" class="form-control" name="emergency_contact_name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Emergency Contact Phone</label>
                                            <input type="tel" class="form-control" name="emergency_contact_phone">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Emergency Contact Relationship</label>
                                            <select class="form-select" name="emergency_contact_relationship">
                                                <option value="">Select Relationship</option>
                                                <option value="Parent">Parent</option>
                                                <option value="Guardian">Guardian</option>
                                                <option value="Spouse">Spouse</option>
                                                <option value="Sibling">Sibling</option>
                                                <option value="Friend">Friend</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information Tab -->
                                <div class="tab-pane fade" id="editAcademicTab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Program</label>
                                            <select class="form-select" name="program">
                                                <option value="">Select Program</option>
                                                <option value="Computer Science">Computer Science</option>
                                                <option value="Business Administration">Business Administration</option>
                                                <option value="Engineering">Engineering</option>
                                                <option value="Medicine">Medicine</option>
                                                <option value="Law">Law</option>
                                                <option value="Education">Education</option>
                                                <option value="Arts and Humanities">Arts and Humanities</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Program Level</label>
                                            <select class="form-select" name="program_level">
                                                <option value="">Select Level</option>
                                                <option value="Certificate">Certificate</option>
                                                <option value="Diploma">Diploma</option>
                                                <option value="Bachelor">Bachelor</option>
                                                <option value="Master">Master</option>
                                                <option value="PhD">PhD</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Enrollment Type</label>
                                            <select class="form-select" name="enrollment_type">
                                                <option value="">Select Type</option>
                                                <option value="Full-Time">Full-Time</option>
                                                <option value="Part-Time">Part-Time</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Start Term</label>
                                            <select class="form-select" name="start_term">
                                                <option value="">Select Term</option>
                                                <option value="Fall 2024">Fall 2024</option>
                                                <option value="Spring 2025">Spring 2025</option>
                                                <option value="Summer 2025">Summer 2025</option>
                                                <option value="Fall 2025">Fall 2025</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Expected Graduation Year</label>
                                            <input type="number" class="form-control" name="expected_graduation_year" min="2024" max="2030">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Preferred Campus</label>
                                            <select class="form-select" name="preferred_campus">
                                                <option value="">Select Campus</option>
                                                <option value="Main Campus">Main Campus</option>
                                                <option value="North Campus">North Campus</option>
                                                <option value="South Campus">South Campus</option>
                                                <option value="Online">Online</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="scholarship_applied" id="editScholarshipCheck">
                                                <label class="form-check-label" for="editScholarshipCheck">
                                                    I am applying for a scholarship
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="editScholarshipTypeDiv" style="display: none;">
                                            <label class="form-label">Scholarship Type</label>
                                            <select class="form-select" name="scholarship_type">
                                                <option value="">Select Scholarship Type</option>
                                                <option value="Academic Merit">Academic Merit</option>
                                                <option value="Need-Based">Need-Based</option>
                                                <option value="Athletic">Athletic</option>
                                                <option value="Minority">Minority</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Educational Background Tab -->
                                <div class="tab-pane fade" id="editEducationalTab">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Education Level</label>
                                            <select class="form-select" name="education_level">
                                                <option value="">Select Level</option>
                                                <option value="High School">High School</option>
                                                <option value="Foundation">Foundation</option>
                                                <option value="Diploma">Diploma</option>
                                                <option value="Bachelor">Bachelor</option>
                                                <option value="Master">Master</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Institution Name</label>
                                            <input type="text" class="form-control" name="institution_name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Graduation Year</label>
                                            <input type="number" class="form-control" name="graduation_year" min="1990" max="2024">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Grade Type</label>
                                            <select class="form-select" name="grade_type">
                                                <option value="">Select Grade Type</option>
                                                <option value="GPA">GPA</option>
                                                <option value="Percentage">Percentage</option>
                                                <option value="Letter">Letter Grade</option>
                                                <option value="Pass/Fail">Pass/Fail</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Grade Value</label>
                                            <input type="text" class="form-control" name="grade_value" placeholder="e.g. 3.5, 85%, A, Pass">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Certificate Number</label>
                                            <input type="text" class="form-control" name="certificate_number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <div class="mt-4">
                                <label class="form-label">Additional Notes</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Add any additional information or changes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="updateApplicationBtn">
                            <i class="fas fa-save me-2"></i>Update Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div class="modal fade" id="viewApplicationModal" tabindex="-1">
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
                    <button type="button" class="btn btn-primary" id="editFromDetailsBtn" style="display: none;">
                        <i class="fas fa-edit me-2"></i>Edit Application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Preview Modal -->
    <div class="modal fade" id="documentPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        <span id="previewFileName">Document Preview</span>
                    </h5>
                    <div class="ms-auto d-flex align-items-center me-3">
                        <!-- Download button -->
                        <button type="button" class="btn btn-outline-light btn-sm me-2" id="downloadDocBtn" title="Download">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <!-- Full screen toggle -->
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optimized Portal JavaScript - Remove ALL existing JS and replace with this
        // Global variables
        let currentPreviewDocument = null;
        let currentUser = <?php echo json_encode($currentUser); ?>;
        let currentApplications = [];
        let currentNotifications = [];
        let currentSection = 'dashboard';
        let currentStep = 1;
        const totalSteps = 5;
        let uploadedFiles = {};
        let currentEditingApplication = null;
        let currentViewingApplication = null;
        let previewState = {
            currentDocument: null,
            isLoading: false,
            loadTimeout: null,
            modalInstance: null
        };

        // Initialize application when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Portal initialized');
            setCurrentDate();
            loadDashboardData();
            setupAllEventListeners();
            setupFormValidation();
            loadNotificationsDropdown();
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', toggleFullscreen);
            }

            const previewModal = document.getElementById('documentPreviewModal');
            if (previewModal) {
                previewModal.addEventListener('hidden.bs.modal', function() {
                    console.log('Modal closed, cleaning up');
                    clearPreviewState();

                    // Reset fullscreen
                    const modal = document.getElementById('documentPreviewModal');
                    modal.classList.remove('fullscreen');

                    // Reset fullscreen button
                    const fullscreenBtn = document.getElementById('fullscreenBtn');
                    if (fullscreenBtn) {
                        const icon = fullscreenBtn.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-expand';
                            fullscreenBtn.title = 'Toggle Fullscreen';
                        }
                    }

                    previewState.currentDocument = null;
                    previewState.modalInstance = null;
                });
            }
        });


        // Setup all event listeners
        function setupAllEventListeners() {
            // Profile form submission
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', handleProfileUpdate);
            }

            // Change password form
            const passwordForm = document.getElementById('changePasswordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', handlePasswordChange);
            }

            // Application form submission
            const appForm = document.getElementById('newApplicationForm');
            if (appForm) {
                appForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (validateAllSteps()) {
                        submitApplication();
                    }
                });
            }

            // Edit application form
            const editForm = document.getElementById('editApplicationForm');
            if (editForm) {
                editForm.addEventListener('submit', handleEditApplicationSubmit);
            }

            // Search functionality
            const searchInput = document.getElementById('applicationSearchInput');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(applyFilters, 500);
                });
            }

            // Filter changes
            const statusFilter = document.getElementById('statusFilterSelect');
            const programFilter = document.getElementById('programFilterSelect');
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
            if (programFilter) programFilter.addEventListener('change', applyFilters);

            // Step navigation buttons
            const nextBtn = document.getElementById('nextStepBtn');
            const prevBtn = document.getElementById('prevStepBtn');

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    if (validateCurrentStep()) {
                        nextStep();
                    }
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    prevStep();
                });
            }

            // Scholarship toggle for new application
            const scholarshipCheckbox = document.querySelector('input[name="scholarship_applied"]');
            if (scholarshipCheckbox) {
                scholarshipCheckbox.addEventListener('change', function() {
                    const scholarshipDiv = document.getElementById('scholarshipTypeDiv');
                    if (scholarshipDiv) {
                        scholarshipDiv.style.display = this.checked ? 'block' : 'none';
                    }
                });
            }

            // Scholarship toggle for edit application
            const editScholarshipCheckbox = document.getElementById('editScholarshipCheck');
            if (editScholarshipCheckbox) {
                editScholarshipCheckbox.addEventListener('change', function() {
                    const scholarshipDiv = document.getElementById('editScholarshipTypeDiv');
                    if (scholarshipDiv) {
                        scholarshipDiv.style.display = this.checked ? 'block' : 'none';
                    }
                });
            }

            // Notification dropdown auto-load on show
            const notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown) {
                notificationDropdown.addEventListener('show.bs.dropdown', function() {
                    loadNotificationsDropdown();
                });
            }
        }

        function loadNotificationsDropdown() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_recent_notifications&limit=10'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentNotifications = data.data;
                        renderNotificationsDropdown(data.data);
                        updateNotificationCount(data.unread_count);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }

        function renderNotificationsDropdown(notifications) {
            const container = document.getElementById('notificationsList');
            if (!container) return;

            if (!notifications || notifications.length === 0) {
                container.innerHTML = '<div class="text-center py-3 text-muted">No notifications</div>';
                return;
            }

            container.innerHTML = notifications.map(notif => `
        <div class="notification-item ${!notif.isRead ? 'unread' : ''}" onclick="markNotificationRead(${notif.id})">
            <div class="notification-title">${notif.title}</div>
            <div class="notification-message">${notif.message}</div>
            <div class="notification-time">${notif.time}</div>
        </div>
    `).join('');
        }

        // Form validation setup
        function setupFormValidation() {
            const form = document.getElementById('newApplicationForm');
            if (form) {
                form.setAttribute('novalidate', 'true');

                // Setup real-time validation
                form.addEventListener('blur', function(e) {
                    if (e.target.matches('input, select, textarea')) {
                        validateField(e.target);
                    }
                }, true);

                form.addEventListener('change', function(e) {
                    if (e.target.matches('select, input[type="checkbox"], input[type="radio"], input[type="file"]')) {
                        validateField(e.target);
                        if (currentStep === 4 && e.target.type === 'file') {
                            updateDocumentStatus();
                        }
                    }
                });
            }
        }

        // Section management
        function showSection(sectionName) {
            console.log('Switching to section:', sectionName);

            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
                currentSection = sectionName;
            }

            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            const activeLink = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // Load section data
            switch (sectionName) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'applications':
                    loadApplications();
                    break;
                case 'profile':
                    // Profile data is already loaded
                    break;
            }
        }

        // Dashboard functions
        function setCurrentDate() {
            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
                const now = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                dateElement.textContent = now.toLocaleDateString('en-US', options);
            }
        }

        function loadDashboardData() {
            console.log('Loading dashboard data');

            // Load dashboard statistics
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_dashboard_stats'
                })
                .then(response => {
                    console.log('Dashboard stats response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Dashboard stats data:', data);
                    if (data.success) {
                        updateDashboardStats(data.data);
                    } else {
                        console.error('Dashboard stats failed:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading dashboard stats:', error);
                });

            // Load recent applications and notifications
            loadRecentApplications();
            loadNotifications();
        }

        function updateDashboardStats(stats) {
            const elements = {
                totalApplications: document.getElementById('totalApplications'),
                pendingApplications: document.getElementById('pendingApplications'),
                approvedApplications: document.getElementById('approvedApplications'),
                documentsCount: document.getElementById('documentsCount')
            };

            if (elements.totalApplications) elements.totalApplications.textContent = stats.total_applications || 0;
            if (elements.pendingApplications) elements.pendingApplications.textContent = stats.pending_applications || 0;
            if (elements.approvedApplications) elements.approvedApplications.textContent = stats.approved_applications || 0;
            if (elements.documentsCount) elements.documentsCount.textContent = stats.documents_count || 0;
        }

        function loadRecentApplications() {
            console.log('Loading recent applications');

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_applications&limit=5'
                })
                .then(response => {
                    console.log('Recent apps response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Recent apps data:', data);
                    if (data.success) {
                        renderRecentApplications(data.data);
                    } else {
                        console.error('Recent apps failed:', data.message);
                        const container = document.getElementById('recentApplicationsList');
                        if (container) {
                            container.innerHTML = '<div class="text-center py-3 text-muted">Unable to load applications</div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading recent applications:', error);
                    const container = document.getElementById('recentApplicationsList');
                    if (container) {
                        container.innerHTML = '<div class="text-center py-3 text-danger">Error loading applications</div>';
                    }
                });
        }

        function renderRecentApplications(applications) {
            const container = document.getElementById('recentApplicationsList');
            if (!container) return;

            if (!applications || applications.length === 0) {
                container.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No applications yet</p>
                <button class="btn btn-primary btn-sm mt-2" onclick="showNewApplicationModal()">
                    Create First Application
                </button>
            </div>
        `;
                return;
            }

            container.innerHTML = applications.map(app => `
        <div class="application-card">
            <div class="d-flex justify-content-between align-items-start p-3">
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">${app.application_number || 'N/A'}</h6>
                    <p class="text-muted mb-2">${app.program || 'N/A'}</p>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        ${formatDate(app.created_at || app.received)}
                    </small>
                </div>
                <span class="status-badge bg-${getStatusColor(app.status)} text-white">
                    ${(app.status || 'submitted').replace('-', ' ').toUpperCase()}
                </span>
            </div>
        </div>
    `).join('');
        }

        function loadNotifications() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_notifications'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRecentNotifications(data.data);
                        updateNotificationCount(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }

        function renderRecentNotifications(notifications) {
            const container = document.getElementById('recentNotifications');
            if (!container) return;

            if (!notifications || notifications.length === 0) {
                container.innerHTML = '<div class="text-center py-3 text-muted">No recent updates</div>';
                return;
            }

            container.innerHTML = notifications.slice(0, 3).map(notif => `
        <div class="timeline-item">
            <strong>${notif.title}</strong>
            <div class="text-muted small">${formatDate(notif.created_at)}</div>
            <p class="mb-0 small">${notif.message}</p>
        </div>
    `).join('');
        }

        function updateNotificationCount(count) {
            const countElement = document.getElementById('notificationCount');
            if (countElement) {
                countElement.textContent = count;
                countElement.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        function markNotificationRead(notificationId) {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=mark_notification_read&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update local notification state
                        const notification = currentNotifications.find(n => n.id === notificationId);
                        if (notification && !notification.isRead) {
                            notification.isRead = true;
                            renderNotificationsDropdown(currentNotifications);

                            // Update count
                            const unreadCount = currentNotifications.filter(n => !n.isRead).length;
                            updateNotificationCount(unreadCount);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }

        function markAllNotificationsRead() {
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=mark_all_notifications_read'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update all notifications to read
                        currentNotifications.forEach(notif => {
                            notif.isRead = true;
                        });
                        renderNotificationsDropdown(currentNotifications);
                        updateNotificationCount(0);
                        showAlert('All notifications marked as read', 'success');
                    } else {
                        showAlert(data.message || 'Failed to mark notifications as read', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                    showAlert('Error marking notifications as read', 'danger');
                });
        }

        // Applications functions
        function loadApplications() {
            console.log('Loading all applications');

            const container = document.getElementById('applicationsList');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="loading-spinner"></div><span class="ms-2">Loading applications...</span></div>';
            }

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_applications'
                })
                .then(response => {
                    console.log('Applications response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Applications data:', data);
                    if (data.success) {
                        currentApplications = data.data;
                        renderApplications(data.data);
                        populateFilterOptions(data.data);
                    } else {
                        console.error('Applications failed:', data.message);
                        if (container) {
                            container.innerHTML = '<div class="text-center py-3 text-danger">Unable to load applications</div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading applications:', error);
                    if (container) {
                        container.innerHTML = '<div class="text-center py-3 text-danger">Error loading applications</div>';
                    }
                });
        }

        function renderApplications(applications) {
            const container = document.getElementById('applicationsList');
            if (!container) return;

            if (!applications || applications.length === 0) {
                container.innerHTML = `
            <div class="card text-center">
                <div class="card-body py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5>No Applications Found</h5>
                    <p class="text-muted mb-4">You haven't submitted any applications yet.</p>
                    <button class="btn btn-primary" onclick="showNewApplicationModal()">
                        <i class="fas fa-plus me-2"></i>Create Your First Application
                    </button>
                </div>
            </div>
        `;
                return;
            }

            container.innerHTML = applications.map(app => {
                // Determine which actions are available based on status
                const editableStatuses = ['submitted', 'under-review'];
                const deletableStatuses = ['submitted'];

                const canEdit = editableStatuses.includes(app.status);
                const canDelete = deletableStatuses.includes(app.status);

                return `
        <div class="application-card">
            <div class="row align-items-center p-3">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1">${app.application_number || 'N/A'}</h5>
                    <h6 class="text-primary mb-2">${app.program || 'N/A'}</h6>
                    <div class="row small text-muted">
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-graduation-cap me-2"></i>Level: ${app.program_level || 'N/A'}</p>
                            <p class="mb-1"><i class="fas fa-clock me-2"></i>Type: ${app.enrollment_type || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-calendar me-2"></i>Term: ${app.start_term || 'N/A'}</p>
                            <p class="mb-1"><i class="fas fa-edit me-2"></i>Updated: ${formatDate(app.updated_at)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="status-badge bg-${getStatusColor(app.status)} text-white d-inline-block mb-3">
                        ${(app.status || 'submitted').replace('-', ' ').toUpperCase()}
                    </span>
                    <div class="application-actions">
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="viewApplication(${app.personal_id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn ${canEdit ? 'btn-outline-success' : 'btn-outline-secondary'} btn-sm" 
                                onclick="${canEdit ? `editApplication(${app.personal_id})` : 'void(0)'}" 
                                title="${canEdit ? 'Edit Application' : 'Cannot edit - ' + app.status}"
                                ${!canEdit ? 'disabled' : ''}>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn ${canDelete ? 'btn-outline-danger' : 'btn-outline-secondary'} btn-sm" 
                                onclick="${canDelete ? `deleteApplication(${app.personal_id}, '${app.application_number}')` : 'void(0)'}" 
                                title="${canDelete ? 'Delete Application' : 'Cannot delete - ' + app.status}"
                                ${!canDelete ? 'disabled' : ''}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
            }).join('');
        }

        function populateFilterOptions(applications) {
            const programSelect = document.getElementById('programFilterSelect');
            if (programSelect && applications) {
                const programs = [...new Set(applications.map(app => app.program).filter(p => p))];
                programSelect.innerHTML = '<option value="">All Programs</option>' +
                    programs.map(program => `<option value="${program}">${program}</option>`).join('');
            }
        }

        function applyFilters() {
            const searchInput = document.getElementById('applicationSearchInput');
            const statusSelect = document.getElementById('statusFilterSelect');
            const programSelect = document.getElementById('programFilterSelect');

            if (!searchInput || !statusSelect || !programSelect) return;

            const search = searchInput.value.toLowerCase();
            const status = statusSelect.value;
            const program = programSelect.value;

            const filteredApps = currentApplications.filter(app => {
                const matchesSearch = !search ||
                    (app.application_number && app.application_number.toLowerCase().includes(search)) ||
                    (app.program && app.program.toLowerCase().includes(search));
                const matchesStatus = !status || app.status === status;
                const matchesProgram = !program || app.program === program;

                return matchesSearch && matchesStatus && matchesProgram;
            });

            renderApplications(filteredApps);
        }

        // Multi-step form functions
        function showNewApplicationModal() {
            resetApplicationForm();
            const modal = new bootstrap.Modal(document.getElementById('newApplicationModal'));
            modal.show();
        }

        function resetApplicationForm() {
            currentStep = 1;
            uploadedFiles = {};

            const form = document.getElementById('newApplicationForm');
            if (form) {
                form.reset();

                // Clear validation states
                form.querySelectorAll('.is-invalid, .is-valid').forEach(field => {
                    field.classList.remove('is-invalid', 'is-valid');
                });

                form.querySelectorAll('.invalid-feedback').forEach(feedback => {
                    feedback.textContent = '';
                });
            }

            // Reset scholarship toggle
            const scholarshipTypeDiv = document.getElementById('scholarshipTypeDiv');
            if (scholarshipTypeDiv) {
                scholarshipTypeDiv.style.display = 'none';
            }

            updateStepDisplay();
            updateDocumentStatus();
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }

        function updateStepDisplay() {
            // Update step indicators
            document.querySelectorAll('.step').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    step.classList.add('active');
                } else if (index + 1 < currentStep) {
                    step.classList.add('completed');
                }
            });

            // Update form steps
            document.querySelectorAll('.form-step').forEach((step, index) => {
                step.classList.remove('active');
                if (index + 1 === currentStep) {
                    step.classList.add('active');
                }
            });

            // Update buttons
            const prevBtn = document.getElementById('prevStepBtn');
            const nextBtn = document.getElementById('nextStepBtn');
            const submitBtn = document.getElementById('submitApplicationBtn');

            if (prevBtn) prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
            if (nextBtn) nextBtn.style.display = currentStep < totalSteps ? 'inline-block' : 'none';
            if (submitBtn) submitBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';

            // Generate review if on last step
            if (currentStep === totalSteps) {
                generateApplicationReview();
            }
        }

        function validateCurrentStep() {
            const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            if (!currentStepElement) return true;

            const fields = currentStepElement.querySelectorAll('[data-required="true"], input[type="file"][data-required="true"]');
            let isValid = true;

            fields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            // Special validation for document upload step
            if (currentStep === 4) {
                isValid = validateDocuments() && isValid;
            }

            return isValid;
        }

        function validateAllSteps() {
            let allValid = true;

            for (let step = 1; step <= totalSteps - 1; step++) { // Exclude review step
                const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
                if (stepElement) {
                    const fields = stepElement.querySelectorAll('[data-required="true"], input[type="file"][data-required="true"]');
                    fields.forEach(field => {
                        if (!validateField(field)) {
                            allValid = false;
                        }
                    });
                }
            }

            return allValid;
        }

        function validateField(field) {
            if (!field) return true;

            const fieldName = field.name;
            const fieldValue = field.value ? field.value.trim() : '';
            const isRequired = field.dataset.required === 'true' || field.hasAttribute('required');
            let isValid = true;
            let errorMessage = '';

            // Clear previous validation state
            field.classList.remove('is-invalid', 'is-valid');
            const feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = '';
            }

            // Skip validation if field is not visible or disabled
            if (field.offsetParent === null || field.disabled) {
                return true;
            }

            // Check if required field is empty
            if (isRequired && !fieldValue) {
                isValid = false;
                errorMessage = `${getFieldLabel(field)} is required`;
            }
            // File validation for required files
            else if (field.type === 'file' && isRequired && field.files.length === 0) {
                isValid = false;
                errorMessage = `${getFieldLabel(field)} is required`;
            }
            // File validation for uploaded files
            else if (field.type === 'file' && field.files.length > 0) {
                const fileValidation = validateFile(field.files[0], fieldName);
                if (!fileValidation.valid) {
                    isValid = false;
                    errorMessage = fileValidation.message;
                }
            }
            // Special validation for graduation year
            else if (fieldName === 'graduation_year' && fieldValue) {
                const year = parseInt(fieldValue);
                const currentYear = new Date().getFullYear();
                if (isNaN(year) || year < 1990 || year > currentYear) {
                    isValid = false;
                    errorMessage = `Graduation year must be between 1990 and ${currentYear}`;
                }
            }
            // Validation for expected graduation year
            else if (fieldName === 'expected_graduation_year' && fieldValue) {
                const year = parseInt(fieldValue);
                const currentYear = new Date().getFullYear();
                if (isNaN(year) || year < currentYear || year > currentYear + 10) {
                    isValid = false;
                    errorMessage = `Expected graduation year must be between ${currentYear} and ${currentYear + 10}`;
                }
            }

            // Apply validation state
            if (isValid) {
                field.classList.add('is-valid');
            } else {
                field.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = errorMessage;
                }
            }

            return isValid;
        }

        function validateFile(file, fieldName) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const photoMaxSize = 2 * 1024 * 1024; // 2MB for photos

            const allowedTypes = {
                'transcript': ['application/pdf', 'image/jpeg', 'image/png'],
                'certificate': ['application/pdf', 'image/jpeg', 'image/png'],
                'identity': ['application/pdf', 'image/jpeg', 'image/png'],
                'photo': ['image/jpeg', 'image/png']
            };

            // Check file size
            const maxFileSize = fieldName === 'photo' ? photoMaxSize : maxSize;
            if (file.size > maxFileSize) {
                return {
                    valid: false,
                    message: `File size must be less than ${fieldName === 'photo' ? '2MB' : '5MB'}`
                };
            }

            // Check file type
            const allowedTypesForField = allowedTypes[fieldName] || [];
            if (allowedTypesForField.length > 0 && !allowedTypesForField.includes(file.type)) {
                return {
                    valid: false,
                    message: 'Invalid file type. Please use PDF, JPEG, or PNG format.'
                };
            }

            return {
                valid: true
            };
        }

        function validateDocuments() {
            const requiredDocuments = ['transcript', 'certificate', 'identity', 'photo'];
            let allValid = true;

            requiredDocuments.forEach(docType => {
                const fileInput = document.querySelector(`input[name="${docType}"]`);
                if (fileInput && fileInput.files.length === 0) {
                    fileInput.classList.add('is-invalid');
                    const feedback = fileInput.parentNode.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = `${getFieldLabel(fileInput)} is required`;
                    }
                    allValid = false;
                }
            });

            updateDocumentStatus();
            return allValid;
        }

        function updateDocumentStatus() {
            const statusContainer = document.getElementById('documentStatus');
            if (!statusContainer) return;

            const documentTypes = [{
                    name: 'transcript',
                    label: 'Academic Transcript',
                    required: true
                },
                {
                    name: 'certificate',
                    label: 'Certificate/Diploma',
                    required: true
                },
                {
                    name: 'identity',
                    label: 'Identity Document',
                    required: true
                },
                {
                    name: 'photo',
                    label: 'Passport Photo',
                    required: true
                },
                {
                    name: 'personal_statement',
                    label: 'Personal Statement',
                    required: false
                },
                {
                    name: 'recommendation_letter',
                    label: 'Recommendation Letter',
                    required: false
                }
            ];

            let statusHtml = '';
            documentTypes.forEach(doc => {
                const fileInput = document.querySelector(`input[name="${doc.name}"]`);
                const hasFile = fileInput && fileInput.files.length > 0;

                let statusClass = 'document-status-required';
                let statusIcon = 'fas fa-exclamation-circle text-danger';
                let statusText = 'Required - Not uploaded';

                if (hasFile) {
                    statusClass = 'document-status-uploaded';
                    statusIcon = 'fas fa-check-circle text-success';
                    statusText = `Uploaded: ${fileInput.files[0].name}`;
                } else if (!doc.required) {
                    statusClass = 'document-status-optional';
                    statusIcon = 'fas fa-info-circle text-warning';
                    statusText = 'Optional - Not uploaded';
                }

                statusHtml += `
            <div class="document-status-item ${statusClass}">
                <div class="d-flex align-items-center">
                    <i class="${statusIcon} me-2"></i>
                    <div>
                        <strong>${doc.label}</strong>
                        <div class="small">${statusText}</div>
                    </div>
                </div>
            </div>
        `;
            });

            statusContainer.innerHTML = statusHtml;
        }

        function generateApplicationReview() {
            const form = document.getElementById('newApplicationForm');
            if (!form) return;

            const formData = new FormData(form);
            const data = {};

            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            const reviewHtml = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6><i class="fas fa-user me-2 text-primary"></i>Personal Information</h6>
                <div class="bg-light p-3 rounded">
                    <p class="mb-1"><strong>Gender:</strong> ${data.gender || 'Not specified'}</p>
                    <p class="mb-1"><strong>Nationality:</strong> ${data.nationality || 'Not specified'}</p>
                    <p class="mb-1"><strong>Address:</strong> ${data.address || 'Not specified'}</p>
                    ${data.emergency_contact_name ? `<p class="mb-0"><strong>Emergency Contact:</strong> ${data.emergency_contact_name}</p>` : ''}
                </div>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2 text-primary"></i>Academic Information</h6>
                <div class="bg-light p-3 rounded">
                    <p class="mb-1"><strong>Program:</strong> ${data.program || 'Not specified'}</p>
                    <p class="mb-1"><strong>Level:</strong> ${data.program_level || 'Not specified'}</p>
                    <p class="mb-1"><strong>Enrollment:</strong> ${data.enrollment_type || 'Not specified'}</p>
                    <p class="mb-0"><strong>Start Term:</strong> ${data.start_term || 'Not specified'}</p>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <h6><i class="fas fa-school me-2 text-primary"></i>Educational Background</h6>
                <div class="bg-light p-3 rounded">
                    <p class="mb-1"><strong>Education Level:</strong> ${data.education_level || 'Not specified'}</p>
                    <p class="mb-1"><strong>Institution:</strong> ${data.institution_name || 'Not specified'}</p>
                    <p class="mb-0"><strong>Graduation Year:</strong> ${data.graduation_year || 'Not specified'}</p>
                </div>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-file-upload me-2 text-primary"></i>Document Status</h6>
                <div class="bg-light p-3 rounded">
                    ${generateDocumentSummary()}
                </div>
            </div>
        </div>
    `;

            const reviewContainer = document.getElementById('applicationReview');
            if (reviewContainer) {
                reviewContainer.innerHTML = reviewHtml;
            }
        }

        function generateDocumentSummary() {
            const documentTypes = ['transcript', 'certificate', 'identity', 'photo'];
            let summary = '';

            documentTypes.forEach(docType => {
                const fileInput = document.querySelector(`input[name="${docType}"]`);
                const hasFile = fileInput && fileInput.files.length > 0;
                const icon = hasFile ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
                const status = hasFile ? 'Uploaded' : 'Not uploaded';
                const label = getFieldLabel(fileInput);

                summary += `<p class="mb-1"><i class="${icon} me-2"></i><strong>${label}:</strong> ${status}</p>`;
            });

            return summary;
        }

        function submitApplication() {
            const form = document.getElementById('newApplicationForm');
            const submitBtn = document.getElementById('submitApplicationBtn');

            if (!form || !submitBtn) return;

            // Show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Submitting...';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('action', 'create_application');

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Application submitted successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('newApplicationModal')).hide();

                        // Reset form
                        resetApplicationForm();

                        // Refresh data
                        if (currentSection === 'applications') {
                            loadApplications();
                        }
                        if (currentSection === 'dashboard') {
                            loadDashboardData();
                        }
                    } else {
                        showAlert(data.message || 'Failed to submit application', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error submitting application:', error);
                    showAlert('Error submitting application. Please try again.', 'danger');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        function viewAllNotifications() {
            // You can implement a full notifications page here
            showAlert('Full notifications page will be implemented', 'info');
        }

        // Form handlers
        function handleProfileUpdate(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('action', 'update_profile');

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Profile updated successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(data.message || 'Failed to update profile', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error updating profile:', error);
                    showAlert('Error updating profile', 'danger');
                });
        }

        function handlePasswordChange(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            if (newPassword !== confirmPassword) {
                showAlert('Passwords do not match', 'danger');
                return;
            }

            formData.append('action', 'change_password');

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Password changed successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                        e.target.reset();
                    } else {
                        showAlert(data.message || 'Failed to change password', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error changing password:', error);
                    showAlert('Error changing password', 'danger');
                });
        }

        // Utility functions
        function getStatusColor(status) {
            const colors = {
                'submitted': 'primary',
                'under-review': 'warning',
                'interview-scheduled': 'info',
                'approved': 'success',
                'rejected': 'danger',
                'waitlisted': 'secondary',
                'enrolled': 'success',
                'withdrawn': 'dark',
                'deleted': 'dark'
            };
            return colors[status] || 'secondary';
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function getFieldLabel(field) {
            if (!field) return 'Field';
            const label = field.parentNode.querySelector('label');
            if (label) {
                return label.textContent.replace('*', '').trim();
            }
            return field.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) return;

            const alertId = 'alert-' + Date.now();
            const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

            alertContainer.insertAdjacentHTML('beforeend', alertHtml);

            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    const bsAlert = bootstrap.Alert.getInstance(alert);
                    if (bsAlert) bsAlert.close();
                }
            }, 5000);
        }

        // Modal functions
        function showChangePasswordModal() {
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        }

        function viewApplication(applicationId) {
            console.log('Viewing application:', applicationId);

            const modal = new bootstrap.Modal(document.getElementById('viewApplicationModal'));
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
                        currentViewingApplication = data.data;
                        // UPDATED: Pass status history to render function
                        renderApplicationDetails(data.data, data.status_history || []);
                    } else {
                        showAlert(data.message || 'Failed to load application details', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error loading application details:', error);
                    showAlert('Error loading application details', 'danger');
                });
        }

        function renderApplicationDetails(appData, statusHistory = []) {
    const container = document.getElementById('applicationDetailsContent');
    const editBtn = document.getElementById('editFromDetailsBtn');

    // Show edit button if application is editable
    const editableStatuses = ['submitted', 'under-review'];
    if (editableStatuses.includes(appData.status)) {
        editBtn.style.display = 'inline-block';
        editBtn.onclick = () => {
            bootstrap.Modal.getInstance(document.getElementById('viewApplicationModal')).hide();
            editApplication(appData.personal_id);
        };
    } else {
        editBtn.style.display = 'none';
    }

    // Documents display with preview functionality
    let documentsHtml = '';
    if (appData.documents && appData.documents.length > 0) {
        documentsHtml = `
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary mb-3"><i class="fas fa-file-upload me-2"></i>Documents (${appData.documents.length})</h6>
                <div class="row">
                    ${appData.documents.map(doc => `
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

    // Status history section
    let statusHistoryHtml = '';
    if (statusHistory && statusHistory.length > 0) {
        statusHistoryHtml = `
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="text-primary mb-3"><i class="fas fa-history me-2"></i>Status History</h6>
                <div class="timeline">
                    ${statusHistory.map(history => `
                                <div class="border-bottom pb-2 mb-2">
                                    <small class="text-muted">${formatDateTime(history.change_date)}</small><br>
                                    <strong>${history.old_status || 'New'} → ${history.new_status}</strong><br>
                                    <small>by ${history.changed_by_name || 'Admin   '}</small>
                                    ${history.change_reason ? `<br><em>${history.change_reason}</em>` : ''}
                                </div>
                            `).join('')}
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
                    <tr><td><strong>Status:</strong></td><td><span class="badge bg-${getStatusColor(appData.status)}">${formatStatusText(appData.status)}</span></td></tr>
                    <tr><td><strong>Submitted:</strong></td><td>${formatDate(appData.created_at)}</td></tr>
                    <tr><td><strong>Last Updated:</strong></td><td>${formatDate(appData.updated_at)}</td></tr>
                    ${appData.notes ? `<tr><td><strong>Notes:</strong></td><td>${appData.notes}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Personal Information</h6>
                <table class="table table-sm table-borderless">
                    <tr><td><strong>Gender:</strong></td><td>${appData.gender || 'N/A'}</td></tr>
                    <tr><td><strong>Nationality:</strong></td><td>${appData.nationality || 'N/A'}</td></tr>
                    <tr><td><strong>Address:</strong></td><td>${appData.address || 'N/A'}</td></tr>
                    ${appData.emergency_contact_name ? `<tr><td><strong>Emergency Contact:</strong></td><td>${appData.emergency_contact_name}</td></tr>` : ''}
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

        ${statusHistoryHtml}
    `;

    container.innerHTML = html;
}

        function formatStatusText(status) {
            const statusNames = {
                'submitted': 'Submitted',
                'under-review': 'Under Review',
                'interview-scheduled': 'Interview Scheduled',
                'approved': 'Approved',
                'rejected': 'Rejected',
                'waitlisted': 'Waitlisted',
                'enrolled': 'Enrolled',
                'graduated': 'Graduated',
                'deleted': 'Deleted'
            };
            return statusNames[status] || status.replace('-', ' ').toUpperCase();
        }

        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
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

        function editApplication(applicationId) {
            console.log('Editing application:', applicationId);

            // Show loading state
            document.getElementById('editApplicationLoading').style.display = 'block';
            document.getElementById('editApplicationContent').style.display = 'none';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editApplicationModal'));
            modal.show();

            // Load application data
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=get_application_for_edit&application_id=${applicationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentEditingApplication = data.data;
                        populateEditForm(data.data);
                        showEditabilityStatus(data.editable, data.deletable, data.status);

                        // Hide loading, show content
                        document.getElementById('editApplicationLoading').style.display = 'none';
                        document.getElementById('editApplicationContent').style.display = 'block';
                    } else {
                        showAlert(data.message || 'Failed to load application data', 'danger');
                        modal.hide();
                    }
                })
                .catch(error => {
                    console.error('Error loading application:', error);
                    showAlert('Error loading application data', 'danger');
                    modal.hide();
                });
        }

        function viewDocument(documentId, filename, documentType, mimeType, fileSize) {
            console.log('=== VIEWING DOCUMENT ===');
            console.log('ID:', documentId, 'File:', filename, 'MIME:', mimeType);

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
                setupDocumentPreview(documentId, filename, mimeType, fileSize);
            }, {
                once: true
            });

            previewState.modalInstance.show();
        }

        function clearPreviewState() {
            console.log('Clearing preview state...');

            // Clear any existing timeouts
            if (previewState.loadTimeout) {
                clearTimeout(previewState.loadTimeout);
                previewState.loadTimeout = null;
            }

            // Reset loading flag
            previewState.isLoading = false;

            // Clear iframe completely
            const pdfFrame = document.getElementById('pdfFrame');
            if (pdfFrame) {
                // Remove all event listeners
                pdfFrame.onload = null;
                pdfFrame.onerror = null;
                pdfFrame.onabort = null;

                // Clear src to stop any loading
                pdfFrame.src = 'about:blank';

                // Reset styles
                pdfFrame.style.display = 'block';
                pdfFrame.style.width = '100%';
                pdfFrame.style.height = '100%';
                pdfFrame.style.border = 'none';
            }

            // Clear image completely
            const img = document.getElementById('previewImage');
            if (img) {
                img.onload = null;
                img.onerror = null;
                img.src = '';
                img.style.transform = 'scale(1)';
                img.onclick = null;
                img.classList.remove('preview-zoom');
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

            // Image types - more comprehensive check
            const imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if (imageTypes.includes(mimeType) || imageExtensions.includes(extension)) {
                return 'image';
            }

            // PDF types
            if (mimeType === 'application/pdf' || extension === 'pdf') {
                return 'pdf';
            }

            // Everything else is unsupported for preview
            return 'unsupported';
        }

        // Load image preview
        function loadImagePreview(documentId) {
            console.log('Loading image preview...');

            const img = document.getElementById('previewImage');
            const imageUrl = `document_viewer.php?id=${documentId}&t=${Date.now()}`;

            console.log('Image URL:', imageUrl);

            // Create a new image element for pre-loading
            const preloadImg = new Image();

            preloadImg.onload = function() {
                console.log('Image preloaded successfully');

                // Set the actual image source
                img.src = preloadImg.src;

                // Show the preview
                showPreviewState('image');

                // Add zoom functionality for large images
                if (preloadImg.naturalWidth > 800 || preloadImg.naturalHeight > 600) {
                    img.classList.add('preview-zoom');
                    img.style.cursor = 'zoom-in';
                    img.onclick = function() {
                        toggleImageZoom(img);
                    };
                }
            };

            preloadImg.onerror = function() {
                console.error('Image failed to preload');
                showPreviewState('error');
            };

            // Start preloading
            preloadImg.src = imageUrl;
        }

        // Load PDF preview
        // REPLACE these specific functions in your portal.php JavaScript section

        function loadPdfPreview(documentId) {
            console.log('Loading PDF preview for document:', documentId);

            const pdfUrl = `document_viewer.php?id=${documentId}&t=${Date.now()}`;

            // Since iframe often fails, go straight to reliable method
            showWorkingPdfPreview(pdfUrl);
        }

        function showWorkingPdfPreview(pdfUrl) {
            const pdfPreview = document.getElementById('pdfPreview');

            // Use object/embed approach which is more reliable than iframe for PDFs
            pdfPreview.innerHTML = `
        <div style="width: 100%; height: 100%; min-height: 500px; background: #f5f5f5;">
            <object data="${pdfUrl}" type="application/pdf" width="100%" height="100%" 
                    style="min-height: 500px; border: none;">
                <!-- Fallback for when object doesn't work -->
                <div class="d-flex justify-content-center align-items-center h-100" 
                     style="min-height: 500px; background: white;">
                    <div class="text-center p-4">
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
                        <small class="text-muted mt-3 d-block">
                            File: ${previewState.currentDocument?.filename || 'Document.pdf'}
                        </small>
                    </div>
                </div>
            </object>
        </div>
    `;

            // Show the container
            showPreviewState('pdf');

            // Check if object loaded after 2 seconds
            setTimeout(() => {
                const objectEl = pdfPreview.querySelector('object');
                if (objectEl) {
                    // If object is very small, it likely failed to load
                    if (objectEl.offsetHeight < 100) {
                        console.log('Object PDF loading failed, user will see fallback options');
                    } else {
                        console.log('Object PDF appears to be working');
                    }
                }
            }, 2000);
        }
















        function downloadPdf(pdfUrl, filename) {
            const downloadUrl = pdfUrl.includes('download=1') ? pdfUrl : pdfUrl + '&download=1';

            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            link.target = '_blank';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            console.log('PDF download initiated');
        }
























        // Show unsupported file preview
        function showUnsupportedPreview(filename, mimeType, fileSize) {
            console.log('Showing unsupported preview for:', filename);

            const fileInfo = document.getElementById('fileInfo');
            fileInfo.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Filename:</strong><br>
                <span class="text-muted">${filename}</span>
            </div>
            <div class="col-md-6">
                <strong>File Type:</strong><br>
                <span class="text-muted">${getFileTypeLabel(mimeType)}</span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <strong>File Size:</strong><br>
                <span class="text-muted">${formatFileSize(fileSize)}</span>
            </div>
            <div class="col-md-6">
                <strong>MIME Type:</strong><br>
                <span class="text-muted">${mimeType}</span>
            </div>
        </div>
    `;

            showPreviewState('text');
        }

        function showPreviewState(state) {
            console.log('Changing preview state to:', state);

            // Get all preview elements
            const elements = {
                loading: document.getElementById('previewLoading'),
                error: document.getElementById('previewError'),
                container: document.getElementById('previewContainer'),
                imagePreview: document.getElementById('imagePreview'),
                pdfPreview: document.getElementById('pdfPreview'),
                textPreview: document.getElementById('textPreview')
            };

            // Hide ALL elements first (including child elements)
            Object.values(elements).forEach(el => {
                if (el) {
                    el.style.display = 'none';
                    el.style.visibility = 'hidden';
                }
            });

            // Show the correct state
            switch (state) {
                case 'loading':
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
                        // First ensure container is visible
                        elements.container.style.display = 'block';
                        elements.container.style.visibility = 'visible';

                        // Then show only PDF preview
                        elements.pdfPreview.style.display = 'block';
                        elements.pdfPreview.style.visibility = 'visible';
                        elements.pdfPreview.style.minHeight = '400px';
                        elements.pdfPreview.style.height = '100%';

                        // Explicitly ensure other previews are hidden
                        if (elements.imagePreview) {
                            elements.imagePreview.style.display = 'none';
                            elements.imagePreview.style.visibility = 'hidden';
                        }
                        if (elements.textPreview) {
                            elements.textPreview.style.display = 'none';
                            elements.textPreview.style.visibility = 'hidden';
                        }
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

            // Always clear loading state when changing to non-loading states
            if (state !== 'loading') {
                previewState.isLoading = false;
                if (previewState.loadTimeout) {
                    clearTimeout(previewState.loadTimeout);
                    previewState.loadTimeout = null;
                }
            }

            return true;
        }


        // Setup download button functionality
        function setupDownloadButtons(documentId, filename) {
            const downloadUrl = `document_viewer.php?id=${documentId}&download=1`;

            // Main download button
            const downloadBtn = document.getElementById('downloadDocBtn');
            if (downloadBtn) {
                downloadBtn.onclick = function() {
                    downloadDocument(downloadUrl, filename);
                };
            }

            // Fallback download buttons
            const fallbackBtn = document.getElementById('downloadFallbackBtn');
            if (fallbackBtn) {
                fallbackBtn.onclick = function() {
                    downloadDocument(downloadUrl, filename);
                };
            }

            const textBtn = document.getElementById('downloadTextBtn');
            if (textBtn) {
                textBtn.onclick = function() {
                    downloadDocument(downloadUrl, filename);
                };
            }
        }

        // Download document
        function downloadDocument(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Toggle image zoom
        function toggleImageZoom(img) {
            if (img.style.transform === 'scale(2)') {
                img.style.transform = 'scale(1)';
                img.style.cursor = 'zoom-in';
            } else {
                img.style.transform = 'scale(2)';
                img.style.cursor = 'zoom-out';
            }
        }

        function toggleFullscreen() {
            const modal = document.getElementById('documentPreviewModal');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const icon = fullscreenBtn.querySelector('i');

            if (modal.classList.contains('fullscreen')) {
                modal.classList.remove('fullscreen');
                icon.className = 'fas fa-expand';
                fullscreenBtn.title = 'Toggle Fullscreen';
            } else {
                modal.classList.add('fullscreen');
                icon.className = 'fas fa-compress';
                fullscreenBtn.title = 'Exit Fullscreen';
            }
        }

        function getFileTypeLabel(mimeType) {
            const typeMap = {
                'application/pdf': 'PDF Document',
                'image/jpeg': 'JPEG Image',
                'image/jpg': 'JPG Image',
                'image/png': 'PNG Image',
                'image/gif': 'GIF Image',
                'image/webp': 'WebP Image',
                'application/msword': 'Word Document',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'Word Document (DOCX)',
                'text/plain': 'Text File'
            };

            return typeMap[mimeType] || mimeType || 'Unknown';
        }

        function populateEditForm(appData) {
            const form = document.getElementById('editApplicationForm');

            // Set application ID
            document.getElementById('editApplicationId').value = appData.personal_id;

            // Personal Information
            setFormValue(form, 'gender', appData.gender);
            setFormValue(form, 'nationality', appData.nationality);
            setFormValue(form, 'address', appData.address);
            setFormValue(form, 'emergency_contact_name', appData.emergency_contact_name);
            setFormValue(form, 'emergency_contact_phone', appData.emergency_contact_phone);
            setFormValue(form, 'emergency_contact_relationship', appData.emergency_contact_relationship);

            // Academic Information
            setFormValue(form, 'program', appData.program);
            setFormValue(form, 'program_level', appData.program_level);
            setFormValue(form, 'enrollment_type', appData.enrollment_type);
            setFormValue(form, 'start_term', appData.start_term);

            // FIXED: Handle graduation year properly
            if (appData.expected_graduation_year) {
                setFormValue(form, 'expected_graduation_year', appData.expected_graduation_year);
            }

            setFormValue(form, 'preferred_campus', appData.preferred_campus);

            // Scholarship information
            const scholarshipApplied = appData.scholarship_applied == 1 || appData.scholarship_applied === 'true';
            setFormValue(form, 'scholarship_applied', scholarshipApplied);
            if (scholarshipApplied) {
                document.getElementById('editScholarshipTypeDiv').style.display = 'block';
                setFormValue(form, 'scholarship_type', appData.scholarship_type);
            }

            // Educational Information
            setFormValue(form, 'education_level', appData.education_level);
            setFormValue(form, 'institution_name', appData.institution_name);

            // FIXED: Handle graduation year with validation
            if (appData.graduation_year) {
                const graduationField = form.querySelector('[name="graduation_year"]');
                if (graduationField) {
                    graduationField.value = appData.graduation_year;
                    // Remove any validation states that might cause focus issues
                    graduationField.classList.remove('is-invalid', 'is-valid');

                    // Ensure field is not disabled or hidden
                    graduationField.disabled = false;
                    graduationField.style.display = '';
                }
            }

            setFormValue(form, 'grade_type', appData.grade_type);
            setFormValue(form, 'grade_value', appData.grade_value);
            setFormValue(form, 'certificate_number', appData.certificate_number);

            // Notes
            setFormValue(form, 'notes', appData.notes);

            // Display documents if available
            if (appData.documents && appData.documents.length > 0) {
                displayEditDocuments(appData.documents);
            }
        }

        function setFormValue(form, name, value) {
            const field = form.querySelector(`[name="${name}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = value == 1 || value === true || value === 'true';
                } else {
                    field.value = value || '';
                }

                // Clear any validation states that might cause focus issues
                field.classList.remove('is-invalid', 'is-valid');

                // Ensure field is accessible
                field.disabled = false;
                field.style.display = '';

                // Remove required attribute temporarily to avoid validation conflicts
                if (field.hasAttribute('required')) {
                    field.setAttribute('data-was-required', 'true');
                }
            }
        }

        function displayEditDocuments(documents) {
            const editContent = document.getElementById('editApplicationContent');
            if (!editContent) return;

            // Remove existing documents container
            const existing = document.getElementById('editDocumentsContainer');
            if (existing) {
                existing.remove();
            }

            if (documents && documents.length > 0) {
                const docHtml = `
            <div id="editDocumentsContainer" class="mt-4">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-file-upload me-2"></i>
                    Uploaded Documents (${documents.length})
                </h6>
                <div class="row">
                    ${documents.map(doc => `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card document-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start mb-2">
                                        <div class="document-icon me-2">
                                            <i class="fas ${getDocumentIcon(doc.document_type)} fa-lg text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 min-width-0">
                                            <h6 class="card-title mb-1 text-truncate" title="${doc.original_filename}">
                                                ${doc.original_filename || 'Unknown File'}
                                            </h6>
                                            <small class="text-muted">${formatDocumentType(doc.document_type)}</small>
                                        </div>
                                    </div>
                                    
                                    <div class="document-meta mb-2">
                                        <span class="badge bg-${getVerificationStatusColor(doc.verification_status)} badge-sm">
                                            ${(doc.verification_status || 'pending').toUpperCase()}
                                        </span>
                                        <small class="text-muted d-block mt-1">${formatFileSize(doc.file_size)}</small>
                                    </div>
                                    
                                    <div class="document-actions">
                                        <button class="btn btn-outline-primary btn-sm w-100" 
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
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    To update documents, please contact support or submit a new application.
                </div>
            </div>
        `;
                editContent.insertAdjacentHTML('beforeend', docHtml);
            }
        }

        function showEditabilityStatus(editable, deletable, status) {
            const alertDiv = document.getElementById('editStatusAlert');
            const updateBtn = document.getElementById('updateApplicationBtn');

            if (!editable) {
                alertDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                This application cannot be edited because it is currently <strong>${status.replace('-', ' ').toUpperCase()}</strong>.
                You can only view the information.
            </div>
        `;
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Editing Disabled';

                // Disable all form fields
                const form = document.getElementById('editApplicationForm');
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.name !== 'application_id') {
                        input.disabled = true;
                    }
                });
            } else {
                alertDiv.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Application is <strong>${status.replace('-', ' ').toUpperCase()}</strong> and can be edited.
                Changes will be saved and may require additional review.
            </div>
        `;
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Application';
            }
        }

        function handleEditApplicationSubmit(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('updateApplicationBtn');
            const originalText = submitBtn.innerHTML;
            const form = e.target;

            // Restore required attributes before validation
            form.querySelectorAll('[data-was-required="true"]').forEach(field => {
                field.setAttribute('required', 'required');
                field.removeAttribute('data-was-required');
            });

            // Validate visible and enabled fields only
            const visibleFields = Array.from(form.querySelectorAll('input, select, textarea'))
                .filter(field => field.offsetParent !== null && !field.disabled);

            let isValid = true;
            visibleFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                // Focus on first invalid field that's visible
                const firstInvalid = form.querySelector('.is-invalid:not([disabled])');
                if (firstInvalid && firstInvalid.offsetParent !== null) {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    setTimeout(() => firstInvalid.focus(), 100);
                }
                showAlert('Please correct the highlighted errors', 'danger');
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Updating...';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('action', 'update_application_data');

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Application updated successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editApplicationModal')).hide();

                        // Refresh applications list if on applications page
                        if (currentSection === 'applications') {
                            loadApplications();
                        }

                        // Refresh dashboard if on dashboard
                        if (currentSection === 'dashboard') {
                            loadDashboardData();
                        }
                    } else {
                        showAlert(data.message || 'Failed to update application', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error updating application:', error);
                    showAlert('Error updating application. Please try again.', 'danger');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        function deleteApplication(applicationId, applicationNumber) {
            // Show confirmation dialog with specific application info
            const confirmed = confirm(
                `Are you sure you want to delete application ${applicationNumber || '#' + applicationId}?\n\n` +
                'This action cannot be undone. The application will be withdrawn and removed from your active applications.'
            );

            if (!confirmed) return;

            console.log('Deleting application:', applicationId);

            // Show loading alert
            showAlert('Deleting application...', 'info');

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete_application_request&application_id=${applicationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message || 'Application deleted successfully!', 'success');

                        // Refresh applications list if on applications page
                        if (currentSection === 'applications') {
                            loadApplications();
                        }

                        // Refresh dashboard if on dashboard
                        if (currentSection === 'dashboard') {
                            loadDashboardData();
                        }
                    } else {
                        showAlert(data.message || 'Failed to delete application', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error deleting application:', error);
                    showAlert('Error deleting application. Please try again.', 'danger');
                });
        }

        function toggleNotifications() {
            showAlert('Notification dropdown will be implemented', 'info');
        }

        function viewLoginHistory() {
            showAlert('Login history will be implemented', 'info');
        }

        function resetProfile() {
            location.reload();
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

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../index.php?logout=1';
            }
        }
    </script>
</body>

</html>