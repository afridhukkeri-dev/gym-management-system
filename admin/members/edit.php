<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');

$memberId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$errors = [];
$formData = [
    'member_id' => $memberId,
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'gender' => '',
    'date_of_birth' => '',
    'address' => '',
    'emergency_contact_name' => '',
    'emergency_contact_phone' => '',
    'join_date' => '',
    'status' => 'active',
    'password' => '',
];

$pdo = getDatabaseConnection();

if ($memberId <= 0) {
    setFlash('error', 'Invalid member ID.');
    redirect(BASE_URL . '/admin/members/index.php');
}

$memberStmt = $pdo->prepare('SELECT m.id AS member_id, u.id AS user_id, u.full_name, u.email, u.phone, m.gender, m.date_of_birth, m.address, m.emergency_contact_name, m.emergency_contact_phone, m.join_date, m.status FROM members m LEFT JOIN users u ON u.id = m.user_id WHERE m.id = :member_id LIMIT 1');
$memberStmt->execute([':member_id' => $memberId]);
$member = $memberStmt->fetch();

if (!$member) {
    setFlash('error', 'Member not found.');
    redirect(BASE_URL . '/admin/members/index.php');
}

$formData = [
    'member_id' => $memberId,
    'full_name' => $member['full_name'] ?? '',
    'email' => $member['email'] ?? '',
    'phone' => $member['phone'] ?? '',
    'gender' => $member['gender'] ?? '',
    'date_of_birth' => $member['date_of_birth'] ?? '',
    'address' => $member['address'] ?? '',
    'emergency_contact_name' => $member['emergency_contact_name'] ?? '',
    'emergency_contact_phone' => $member['emergency_contact_phone'] ?? '',
    'join_date' => $member['join_date'] ?? '',
    'status' => $member['status'] ?? 'active',
    'password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/admin/members/edit.php?id=' . $memberId);
    }

    $formData['full_name'] = sanitizeString($_POST['full_name'] ?? '');
    $formData['email'] = sanitizeEmail($_POST['email'] ?? '');
    $formData['phone'] = sanitizeString((string) ($_POST['phone'] ?? ''));
    $formData['gender'] = sanitizeString((string) ($_POST['gender'] ?? ''));
    $formData['date_of_birth'] = sanitizeString((string) ($_POST['date_of_birth'] ?? ''));
    $formData['address'] = sanitizeString((string) ($_POST['address'] ?? ''));
    $formData['emergency_contact_name'] = sanitizeString((string) ($_POST['emergency_contact_name'] ?? ''));
    $formData['emergency_contact_phone'] = sanitizeString((string) ($_POST['emergency_contact_phone'] ?? ''));
    $formData['join_date'] = sanitizeString((string) ($_POST['join_date'] ?? ''));
    $formData['status'] = in_array($_POST['status'] ?? '', ['active', 'inactive', 'paused'], true) ? (string) $_POST['status'] : 'active';
    $formData['password'] = (string) ($_POST['password'] ?? '');

    if ($formData['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if ($formData['email'] === '' || !isValidEmail($formData['email'])) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($formData['join_date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData['join_date'])) {
        $errors[] = 'Join date is required and must use the format YYYY-MM-DD.';
    }

    if ($formData['gender'] !== '' && !in_array($formData['gender'], ['male', 'female', 'other'], true)) {
        $errors[] = 'Selected gender is invalid.';
    }

    if ($errors === []) {
        try {
            $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id LIMIT 1');
            $existingStmt->execute([
                ':email' => $formData['email'],
                ':user_id' => (int) $member['user_id'],
            ]);
            if ($existingStmt->fetch()) {
                $errors[] = 'This email address already belongs to another user.';
            }
        } catch (Throwable $e) {
            if (DEBUG_MODE) {
                error_log('Member update validation failed: ' . $e->getMessage());
            }
            $errors[] = 'Unable to validate the updated member details right now.';
        }
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

            $userSql = 'UPDATE users SET full_name = :full_name, email = :email, phone = :phone';
            $userParams = [
                ':full_name' => $formData['full_name'],
                ':email' => $formData['email'],
                ':phone' => $formData['phone'] !== '' ? $formData['phone'] : null,
                ':user_id' => (int) $member['user_id'],
            ];
            if ($formData['password'] !== '') {
                $userSql .= ', password_hash = :password_hash';
                $userParams[':password_hash'] = password_hash($formData['password'], PASSWORD_BCRYPT);
            }
            $userSql .= ' WHERE id = :user_id';

            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute($userParams);

            $memberStmt = $pdo->prepare('UPDATE members SET gender = :gender, date_of_birth = :date_of_birth, address = :address, emergency_contact_name = :emergency_contact_name, emergency_contact_phone = :emergency_contact_phone, join_date = :join_date, status = :status WHERE id = :member_id');
            $memberStmt->execute([
                ':gender' => $formData['gender'] !== '' ? $formData['gender'] : null,
                ':date_of_birth' => $formData['date_of_birth'] !== '' ? $formData['date_of_birth'] : null,
                ':address' => $formData['address'] !== '' ? $formData['address'] : null,
                ':emergency_contact_name' => $formData['emergency_contact_name'] !== '' ? $formData['emergency_contact_name'] : null,
                ':emergency_contact_phone' => $formData['emergency_contact_phone'] !== '' ? $formData['emergency_contact_phone'] : null,
                ':join_date' => $formData['join_date'],
                ':status' => $formData['status'],
                ':member_id' => $memberId,
            ]);

            $pdo->commit();
            setFlash('success', 'Member updated successfully.');
            redirect(BASE_URL . '/admin/members/index.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (DEBUG_MODE) {
                error_log('Member update failed: ' . $e->getMessage());
            }
            $errors[] = 'Unable to update the member right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Edit Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <style>
        body { background: #f4f7fb; min-height: 100vh; }
    </style>
</head>
<body>
    <div class="container py-5 page-shell">
        <div class="form-panel">
            <div class="page-topbar mb-4">
                <div>
                    <span class="page-kicker">Admin Panel</span>
                    <h3 class="page-title">Edit Member</h3>
                </div>
                <div class="page-actions">
                    <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/members/index.php" class="btn btn-outline-secondary btn-sm">Back to Members</a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?php echo csrfField(); ?>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                    </div>
                    <div>
                        <label class="form-label">New Password (optional)</label>
                        <input type="password" class="form-control" name="password" minlength="8" placeholder="Leave blank to keep existing password">
                    </div>
                    <div>
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select gender</option>
                            <option value="male" <?php echo $formData['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $formData['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $formData['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($formData['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label class="form-label">Join Date</label>
                        <input type="date" class="form-control" name="join_date" value="<?php echo htmlspecialchars($formData['join_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Member Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="paused" <?php echo $formData['status'] === 'paused' ? 'selected' : ''; ?>>Paused</option>
                        </select>
                    </div>
                    <div class="full-span">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($formData['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Emergency Contact Name</label>
                        <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo htmlspecialchars($formData['emergency_contact_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label class="form-label">Emergency Contact Phone</label>
                        <input type="text" class="form-control" name="emergency_contact_phone" value="<?php echo htmlspecialchars($formData['emergency_contact_phone'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="full-span form-actions">
                        <button type="submit" class="btn btn-primary">Update Member</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
