<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Scheduling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .dash-card { border-radius: 15px; color: white; overflow: hidden; position: relative; }
        .bg-teal { background: linear-gradient(135deg, #20c997, #0cb285); }
        .bg-pink { background: linear-gradient(135deg, #e83e8c, #d63384); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .card-icon-bg { position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.2; transform: rotate(-15deg); }
        
        /* Search Styles */
        #customerSearchSection { display: none; background: #f8f9fa; border-radius: 12px; border: 2px dashed #dee2e6; }
        .suggestions-list { position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,.15); border-radius: 0 0 .375rem .375rem; }
        .suggestion-item { cursor: pointer; padding: 10px 15px; background: #fff; border-bottom: 1px solid #eee; transition: background 0.2s; }
        .suggestion-item:hover { background-color: #f1f3f5; }
        #selectedCustomerCard { display: none; background: #fff; border-radius: 12px; border: 1px solid #e9ecef; }
        .avatar-circle { width: 60px; height: 60px; background-color: #4f46e5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; }
        
        /* Payment Modal Styles */
        .btn-indigo { background-color: #4f46e5; color: white; }
        .btn-indigo:hover { background-color: #4338ca; color: white; }
    </style>
</head>
<body>

    <?php include '../../layout/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <div class="container-fluid px-4 pt-4">
            
            <div id="mainDashboardView">
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card dash-card bg-teal shadow-sm h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                               <div><h6 class="text-uppercase mb-1">Open Today</h6><h2 class="mb-0 fw-bold" id="countOpen">0</h2></div>
                               <i class="far fa-clock fa-3x"></i>
                            </div>
                            <i class="far fa-clock card-icon-bg"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card dash-card bg-pink shadow-sm h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                               <div><h6 class="text-uppercase mb-1">Closed/Done Today</h6><h2 class="mb-0 fw-bold" id="countClosed">0</h2></div>
                               <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                            <i class="fas fa-check-circle card-icon-bg"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card dash-card bg-blue shadow-sm h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                               <div><h6 class="text-uppercase mb-1">Total Revenue</h6><h2 class="mb-0 fw-bold" id="countRevenue">₹0.00</h2></div>
                               <i class="fas fa-rupee-sign fa-3x"></i>
                            </div>
                            <i class="fas fa-wallet card-icon-bg"></i>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center bg-white rounded border p-1">
                            <span class="input-group-text border-0 bg-transparent"><i class="fas fa-calendar-alt text-muted"></i></span>
                            <input type="date" id="dateFilter" class="form-control border-0 shadow-none" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button class="btn btn-primary px-4 py-2" onclick="toggleSearchSection()">
                            <i class="fas fa-plus-circle me-2"></i> Book an Appointment
                        </button>
                    </div>
                </div>

                <div id="customerSearchSection" class="mb-4 p-4">
                    <div class="row">
                        <div class="col-md-5 position-relative">
                            <label class="form-label fw-bold text-muted">Search Customer (Name or Phone)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="customerSearchInput" class="form-control form-control-lg" placeholder="Start typing..." autocomplete="off">
                                 <button class="btn btn-outline-primary" onclick="openModalForNew()">New</button>
                            </div>
                            <div id="suggestionsList" class="suggestions-list list-group d-none"></div>
                        </div>
                        <div class="col-md-7">
                            <div id="selectedCustomerCard" class="p-3 shadow-sm d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><i class="fas fa-user"></i></div>
                                    <div>
                                        <h5 class="mb-1 fw-bold" id="cardCustName">--</h5>
                                        <div class="text-muted mb-1"><i class="fas fa-phone-alt me-2"></i><span id="cardCustPhone">--</span></div>
                                        <div class="d-flex gap-2">
                                             <span class="badge bg-light text-dark border" id="cardCustGender">--</span>
                                             <span class="badge bg-info text-white" id="cardCustType">--</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-lg px-4" id="btnContinueToBook" disabled>
                                        <i class="fas fa-plus me-2"></i> Add Appointment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Appt ID</th><th>Time</th><th>Client Name</th><th>Role/Type</th><th>Stylist</th><th>Service & Price</th><th>Status</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="apptTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="billingView" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-primary"><i class="fas fa-cash-register me-2"></i>Edit Services / Billing</h4>
                    <button class="btn btn-secondary" onclick="closeBillingView()">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-9">
                         <div class="card shadow-sm border-0 mb-4 bg-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-4 d-flex align-items-center border-end">
                                        <div class="avatar-circle me-3 bg-success text-white" style="width:50px; height:50px; font-size:20px;"><i class="fas fa-user"></i></div>
                                        <div><h5 class="mb-0 fw-bold" id="advCustName">Name</h5><div class="text-muted" id="advCustPhone">--</div></div>
                                    </div>
                                    <div class="col-md-3 border-end">
                                        <label class="small text-muted fw-bold">Date</label>
                                        <input type="date" class="form-control form-control-sm border-0 bg-transparent fw-bold p-0" id="advDate" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <div class="text-end">
                                            <small class="text-muted d-block">Total Amount</small>
                                            <span class="fw-bold text-primary fs-3" id="headerNet">₹0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-list me-2"></i>Services Cart</span>
                                <button class="btn btn-sm btn-success" onclick="addNewServiceRow()"><i class="fas fa-plus me-1"></i> Add Row</button>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered align-middle mb-0" id="serviceCartTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 30%;">Staff</th>
                                            <th style="width: 40%;">Service</th> <th style="width: 15%;">Price</th> <th style="width: 10%;">Total</th> <th style="width: 5%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="serviceCartBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                         <div class="card shadow-sm mb-3 border-top border-4 border-primary">
                            <div class="card-header bg-white fw-bold"><i class="fas fa-history me-2 text-primary"></i>History</div>
                            <div class="card-body p-3 small">
                                <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span class="text-muted">Visits:</span><span class="fw-bold text-danger" id="histVisits">--</span></div>
                                <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span class="text-muted">First:</span><span class="fw-bold" id="histFirst">--</span></div>
                                <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span class="text-muted">Last:</span><span class="fw-bold" id="histLast">--</span></div>
                                <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span class="text-muted">Total:</span><span class="fw-bold text-success" id="histSpent">₹0</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 p-3 bg-white shadow-sm rounded">
                    <button type="button" class="btn btn-danger me-2 px-4" onclick="closeBillingView()"><i class="fas fa-times me-2"></i> Cancel</button>
                    <button type="button" class="btn btn-success px-5" id="btnSaveBooking" onclick="saveAdvancedBooking()"><i class="fas fa-save me-2"></i> Save Changes</button>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="apptModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Book New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="apptForm">
                        <input type="hidden" id="apptId" name="id">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="form-label">Date</label><input type="date" class="form-control" name="appointment_date" id="apptDate" required value="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Time</label><input type="time" class="form-control" name="appointment_time" id="apptTime" required></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="form-label">Name</label><input type="text" class="form-control" name="client_name" id="clientName" required></div>
                            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="client_phone" id="clientPhone"></div>
                        </div>
                         <div class="row mb-3">
                             <div class="col-md-6"><label class="form-label">Gender</label><select class="form-select" name="gender" id="clientGender" required><option value="Male">Male</option><option value="Female">Female</option></select></div>
                            <div class="col-md-6"><label class="form-label">Type</label><select class="form-select" name="client_type" id="clientType" required><option value="Outsider">Outsider</option><option value="Student">Student</option><option value="Faculty">Faculty</option></select></div>
                        </div>
                        <div class="mb-4" id="statusDiv" style="display:none;">
                             <label class="form-label">Status</label>
                             <select class="form-select fw-bold" name="status" id="apptStatus">
                                 <option value="Scheduled">Scheduled</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option>
                             </select>
                        </div>
                        <div class="d-grid text-end"><button type="submit" class="btn btn-primary px-5" id="submitBtn">Confirm Booking</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveBillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-indigo text-white">
                     <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Finalize Bill</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body font-monospace">
                    <div class="mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-muted mb-1">Customer Details</h6>
                        <div id="billClientName" class="fw-bold fs-5"></div>
                        <div id="billClientPhone" class="text-muted small"></div>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold text-muted mb-2">Services Summary</h6>
                        <div id="billServicesList" class="ps-2"></div>
                    </div>
                    <div class="alert alert-success d-flex justify-content-between align-items-center mt-3 mb-0">
                        <span class="fw-bold">Grand Total To Pay:</span>
                        <span id="billGrandTotal" class="fs-4 fw-bold"></span>
                    </div>
                    <div id="paymentOptionsSection" class="mt-4" style="display:none;">
                        <h6 class="text-center text-muted mb-3">- Select Payment Method -</h6>
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-outline-dark p-3 d-flex align-items-center gap-2" id="gpayBtn" style="border-radius: 12px;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/2560px-Google_Pay_Logo.svg.png" alt="GPay" height="24">
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <input type="hidden" id="hiddenBillPhone">
                    <input type="hidden" id="hiddenBillDate">
                    <input type="hidden" id="hiddenBillTime">
                    <button type="button" class="btn btn-indigo text-white w-100 py-2" id="btnGenerateBillStep">
                        <i class="fas fa-cog me-2"></i>Generate Final Bill
                    </button>
                    <button type="button" class="btn btn-success w-100 py-2" id="btnFinalCheckout" style="display:none;">
                        <i class="fas fa-check-circle me-2"></i>Confirm Checkout & Move
                    </button>
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