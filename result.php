<?php
session_start();

// Cek apakah data gejala telah diset di sesi
if (!isset($_SESSION['selected_symptoms'])) {
    header('Location: index.php');
    exit();
}

$selected_symptoms = $_SESSION['selected_symptoms'];
$count_symptoms = count($selected_symptoms);

// Include koneksi database
include 'db.php';

// Tentukan kode kecemasan berdasarkan jumlah gejala
if ($count_symptoms < 4) {
    $kode_kecemasan = 'T001';  // Rendah
} elseif ($count_symptoms >= 4 && $count_symptoms <= 6) {
    $kode_kecemasan = 'T002';  // Sedang
} else {
    $kode_kecemasan = 'T003';  // Berat
}

// Ambil data diagnosis dari tabel levels berdasarkan kode kecemasan
$stmt = $conn->prepare("SELECT name, suggestion FROM levels WHERE code = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $kode_kecemasan);
$stmt->execute();
$stmt->bind_result($diag_name, $advice);
if (!$stmt->fetch()) {
    die("Fetch failed: Data tidak ditemukan untuk kode $kode_kecemasan");
}
$stmt->close();

// Ambil nama gejala dari kode gejala yang dipilih
$gejala_names = [];
foreach ($selected_symptoms as $code) {
    $stmt = $conn->prepare("SELECT name FROM symptoms WHERE code = ?");
    if (!$stmt) {
        die("Prepare failed (gejala): " . $conn->error);
    }
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($name);
    if ($stmt->fetch()) {
        $gejala_names[] = "$code - $name";
    } else {
        $gejala_names[] = "$code - (nama gejala tidak ditemukan)";
    }
    $stmt->close();
}

date_default_timezone_set('Asia/Jakarta');
$timestamp = date('Y-m-d H:i:s', time());

// Simpan data diagnosis ke session agar bisa digunakan di tampilan hasil
$_SESSION['diagnosis'] = [
    'name' => $diag_name,
    'suggestion' => $advice,
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hasil Diagnosa Tingkat Kecemasan - CemasYa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: url('cemas_bg.svg') no-repeat center center fixed;
            background-size: cover;
            color: #e9f0f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        .overlay {
            background-color: rgba(180, 211, 251, 0.75);
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: black;
        }
        main {
            max-width: 600px;
            width: 100%;
            background-color: white;
            padding: 2rem 3rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            text-align: left;
        }
        main h1 {
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            color: #2c6fe3;
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
            margin: 0;
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
        footer {
            background-color: rgba(44, 111, 227, 0.85);
            color: #c1dbee;
            text-align: center;
            padding: 1rem 2rem;
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
        .btn-group {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
    </style>
</head>
<body>
<header>
    <h3 onclick="window.location.href = 'index.php'">
        <img src="logocemas.png" alt="Logo CemasYa" />
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

<div class="overlay">
    <main>
        <h1>Hasil Diagnosa</h1>
        <div>
            <h5>Tingkat kecemasan yang terdeteksi: <strong><?= htmlspecialchars($diag_name) ?></strong></h5>
            <p><strong>Saran:</strong> <?= htmlspecialchars($advice) ?></p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='clear_diagnosis.php'">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="showModalOrGeneratePDF()">Cetak PDF</button>
        </div>
    </main>
</div>

<footer>
    <p>CemasYa - <a href="index.php">Jangan Abaikan Kecemasan Anda</a> &copy; 2025</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>

<script>
function showModalOrGeneratePDF() {
    <?php if (!isset($_SESSION['username'])): ?>
    var nameModal = new bootstrap.Modal(document.getElementById('nameModal'));
    nameModal.show();
    <?php else: ?>
    generatePDF();
    <?php endif; ?>
}

function generatePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    var userName = document.getElementById('userName')?.value || 'User';
    var diagnosisName = '<?= addslashes($diag_name) ?>';
    var suggestion = <?= json_encode($advice) ?>;
    var timestamp = new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' });

    const marginX = 20;
    const marginY = 20;
    const contentWidth = doc.internal.pageSize.width - 2 * marginX;
    let y = marginY;

    var img = new Image();
    img.src = 'logocemas.png';
    img.onload = function() {
        const imgWidth = 30;
        const imgHeight = 30 * (img.height / img.width);
        doc.addImage(img, 'PNG', marginX, y, imgWidth, imgHeight);
        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text('CemasYa', marginX + imgWidth + 10, y + imgHeight / 2 + 5);

        y += imgHeight + 10;

        doc.setFontSize(24);
        doc.text('Laporan', doc.internal.pageSize.width / 2, y, { align: 'center' });
        y += 10;

        doc.setFontSize(14);
        doc.text('Daftar Penanganan Tingkat Kecemasan Anda', doc.internal.pageSize.width / 2, y, { align: 'center' });
        y += 20;

        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text('Nama', marginX, y);
        doc.text(`:  ${userName}`, marginX + 35, y);
        y += 5;

        const startY = y;
        y += 10;

        doc.setFont('helvetica', 'bold');
        doc.text('Diagnosa', marginX, y);
        doc.text(`:`, marginX + 35, y);
        doc.setFont('helvetica', 'normal');
        doc.text(`${diagnosisName}`, marginX + 38, y);
        y += 10;

        doc.setFont('helvetica', 'bold');
        doc.text('Saran', marginX, y);
        doc.text(`:`, marginX + 35, y);
        doc.setFont('helvetica', 'normal');
        let adviceLines = doc.splitTextToSize(suggestion, contentWidth - 38);
        doc.text(adviceLines, marginX + 38, y);
        y += adviceLines.length * 10;

        doc.setFont('helvetica', 'bold');
        doc.text('Gejala', marginX, y);
        doc.text(`:`, marginX + 35, y);

        y += 10;
        const gejala = <?= json_encode($gejala_names); ?>;

        // Tampilkan gejala satu per satu ke bawah
        gejala.forEach(item => {
            let lines = doc.splitTextToSize(item, contentWidth - 35);
            doc.text(lines, marginX + 35, y);
            y += lines.length * 10;
        });

        const endY = y;

        doc.setFont('helvetica', 'normal');
        doc.text(`Timestamp: ${timestamp}`, marginX, endY + 10);

        doc.setLineWidth(0.5);
        doc.roundedRect(marginX - 10, startY, doc.internal.pageSize.width - 2 * marginX + 20, endY - startY, 10, 10, 'S');

        doc.save(`diagnosis_report_${userName}.pdf`);
    };

    img.onerror = function() {
        // Jika logo gagal load, lanjutkan tanpa logo
        doc.setFontSize(24);
        doc.setFont('helvetica', 'bold');
        doc.text('CemasYa', marginX, y);
        y += 15;

        doc.setFontSize(24);
        doc.text('Laporan', doc.internal.pageSize.width / 2, y, { align: 'center' });
        y += 10;

        doc.setFontSize(14);
        doc.text('Daftar Penanganan Tingkat Kecemasan Anda', doc.internal.pageSize.width / 2, y, { align: 'center' });
        y += 20;

        // Tambahkan detail lain seperti di atas tapi tanpa logo
    };
}
</script>

<!-- Modal input nama -->
<div class="modal fade" id="nameModal" tabindex="-1" aria-labelledby="nameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="nameModalLabel">Masukkan Nama Anda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="userName" placeholder="Nama Anda" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="generatePDF()">Cetak</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>