/* modules/billing/script.js */
$(document).ready(function() {
    // Load today's billing queue by default
    let today = $('#dateFilter').val(); 
    loadBillingTable(today);

    // Handle Date Change
    $('#dateFilter').on('change', function() {
        loadBillingTable($(this).val());
    });

    // Handle "View Bill PDF" Click
    $(document).on('click', '.view-pdf-btn', function() {
        const phone = $(this).data('phone');
        const date = $(this).data('date');
        const time = $(this).data('time');

        // Open the bill view in a new window
        const url = `view_bill.php?phone=${phone}&date=${date}&time=${time}`;
        window.open(url, '_blank', 'noopener,noreferrer');
    });
});

function loadBillingTable(date) {
    $('#billingTableBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');

    // We use the APPOINTMENTS API 'fetch_billing' action
    $.post('../appointments/api.php', { action: 'fetch_billing', date_filter: date }, function(data) {
        let rows = '';

        if(data.length === 0) {
             rows = '<tr><td colspan="8" class="text-center text-muted py-4">No completed bills found for this date.</td></tr>';
        } else {
            data.forEach(function(bill) {
                let timeParts = bill.appointment_time.split(':');
                let formattedTime = new Date(0, 0, 0, timeParts[0], timeParts[1]).toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit', hour12: true });
                let servicesFormatted = bill.service_details.replace(/<br>/g, '<div class="border-bottom my-1"></div>');

                rows += `
                <tr>
                    <td class="fw-bold text-primary">#${bill.id}</td>
                    <td>${formattedTime}</td>
                    <td>
                        <div class="fw-bold">${bill.client_name}</div>
                        <small class="text-muted">${bill.client_phone}</small>
                    </td>
                    <td><small class="text-indigo fw-medium">${bill.employee_name}</small></td>
                    <td>
                        <div style="font-size: 0.9rem;">${servicesFormatted}</div>
                    </td>
                    <td><span class="fs-5 fw-bold text-success">₹${parseFloat(bill.total_price || 0).toFixed(2)}</span></td>
                    
                    <td><span class="badge bg-success">Bill Closed</span></td>
                    
                    <td>
                        <button class="btn btn-sm btn-danger text-white me-1 view-pdf-btn" 
                                data-phone="${bill.client_phone}" 
                                data-date="${bill.appointment_date}" 
                                data-time="${bill.appointment_time}"
                                title="View PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>

                        <button class="btn btn-sm btn-success opacity-75" disabled title="Paid">
                            <i class="fas fa-check-circle me-1"></i> Paid
                        </button>
                    </td>
                </tr>`;
            });
        }
        $('#billingTableBody').html(rows);
    }, 'json').fail(function() {
        $('#billingTableBody').html('<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>');
    });
}