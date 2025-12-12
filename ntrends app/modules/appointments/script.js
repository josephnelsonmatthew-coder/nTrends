// modules/appointments/script.js

let allAppointments = [];
let selectedCustomerData = null; 
let serviceOptionsHTML = ''; 
let employeeOptionsHTML = '';
let editingState = null;        
let tempBookingTime = '';       

$(document).ready(function() {
    // 1. Initial Load
    let today = $('#dateFilter').val();
    loadDashboardAndTable(today);
    loadDropdownData(); 

    // 2. Date Filter
    $('#dateFilter').on('change', function() {
        loadDashboardAndTable($(this).val());
    });

    // 3. Handle Booking/Edit Form Submit
    $('#apptForm').submit(function(e) {
        e.preventDefault();
        let action = $('#formAction').val();

        if(action === 'create') {
            let name = $('#clientName').val();
            let phone = $('#clientPhone').val();
            let gender = $('#clientGender').val();
            let type = $('#clientType').val();
            let date = $('#apptDate').val();
            let time = $('#apptTime').val(); 

            if(!name || !date || !time) {
                Swal.fire('Error', 'Please fill in Name, Date, and Time', 'warning');
                return;
            }

            selectedCustomerData = { client_name: name, client_phone: phone, gender: gender, client_type: type };
            tempBookingTime = time; 
            editingState = null;

            $('#advCustName').text(name);
            $('#advCustPhone').text(phone);
            $('#advDate').val(date);
            $('#serviceCartBody').html('');
            addNewServiceRow(); 
            if(phone) loadCustomerHistory(phone);

            $('#apptModal').modal('hide'); 
            openBillingView(); 
        } 
        else {
            // Edit Details (Blue Button)
            let $btn = $('#submitBtn');
            $btn.prop('disabled', true).text('Processing...');
            $.ajax({
                url: 'api.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(res) {
                    $('#apptModal').modal('hide');
                    loadDashboardAndTable($('#dateFilter').val());
                    Swal.fire('Success', 'Updated successfully!', 'success');
                },
                complete: function() { $btn.prop('disabled', false).text('Update Details'); }
            });
        }
    });

    // 4. Handle "Edit Services" Click (Yellow Button)
    $(document).on('click', '.view-bill-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) viewBilling(apptData);
    });

    // 5. Handle "Edit Details" Click (Blue Button)
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) editAppt(apptData);
    });

    // 6. Search Logic
    $('#customerSearchInput').on('keyup', function() {
        let query = $(this).val();
        if (query.length < 2) { $('#suggestionsList').addClass('d-none'); return; }
        $.post('api.php', { action: 'search_clients', query: query }, function(data) {
            let html = '';
            if (data.length > 0) {
                data.forEach(client => {
                    let clientDataStr = JSON.stringify(client).replace(/"/g, '&quot;');
                    html += `<div class="suggestion-item list-group-item list-group-item-action" onclick="selectCustomer(${clientDataStr})">
                            <div class="fw-bold">${client.client_phone} - <span class="text-primary text-uppercase">${client.client_name}</span></div>
                        </div>`;
                });
            } else { html = '<div class="list-group-item text-muted">No previous customers found.</div>'; }
            $('#suggestionsList').html(html).removeClass('d-none');
        }, 'json');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customerSearchInput, #suggestionsList').length) {
            $('#suggestionsList').addClass('d-none');
        }
    });

    $('#btnContinueToBook').on('click', function() {
        if (!selectedCustomerData) return;
        editingState = null; 
        tempBookingTime = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        $('#advCustName').text(selectedCustomerData.client_name);
        $('#advCustPhone').text(selectedCustomerData.client_phone);
        loadCustomerHistory(selectedCustomerData.client_phone);
        $('#serviceCartBody').html(''); 
        addNewServiceRow();
        openBillingView(); 
    });

    // =========================================================
    // NEW: CHECKOUT / MOVE BILL LOGIC
    // =========================================================
    
    // 1. Click "Move Bill" (Indigo Button)
    $(document).on('click', '.move-bill-btn', function() {
        const name = $(this).data('name');
        const phone = $(this).data('phone');
        // Format services nicely
        const servicesHtml = $(this).data('services').toString().replace(/<br>/g, '<div class="mb-1 border-bottom border-light pb-1"></div>');
        const total = $(this).data('total');
        
        // Populate Modal
        $('#billClientName').text(name);
        $('#billClientPhone').text(phone);
        $('#billServicesList').html(servicesHtml);
        $('#billGrandTotal').text('₹' + parseFloat(total).toFixed(2));
        
        $('#hiddenBillPhone').val(phone);
        $('#hiddenBillDate').val($(this).data('datesafe'));
        $('#hiddenBillTime').val($(this).data('timesafe'));

        // Reset UI
        $('#paymentOptionsSection').hide();
        $('#gpayBtn').removeClass('btn-success text-white border-0').addClass('btn-outline-dark');
        $('#btnGenerateBillStep').show();
        $('#btnFinalCheckout').hide();

        $('#moveBillModal').modal('show');
    });

    // 2. Generate Bill Step
    $('#btnGenerateBillStep').click(function() {
        $(this).hide();
        $('#paymentOptionsSection').fadeIn();
    });

    // 3. Select GPay
    $('#gpayBtn').click(function() {
        $(this).removeClass('btn-outline-dark').addClass('btn-success text-white border-0');
        $('#btnFinalCheckout').fadeIn();
    });

    // 4. Final Checkout (API Call)
    $('#btnFinalCheckout').click(function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');

        $.post('api.php', {
            action: 'move_to_billing',
            client_phone: $('#hiddenBillPhone').val(),
            appointment_date: $('#hiddenBillDate').val(),
            appointment_time: $('#hiddenBillTime').val()
        }, function(response) {
            if(response.status === 'success') {
                $('#moveBillModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Moved to Billing!', timer: 1500, showConfirmButton: false });
                loadDashboardAndTable($('#dateFilter').val());
            } else {
                Swal.fire('Error', 'Failed to move.', 'error');
            }
            $btn.prop('disabled', false).text('Confirm Checkout & Move');
        }, 'json');
    });
});

