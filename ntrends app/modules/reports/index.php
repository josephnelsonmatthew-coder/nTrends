<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, go to login page
    // Note: Use ../../login.php because modules are 2 levels deep
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Export</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <?php include '../../layout/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 pt-4">
            
            <h4 class="mb-4 text-dark fw-bold"><i class="fas fa-chart-line me-2"></i>Reports Module</h4>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Export Client Data</h5>
                    <p class="text-muted">Download a complete history of all appointments, client details, and revenue data in Excel (CSV) format.</p>
                    
                    <form action="export.php" method="GET">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">From Date</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">To Date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="submit" class="btn btn-success w-100 py-2">
                                    <i class="fas fa-file-excel me-2"></i> Download Excel Report
                                </button>
                            </div>
                        </div>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i> Leave dates empty to download <strong>everything</strong> since the beginning.
                        </div>
                    </form>

                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                     <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                         <i class="fas fa-lightbulb fa-2x me-3"></i>
                         <div>
                             <strong>Tip:</strong> The downloaded file is in <code>.csv</code> format. You can open it directly with Microsoft Excel, Google Sheets, or Apple Numbers.
                         </div>
                     </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebar.js"></script>

</body>
</html>