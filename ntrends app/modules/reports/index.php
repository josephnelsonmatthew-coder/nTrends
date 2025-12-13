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

        <h4 class="mb-4 text-dark fw-bold">
            <i class="fas fa-chart-line me-2"></i>Reports Module
        </h4>

        <!-- ================= EXPORT CLIENT DATA ================= -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">Export Client Data</h5>
                <p class="text-muted">
                    Download a complete history of all appointments, client details,
                    and revenue data in Excel (CSV) format.
                </p>

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
                        <i class="fas fa-info-circle me-1"></i>
                        Leave dates empty to download <strong>everything</strong>.
                    </div>
                </form>
            </div>
        </div>

        <!-- INFO -->
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mt-4">
            <i class="fas fa-lightbulb fa-2x me-3"></i>
            <div>
                <strong>Tip:</strong> The downloaded file is in <code>.csv</code> format.
            </div>
        </div>

        <hr class="my-4">

        <!-- ================= REPORTS SECTION ================= -->
        <div class="row g-4">

            <!-- EMPLOYEE BASED REPORT -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="mb-3">Employee Based Report</h6>

                        <div class="mb-3">
                            <select id="employeeFilter" class="form-select w-100">
                                <option value="">All Employees</option>
                            </select>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button id="btnEmployeeReport" class="btn btn-primary">
                                View Report
                            </button>

                            <a id="downloadEmployeeExcel"
                               href="export.php?type=employee"
                               class="btn btn-success">
                               Download Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SERVICE BASED REPORT -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="mb-3">Service Based Report</h6>

                        <div class="d-flex flex-wrap gap-2">
                            <button id="btnServiceReport" class="btn btn-primary">
                                View Report
                            </button>

                            <a href="export.php?type=service"
                               class="btn btn-success">
                               Download Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- (OPTIONAL legacy table, if still needed) -->
        <table class="table table-bordered mt-4">
            <thead id="reportHead"></thead>
            <tbody id="reportBody"></tbody>
        </table>

    </div>
</div>





    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebar.js"></script>
    <script src="script.js"></script>


</body>
</html>