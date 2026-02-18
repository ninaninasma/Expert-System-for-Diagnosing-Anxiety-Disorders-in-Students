<?php
// error_reporting(E_ALL); // Uncomment for debugging only
// ini_set('display_errors', 1); // Uncomment for debugging only

session_start();
require '../sql/functions.php'; // Pastikan file ini berisi fungsi yang dibutuhkan

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$limit = 10;

// Ambil halaman dan offset
$page_levels = isset($_GET['page_levels']) ? intval($_GET['page_levels']) : 1;
// Pastikan page_levels tidak kurang dari 1
if ($page_levels < 1) {
    $page_levels = 1;
}

// Ambil total data terlebih dahulu untuk menghitung total halaman yang benar
$total_levels = getTotalRows('levels');
$total_pages_levels = ceil($total_levels / $limit);

// Sesuaikan page_levels jika melebihi total halaman yang ada (misal setelah penghapusan)
// Atau jika tidak ada data sama sekali, set ke halaman 1
if ($total_levels == 0) {
    $page_levels = 1;
} elseif ($page_levels > $total_pages_levels) {
    $page_levels = $total_pages_levels;
}

$offset_levels = ($page_levels - 1) * $limit;

// Ambil data dari tabel levels sesuai pagination
$levels = fetchData('levels', $limit, $offset_levels);

// --- Proses Form Submision (POST Requests) ---

// Proses tambah kecemasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $suggestion = filter_input(INPUT_POST, 'suggestion', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($code) || empty($name) || empty($suggestion)) {
        $_SESSION['message'] = "Kode, Nama, dan Saran tidak boleh kosong.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = addLevel($code, $name, $suggestion); // Fungsi ini harus ada di functions.php
        
        if ($result === true) {
            $message = "Kecemasan <strong>" . htmlspecialchars($code) . "</strong> berhasil ditambahkan.";
            $_SESSION['message_type'] = 'success';
            // Redirect ke halaman terakhir yang mungkin berisi data baru
            $new_total_levels = getTotalRows('levels');
            $page_levels = ceil($new_total_levels / $limit);
            if ($page_levels == 0) $page_levels = 1; // Jika baru data pertama
        } else {
            $message = "Gagal menambahkan kecemasan <strong>" . htmlspecialchars($code) . "</strong>: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: kecemasan.php?page_levels=$page_levels");
    exit();
}

// Proses edit kecemasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_level'])) {
    $id = filter_input(INPUT_POST, 'levelid', FILTER_VALIDATE_INT);
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $suggestion = filter_input(INPUT_POST, 'suggestion', FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($id) || !is_numeric($id) || empty($code) || empty($name) || empty($suggestion)) {
        $_SESSION['message'] = "ID Kecemasan, Kode, Nama, dan Saran tidak boleh kosong atau tidak valid.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = updateLevel($id, $code, $name, $suggestion); // Fungsi ini harus ada di functions.php

        if ($result === true) {
            $message = "Kecemasan <strong>" . htmlspecialchars($code) . "</strong> berhasil diperbarui.";
            $_SESSION['message_type'] = 'success';
        } else {
            $message = "Gagal memperbarui kecemasan <strong>" . htmlspecialchars($code) . "</strong>: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: kecemasan.php?page_levels=$page_levels");
    exit();
}

// Proses hapus kecemasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_level'])) {
    $id = filter_input(INPUT_POST, 'levelid', FILTER_VALIDATE_INT);

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['message'] = "ID Kecemasan tidak valid.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = deleteLevel($id); // Fungsi ini harus ada di functions.php

        if ($result === true) {
            $message = "Kecemasan berhasil dihapus.";
            $_SESSION['message_type'] = 'success';
            
            // Sesuaikan halaman setelah penghapusan
            $new_total_levels_after_delete = getTotalRows('levels');
            $new_total_pages_levels_after_delete = ceil($new_total_levels_after_delete / $limit);
            
            // Jika halaman saat ini menjadi kosong setelah penghapusan, kembali ke halaman sebelumnya
            if ($page_levels > $new_total_pages_levels_after_delete && $new_total_pages_levels_after_delete > 0) {
                $page_levels = $new_total_pages_levels_after_delete;
            } elseif ($new_total_pages_levels_after_delete == 0) { // Jika semua data terhapus
                $page_levels = 1;
            }
        } else {
            $message = "Gagal menghapus kecemasan: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: kecemasan.php?page_levels=$page_levels");
    exit();
}

