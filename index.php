<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(APP_NAME); ?> | Foundation Phase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(BASE_URL); ?>/index.php"><?php echo e(APP_NAME); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-warning px-3 py-2 mb-3">Phase 1 Foundation</span>
                    <h1 class="display-4 mb-3">Professional Fitness Management Starts Here</h1>
                    <p class="lead text-light mb-4">
                        A clean and scalable system for managing members, trainers, memberships,
                        attendance, workout plans, and progress for a modern gym business.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#features" class="btn btn-primary btn-lg">Explore Features</a>
                        <a href="#about" class="btn btn-outline-light btn-lg">Project Overview</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="stat-card p-4">
                        <h4 class="mb-3">System Status</h4>
                        <div class="mb-3">
                            <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">Application Running</span>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Database</span>
                                <span class="badge text-bg-light">MySQL / PDO</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Framework</span>
                                <span class="badge text-bg-light">Core PHP</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Phase</span>
                                <span class="badge text-bg-light">Foundation</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section id="features" class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title">Core Foundation</h2>
                    <p class="text-muted-custom mx-auto" style="max-width: 650px;">
                        This project currently establishes the base architecture needed for future gym operations and management modules.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">⚙️</div>
                            <h5>System Configuration</h5>
                            <p class="text-muted-custom mb-0">Environment setup, base URL, upload settings, and safe application constants.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">🗃️</div>
                            <h5>Database Design</h5>
                            <p class="text-muted-custom mb-0">Normalized relational schema covering users, members, trainers, plans, payments, and progress.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">🛠️</div>
                            <h5>Reusable Helpers</h5>
                            <p class="text-muted-custom mb-0">Utility functions for redirects, escaping output, flash messages, and validation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="py-5 bg-white">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <h2 class="section-title">Project Overview</h2>
                        <p class="text-muted-custom">
                            This BCA final-year project is being built step by step. The current phase focuses on a strong and professional foundation.
                        </p>
                        <p class="text-muted-custom">
                            The system is intentionally limited to foundation work only. Authentication, admin panels, trainers, membership management,
                            attendance tracking, and payment modules will be added in subsequent phases.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h4 class="mb-3">Included in Phase 1</h4>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">✓ Project folder structure</li>
                                <li class="mb-2">✓ Database schema</li>
                                <li class="mb-2">✓ PDO configuration</li>
                                <li class="mb-2">✓ Core helper functions</li>
                                <li class="mb-2">✓ Professional landing page</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact" class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <strong><?php echo e(APP_NAME); ?></strong>
                </div>
                <div class="col-md-6 text-md-end">
                    <span>&copy; <span id="current-year"></span> Gym Management System</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo e(BASE_URL); ?>/assets/js/script.js"></script>
</body>
</html>
