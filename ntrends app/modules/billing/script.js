$(document).ready(function() {
    // Initial Load
    let today = $('#dateFilter').val();
    loadBillingTable(today);
    
    // Date Filter Change
    $('#dateFilter').on('change', function() {
        loadBillingTable($(this).val());
    });
});

function loadBillingTable(date) {
    // Note: We use the APPOINTMENTS API because it has all the logic
    $.post('../appointments/api.php', { action: 'fetch_billing', date_filter: date }, function(data) {
        let rows = '';

        if(data.length === 0) {
             rows = '<tr><td colspan="8" class="text-center text-muted py-4">No pending bills for this date.</td></tr>';
        } else {
            data.forEach(function(appt) {
                
                let timeParts = appt.appointment_time.split(':');
                let dateObj = new Date(0, 0, 0, timeParts[0], timeParts[1]);
                let formattedTime = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit', hour12: true });

                rows += `
                <tr>
                    <td class="fw-bold text-secondary">#${appt.id}</td>
                    <td>${formattedTime}</td>
                    <td>
                        <div class="fw-bold">${appt.client_name}</div>
                        <small class="text-muted">${appt.client_phone}</small>
                    </td>
                    <td>${appt.employee_name}</td>
                    <td><small>${appt.service_details}</small></td>
                    <td><span class="badge bg-info text-dark">Ready for Bill</span></td>
                    <td class="fw-bold text-success fs-5">₹${parseFloat(appt.total_price || 0).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="markPaid(${appt.id})">
                            <i class="fas fa-check-circle me-1"></i> Pay
                        </button>
                    </td>
                </tr>`;
            });
        }
        $('#billingTableBody').html(rows);
    }, 'json');
}

function markPaid(id) {
    Swal.fire('Success', 'Payment functionality can be added here!', 'success');
}