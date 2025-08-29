<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Application Review - University of Excellence</title>
    <link rel="stylesheet" href="../css/applicationReview.css">
</head>
<body>
    
    <div class="main-header">
        <div class="university-logo">UE</div>
        <h1>University of Excellence</h1>
        <h2>Student Application Review</h2>
        <div class="academic-year">Academic Year 2024-2025</div>
    </div>

    <div class="container">
        <div class="admin-controls">
            <div class="control-row">
                <div class="application-info">
                    <span>Application ID: <span class="application-id">APP-2024-001457</span></span>
                    <span>Received: March 15, 2024</span>
                    <span>Applicant: Sarah Marie Johnson</span>
                </div>
            </div>
            <div class="control-row">
                <div class="status-section">
                    <span style="font-weight: 600; color: #374151;">Current Status:</span>
                    <div class="current-status status-under-review" id="currentStatus">Under Review</div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-approve" onclick="updateApplicationStatus('approved', event)">APPROVE</button>
                    <button class="btn btn-pending" onclick="updateApplicationStatus('pending', event)">PENDING</button>
                    <button class="btn btn-reject" onclick="updateApplicationStatus('rejected', event)">REJECT</button>
                </div>
            </div>
            <div class="control-row">
                <div></div>
                <div class="btn-group">
                    <button class="btn btn-edit" id="editBtn" onclick="toggleEditMode()">EDIT APPLICATION</button>
                    <button class="btn btn-save" id="saveBtn" onclick="saveChanges()" style="display: none;">SAVE CHANGES</button>
                    <button class="btn btn-cancel" id="cancelBtn" onclick="cancelEdit()" style="display: none;">CANCEL</button>
                </div>
            </div>
        </div>

        <div class="form-header">
            <h3>Application Review and Verification</h3>
            <div class="review-instructions">
                Review all application information for accuracy and completeness. Use the edit function to make corrections if necessary.
            </div>
            <div class="edit-notice" id="editNotice">
                <strong>Edit Mode Active:</strong> You can now modify application fields. Click Save Changes to confirm or Cancel to discard changes.
            </div>
        </div>

        <form id="applicationForm">
            <div class="form-container">
                <div class="section-header">I. Personal Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <div class="form-display" data-field="lastName" data-type="text">Johnson</div>
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <div class="form-display" data-field="firstName" data-type="text">Sarah</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <div class="form-display" data-field="dateOfBirth" data-type="date">March 15, 2005</div>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="form-display" data-field="gender" data-type="select" data-options="Male,Female,Other,Prefer not to disclose">Female</div>
                    </div>
                    <div class="form-group">
                        <label>Nationality <span class="required">*</span></label>
                        <div class="form-display" data-field="nationality" data-type="text">United States</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <div class="form-display" data-field="email" data-type="text">sarah.johnson@email.com</div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <div class="form-display" data-field="phone" data-type="text">+1 (555) 123-4567</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Permanent Address <span class="required">*</span></label>
                        <div class="form-display" data-field="permanentAddress" data-type="textarea">123 Main Street, Hometown, TX 75001, United States</div>
                    </div>
                </div>

                <div class="section-header">II. Academic Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Program of Study <span class="required">*</span></label>
                        <div class="form-display" data-field="program" data-type="select" data-options="Bachelor of Arts,Bachelor of Science,Bachelor of Engineering,Bachelor of Business Administration,Master of Arts,Master of Science,Master of Business Administration,Master of Engineering,Doctor of Philosophy (PhD),Doctor of Education (EdD)">Bachelor of Science</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Enrollment Type <span class="required">*</span></label>
                        <div class="form-display" data-field="enrollmentType" data-type="select" data-options="Full-time,Part-time">Full-time</div>
                    </div>
                    <div class="form-group">
                        <label>Intended Start Term <span class="required">*</span></label>
                        <div class="form-display" data-field="startTerm" data-type="select" data-options="Fall 2024,Spring 2025,Summer 2025,Fall 2025">Fall 2024</div>
                    </div>
                </div>

                <div class="section-header">III. Educational Background</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>High School/Secondary School Name <span class="required">*</span></label>
                        <div class="form-display" data-field="highSchool" data-type="text">Central High School</div>
                    </div>
                    <div class="form-group">
                        <label>Year of Graduation <span class="required">*</span></label>
                        <div class="form-display" data-field="graduationYear" data-type="number">2023</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>GPA/Grade Average</label>
                        <div class="form-display" data-field="gpa" data-type="text">3.85 / 4.0</div>
                    </div>
                    <div class="form-group">
                        <label>Standardized Test Scores</label>
                        <div class="form-display" data-field="testScores" data-type="text">SAT: 1450, ACT: 32</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Previous Colleges/Universities Attended (if any)</label>
                        <div class="form-display empty" data-field="previousColleges" data-type="textarea">None - First-time college student</div>
                    </div>
                </div>

                <div class="admin-notes">
                    <h4>Admissions Review Notes</h4>
                    <textarea class="notes-textarea" id="adminNotes" placeholder="Enter review notes, comments, or recommendations here...">Excellent academic record with strong test scores. Letters of recommendation highlight leadership qualities and community service. Personal statement demonstrates clear academic goals and passion for computer science. All required documents verified and complete. Recommend approval for Fall 2024 admission.</textarea>
                </div>
            </div>
        </form>

        <div class="footer-info">
            For technical support with the review system, contact IT Support: (555) 987-6543 | itsupport@university.edu<br>
            Last Modified: <span id="lastModified">March 20, 2024 at 2:30 PM</span> by <span id="reviewerName">Dr. Amanda Richards, Admissions Officer</span>
        </div>
    </div>

    <script>
        let isEditMode = false;
        let originalData = {};

        function toggleEditMode() {
            isEditMode = !isEditMode;
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const editNotice = document.getElementById('editNotice');
            const formDisplays = document.querySelectorAll('.form-display[data-field]');

            if (isEditMode) {
                // Store original data
                formDisplays.forEach(field => {
                    const fieldName = field.getAttribute('data-field');
                    originalData[fieldName] = field.textContent.trim();
                });

                // Switch to edit mode
                editBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                editNotice.style.display = 'block';

                formDisplays.forEach(makeFieldEditable);
            } else {
                // Switch back to view mode
                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                editNotice.style.display = 'none';

                formDisplays.forEach(makeFieldReadonly);
            }
        }

        function makeFieldEditable(field) {
            const fieldType = field.getAttribute('data-type');
            const currentValue = field.textContent.trim();
            
            field.classList.add('editing');
            
            if (fieldType === 'select') {
                const options = field.getAttribute('data-options').split(',');
                const select = document.createElement('select');
                select.className = 'form-select';
                
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    if (option === currentValue) {
                        optionElement.selected = true;
                    }
                    select.appendChild(optionElement);
                });
                
                field.innerHTML = '';
                field.appendChild(select);
            } else if (fieldType === 'date') {
                const input = document.createElement('input');
                input.type = 'date';
                input.className = 'form-input';
                if (currentValue && !currentValue.includes('Same as') && !currentValue.includes('None')) {
                    const date = new Date(currentValue);
                    if (!isNaN(date.getTime())) {
                        input.value = date.toISOString().split('T')[0];
                    }
                }
                field.innerHTML = '';
                field.appendChild(input);
            } else if (fieldType === 'number') {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-input';
                input.value = currentValue === 'Same as permanent address' || currentValue.includes('None') ? '' : currentValue;
                field.innerHTML = '';
                field.appendChild(input);
            } else if (fieldType === 'textarea') {
                const textarea = document.createElement('textarea');
                textarea.className = 'form-input';
                textarea.value = currentValue === 'Same as permanent address' || currentValue.includes('None') ? '' : currentValue;
                textarea.rows = 3;
                field.innerHTML = '';
                field.appendChild(textarea);
            } else {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-input';
                input.value = currentValue === 'Same as permanent address' || currentValue.includes('None') ? '' : currentValue;
                field.innerHTML = '';
                field.appendChild(input);
            }
        }

        function makeFieldReadonly(field) {
            const input = field.querySelector('input, select, textarea');
            let value = '';
            
            if (input) {
                if (input.type === 'date') {
                    if (input.value) {
                        const date = new Date(input.value);
                        value = date.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                    }
                } else {
                    value = input.value;
                }
                
                if (!value || value.trim() === '') {
                    const fieldName = field.getAttribute('data-field');
                    if (fieldName === 'mailingAddress') {
                        value = 'Same as permanent address';
                        field.classList.add('empty');
                    } else if (fieldName === 'previousColleges') {
                        value = 'None - First-time college student';
                        field.classList.add('empty');
                    } else {
                        field.classList.add('empty');
                    }
                } else {
                    field.classList.remove('empty');
                }
                
                field.textContent = value;
            }
            
            field.classList.remove('editing');
        }

        function saveChanges() {
            const formDisplays = document.querySelectorAll('.form-display[data-field]');
            const changes = {};
            
            formDisplays.forEach(field => {
                const fieldName = field.getAttribute('data-field');
                const input = field.querySelector('input, select, textarea');
                if (input) {
                    let newValue = input.value;
                    if (input.type === 'date' && newValue) {
                        const date = new Date(newValue);
                        newValue = date.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                    }
                    
                    if (newValue !== originalData[fieldName]) {
                        changes[fieldName] = newValue;
                    }
                }
            });
            
            // Update last modified timestamp
            const now = new Date();
            const timestamp = now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('lastModified').textContent = timestamp;
            
            // Show save confirmation
            const hasChanges = Object.keys(changes).length > 0;
            const confirmationText = hasChanges 
                ? `Application updated successfully! ${Object.keys(changes).length} field(s) modified.`
                : 'No changes were made to the application.';
            
            showNotification(confirmationText, hasChanges ? '#059669' : '#856404');
            
            // Exit edit mode
            toggleEditMode();
        }

        function cancelEdit() {
            // Restore original values
            const formDisplays = document.querySelectorAll('.form-display[data-field]');
            formDisplays.forEach(field => {
                const fieldName = field.getAttribute('data-field');
                field.textContent = originalData[fieldName];
                field.classList.remove('editing');
            });
            
            showNotification('Edit cancelled. All changes have been discarded.', '#6b7280');
            
            // Exit edit mode
            toggleEditMode();
        }

        function updateApplicationStatus(status, event) {
            const statusElement = document.getElementById('currentStatus');
            const statusMap = {
                'approved': { 
                    text: 'APPROVED', 
                    color: '#059669', 
                    bgColor: '#d1f2eb',
                    borderColor: '#c3e6cb',
                    class: 'status-approved'
                },
                'rejected': { 
                    text: 'REJECTED', 
                    color: '#991b1b', 
                    bgColor: '#f8d7da',
                    borderColor: '#f5c6cb',
                    class: 'status-rejected'
                },
                'pending': { 
                    text: 'UNDER REVIEW', 
                    color: '#856404', 
                    bgColor: '#fff3cd',
                    borderColor: '#ffeaa7',
                    class: 'status-under-review'
                }
            };
            
            const info = statusMap[status];
            
            // Update status display
            statusElement.className = `current-status ${info.class}`;
            statusElement.textContent = info.text;
            
            // Add visual feedback to button
            if (event && event.target) {
                const originalBg = event.target.style.backgroundColor;
                const originalColor = event.target.style.color;
                
                event.target.style.backgroundColor = info.color;
                event.target.style.color = 'white';
                event.target.style.transform = 'scale(0.95)';
                
                setTimeout(() => {
                    event.target.style.transform = 'scale(1)';
                    setTimeout(() => {
                        event.target.style.backgroundColor = originalBg;
                        event.target.style.color = originalColor;
                    }, 200);
                }, 150);
            }
            
            // Update last modified timestamp
            const now = new Date();
            const timestamp = now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('lastModified').textContent = timestamp;
            
            // Show confirmation
            showNotification(`Application status changed to: ${info.text}`, info.color);
        }

        function showNotification(message, color) {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.backgroundColor = color;
            notification.style.color = 'white';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Hide notification
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Print functionality
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                // Exit edit mode before printing
                if (isEditMode) {
                    toggleEditMode();
                }
                setTimeout(() => {
                    window.print();
                }, 100);
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial last modified timestamp
            const now = new Date();
            const timestamp = now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Show welcome message
            showNotification('Application loaded successfully. Ready for review.', '#1e3a8a');
        });
    </script>
</body>
</html>