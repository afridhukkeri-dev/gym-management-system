
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Invalid or expired CSRF token. Please try again.');
        redirect(BASE_URL . '/admin/trainers/create.php');
    }

    $formData['full_name'] = sanitizeString($_POST['full_name'] ?? '');
    $formData['email'] = sanitizeEmail($_POST['email'] ?? '');
    $formData['phone'] = sanitizeString($_POST['phone'] ?? '');
    $formData['specialization'] = sanitizeString($_POST['specialization'] ?? '');
    $formData['experience_years'] = trim((string) ($_POST['experience_years'] ?? '0'));
    $formData['certifications'] = trim((string) ($_POST['certifications'] ?? ''));
    $formData['bio'] = trim((string) ($_POST['bio'] ?? ''));

    $formData['status'] = in_array(
        $_POST['status'] ?? '',
        ['active', 'inactive'],
        true
    ) ? $_POST['status'] : 'active';

    $password = (string) ($_POST['password'] ?? '');

    // Validation
    if ($formData['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if (
        $formData['email'] === '' ||
        !isValidEmail($formData['email'])
    ) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (
        !ctype_digit($formData['experience_years']) ||
        (int) $formData['experience_years'] > 80
    ) {
        $errors[] = 'Experience must be a valid number between 0 and 80.';
    }

    if (!in_array($formData['status'], ['active', 'inactive'], true)) {
        $errors[] = 'Invalid trainer status.';
    }

    // Check whether email already exists
    if ($errors === []) {
        try {
            $pdo = getDatabaseConnection();

            $emailStmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email LIMIT 1'
            );
            $emailStmt->execute([
                ':email' => $formData['email'],
            ]);

            if ($emailStmt->fetch()) {
                $errors[] = 'This email address is already registered.';
            }
        } catch (Throwable $e) {
            if (DEBUG_MODE) {
                error_log('Trainer email validation failed: ' . $e->getMessage());
            }

            $errors[] = 'Unable to validate trainer details. Please try again.';
        }
    }

    // Insert user and trainer records
    if ($errors === []) {
        $pdo = null;

        try {
            $pdo = getDatabaseConnection();
            $pdo->beginTransaction();

            $userStmt = $pdo->prepare(
                'INSERT INTO users
                (full_name, email, phone, password_hash, role, status)
                VALUES
                (:full_name, :email, :phone, :password_hash, :role, :status)'
            );

            $userStmt->execute([
                ':full_name' => $formData['full_name'],
                ':email' => $formData['email'],
                ':phone' => $formData['phone'] !== ''
                    ? $formData['phone']
                    : null,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => 'trainer',
                ':status' => $formData['status'],
            ]);

            $userId = (int) $pdo->lastInsertId();

            $trainerStmt = $pdo->prepare(
                'INSERT INTO trainers
                (user_id, specialization, experience_years, certifications, bio, status)
                VALUES
                (:user_id, :specialization, :experience_years, :certifications, :bio, :status)'
            );

            $trainerStmt->execute([
                ':user_id' => $userId,
                ':specialization' => $formData['specialization'] !== ''
                    ? $formData['specialization']
                    : null,
                ':experience_years' => (int) $formData['experience_years'],
                ':certifications' => $formData['certifications'] !== ''
                    ? $formData['certifications']
                    : null,
                ':bio' => $formData['bio'] !== ''
                    ? $formData['bio']
                    : null,
                ':status' => $formData['status'],
            ]);

            $pdo->commit();

            setFlash('success', 'Trainer added successfully.');
            redirect(BASE_URL . '/admin/trainers/index.php');

        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (DEBUG_MODE) {
                error_log('Trainer creation failed: ' . $e->getMessage());
            }

            $errors[] = 'Unable to create trainer. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?>
        | Add Trainer
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css"
    >

    <style>
        body {
            background: #f4f7fb;
            min-height: 100vh;
        }

        .dashboard-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="dashboard-card p-4 p-lg-5 mx-auto" style="max-width: 980px;">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">
                        Admin Panel
                    </div>
                    <h3 class="mb-0">Add New Trainer</h3>
                </div>

                <a
                    href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Back to Trainers
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrfField(); ?>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input
                            type="text"
                            class="form-control"
                            name="full_name"
                            value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input
                            type="text"
                            class="form-control"
                            name="phone"
                            value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password *</label>
                        <input
                            type="password"
                            class="form-control"
                            name="password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Specialization</label>
                        <input
                            type="text"
                            class="form-control"
                            name="specialization"
                            value="<?php echo htmlspecialchars($formData['specialization'], ENT_QUOTES, 'UTF-8'); ?>"
                            maxlength="255"
                            placeholder="e.g. Personal Training, Yoga"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Experience (Years)</label>
                        <input
                            type="number"
                            class="form-control"
                            name="experience_years"
                            min="0"
                            max="80"
                            value="<?php echo htmlspecialchars($formData['experience_years'], ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Certifications</label>
                        <textarea
                            class="form-control"
                            name="certifications"
                            rows="3"
                            placeholder="e.g. Certified Personal Trainer"
                        ><?php echo htmlspecialchars($formData['certifications'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Trainer Bio</label>
                        <textarea
                            class="form-control"
                            name="bio"
                            rows="4"
                            placeholder="Write a short introduction about the trainer."
                        ><?php echo htmlspecialchars($formData['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Save Trainer
                        </button>

                        <a
                            href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/admin/trainers/index.php"
                            class="btn btn-outline-secondary"
                        >
                            Cancel
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</body>
</html>