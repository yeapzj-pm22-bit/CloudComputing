<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University User Profile Management System</title>
    <link rel="stylesheet" href="../css/profile.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</head>
<body>
        <?php
    require '../_head.php';
    ?>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar" id="profileAvatar">JD</div>
            <h2>USER PROFILE</h2>
        </div>
        
        <div class="content">
            <!-- View Mode -->
            <div id="viewMode" class="view-mode active">
                <h3 class="section-title">Profile Information</h3>
                
                <div class="profile-info">
                    <div class="info-row">
                        <span class="info-label">First Name</span>
                        <span class="info-value" id="displayFirstName">John</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Name</span>
                        <span class="info-value" id="displayLastName">Doe</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email Address</span>
                        <span class="info-value" id="displayEmail">john.doe@university.edu</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value" id="displayDateOfBirth">January 15, 1995</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value" id="displayPhoneNumber">(555) 123-4567</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Type</span>
                        <span class="info-value" id="displayAccountType">Undergraduate Student</span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="toggleEditMode(true)">
                        EDIT PROFILE
                    </button>
                    <button type="button" class="btn btn-outline" onclick="changePassword()">
                        CHANGE PASSWORD
                    </button>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="editMode" class="edit-mode">
                <h3 class="section-title">Edit Profile Information</h3>
                
                <div class="form-section">
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="firstName" class="required">First Name</label>
                            <input type="text" id="firstName" name="firstName" value="John" required>
                            <div class="error-message">First name is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName" class="required">Last Name</label>
                            <input type="text" id="lastName" name="lastName" value="Doe" required>
                            <div class="error-message">Last name is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="required">Email Address</label>
                            <input type="email" id="email" name="email" value="john.doe@university.edu" required>
                            <div class="error-message">Email is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="dateOfBirth" class="required">Date of Birth</label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth" value="1995-01-15" required>
                            <div class="error-message">Date of Birth is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phoneNumber" class="required">Phone Number</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" value="(555) 123-4567" required>
                            <div class="error-message">Phone number is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="accountType" class="required">Account Type</label>
                            <select id="accountType" name="accountType" required>
                                <option value="">Select Account Type</option>
                                <option value="Undergraduate Student" selected>Undergraduate Student</option>
                                <option value="Graduate Student">Graduate Student</option>
                                <option value="Doctoral Student">Doctoral Student</option>
                                <option value="Faculty Member">Faculty Member</option>
                                <option value="Staff Member">Staff Member</option>
                                <option value="Alumni">Alumni</option>
                            </select>
                            <div class="error-message">Please select an account type</div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                SAVE CHANGES
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditMode(false)">
                                CANCEL
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Change Modal -->
            <div id="passwordModal" class="edit-mode">
                <h3 class="section-title">Change Password</h3>
                
                <div class="form-section">
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword" class="required">Current Password</label>
                            <div class="password-container">
                                <input type="password" id="currentPassword" name="currentPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">SHOW</button>
                            </div>
                            <div class="error-message">Current password is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="required">New Password</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">SHOW</button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                            <div class="help-text">Password must be at least 8 characters with uppercase, lowercase, number, and special character</div>
                            <div class="error-message">Password is required</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword" class="required">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" id="confirmPassword" name="confirmPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">SHOW</button>
                            </div>
                            <div class="error-message">Passwords do not match</div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                UPDATE PASSWORD
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cancelPasswordChange()">
                                CANCEL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let profileData = {
            firstName: 'John',
            lastName: 'Doe',
            email: 'john.doe@university.edu',
            dateOfBirth: '1995-01-15',
            phoneNumber: '(555) 123-4567',
            accountType: 'Undergraduate Student'
        };

        // Toggle between view and edit modes
        function toggleEditMode(isEdit) {
            const viewMode = document.getElementById('viewMode');
            const editMode = document.getElementById('editMode');
            const passwordModal = document.getElementById('passwordModal');
            
            if (isEdit) {
                viewMode.classList.remove('active');
                editMode.classList.add('active');
                passwordModal.classList.remove('active');
            } else {
                viewMode.classList.add('active');
                editMode.classList.remove('active');
                passwordModal.classList.remove('active');
            }
        }

        // Show password change form
        function changePassword() {
            const viewMode = document.getElementById('viewMode');
            const passwordModal = document.getElementById('passwordModal');
            
            viewMode.classList.remove('active');
            passwordModal.classList.add('active');
        }

        // Cancel password change
        function cancelPasswordChange() {
            toggleEditMode(false);
        }

        // Update profile avatar
        function updateAvatar() {
            const avatar = document.getElementById('profileAvatar');
            const firstName = profileData.firstName || '';
            const lastName = profileData.lastName || '';
            avatar.textContent = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        }

        // Update display values
        function updateDisplayValues() {
            document.getElementById('displayFirstName').textContent = profileData.firstName;
            document.getElementById('displayLastName').textContent = profileData.lastName;
            document.getElementById('displayEmail').textContent = profileData.email;
            
            // Format date for display
            if (profileData.dateOfBirth) {
                const date = new Date(profileData.dateOfBirth);
                document.getElementById('displayDateOfBirth').textContent = date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            document.getElementById('displayPhoneNumber').textContent = profileData.phoneNumber;
            document.getElementById('displayAccountType').textContent = profileData.accountType;
            
            updateAvatar();
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = 'HIDE';
            } else {
                field.type = 'password';
                button.textContent = 'SHOW';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            strengthBar.className = 'strength-fill';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.classList.add('strength-weak');
                    feedback = 'WEAK PASSWORD';
                    break;
                case 2:
                case 3:
                    strengthBar.classList.add('strength-fair');
                    feedback = 'FAIR PASSWORD';
                    break;
                case 4:
                    strengthBar.classList.add('strength-good');
                    feedback = 'GOOD PASSWORD';
                    break;
                case 5:
                    strengthBar.classList.add('strength-strong');
                    feedback = 'STRONG PASSWORD';
                    break;
            }
            
            strengthText.textContent = feedback;
            return strength >= 4;
        }

        // Form validation
        function validateField(field) {
            const formGroup = field.closest('.form-group');
            const errorMessage = formGroup.querySelector('.error-message');
            let isValid = true;

            // Remove previous states
            formGroup.classList.remove('error', 'success');
            errorMessage.style.display = 'none';

            // Check if required field is empty
            if (field.hasAttribute('required') && !field.value.trim()) {
                formGroup.classList.add('error');
                errorMessage.style.display = 'block';
                isValid = false;
            } 
            // Special validations
            else if (field.type === 'email' && field.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    formGroup.classList.add('error');
                    errorMessage.textContent = 'Please enter a valid email address';
                    errorMessage.style.display = 'block';
                    isValid = false;
                } else {
                    formGroup.classList.add('success');
                }
            }
            else if (field.id === 'password' && field.value) {
                if (!checkPasswordStrength(field.value)) {
                    formGroup.classList.add('error');
                    errorMessage.style.display = 'block';
                    isValid = false;
                } else {
                    formGroup.classList.add('success');
                }
            }
            else if (field.id === 'confirmPassword' && field.value) {
                const password = document.getElementById('password').value;
                if (field.value !== password) {
                    formGroup.classList.add('error');
                    errorMessage.style.display = 'block';
                    isValid = false;
                } else {
                    formGroup.classList.add('success');
                }
            }
            else if (field.value.trim()) {
                formGroup.classList.add('success');
            }

            return isValid;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateDisplayValues();

            // Profile form submission
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Update profile data
                profileData.firstName = document.getElementById('firstName').value;
                profileData.lastName = document.getElementById('lastName').value;
                profileData.email = document.getElementById('email').value;
                profileData.dateOfBirth = document.getElementById('dateOfBirth').value;
                profileData.phoneNumber = document.getElementById('phoneNumber').value;
                profileData.accountType = document.getElementById('accountType').value;

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success';
                successDiv.innerHTML = '<strong>SUCCESS:</strong> Profile information has been updated successfully.';
                
                const content = document.querySelector('.content');
                content.insertBefore(successDiv, content.firstChild);

                // Update display and switch to view mode
                updateDisplayValues();
                toggleEditMode(false);

                // Remove success message after 5 seconds
                setTimeout(() => {
                    successDiv.remove();
                }, 5000);

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // Password form submission
            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success';
                successDiv.innerHTML = '<strong>SUCCESS:</strong> Password has been changed successfully. Please log in with your new password.';
                
                const content = document.querySelector('.content');
                content.insertBefore(successDiv, content.firstChild);

                // Clear password fields and switch to view mode
                document.getElementById('currentPassword').value = '';
                document.getElementById('password').value = '';
                document.getElementById('confirmPassword').value = '';
                toggleEditMode(false);

                // Remove success message after 5 seconds
                setTimeout(() => {
                    successDiv.remove();
                }, 5000);

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // Add validation to all forms
            const allFields = document.querySelectorAll('input, select');
            allFields.forEach(field => {
                field.addEventListener('blur', () => validateField(field));
                field.addEventListener('input', () => {
                    if (field.id === 'password') {
                        checkPasswordStrength(field.value);
                    }
                    // Clear error state on input
                    const formGroup = field.closest('.form-group');
                    if (formGroup && formGroup.classList.contains('error') && field.value.trim()) {
                        validateField(field);
                    }
                });
            });

            // Phone number formatting
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 6) {
                        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                    } else if (value.length >= 3) {
                        value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
                    }
                    e.target.value = value;
                });
            });
            });

            // Set max date for birth date (must be at least 18 years old for university)
            const birthDateInput = document.getElementById('dateOfBirth');
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() - 18);
            birthDateInput.max = maxDate.toISOString().split('T')[0];

            // Email domain validation for university emails
            const emailInput = document.getElementById('email');
            emailInput.addEventListener('blur', function() {
                if (this.value && !this.value.endsWith('@university.edu')) {
                    const formGroup = this.closest('.form-group');
                    const errorMessage = formGroup.querySelector('.error-message');
                    formGroup.classList.add('error');
                    errorMessage.textContent = 'University email address required (@university.edu)';
                    errorMessage.style.display = 'block';
                }
            });

            // Form auto-save functionality (draft mode)
            let autoSaveTimer;
            const formFields = document.querySelectorAll('#profileForm input, #profileForm select');
            
            formFields.forEach(field => {
                field.addEventListener('input', function() {
                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(() => {
                        // Auto-save draft after 3 seconds of inactivity
                        console.log('Draft auto-saved at:', new Date().toLocaleTimeString());
                        // In production, this would save to server/localStorage
                    }, 3000);
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+S or Cmd+S to save profile
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    if (document.getElementById('editMode').classList.contains('active')) {
                        document.getElementById('profileForm').dispatchEvent(new Event('submit'));
                    }
                }
                
                // Escape key to cancel editing
                if (e.key === 'Escape') {
                    if (document.getElementById('editMode').classList.contains('active') || 
                        document.getElementById('passwordModal').classList.contains('active')) {
                        toggleEditMode(false);
                    }
                }
            });

            // Activity logging simulation
            function logActivity(action, details) {
                const timestamp = new Date().toISOString();
                console.log(`[${timestamp}] ACTIVITY LOG: ${action} - ${details}`);
                // In production, this would send to audit trail system
            }

            // Log profile activities
            logActivity('PROFILE_VIEW', 'User accessed profile management system');
            
            // Add activity logging to form submissions
            const originalProfileSubmit = document.getElementById('profileForm').onsubmit;
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                logActivity('PROFILE_UPDATE', 'User updated profile information');
            });

            const originalPasswordSubmit = document.getElementById('passwordForm').onsubmit;
            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                logActivity('PASSWORD_CHANGE', 'User changed account password');
            });

    </script>
</body>
</html>