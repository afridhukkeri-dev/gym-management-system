<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = currentUser();
$todayLabel = date('l, F j, Y');

$stats = [
    'members' => 0,
    'trainers' => 0,
    'active_memberships' => 0,
    'total_payments' => 0,
];

$recentPayments = [];

try {
    $pdo = getDatabaseConnection();

    $countQueries = [
        'members' => 'SELECT COUNT(*) AS total FROM members',
        'trainers' => 'SELECT COUNT(*) AS total FROM trainers',
        'active_memberships' => "SELECT COUNT(*) AS total FROM memberships WHERE status = 'active'",
        'total_payments' => 'SELECT COUNT(*) AS total FROM payments',
    ];

    foreach ($countQueries as $key => $query) {
        $stmt = $pdo->query($query);
        $row = $stmt->fetch();
        if ($row) {
            $stats[$key] = (int) $row['total'];
        }
    }

    $recentStmt = $pdo->prepare(
        'SELECT p.id, p.amount, p.payment_date, p.status,
                COALESCE(u.full_name, "Unknown Member") AS member_name
         FROM payments p
         LEFT JOIN memberships m ON m.id = p.membership_id
         LEFT JOIN members mem ON mem.id = m.member_id
         LEFT JOIN users u ON u.id = mem.user_id
         ORDER BY p.payment_date DESC
         LIMIT 5'
    );
    $recentStmt->execute();
    $recentPayments = $recentStmt->fetchAll();
} catch (Throwable $e) {
    if (DEBUG_MODE) {
        error_log('Admin dashboard query error: ' . $e->getMessage());
    }

    $stats = [
        'members' => 0,
        'trainers' => 0,
        'active_memberships' => 0,
        'total_payments' => 0,
    ];
    $recentPayments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: #f4f7fb;
        }

        .sidebar {
            background: #0f172a;
            min-height: 100vh;
            color: #fff;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            margin-bottom: 0.35rem;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .sidebar .nav-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .dashboard-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .stat-box {
            border-radius: 16px;
            padding: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <aside class="col-lg-2 sidebar p-3">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-2 px-2 py-1 rounded bg-warning text-dark fw-bold">GM</div>
                    <div>
                        <div class="fw-bold">Gym Management</div>
                    </div>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link active" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/dashboard.php">Dashboard</a>
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/members/index.php">Members</a>
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php">Trainers</a>
                    <a class="nav-link disabled" href="#">Membership Plans</a>
                    <a class="nav-link disabled" href="#">Memberships</a>
                    <a class="nav-link disabled" href="#">Payments</a>
                    <a class="nav-link disabled" href="#">Attendance</a>
                    <a class="nav-link disabled" href="#">Workout Plans</a>
                    <a class="nav-link disabled" href="#">Progress</a>
                    <a class="nav-link disabled" href="#">Reports</a>
                    <a class="nav-link disabled" href="#">Settings</a>
                </nav>
            </aside>

            <main class="col-lg-10 px-4 py-4">
                <header class="dashboard-card mb-4 p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Admin Panel</div>
                            <h3 class="mb-0 mt-1">Gym Management System</h3>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="fw-semibold"><?php echo htmlspecialchars($user['full_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted text-uppercase"><?php echo htmlspecialchars($user['role'] ?? 'admin', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                        </div>
                    </div>
                </header>

                <section class="mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="mb-1">Welcome back, Administrator!</h2>
                            <p class="text-muted mb-0">Today is <?php echo htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="stat-box dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="text-muted small">Total Members</div>
                                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string) $stats['members'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">👥</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="stat-box dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="text-muted small">Total Trainers</div>
                                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string) $stats['trainers'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">🏋️</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="stat-box dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="text-muted small">Active Memberships</div>
                                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string) $stats['active_memberships'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">✅</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="stat-box dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="text-muted small">Total Payments</div>
                                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string) $stats['total_payments'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">💰</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dashboard-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Recent Payments</h4>
                    </div>

                    <?php if (empty($recentPayments)): ?>
                        <div class="text-muted">No payment records found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['member_name'] ?? 'Unknown Member', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(number_format((float) $payment['amount'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) $payment['payment_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($payment['status'] === 'paid') ? 'success' : 'warning'; ?> text-uppercase">
                                                    <?php echo htmlspecialchars((string) $payment['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
