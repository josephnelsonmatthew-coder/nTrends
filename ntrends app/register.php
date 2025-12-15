<?php
require 'config/security.php';
require 'config/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = '<div class="alert alert-danger">Passwords do not match!</div>';
    } else {
        // Check if username taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-warning">Username already exists.</div>';
        } else {
            // Hash password and save
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$username, $hashed])) {
                header("Location: login.php?error=Account created! Please login.");
                exit;
            } else {
                $message = '<div class="alert alert-danger">Error creating account.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - nTrends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            background-color: #f9fafb;
        }

        .btn-success {
            background-color: #10b981;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-weight: 500;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px auto;
        }
    </style>
</head>

<body>

    <div class="register-card">
        <div class="brand-logo"><i class="fas fa-user-plus"></i></div>
        <h4 class="text-center fw-bold mb-1">Create Account</h4>
        <p class="text-center text-muted mb-4">Join Salon Pro</p>

        <?php echo $message; ?>

        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Choose a username">
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Create password">
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted fw-bold">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required
                    placeholder="Repeat password">
            </div>
            <button type="submit" class="btn btn-success shadow-sm mb-3">Sign Up</button>

            <div class="text-center">
                <a href="login.php" class="text-decoration-none text-muted small">Already have an account?
                    <strong>Login</strong></a>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>

</html>