<?php
require '../../config/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

/* ==============================
   EMPLOYEE BASED REPORT (WITH FILTER)
================================ */
if ($action === 'employee_report') {

    $employeeId = $_POST['employee_id'] ?? '';

    $sql = "SELECT 
                e.id AS employee_id,
                e.name AS employee_name,
                COUNT(a.id) AS total_services,
                COALESCE(SUM(s.price),0) AS total_revenue
            FROM appointments a
            JOIN employees e ON a.employee_id = e.id
            JOIN services s ON a.service_id = s.id
            WHERE a.status = 'Completed'";

    $params = [];

    // Apply filter only if employee selected
    if (!empty($employeeId)) {
        $sql .= " AND e.id = ?";
        $params[] = $employeeId;
    }

    $sql .= " GROUP BY e.id ORDER BY total_services DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}


/* ==============================
   SERVICE BASED REPORT
================================ */
if ($action === 'service_report') {

    $sql = "SELECT 
                s.id AS service_id,
                s.service_name,
                COUNT(a.id) AS total_count,
                SUM(s.price) AS total_revenue
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.status = 'Completed'
            GROUP BY s.id
            ORDER BY total_count DESC";

    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
