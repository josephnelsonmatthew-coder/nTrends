<?php
// modules/inventory/api.php
require '../../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// 1. READ (Fetch Inventory)
if ($action == 'fetch') {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY id DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 2. CREATE (Add Product)
if ($action == 'create') {
    $sql = "INSERT INTO inventory (product_name, quantity, price) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['product_name'], 
        $_POST['quantity'], 
        $_POST['price']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 3. UPDATE (Edit Product)
if ($action == 'update') {
    $sql = "UPDATE inventory SET product_name=?, quantity=?, price=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['product_name'], 
        $_POST['quantity'], 
        $_POST['price'], 
        $_POST['id']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 4. DELETE (Remove Product)
if ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    $result = $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}
?>