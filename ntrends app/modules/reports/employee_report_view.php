<?php
require '../../config/db.php';

$employeeId = $_GET['employee_id'] ?? '';

$sql = "SELECT 
            e.name AS employee_name,
            COUNT(a.id) AS total_services,
            COALESCE(SUM(s.price),0) AS total_revenue
        FROM appointments a
        JOIN employees e ON a.employee_id = e.id
        JOIN services s ON a.service_id = s.id
        WHERE a.status = 'Completed'";

$params = [];

if (!empty($employeeId)) {
    $sql .= " AND e.id = ?";
    $params[] = $employeeId;
}

$sql .= " GROUP BY e.id ORDER BY total_services DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="p-4">

<h3>Employee Report</h3>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Total Services</th>
            <th>Total Revenue</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td><?= $row['total_services'] ?></td>
                <td>₹<?= number_format($row['total_revenue'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
