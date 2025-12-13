<?php
// modules/reports/export.php
require '../../config/db.php';

// Get report type
$type = $_GET['type'] ?? '';
$type = in_array($type, ['employee', 'service']) ? $type : 'employee';

// Excel headers
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename={$type}_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1' cellpadding='6'>";

/* =====================================================
   EMPLOYEE BASED REPORT
===================================================== */
if ($type === 'employee') {

    $employeeId = $_GET['employee_id'] ?? '';

    echo "<tr style='font-weight:bold; background:#f2f2f2;'>
            <th>Date</th>
            <th>Employee Name</th>
            <th>Service Name</th>
            <th>Service Price</th>
            <th>Total Revenue (Employee)</th>
          </tr>";

    $sql = "SELECT 
                a.appointment_date,
                e.name AS employee_name,
                s.service_name,
                s.price AS service_price,
                totals.total_revenue
            FROM appointments a
            JOIN employees e ON a.employee_id = e.id
            JOIN services s ON a.service_id = s.id

            /* Subquery to calculate total revenue per employee */
            JOIN (
                SELECT 
                    employee_id,
                    SUM(s2.price) AS total_revenue
                FROM appointments a2
                JOIN services s2 ON a2.service_id = s2.id
                WHERE a2.status = 'Completed'
                GROUP BY employee_id
            ) totals ON totals.employee_id = e.id

            WHERE a.status = 'Completed'";

    $params = [];

    // ✅ Filter by specific employee if selected
    if (!empty($employeeId)) {
        $sql .= " AND e.id = ?";
        $params[] = $employeeId;
    }

    $sql .= " ORDER BY e.name ASC, a.appointment_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['appointment_date']}</td>
                <td>{$row['employee_name']}</td>
                <td>{$row['service_name']}</td>
                <td>{$row['service_price']}</td>
                <td>{$row['total_revenue']}</td>
              </tr>";
    }
}




/* =====================================================
   SERVICE BASED REPORT (MOST DONE SERVICES)
===================================================== */
if ($type === 'service') {

    echo "<tr style='font-weight:bold; background:#f2f2f2;'>
            <th>Service Name</th>
            <th>Times Done</th>
            <th>Total Revenue</th>
          </tr>";

    $sql = "SELECT 
                s.service_name,
                COUNT(a.id) AS total_count,
                COALESCE(SUM(s.price),0) AS total_revenue
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.status = 'Completed'
            GROUP BY s.id
            ORDER BY total_count DESC";

    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['service_name']}</td>
                <td>{$row['total_count']}</td>
                <td>{$row['total_revenue']}</td>
              </tr>";
    }
}

echo "</table>";
exit;
