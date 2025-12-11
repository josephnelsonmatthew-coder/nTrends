// --- GLOBAL VARIABLES ---
let allAppointments = [];
let selectedCustomerData = null; 
let serviceOptionsHTML = ''; 
let employeeOptionsHTML = '';
let editingState = null;        // Tracks if we are editing an existing appointment
let tempBookingTime = '';       // Temporarily stores time from the modal

$(document).ready(function() {
    // 1. Initial Load
    let today = $('#dateFilter').val();
    loadDashboardAndTable(today);
    
    // Load dropdowns immediately
    loadDropdownData(); 

    // 2. Handle Date Filter Change
    $('#dateFilter').on('change', function() {
        let selectedDate = $(this).val();
        loadDashboardAndTable(selectedDate);
    });

    // 3. Handle Form Submit (Intercept "Confirm Booking")
    $('#apptForm').submit(function(e) {
        e.preventDefault();
        
        let action = $('#formAction').val();

        // SCENARIO 1: NEW BOOKING -> Go to Billing View
        if(action === 'create') {
            // A. Capture Data from the Modal
            let name = $('#clientName').val();
            let phone = $('#clientPhone').val();
            let gender = $('#clientGender').val();
            let type = $('#clientType').val();
            let date = $('#apptDate').val();
            let time = $('#apptTime').val(); 

            // B. Validate
            if(!name || !date || !time) {
                Swal.fire('Error', 'Please fill in Name, Date, and Time', 'warning');
                return;
            }

            // C. Store Data Globally
            selectedCustomerData = {
                client_name: name,
                client_phone: phone,
                gender: gender,
                client_type: type
            };
            
            // Save the time to use later when clicking "Save"
            tempBookingTime = time; 
            
            // Clear Edit State (since this is a new booking)
            editingState = null;

            // D. Populate Billing View UI
            $('#advCustName').text(name);
            $('#advCustPhone').text(phone);
            $('#advDate').val(date);
            
            // E. Reset Cart (Start fresh)
            $('#serviceCartBody').html('');
            addNewServiceRow(); 
            
            // F. Load History
            if(phone) loadCustomerHistory(phone);

            // G. Switch Views
            $('#apptModal').modal('hide'); 
            openBillingView(); 

        } 
        // SCENARIO 2: EDITING EXISTING DETAILS (Blue Button) -> Update DB Immediately
        else {
            let $btn = $('#submitBtn');
            let originalText = $btn.text();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');

            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        $('#apptModal').modal('hide');
                        loadDashboardAndTable($('#dateFilter').val());
                        Swal.fire('Success', 'Updated successfully!', 'success');
                    } else {
                        Swal.fire('Error', 'Operation failed.', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Server connection failed.', 'error'); },
                complete: function() { $btn.prop('disabled', false).text(originalText); }
            });
        }
    });

    // 4. Handle "View Billing" Click (Yellow Button)
    $(document).on('click', '.view-bill-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) viewBilling(apptData);
    });

    // 5. Handle "Edit" Click (Blue Button)
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) editAppt(apptData);
    });

    // 6. Search Input listener
    $('#customerSearchInput').on('keyup', function() {
        let query = $(this).val();
        if (query.length < 2) {
            $('#suggestionsList').addClass('d-none');
            return;
        }
        $.post('api.php', { action: 'search_clients', query: query }, function(data) {
            let html = '';
            if (data.length > 0) {
                data.forEach(client => {
                    let clientDataStr = JSON.stringify(client).replace(/"/g, '&quot;');
                    html += `
                        <div class="suggestion-item list-group-item list-group-item-action" onclick="selectCustomer(${clientDataStr})">
                            <div class="fw-bold">${client.client_phone} - <span class="text-primary text-uppercase">${client.client_name}</span></div>
                            <small class="text-muted">${client.gender} | ${client.client_type}</small>
                        </div>`;
                });
            } else {
                html = '<div class="list-group-item text-muted">No previous customers found. Click "New".</div>';
            }
            $('#suggestionsList').html(html).removeClass('d-none');
        }, 'json');
    });

    // Hide suggestions on click outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customerSearchInput, #suggestionsList').length) {
            $('#suggestionsList').addClass('d-none');
        }
    });

    // 7. "Add Appointment" from Search Card
    $('#btnContinueToBook').on('click', function() {
        if (!selectedCustomerData) return;
        
        editingState = null; 
        tempBookingTime = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }); // Default "Now"

        $('#advCustName').text(selectedCustomerData.client_name);
        $('#advCustPhone').text(selectedCustomerData.client_phone);
        loadCustomerHistory(selectedCustomerData.client_phone);
        
        $('#serviceCartBody').html(''); 
        addNewServiceRow();
        openBillingView(); 
    });
});

