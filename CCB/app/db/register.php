<?php
// db/register.php - Fixed Student Registration Handler
session_start();
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Include the enhanced system
require_once '../includes/bootstrap.php';

try {
    // Get and sanitize form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dateOfBirth = $_POST['dateOfBirth'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Server-side validation
    $errors = [];

    // Name validation - EXACT match with client-side
    if (empty($firstName)) {
        $errors['firstName'] = 'First name is required';
    } elseif (!preg_match('/^[A-Za-z\s\'-]{2,50}$/', $firstName)) {
        $errors['firstName'] = 'Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)';
    }
    
    if (empty($lastName)) {
        $errors['lastName'] = 'Last name is required';
    } elseif (!preg_match('/^[A-Za-z\s\'-]{2,50}$/', $lastName)) {
        $errors['lastName'] = 'Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)';
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Must be a valid email address';
    } else {
        // Check if email already exists
        $user = new User();
        if ($user->findByEmail($email)) {
            $errors['email'] = 'An account with this email address already exists';
        }
    }

    // Date of birth validation - EXACT match with client-side
    if (empty($dateOfBirth)) {
        $errors['dateOfBirth'] = 'Date of Birth is required';
    } else {
        $birthDate = new DateTime($dateOfBirth);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 18) {
            $errors['dateOfBirth'] = 'You must be at least 18 years old';
        }
    }

    // Phone validation - EXACT match with client-side
    if (empty($phoneNumber)) {
        $errors['phoneNumber'] = 'Phone number is required';
    } elseif (!preg_match('/^[\+]?[\d\s\-\(\)]{10,15}$/', $phoneNumber)) {
        $errors['phoneNumber'] = 'Enter a valid phone number (10-15 digits)';
    }

    // Password validation - EXACT match with client-side
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } else {
        $hasMinLength = strlen($password) >= 8;
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[@$!%*?&]/', $password); // FIXED: Match client-side exactly
        
        if (!($hasMinLength && $hasUpper && $hasLower && $hasNumber && $hasSpecial)) {
            $errors['password'] = 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character (@$!%*?&)';
        }
    }

    // Confirm password validation
    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = 'Please confirm your password';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the errors above',
            'errors' => $errors
        ]);
        exit;
    }

    // Create the user account
    $user = new User();
    
    $userData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'date_of_birth' => $dateOfBirth,
        'phone_number' => $phoneNumber,
        'password' => $password, // Will be hashed automatically by User model
        'account_type' => 'Student',
        'is_active' => true,
        'email_verified' => false
    ];

    $result = $user->createUser($userData);

    if ($result['success']) {
        // Create welcome notification if Notification class exists
        if (class_exists('Notification')) {
            try {
                $notification = new Notification();
                $notification->createNotification(
                    $result['user_id'],
                    'Welcome to University of Excellence',
                    'Your student account has been created successfully. You can now access the student portal.',
                    'success'
                );
            } catch (Exception $e) {
                // Log notification error but don't fail registration
                error_log("Notification creation failed: " . $e->getMessage());
            }
        }

        // Log the registration
        error_log("New student registration: {$email} (ID: {$result['user_id']})");

        echo json_encode([
            'success' => true,
            'message' => 'Student account created successfully! You can now sign in with your credentials.',
            'user_id' => $result['user_id']
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    // Log the error
    error_log("Registration error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during registration. Please try again later.'
    ]);
}
?>