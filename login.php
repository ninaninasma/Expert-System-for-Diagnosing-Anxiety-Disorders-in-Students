<?php
session_start();
require 'db.php'; // File ini berisi konfigurasi database Anda

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Username atau password salah';
        }
    } else {
        $error = 'Username atau password salah';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - CemasYa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
         /* Background image & overlay */
        body {
            background: url('cemas_bg.svg') no-repeat center center fixed;
            background-size: cover;
            color: #e9f0f7;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Overlay to darken the image for better text contrast */
        .overlay {
            background-color: rgba(180, 211, 251, 0.75);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }

        header {
            background-color: rgba(44, 111, 227, 0.85);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #c1dbee;
        }

        header h3 {
            cursor: pointer;
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header h3 img {
            height: 40px;
            width: 40px;
        }

        nav a {
            color: #c1dbee;
            font-weight: 600;
            margin-left: 1.2rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        nav a:hover, nav a.active {
            color: #ffde59;
            text-decoration: underline;
        }

        main h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        main p.lead {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }

        .btn-custom {
            background-color: #1a75ff;
            border: none;
            color: white;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #005ce6;
            color: #e9f0f7;
            text-decoration: none;
        }

        footer {
            background-color: rgba(44, 111, 227, 0.85);
            color: #c1dbee;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
        }

        footer a {
            color: #ffde59;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Card login */
        .card-login {
            background-color: rgba(44, 111, 227, 0.85);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            color: #c1dbee;
            padding: 2rem;
        }

        .card-login .card-title {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        label {
            color: #c1dbee;
            font-weight: 600;
        }

        input.form-control {
            background-color: #e9f0f7;
            border: none;
            border-radius: 6px;
            color: #333;
        }

        input.form-control:focus {
            background-color: #f0f7ff;
            box-shadow: 0 0 5px #1a75ff;
            outline: none;
        }

        /* Button */
        .btn-custom, .btn-primary {
            background-color: #1a75ff;
            border: none;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            color: white;
        }

        .btn-custom:hover, .btn-primary:hover {
            background-color: #005ce6;
            color: #e9f0f7;
        }

        /* Alert */
        .alert-danger {
            background-color: rgba(255, 77, 77, 0.85);
            border: none;
            color: #fff;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        /* Footer */
        footer {
            background-color: rgba(44, 111, 227, 0.85);
            color: #c1dbee;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            margin-top: auto;
        }

        footer a {
            color: #ffde59;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Checkbox label */
        .form-check-label {
            color: #c1dbee;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <h3 onclick="window.location.href='index.php'">
            <img src="logocemas.png" alt="Logo Sistem Pakar" />
            CemasYa
        </h3>
        <nav>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="index.php" class="active"><?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="admin/gejala.php">Kelola Data</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="index.php">Home</a>
                <a href="login.php" class="active" aria-current="page">Login Pakar</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="overlay">
        <div class="card card-login">
            <h5 class="card-title text-center">Login</h5>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username anda ..." required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password anda ..." required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">Ingat Saya</label>
                    </div>
                    <button type="submit" class="btn btn-custom">Login</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        CemasYa - <a href="index.php">Jangan Abaikan Kecemasan Anda</a> &copy; 2025
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
