<?php
session_start();
require '../sql/functions.php'; // Memuat fungsi dari file terpisah

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$limit = 10;

function getTotalRowsThresholds() {
    global $conn;
    $query = "SELECT COUNT(*) AS total_rows FROM dass_anxiety_thresholds";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total_rows'];
    } else {
        return 0;
    }
}

$page_thresholds = isset($_GET['page_thresholds']) ? intval($_GET['page_thresholds']) : 1;
$offset_thresholds = ($page_thresholds - 1) * $limit;
$total_thresholds = getTotalRowsThresholds();
$total_pages_thresholds = ceil($total_thresholds / $limit);

// Ambil data ambang batas untuk tampilan
$query = "SELECT * FROM dass_anxiety_thresholds LIMIT $limit OFFSET $offset_thresholds";
$result = $conn->query($query);
$thresholds = $result->fetch_all(MYSQLI_ASSOC);

// Handle messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Form handling logic for adding, editing, and deleting thresholds goes here

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Gejala - CemasYa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Background image & overlay */
        body {
            background: url('../cemas_bg.svg') no-repeat center center fixed;
            background-size: cover;
            color: #e9f0f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .overlay {
            background-color: rgba(180, 211, 251, 0.75);
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            color: black;
        }

        header {
            background-color: rgba(44, 111, 227, 0.85);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #c1dbee;
            flex-shrink: 0;
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

        main {
            flex-grow: 1;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .nav-pills .nav-link {
            background-color: transparent;
            color: black;
            font-weight: 600;
            margin-right: 0.5rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background-color: #2c6fe3;
            color: #ffde59;
        }

        .card-admin {
            background-color: #ffffffcc;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            padding: 1rem;
        }

        .data-info {
            font-weight: 600;
            color: #333;
        }

        .btn-primary {
            background-color: #1a75ff;
            border: none;
            font-weight: 700;
        }

        .btn-primary:hover {
            background-color: #005ce6;
        }

        table thead {
            background-color: #2c6fe3;
            color: white;
        }

        table tbody tr:hover {
            background-color: #dce6fb;
        }

        footer {
            background-color: rgba(44, 111, 227, 0.85);
            color: #c1dbee;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        footer a {
            color: #ffde59;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Modal title text color */
        .modal-title.text-dark {
            color: #000 !important;
        }

        /* Modal body text color */
        .modal-body p, 
        .modal-body label {
            color: #000 !important;
        }

        /* Text dark for inputs labels */
        .form-label.text-dark {
            color: #000 !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</head>
<body>
    <header>
        <h3 onclick="window.location.href = '../index.php'">
            <img src="../logocemas.png" alt="Logo CemasYa" />
            CemasYa
        </h3>
        <nav>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="../index.php"><?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="kecemasan.php" class="active">Kelola Data</a>
                <a href="../logout.php">Logout</a>
            <?php else: ?>
                <a href="../index.php" class="active" aria-current="page">Home</a>
                <a href="../login.php">Login Pakar</a>
            <?php endif; ?>
        </nav>
    </header>

<div class="overlay">
    <main>
        <div class="d-flex justify-content-center mb-3 mt-3">
            <ul class="nav nav-pills" id="adminTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="pengguna.php">Pengguna</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="gejala.php">Gejala</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="kecemasan.php">Kecemasan</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" href="aturan.php">Aturan</a>
                </li>
            </ul>
        </div>

            <div class="card card-admin mx-auto">
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header justify-content-center">
                                    <h5 class="modal-title" id="messageModalLabel">Informasi</h5>
                                </div>
                                <div class="modal-body text-center">
                                    <p class="mb-0"><?= htmlspecialchars($message) ?></p>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="data-info">Menampilkan <?= count($thresholds) ?> data dari <?= $total_thresholds ?></span>
                        <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addThresholdModal">Tambah Aturan</button>
                    </div>

                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Level</th>
                                <th>Skor Min</th>
                                <th>Skor Max</th>
                                <th>Saran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($thresholds) > 0): ?>
                                <?php foreach ($thresholds as $threshold): ?>
                                <tr>
                                    <td><?= htmlspecialchars($threshold['id']) ?></td>
                                    <td><?= htmlspecialchars($threshold['level_name']) ?></td>
                                    <td><?= htmlspecialchars($threshold['min_score']) ?></td>
                                    <td><?= htmlspecialchars($threshold['max_score']) ?></td>
                                    <td><?= htmlspecialchars($threshold['suggestion']) ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editThresholdModal" data-thresholdid="<?= htmlspecialchars($threshold['id']) ?>" data-level_name="<?= htmlspecialchars($threshold['level_name']) ?>" data-min_score="<?= htmlspecialchars($threshold['min_score']) ?>" data-max_score="<?= htmlspecialchars($threshold['max_score']) ?>" data-suggestion="<?= htmlspecialchars($threshold['suggestion']) ?>">Edit</button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteThresholdModal" data-thresholdid="<?= htmlspecialchars($threshold['id']) ?>" data-level_name="<?= htmlspecialchars($threshold['level_name']) ?>">Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Tidak ada data ambang batas kecemasan tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages_thresholds > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages_thresholds; $i++): ?>
                                <li class="page-item <?= $i == $page_thresholds ? 'active' : '' ?>">
                                    <a class="page-link" href="aturan.php?page_thresholds=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <footer>
        CemasYa - <a href="../index.php">Jangan Abaikan Kecemasan Anda</a> &copy; 2025
    </footer>

<!-- Modal Add -->
<div class="modal fade" id="addThresholdModal" tabindex="-1" aria-labelledby="addThresholdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="aturan.php?page_thresholds=<?= $page_thresholds ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="addThresholdModalLabel">Tambah Aturan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="levelName" class="form-label">Nama Level</label>
                        <input type="text" class="form-control" id="levelName" name="level_name" required />
                    </div>
                    <div class="mb-3">
                        <label for="minScore" class="form-label">Skor Minimum</label>
                        <input type="number" class="form-control" id="minScore" name="min_score" required />
                    </div>
                    <div class="mb-3">
                        <label for="maxScore" class="form-label">Skor Maksimum</label>
                        <input type="number" class="form-control" id="maxScore" name="max_score" required />
                    </div>
                    <div class="mb-3">
                        <label for="suggestion" class="form-label">Saran</label>
                        <textarea class="form-control" id="suggestion" name="suggestion" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="add_threshold">Tambah Aturan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script>
    // Handle modal pre-population for Edit and Delete
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($message): ?>
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        <?php endif; ?>

        // Edit Modal pre-population
        var editThresholdModal = document.getElementById('editThresholdModal');
        editThresholdModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var thresholdId = button.getAttribute('data-thresholdid');
            var levelName = button.getAttribute('data-level_name');
            var minScore = button.getAttribute('data-min_score');
            var maxScore = button.getAttribute('data-max_score');
            var suggestion = button.getAttribute('data-suggestion');

            document.getElementById('editThresholdId').value = thresholdId;
            document.getElementById('editLevelName').value = levelName;
            document.getElementById('editMinScore').value = minScore;
            document.getElementById('editMaxScore').value = maxScore;
            document.getElementById('editSuggestion').value = suggestion;
        });

        // Delete Modal pre-population
        var deleteThresholdModal = document.getElementById('deleteThresholdModal');
        deleteThresholdModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var thresholdId = button.getAttribute('data-thresholdid');
            var levelName = button.getAttribute('data-level_name');
            document.getElementById('deleteLevelName').textContent = levelName;
            document.getElementById('deleteThresholdId').value = thresholdId;
        });
    });
</script>
</body>
</html>