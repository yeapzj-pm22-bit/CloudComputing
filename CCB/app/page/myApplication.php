<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - University of Excellence</title>
    <link rel="stylesheet" href="../css/myApplication.css">

</head>
<body>
    <div class="header-bar">
        Student Portal | Academic Support: (555) 123-4567 | Email: student.services@university.edu
    </div>
    
    <div class="main-header">
        <div class="header-content">
            <div class="university-info">
                <div class="university-seal">UE</div>
                <div class="header-text">
                    <h1>University of Excellence</h1>
                    <p>Student Application Portal</p>
                </div>
            </div>
            <div class="user-info">
                <h3>John Smith</h3>
                <p>Student ID: 2024001234</p>
                <p>john.smith@university.edu</p>
                <button class="logout-btn" onclick="logout()">Sign Out</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Applications</h1>
            <p class="page-subtitle">Track and manage your university applications</p>
            
            <div class="quick-stats">
                <div class="stat-card">
                    <span class="stat-number" id="totalApps">3</span>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="pendingApps">2</span>
                    <div class="stat-label">Under Review</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="approvedApps">1</span>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="rejectedApps">0</span>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <div class="filters">
                <div class="filter-group">
                    <label>Status</label>
                    <select id="statusFilter" onchange="filterApplications()">
                        <option value="">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="under-review">Under Review</option>
                        <option value="interview-scheduled">Interview Scheduled</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="waitlisted">Waitlisted</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Program Type</label>
                    <select id="programFilter" onchange="filterApplications()">
                        <option value="">All Programs</option>
                        <option value="undergraduate">Undergraduate</option>
                        <option value="graduate">Graduate</option>
                        <option value="doctoral">Doctoral</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchInput" placeholder="Search applications..." oninput="filterApplications()">
                </div>
            </div>
            <a href="enrollment.php" class="btn btn-primary" onclick="showNewApplicationModal()">+ New Application</a>
        </div>

        <div class="applications-grid" id="applicationsGrid">
            <!-- Applications will be populated by JavaScript -->
        </div>
    </div>

    <script>
        // Sample application data
        const applications = [
            {
                id: 'APP-2024-001',
                title: 'Master of Computer Science',
                program: 'graduate',
                status: 'approved',
                submitted: '2024-01-15',
                lastUpdated: '2024-02-10',
                progress: 100,
                semester: 'Fall 2024',
                department: 'Computer Science',
                timeline: [
                    { title: 'Application Submitted', date: '2024-01-15', status: 'completed' },
                    { title: 'Documents Verified', date: '2024-01-20', status: 'completed' },
                    { title: 'Academic Review', date: '2024-01-25', status: 'completed' },
                    { title: 'Interview Completed', date: '2024-02-05', status: 'completed' },
                    { title: 'Admission Decision', date: '2024-02-10', status: 'completed' }
                ],
                documents: [
                    { name: 'Transcript', status: 'verified', icon: 'T' },
                    { name: 'Statement of Purpose', status: 'verified', icon: 'S' },
                    { name: 'Letters of Recommendation', status: 'verified', icon: 'L' },
                    { name: 'GRE Scores', status: 'verified', icon: 'G' }
                ],
                notes: 'Congratulations! You have been accepted into the Master of Computer Science program.'
            },
            {
                id: 'APP-2024-002',
                title: 'PhD in Data Science',
                program: 'doctoral',
                status: 'interview-scheduled',
                submitted: '2024-02-01',
                lastUpdated: '2024-02-20',
                progress: 75,
                semester: 'Fall 2024',
                department: 'Data Science',
                timeline: [
                    { title: 'Application Submitted', date: '2024-02-01', status: 'completed' },
                    { title: 'Documents Verified', date: '2024-02-05', status: 'completed' },
                    { title: 'Academic Review', date: '2024-02-15', status: 'completed' },
                    { title: 'Interview Scheduled', date: '2024-02-25', status: 'current' },
                    { title: 'Admission Decision', date: 'Pending', status: 'pending' }
                ],
                documents: [
                    { name: 'Transcript', status: 'verified', icon: 'T' },
                    { name: 'Research Proposal', status: 'verified', icon: 'R' },
                    { name: 'Letters of Recommendation', status: 'verified', icon: 'L' },
                    { name: 'GRE Scores', status: 'pending', icon: 'G' }
                ],
                notes: 'Your interview is scheduled for February 25, 2024 at 2:00 PM via Zoom.'
            },
            {
                id: 'APP-2024-003',
                title: 'Bachelor of Engineering',
                program: 'undergraduate',
                status: 'under-review',
                submitted: '2024-02-15',
                lastUpdated: '2024-02-22',
                progress: 40,
                semester: 'Fall 2024',
                department: 'Engineering',
                timeline: [
                    { title: 'Application Submitted', date: '2024-02-15', status: 'completed' },
                    { title: 'Documents Verified', date: '2024-02-18', status: 'completed' },
                    { title: 'Academic Review', date: 'In Progress', status: 'current' },
                    { title: 'Admission Decision', date: 'Pending', status: 'pending' }
                ],
                documents: [
                    { name: 'High School Transcript', status: 'verified', icon: 'H' },
                    { name: 'SAT Scores', status: 'verified', icon: 'S' },
                    { name: 'Personal Essay', status: 'verified', icon: 'P' },
                    { name: 'Letters of Recommendation', status: 'missing', icon: 'L' }
                ],
                notes: 'Please submit your letters of recommendation to complete your application.'
            }
        ];

        let filteredApplications = [...applications];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            renderApplications();
        });

        function updateStats() {
            const total = applications.length;
            const pending = applications.filter(app => 
                ['submitted', 'under-review', 'interview-scheduled'].includes(app.status)
            ).length;
            const approved = applications.filter(app => app.status === 'approved').length;
            const rejected = applications.filter(app => app.status === 'rejected').length;

            document.getElementById('totalApps').textContent = total;
            document.getElementById('pendingApps').textContent = pending;
            document.getElementById('approvedApps').textContent = approved;
            document.getElementById('rejectedApps').textContent = rejected;
        }

        function renderApplications() {
            const grid = document.getElementById('applicationsGrid');
            
            if (filteredApplications.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>No applications found</h3>
                        <p>No applications match your current filters.</p>
                        <button class="btn btn-primary" onclick="clearFilters()" style="margin-top: 20px;">Clear Filters</button>
                    </div>
                `;
                return;
            }

            grid.innerHTML = filteredApplications.map(app => `
                <div class="application-card">
                    <div class="application-header">
                        <div class="application-title">
                            <h3>${app.title}</h3>
                            <div class="application-meta">
                                <div class="meta-item">
                                    <strong>ID:</strong> ${app.id}
                                </div>
                                <div class="meta-item">
                                    <strong>Department:</strong> ${app.department}
                                </div>
                                <div class="meta-item">
                                    <strong>Semester:</strong> ${app.semester}
                                </div>
                                <div class="meta-item">
                                    <strong>Submitted:</strong> ${formatDate(app.submitted)}
                                </div>
                            </div>
                        </div>
                        <div class="application-status">
                            <span class="status-badge status-${app.status}">
                                ${formatStatus(app.status)}
                            </span>
                            <small style="color: #6b7280;">
                                Updated: ${formatDate(app.lastUpdated)}
                            </small>
                        </div>
                    </div>

                    <div class="application-details">
                        <div class="detail-group">
                            <h4>Program Type</h4>
                            <p>${formatProgram(app.program)}</p>
                        </div>
                        <div class="detail-group">
                            <h4>Current Stage</h4>
                            <p>${getCurrentStage(app)}</p>
                        </div>
                        <div class="detail-group">
                            <h4>Documents</h4>
                            <p>${getDocumentStatus(app.documents)}</p>
                        </div>
                    </div>

                    ${app.notes ? `
                    <div class="documents-section">
                        <div class="documents-header">
                            <h4 style="color: #374151;">Latest Update</h4>
                        </div>
                        <p style="color: #6b7280; font-size: 0.95rem; line-height: 1.6;">${app.notes}</p>
                    </div>
                    ` : ''}
                        ${app.status === 'under-review' ? `
                        <button class="btn btn-secondary" onclick="withdrawApplication('${app.id}')">
                            Withdraw
                        </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        function filterApplications() {
            const statusFilter = document.getElementById('statusFilter').value;
            const programFilter = document.getElementById('programFilter').value;
            const searchInput = document.getElementById('searchInput').value.toLowerCase();

            filteredApplications = applications.filter(app => {
                const matchesStatus = !statusFilter || app.status === statusFilter;
                const matchesProgram = !programFilter || app.program === programFilter;
                const matchesSearch = !searchInput || 
                    app.title.toLowerCase().includes(searchInput) ||
                    app.id.toLowerCase().includes(searchInput) ||
                    app.department.toLowerCase().includes(searchInput);

                return matchesStatus && matchesProgram && matchesSearch;
            });

            renderApplications();
        }

        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('programFilter').value = '';
            document.getElementById('searchInput').value = '';
            filteredApplications = [...applications];
            renderApplications();
        }

        function closeModal() {
            document.getElementById('applicationModal').style.display = 'none';
        }

        function formatDate(dateStr) {
            if (!dateStr || dateStr.toLowerCase() === 'pending' || dateStr.toLowerCase() === 'in progress') return dateStr;
            const date = new Date(dateStr);
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function formatStatus(status) {
            return status.replace(/-/g, ' ')
                         .replace(/\b\w/g, c => c.toUpperCase());
        }

        function formatProgram(program) {
            return program.charAt(0).toUpperCase() + program.slice(1);
        }

        function getCurrentStage(app) {
            const current = app.timeline.find(t => t.status === 'current');
            return current ? current.title : (app.timeline.find(t => t.status === 'pending')?.title || 'Completed');
        }

        function getDocumentStatus(docs) {
            const missing = docs.filter(d => d.status === 'missing').length;
            const pending = docs.filter(d => d.status === 'pending').length;
            if (missing > 0) return `${missing} Missing`;
            if (pending > 0) return `${pending} Pending`;
            return 'All Verified';
        }

        function downloadPDF(id) {
            alert(`Downloading application ${id} as PDF... (demo only)`);
        }

        function withdrawApplication(id) {
            const app = applications.find(a => a.id === id);
            if (app && confirm(`Are you sure you want to withdraw application ${id}?`)) {
                app.status = 'withdrawn';
                app.progress = 0;
                app.notes = 'You have withdrawn this application.';
                updateStats();
                renderApplications();
                closeModal();
            }
        }

        function logout() {
            alert('Logging out...');
            // Redirect simulation
            window.location.href = '/login.html';
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('applicationModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
