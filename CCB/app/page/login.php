<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Portal - Authentication System</title>
    <link rel="stylesheet" href="../css/login.css">
</head>

<body>
    <div class="header-bar">
        Information Technology Services | Help Desk: (555) 123-4567 | IT Support: itsupport@university.edu
    </div>

    <div class="main-header">
        <div class="university-seal">UE</div>
        <h1>University of Excellence</h1>
        <div class="subtitle">Student Information System Portal</div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="auth-container">
        <div class="left-panel">
            <div class="welcome-content">
                <h3>Welcome to the University Portal</h3>
                <p>
                    Access your academic records, course registration, financial information,
                    and university services through our secure student information system.
                </p>
                <p>
                    This portal provides 24/7 access to essential university services and
                    academic resources for current students, faculty, and staff members.
                </p>
            </div>

            <div class="university-info">
                <h4>Portal Services</h4>
                <ul class="info-list">
                    <li>Course Registration & Scheduling</li>
                    <li>Academic Records & Transcripts</li>
                    <li>Financial Aid & Billing Information</li>
                    <li>Library Resources & Research Tools</li>
                    <li>Campus Services & Support</li>
                    <li>Academic Calendar & Announcements</li>
                </ul>
            </div>

            <div class="security-notice">
                <h4>Security Guidelines</h4>
                <ul>
                    <li>Never share your login credentials</li>
                    <li>Always log out when finished</li>
                    <li>Use secure networks when accessing</li>
                    <li>Report suspicious activity immediately</li>
                </ul>
            </div>
        </div>

        <div class="right-panel">

            <div class="form-tabs">
                <button class="tab-button active" onclick="switchTab('login')">Sign In</button>
                <button class="tab-button" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <div class="form-section active" id="loginForm">
                <div class="panel-header">
                    <h2>Account Authentication</h2>
                    <p>Enter your university credentials to access the portal</p>
                </div>

                <div id="loginMessage"></div>

                <form onsubmit="handleLogin(event)" id="loginFormElement">
                    <div class="form-group">
                        <label for="loginEmail">University Email Address <span class="required-indicator">*</span></label>
                        <input type="email" id="loginEmail" name="email"
                            placeholder="username@university.edu">
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="loginPassword" name="password">
                            <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                Show
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Authenticate & Sign In
                    </button>
                </form>

                <div class="forgot-password">
                    <small style="color: #6b7280;">
                        Need technical assistance? Contact IT Help Desk at (555) 123-4567
                    </small>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="form-section" id="registerForm">
                <div class="panel-header">
                    <h2>Account Registration</h2>
                    <p>Create your university portal account</p>
                </div>

                <div id="registerMessage"></div>

                <form onsubmit="handleRegister(event)" id="registerFormElement">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name <span class="required-indicator">*</span></label>
                            <input type="text" id="firstName" name="firstName">
                            <div class="error-message" id="firstNameError"></div>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name <span class="required-indicator">*</span></label>
                            <input type="text" id="lastName" name="lastName">
                            <div class="error-message" id="lastNameError"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="registerEmail">Email Address <span class="required-indicator">*</span></label>
                        <input type="email" id="registerEmail" name="email">
                        <div class="error-message" id="emailError"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth <span class="required-indicator">*</span></label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth">
                            <div class="error-message" id="dobError"></div>
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number <span class="required-indicator">*</span></label>
                            <input type="tel" id="phoneNumber" name="phoneNumber">
                            <div class="error-message" id="phoneError"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="accountType">Account Type <span class="required-indicator">*</span></label>
                        <select id="accountType" name="accountType">
                            <option value="">Select Account Type</option>
                            <option value="undergraduate">Undergraduate Student</option>
                            <option value="graduate">Graduate Student</option>
                            <option value="doctoral">Doctoral Student</option>
                            <option value="faculty">Faculty Member</option>
                            <option value="staff">Staff Member</option>
                            <option value="alumni">Alumni</option>
                        </select>
                        <div class="error-message" id="accountTypeError"></div>
                    </div>

                    <div class="form-group">
                        <label for="registerPassword">Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="registerPassword" name="password">
                            <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">Show</button>
                        </div>
                        <small>Password must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                        <div class="error-message" id="passwordError"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password <span class="required-indicator">*</span></label>
                        <div class="input-group">
                            <input type="password" id="confirmPassword" name="confirmPassword">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">Show</button>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create University Account</button>
                </form>


                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <small style="color: #6b7280;">
                        Account creation requires verification by university administration.<br>
                        You will receive confirmation within 1-2 business days.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-info">
        <div>
            University of Excellence Information Technology Services<br>
            © 2024 University of Excellence. All rights reserved.
        </div>
        <div class="footer-links">
            <a href="#" onclick="showDocument('accessibility')">Accessibility</a>
            <a href="#" onclick="showDocument('support')">Technical Support</a>
            <a href="#" onclick="showDocument('privacy')">Privacy Policy</a>
            <a href="#" onclick="showDocument('security')">Security Information</a>
        </div>
    </div>

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

            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = 'Hide';
            } else {
                field.type = 'password';
                button.textContent = 'Show';
            }
        }

        // Form validation functions
        function validateUniversityEmail(email) {
            const universityDomain = /@university\.edu$/;
            const generalEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return generalEmail.test(email) && universityDomain.test(email);
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

            const formData = new FormData(event.target);
            const email = formData.get('email');
            const password = formData.get('password');

            clearMessages();

            // Validation
            if (!validateUniversityEmail(email)) {
                showMessage('loginMessage', 'Please enter a valid university email address (@university.edu)', 'error');
                return;
            }

            if (password.length < 3) {
                showMessage('loginMessage', 'Please enter your password.', 'error');
                return;
            }

            // Show loading
            showLoading(true);

            // Simulate authentication
            setTimeout(() => {
                showLoading(false);
                showMessage('loginMessage', 'Authentication successful. Redirecting to dashboard...', 'success');

                setTimeout(() => {
                    alert('Login successful! In a real system, you would be redirected to the student/faculty dashboard.');
                }, 2000);
            }, 3000);
        }

        // Handle registration submission
        function handleRegister(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            // Clear all previous error messages
            document.querySelectorAll(".error-message").forEach(el => el.textContent = "");

            let hasError = false;

            function validateName(name) {
                // Allows letters, spaces, hyphens, and apostrophes
                const nameRegex = /^[A-Za-z\s'-]{2,50}$/;
                return nameRegex.test(name.trim());
            }

            // First Name
            const firstName = formData.get("firstName");
            if (!firstName) {
                document.getElementById("firstNameError").textContent = "First name is required";
                hasError = true;
            } else if (!validateName(firstName)) {
                document.getElementById("firstNameError").textContent = "Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)";
                hasError = true;
            }

            // Last Name
            const lastName = formData.get("lastName");
            if (!lastName) {
                document.getElementById("lastNameError").textContent = "Last name is required";
                hasError = true;
            } else if (!validateName(lastName)) {
                document.getElementById("lastNameError").textContent = "Only letters, spaces, hyphens, and apostrophes allowed (2–50 characters)";
                hasError = true;
            }


            // Email
            function validateUniversityEmail(email) {
                // General email format + must end with @university.edu
                const regex = /^[a-zA-Z0-9._%+-]+@university\.edu$/i;
                return regex.test(email.trim());
            }

            function validateUniversityEmail(email) {
                // Accept multiple valid domains
                const allowedDomains = ["faculty.university.edu"];
                const regex = /^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+)$/i;
                const match = email.trim().match(regex);
                return match && allowedDomains.includes(match[1].toLowerCase());
            }

            const email = formData.get("email");
            if (!email) {
                document.getElementById("emailError").textContent = "Email is required";
                hasError = true;
            } else if (!validateUniversityEmail(email)) {
                document.getElementById("emailError").textContent = "Must be a valid university email (e.g., name@university.edu)";
                hasError = true;
            }


            // Date of Birth
            function isAtLeast18(dob) {
                const birthDate = new Date(dob);
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();

                // Adjust if birthday hasn't happened yet this year
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    return age - 1 >= 18;
                }
                return age >= 18;
            }

            // Date of Birth
            const dob = formData.get("dateOfBirth");
            if (!dob) {
                document.getElementById("dobError").textContent = "Date of Birth is required";
                hasError = true;
            } else if (!isAtLeast18(dob)) {
                document.getElementById("dobError").textContent = "You must be at least 18 years old";
                hasError = true;
            }


            // Phone
            function validatePhoneNumber(phone) {
                // Accepts +60XXXXXXXXX or 01XXXXXXXX
                const regex = /^(?:\+?60|0)[1-9]\d{7,9}$/;
                return regex.test(phone.trim());
            }

            // Phone
            const phone = formData.get("phoneNumber");
            if (!phone) {
                document.getElementById("phoneError").textContent = "Phone number is required";
                hasError = true;
            } else if (!validatePhoneNumber(phone)) {
                document.getElementById("phoneError").textContent = "Enter a valid Malaysian phone number (e.g., 0123456789 or +60123456789)";
                hasError = true;
            }


            // Account Type
            if (!formData.get("accountType")) {
                document.getElementById("accountTypeError").textContent = "Please select an account type";
                hasError = true;
            }

            // Password
            function validatePassword(password) {
                // At least 8 chars, one uppercase, one lowercase, one digit, one special character
                const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;
                return regex.test(password);
            }

            // Password
            const password = formData.get("password");
            if (!password) {
                document.getElementById("passwordError").textContent = "Password is required";
                hasError = true;
            } else if (!validatePassword(password)) {
                document.getElementById("passwordError").textContent =
                    "Password must be at least 8 characters, include uppercase, lowercase, number, and special character (!@#$%^&*)";
                hasError = true;
            }


            // Confirm Password
            if (formData.get("password") !== formData.get("confirmPassword")) {
                document.getElementById("confirmPasswordError").textContent = "Passwords do not match";
                hasError = true;
            }

            // Agreements
            if (!formData.get("termsAgreement")) {
                document.getElementById("termsError").textContent = "You must agree to the Terms";
                hasError = true;
            }
            if (!formData.get("ferpaConsent")) {
                document.getElementById("ferpaError").textContent = "You must consent to FERPA";
                hasError = true;
            }

            // Stop if errors exist
            if (hasError) return;

            // Show loading and simulate submission
            showLoading(true);
            setTimeout(() => {
                showLoading(false);
                alert("✅ Registration successful! Await admin approval.");
            }, 2000);
        }

        // Utility functions
        function showMessage(elementId, message, type) {
            const element = document.getElementById(elementId);
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            element.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;

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

        // Placeholder functions for document/policy links
        function showDocument(type) {
            const documents = {
                'terms': 'University Terms of Use',
                'privacy': 'Privacy Policy',
                'conduct': 'Code of Conduct',
                'accessibility': 'Accessibility Statement',
                'support': 'Technical Support Information',
                'security': 'Security Guidelines'
            };

            alert(`${documents[type]} would be displayed here in a real system.`);
        }

        function showForgotPassword() {
            alert('Password recovery system would be available here. Typically involves:\n\n1. Email verification\n2. Security questions\n3. Password reset link\n4. Contact IT Help Desk for assistance');
        }

        // Initialize form restrictions
        document.addEventListener('DOMContentLoaded', function() {
            // Set max date for date of birth
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 16, today.getMonth(), today.getDate());
            document.getElementById('dateOfBirth').max = maxDate.toISOString().split('T')[0];

            // Format phone number input
            document.getElementById('phoneNumber').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                } else if (value.length >= 3) {
                    value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
                } else if (value.length >= 1) {
                    value = value.replace(/(\d{3})/, '($1');
                }
                e.target.value = value;
            });

            // Email domain suggestion
            const emailFields = ['loginEmail', 'registerEmail'];
            emailFields.forEach(fieldId => {
                document.getElementById(fieldId).addEventListener('blur', function(e) {
                    let email = e.target.value.toLowerCase();
                    if (email && !email.includes('@university.edu') && email.includes('@')) {
                        const username = email.split('@')[0];
                        e.target.value = username + '@university.edu';
                    }
                });
            });

            // Password strength indicator for registration
            document.getElementById('registerPassword').addEventListener('input', function(e) {
                const password = e.target.value;
                const requirements = {
                    length: password.length >= 8,
                    upper: /[A-Z]/.test(password),
                    lower: /[a-z]/.test(password),
                    number: /\d/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                // You could add visual password strength indicator here
                const isValid = Object.values(requirements).every(req => req);
                e.target.style.borderColor = isValid ? '#22c55e' : '#d1d5db';
            });

            // Real-time password confirmation
            document.getElementById('confirmPassword').addEventListener('input', function(e) {
                const password = document.getElementById('registerPassword').value;
                const confirm = e.target.value;

                if (confirm && password !== confirm) {
                    e.target.style.borderColor = '#dc2626';
                } else if (confirm && password === confirm) {
                    e.target.style.borderColor = '#22c55e';
                } else {
                    e.target.style.borderColor = '#d1d5db';
                }
            });
        });

        // Security enhancements
        function preventBruteForce() {
            // In a real system, implement rate limiting
            console.log('Rate limiting and security measures would be implemented here');
        }

        function logSecurityEvent(event, details) {
            // In a real system, log security events
            console.log(`Security Event: ${event}`, details);
        }

        // Session management
        function initializeSession() {
            // In a real system, initialize secure session
            console.log('Secure session initialization');
        }

        // Accessibility enhancements
        document.addEventListener('keydown', function(e) {
            // Tab navigation improvements
            if (e.key === 'Tab' && !e.shiftKey) {
                // Enhanced tab navigation logic could go here
            }

            // Enter key handling for forms
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                const form = e.target.closest('form');
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.click();
                    }
                }
            }
        });

        // Error logging for production
        window.addEventListener('error', function(e) {
            // In production, log errors to monitoring system
            console.error('Application Error:', e.error);
        });
    </script>
</body>

</html>