<?php
session_start();
require '../sql/functions.php'; // Memuat fungsi dari file terpisah

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Jumlah data per halaman
$limit = 10;

// Mengambil data dari tabel symptoms
$page_symptoms = isset($_GET['page_symptoms']) ? intval($_GET['page_symptoms']) : 1;
$offset_symptoms = ($page_symptoms - 1) * $limit;
$symptoms = fetchData('symptoms', $limit, $offset_symptoms);
$total_symptoms = getTotalRows('symptoms');
$total_pages_symptoms = ceil($total_symptoms / $limit);

// Proses tambah gejala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_symptom'])) {
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $result = addSymptom($code, $name);
    $message = $result === true ? "Gejala $code berhasil ditambahkan." : "Gagal menambahkan gejala $code: " . $result;
    $page_symptoms = ceil(($total_symptoms + 1) / $limit);
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $result === true ? 'success' : 'danger';
    header("Location: gejala.php?page_symptoms=$page_symptoms");
    exit();
}

// Proses edit gejala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_symptom'])) {
    $id = filter_input(INPUT_POST, 'symptomid', FILTER_VALIDATE_INT);
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $result = updateSymptom($id, $code, $name);
    $message = $result === true ? "Gejala $code berhasil diperbarui." : "Gagal memperbarui gejala $code: " . $result;
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $result === true ? 'success' : 'danger';
    header("Location: gejala.php?page_symptoms=$page_symptoms");
    exit();
}

// Proses hapus gejala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_symptom'])) {
    $id = filter_input(INPUT_POST, 'symptomid', FILTER_VALIDATE_INT);
    $result = deleteSymptom($id);
    if ($page_symptoms > 1 && $offset_symptoms >= $total_symptoms - 1) {
        $page_symptoms--;
    }
    $message = $result === true ? "Gejala berhasil dihapus." : "Gagal menghapus gejala: " . $result;
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $result === true ? 'success' : 'danger';
    header("Location: gejala.php?page_symptoms=$page_symptoms");
    exit();
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);
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
                <a href="gejala.php" class="active">Kelola Data</a>
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
                        <a class="nav-link text-dark" href="pengguna.php">Pengguna</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="gejala.php">Gejala</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-dark" href="kecemasan.php">Kecemasan</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-dark" href="aturan.php">Aturan</a>
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
                        <span class="data-info">Menampilkan <?= count($symptoms) ?> data dari <?= $total_symptoms ?></span>
                        <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addSymptomModal">Tambah Gejala</button>
                    </div>

                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($symptoms) > 0): ?>
                                <?php foreach ($symptoms as $symptom): ?>
                                <tr>
                                    <td><?= $symptom['id'] ?></td>
                                    <td><?= $symptom['code'] ?></td>
                                    <td><?= $symptom['name'] ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSymptomModal" data-symptomid="<?= $symptom['id'] ?>" data-code="<?= $symptom['code'] ?>" data-name="<?= $symptom['name'] ?>">Edit</button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSymptomModal" data-symptomid="<?= $symptom['id'] ?>" data-code="<?= $symptom['code'] ?>">Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Tidak ada data tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($total_symptoms > $limit): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages_symptoms; $i++): ?>
                                <li class="page-item <?= $i == $page_symptoms ? 'active' : '' ?>">
                                    <a class="page-link" href="gejala.php?page_symptoms=<?= $i ?>"><?= $i ?></a>
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

    <!-- Modals -->
    <!-- Add Symptom Modal -->
    <div class="modal fade" id="addSymptomModal" tabindex="-1" aria-labelledby="addSymptomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="addSymptomModalLabel">Tambah Gejala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="gejala.php?page_symptoms=<?= $page_symptoms ?>">
                        <div class="mb-3">
                            <label for="code" class="form-label text-dark">Kode</label>
                            <input type="text" class="form-control" id="code" name="code" required />
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label text-dark">Nama</label>
                            <input type="text" class="form-control" id="name" name="name" required />
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_symptom">Tambah Gejala</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Symptom Modal -->
    <div class="modal fade" id="editSymptomModal" tabindex="-1" aria-labelledby="editSymptomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="editSymptomModalLabel">Edit Gejala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="gejala.php?page_symptoms=<?= $page_symptoms ?>">
                        <input type="hidden" id="editSymptomId" name="symptomid" />
                        <div class="mb-3">
                            <label for="editCode" class="form-label text-dark">Kode</label>
                            <input type="text" class="form-control" id="editCode" name="code" required />
                        </div>
                        <div class="mb-3">
                            <label for="editName" class="form-label text-dark">Nama</label>
                            <input type="text" class="form-control" id="editName" name="name" required />
                        </div>
                        <button type="submit" class="btn btn-primary" name="edit_symptom">Perbarui Gejala</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Symptom Modal -->
    <div class="modal fade" id="deleteSymptomModal" tabindex="-1" aria-labelledby="deleteSymptomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="deleteSymptomModalLabel">Hapus Gejala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="gejala.php?page_symptoms=<?= $page_symptoms ?>">
                        <p class="text-dark">Anda yakin ingin menghapus gejala dengan kode <span id="deleteSymptomCode"></span>?</p>
                        <input type="hidden" id="deleteSymptomId" name="symptomid" />
                        <button type="submit" class="btn btn-danger" name="delete_symptom">Hapus</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // Handle showing data in modals
        var editSymptomModal = document.getElementById('editSymptomModal');
        editSymptomModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var symptomId = button.getAttribute('data-symptomid');
            var code = button.getAttribute('data-code');
            var name = button.getAttribute('data-name');
            var modalTitle = editSymptomModal.querySelector('.modal-title');
            var modalBodyInputCode = editSymptomModal.querySelector('.modal-body input#editCode');
            var modalBodyInputName = editSymptomModal.querySelector('.modal-body input#editName');
            var modalBodyIdInput = editSymptomModal.querySelector('.modal-body input#editSymptomId');

            modalTitle.textContent = 'Edit Gejala ' + name;
            modalBodyInputCode.value = code;
            modalBodyInputName.value = name;
            modalBodyIdInput.value = symptomId;
        });

        var deleteSymptomModal = document.getElementById('deleteSymptomModal');
        deleteSymptomModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var symptomId = button.getAttribute('data-symptomid');
            var code = button.getAttribute('data-code');
            var modalBodyCode = deleteSymptomModal.querySelector('.modal-body #deleteSymptomCode');
            var modalBodyIdInput = deleteSymptomModal.querySelector('.modal-body input#deleteSymptomId');

            modalBodyCode.textContent = code;
            modalBodyIdInput.value = symptomId;
        });

        // Show message modal if there is a message
        <?php if ($message): ?>
        var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        messageModal.show();
        <?php endif; ?>
    </script>
</body>
</html>