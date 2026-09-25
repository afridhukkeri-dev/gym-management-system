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

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $sql = "SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$search = sanitizeString($search);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$memberColumns = [
    'name' => tableHasColumn($pdo, 'users', 'full_name'),
    'email' => tableHasColumn($pdo, 'users', 'email'),
    'phone' => tableHasColumn($pdo, 'users', 'phone'),
    'gender' => tableHasColumn($pdo, 'members', 'gender'),
    'join_date' => tableHasColumn($pdo, 'members', 'join_date'),
    'status' => tableHasColumn($pdo, 'members', 'status'),
];

$whereSql = '';
$params = [];

if ($search !== '') {
    $clauses = [];

    if ($memberColumns['name']) {
        $clauses[] = 'u.full_name LIKE :search_name';
        $params[':search_name'] = '%' . $search . '%';
    }

    if ($memberColumns['email']) {
        $clauses[] = 'u.email LIKE :search_email';
        $params[':search_email'] = '%' . $search . '%';
    }

    if ($memberColumns['phone']) {
        $clauses[] = 'u.phone LIKE :search_phone';
        $params[':search_phone'] = '%' . $search . '%';
    }

    if (!empty($clauses)) {
        $whereSql = ' WHERE (' . implode(' OR ', $clauses) . ')';
    }
}

$countSql = 'SELECT COUNT(*) AS total FROM members m LEFT JOIN users u ON u.id = m.user_id' . $whereSql;
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalMembers = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalMembers / $perPage));
if ($page > $totalPages && $totalMembers > 0) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listSql = 'SELECT m.id AS member_id, u.full_name, u.email, u.phone, m.gender, m.join_date, m.status, u.id AS user_id
    FROM members m
    LEFT JOIN users u ON u.id = m.user_id' . $whereSql . ' ORDER BY m.join_date DESC, m.id DESC LIMIT :limit OFFSET :offset';
$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$members = $listStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/admin/members/index.php');
    }

    $memberId = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
    $memberId = max(0, $memberId);
    if ($memberId <= 0) {
        setFlash('error', 'A valid member ID is required for deletion.');
        redirect(BASE_URL . '/admin/members/index.php');
    }

    $relatedCheckSql = 'SELECT
        (SELECT COUNT(*) FROM memberships WHERE member_id = :member_id) +
        (SELECT COUNT(*) FROM attendance WHERE member_id = :member_id) +
        (SELECT COUNT(*) FROM workout_plans WHERE member_id = :member_id) +
        (SELECT COUNT(*) FROM progress WHERE member_id = :member_id) AS related_count';
    $relatedStmt = $pdo->prepare($relatedCheckSql);
    $relatedStmt->execute([':member_id' => $memberId]);
    $relatedCount = (int) $relatedStmt->fetchColumn();

    if ($relatedCount > 0) {
        setFlash('error', 'This member has related records and cannot be hard-deleted safely. Please deactivate the member or remove the related records in a separate controlled process.');
        redirect(BASE_URL . '/admin/members/index.php');
    }

    $memberRowStmt = $pdo->prepare('SELECT user_id FROM members WHERE id = :member_id LIMIT 1');
    $memberRowStmt->execute([':member_id' => $memberId]);
    $memberRow = $memberRowStmt->fetch();

    if (!$memberRow) {
        setFlash('error', 'Member not found.');
        redirect(BASE_URL . '/admin/members/index.php');
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM members WHERE id = :member_id')->execute([':member_id' => $memberId]);
        $pdo->prepare('DELETE FROM users WHERE id = :user_id')->execute([':user_id' => (int) $memberRow['user_id']]);
        $pdo->commit();
        setFlash('success', 'Member deleted successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (DEBUG_MODE) {
            error_log('Member delete failed: ' . $e->getMessage());
        }
        setFlash('error', 'Unable to delete member because of a database constraint.');
    }

    redirect(BASE_URL . '/admin/members/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Members</title>
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
                    <a class="nav-link active" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/members/index.php">Members</a>
                    <a class="nav-link disabled" href="#">Trainers</a>
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
                            <h3 class="mb-0">Members Management</h3>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
                            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/members/create.php" class="btn btn-primary btn-sm">Add Member</a>
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
                            <label for="memberSearch" class="form-label">Search members</label>
                            <input type="text" class="form-control" id="memberSearch" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by name, email, or phone">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="dashboard-card p-0 overflow-hidden">
                    <?php if (empty($members)): ?>
                        <div class="p-5 text-center text-muted">
                            <h5 class="mb-2">No members found</h5>
                            <p class="mb-3">Add your first member to start managing gym records.</p>
                            <a href="create.php" class="btn btn-primary">Add Member</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string) $member['member_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($member['full_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($member['email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($member['phone'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($member['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($member['join_date'] ? date('d M Y', strtotime((string) $member['join_date'])) : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($member['status'] ?? 'inactive') === 'active' ? 'success' : 'secondary'; ?> rounded-pill">
                                                    <?php echo htmlspecialchars((string) ($member['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=<?php echo (int) $member['member_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="post" onsubmit="return confirm('Delete this member? This action is safe only when no related records exist.');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="member_id" value="<?php echo (int) $member['member_id']; ?>">
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

                <?php if ($totalMembers > 0): ?>
                    <nav aria-label="Member pagination" class="mt-4">
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