// --- HELPER FUNCTIONS ---

function loadDashboardAndTable(date) {
    // 1. Fetch Dashboard Counts
    $.post('api.php', { action: 'fetch_counts', date_filter: date }, function(data) {
        $('#countOpen').text(data.open_count);
        $('#countClosed').text(data.closed_count);
        $('#countRevenue').text('₹' + (parseFloat(data.total_revenue)||0).toFixed(2));
    }, 'json');

    // 2. Fetch Appointments Table Data
    $.post('api.php', { action: 'fetch_by_date', date_filter: date }, function(data) {
        allAppointments = data;
        let rows = '';

        if(data.length === 0) {
             rows = '<tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>';
        } else {
            data.forEach(function(appt) {
                
                // --- Status Badge Logic ---
                let statusBadge = '';
                if (appt.status === 'Scheduled') {
                    statusBadge = '<span class="badge bg-primary">Scheduled</span>';
                } else if (appt.status === 'Completed') {
                    statusBadge = '<span class="badge bg-success">Completed</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                }

                // --- Format Time ---
                let timeParts = appt.appointment_time.split(':');
                let formattedTime = new Date(0, 0, 0, timeParts[0], timeParts[1]).toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit', hour12: true });

                // --- Safe Variables ---
                let phoneSafe = appt.client_phone || '';
                let dateSafe = appt.appointment_date;
                let timeSafe = appt.appointment_time;

                // --- ACTIONS LOGIC (The Fix) ---
                let actionsColumnContent = '';

                if (appt.status === 'Completed') {
                    // CASE 1: If Moved to Billing -> Show NO Actions (or a simple text)
                    actionsColumnContent = '<span class="text-muted small fst-italic"><i class="fas fa-check-circle me-1"></i>Billed</span>';
                } else {
                    // CASE 2: If Scheduled/Cancelled -> Show All Buttons
                    actionsColumnContent = `
                        <button class="btn btn-sm btn-warning text-dark me-1 view-bill-btn" data-id="${appt.id}" title="Edit Services">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-info text-white me-1 edit-btn" data-id="${appt.id}" title="Edit Details">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn btn-sm btn-indigo text-white me-1 move-bill-btn" 
                                data-id="${appt.id}"
                                data-name="${appt.client_name}" 
                                data-phone="${phoneSafe}" 
                                data-services="${appt.service_details}" 
                                data-total="${appt.total_price}" 
                                data-datesafe="${dateSafe}" 
                                data-timesafe="${timeSafe}"
                                title="Checkout">
                            <i class="fas fa-file-import"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-danger" onclick="deleteGroupAppt('${phoneSafe}', '${dateSafe}', '${timeSafe}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }

                // --- Build Row HTML ---
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
                    
                    <td>${actionsColumnContent}</td>
                </tr>`;
            });
        }
        $('#apptTableBody').html(rows);
    }, 'json');
}

function loadDropdownData() {
    $.post('api.php', { action: 'fetch_dropdowns' }, function(data) {
        employeeOptionsHTML = '<option value="">Select Staff</option>';
        data.employees.forEach(e => { employeeOptionsHTML += `<option value="${e.id}">${e.name}</option>`; });
        serviceOptionsHTML = '<option value="" data-price="0">Select Service</option>';
        data.services.forEach(s => { serviceOptionsHTML += `<option value="${s.id}" data-price="${s.price}">${s.service_name}</option>`; });
        $('#employeeSelect').html(employeeOptionsHTML);
        $('#serviceSelect').html(serviceOptionsHTML);
    }, 'json');
}

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

function deleteGroupAppt(phone, date, time) {
    Swal.fire({
        title: 'Delete Appointment?', text: "Removes all services for this visit.", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete all!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { action: 'delete_group', phone: phone, date: date, time: time }, function(res) {
                loadDashboardAndTable($('#dateFilter').val());
                Swal.fire('Deleted!', '', 'success');
            }, 'json');
        }
    });
}

