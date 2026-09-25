<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

initializeSession();

if (isLoggedIn()) {
    $user = currentUser();
    if ($user && isset($user['role'])) {
        redirect(BASE_URL . '/' . $user['role'] . '/test.php');
    }
}

$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken()) {
        $errors[] = 'Invalid or expired security token. Please try again.';
    }

    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $errors[] = 'Your account is inactive. Please contact the administrator.';
                } else {
                    loginUser($user);
                    $role = $user['role'];
                    redirect(BASE_URL . '/' . $role . '/test.php');
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } catch (Throwable $e) {
            if (DEBUG_MODE) {
                error_log('Login error: ' . $e->getMessage());
            }
            $errors[] = 'Unable to sign in right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(APP_NAME); ?> | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-1">Sign In</h2>
                            <p class="text-muted mb-0">Gym Management System</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php
                        $flashError = getFlash('error');
                        if ($flashError):
                        ?>
                            <div class="alert alert-warning" role="alert"><?php echo e($flashError); ?></div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <?php echo csrfField(); ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control form-control-lg"
                                    id="email"
                                    name="email"
                                    value="<?php echo e($_POST['email'] ?? ''); ?>"
                                    placeholder="admin@gymmanagement.local"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    class="form-control form-control-lg"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
                        </form>

                        <div class="text-center mt-4 small text-muted">
                            Secure access for Admin, Trainer, and Member roles only.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
