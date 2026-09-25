<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');

$trainerId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'specialization' => '',
    'experience_years' => '0',
    'certifications' => '',
    'bio' => '',
    'status' => 'active',
];

if ($trainerId <= 0) {
    setFlash('error', 'Invalid trainer ID.');
    redirect(BASE_URL . '/admin/trainers/index.php');
}

$pdo = getDatabaseConnection();
$trainerStmt = $pdo->prepare(
    'SELECT t.id AS trainer_id, t.user_id, u.full_name, u.email, u.phone, t.specialization, t.experience_years, t.certifications, t.bio, t.status
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

$formData = [
    'full_name' => $trainer['full_name'] ?? '',
    'email' => $trainer['email'] ?? '',
    'phone' => $trainer['phone'] ?? '',
    'specialization' => $trainer['specialization'] ?? '',
    'experience_years' => (string) ($trainer['experience_years'] ?? 0),
    'certifications' => $trainer['certifications'] ?? '',
    'bio' => $trainer['bio'] ?? '',
    'status' => in_array($trainer['status'] ?? '', ['active', 'inactive'], true) ? $trainer['status'] : 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/admin/trainers/edit.php?id=' . $trainerId);
    }

    $formData['full_name'] = sanitizeString($_POST['full_name'] ?? '');
    $formData['email'] = sanitizeEmail($_POST['email'] ?? '');
    $formData['phone'] = sanitizeString((string) ($_POST['phone'] ?? ''));
    $formData['specialization'] = sanitizeString((string) ($_POST['specialization'] ?? ''));
    $formData['experience_years'] = trim((string) ($_POST['experience_years'] ?? '0'));
    $formData['certifications'] = trim((string) ($_POST['certifications'] ?? ''));
    $formData['bio'] = trim((string) ($_POST['bio'] ?? ''));
    $formData['status'] = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string) $_POST['status'] : 'active';

    if ($formData['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if ($formData['email'] === '' || !isValidEmail($formData['email'])) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($formData['experience_years'] === '' || !ctype_digit($formData['experience_years']) || (int) $formData['experience_years'] > 80) {
        $errors[] = 'Experience must be a valid number between 0 and 80.';
    }

    if ($errors === []) {
        try {
            $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id LIMIT 1');
            $existingStmt->execute([
                ':email' => $formData['email'],
                ':user_id' => (int) ($trainer['user_id'] ?? 0),
            ]);

            if ($existingStmt->fetch()) {
                $errors[] = 'This email address already belongs to another user.';
            }
        } catch (Throwable $e) {
            if (DEBUG_MODE) {
                error_log('Trainer edit duplicate check failed: ' . $e->getMessage());
            }
            $errors[] = 'Unable to validate the updated trainer details right now.';
        }
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

            $userStmt = $pdo->prepare(
                'UPDATE users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :user_id'
            );
            $userStmt->execute([
                ':full_name' => $formData['full_name'],
                ':email' => $formData['email'],
                ':phone' => $formData['phone'] !== '' ? $formData['phone'] : null,
                ':user_id' => (int) ($trainer['user_id'] ?? 0),
            ]);

            $trainerStmt = $pdo->prepare(
                'UPDATE trainers SET specialization = :specialization, experience_years = :experience_years, certifications = :certifications, bio = :bio, status = :status WHERE id = :trainer_id'
            );
            $trainerStmt->execute([
                ':specialization' => $formData['specialization'] !== '' ? $formData['specialization'] : null,
                ':experience_years' => (int) $formData['experience_years'],
                ':certifications' => $formData['certifications'] !== '' ? $formData['certifications'] : null,
                ':bio' => $formData['bio'] !== '' ? $formData['bio'] : null,
                ':status' => $formData['status'],
                ':trainer_id' => $trainerId,
            ]);

            $pdo->commit();
            setFlash('success', 'Trainer updated successfully.');
            redirect(BASE_URL . '/admin/trainers/index.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (DEBUG_MODE) {
                error_log('Trainer update failed: ' . $e->getMessage());
            }

            $errors[] = 'Unable to update the trainer right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> | Edit Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <style>
        body { background: #f4f7fb; min-height: 100vh; }
        .dashboard-card { border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="dashboard-card p-4 p-lg-5 mx-auto" style="max-width: 980px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Admin Panel</div>
                    <h3 class="mb-0">Edit Trainer</h3>
                </div>
                <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php" class="btn btn-outline-secondary btn-sm">Back to Trainers</a>
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($formData['specialization'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Experience (Years)</label>
                        <input type="number" class="form-control" name="experience_years" min="0" max="80" value="<?php echo htmlspecialchars($formData['experience_years'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Certifications</label>
                        <textarea class="form-control" name="certifications" rows="3" placeholder="List certifications or qualifications"><?php echo htmlspecialchars($formData['certifications'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4" placeholder="Short trainer bio"><?php echo htmlspecialchars($formData['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-12 mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Trainer</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
