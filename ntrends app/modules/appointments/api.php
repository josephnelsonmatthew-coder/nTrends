<?php
// modules/appointments/api.php
require '../../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// =========================================================
// 1. DROPDOWNS & SEARCH (Fixes "Search not working")
// =========================================================

if ($action == 'fetch_dropdowns') {
    $empStmt = $pdo->query("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

    $svcStmt = $pdo->query("SELECT id, service_name, price FROM services ORDER BY service_name ASC");
    $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['employees' => $employees, 'services' => $services]);
    exit;
}

if ($action == 'search_clients') {
    $query = $_POST['query'] ?? '';
    $searchTerm = "%$query%";

    $sql = "SELECT DISTINCT client_phone, client_name, gender, client_type 
            FROM appointments 
            WHERE client_phone LIKE ? OR client_name LIKE ? 
            GROUP BY client_phone 
            LIMIT 8";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// =========================================================
// 2. DASHBOARD & TABLE DATA
// =========================================================

if ($action == 'fetch_counts') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');
    
    $sql = "SELECT 
                COUNT(CASE WHEN a.status = 'Scheduled' THEN 1 END) as open_count,
                COUNT(CASE WHEN a.status = 'Completed' THEN 1 END) as closed_count,
                COALESCE(SUM(CASE WHEN a.status != 'Cancelled' THEN s.price ELSE 0 END), 0) as total_revenue
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

if ($action == 'fetch_by_date') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');

    // NOTE: We show 'Completed' items here so you can see they are done (Green Badge)
    $sql = "SELECT 
                MIN(a.id) as id, 
                a.appointment_date, 
                a.appointment_time, 
                a.client_name, 
                a.client_phone, 
                a.gender, 
                a.client_type, 
                a.status,
                GROUP_CONCAT(DISTINCT e.name SEPARATOR '<br>') as employee_name,
                GROUP_CONCAT(CONCAT(s.service_name, ' (₹', s.price, ')') SEPARATOR '<br>') as service_details,
                SUM(s.price) as total_price
            FROM appointments a
            LEFT JOIN employees e ON a.employee_id = e.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date = ? 
            GROUP BY a.appointment_date, a.appointment_time, a.client_name, a.client_phone
            ORDER BY a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// =========================================================
// 3. APPOINTMENT ACTIONS
// =========================================================

if ($action == 'create_multi' || $action == 'save_appointment') {
    $services = $_POST['services'] ?? [];
    $date = $_POST['appointment_date'];
    $phone = $_POST['client_phone'];
    $time = $_POST['appointment_time'] ?? date('H:i');

    if (!empty($_POST['original_phone'])) {
        $delSql = "DELETE FROM appointments WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
        $delStmt = $pdo->prepare($delSql);
        $delStmt->execute([$_POST['original_phone'], $_POST['original_date'], $_POST['original_time']]);
        $time = $_POST['original_time'];
    }

    $successCount = 0;
    $sql = "INSERT INTO appointments (appointment_date, appointment_time, client_name, client_phone, gender, client_type, employee_id, service_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')";
    $stmt = $pdo->prepare($sql);

    foreach ($services as $svc) {
        $result = $stmt->execute([
            $date, $time, $_POST['client_name'], $phone, 
            $_POST['gender'], $_POST['client_type'], 
            $svc['employee_id'], $svc['service_id']
        ]);
        if ($result) $successCount++;
    }
    
    echo json_encode(['status' => ($successCount > 0) ? 'success' : 'error']);
    exit;
}

// --- FIX: USE 'Completed' STATUS INSTEAD OF 'Billing' ---
if ($action == 'move_to_billing') {
    $client_phone = $_POST['client_phone'];
    $appt_date = $_POST['appointment_date'];
    $appt_time = $_POST['appointment_time'];

    if(!$client_phone || !$appt_date || !$appt_time) {
         echo json_encode(['status' => 'error', 'message' => 'Missing data']);
         exit;
    }

    // Set status to 'Completed' because your DB likely rejects 'Billing'
    $sql = "UPDATE appointments 
            SET status = 'Completed' 
            WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$client_phone, $appt_date, $appt_time])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// --- FIX: FETCH 'Completed' ITEMS FOR BILLING QUEUE ---
if ($action == 'fetch_billing') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');

    // We fetch items that are 'Completed' so they show up in the Billing Queue
    $sql = "SELECT 
                MIN(a.id) as id, 
                a.appointment_date, 
                a.appointment_time, 
                a.client_name, 
                a.client_phone, 
                a.gender, 
                a.client_type, 
                a.status,
                GROUP_CONCAT(DISTINCT e.name SEPARATOR '<br>') as employee_name,
                GROUP_CONCAT(CONCAT(s.service_name, ' (₹', s.price, ')') SEPARATOR '<br>') as service_details,
                SUM(s.price) as total_price
            FROM appointments a
            LEFT JOIN employees e ON a.employee_id = e.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.status = 'Completed' AND a.appointment_date = ? 
            GROUP BY a.appointment_date, a.appointment_time, a.client_name, a.client_phone
            ORDER BY a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action == 'delete_group') {
    $sql = "DELETE FROM appointments 
            WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$_POST['phone'], $_POST['date'], $_POST['time']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// =========================================================
// 4. HELPERS
// =========================================================

if ($action == 'fetch_client_history') {
    $phone = $_POST['client_phone'];

    $sqlStats = "SELECT COUNT(a.id) as visit_count, SUM(s.price) as total_spent,
                 MIN(a.appointment_date) as first_visit, MAX(a.appointment_date) as last_visit
                 FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.client_phone = ?";
    $stmt = $pdo->prepare($sqlStats);
    $stmt->execute([$phone]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlLast = "SELECT a.id, e.name as stylist, s.service_name 
                FROM appointments a JOIN employees e ON a.employee_id = e.id JOIN services s ON a.service_id = s.id
                WHERE a.client_phone = ? ORDER BY a.appointment_date DESC LIMIT 1";
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([$phone]);
    $lastBill = $stmtLast->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['stats' => $stats, 'last_bill' => $lastBill]);
    exit;
}

if ($action == 'fetch_group_details') {
    $sql = "SELECT a.employee_id, a.service_id, s.price 
            FROM appointments a LEFT JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date = ? AND a.appointment_time = ? AND a.client_phone = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['date'], $_POST['time'], $_POST['phone']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
// --- SEARCH SERVICES BY PRICE ---
// =========================================================
// SEARCH SERVICES BY PRICE (Reverse Lookup for Billing)
// =========================================================
if ($action == "search_services_by_price") {

    $price_raw = $_POST['price'] ?? '';

    // Return empty list if no price given
    if ($price_raw === '' || !is_numeric($price_raw)) {
        echo json_encode([]);
        exit;
    }

    // Normalize price
    $price = floatval($price_raw);

    // Query services with exact price match
    $sql = "SELECT id, service_name, price
            FROM services
            WHERE price = ?
            ORDER BY service_name ASC
            LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$price]);

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($services);
    exit;
}


?>