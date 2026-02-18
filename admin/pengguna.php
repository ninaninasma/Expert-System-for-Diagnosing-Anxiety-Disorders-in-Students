<?php
session_start();
// Memuat fungsi dari file terpisah (pastikan path relatif sudah benar)
// Jika admin/pengguna.php dan sql/functions.php, maka path-nya "../sql/functions.php"
require '../sql/functions.php'; 

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect ke halaman login jika belum login
    exit();
}

// Jumlah data per halaman
$limit = 10;

// Mengambil data dari tabel users
$page_users = isset($_GET['page_users']) ? intval($_GET['page_users']) : 1;
// Pastikan page_users tidak kurang dari 1
if ($page_users < 1) {
    $page_users = 1;
}

// Inisialisasi variabel untuk total pengguna dan total halaman
$total_users = getTotalRows('users');
$total_pages_users = ceil($total_users / $limit);

// Sesuaikan page_users jika melebihi total halaman yang ada (misal setelah penghapusan)
// Atau jika tidak ada data sama sekali, set ke halaman 1
if ($total_users == 0) {
    $page_users = 1;
} elseif ($page_users > $total_pages_users) {
    $page_users = $total_pages_users;
}

$offset_users = ($page_users - 1) * $limit;

// Ambil data pengguna sesuai pagination
$users = fetchData('users', $limit, $offset_users);

// --- Proses Form Submision (POST Requests) ---

// Proses tambah pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password_raw = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (empty($username) || empty($password_raw)) {
        $_SESSION['message'] = "Username dan password tidak boleh kosong.";
        $_SESSION['message_type'] = 'danger';
    } elseif (strlen($password_raw) < 6) { // Contoh: password minimal 6 karakter
        $_SESSION['message'] = "Password minimal 6 karakter.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $password_hashed = password_hash($password_raw, PASSWORD_BCRYPT); 
        $result = addUser($username, $password_hashed); // Fungsi ini harus ada di functions.php
        
        if ($result === true) {
            $message = "Pengguna <strong>" . htmlspecialchars($username) . "</strong> berhasil ditambahkan.";
            $_SESSION['message_type'] = 'success';
            // Redirect ke halaman terakhir yang mungkin berisi pengguna baru
            $new_total_users = getTotalRows('users');
            $page_users = ceil($new_total_users / $limit);
            if ($page_users == 0) $page_users = 1; // Jika baru data pertama
        } else {
            $message = "Gagal menambahkan pengguna: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: pengguna.php?page_users=$page_users");
    exit();
}

// Proses edit pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password_raw = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    
    if (empty($id) || !is_numeric($id) || empty($username)) { // Validasi ID dan username
        $_SESSION['message'] = "ID pengguna atau username tidak valid.";
        $_SESSION['message_type'] = 'danger';
    } elseif (!empty($password_raw) && strlen($password_raw) < 6) {
        $_SESSION['message'] = "Password minimal 6 karakter jika diisi.";
        $_SESSION['message_type'] = 'danger';
    } else {
        // Jika password kosong, kirim null ke fungsi updateUser agar tidak diubah
        $password_for_update = empty($password_raw) ? null : $password_raw;
        
        $result = updateUser($id, $username, $password_for_update); // Fungsi ini harus ada di functions.php
        
        if ($result === true) {
            $message = "Pengguna <strong>" . htmlspecialchars($username) . "</strong> berhasil diperbarui.";
            $_SESSION['message_type'] = 'success';
        } else {
            $message = "Gagal memperbarui pengguna: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: pengguna.php?page_users=$page_users");
    exit();
}

