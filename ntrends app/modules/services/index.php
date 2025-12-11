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
    <title>Service Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <?php include '../../layout/sidebar.php'; ?>

    <div id="page-content-wrapper">
        

        <div class="container-fluid p-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-cut"></i> Service List</h4>
                        <button class="btn btn-success" onclick="openModal()">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Service Name</th>
                                    <th>Price (₹)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="serviceTableBody">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="serviceForm">
                        <input type="hidden" id="serviceId" name="id">
                        <input type="hidden" name="action" id="formAction" value="create">

                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" name="service_name" id="serviceName" required placeholder="e.g. Haircut">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="servicePrice" required placeholder="0.00">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success" id="submitBtn">Save Service</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/sidebar.js"></script>
    <script src="script.js"></script>

</body>
</html>