<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');

$pdo = getDatabaseConnection();
$successMessage = getFlash('success');
$errorMessage = getFlash('error');

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$search = sanitizeString($search);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$whereSql = '';
$params = [];

if ($search !== '') {
    $clauses = [
        'u.full_name LIKE :search_name',
        'u.email LIKE :search_email',
        't.specialization LIKE :search_specialization',
    ];

    $params[':search_name'] = '%' . $search . '%';
    $params[':search_email'] = '%' . $search . '%';
    $params[':search_specialization'] = '%' . $search . '%';

    $whereSql = ' WHERE (' . implode(' OR ', $clauses) . ')';
}

$countSql = 'SELECT COUNT(*) AS total
             FROM trainers t
             LEFT JOIN users u ON u.id = t.user_id' . $whereSql;

$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalTrainers = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalTrainers / $perPage));

if ($page > $totalPages && $totalTrainers > 0) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$listSql = 'SELECT t.id AS trainer_id,
                  u.full_name,
                  u.email,
                  u.phone,
                  t.specialization,
                  t.experience_years,
                  t.status,
                  u.id AS user_id
             FROM trainers t
             LEFT JOIN users u ON u.id = t.user_id' . $whereSql . '
             ORDER BY t.id DESC
             LIMIT :limit OFFSET :offset';

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$trainers = $listStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/admin/trainers/index.php');
    }

    $trainerId = isset($_POST['trainer_id']) ? max(0, (int) $_POST['trainer_id']) : 0;
    if ($trainerId <= 0) {
        setFlash('error', 'A valid trainer ID is required for deletion.');
        redirect(BASE_URL . '/admin/trainers/index.php');
    }

    $relatedSql = 'SELECT COUNT(*) AS related_count
                   FROM workout_plans
                   WHERE trainer_id = :trainer_id';
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->execute([':trainer_id' => $trainerId]);
    $relatedCount = (int) $relatedStmt->fetchColumn();

    if ($relatedCount > 0) {
        setFlash('error', 'This trainer has related workout plan records and cannot be deleted safely. Please remove those records or deactivate the trainer instead.');
        redirect(BASE_URL . '/admin/trainers/index.php');
    }

    $trainerRowStmt = $pdo->prepare('SELECT user_id FROM trainers WHERE id = :trainer_id LIMIT 1');
    $trainerRowStmt->execute([':trainer_id' => $trainerId]);
    $trainerRow = $trainerRowStmt->fetch();

    if (!$trainerRow) {
        setFlash('error', 'Trainer not found.');
        redirect(BASE_URL . '/admin/trainers/index.php');
    }

    try {
        $pdo->beginTransaction();

        $deleteTrainerStmt = $pdo->prepare('DELETE FROM trainers WHERE id = :trainer_id');
        $deleteTrainerStmt->execute([':trainer_id' => $trainerId]);

        $deleteUserStmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
        $deleteUserStmt->execute([':user_id' => (int) $trainerRow['user_id']]);

        $pdo->commit();
        setFlash('success', 'Trainer deleted successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (DEBUG_MODE) {
            error_log('Trainer delete failed: ' . $e->getMessage());
        }

        setFlash('error', 'Unable to delete trainer because of a related database constraint.');
    }

    redirect(BASE_URL . '/admin/trainers/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Trainers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <style>
        body { background: #f4f7fb; min-height: 100vh; }
        .sidebar { background: #0f172a; min-height: 100vh; color: #fff; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); border-radius: 10px; padding: 0.7rem 1rem; margin-bottom: 0.35rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .dashboard-card { border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
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
                    <a class="nav-link" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/dashboard.php">Dashboard</a>
                    <a class="nav-link disabled" href="#">Members</a>
                    <a class="nav-link active" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php">Trainers</a>
                    <a class="nav-link disabled" href="#">Membership Plans</a>
                    <a class="nav-link disabled" href="#">Memberships</a>
                    <a class="nav-link disabled" href="#">Payments</a>
                    <a class="nav-link disabled" href="#">Attendance</a>
                </nav>
            </aside>

            <main class="col-lg-10 px-4 py-4">
                <div class="dashboard-card p-4 mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Admin Panel</div>
                            <h3 class="mb-0">Trainers Management</h3>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/create.php" class="btn btn-primary btn-sm">Add Trainer</a>
                        </div>
                    </div>
                </div>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="dashboard-card p-4 mb-4">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <label for="trainerSearch" class="form-label">Search trainers</label>
                            <input type="text" class="form-control" id="trainerSearch" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by trainer name, email, or specialization">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="dashboard-card p-0 overflow-hidden">
                    <?php if (empty($trainers)): ?>
                        <div class="p-5 text-center text-muted">
                            <h5 class="mb-2">No trainers found</h5>
                            <p class="mb-3">Add a trainer to get started with your gym staff records.</p>
                            <a href="create.php" class="btn btn-primary">Add Trainer</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Specialization</th>
                                        <th>Experience</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string) $trainer['trainer_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($trainer['full_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($trainer['email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($trainer['phone'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($trainer['specialization'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($trainer['experience_years'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> yrs</td>
                                            <td>
                                                <span class="badge bg-<?php echo ($trainer['status'] ?? 'inactive') === 'active' ? 'success' : 'secondary'; ?> rounded-pill">
                                                    <?php echo htmlspecialchars((string) ($trainer['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=<?php echo (int) $trainer['trainer_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="post" onsubmit="return confirm('Delete this trainer? This action is safe only when no related workout plans exist.');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="trainer_id" value="<?php echo (int) $trainer['trainer_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($totalTrainers > 0): ?>
                    <nav aria-label="Trainer pagination" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
