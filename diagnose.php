<?php
include 'db.php';

session_start();

if (!isset($_SESSION['symptom_index'])) {
    $_SESSION['symptom_index'] = 0;
}

if (!isset($_SESSION['selected_symptoms'])) {
    $_SESSION['selected_symptoms'] = [];
}

if (!isset($_SESSION['previous_selections'])) {
    $_SESSION['previous_selections'] = [];
}

$symptoms_sql = "SELECT * FROM symptoms";
$symptoms_result = $conn->query($symptoms_sql);
$symptoms = $symptoms_result->fetch_all(MYSQLI_ASSOC);

$total_symptoms = count($symptoms);
$symptom_index = intval($_SESSION['symptom_index']);

// Batas pertanyaan per halaman menjadi 7
$symptoms_to_display = array_slice($symptoms, $symptom_index, 7);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['next'])) {
        // Reset previous selections untuk halaman ini
        $_SESSION['previous_selections'] = [];

        // Reset selected symptoms supaya tidak ada duplikat ketika user maju ke halaman berikutnya
        // Namun, kamu mungkin ingin menyimpan gejala dari halaman sebelumnya
        // Jadi kita tidak reset, tapi tambahkan saja gejala baru
        // Tapi untuk aman, kita cek dulu, supaya tidak duplikat
        // Saya akan pakai array_unique setelah menambah

        if (isset($_POST['symptom'])) {
            foreach ($_POST['symptom'] as $index => $value) {
                $symptomCode = $symptoms[$symptom_index + $index]['code'];
                $_SESSION['previous_selections'][$symptomCode] = $value;

                // Anggap "lumayan sering" dan "sering" sebagai gejala yang dipilih
                if ($value === 'lumayan sering' || $value === 'sering') {
                    $_SESSION['selected_symptoms'][] = $symptomCode;
                }
            }
            // Hilangkan duplikat gejala
            $_SESSION['selected_symptoms'] = array_unique($_SESSION['selected_symptoms']);
        }

        $_SESSION['symptom_index'] += 7;
        $symptom_index = $_SESSION['symptom_index'];

        if ($symptom_index >= $total_symptoms) {
            $forwardChainingResult = performForwardChaining($conn, $_SESSION['selected_symptoms']);
            if ($forwardChainingResult) {
                $_SESSION['diagnosis'] = $forwardChainingResult;
                header('Location: result.php');
                exit();
            }
        }
    } elseif (isset($_POST['reset'])) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    } else {
        $forwardChainingResult = performForwardChaining($conn, $_SESSION['selected_symptoms']);
        if ($forwardChainingResult) {
            $_SESSION['diagnosis'] = $forwardChainingResult;
            header('Location: result.php');
            exit();
        }
    }

    // Slice ulang setelah submit agar sesuai index baru
    $symptoms_to_display = array_slice($symptoms, $symptom_index, 7);
}