function toggleSearchSection() { $('#customerSearchSection').slideToggle(); $('#customerSearchInput').focus(); resetSearch(); }
function resetSearch() { $('#customerSearchInput').val(''); $('#suggestionsList').addClass('d-none'); $('#selectedCustomerCard').hide(); $('#btnContinueToBook').prop('disabled', true); selectedCustomerData = null; }
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

function loadCustomerHistory(phone) {
    $('#histVisits').text('Loading...');
    $.post('api.php', { action: 'fetch_client_history', client_phone: phone }, function(data) {
        let stats = data.stats;
        $('#histVisits').text(stats.visit_count > 0 ? stats.visit_count : 'New');
        $('#histFirst').text(stats.first_visit || '--');
        $('#histLast').text(stats.last_visit || '--');
        $('#histSpent').text('₹' + (parseFloat(stats.total_spent) || 0).toFixed(2));
    }, 'json');
}

function addNewServiceRow() {
    let rowId = Date.now();
    let row = `<tr id="row_${rowId}">
            <td><select class="form-select form-select-sm emp-select">${employeeOptionsHTML}</select></td>
            <td><select class="form-select form-select-sm svc-select" onchange="updateRowPrice(${rowId}, this)">${serviceOptionsHTML}</select></td>
            <td><input type="number" class="form-control form-control-sm price-input" value="0" onkeyup="calculateRowTotal(${rowId})"></td>
            <td><input type="number" class="form-control form-control-sm total-input fw-bold" value="0" readonly></td>
            <td class="text-center"><button class="btn btn-sm btn-outline-danger border-0" onclick="$('#row_${rowId}').remove(); calculateGrandTotal();"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    $('#serviceCartBody').append(row);
}
function addServiceRowWithData(item) {
    let rowId = Date.now() + Math.floor(Math.random() * 1000); 
    let price = parseFloat(item.price) || 0;
    let row = `<tr id="row_${rowId}">
            <td><select class="form-select form-select-sm emp-select">${employeeOptionsHTML}</select></td>
            <td><select class="form-select form-select-sm svc-select" onchange="updateRowPrice(${rowId}, this)">${serviceOptionsHTML}</select></td>
            <td><input type="number" class="form-control form-control-sm price-input" value="${price.toFixed(2)}" onkeyup="calculateRowTotal(${rowId})"></td>
            <td><input type="number" class="form-control form-control-sm total-input fw-bold" value="${price.toFixed(2)}" readonly></td>
            <td class="text-center"><button class="btn btn-sm btn-outline-danger border-0" onclick="$('#row_${rowId}').remove(); calculateGrandTotal();"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    $('#serviceCartBody').append(row);
    let $row = $('#row_' + rowId);
    $row.find('.emp-select').val(item.employee_id);
    $row.find('.svc-select').val(item.service_id);
}

