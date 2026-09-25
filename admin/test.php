<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(APP_NAME); ?> | Admin Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="alert alert-success text-center">
            <h2 class="mb-2">Admin Access Approved</h2>
            <p class="mb-0">Welcome, <?php echo e($user['full_name']); ?>.</p>
            <p class="mb-0">Role: <?php echo e(strtoupper($user['role'])); ?></p>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo e(BASE_URL); ?>/logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>
</body>
</html>
