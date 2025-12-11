<?php
// modules/appointments/api.php
require '../../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';


// --- NEW: Fetch Customer Stats & History ---
if ($action == 'fetch_client_history') {
    $phone = $_POST['client_phone'];

    // 1. Basic Stats (Visits & Total Spend)
    // Note: We join services to sum the price
    $sqlStats = "SELECT 
                    COUNT(a.id) as visit_count, 
                    SUM(s.price) as total_spent,
                    MIN(a.appointment_date) as first_visit,
                    MAX(a.appointment_date) as last_visit
                 FROM appointments a
                 JOIN services s ON a.service_id = s.id
                 WHERE a.client_phone = ?";
    $stmt = $pdo->prepare($sqlStats);
    $stmt->execute([$phone]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Last Bill Details
    $sqlLast = "SELECT 
                    a.id, a.appointment_date, e.name as stylist, s.service_name 
                FROM appointments a
                JOIN employees e ON a.employee_id = e.id
                JOIN services s ON a.service_id = s.id
                WHERE a.client_phone = ? 
                ORDER BY a.appointment_date DESC LIMIT 1";
    $stmtLast = $pdo->prepare($sqlLast);
    $stmtLast->execute([$phone]);
    $lastBill = $stmtLast->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['stats' => $stats, 'last_bill' => $lastBill]);
    exit;
}// --- UPDATE: Create (Modified to handle multiple service rows) ---


// --- NEW CODE: PASTE THIS HERE ---
// --- FIX: Smart Save (Handles both Create AND Update) ---
if ($action == 'save_appointment') {
    $services = $_POST['services'] ?? [];
    $phone = $_POST['client_phone'];
    $date = $_POST['appointment_date'];
    
    // Default Time: Current time (for new bookings)
    $bookingTime = $_POST['appointment_time'] ?? date('H:i');

    // 1. If Updating: DELETE the old records first
    // We check if 'original_phone' was sent from JavaScript
    if (!empty($_POST['original_phone'])) {
        $oldPhone = $_POST['original_phone'];
        $oldDate = $_POST['original_date'];
        $oldTime = $_POST['original_time'];

        // Delete the old group of services
        $delSql = "DELETE FROM appointments WHERE client_phone = ? AND appointment_date = ? AND appointment_time = ?";
        $delStmt = $pdo->prepare($delSql);
        $delStmt->execute([$oldPhone, $oldDate, $oldTime]);

        // IMPORTANT: Reuse the original time so the appointment stays in the same slot
        $bookingTime = $oldTime;
    }

    // 2. Insert the NEW records (Clean slate)
    $successCount = 0;
    $sql = "INSERT INTO appointments (appointment_date, appointment_time, client_name, client_phone, gender, client_type, employee_id, service_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')";
    $stmt = $pdo->prepare($sql);

    foreach ($services as $svc) {
        $result = $stmt->execute([
            $date, 
            $bookingTime, // Uses old time if updating, current time if new
            $_POST['client_name'], 
            $phone, 
            $_POST['gender'], 
            $_POST['client_type'], 
            $svc['employee_id'], 
            $svc['service_id']
        ]);
        if ($result) $successCount++;
    }
    
    echo json_encode(['status' => ($successCount > 0) ? 'success' : 'error']);
    exit;
}
// --- HELPER: Fetch Dropdown Data (Employees & Services) ---


if ($action == 'fetch_dropdowns') {
    // Fetch Active Employees
    $empStmt = $pdo->query("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Services
    $svcStmt = $pdo->query("SELECT id, service_name, price FROM services ORDER BY service_name ASC");
    $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['employees' => $employees, 'services' => $services]);
    exit;
}
// --- NEW: Search Existing Clients for Autocomplete ---
if ($action == 'search_clients') {
    $query = $_POST['query'] ?? '';
    $searchTerm = "%$query%";

    // We use DISTINCT and GROUP BY phone to get unique customers from past appointments
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

// --- HELPER: Fetch Dashboard Counts for a specific date ---
// --- HELPER: Fetch Dashboard Counts for a specific date ---
if ($action == 'fetch_counts') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');
    
    // Updated Query: Joins services table to calculate Total Revenue
    // We count Scheduled/Completed separately
    // We sum Price for everything NOT Cancelled
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

    // We use GROUP_CONCAT to combine multiple services into one string
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
            WHERE a.appointment_date = ? AND a.status != 'Billing'  /* <--- CHANGED THIS LINE */
            GROUP BY a.appointment_date, a.appointment_time, a.client_name, a.client_phone
            ORDER BY a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 2. CREATE (Book Appointment)
if ($action == 'create') {
    $sql = "INSERT INTO appointments (appointment_date, appointment_time, client_name, client_phone, gender, client_type, employee_id, service_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['appointment_date'], $_POST['appointment_time'], $_POST['client_name'], 
        $_POST['client_phone'], $_POST['gender'], $_POST['client_type'], 
        $_POST['employee_id'], $_POST['service_id']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 3. UPDATE (Edit Appointment Details & Status)
if ($action == 'update') {
    $sql = "UPDATE appointments SET appointment_date=?, appointment_time=?, client_name=?, client_phone=?, gender=?, client_type=?, employee_id=?, service_id=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['appointment_date'], $_POST['appointment_time'], $_POST['client_name'], 
        $_POST['client_phone'], $_POST['gender'], $_POST['client_type'], 
        $_POST['employee_id'], $_POST['service_id'], $_POST['status'], $_POST['id']
    ]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// 4. DELETE (Cancel Appointment)
if ($action == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
    $result = $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// --- NEW: Fetch Details for a Grouped Appointment ---
if ($action == 'fetch_group_details') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $phone = $_POST['phone'];

    // Select the individual service rows for this specific client & time
    $sql = "SELECT a.id, a.employee_id, a.service_id, s.price 
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.appointment_date = ? 
            AND a.appointment_time = ? 
            AND a.client_phone = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date, $time, $phone]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- FIX: Delete the Entire Group (All services for that visit) ---
if ($action == 'delete_group') {
    // We delete based on Date, Time, and Phone to remove the whole "Bundle"
    $sql = "DELETE FROM appointments 
            WHERE client_phone = ? 
            AND appointment_date = ? 
            AND appointment_time = ?";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['phone'], 
        $_POST['date'], 
        $_POST['time']
    ]);
    
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// --- ACTION: Move Appointment to Billing Page ---
if ($action == 'move_to_bill') {
    $sql = "UPDATE appointments SET status = 'Billing' 
            WHERE client_phone = ? 
            AND appointment_date = ? 
            AND appointment_time = ?";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['phone'], 
        $_POST['date'], 
        $_POST['time']
    ]);
    
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// --- ACTION: Fetch Only Billed Items ---
if ($action == 'fetch_billing') {
    $date = $_POST['date_filter'] ?? date('Y-m-d');

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
            WHERE a.status = 'Billing' AND a.appointment_date = ? /* <--- SHOWS ONLY BILLING */
            GROUP BY a.appointment_date, a.appointment_time, a.client_name, a.client_phone
            ORDER BY a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>