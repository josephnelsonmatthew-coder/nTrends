<?php
// modules/reports/export.php
require '../../config/db.php';

// 1. Get Filters (Defaults to All Time if empty)
$startDate = $_GET['start_date'] ?? null;
$endDate   = $_GET['end_date'] ?? null;

// 2. Build SQL Query
$sql = "SELECT 
            a.id, 
            a.appointment_date, 
            a.appointment_time, 
            a.client_name, 
            a.client_phone, 
            a.gender, 
            a.client_type, 
            e.name as stylist, 
            s.service_name, 
            s.price, 
            a.status 
        FROM appointments a
        JOIN employees e ON a.employee_id = e.id
        JOIN services s ON a.service_id = s.id";

$params = [];

// Apply Date Filter if provided
if ($startDate && $endDate) {
    $sql .= " WHERE a.appointment_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY a.appointment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Set Headers to Force Download as CSV (Excel readable)
$filename = "salon_report_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 4. Open Output Stream
$output = fopen('php://output', 'w');

// 5. Add Column Headers
fputcsv($output, [
    'Appointment ID', 
    'Date', 
    'Time', 
    'Client Name', 
    'Phone Number', 
    'Gender', 
    'Client Type', 
    'Stylist', 
    'Service', 
    'Price', 
    'Status'
]);

// 6. Loop and Add Data Rows
foreach ($data as $row) {
    fputcsv($output, $row);
}

// 7. Close Stream
fclose($output);
exit;
?>