// Proses hapus pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $id = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
    
    if (empty($id) || !is_numeric($id)) { // Validasi ID
        $_SESSION['message'] = "ID pengguna tidak valid.";
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = deleteUser($id); // Fungsi ini harus ada di functions.php
        
        if ($result === true) {
            $message = "Pengguna berhasil dihapus.";
            $_SESSION['message_type'] = 'success';
            
            // Sesuaikan halaman setelah penghapusan
            $new_total_users_after_delete = getTotalRows('users');
            $new_total_pages_users_after_delete = ceil($new_total_users_after_delete / $limit);
            
            // Jika halaman saat ini menjadi kosong setelah penghapusan, kembali ke halaman sebelumnya
            if ($page_users > $new_total_pages_users_after_delete && $new_total_pages_users_after_delete > 0) {
                $page_users = $new_total_pages_users_after_delete;
            } elseif ($new_total_pages_users_after_delete == 0) { // Jika semua data terhapus
                $page_users = 1;
            }
        } else {
            $message = "Gagal menghapus pengguna: " . htmlspecialchars($result);
            $_SESSION['message_type'] = 'danger';
        }
        $_SESSION['message'] = $message;
    }
    header("Location: pengguna.php?page_users=$page_users");
    exit();
}

// Cek pesan dari operasi sebelumnya (untuk ditampilkan di modal)
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
unset($_SESSION['message']); // Hapus pesan setelah diambil
unset($_SESSION['message_type']); // Hapus tipe pesan setelah diambil

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
                    <a class="nav-link active" href="pengguna.php">Pengguna</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="gejala.php">Gejala</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="kecemasan.php">Kecemasan</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="aturan.php">Aturan</a>
                </li>
            </ul>
        </div>

            <div class="card card-admin mx-auto mb-3">
                <div class="card-body">
                    <?php if ($message): // Menampilkan modal notifikasi ?>
                    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header justify-content-center">
                                    <h5 class="modal-title" id="messageModalLabel">Informasi</h5>
                                </div>
                                <div class="modal-body text-center">
                                    <p class="mb-0"><?= $message ?></p>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="data-info">Menampilkan <?= count($users) ?> data dari <?= $total_users ?></span>
                        <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Tambah Pengguna</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped mt-3">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" data-userid="<?= htmlspecialchars($user['id']) ?>" data-username="<?= htmlspecialchars($user['username']) ?>">Edit</button>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-userid="<?= htmlspecialchars($user['id']) ?>" data-username="<?= htmlspecialchars($user['username']) ?>">Hapus</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data tersedia.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages_users > 1): // Hanya tampilkan pagination jika lebih dari 1 halaman ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages_users; $i++): ?>
                                <li class="page-item <?= $i == $page_users ? 'active' : '' ?>">
                                    <a class="page-link" href="pengguna.php?page_users=<?= $i ?>"><?= $i ?></a>
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

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="pengguna.php?page_users=<?= $page_users ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required />
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required />
                            <small class="text-muted">Minimal 6 karakter.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_user">Tambah Pengguna</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="pengguna.php?page_users=<?= $page_users ?>">
                        <input type="hidden" id="editUserId" name="userid" />
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required />
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="editPassword" name="password" />
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter jika diisi.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" name="edit_user">Perbarui Pengguna</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Hapus Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="pengguna.php?page_users=<?= $page_users ?>">
                        <p>Anda yakin ingin menghapus pengguna dengan username <strong><span id="deleteUsername"></span></strong>?</p>
                        <input type="hidden" id="deleteUserId" name="userid" />
                        <button type="submit" class="btn btn-danger" name="delete_user">Hapus</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle showing data in edit user modal
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var userId = button.getAttribute('data-userid');
            var username = button.getAttribute('data-username');
            
            var modalTitle = editUserModal.querySelector('.modal-title');
            var modalBodyInputUsername = editUserModal.querySelector('.modal-body input#editUsername');
            var modalBodyIdInput = editUserModal.querySelector('.modal-body input#editUserId');

            modalTitle.textContent = 'Edit Pengguna ' + username;
            modalBodyInputUsername.value = username;
            modalBodyIdInput.value = userId;
            // Kosongkan password field saat modal dibuka untuk keamanan
            editUserModal.querySelector('.modal-body input#editPassword').value = '';
        });

        // Handle showing data in delete user modal
        var deleteUserModal = document.getElementById('deleteUserModal');
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var userId = button.getAttribute('data-userid');
            var username = button.getAttribute('data-username');
            
            var modalBodyDeleteUsername = deleteUserModal.querySelector('.modal-body span#deleteUsername');
            var modalBodyIdInput = deleteUserModal.querySelector('.modal-body input#deleteUserId');

            modalBodyDeleteUsername.textContent = username;
            modalBodyIdInput.value = userId;
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