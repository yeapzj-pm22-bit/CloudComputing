<?php
require '../lib.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - Records Management</title>
    <link rel="stylesheet" href="../css/studentPage.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</head>

<body>
    <?php
    require '../_head.php';
    ?>

    <!-- Student List Header Section -->
    <div class="p-4 mb-4">
        <div class="container-fluid">
            <div class="row align-items-center">

                <!-- Left Side: Icon + Title + Stats -->
                <div class="col-lg-8 col-md-7 mb-3 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <div>
                            <h2 class="mb-1 fw-bold">Student List</h2>
                            <p class="text-muted mb-0">Manage and view all student records</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="controls-section p-4 mb-4">
        <!-- Search + Action Buttons -->
        <div class="row g-3 mb-3 align-items-center">
            <div class="col-md-6">
                <div class="position-relative">
                    <input type="text" id="studentSearch" class="form-control ps-5" placeholder="Search students by name, ID, email, or course...">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y text-muted ms-3"></i>
                </div>

            </div>
            <div class="col-md-6 text-md-end">
            </div>
        </div>

        <!-- Course & Date Filters -->
        <div class="row g-3 mb-3">
            <!-- Course Dropdown -->
            <div class="col-md-2">
                <select id="courseFilter" class="form-select">
                    <option value="">All Courses</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Physics">Physics</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Astronomy">Astronomy</option>
                </select>
            </div>

            <!-- Start Date -->
            <div class="col-md-2">
                <input type="date" id="startDateFilter" class="form-control" placeholder="Start Date">
            </div>

            <!-- End Date -->
            <div class="col-md-2">
                <input type="date" id="endDateFilter" class="form-control" placeholder="End Date">
            </div>

            <!-- Apply Filter Button -->
            <div class="col-md-2 text-md-end">
                <button id="applyFiltersBtn" class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter me-2"></i> Apply
                </button>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <?php
            renderFilterButton('all', 'All', 1247, 'dark', true);
            renderFilterButton('pending', 'Pending', 342, 'warning');
            renderFilterButton('approved', 'Approved', 785, 'success');
            renderFilterButton('rejected', 'Rejected', 120, 'danger');
            renderFilterButton('waitlist', 'Waitlist', 87, 'info');
            ?>
        </div>
    </div>


    <!-- Student Grid -->
    <section class="p-4 mb-4">
        <!-- Table View -->
        <div class="table-view active" id="tableView">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th data-sort="id">Student ID <i class="fas fa-sort"></i></th>
                            <th data-sort="name">Full Name <i class="fas fa-sort"></i></th>
                            <th data-sort="email">Email <i class="fas fa-sort"></i></th>
                            <th>Phone</th>
                            <th data-sort="course">Course <i class="fas fa-sort"></i></th>
                            <th data-sort="date">Application Date <i class="fas fa-sort"></i></th>
                            <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?= renderStudentRow(
                            "STU2024001",
                            "John Michael Doe",
                            "john.doe@email.com",
                            "+60 12-345 6789",
                            "Computer Science",
                            "Aug 15, 2024",
                            "Approved",
                            "success"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024002",
                            "Alicia Tan",
                            "alicia.tan@university.edu",
                            "+60 19-876 5432",
                            "Information Technology",
                            "Aug 20, 2024",
                            "Pending",
                            "warning"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024003",
                            "Mohd Farid Rahman",
                            "farid.rahman@university.edu",
                            "+60 17-222 3344",
                            "Mechanical Engineering",
                            "Jul 30, 2024",
                            "Rejected",
                            "danger"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024004",
                            "Sophia Lim",
                            "sophia.lim@university.edu",
                            "+60 18-555 6677",
                            "Business Administration",
                            "Aug 25, 2024",
                            "Approved",
                            "success"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024005",
                            "David Wong",
                            "david.wong@university.edu",
                            "+60 16-444 7788",
                            "Computer Science",
                            "Sep 1, 2024",
                            "Waitlist",
                            "secondary"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024006",
                            "Emily Johnson",
                            "emily.johnson@university.edu",
                            "+60 13-321 6543",
                            "Psychology",
                            "Aug 10, 2024",
                            "Pending",
                            "warning"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024007",
                            "Tan Wei Jie",
                            "wei.jie@university.edu",
                            "+60 11-987 1234",
                            "Electrical Engineering",
                            "Aug 28, 2024",
                            "Approved",
                            "success"
                        ) ?>

                        <?= renderStudentRow(
                            "STU2024008",
                            "Nur Aisyah Binti Ali",
                            "aisyah.ali@university.edu",
                            "+60 12-876 1122",
                            "Medicine",
                            "Jul 25, 2024",
                            "Rejected",
                            "danger"
                        ) ?>
                    </tbody>

                </table>
            </div>
        </div>


    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById("applyFiltersBtn").addEventListener("click", function() {
            const course = document.getElementById("courseFilter").value;
            const startDateInput = document.getElementById("startDateFilter");
            const endDateInput = document.getElementById("endDateFilter");

            const startDate = startDateInput.value ? new Date(startDateInput.value) : null;
            const endDate = endDateInput.value ? new Date(endDateInput.value) : null;

            // Clear previous error styles
            startDateInput.classList.remove("is-invalid");
            endDateInput.classList.remove("is-invalid");

            // Validation: start date must be <= end date
            if (startDate && endDate && startDate > endDate) {
                startDateInput.classList.add("is-invalid");
                endDateInput.classList.add("is-invalid");
                alert("Start date cannot be later than end date.");
                return; // stop filter
            }

            // Validation: at least one filter must be applied (optional)
            if (!course && !startDate && !endDate) {
                alert("Please select at least one filter before applying.");
                return;
            }

            const rows = document.querySelectorAll("#tableView tbody tr");

            rows.forEach(row => {
                const rowCourse = row.querySelector("td:nth-child(6)")?.textContent.trim(); // Course column
                const rowDateStr = row.querySelector("td:nth-child(7)")?.textContent.trim(); // Application Date column
                const rowDate = rowDateStr ? new Date(rowDateStr) : null;

                let show = true;

                // Filter by course
                if (course && rowCourse !== course) {
                    show = false;
                }

                // Filter by date range
                if (rowDate) {
                    if (startDate && rowDate < startDate) {
                        show = false;
                    }
                    if (endDate && rowDate > endDate) {
                        show = false;
                    }
                }

                row.style.display = show ? "" : "none";
            });
        });

        // Function to filter students by status
        function filterByStatus(status) {
            const rows = document.querySelectorAll("#tableView tbody tr");

            rows.forEach(row => {
                const rowStatus = row.querySelector("td:nth-child(8)")?.textContent.trim(); // Status column
                let show = true;

                if (status !== "all" && rowStatus.toLowerCase() !== status.toLowerCase()) {
                    show = false;
                }

                row.style.display = show ? "" : "none";
            });

            // Highlight active button
            document.querySelectorAll(".filter-btn").forEach(btn => {
                btn.classList.remove("active");
            });
            document.getElementById(`filter-${status}`).classList.add("active");
        }

        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("studentSearch");
            const tableBody = document.querySelector("#tableView tbody");

            if (searchInput && tableBody) {
                searchInput.addEventListener("input", function() {
                    const query = searchInput.value.toLowerCase();
                    const rows = tableBody.querySelectorAll("tr");

                    rows.forEach(row => {
                        const id = row.querySelector("td:nth-child(2)")?.innerText.toLowerCase() || "";
                        const name = row.querySelector("td:nth-child(3)")?.innerText.toLowerCase() || "";
                        const email = row.querySelector("td:nth-child(4)")?.innerText.toLowerCase() || "";
                        const course = row.querySelector("td:nth-child(6)")?.innerText.toLowerCase() || "";

                        if (id.includes(query) || name.includes(query) || email.includes(query) || course.includes(query)) {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    });
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const table = document.querySelector("#tableView");
            const headers = table.querySelectorAll("th[data-sort]");
            let sortDirection = {};

            headers.forEach(header => {
                header.addEventListener("click", function() {
                    const sortKey = header.getAttribute("data-sort");
                    const tableBody = table.querySelector("tbody");
                    const rows = Array.from(tableBody.querySelectorAll("tr"));

                    // toggle direction
                    sortDirection[sortKey] = !sortDirection[sortKey];
                    const direction = sortDirection[sortKey] ? 1 : -1;

                    // get column index
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);

                    rows.sort((a, b) => {
                        let aText = a.children[columnIndex].innerText.trim().toLowerCase();
                        let bText = b.children[columnIndex].innerText.trim().toLowerCase();

                        // special cases
                        if (sortKey === "id") {
                            return direction * (parseInt(aText) - parseInt(bText));
                        }
                        if (sortKey === "date") {
                            return direction * (new Date(aText) - new Date(bText));
                        }

                        // default: string compare
                        return direction * aText.localeCompare(bText);
                    });

                    // re-attach rows
                    rows.forEach(row => tableBody.appendChild(row));

                    // update icon
                    headers.forEach(h => h.querySelector("i").className = "fas fa-sort"); // reset all
                    header.querySelector("i").className = sortDirection[sortKey] ? "fas fa-sort-up" : "fas fa-sort-down";
                });
            });
        });
    </script>

</body>

</html>