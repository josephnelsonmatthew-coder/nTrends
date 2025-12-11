<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: modules/appointments/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - nTrends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            
            font-family: 'Poppins', sans-serif;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            background-color: #f9fafb;
        }
        .btn-primary {
            background-color: #4f46e5;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
        }
        .btn-primary:hover { background-color: #4338ca; }
        .brand-logo {
            width: 60px;
            height: 60px;
            background: #4f46e5;
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

    <div class="login-card">
        <div class="brand-logo"><i class="fas fa-cut"></i></div>
        <h4 class="text-center fw-bold mb-1">Welcome Back</h4>
        <p class="text-center text-muted mb-4">Sign in to nTrends</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center p-2 mb-3 small">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Enter username">
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary shadow-sm">Login</button>
        </form>
        <div class="text-center">
            <a href="register.php" class="text-decoration-none text-muted small">
                New User? <strong>Create an account</strong>
            </a>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>