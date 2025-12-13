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

    $('#clientPhone').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // 3. EDIT SERVICES (Yellow Button)
    $(document).on('click', '.view-bill-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) viewBilling(apptData);
    });

    // 4. EDIT DETAILS (Blue Button)
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id'); 
        let apptData = allAppointments.find(a => a.id == id);
        if (apptData) editAppt(apptData);
    });

    // =========================================================
    // 5. MOVE TO BILL (Indigo Button) -> OPENS CHECKOUT MODAL
    // =========================================================
    $(document).on('click', '.move-bill-btn', function() {
        // Get data from button data-attributes
        const name = $(this).data('name');
        const phone = $(this).data('phone');
        const services = $(this).data('services');
        const netTotal = parseFloat($(this).data('net')); // Final Amount (after discount)
        const discount = $(this).data('discount');
        const dateSafe = $(this).data('datesafe');
        const timeSafe = $(this).data('timesafe');

        // Populate Modal UI
        $('#payModalName').text(name);
        $('#payModalServices').html(services.replace(/<br>/g, ', '));
        $('#payModalTotal').text('₹' + netTotal.toFixed(2));
        
        // Show discount badge if applicable
        if(discount > 0) {
            $('#payModalDiscount').text('Discount Applied: ' + discount + '%').show();
        } else {
            $('#payModalDiscount').hide();
        }

        // Set Hidden Fields for API
        $('#payHiddenPhone').val(phone);
        $('#payHiddenDate').val(dateSafe);
        $('#payHiddenTime').val(timeSafe);

        // Reset Payment Selection to Cash by default
        $('#pm_cash').prop('checked', true);

        // Show Modal
        $('#checkoutModal').modal('show');
    });

    // 6. CONFIRM PAYMENT CLICK (Inside Modal)
    $('#btnConfirmPayment').click(function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');

        const paymentMethod = $('input[name="payMethod"]:checked').val();
        
        $.post('api.php', {
            action: 'move_to_billing',
            client_phone: $('#payHiddenPhone').val(),
            appointment_date: $('#payHiddenDate').val(),
            appointment_time: $('#payHiddenTime').val(),
            payment_method: paymentMethod // Send chosen method
        }, function(res) {
            if(res.status === 'success') {
                $('#checkoutModal').modal('hide');
                loadDashboardAndTable($('#dateFilter').val());
                Swal.fire({ icon: 'success', title: 'Payment Successful!', text: 'Moved to Billing History.', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire('Error', 'Failed to process payment.', 'error');
            }
            // Reset button
            $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Confirm Payment & Close Bill');
        }, 'json');
    });

    // 7. HANDLE BOOKING FORM SUBMIT
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

            if(!name || !date || !time) { Swal.fire('Error', 'Fill required fields', 'warning'); return; }

            selectedCustomerData = { client_name: name, client_phone: phone, gender: gender, client_type: type };
            tempBookingTime = time; 
            editingState = null;

            $('#advCustName').text(name);
            $('#advCustPhone').text(phone);
            $('#advDate').val(date);
            $('#advDiscount').val(0); // Reset discount
            $('#serviceCartBody').html('');
            addNewServiceRow(); 
            if(phone) loadCustomerHistory(phone);

            $('#apptModal').modal('hide'); 
            openBillingView(); 
        } 
        else {
            // Update Existing Details
            $.ajax({
                url: 'api.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(res) {
                    $('#apptModal').modal('hide');
                    loadDashboardAndTable($('#dateFilter').val());
                    Swal.fire('Success', 'Updated!', 'success');
                }
            });
        }
    });

    // Search Logic
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
        $('#advDiscount').val(0); 
        loadCustomerHistory(selectedCustomerData.client_phone);
        $('#serviceCartBody').html(''); 
        addNewServiceRow();
        openBillingView(); 
    });
});

// --- CORE FUNCTIONS ---

/* modules/appointments/script.js */