function performForwardChaining($conn, $selected_symptoms) {
    if (empty($selected_symptoms)) return null;

    $selected_symptoms_str = "'" . implode("', '", $selected_symptoms) . "'";

    $sql = "SELECT r.id as rule_id, r.level_code, rs.symptom_code, l.name as level_name, l.suggestion
            FROM rules r
            JOIN rule_symptoms rs ON r.id = rs.rule_id
            JOIN levels l ON r.level_code = l.code";
    $result = $conn->query($sql);

    $rules = [];
    while ($row = $result->fetch_assoc()) {
        $rules[$row['rule_id']]['level_code'] = $row['level_code'];
        $rules[$row['rule_id']]['level_name'] = $row['level_name'];
        $rules[$row['rule_id']]['suggestion'] = $row['suggestion'];
        $rules[$row['rule_id']]['symptoms'][] = $row['symptom_code'];
    }

    foreach ($rules as $rule) {
        $rule_symptoms = $rule['symptoms'];
        $matched_symptoms = array_intersect($rule_symptoms, $selected_symptoms);

        if (count($matched_symptoms) == count($rule_symptoms)) {
            return [
                'name' => $rule['level_name'],
                'suggestion' => $rule['suggestion']
            ];
        }
    }

    $sql = "SELECT l.code, l.name, l.suggestion, COUNT(*) as symptom_count, 
            (COUNT(*) / (SELECT COUNT(*) FROM rule_symptoms WHERE rule_symptoms.rule_id = r.id)) as match_percentage
            FROM levels l
            JOIN rules r ON l.code = r.level_code
            JOIN rule_symptoms rs ON r.id = rs.rule_id
            WHERE rs.symptom_code IN ($selected_symptoms_str)
            GROUP BY l.code, l.name, l.suggestion, r.id
            HAVING match_percentage >= 0.8
            ORDER BY symptom_count DESC, l.name ASC
            LIMIT 1";

    $result = $conn->query($sql);
    $diagnosis = $result->fetch_assoc();

    if ($diagnosis) {
        return [
            'name' => $diagnosis['name'],
            'suggestion' => $diagnosis['suggestion']
        ];
    }

    return null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Diagnosa Tingkat Kecemasan - CemasYa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: url('cemas_bg.svg') no-repeat center center fixed;
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
            justify-content: flex-start;
            align-items: center;
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
            max-width: 600px;
            width: 100%;
            text-align: left;
        }
        h1 {
            margin-bottom: 1rem;
            font-weight: 700;
            color: #2c6fe3;
        }
        .symptom-question {
            background-color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
            display: flex;
            flex-direction: column;
        }
        .question {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .form-check-label {
            user-select: none;
            cursor: pointer;
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
        .panduan-box {
            background: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
            color: black;
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
        <h1>Diagnosa Tingkat Kecemasan</h1>
        <div class="panduan-box">
            ⚠️ <strong>Panduan Pengisian</strong><br />
            1. Pilih <strong>tidak pernah</strong>: jika gejala tidak pernah dialami<br />
            2. Pilih <strong>kadang-kadang</strong>: jika gejala kadang-kadang dialami<br />
            3. Pilih <strong>lumayan sering</strong>: jika gejala sering dialami secara cukup signifikan<br />
            4. Pilih <strong>sering</strong>: jika gejala sangat sering dialami
        </div>
        <form id="diagnosisForm" method="post" novalidate>
            <?php if (!empty($symptoms_to_display)): ?>
                <?php foreach ($symptoms_to_display as $index => $symptom): ?>
                    <?php 
                        $symptomCode = $symptom['code'];
                        $previousSelection = null;
                        if (isset($_SESSION['previous_selections'][$symptomCode])) {
                            $previousSelection = $_SESSION['previous_selections'][$symptomCode];
                        }
                    ?>
                    <div class="symptom-question">
                        <p class="question">Apakah Anda sering mengalami gejala <strong><?= htmlspecialchars($symptom['name']) ?></strong> (<?= htmlspecialchars($symptom['code']) ?>)?</p>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="symptom[<?= $index ?>]" id="symptom-never-<?= $index ?>" value="tidak pernah" required
                            <?php if ($previousSelection === 'tidak pernah') echo 'checked'; ?> />
                            <label class="form-check-label" for="symptom-never-<?= $index ?>">Tidak Pernah</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="symptom[<?= $index ?>]" id="symptom-sometimes-<?= $index ?>" value="kadang-kadang"
                            <?php if ($previousSelection === 'kadang-kadang') echo 'checked'; ?> />
                            <label class="form-check-label" for="symptom-sometimes-<?= $index ?>">Kadang-kadang</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="symptom[<?= $index ?>]" id="symptom-often-<?= $index ?>" value="lumayan sering"
                            <?php if ($previousSelection === 'lumayan sering') echo 'checked'; ?> />
                            <label class="form-check-label" for="symptom-often-<?= $index ?>">Lumayan Sering</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="symptom[<?= $index ?>]" id="symptom-very-often-<?= $index ?>" value="sering"
                            <?php if ($previousSelection === 'sering') echo 'checked'; ?> />
                            <label class="form-check-label" for="symptom-very-often-<?= $index ?>">Sering</label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between">
                    <button type="submit" name="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" name="next" class="btn btn-primary">Next</button>
                </div>
            <?php else: ?>
                <p>Tidak ada gejala yang tersedia.</p>
                <div class="d-flex justify-content-center">
                    <form method="post">
                        <button type="submit" name="reset" class="btn btn-secondary">Reset</button>
                    </form>
                </div>
            <?php endif; ?>
        </form>
    </main>
</div>

<footer>
    <p>CemasYa - <a href="index.php">Jangan Abaikan Kecemasan Anda</a> &copy; 2025</p>
</footer>

<script>
document.getElementById('diagnosisForm').addEventListener('submit', function(event) {
    const symptomCount = <?= count($symptoms_to_display) ?>;
    for (let i = 0; i < symptomCount; i++) {
        const radios = document.getElementsByName(`symptom[${i}]`);
        let checked = false;
        for (const radio of radios) {
            if (radio.checked) {
                checked = true;
                break;
            }
        }
        if (!checked) {
            alert('Semua pertanyaan harus dijawab sebelum melanjutkan.');
            event.preventDefault();
            return false;
        }
    }
});
</script>
</body>
</html>
