<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, go to login page
    // Note: Use ../../login.php because modules are 2 levels deep
    header("Location: ../../login.php");
    exit;
}
?>
<script>const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token']; ?>";</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- GLOBAL & SIDEBAR CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

    <!-- 1. INCLUDE SIDEBAR HTML -->
    <?php include '../../layout/sidebar.php'; ?>


    <!-- 2. MAIN CONTENT WRAPPER -->
    <div id="page-content-wrapper">

        <!-- Navbar with Toggle Button -->


        <!-- Page Content -->
        <div class="container-fluid p-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Header & Add Button -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><i class="fas fa-users-cog"></i> Employee List</h4>
                        <button class="btn btn-success" onclick="openModal()">
                            <i class="fas fa-plus"></i> Add New Employee
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="employeeTableBody">
                                <!-- Loaded via script.js -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. ADD/EDIT MODAL -->
    <div class="modal fade" id="employeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="employeeForm">
                        <input type="hidden" id="empId" name="id">
                        <input type="hidden" name="action" id="formAction" value="create">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="role">
                                <option value="Stylist">Stylist</option>
                                <option value="Senior Stylist">Senior Stylist</option>
                                <option value="Receptionist">Receptionist</option>
                                <option value="Helper">Helper</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="phone">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success" id="submitBtn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- FIX 1: Point to the correct folder (../../) for the sidebar logic -->
    <script src="../../assets/js/sidebar.js"></script>

    <!-- FIX 2: Keep the employee logic in its own file -->
    <script src="script.js"></script>

</body>

</html>