<?php
// modules/appointments/api.php
require '../../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action == 'fetch_dropdowns') {
    $empStmt = $pdo->query("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    $svcStmt = $pdo->query("SELECT id, service_name, price FROM services ORDER BY service_name ASC");
    $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['employees' => $employees, 'services' => $services]);
    exit;
}


// --- NEW: Filter Services by Price ---
if ($action == 'search_services_by_price') {
    $price = $_POST['price'] ?? '';
    
    // We use LIKE so if you type "5", it shows 50, 500, etc.
    // If you want exact match only, change LIKE to =
    $searchTerm = "$price%"; 

    $sql = "SELECT id, service_name, price FROM services WHERE price LIKE ? ORDER BY service_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action == 'search_clients') {
    $query = $_POST['query'] ?? '';
    $searchTerm = "%$query%";
    $sql = "SELECT DISTINCT client_phone, client_name, gender, client_type FROM appointments WHERE client_phone LIKE ? OR client_name LIKE ? GROUP BY client_phone LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action == 'fetch_counts') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');
    $sql = "SELECT COUNT(CASE WHEN a.status = 'Scheduled' THEN 1 END) as open_count,
                   COUNT(CASE WHEN a.status = 'Completed' THEN 1 END) as closed_count,
                   COALESCE(SUM(CASE WHEN a.status != 'Cancelled' THEN s.price ELSE 0 END), 0) as total_revenue
            FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.appointment_date = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

if ($action == 'fetch_by_date') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');

    // Added MAX(a.discount_percent) to ensure it is fetched correctly
    $sql = "SELECT 
                MIN(a.id) as id, 
                a.appointment_date, 
                a.appointment_time, 
                a.client_name, 
                a.client_phone, 
                a.gender, 
                a.client_type, 
                a.status,
                MAX(a.discount_percent) as discount_percent, 
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

if ($action == 'create_multi' || $action == 'save_appointment') {
    // 1. Get Services
    $services = $_POST['services'] ?? [];

    // 2. SECURE: Get Client Name (Added this line)
    // htmlspecialchars + strip_tags prevents script attacks
    $client_name = htmlspecialchars(strip_tags(trim($_POST['client_name'] ?? '')));

    // 3. SECURE: Get Phone (Numbers only)
    $phoneRaw = $_POST['client_phone'] ?? '';
    $phone = preg_replace('/[^0-9]/', '', $phoneRaw); 

    // 4. Get Date & Time
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'] ?? date('H:i');
    $discount = $_POST['discount_percent'] ?? 0;

    // 5. VALIDATION: Stop if Name or Phone is missing after cleaning
    if (empty($client_name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Name and Phone are required!']);
        exit;
    }

    if (!empty($_POST['original_phone'])) {
        $delSql = "DELETE FROM appointments WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
        $delStmt = $pdo->prepare($delSql);
        $delStmt->execute([$_POST['original_phone'], $_POST['original_date'], $_POST['original_time']]);
        $time = $_POST['original_time'];
    }

    $successCount = 0;
    $sql = "INSERT INTO appointments (appointment_date, appointment_time, client_name, client_phone, gender, client_type, employee_id, service_id, status, discount_percent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled', ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($services as $svc) {
        $result = $stmt->execute([
            $date, $time, $_POST['client_name'], $phone, 
            $_POST['gender'], $_POST['client_type'], 
            $svc['employee_id'], $svc['service_id'],
            $discount
        ]);
        if ($result) $successCount++;
    }
    
    echo json_encode(['status' => ($successCount > 0) ? 'success' : 'error']);
    exit;
}

if ($action == 'move_to_billing') {
    $sql = "UPDATE appointments SET status = 'Completed' WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$_POST['client_phone'], $_POST['appointment_date'], $_POST['appointment_time']])) {
        echo json_encode(['status' => 'success']);
    } else { echo json_encode(['status' => 'error']); }
    exit;
}

if ($action == 'fetch_billing') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');

    // Added MAX(a.discount_percent) here too
    $sql = "SELECT 
                MIN(a.id) as id, 
                a.appointment_date, 
                a.appointment_time, 
                a.client_name, 
                a.client_phone, 
                a.status, 
                MAX(a.discount_percent) as discount_percent,
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
    $sql = "DELETE FROM appointments WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$_POST['phone'], $_POST['date'], $_POST['time']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

if ($action == 'fetch_client_history') {
    $phone = $_POST['client_phone'];

    // 1. General Stats (Using s.price instead of saved_price)
    $sqlStats = "SELECT 
                    COUNT(DISTINCT CONCAT(appointment_date, appointment_time)) as visit_count, 
                    COALESCE(SUM(s.price), 0) as total_spent, 
                    MIN(appointment_date) as first_visit, 
                    MAX(appointment_date) as last_visit 
                 FROM appointments a 
                 LEFT JOIN services s ON a.service_id = s.id
                 WHERE a.client_phone = ? AND a.status = 'Completed'";
    $stmt = $pdo->prepare($sqlStats);
    $stmt->execute([$phone]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. DETAILED Last Visit Info
    $sqlLast = "SELECT 
                    a.appointment_date,
                    GROUP_CONCAT(s.service_name SEPARATOR ', ') as services,
                    SUM(s.price) as gross_total,
                    MAX(a.discount_percent) as discount_percent
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                WHERE a.client_phone = ? AND a.status = 'Completed'
                GROUP BY a.appointment_date, a.appointment_time
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT 1";
    
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([$phone]);
    $lastVisit = $stmtLast->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['stats' => $stats, 'last_visit' => $lastVisit]);
    exit;
}

if ($action == 'fetch_group_details') {
    $sql = "SELECT a.employee_id, a.service_id, s.price, a.discount_percent 
            FROM appointments a LEFT JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date = ? AND a.appointment_time = ? AND a.client_phone = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['date'], $_POST['time'], $_POST['phone']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $discount = $items[0]['discount_percent'] ?? 0;
    echo json_encode(['items' => $items, 'discount' => $discount]);
    exit;
}
?>