function updateRowPrice(rowId, selectEl) {
    let price = $(selectEl).find(':selected').data('price');
    let row = $('#row_' + rowId);
    row.find('.price-input').val(parseFloat(price||0).toFixed(2));
    row.find('.total-input').val(parseFloat(price||0).toFixed(2));
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
    $('#serviceCartBody tr').each(function() { grandTotal += parseFloat($(this).find('.total-input').val()) || 0; });
    $('#headerNet').text('₹' + grandTotal.toFixed(2));
}

function saveAdvancedBooking() {
    let services = [];
    $('#serviceCartBody tr').each(function() {
        let empId = $(this).find('.emp-select').val();
        let svcId = $(this).find('.svc-select').val();
        if(empId && svcId) services.push({ employee_id: empId, service_id: svcId });
    });

    if(services.length === 0) { Swal.fire('Error', 'Please add services', 'warning'); return; }

    let $btn = $('#btnSaveBooking');
    $btn.prop('disabled', true).text('Saving...');

    let payload = {
        action: 'save_appointment',
        client_name: $('#advCustName').text(),
        client_phone: $('#advCustPhone').text(),
        gender: selectedCustomerData.gender,
        client_type: selectedCustomerData.client_type,
        appointment_date: $('#advDate').val(),
        appointment_time: tempBookingTime, 
        services: services
    };

    if (editingState) {
        payload.original_date = editingState.original_date;
        payload.original_time = editingState.original_time;
        payload.original_phone = editingState.original_phone;
    }

    $.post('api.php', payload, function(res) {
        $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Changes');
        if(res.status === 'success') {
            closeBillingView(); 
            $('#customerSearchSection').slideUp();
            loadDashboardAndTable($('#dateFilter').val());
            Swal.fire('Success', 'Saved!', 'success');
        } else { Swal.fire('Error', 'Failed to save', 'error'); }
    }, 'json');
}

function viewBilling(appt) {
    $('#advCustName').text(appt.client_name);
    $('#advCustPhone').text(appt.client_phone);
    $('#advDate').val(appt.appointment_date);
    editingState = { original_date: appt.appointment_date, original_time: appt.appointment_time, original_phone: appt.client_phone };
    tempBookingTime = appt.appointment_time;
    selectedCustomerData = { client_name: appt.client_name, client_phone: appt.client_phone, gender: appt.gender, client_type: appt.client_type };
    $('#serviceCartBody').html('');
    $.post('api.php', { action: 'fetch_group_details', date: appt.appointment_date, time: appt.appointment_time, phone: appt.client_phone }, function(items) {
        items.forEach(function(item) { addServiceRowWithData(item); });
        calculateGrandTotal();
    }, 'json');
    loadCustomerHistory(appt.client_phone);
    openBillingView();
}

function openBillingView() { $('#mainDashboardView').addClass('d-none'); $('#billingView').removeClass('d-none'); }
function closeBillingView() { $('#billingView').addClass('d-none'); $('#mainDashboardView').removeClass('d-none'); }