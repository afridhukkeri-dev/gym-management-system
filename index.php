<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

initializeSession();

$currentUser = currentUser();
$loggedIn = isLoggedIn();
$homeActionUrl = BASE_URL . '/login.php';
$homeActionLabel = 'Login';

if ($loggedIn && $currentUser && isset($currentUser['role'])) {
    $role = $currentUser['role'];
    $dashboardMap = [
        'admin' => BASE_URL . '/admin/dashboard.php',
        'trainer' => BASE_URL . '/trainer/test.php',
        'member' => BASE_URL . '/member/test.php',
    ];
    $homeActionUrl = $dashboardMap[$role] ?? BASE_URL . '/login.php';
    $homeActionLabel = 'Dashboard';
}

$siteStats = [
    'members' => 0,
    'trainers' => 0,
    'active_memberships' => 0,
    'payments' => 0,
];

try {
    $pdo = getDatabaseConnection();
    $siteStats['members'] = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $siteStats['trainers'] = (int) $pdo->query('SELECT COUNT(*) FROM trainers')->fetchColumn();
    $siteStats['active_memberships'] = (int) $pdo->query("SELECT COUNT(*) FROM memberships WHERE status = 'active'")->fetchColumn();
    $siteStats['payments'] = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
} catch (Throwable $e) {
    if (DEBUG_MODE) {
        error_log('Home stats query failed: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(APP_NAME); ?> | Premium Gym Management</title>
    <meta name="description" content="Gym Management System for modern fitness businesses, trainers, memberships, and member operations.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(BASE_URL); ?>/index.php"><?php echo e(APP_NAME); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#performance">Performance</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item mt-2 mt-lg-0">
                        <a class="btn home-action-btn <?php echo $loggedIn ? 'btn-success' : 'btn-primary'; ?> px-3 py-2" href="<?php echo e($homeActionUrl); ?>"><?php echo e($homeActionLabel); ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-warning px-3 py-2 mb-3">High-performance fitness operations</span>
                    <h1 class="display-4 mb-3">Build a stronger gym business from the inside out.</h1>
                    <p class="lead text-light mb-4">
                        Manage members, trainers, memberships, attendance, motivations, and performance with a premium platform designed for modern fitness brands.
                    </p>
                    <div class="d-flex gap-3 flex-wrap align-items-center">
                        <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary btn-lg home-action-btn">Member Login</a>
                        <a href="#services" class="btn btn-outline-light btn-lg home-action-btn secondary-btn">Explore Services</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="stat-card p-4 home-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Live System</div>
                                <h4 class="mb-0 mt-1">Gym Performance</h4>
                            </div>
                            <span class="badge text-bg-success">Active</span>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="mini-label">Members</div>
                                    <div class="mini-value"><?php echo e((string) $siteStats['members']); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="mini-label">Trainers</div>
                                    <div class="mini-value"><?php echo e((string) $siteStats['trainers']); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="mini-label">Active Plans</div>
                                    <div class="mini-value"><?php echo e((string) $siteStats['active_memberships']); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="mini-label">Payments</div>
                                    <div class="mini-value"><?php echo e((string) $siteStats['payments']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section id="services" class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-kicker">Fitness operations</span>
                    <h2 class="section-title">Everything your gym needs to scale.</h2>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">🏋️</div>
                            <h5>Member Management</h5>
                            <p class="text-muted-custom mb-0">Track member profiles, joins, status, and engagement in one place.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">👥</div>
                            <h5>Trainer Coordination</h5>
                            <p class="text-muted-custom mb-0">Assign plans, manage staff schedules, and maintain fitness programs.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="icon-circle mb-3">💳</div>
                            <h5>Membership & Billing</h5>
                            <p class="text-muted-custom mb-0">Monitor active plans, renewals, payment records, and business performance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="performance" class="py-5 bg-white">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <span class="section-kicker">Performance snapshot</span>
                        <h2 class="section-title">Built for growth, accountability, and consistent results.</h2>
                        <p class="text-muted-custom mb-4">
                            This platform helps gym owners and operators manage daily operations with clarity, maintain member trust, and keep every training program aligned to business goals.
                        </p>
                        <ul class="list-unstyled mb-0 home-feature-list">
                            <li>✓ Real-time member and trainer data</li>
                            <li>✓ Clean attendance and progress tracking</li>
                            <li>✓ Secure role-based dashboard access</li>
                            <li>✓ Organized payment and membership flow</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="stat-card home-panel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Operating Snapshot</h4>
                                <span class="badge text-bg-warning">Updated</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="mini-stat alt">
                                        <div class="mini-label">Members</div>
                                        <div class="mini-value"><?php echo e((string) $siteStats['members']); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat alt">
                                        <div class="mini-label">Trainers</div>
                                        <div class="mini-value"><?php echo e((string) $siteStats['trainers']); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat alt">
                                        <div class="mini-label">Plans</div>
                                        <div class="mini-value"><?php echo e((string) $siteStats['active_memberships']); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-stat alt">
                                        <div class="mini-label">Payments</div>
                                        <div class="mini-value"><?php echo e((string) $siteStats['payments']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact" class="footer">
        <div class="container">
            <div class="row align-items-center gy-3">
                <div class="col-md-6">
                    <div class="fw-semibold"><?php echo e(APP_NAME); ?></div>
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
