<!-- Header Section -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 shadow-sm">
    <div class="container-fluid">

        <!-- Brand / Dashboard Title -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="fas fa-tachometer-alt me-2" style="font-size: 1.5rem;"></i>
            <span></span>
        </a>

        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarContent">

            <!-- Left Navigation -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/page/demo1.php"><i class="fas fa-users me-1"></i> Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fas fa-book me-1"></i> Courses</a>
                </li>
            </ul>

            <!-- Right Actions -->
            <div class="d-flex align-items-center">

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://via.placeholder.com/40" alt="Profile" class="rounded-circle me-2" width="40" height="40">
                        <strong>John Doe</strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>

            </div>

        </div>
    </div>
</nav>