// --- HELPER FUNCTIONS ---

function loadDashboardAndTable(date) {
    // Load Counts
    $.post('api.php', { action: 'fetch_counts', date_filter: date }, function(data) {
        $('#countOpen').text(data.open_count);
        $('#countClosed').text(data.closed_count);
        let revenue = parseFloat(data.total_revenue) || 0;
        $('#countRevenue').text('₹' + revenue.toFixed(2));
    }, 'json');

    // Load Table
    $.post('api.php', { action: 'fetch_by_date', date_filter: date }, function(data) {
        allAppointments = data;
        let rows = '';

        if(data.length === 0) {
             rows = '<tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>';
        } else {
            data.forEach(function(appt) {
                let statusBadge = appt.status === 'Scheduled' ? '<span class="badge bg-primary">Scheduled</span>' : 
                                  (appt.status === 'Completed' ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-danger">Cancelled</span>');
                
                let timeParts = appt.appointment_time.split(':');
                let dateObj = new Date(0, 0, 0, timeParts[0], timeParts[1]);
                let formattedTime = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit', hour12: true });

                let phoneSafe = appt.client_phone || '';
                let dateSafe = appt.appointment_date;
                let timeSafe = appt.appointment_time;

                rows += `
                <tr>
                    <td class="fw-bold text-primary">#${appt.id}</td>
                    <td><i class="far fa-clock text-muted me-1"></i> ${formattedTime}</td>
                    <td>
                        <div class="fw-bold">${appt.client_name}</div>
                        <small class="text-muted">${phoneSafe}</small>
                    </td>
                    <td><small>${appt.gender} / ${appt.client_type}</small></td>
                    <td class="text-indigo fw-medium"><small>${appt.employee_name}</small></td>
                    <td>
                        <div style="font-size: 0.85rem; margin-bottom: 4px;">${appt.service_details}</div>
                        <div class="fw-bold text-success border-top pt-1" style="font-size: 0.9rem;">
                            Total: ₹${parseFloat(appt.total_price || 0).toFixed(2)}
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-warning text-dark me-1 view-bill-btn" data-id="${appt.id}" title="View/Edit Services">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </button>

                        <button class="btn btn-sm btn-info text-white me-1" onclick="moveToBill('${phoneSafe}', '${dateSafe}', '${timeSafe}')" title="Move to Billing">
                <i class="fas fa-file-import"></i>
            </button>
                        
                        <button class="btn btn-sm btn-danger" onclick="deleteGroupAppt('${phoneSafe}', '${dateSafe}', '${timeSafe}')" title="Delete All">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        }
        $('#apptTableBody').html(rows);
    }, 'json');
}

function loadDropdownData() {
    $.post('api.php', { action: 'fetch_dropdowns' }, function(data) {
        employeeOptionsHTML = '<option value="">Select Staff</option>';
        data.employees.forEach(e => {
            employeeOptionsHTML += `<option value="${e.id}">${e.name}</option>`;
        });

        serviceOptionsHTML = '<option value="" data-price="0">Select Service</option>';
        data.services.forEach(s => {
            serviceOptionsHTML += `<option value="${s.id}" data-price="${s.price}">${s.service_name}</option>`;
        });
        
        // Populate Fallback Modal dropdowns
        $('#employeeSelect').html(employeeOptionsHTML);
        $('#serviceSelect').html(serviceOptionsHTML);
    }, 'json');
}

// --- VIEW & MODAL LOGIC ---

function openModalForNew() {
    resetSearch();
    $('#customerSearchSection').slideUp();
    $('#apptForm')[0].reset();
    $('#apptId').val('');
    $('#formAction').val('create');
    $('#modalTitle').html('<i class="fas fa-calendar-plus me-2"></i>Book New Appointment');
    $('#submitBtn').text('Confirm Booking').removeClass('btn-warning').addClass('btn-primary');
    $('#statusDiv').hide();
    $('#apptDate').val($('#dateFilter').val());
    $('#apptModal').modal('show');
}

function editAppt(appt) {
    $('#apptId').val(appt.id);
    $('#apptDate').val(appt.appointment_date);
    $('#apptTime').val(appt.appointment_time);
    $('#clientName').val(appt.client_name);
    $('#clientPhone').val(appt.client_phone);
    $('#clientGender').val(appt.gender);
    $('#clientType').val(appt.client_type);
    $('#apptStatus').val(appt.status);
    
    $('#formAction').val('update');
    $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Details #' + appt.id);
    $('#submitBtn').text('Update Details').removeClass('btn-primary').addClass('btn-warning');
    $('#statusDiv').show();
    
    $('#apptModal').modal('show');
}

// --- GROUP DELETE LOGIC ---
function deleteGroupAppt(phone, date, time) {
    Swal.fire({
        title: 'Delete Appointment?',
        text: "This will remove ALL services for this appointment.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete all!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { 
                action: 'delete_group', 
                phone: phone, 
                date: date, 
                time: time 
            }, function(res) {
                if(res.status === 'success') {
                    loadDashboardAndTable($('#dateFilter').val());
                    Swal.fire('Deleted!', 'Appointment removed.', 'success');
                } else {
                    Swal.fire('Error', 'Could not delete.', 'error');
                }
            }, 'json');
        }
    });
}

// --- SEARCH LOGIC ---

function toggleSearchSection() {
    $('#customerSearchSection').slideToggle();
    $('#customerSearchInput').focus();
    resetSearch();
}

function resetSearch() {
    $('#customerSearchInput').val('');
    $('#suggestionsList').addClass('d-none').html('');
    $('#selectedCustomerCard').hide();
    $('#btnContinueToBook').prop('disabled', true);
    selectedCustomerData = null;
}

function selectCustomer(clientData) {
    selectedCustomerData = clientData;
    $('#suggestionsList').addClass('d-none');
    $('#customerSearchInput').val('');
    $('#cardCustName').text(clientData.client_name);
    $('#cardCustPhone').text(clientData.client_phone);
    $('#cardCustGender').text(clientData.gender);
    $('#cardCustType').text(clientData.client_type);
    $('#selectedCustomerCard').fadeIn();
    $('#btnContinueToBook').prop('disabled', false);
}

// --- BILLING / CART LOGIC ---

function loadCustomerHistory(phone) {
    $('#histVisits').text('Loading...');
    $('#histSpent').text('...');
    
    $.post('api.php', { action: 'fetch_client_history', client_phone: phone }, function(data) {
        let stats = data.stats;
        let bill = data.last_bill;
        
        $('#histVisits').text(stats.visit_count > 0 ? stats.visit_count : 'New Client');
        $('#histFirst').text(stats.first_visit || '--');
        $('#histLast').text(stats.last_visit || '--');
        $('#histSpent').text('₹' + (parseFloat(stats.total_spent) || 0).toFixed(2));
        
        if(bill) {
            $('#lastStylist').text(bill.stylist);
            $('#lastService').text(bill.service_name);
        } else {
            $('#lastStylist').text('--');
            $('#lastService').text('--');
        }
    }, 'json');
}

function addNewServiceRow() {
    let rowId = Date.now();
    let row = `
        <tr id="row_${rowId}">
            <td><select class="form-select form-select-sm emp-select">${employeeOptionsHTML}</select></td>
            <td><select class="form-select form-select-sm svc-select" onchange="updateRowPrice(${rowId}, this)">${serviceOptionsHTML}</select></td>
            <td><input type="number" class="form-control form-control-sm price-input" value="0" onkeyup="calculateRowTotal(${rowId})"></td>
            <td><input type="number" class="form-control form-control-sm total-input fw-bold" value="0" readonly></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger border-0" onclick="$('#row_${rowId}').remove(); calculateGrandTotal();"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    $('#serviceCartBody').append(row);
}

function addServiceRowWithData(item) {
    let rowId = Date.now() + Math.floor(Math.random() * 1000); 
    let price = parseFloat(item.price) || 0;

    let row = `
        <tr id="row_${rowId}">
            <td><select class="form-select form-select-sm emp-select">${employeeOptionsHTML}</select></td>
            <td><select class="form-select form-select-sm svc-select" onchange="updateRowPrice(${rowId}, this)">${serviceOptionsHTML}</select></td>
            <td><input type="number" class="form-control form-control-sm price-input" value="${price.toFixed(2)}" onkeyup="calculateRowTotal(${rowId})"></td>
            <td><input type="number" class="form-control form-control-sm total-input fw-bold" value="${price.toFixed(2)}" readonly></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger border-0" onclick="$('#row_${rowId}').remove(); calculateGrandTotal();"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    
    $('#serviceCartBody').append(row);

    let $row = $('#row_' + rowId);
    $row.find('.emp-select').val(item.employee_id);
    $row.find('.svc-select').val(item.service_id);
}

function updateRowPrice(rowId, selectEl) {
    let price = $(selectEl).find(':selected').data('price');
    let row = $('#row_' + rowId);
    let basePrice = parseFloat(price) || 0;
    
    row.find('.price-input').val(basePrice.toFixed(2));
    row.find('.total-input').val(basePrice.toFixed(2));
    calculateGrandTotal();
}

function calculateRowTotal(rowId) {
    let row = $('#row_' + rowId);
    let price = parseFloat(row.find('.price-input').val()) || 0;
    row.find('.total-input').val(price.toFixed(2));
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grandTotal = 0;
    $('#serviceCartBody tr').each(function() {
        let total = parseFloat($(this).find('.total-input').val()) || 0;
        grandTotal += total;
    });
    $('#headerNet').text('₹' + grandTotal.toFixed(2));
}

function saveAdvancedBooking() {
    let services = [];
    $('#serviceCartBody tr').each(function() {
        let empId = $(this).find('.emp-select').val();
        let svcId = $(this).find('.svc-select').val();
        if(empId && svcId) {
            services.push({ employee_id: empId, service_id: svcId });
        }
    });

    if(services.length === 0) {
        Swal.fire('Error', 'Please add at least one service', 'warning');
        return;
    }

    let $btn = $('#btnSaveBooking');
    $btn.prop('disabled', true).text('Saving...');

    // Prepare data
    let payload = {
        action: 'save_appointment',
        client_name: $('#advCustName').text(),
        client_phone: $('#advCustPhone').text(),
        gender: selectedCustomerData.gender,
        client_type: selectedCustomerData.client_type,
        appointment_date: $('#advDate').val(),
        appointment_time: tempBookingTime, // Send the captured time
        services: services
    };

    // If editing, send original details for deletion
    if (editingState) {
        payload.original_date = editingState.original_date;
        payload.original_time = editingState.original_time;
        payload.original_phone = editingState.original_phone;
    }

    $.post('api.php', payload, function(res) {
        $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save');
        
        if(res.status === 'success') {
            closeBillingView(); 
            $('#customerSearchSection').slideUp();
            loadDashboardAndTable($('#dateFilter').val());
            Swal.fire('Success', 'Appointment Saved!', 'success');
        } else {
            Swal.fire('Error', 'Failed to save', 'error');
        }
    }, 'json').fail(function() {
        $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save');
        Swal.fire('Error', 'Server Error', 'error');
    });
}

function viewBilling(appt) {
    $('#advCustName').text(appt.client_name);
    $('#advCustPhone').text(appt.client_phone);
    $('#advDate').val(appt.appointment_date);

    // Save state for "Edit"
    editingState = {
        original_date: appt.appointment_date,
        original_time: appt.appointment_time,
        original_phone: appt.client_phone
    };
    
    // Also set tempBookingTime so if we save without changes, it keeps the time
    tempBookingTime = appt.appointment_time;

    selectedCustomerData = {
        client_name: appt.client_name,
        client_phone: appt.client_phone,
        gender: appt.gender,
        client_type: appt.client_type
    };

    $('#serviceCartBody').html('');
    $('#headerNet').text('Loading...');

    // Fetch and populate rows
    $.post('api.php', { 
        action: 'fetch_group_details',
        date: appt.appointment_date,
        time: appt.appointment_time,
        phone: appt.client_phone
    }, function(items) {
        items.forEach(function(item) {
            addServiceRowWithData(item);
        });
        calculateGrandTotal();
    }, 'json');
    
    loadCustomerHistory(appt.client_phone);
    openBillingView();
}

// --- TOGGLE VIEWS ---

function openBillingView() {
    $('#mainDashboardView').addClass('d-none');
    $('#billingView').removeClass('d-none');
}

function closeBillingView() {
    $('#billingView').addClass('d-none');
    $('#mainDashboardView').removeClass('d-none');
}

// --- MOVE TO BILL LOGIC ---
function moveToBill(phone, date, time) {
    Swal.fire({
        title: 'Move to Billing?',
        text: "This will remove it from Appointments and show it in the Billing menu.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Move it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { 
                action: 'move_to_bill', 
                phone: phone, 
                date: date, 
                time: time 
            }, function(res) {
                if(res.status === 'success') {
                    loadDashboardAndTable($('#dateFilter').val());
                    Swal.fire('Moved!', 'Appointment moved to Billing.', 'success');
                } else {
                    Swal.fire('Error', 'Could not move.', 'error');
                }
            }, 'json');
        }
    });
}