// Ambil pesan dari session untuk ditampilkan di modal
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? null;
unset($_SESSION['message'], $_SESSION['message_type']); // Hapus pesan setelah diambil
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
                    <a class="nav-link active" href="kecemasan.php">Kecemasan</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="aturan.php">Aturan</a>
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
                    <span class="data-info">Menampilkan <?= count($levels) ?> data dari <?= $total_levels ?></span>
                    <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addLevelModal">Tambah Kecemasan</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Saran Penanganan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($levels) > 0): ?>
                                <?php foreach ($levels as $level): ?>
                                <tr>
                                    <td><?= htmlspecialchars($level['id']) ?></td>
                                    <td><?= htmlspecialchars($level['code']) ?></td>
                                    <td><?= htmlspecialchars($level['name']) ?></td>
                                    <td><?= htmlspecialchars($level['suggestion']) ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editLevelModal" data-levelid="<?= htmlspecialchars($level['id']) ?>" data-code="<?= htmlspecialchars($level['code']) ?>" data-name="<?= htmlspecialchars($level['name']) ?>" data-suggestion="<?= htmlspecialchars($level['suggestion']) ?>">Edit</button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteLevelModal" data-levelid="<?= htmlspecialchars($level['id']) ?>" data-code="<?= htmlspecialchars($level['code']) ?>">Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages_levels > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages_levels; $i++): ?>
                            <li class="page-item <?= $i == $page_levels ? 'active' : '' ?>">
                                <a class="page-link" href="kecemasan.php?page_levels=<?= $i ?>"><?= $i ?></a>
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
<div class="modal fade" id="addLevelModal" tabindex="-1" aria-labelledby="addLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="kecemasan.php?page_levels=<?= $page_levels ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLevelModalLabel">Tambah Kecemasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode</label>
                        <input type="text" class="form-control" id="code" name="code" required />
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="name" name="name" required />
                    </div>
                    <div class="mb-3">
                        <label for="suggestion" class="form-label">Saran Penanganan</label>
                        <textarea class="form-control" id="suggestion" name="suggestion" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="add_level">Tambah Kecemasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editLevelModal" tabindex="-1" aria-labelledby="editLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="kecemasan.php?page_levels=<?= $page_levels ?>">
                <input type="hidden" id="editLevelId" name="levelid" />
                <div class="modal-header">
                    <h5 class="modal-title" id="editLevelModalLabel">Edit Kecemasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCode" class="form-label">Kode</label>
                        <input type="text" class="form-control" id="editCode" name="code" required />
                    </div>
                    <div class="mb-3">
                        <label for="editName" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="editName" name="name" required />
                    </div>
                    <div class="mb-3">
                        <label for="editSuggestion" class="form-label">Saran Penanganan</label>
                        <textarea class="form-control" id="editSuggestion" name="suggestion" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="edit_level">Perbarui Kecemasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal fade" id="deleteLevelModal" tabindex="-1" aria-labelledby="deleteLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="kecemasan.php?page_levels=<?= $page_levels ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLevelModalLabel">Hapus Kecemasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus kecemasan dengan kode <strong><span id="deleteLevelCode"></span></strong>?</p>
                    <input type="hidden" id="deleteLevelId" name="levelid" />
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger" name="delete_level">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle showing data in edit level modal
    var editLevelModal = document.getElementById('editLevelModal');
    editLevelModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget; // Tombol yang memicu modal
        var levelId = button.getAttribute('data-levelid');
        var code = button.getAttribute('data-code');
        var name = button.getAttribute('data-name');
        var suggestion = button.getAttribute('data-suggestion');

        var modalTitle = editLevelModal.querySelector('.modal-title');
        var inputId = editLevelModal.querySelector('input#editLevelId');
        var inputCode = editLevelModal.querySelector('input#editCode');
        var inputName = editLevelModal.querySelector('input#editName');
        var inputSuggestion = editLevelModal.querySelector('textarea#editSuggestion');

        modalTitle.textContent = 'Edit Kecemasan ' + name;
        inputId.value = levelId;
        inputCode.value = code;
        inputName.value = name;
        inputSuggestion.value = suggestion;
    });

    // Handle showing data in delete level modal
    var deleteLevelModal = document.getElementById('deleteLevelModal');
    deleteLevelModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget; // Tombol yang memicu modal
        var levelId = button.getAttribute('data-levelid');
        var code = button.getAttribute('data-code');

        var modalCode = deleteLevelModal.querySelector('span#deleteLevelCode');
        var inputId = deleteLevelModal.querySelector('input#deleteLevelId');

        modalCode.textContent = code;
        inputId.value = levelId;
    });

    // Tampilkan modal pesan jika ada pesan dari sesi
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($message): ?>
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        <?php endif; ?>
    });
</script>

</body>
</html>