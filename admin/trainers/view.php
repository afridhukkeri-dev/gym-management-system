<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');

$trainerId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
if ($trainerId <= 0) {
    setFlash('error', 'Invalid trainer ID.');
    redirect(BASE_URL . '/admin/trainers/index.php');
}

$pdo = getDatabaseConnection();
$trainerStmt = $pdo->prepare(
    'SELECT t.id AS trainer_id, t.user_id, u.full_name, u.email, u.phone, t.specialization, t.experience_years, t.certifications, t.bio, t.status, t.created_at
     FROM trainers t
     LEFT JOIN users u ON u.id = t.user_id
     WHERE t.id = :trainer_id LIMIT 1'
);
$trainerStmt->execute([':trainer_id' => $trainerId]);
$trainer = $trainerStmt->fetch();

if (!$trainer) {
    setFlash('error', 'Trainer not found.');
    redirect(BASE_URL . '/admin/trainers/index.php');
}

$workoutPlanStmt = $pdo->prepare(
    'SELECT wp.id, wp.title, wp.status, wp.start_date, wp.end_date, m.full_name AS member_name
     FROM workout_plans wp
     LEFT JOIN members mem ON mem.id = wp.member_id
     LEFT JOIN users m ON m.id = mem.user_id
     WHERE wp.trainer_id = :trainer_id
     ORDER BY wp.start_date DESC
     LIMIT 5'
);
$workoutPlanStmt->execute([':trainer_id' => $trainerId]);
$assignedPlans = $workoutPlanStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Trainer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <style>
        body { background: #f4f7fb; min-height: 100vh; }
        .dashboard-card { border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
        .profile-header { background: linear-gradient(135deg, #111827, #1f2937); color: white; border-radius: 18px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="dashboard-card overflow-hidden">
            <div class="profile-header p-4 p-lg-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="text-uppercase small text-white-50">Trainer Profile</div>
                        <h2 class="mb-0 mt-2"><?php echo htmlspecialchars((string) ($trainer['full_name'] ?? 'Unknown Trainer'), ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/edit.php?id=<?php echo (int) $trainerId; ?>" class="btn btn-light btn-sm">Edit</a>
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php" class="btn btn-outline-light btn-sm">Back</a>
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="border rounded-4 p-4 bg-light h-100">
                            <div class="text-muted small text-uppercase mb-3">Account</div>
                            <div class="mb-3">
                                <div class="text-muted small">Status</div>
                                <span class="badge bg-<?php echo ($trainer['status'] ?? 'inactive') === 'active' ? 'success' : 'secondary'; ?> rounded-pill mt-1">
                                    <?php echo htmlspecialchars((string) ($trainer['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Email</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($trainer['email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Phone</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($trainer['phone'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="mb-0">
                                <div class="text-muted small">Created</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($trainer['created_at'] ? date('d M Y', strtotime((string) $trainer['created_at'])) : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-4 p-4 h-100">
                                    <div class="text-muted small text-uppercase">Specialization</div>
                                    <div class="fw-semibold mt-2"><?php echo htmlspecialchars((string) ($trainer['specialization'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-4 p-4 h-100">
                                    <div class="text-muted small text-uppercase">Experience</div>
                                    <div class="fw-semibold mt-2"><?php echo htmlspecialchars((string) ($trainer['experience_years'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> years</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-4 p-4">
                                    <div class="text-muted small text-uppercase">Certifications</div>
                                    <div class="mt-2"><?php echo !empty($trainer['certifications']) ? nl2br(htmlspecialchars((string) $trainer['certifications'], ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">Not provided</span>'; ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-4 p-4">
                                    <div class="text-muted small text-uppercase">Bio</div>
                                    <div class="mt-2"><?php echo !empty($trainer['bio']) ? nl2br(htmlspecialchars((string) $trainer['bio'], ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">No bio available</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Assigned Workout Plans</h4>
                    </div>

                    <?php if (empty($assignedPlans)): ?>
                        <div class="alert alert-light border mb-0">No workout plans are currently assigned to this trainer.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plan</th>
                                        <th>Member</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedPlans as $plan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string) ($plan['title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($plan['member_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($plan['start_date'] ? date('d M Y', strtotime((string) $plan['start_date'])) : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($plan['end_date'] ? date('d M Y', strtotime((string) $plan['end_date'])) : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($plan['status'] ?? 'inactive') === 'active' ? 'success' : 'secondary'; ?> rounded-pill">
                                                    <?php echo htmlspecialchars((string) ($plan['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
