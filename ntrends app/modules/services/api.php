<?php
// modules/services/api.php
require '../../config/db.php'; // Connection to database

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// 1. READ (Fetch All Services)
if ($action == 'fetch') {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 2. CREATE (Add Service)
if ($action == 'create') {
    $sql = "INSERT INTO services (service_name, price) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$_POST['service_name'], $_POST['price']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 3. UPDATE (Edit Service)
if ($action == 'update') {
    $sql = "UPDATE services SET service_name=?, price=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['service_name'], 
        $_POST['price'], 
        $_POST['id']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 4. DELETE (Remove Service)
if ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $result = $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}
?>