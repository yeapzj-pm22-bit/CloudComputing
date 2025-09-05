<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// Add this at the very top of your index.php file
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Only process if it's a POST request (login submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    // Include your enhanced system
    require_once 'includes/bootstrap.php';

    header('Content-Type: application/json');

    try {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Basic validation
        if (empty($email) || empty($password)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]);
            exit;
        }

        // Validate email format
        if (!ValidationHelper::validateEmail($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email format'
            ]);
            exit;
        }

        // Authenticate user
        $user = new User();
        $authResult = $user->verifyPasswordByEmail($email, $password);

        // Add this debugging code temporarily
error_log("Login attempt for email: $email");
error_log("Password provided: $password");

// Test finding user manually
$testUser = $user->findByEmail($email);
if ($testUser) {
    error_log("User found: " . $testUser['first_name']);
    error_log("Stored hash: " . $testUser['password_hash']);
    error_log("Password verify result: " . (password_verify($password, $testUser['password_hash']) ? 'TRUE' : 'FALSE'));
} else {
    error_log("User not found");
}

        if ($authResult) {
            // Check if account is active
            if (!$authResult['is_active']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact administration.'
                ]);
                exit;
            }

            // Set session variables
            $_SESSION['user_id'] = $authResult['user_id'];
            $_SESSION['first_name'] = $authResult['first_name'];
            $_SESSION['last_name'] = $authResult['last_name'];
            $_SESSION['email'] = $authResult['email'];
            $_SESSION['account_type'] = $authResult['account_type'];
            $_SESSION['login_time'] = time();

            // Update last login
            $user->updateLastLogin($authResult['user_id']);

            // Determine redirect URL based on account type
            $redirectUrl = '';
            switch ($authResult['account_type']) {
                case 'Admin':
                    $redirectUrl = 'admin/adminportal.php';
                    break;
                case 'Student':
                default:
                    $redirectUrl = 'student/portal.php';
                    break;
            }

            // Create login notification
            $notification = new Notification();
            $notification->createNotification(
                $authResult['user_id'],
                'Login Successful',
                'You have successfully logged into the University Portal.',
                'success'
            );

            echo json_encode([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirectUrl,
                'user' => [
                    'name' => $authResult['first_name'] . ' ' . $authResult['last_name'],
                    'role' => $authResult['account_type']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during login. Please try again.'
        ]);
    }

    exit; // Important: Stop execution after handling login
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Portal - Sign In</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .university-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .university-info h1 {
            color: #1f2937;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .university-info p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Main Container */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #f9fafb;
        }

        /* Auth Card */
        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            border: 1px solid #e5e7eb;
        }

        /* Form Tabs */
        .form-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: #f3f4f6;
            border-radius: 8px;
            padding: 4px;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-button.active {
            background: #ffffff;
            color: #3b82f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Form Sections */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Info Box */
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box i {
            color: #0ea5e9;
            font-size: 1rem;
        }

        .info-box span {
            color: #0c4a6e;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            color: #374151;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .required-indicator {
            color: #ef4444;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        input.error {
            border-color: #ef4444;
            /* Red for errors */
        }

        input:valid:not(:focus):not([value=""]) {
            border-color: #10b981;
        }

        /* Input Group (for password with toggle) */
        .input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: #3b82f6;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 0.875rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Alerts */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Error Messages */
        .error-message {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            min-height: 1rem;
        }

        /* Help Text */
        small {
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }

        /* Loading Overlay */
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

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Footer */
        .footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 1.5rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .footer-links {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: #2563eb;
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .main-container {
                padding: 1rem;
            }

            .auth-card {
                padding: 2rem 1.5rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .header-content {
                padding: 0 1rem;
            }

            .university-info h1 {
                font-size: 1.5rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus visible for keyboard navigation */
        .tab-button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        .btn:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="university-logo">UE</div>
            <div class="university-info">
                <h1>University of Excellence</h1>
                <p>Student Information System Portal</p>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <div class="auth-card">
            <!-- Form Tabs -->
            <div class="form-tabs">
                <button class="tab-button active" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                <button class="tab-button" onclick="switchTab('register')">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </div>

            <!-- Login Form -->
            <div class="form-section active" id="loginForm">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Enter your credentials to access the portal</p>
                </div>

                <div id="loginMessage"></div>

                <form onsubmit="handleLogin(event)" id="loginFormElement">
                    <div class="form-group">
                        <label for="loginEmail">Email Address <span class="required-indicator">*</span></label>
                        <input type="email" id="loginEmail" name="email" placeholder="your.email@university.edu" required>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In to Portal
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1.5rem;">
                    <small style="color: #6b7280;">
                        <i class="fas fa-info-circle"></i>
                        Need help? Contact IT Support at (555) 123-4567
                    </small>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="form-section" id="registerForm">
                <div class="form-header">
                    <h2>Create Student Account</h2>
                    <p>Register as a new student at University of Excellence</p>
                </div>

                <!-- Student Account Info Box -->
                <div class="info-box">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Registration - Admin and staff accounts are created by university administration</span>
                </div>

                <div id="registerMessage"></div>

                <form onsubmit="handleRegister(event)" id="registerFormElement">
                    <!-- Personal Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name <span class="required-indicator">*</span></label>
                            <input type="text" id="firstName" name="firstName" placeholder="John" required>
                            <div class="error-message" id="firstNameError"></div>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name <span class="required-indicator">*</span></label>
                            <input type="text" id="lastName" name="lastName" placeholder="Doe" required>
                            <div class="error-message" id="lastNameError"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="registerEmail">Email Address <span class="required-indicator">*</span></label>
                        <input type="email" id="registerEmail" name="email" placeholder="john.doe@email.com" required>
                        <div class="error-message" id="emailError"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth <span class="required-indicator">*</span></label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                            <div class="error-message" id="dobError"></div>
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number <span class="required-indicator">*</span></label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="+60123456789" required>
                            <div class="error-message" id="phoneError"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="registerPassword">Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="registerPassword" name="password" placeholder="Create a strong password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small>Must be 8+ characters with uppercase, lowercase, number, and special character</small>
                        <div class="error-message" id="passwordError"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-graduation-cap"></i>
                        Create Student Account
                    </button>
                </form>

                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <small style="color: #6b7280;">
                        <i class="fas fa-shield-alt"></i>
                        Student account verification required by administration (1-2 business days)
                    </small>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div>University of Excellence Information Technology Services</div>
            <div style="margin-top: 0.25rem;">© 2024 University of Excellence. All rights reserved.</div>
            <div class="footer-links">
                <a href="#"><i class="fas fa-universal-access"></i> Accessibility</a>
                <a href="#"><i class="fas fa-headset"></i> Support</a>
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
                <a href="#"><i class="fas fa-lock"></i> Security</a>
            </div>
        </div>
    </footer>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update form sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });

            if (tabName === 'login') {
                document.getElementById('loginForm').classList.add('active');
            } else {
                document.getElementById('registerForm').classList.add('active');
            }

            clearMessages();
        }

        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Form validation functions
        function validateUniversityEmail(email) {
            const generalEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return generalEmail.test(email);
        }

        function validatePassword(password) {
            const minLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            return minLength && hasUpper && hasLower && hasNumber && hasSpecial;
        }

        // Handle login submission
        function handleLogin(event) {
            event.preventDefault();

            const form = document.getElementById("loginFormElement");
            const formData = new FormData(form);

            showLoading(true);

            fetch("index.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    showLoading(false);
                    if (data.success) {
                        showMessage("loginMessage", data.message, "success");
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        showMessage("loginMessage", data.message, "error");
                    }
                })
                .catch(err => {
                    showLoading(false);
                    console.error("Login error:", err);
                    showMessage("loginMessage", "Server error, please try again later.", "error");
                });
        }

        // Handle registration submission
        function handleRegister(event) {
            event.preventDefault();

            const formData = new FormData(event.target);

            // Clear all previous error messages and styling
            document.querySelectorAll(".error-message").forEach(el => {
                el.textContent = "";
            });
            document.querySelectorAll("input").forEach(input => {
                input.classList.remove('error', 'valid');
            });

            let hasError = false;

            // Client-side validation (must match server-side exactly)
            function validateName(name) {
                const nameRegex = /^[A-Za-z\s\'-]{2,50}$/;
                return nameRegex.test(name.trim());
            }

            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function validatePhone(phone) {
                // Match server-side validation more closely
                return /^[\+]?[\d\s\-\(\)]{10,15}$/.test(phone.trim());
            }

            function validatePassword(password) {
                const minLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                // FIXED: Match server-side special characters exactly
                const hasSpecial = /[@$!%*?&]/.test(password);

                return minLength && hasUpper && hasLower && hasNumber && hasSpecial;
            }

            function isAtLeast18(dob) {
                const birthDate = new Date(dob);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 18;
            }

            function showFieldError(fieldId, errorId, message) {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(errorId);

                if (field) field.classList.add('error');
                if (errorElement) {
                    errorElement.textContent = message;
                }
                hasError = true;
            }

            // Validate all fields
            const firstName = formData.get("firstName");
            if (!firstName || !validateName(firstName)) {
                showFieldError("firstName", "firstNameError",
                    !firstName ? "First name is required" :
                    "Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)");
            }

            const lastName = formData.get("lastName");
            if (!lastName || !validateName(lastName)) {
                showFieldError("lastName", "lastNameError",
                    !lastName ? "Last name is required" :
                    "Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)");
            }

            const email = formData.get("email");
            if (!email || !validateEmail(email)) {
                showFieldError("registerEmail", "emailError",
                    !email ? "Email is required" : "Must be a valid email address");
            }

            const dob = formData.get("dateOfBirth");
            if (!dob || !isAtLeast18(dob)) {
                showFieldError("dateOfBirth", "dobError",
                    !dob ? "Date of Birth is required" : "You must be at least 18 years old");
            }

            const phone = formData.get("phoneNumber");
            if (!phone || !validatePhone(phone)) {
                showFieldError("phoneNumber", "phoneError",
                    !phone ? "Phone number is required" : "Enter a valid phone number (10-15 digits)");
            }

            const password = formData.get("password");
            if (!password || !validatePassword(password)) {
                showFieldError("registerPassword", "passwordError",
                    !password ? "Password is required" :
                    "Password must be at least 8 characters, include uppercase, lowercase, number, and special character (@$!%*?&)");
            }

            const confirmPassword = formData.get("confirmPassword");
            if (password !== confirmPassword) {
                showFieldError("confirmPassword", "confirmPasswordError", "Passwords do not match");
            }

            // If client-side validation fails, stop here
            if (hasError) {
                showMessage("registerMessage", "Please correct the errors above", "error");
                return;
            }

            // Submit to server
            showLoading(true);
            fetch("db/register.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);

                    if (data.success) {
                        showMessage("registerMessage", data.message, "success");
                        event.target.reset();
                        // Clear all validation styling
                        document.querySelectorAll("input").forEach(input => {
                            input.classList.remove('error', 'valid');
                        });
                    } else {
                        // FIXED: Handle individual field errors from server
                        if (data.errors) {
                            // Show server-side field errors
                            const fieldMapping = {
                                'firstName': ['firstName', 'firstNameError'],
                                'lastName': ['lastName', 'lastNameError'],
                                'email': ['registerEmail', 'emailError'],
                                'dateOfBirth': ['dateOfBirth', 'dobError'],
                                'phoneNumber': ['phoneNumber', 'phoneError'],
                                'password': ['registerPassword', 'passwordError'],
                                'confirmPassword': ['confirmPassword', 'confirmPasswordError']
                            };

                            Object.entries(data.errors).forEach(([field, message]) => {
                                if (fieldMapping[field]) {
                                    const [fieldId, errorId] = fieldMapping[field];
                                    const fieldElement = document.getElementById(fieldId);
                                    const errorElement = document.getElementById(errorId);

                                    if (fieldElement) fieldElement.classList.add('error');
                                    if (errorElement) errorElement.textContent = message;
                                }
                            });

                            showMessage("registerMessage", data.message, "error");
                        } else {
                            // Fallback to generic error message
                            showMessage("registerMessage", data.message || "Registration failed", "error");
                        }
                    }
                })
                .catch(error => {
                    console.error("Registration error:", error);
                    showLoading(false);
                    showMessage("registerMessage", "Something went wrong. Please try again later.", "error");
                });
        }

        // Utility functions
        function showMessage(elementId, message, type) {
            const element = document.getElementById(elementId);
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            const iconClass = type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
            element.innerHTML = `<div class="alert ${alertClass}"><i class="${iconClass}"></i> ${message}</div>`;

            // Auto-clear success messages
            if (type === 'success') {
                setTimeout(() => {
                    element.innerHTML = '';
                }, 5000);
            }
        }

        function clearMessages() {
            document.getElementById('loginMessage').innerHTML = '';
            document.getElementById('registerMessage').innerHTML = '';
        }

        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = show ? 'flex' : 'none';
        }

        // Initialize form restrictions and real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordHelp = document.querySelector('#registerPassword').nextElementSibling;
            if (passwordHelp && passwordHelp.tagName === 'SMALL') {
                passwordHelp.textContent = 'Must be 8+ characters with uppercase, lowercase, number, and special character (@$!%*?&)';
            }
            // Set max date for date of birth (minimum 18 years old)
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            document.getElementById('dateOfBirth').max = maxDate.toISOString().split('T')[0];

            // Real-time password validation
            const passwordField = document.getElementById('registerPassword');
            const confirmField = document.getElementById('confirmPassword');

            if (passwordField) {
                passwordField.addEventListener('input', function(e) {
                    const password = e.target.value;
                    const isValid = validatePassword(password);

                    e.target.classList.remove('error', 'valid');
                    if (password.length > 0) {
                        e.target.classList.add(isValid ? 'valid' : 'error');
                    }
                });
            }

            // Real-time password confirmation
            if (confirmField && passwordField) {
                confirmField.addEventListener('input', function(e) {
                    const password = passwordField.value;
                    const confirm = e.target.value;

                    if (confirm.length > 0) {
                        if (password === confirm) {
                            e.target.style.borderColor = '#10b981';
                        } else {
                            e.target.style.borderColor = '#ef4444';
                        }
                    } else {
                        e.target.style.borderColor = '#d1d5db';
                    }
                });
            }

            // Real-time email validation
            const emailFields = ['loginEmail', 'registerEmail'];
            emailFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function(e) {
                        const email = e.target.value;
                        e.target.classList.remove('error', 'valid');
                        if (email.length > 0) {
                            const isValid = validateUniversityEmail(email);
                            e.target.classList.add(isValid ? 'valid' : 'error');
                        }
                    });
                }
            });
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                const activeForm = document.querySelector('.form-section.active form');
                if (activeForm) {
                    activeForm.requestSubmit();
                }
            }
        });

        // Error logging for debugging
        window.addEventListener('error', function(e) {
            console.error('Application Error:', e.error);
        });
    </script>
</body>

</html>