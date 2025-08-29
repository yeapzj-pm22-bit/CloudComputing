<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment Application - University of Excellence</title>
    <link rel="stylesheet" href="../css/enrollment.css">
</head>

<body>
    <div class="header-bar">
        Office of Admissions | Phone: (555) 123-4567 | Email: admissions@university.edu
    </div>

    <div class="main-header">
        <div class="university-logo">UE</div>
        <h1>University of Excellence</h1>
        <h2>Student Enrollment Application</h2>
        <div class="academic-year">Academic Year 2024-2025</div>
    </div>

    <div class="container">
        <div class="form-header">
            <h3>Application for Admission</h3>
            <div class="form-instructions">
                Please complete all sections of this application form carefully. Incomplete applications may delay the admission process.
            </div>
            <div class="required-notice">
                <strong>Important:</strong> Fields marked with an asterisk (*) are required.
            </div>
        </div>

        <form id="enrollmentForm">
            <div id="applicationForm">
                <div class="form-container">
                    <div class="section-header">I. Personal Information</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastName">Last Name <span class="required">*</span></label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                        <div class="form-group">
                            <label for="firstName">First Name <span class="required">*</span></label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer-not-to-say">Prefer not to disclose</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nationality">Nationality <span class="required">*</span></label>
                            <select id="nationality" name="nationality" required>
                                <option value="">Select Country</option>
                                <option value="Afghanistan">Afghanistan</option>
                                <option value="Albania">Albania</option>
                                <option value="Algeria">Algeria</option>
                                <option value="Andorra">Andorra</option>
                                <option value="Angola">Angola</option>
                                <option value="Argentina">Argentina</option>
                                <option value="Armenia">Armenia</option>
                                <option value="Australia">Australia</option>
                                <option value="Austria">Austria</option>
                                <option value="Azerbaijan">Azerbaijan</option>
                                <option value="Bahamas">Bahamas</option>
                                <option value="Bahrain">Bahrain</option>
                                <option value="Bangladesh">Bangladesh</option>
                                <option value="Barbados">Barbados</option>
                                <option value="Belarus">Belarus</option>
                                <option value="Belgium">Belgium</option>
                                <option value="Belize">Belize</option>
                                <option value="Benin">Benin</option>
                                <option value="Bhutan">Bhutan</option>
                                <option value="Bolivia">Bolivia</option>
                                <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                <option value="Botswana">Botswana</option>
                                <option value="Brazil">Brazil</option>
                                <option value="Brunei">Brunei</option>
                                <option value="Bulgaria">Bulgaria</option>
                                <option value="Burkina Faso">Burkina Faso</option>
                                <option value="Burundi">Burundi</option>
                                <option value="Cambodia">Cambodia</option>
                                <option value="Cameroon">Cameroon</option>
                                <option value="Canada">Canada</option>
                                <option value="Cape Verde">Cape Verde</option>
                                <option value="Central African Republic">Central African Republic</option>
                                <option value="Chad">Chad</option>
                                <option value="Chile">Chile</option>
                                <option value="China">China</option>
                                <option value="Colombia">Colombia</option>
                                <option value="Comoros">Comoros</option>
                                <option value="Congo">Congo</option>
                                <option value="Costa Rica">Costa Rica</option>
                                <option value="Croatia">Croatia</option>
                                <option value="Cuba">Cuba</option>
                                <option value="Cyprus">Cyprus</option>
                                <option value="Czech Republic">Czech Republic</option>
                                <option value="Denmark">Denmark</option>
                                <option value="Djibouti">Djibouti</option>
                                <option value="Dominica">Dominica</option>
                                <option value="Dominican Republic">Dominican Republic</option>
                                <option value="Ecuador">Ecuador</option>
                                <option value="Egypt">Egypt</option>
                                <option value="El Salvador">El Salvador</option>
                                <option value="Estonia">Estonia</option>
                                <option value="Eswatini">Eswatini</option>
                                <option value="Ethiopia">Ethiopia</option>
                                <option value="Fiji">Fiji</option>
                                <option value="Finland">Finland</option>
                                <option value="France">France</option>
                                <option value="Gabon">Gabon</option>
                                <option value="Gambia">Gambia</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Germany">Germany</option>
                                <option value="Ghana">Ghana</option>
                                <option value="Greece">Greece</option>
                                <option value="Grenada">Grenada</option>
                                <option value="Guatemala">Guatemala</option>
                                <option value="Guinea">Guinea</option>
                                <option value="Guyana">Guyana</option>
                                <option value="Haiti">Haiti</option>
                                <option value="Honduras">Honduras</option>
                                <option value="Hungary">Hungary</option>
                                <option value="Iceland">Iceland</option>
                                <option value="India">India</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Iran">Iran</option>
                                <option value="Iraq">Iraq</option>
                                <option value="Ireland">Ireland</option>
                                <option value="Israel">Israel</option>
                                <option value="Italy">Italy</option>
                                <option value="Jamaica">Jamaica</option>
                                <option value="Japan">Japan</option>
                                <option value="Jordan">Jordan</option>
                                <option value="Kazakhstan">Kazakhstan</option>
                                <option value="Kenya">Kenya</option>
                                <option value="Kiribati">Kiribati</option>
                                <option value="Kuwait">Kuwait</option>
                                <option value="Kyrgyzstan">Kyrgyzstan</option>
                                <option value="Laos">Laos</option>
                                <option value="Latvia">Latvia</option>
                                <option value="Lebanon">Lebanon</option>
                                <option value="Lesotho">Lesotho</option>
                                <option value="Liberia">Liberia</option>
                                <option value="Libya">Libya</option>
                                <option value="Lithuania">Lithuania</option>
                                <option value="Luxembourg">Luxembourg</option>
                                <option value="Madagascar">Madagascar</option>
                                <option value="Malawi">Malawi</option>
                                <option value="Malaysia">Malaysia</option>
                                <option value="Maldives">Maldives</option>
                                <option value="Mali">Mali</option>
                                <option value="Malta">Malta</option>
                                <option value="Mauritania">Mauritania</option>
                                <option value="Mauritius">Mauritius</option>
                                <option value="Mexico">Mexico</option>
                                <option value="Moldova">Moldova</option>
                                <option value="Monaco">Monaco</option>
                                <option value="Mongolia">Mongolia</option>
                                <option value="Montenegro">Montenegro</option>
                                <option value="Morocco">Morocco</option>
                                <option value="Mozambique">Mozambique</option>
                                <option value="Myanmar">Myanmar</option>
                                <option value="Namibia">Namibia</option>
                                <option value="Nepal">Nepal</option>
                                <option value="Netherlands">Netherlands</option>
                                <option value="New Zealand">New Zealand</option>
                                <option value="Nicaragua">Nicaragua</option>
                                <option value="Niger">Niger</option>
                                <option value="Nigeria">Nigeria</option>
                                <option value="North Korea">North Korea</option>
                                <option value="North Macedonia">North Macedonia</option>
                                <option value="Norway">Norway</option>
                                <option value="Oman">Oman</option>
                                <option value="Pakistan">Pakistan</option>
                                <option value="Palestine">Palestine</option>
                                <option value="Panama">Panama</option>
                                <option value="Papua New Guinea">Papua New Guinea</option>
                                <option value="Paraguay">Paraguay</option>
                                <option value="Peru">Peru</option>
                                <option value="Philippines">Philippines</option>
                                <option value="Poland">Poland</option>
                                <option value="Portugal">Portugal</option>
                                <option value="Qatar">Qatar</option>
                                <option value="Romania">Romania</option>
                                <option value="Russia">Russia</option>
                                <option value="Rwanda">Rwanda</option>
                                <option value="Saint Lucia">Saint Lucia</option>
                                <option value="Samoa">Samoa</option>
                                <option value="San Marino">San Marino</option>
                                <option value="Saudi Arabia">Saudi Arabia</option>
                                <option value="Senegal">Senegal</option>
                                <option value="Serbia">Serbia</option>
                                <option value="Seychelles">Seychelles</option>
                                <option value="Sierra Leone">Sierra Leone</option>
                                <option value="Singapore">Singapore</option>
                                <option value="Slovakia">Slovakia</option>
                                <option value="Slovenia">Slovenia</option>
                                <option value="Solomon Islands">Solomon Islands</option>
                                <option value="Somalia">Somalia</option>
                                <option value="South Africa">South Africa</option>
                                <option value="South Korea">South Korea</option>
                                <option value="Spain">Spain</option>
                                <option value="Sri Lanka">Sri Lanka</option>
                                <option value="Sudan">Sudan</option>
                                <option value="Suriname">Suriname</option>
                                <option value="Sweden">Sweden</option>
                                <option value="Switzerland">Switzerland</option>
                                <option value="Syria">Syria</option>
                                <option value="Taiwan">Taiwan</option>
                                <option value="Tajikistan">Tajikistan</option>
                                <option value="Tanzania">Tanzania</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Togo">Togo</option>
                                <option value="Tonga">Tonga</option>
                                <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                <option value="Tunisia">Tunisia</option>
                                <option value="Turkey">Turkey</option>
                                <option value="Turkmenistan">Turkmenistan</option>
                                <option value="Uganda">Uganda</option>
                                <option value="Ukraine">Ukraine</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="United States">United States</option>
                                <option value="Uruguay">Uruguay</option>
                                <option value="Uzbekistan">Uzbekistan</option>
                                <option value="Vanuatu">Vanuatu</option>
                                <option value="Vatican City">Vatican City</option>
                                <option value="Venezuela">Venezuela</option>
                                <option value="Vietnam">Vietnam</option>
                                <option value="Yemen">Yemen</option>
                                <option value="Zambia">Zambia</option>
                                <option value="Zimbabwe">Zimbabwe</option>
                            </select>
                        </div>

                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="address">Permanent Address <span class="required">*</span></label>
                            <textarea id="address" name="address" required placeholder="Street Address, City, State, ZIP Code, Country"></textarea>
                        </div>
                    </div>

                    <div class="section-header">II. Academic Information</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="program">Program of Study <span class="required">*</span></label>
                            <select id="program" name="program" required>
                                <option value="">Select Program</option>
                                <optgroup label="Undergraduate Programs">
                                    <option value="bachelor-arts">Bachelor of Arts</option>
                                    <option value="bachelor-science">Bachelor of Science</option>
                                    <option value="bachelor-engineering">Bachelor of Engineering</option>
                                    <option value="bachelor-business">Bachelor of Business Administration</option>
                                </optgroup>
                                <optgroup label="Graduate Programs">
                                    <option value="master-arts">Master of Arts</option>
                                    <option value="master-science">Master of Science</option>
                                    <option value="master-business">Master of Business Administration</option>
                                    <option value="master-engineering">Master of Engineering</option>
                                </optgroup>
                                <optgroup label="Doctoral Programs">
                                    <option value="phd">Doctor of Philosophy (PhD)</option>
                                    <option value="edd">Doctor of Education (EdD)</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="enrollmentType">Enrollment Type <span class="required">*</span></label>
                            <select id="enrollmentType" name="enrollmentType" required>
                                <option value="">Select</option>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startTerm">Intended Start Term <span class="required">*</span></label>
                            <select id="startTerm" name="startTerm" required>
                                <option value="">Select</option>
                                <option value="fall-2024">Fall 2024</option>
                                <option value="spring-2025">Spring 2025</option>
                                <option value="summer-2025">Summer 2025</option>
                                <option value="fall-2025">Fall 2025</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-header">III. Educational Background</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="highSchool">Secondary School Name <span class="required">*</span></label>
                            <input type="text" id="highSchool" name="highSchool" required>
                        </div>
                        <div class="form-group">
                            <label for="spmYear">Year of SPM Completion <span class="required">*</span></label>
                            <input type="number" id="spmYear" name="spmYear" min="2000" max="2024" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="spmSubjects">Number of SPM Subjects Taken <span class="required">*</span></label>
                            <input type="number" id="spmSubjects" name="spmSubjects" min="1" max="20" required>
                        </div>
                        <div class="form-group">
                            <label for="spmResults">SPM Results (Grades) <span class="required">*</span></label>
                            <input type="text" id="spmResults" name="spmResults" placeholder="e.g., BM-A, BI-A+, Math-A, Science-B" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="previousInstitutionsFile">Upload Previous Education Documents (if any)</label>
                            <input type="file" id="previousInstitutionsFile" name="previousInstitutionsFile" accept=".pdf,.png,.jpg,.jpeg">
                            <small>You may upload transcripts, certificates, or related documents (PDF, PNG, JPG).</small>

                            <!-- Preview Container -->
                            <div id="filePreview" style="margin-top:10px;"></div>
                        </div>
                    </div>

                    <div class="document-requirements">
                        <h4>Required Documents Checklist</h4>
                        <p style="margin-bottom: 15px; font-size: 0.9rem;">Please ensure you submit the following documents with your application:</p>
                        <ul class="document-list">
                            <li>Official high school transcript or equivalent</li>
                            <li>Official college transcripts (if applicable)</li>
                            <li>Standardized test scores (SAT, ACT, GRE, etc.)</li>
                            <li>English proficiency test scores (TOEFL/IELTS for international students)</li>
                            <li>Letters of recommendation (2-3 required)</li>
                            <li>Personal statement or essay</li>
                            <li>Copy of passport or birth certificate</li>
                            <li>Application fee payment confirmation</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="successPage" class="success-page">
                <div class="success-icon">✓</div>
                <h2 style="color: #059669; margin-bottom: 20px;">Application Successfully Submitted</h2>
                <p style="margin-bottom: 25px; font-size: 1.1rem;">
                    Thank you for your application to the University of Excellence.
                </p>
                <div class="reference-number">
                    Application Reference Number: <span id="refNumber"></span>
                </div>
                <p style="margin-bottom: 30px; color: #6b7280;">
                    Please save this reference number for your records. You will receive an email confirmation within 24 hours containing your application status and next steps.
                </p>
                <div style="margin-bottom: 20px;">
                    <strong>What happens next:</strong>
                </div>
                <ul style="text-align: left; max-width: 500px; margin: 0 auto 30px; color: #4b5563;">
                    <li>Application review by admissions committee (2-4 weeks)</li>
                    <li>Document verification and background check</li>
                    <li>Interview scheduling (if required)</li>
                    <li>Admission decision notification</li>
                </ul>
                <button type="button" class="btn btn-primary" onclick="resetForm()">Submit New Application</button>
            </div>
        </form>

        <div class="btn-section">
            <div class="application-number">
                Application ID: APP-<span id="appId"></span>
            </div>
            <div>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset Form</button>
                <button type="button" class="btn btn-primary" onclick="submitApplication()" style="margin-left: 15px;">Submit Application</button>
            </div>
        </div>

        <div class="footer-info">
            For assistance with your application, please contact the Office of Admissions:<br>
            Phone: (555) 123-4567 | Email: admissions@university.edu | Office Hours: Monday-Friday, 8:00 AM - 5:00 PM
        </div>
    </div>

    <script>
        // Generate application ID
        function generateAppId() {
            return Math.random().toString(36).substr(2, 8).toUpperCase();
        }

        // Set current date
        function setCurrentDate() {
            const today = new Date();
            const dateString = today.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('currentDate').textContent = dateString;
        }

        // Validate form
        function validateForm() {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
            document.querySelectorAll('.error-message').forEach(msg => msg.remove());

            // Required check
            requiredFields.forEach(field => {
                if (!field.value.trim() || (field.type === 'checkbox' && !field.checked)) {
                    addError(field, "This field is required");
                    isValid = false;
                }
            });

            // Email format check
            const emailField = document.getElementById('email');
            if (emailField.value && !isValidEmail(emailField.value)) {
                addError(emailField, "Please enter a valid email address");
                isValid = false;
            }

            // Name fields (letters only)
            ["firstName", "lastName", "middleName", "emergencyName"].forEach(id => {
                const field = document.getElementById(id);
                if (field && field.value && !/^[A-Za-z\s]+$/.test(field.value)) {
                    addError(field, "Only letters and spaces allowed");
                    isValid = false;
                }
            });

            // Malaysian phone number validation
            ["phone", "altPhone", "emergencyPhone"].forEach(id => {
                const field = document.getElementById(id);
                if (field && field.value) {
                    // Regex for Malaysian numbers
                    const malaysiaPhoneRegex = /^(?:\+?60|0)(1[0-9]-?\d{7,8}|[3-9]\d-?\d{6,8})$/;

                    if (!malaysiaPhoneRegex.test(field.value)) {
                        addError(field, "Enter a valid Malaysian phone number (e.g. 012-3456789 or +6012-3456789)");
                        isValid = false;
                    }
                }
            });


            // DOB validation (not future + min age 15)
            const dobField = document.getElementById('dateOfBirth');
            if (dobField.value) {
                const dob = new Date(dobField.value);
                const today = new Date();
                const minDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
                if (dob > today) {
                    addError(dobField, "Date of birth cannot be in the future");
                    isValid = false;
                } else if (dob > minDate) {
                    addError(dobField, "You must be at least 18 years old");
                    isValid = false;
                }
            }

            // SPM Year validation (2000–current year+1)
            const spmYearField = document.getElementById('spmYear');
            if (spmYearField.value) {
                const year = parseInt(spmYearField.value);
                const currentYear = new Date().getFullYear() + 1;
                if (year < 2000 || year > currentYear) {
                    addError(spmYearField, `SPM year must be between 2000 and ${currentYear}`);
                    isValid = false;
                }
            }

            // Secondary School Name (letters, spaces, and common symbols only)
            const schoolField = document.getElementById('highSchool');
            if (schoolField.value && !/^[A-Za-z\s'.&()-]+$/.test(schoolField.value)) {
                addError(schoolField, "School name can only contain letters, spaces, apostrophes, dots, hyphens, parentheses, and &");
                isValid = false;
            }


            // Year of SPM Completion (already validated, keep your existing check)

            // Number of SPM Subjects Taken (must be 1–20)
            const spmSubjectsField = document.getElementById('spmSubjects');
            if (spmSubjectsField.value) {
                const numSubjects = parseInt(spmSubjectsField.value);
                if (numSubjects < 1 || numSubjects > 20) {
                    addError(spmSubjectsField, "Number of subjects must be between 1 and 20");
                    isValid = false;
                }
            }

            // SPM Results format (e.g., BM-A, BI-A+)
            const spmResultsField = document.getElementById('spmResults');
            if (spmResultsField.value && !/^([A-Za-z\s]{2,10}-[A-D][+-]?)(,\s*[A-Za-z\s]{2,10}-[A-D][+-]?)*$/.test(spmResultsField.value)) {
                addError(spmResultsField, "Enter results like: BM-A, BI-A+, Math-B");
                isValid = false;
            }

            // File upload validation (mandatory)
            const fileField = document.getElementById('previousInstitutionsFile');
            if (fileField) {
                if (fileField.files.length === 0) {
                    addError(fileField, "You must upload a document (PDF, PNG, or JPG)");
                    isValid = false;
                } else {
                    const file = fileField.files[0];
                    const allowedTypes = ["application/pdf", "image/png", "image/jpeg"];
                    const maxSize = 5 * 1024 * 1024; // 5 MB

                    if (!allowedTypes.includes(file.type)) {
                        addError(fileField, "Only PDF, PNG, JPG files are allowed");
                        isValid = false;
                    }
                    if (file.size > maxSize) {
                        addError(fileField, "File must be smaller than 5MB");
                        isValid = false;
                    }
                }
            }


            return isValid;
        }

        function addError(field, message) {
            field.classList.add('error');
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = message;
            field.parentNode.appendChild(errorMsg);
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function submitApplication() {
            if (validateForm()) {
                const refNumber = 'UE' + new Date().getFullYear() +
                    Math.random().toString(36).substr(2, 6).toUpperCase();
                document.getElementById('refNumber').textContent = refNumber;
                document.getElementById('applicationForm').style.display = 'none';
                document.getElementById('successPage').style.display = 'block';
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                const firstError = document.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        }

        function resetForm() {
            document.getElementById('enrollmentForm').reset();
            document.getElementById('applicationForm').style.display = 'block';
            document.getElementById('successPage').style.display = 'none';
            document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
            document.querySelectorAll('.error-message').forEach(msg => msg.remove());
            document.getElementById('appId').textContent = generateAppId();
            setCurrentDate();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('appId').textContent = generateAppId();
            setCurrentDate();

            document.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        this.classList.remove('error');
                        const errorMsg = this.parentNode.querySelector('.error-message');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            });
        });

        document.getElementById('previousInstitutionsFile').addEventListener('change', function(event) {
            const filePreview = document.getElementById('filePreview');
            filePreview.innerHTML = ''; // clear previous preview
            const file = event.target.files[0];
            if (!file) return;

            const fileType = file.type;

            if (fileType.startsWith('image/')) {
                // Show image preview
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '300px';
                img.style.marginTop = '10px';
                img.style.border = '1px solid #ccc';
                img.style.borderRadius = '8px';
                filePreview.appendChild(img);
            } else if (fileType === 'application/pdf') {
                // Show PDF preview (first page in iframe)
                const iframe = document.createElement('iframe');
                iframe.src = URL.createObjectURL(file);
                iframe.width = "100%";
                iframe.height = "400px";
                iframe.style.border = "1px solid #ccc";
                iframe.style.borderRadius = "8px";
                filePreview.appendChild(iframe);
            } else {
                // Fallback for unsupported types
                const link = document.createElement('a');
                link.href = URL.createObjectURL(file);
                link.textContent = "Download File";
                link.target = "_blank";
                filePreview.appendChild(link);
            }
        });
    </script>

</body>

</html>