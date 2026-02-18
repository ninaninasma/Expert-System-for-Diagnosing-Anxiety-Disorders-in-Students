<?php
include 'db.php';

session_start();

// Fetch Symptoms
$symptoms_sql = "SELECT * FROM symptoms";
$symptoms_result = $conn->query($symptoms_sql);
$symptoms = $symptoms_result->fetch_all(MYSQLI_ASSOC);

unset($_SESSION['diagnosis']);
unset($_SESSION['symptom_index']);
unset($_SESSION['selected_symptoms']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CemasYa</title>
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
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
                <a href="index.php" class="active" aria-current="page">Home</a>
                <a href="login.php">Login Pakar</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="overlay" style="color: black;">
        <main>
            <h1>Ketahui Tingkat Kecemasan Anda</h1>
            <p class="lead">Ingin Tahu Seberapa Tinggi Tingkat Kecemasan Anda? Mulai Sekarang!</p>
            <a href="diagnose.php" class="btn btn-custom">Mulai Sekarang</a>
        </main>
    </div>

    <footer>
        CemasYa - <a href="index.php">Jangan Abaikan Kecemasan Anda</a> &copy; 2025
    </footer>
</body>
</html>
