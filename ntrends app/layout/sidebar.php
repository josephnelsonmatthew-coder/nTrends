<div class="d-flex" id="wrapper">
    <div id="sidebar-wrapper" class="d-flex flex-column">

        <div class="sidebar-heading d-flex align-items-center">
            <button id="sidebar-toggle" class="btn btn-sm btn-outline-light">
                <i class="fas fa-bars"></i>
            </button>

            <span class="brand-text">nTrends</span>
        </div>

        <div class="list-group list-group-flush">
            <a href="../appointments/index.php" class="list-group-item list-group-item-action" title="Appointments">
                <i class="fas fa-calendar-alt fa-fw me-2"></i> <span class="link-text">Appointments</span>
            </a>
            <a href="../billing/index.php" class="list-group-item list-group-item-action" title="Billing">
                <i class="fas fa-file-invoice-dollar fa-fw me-2"></i> <span class="link-text">Billing</span>
            </a>
            <a href="../employee/index.php" class="list-group-item list-group-item-action" title="Employees">
                <i class="fas fa-users fa-fw me-2"></i> <span class="link-text">Employees</span>
            </a>
            <a href="../inventory/index.php" class="list-group-item list-group-item-action" title="Inventory">
                <i class="fas fa-boxes fa-fw me-2"></i> <span class="link-text">Inventory</span>
            </a>
            <a href="../services/index.php" class="list-group-item list-group-item-action" title="Services">
                <i class="fas fa-cut fa-fw me-2"></i> <span class="link-text">Services</span>
            </a>
            <a href="../reports/index.php" class="list-group-item list-group-item-action" title="Reports">
                <i class="fas fa-chart-line fa-fw me-2"></i> <span class="link-text">Reports</span>
            </a>
        </div>

        <div class="mt-auto p-3">
            <form action="../../logout.php" method="POST" id="logoutForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit"
                    class="btn btn-danger w-100 d-flex align-items-center justify-content-center text-white"
                    style="border-radius: 8px;">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <span class="link-text font-weight-bold">Logout</span>
                </button>
            </form>
        </div>

    </div>
</div>