function loadDashboardAndTable(date) {
    // 1. Fetch Stats
    $.post('api.php', { action: 'fetch_counts', date_filter: date }, function(data) {
        $('#countOpen').text(data.open_count);
        $('#countClosed').text(data.closed_count);
        $('#countRevenue').text('₹' + (parseFloat(data.total_revenue)||0).toFixed(2));
    }, 'json');

    // 2. Fetch Table
    $.post('api.php', { action: 'fetch_by_date', date_filter: date }, function(data) {
        allAppointments = data;
        let rows = '';

        if(data.length === 0) {
             rows = '<tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>';
        } else {
            data.forEach(function(appt) {
                // Status Badge
                let statusBadge = '';
                if (appt.status === 'Scheduled') statusBadge = '<span class="badge bg-primary">Scheduled</span>';
                else if (appt.status === 'Completed') statusBadge = '<span class="badge bg-success">Completed</span>';
                else statusBadge = '<span class="badge bg-danger">Cancelled</span>';

                // --- CALCULATION LOGIC ---
                let grossTotal = parseFloat(appt.total_price || 0);
                let discountPercent = parseInt(appt.discount_percent || 0);
                let discountAmount = (grossTotal * discountPercent) / 100;
                let netTotal = grossTotal - discountAmount;

                // --- PRICE DISPLAY HTML ---
                let priceHTML = '';
                if(discountPercent > 0) {
                    // Show Discounted View
                    priceHTML = `
                        <div class="d-flex flex-column">
                            <small class="text-muted text-decoration-line-through" style="font-size: 0.75rem;">₹${grossTotal.toFixed(2)}</small>
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-success me-2">₹${netTotal.toFixed(2)}</span>
                                <span class="badge bg-danger" style="font-size: 0.65rem;">-${discountPercent}%</span>
                            </div>
                        </div>`;
                } else {
                    // Show Normal View
                    priceHTML = `<span class="fw-bold text-success">₹${netTotal.toFixed(2)}</span>`;
                }

                // Standard Fields
                let phoneSafe = appt.client_phone || '';
                let dateSafe = appt.appointment_date;
                let timeSafe = appt.appointment_time;
                let formattedTime = new Date('1970-01-01T' + timeSafe).toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit', hour12: true });

                // Actions Logic
                let actionsColumnContent = '';
                if (appt.status === 'Completed') {
                    actionsColumnContent = '<span class="text-muted small fst-italic"><i class="fas fa-check-circle me-1"></i>Billed</span>';
                } else {
                    actionsColumnContent = `
                        <button class="btn btn-sm btn-warning text-dark me-1 view-bill-btn" data-id="${appt.id}" title="Edit Services"><i class="fas fa-list-ul"></i></button>
                        <button class="btn btn-sm btn-info text-white me-1 edit-btn" data-id="${appt.id}" title="Edit Details"><i class="fas fa-edit"></i></button>
                        
                        <button class="btn btn-sm btn-indigo text-white me-1 move-bill-btn" 
                                data-id="${appt.id}"
                                data-name="${appt.client_name}" 
                                data-phone="${phoneSafe}" 
                                data-services="${appt.service_details}" 
                                data-net="${netTotal}" 
                                data-discount="${discountPercent}"
                                data-datesafe="${dateSafe}" 
                                data-timesafe="${timeSafe}"
                                title="Checkout">
                            <i class="fas fa-file-import"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-danger" onclick="deleteGroupAppt('${phoneSafe}', '${dateSafe}', '${timeSafe}')" title="Delete"><i class="fas fa-trash"></i></button>
                    `;
                }

                rows += `
                <tr>
                    <td class="fw-bold text-primary">#${appt.id}</td>
                    <td><i class="far fa-clock text-muted me-1"></i> ${formattedTime}</td>
                    <td><div class="fw-bold">${appt.client_name}</div><small class="text-muted">${phoneSafe}</small></td>
                    <td><small>${appt.gender} / ${appt.client_type}</small></td>
                    <td class="text-indigo fw-medium"><small>${appt.employee_name}</small></td>
                    <td>
                        <div style="font-size: 0.85rem; margin-bottom: 4px;">${appt.service_details}</div>
                        ${priceHTML}
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
    // 1. Capture what the user typed in the search box
    let typedValue = $('#customerSearchInput').val().trim();

    // 2. Standard Reset (hides search, clears form)
    resetSearch(); 
    $('#customerSearchSection').slideUp();
    $('#apptForm')[0].reset();
    $('#apptId').val('');
    $('#formAction').val('create');
    $('#modalTitle').html('<i class="fas fa-calendar-plus me-2"></i>Book New Appointment');
    $('#submitBtn').text('Confirm Booking').removeClass('btn-warning').addClass('btn-primary');
    $('#statusDiv').hide();
    $('#apptDate').val($('#dateFilter').val());

    // 3. AUTO-FILL LOGIC
    if (typedValue) {
        // Check if the input contains only numbers (and symbols like + or -)
        // If yes -> It's a Phone Number
        // If no (has letters) -> It's a Name
        let isPhoneNumber = /^[0-9\-\+\s]+$/.test(typedValue);

        if (isPhoneNumber) {
            $('#clientPhone').val(typedValue);
        } else {
            $('#clientName').val(typedValue);
        }
    }

    // 4. Show Modal
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
    // 1. Reset UI to Loading State
    $('#histVisits').text('...');
    $('#histSpent').text('...');
    $('#clientInsightsBody').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Checking records...</div>');

    // 2. Fetch Data
    $.post('api.php', { action: 'fetch_client_history', client_phone: phone }, function(data) {
        let stats = data.stats;
        let last = data.last_visit;

        // --- UPDATE STATS CARD ---
        $('#histVisits').text(stats.visit_count > 0 ? stats.visit_count : '0');
        $('#histSpent').text('₹' + (parseFloat(stats.total_spent) || 0).toFixed(2));

        // --- UPDATE INSIGHTS CARD (The Logic) ---
        let insightsHTML = '';

        if (!last || stats.visit_count == 0) {
            // CASE: NEW CLIENT
            insightsHTML = `
                <div class="alert alert-success mb-0 text-center border-0 bg-success bg-opacity-10">
                    <div class="fs-1">🌟</div>
                    <div class="fw-bold text-success">New Client</div>
                    <small class="text-muted">First time visit!</small>
                </div>`;
        } else {
            // CASE: RETURNING CLIENT
            
            // Calculate previous bill totals
            let gross = parseFloat(last.gross_total || 0);
            let discPerc = parseInt(last.discount_percent || 0);
            let net = gross - ((gross * discPerc) / 100);

            // Discount Badge Logic
            let discountBadge = '';
            if (discPerc > 0) {
                discountBadge = `<div class="badge bg-danger mb-2 w-100">Last Bill had ${discPerc}% Discount</div>`;
            } else {
                discountBadge = `<div class="badge bg-light text-muted border mb-2 w-100">No Discount on Last Bill</div>`;
            }

            insightsHTML = `
                ${discountBadge}
                
                <div class="mb-2">
                    <small class="text-muted fw-bold text-uppercase" style="font-size:10px;">Last Visit Date</small>
                    <div class="fw-bold">${last.appointment_date}</div>
                </div>

                <div class="mb-2">
                    <small class="text-muted fw-bold text-uppercase" style="font-size:10px;">Last Services</small>
                    <div class="text-dark small" style="line-height:1.2;">${last.services}</div>
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Last Bill:</small>
                    <div class="text-end">
                        ${discPerc > 0 ? `<small class="text-decoration-line-through text-muted me-1">₹${gross}</small>` : ''}
                        <span class="fw-bold text-primary">₹${net.toFixed(0)}</span>
                    </div>
                </div>
            `;
        }

        $('#clientInsightsBody').html(insightsHTML);

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

// --- CALCULATIONS & SAVING ---

function calculateGrandTotal() {
    let subTotal = 0;
    $('#serviceCartBody tr').each(function() { 
        subTotal += parseFloat($(this).find('.total-input').val()) || 0; 
    });

    // Get Discount Percent
    let discPercent = parseInt($('#advDiscount').val()) || 0;
    let discAmount = (subTotal * discPercent) / 100;
    let finalTotal = subTotal - discAmount;

    // Update Header
    $('#headerNet').text('₹' + finalTotal.toFixed(2));
    
    // Show/Hide Original Price (Strike-through)
    if(discPercent > 0) {
        $('#headerGross').text('₹' + subTotal.toFixed(2)).show();
    } else {
        $('#headerGross').hide();
    }
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
        discount_percent: $('#advDiscount').val(), // Save Discount
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
    
    $.post('api.php', { action: 'fetch_group_details', date: appt.appointment_date, time: appt.appointment_time, phone: appt.client_phone }, function(data) {
        let items = data.items;
        $('#advDiscount').val(data.discount || 0); // Load saved discount
        items.forEach(function(item) { addServiceRowWithData(item); });
        calculateGrandTotal();
    }, 'json');
    
    loadCustomerHistory(appt.client_phone);
    openBillingView();
}

function openBillingView() { $('#mainDashboardView').addClass('d-none'); $('#billingView').removeClass('d-none'); }
function closeBillingView() { $('#billingView').addClass('d-none'); $('#mainDashboardView').removeClass('d-none'); }


// 1. Debounce Function (Prevents spamming server while typing)
function debounce(fn, delay) {
    let timer = null;
    return function () {
        const ctx = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(ctx, args), delay);
    };
}

// 2. Listen for typing in PRICE field
$(document).on('input', '.price-input', debounce(function () {
    const $priceInput = $(this);
    const priceRaw = $priceInput.val().trim();
    const $row = $priceInput.closest('tr');
    const $svcDropdown = $row.find('.svc-select');

    // If empty or not a number -> Reset dropdown to show ALL services
    // We rely on the global 'serviceOptionsHTML' variable we loaded earlier
    if (priceRaw === '' || isNaN(parseFloat(priceRaw))) {
        $svcDropdown.html(serviceOptionsHTML);
        // If Select2 is active, we need to refresh it
        if ($svcDropdown.hasClass('select2-hidden-accessible')) {
            $svcDropdown.trigger('change.select2');
        }
        return;
    }

    const price = parseFloat(priceRaw);

    // Show "Searching..."
    $svcDropdown.html('<option>Searching...</option>');

    // AJAX request
    $.post('api.php', { action: 'search_services_by_price', price: price }, function (data) {
        $svcDropdown.empty();

        // No matches found
        if (!Array.isArray(data) || data.length === 0) {
            $svcDropdown.append('<option value="">No services found for ₹' + price + '</option>');
        } else {
            // Populate dropdown with filtered services
            $svcDropdown.append('<option value="">Select Service (Found ' + data.length + ')</option>');
            
            data.forEach(s => {
                const dprice = s.price ? parseFloat(s.price).toFixed(2) : '0.00';
                // Note: We include the price in the text so user confirms it matches
                $svcDropdown.append(`<option value="${s.id}" data-price="${dprice}">${s.service_name} - ₹${dprice}</option>`);
            });
        }
        
        // If Select2 is active, refresh it
        if ($svcDropdown.hasClass('select2-hidden-accessible')) {
            $svcDropdown.trigger('change'); // Updates Select2 UI
        }
    }, 'json')
    .fail(function () {
        $svcDropdown.html('<option value="">Error fetching services</option>');
    });

}, 300)); // 300ms delay

/* modules/appointments/script.js */

// ... (Existing code) ...

// NEW: Date Navigation Logic
function changeDate(days) {
    // 1. Get current date from input
    let $input = $('#dateFilter');
    let currentVal = $input.val();
    
    // 2. Create Date Object
    let dateObj = new Date(currentVal);
    
    // 3. Add or Subtract Days
    dateObj.setDate(dateObj.getDate() + days);
    
    // 4. Format back to YYYY-MM-DD
    // Note: We use .toLocaleDateString to avoid timezone issues
    let year = dateObj.getFullYear();
    let month = String(dateObj.getMonth() + 1).padStart(2, '0');
    let day = String(dateObj.getDate()).padStart(2, '0');
    let newDate = `${year}-${month}-${day}`;
    
    // 5. Update Input and Trigger Change
    // This automatically calls loadDashboardAndTable because of your existing .on('change') listener
    $input.val(newDate).trigger('change');
}