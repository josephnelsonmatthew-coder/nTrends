<?php
require '../../config/security.php';
require '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Service Based Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <div class="container-fluid px-4 py-4">

        <h4 class="mb-4 fw-bold">
            <i class="fas fa-concierge-bell me-2"></i>Service Based Report
        </h4>

        <div class="card shadow-sm">
            <div class="card-body">

                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Service Name</th>
                            <th>Times Done</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                            <td>₹" . number_format($row['total_revenue'], 2) . "</td>
                          </tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>
        </div>

    </div>

</body>

</html>