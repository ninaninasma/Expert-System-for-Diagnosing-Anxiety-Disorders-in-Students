<?php
require '../db.php';

// Fungsi untuk mengambil data dari tabel dengan pagination
function fetchData($table, $limit, $offset) {
    global $conn;
    $sql = "SELECT * FROM $table LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchAllData($table) {
    global $conn;
    $sql = "SELECT * FROM $table";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk menambah aturan (rule)
function addRule($level_code, $symptom_codes) {
    global $conn;
    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO rules (level_code) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $level_code);
        $stmt->execute();
        $ruleId = $stmt->insert_id;

        $symptom_codes_array = explode(',', $symptom_codes);
        foreach ($symptom_codes_array as $symptom_code) {
            $symptom_code = trim($symptom_code);
            $sql = "INSERT INTO rule_symptoms (rule_id, symptom_code) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $ruleId, $symptom_code);
            $stmt->execute();
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

// Fungsi untuk memperbarui aturan (rule)
function updateRule($id, $level_code, $symptom_codes) {
    global $conn;
    $conn->begin_transaction();
    try {
        $sql = "UPDATE rules SET level_code = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $level_code, $id);
        $stmt->execute();

        $sql = "DELETE FROM rule_symptoms WHERE rule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $symptom_codes_array = explode(',', $symptom_codes);
        foreach ($symptom_codes_array as $symptom_code) {
            $symptom_code = trim($symptom_code);
            $sql = "INSERT INTO rule_symptoms (rule_id, symptom_code) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $id, $symptom_code);
            $stmt->execute();
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

// Fungsi untuk menghapus aturan (rule)
function deleteRule($id) {
    global $conn;
    $conn->begin_transaction();
    try {
        $sql = "DELETE FROM rule_symptoms WHERE rule_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $sql = "DELETE FROM rules WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return $e->getMessage();
    }
}

// Fungsi untuk mengambil data aturan dengan gejala
function fetchDataWithSymptoms($limit, $offset) {
    global $conn;
    $sql = "
        SELECT r.id, r.level_code, GROUP_CONCAT(rs.symptom_code SEPARATOR ', ') AS symptoms
        FROM rules r
        LEFT JOIN rule_symptoms rs ON r.id = rs.rule_id
        GROUP BY r.id, r.level_code
        LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan total baris dalam tabel
function getTotalRows($table) {
    global $conn;
    $sql = "SELECT COUNT(*) FROM $table";
    $result = $conn->query($sql);
    $row = $result->fetch_row();
    return $row[0];
}

// Fungsi untuk menambah gejala
function addSymptom($code, $name) {
    global $conn;
    $sql = "INSERT INTO symptoms (code, name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $code, $name);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk memperbarui gejala
function updateSymptom($id, $code, $name) {
    global $conn;
    $sql = "UPDATE symptoms SET code = ?, name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $code, $name, $id);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk menghapus gejala
function deleteSymptom($id) {
    global $conn;
    $sql = "DELETE FROM symptoms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk menambah kecemasan (level)
function addLevel($code, $name, $suggestion) {
    global $conn;
    $sql = "INSERT INTO levels (code, name, suggestion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $code, $name, $suggestion);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk memperbarui kecemasan (level)
function updateLevel($id, $code, $name, $suggestion) {
    global $conn;
    $sql = "UPDATE levels SET code = ?, name = ?, suggestion = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $code, $name, $suggestion, $id);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk menghapus kecemasan (level)
function deleteLevel($id) {
    global $conn;
    $sql = "DELETE FROM levels WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk menambah pengguna
function addUser($username, $password) {
    global $conn;
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $password);
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk memperbarui pengguna
function updateUser($id, $username, $password = null) {
    global $conn;
    if ($password) {
        $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $username, $password, $id);
    } else {
        $sql = "UPDATE users SET username = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $username, $id);
    }
    return $stmt->execute() ? true : $stmt->error;
}

// Fungsi untuk menghapus pengguna
function deleteUser($id) {
    global $conn;
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute() ? true : $stmt->error;
}
?>
