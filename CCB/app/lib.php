<?php
function renderActivityItem($iconClass, $text, $time, $typeClass = '')
{
    echo '
    <div class="activity-item">
        <div class="activity-icon ' . htmlspecialchars($typeClass) . '">
            <i class="' . htmlspecialchars($iconClass) . '"></i>
        </div>
        <div class="activity-content">
            <p class="activity-text">' . htmlspecialchars($text) . '</p>
            <p class="activity-time">' . htmlspecialchars($time) . '</p>
        </div>
    </div>';
}

// Example hardcoded data (replace with DB query later if needed)
$activities = [
    [
        'iconClass' => 'fas fa-plus',
        'text' => 'New student application received: Sarah Johnson',
        'time' => '2 minutes ago',
        'typeClass' => 'add'
    ],
    [
        'iconClass' => 'fas fa-edit',
        'text' => 'Student profile updated: Michael Lee',
        'time' => '10 minutes ago',
        'typeClass' => 'edit'
    ],
    [
        'iconClass' => 'fas fa-trash',
        'text' => 'Student removed: Anna Kim',
        'time' => '30 minutes ago',
        'typeClass' => 'delete'
    ]
];

function renderCourseItem($iconClass, $courseName, $count)
{
    echo '
    <div class="course-item">
        <div class="course-info">
            <div class="course-icon">
                <i class="' . htmlspecialchars($iconClass) . '"></i>
            </div>
            <div>
                <p class="course-name">' . htmlspecialchars($courseName) . '</p>
            </div>
        </div>
        <div class="course-count">' . intval($count) . '</div>
    </div>';
}
// Example hardcoded course list (replace with DB query later)
$courses = [
    [
        'iconClass' => 'fas fa-laptop-code',
        'courseName' => 'Computer Science',
        'count' => 287
    ],
    [
        'iconClass' => 'fas fa-atom',
        'courseName' => 'Physics',
        'count' => 192
    ],
    [
        'iconClass' => 'fas fa-calculator',
        'courseName' => 'Mathematics',
        'count' => 310
    ]
];

?>

<?php
function renderHeader($title, $subtitle, $actions = [])
{
    echo '
    <!-- Header Section -->
    <div class="header-section">
        <div class="container-fluid header-content">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tachometer-alt me-3" style="font-size: 3rem;"></i>
                        <div>
                            <h1 class="dashboard-title">' . htmlspecialchars($title) . '</h1>
                            <p class="dashboard-subtitle mb-0">' . htmlspecialchars($subtitle) . '</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="quick-actions">';

    foreach ($actions as $action) {
        echo '
                            <button class="quick-btn">
                                <i class="' . htmlspecialchars($action['icon']) . ' me-2"></i>'
            . htmlspecialchars($action['label']) . '
                            </button>';
    }

    echo '          </div>
                </div>
            </div>
        </div>
    </div>';
}

function renderFilterButton($status, $label, $count, $color = 'dark', $active = false)
{
    $activeClass = $active ? ' active' : '';
    $iconMap = [
        'all'      => 'fas fa-list',
        'pending'  => 'fas fa-clock',
        'approved' => 'fas fa-check',
        'rejected' => 'fas fa-times',
        'waitlist' => 'fas fa-pause'
    ];

    $icon = isset($iconMap[$status]) ? $iconMap[$status] : 'fas fa-filter';

    echo <<<HTML
        <button 
            id="filter-{$status}" 
            class="btn btn-outline-{$color}{$activeClass} filter-btn" 
            onclick="filterByStatus('{$status}')"
        >
            <i class="{$icon} me-1"></i> {$label}
        </button>
    HTML;
}


function renderStudentCard($name, $id, $email, $phone, $course, $date, $status, $statusColor, $actions = [])
{
    // Default actions if none are provided
    if (empty($actions)) {
        $actions = [
            ['label' => 'View',   'icon' => 'fas fa-eye',   'color' => 'primary'],
            ['label' => 'Edit',   'icon' => 'fas fa-edit',  'color' => 'success'],
            ['label' => 'Delete', 'icon' => 'fas fa-trash', 'color' => 'danger']
        ];
    }

    echo <<<HTML
    <div class="student-details">
        <h5 class="student-name mb-0">{$name}</h5>
        <small class="student-id text-muted">{$id}</small>
        <ul class="list-unstyled mt-2 mb-3">
            <li><i class="fas fa-envelope me-2"></i>{$email}</li>
            <li><i class="fas fa-phone me-2"></i>{$phone}</li>
            <li><i class="fas fa-book me-2"></i>{$course}</li>
            <li><i class="fas fa-calendar me-2"></i>{$date}</li>
        </ul>
        <span class="badge bg-{$statusColor} mb-2">{$status}</span>
        <div class="card-actions">
    HTML;

    // Actions loop
    foreach ($actions as $action) {
        $btnLabel = $action['label'];
        $btnIcon  = $action['icon'];
        $btnColor = $action['color'];
        echo <<<BTN
            <button class="btn btn-sm btn-outline-{$btnColor}">
                <i class="{$btnIcon} me-1"></i>{$btnLabel}
            </button>
        BTN;
    }

    echo "</div></div>";
}

function renderStudentRow($id, $name, $email, $phone, $course, $date, $status, $badgeColor) {
    return '
    <tr data-status="' . strtolower($status) . '">
        <td><input type="checkbox" class="student-checkbox"></td>
        <td><strong>' . htmlspecialchars($id) . '</strong></td>
        <td>' . htmlspecialchars($name) . '</td>
        <td>' . htmlspecialchars($email) . '</td>
        <td>' . htmlspecialchars($phone) . '</td>
        <td>' . htmlspecialchars($course) . '</td>
        <td>' . htmlspecialchars($date) . '</td>
        <td><span class="badge bg-' . $badgeColor . '">' . htmlspecialchars($status) . '</span></td>
        <td>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to delete this student?\')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>';
}


?>
