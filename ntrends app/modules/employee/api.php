<?php
// modules/employee/api.php
require '../../config/security.php';
require '../../config/db.php'; // Connection to database

header('Content-Type: application/json');

// Security Checks
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
verify_csrf_token($_POST['csrf_token'] ?? '');

$action = $_POST['action'] ?? '';

// 1. READ (Fetch All Employees)
if ($action == 'fetch') {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 2. CREATE (Add New Employee)
if ($action == 'create') {
    $sql = "INSERT INTO employees (name, phone, role, status) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['role'],
        $_POST['status']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 3. UPDATE (Edit Employee)
if ($action == 'update') {
    $sql = "UPDATE employees SET name=?, phone=?, role=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['role'],
        $_POST['status'],
        $_POST['id']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 4. DELETE (Remove Employee)
if ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $result = $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}
?>