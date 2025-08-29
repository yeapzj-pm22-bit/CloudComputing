<?php
require 'lib.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/app.css">

    <title>Student Records Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.min.js"></script>
</head>

<body>
    <?php
    require '_head.php';
    ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-icon total">
                <i class="fas fa-users"></i>
            </div>
            <h2 class="stat-number">1,247</h2>
            <p class="stat-label">Total Students</p>
            <p class="stat-change positive">
                <i class="fas fa-arrow-up me-1"></i>+12.5% from last month
            </p>
        </div>

        <div class="stat-card pending">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <h2 class="stat-number">342</h2>
            <p class="stat-label">Pending Applications</p>
            <p class="stat-change positive">
                <i class="fas fa-arrow-up me-1"></i>+8.3% from last month
            </p>
        </div>

        <div class="stat-card approved">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="stat-number">785</h2>
            <p class="stat-label">Approved Students</p>
            <p class="stat-change positive">
                <i class="fas fa-arrow-up me-1"></i>+15.2% from last month
            </p>
        </div>

        <div class="stat-card rejected">
            <div class="stat-icon rejected">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 class="stat-number">120</h2>
            <p class="stat-label">Rejected Applications</p>
            <p class="stat-change negative">
                <i class="fas fa-arrow-down me-1"></i>-5.7% from last month
            </p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <h3 class="chart-title">
                <i class="fas fa-chart-line"></i>
                Application Trends (Last 6 Months)
            </h3>
            <canvas id="applicationChart"></canvas>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">
                <i class="fas fa-chart-pie"></i>
                Status Distribution
            </h3>
            <div class="d-flex justify-content-center">
                <canvas id="statusChart" width="300" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <div class="activity-card">
            <h3 class="chart-title">
                <i class="fas fa-history"></i>
                Recent Activity
            </h3>
            <?php
            foreach ($activities as $activity) {
                renderActivityItem(
                    $activity['iconClass'],
                    $activity['text'],
                    $activity['time'],
                    $activity['typeClass']
                );
            }
            ?>
        </div>
    </div>

    <!-- Top Courses -->
    <div class="top-courses">
        <div class="activity-card">
            <h3 class="chart-title">
                <i class="fas fa-star"></i>
                Most Popular Courses
            </h3>
            <?php
            foreach ($courses as $course) {
                renderCourseItem(
                    $course['iconClass'],
                    $course['courseName'],
                    $course['count']
                );
            }
            ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>

</html>