/* modules/billing/script.js */
// Global AJAX Setup for CSRF
$.ajaxSetup({
    headers: { 'X-CSRF-Token': CSRF_TOKEN }
});

$(document).ready(function () {
    // Load today's billing queue by default
    let today = $('#dateFilter').val();
    loadBillingTable(today);

    // Handle Date Change
    $('#dateFilter').on('change', function () {
        loadBillingTable($(this).val());
    });

    // Handle "View Bill PDF" Click
    $(document).on('click', '.view-pdf-btn', function () {
        const phone = $(this).data('phone');
        const date = $(this).data('date');
        const time = $(this).data('time');

        // Open the bill view in a new window
        const url = `view_bill.php?phone=${phone}&date=${date}&time=${time}`;
        window.open(url, '_blank', 'noopener,noreferrer');
    });
});

/* modules/billing/script.js */

/* modules/billing/script.js */

/* modules/billing/script.js */

function loadBillingTable(date) {
    // Show loading spinner
    $('#billingTableBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');

    // Fetch data from API
    $.post('../appointments/api.php', { action: 'fetch_billing', date_filter: date }, function (data) {
        let rows = '';

        if (data.length === 0) {
            rows = '<tr><td colspan="8" class="text-center text-muted py-4">No completed bills found for this date.</td></tr>';
        } else {
            data.forEach(function (bill) {
                // 1. Format Time
                let timeParts = bill.appointment_time.split(':');
                let formattedTime = new Date(0, 0, 0, timeParts[0], timeParts[1]).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

                // 2. Format Services (Replace <br> with commas for cleaner text)
                let servicesFormatted = bill.service_details.replace(/<br>/g, ', ');

                // 3. --- CALCULATE PRICES ---
                let grossTotal = parseFloat(bill.total_price || 0);
                let discountPercent = parseInt(bill.discount_percent || 0);

                // Calculate Net Total
                let discountAmount = (grossTotal * discountPercent) / 100;
                let netTotal = grossTotal - discountAmount;

                // 4. --- GENERATE PRICE DISPLAY HTML ---
                let priceHTML = '';

                if (discountPercent > 0) {
                    // VIEW: Discount Applied (Crossed out price + Badge)
                    priceHTML = `
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center" style="font-size: 0.85rem;">
                                <span class="text-muted text-decoration-line-through me-2">₹${grossTotal.toFixed(2)}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-success fs-5 me-2">₹${netTotal.toFixed(2)}</span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-1 py-0" style="font-size: 0.7em;">-${discountPercent}%</span>
                            </div>
                        </div>`;
                } else {
                    // VIEW: Standard Price
                    priceHTML = `<span class="fs-5 fw-bold text-success">₹${netTotal.toFixed(2)}</span>`;
                }

                // 5. WhatsApp Message Logic
                let message = `*INVOICE - nTrends Salon* %0A`;
                message += `Dear ${bill.client_name},%0A`;
                message += `Here is your service receipt.%0A`;
                message += `📅 Date: ${bill.appointment_date}%0A`;
                message += `✂ Services: ${servicesFormatted}%0A`;

                if (discountPercent > 0) {
                    message += `💰 *Total Paid: ₹${netTotal.toFixed(2)}* (Saved ${discountPercent}%)%0A`;
                } else {
                    message += `💰 *Total Paid: ₹${netTotal.toFixed(2)}*%0A`;
                }

                message += `Thank you for visiting!`;
                let rawPhone = bill.client_phone.replace(/[^0-9]/g, ''); // Remove spaces or dashes

                // If number is 10 digits (e.g., 9876543210), add '91' at the start
                if (rawPhone.length === 10) {
                    rawPhone = '91' + rawPhone;
                }

                let waLink = `https://wa.me/${rawPhone}?text=${message}`;

                // 6. Build the Row
                rows += `
                <tr>
                    <td class="fw-bold text-primary">#${bill.id}</td>
                    <td>${formattedTime}</td>
                    <td>
                        <div class="fw-bold">${bill.client_name}</div>
                        <small class="text-muted">${bill.client_phone}</small>
                    </td>
                    <td><small class="text-indigo fw-medium">${bill.employee_name}</small></td>
                    <td><div style="font-size: 0.9rem;">${bill.service_details}</div></td>
                    
                    <td>${priceHTML}</td>

                    <td><span class="badge bg-success">Bill Closed</span></td>
                    
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-danger text-white view-pdf-btn" 
                                    data-phone="${bill.client_phone}" 
                                    data-date="${bill.appointment_date}" 
                                    data-time="${bill.appointment_time}"
                                    title="Download PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>

                            <a href="${waLink}" target="_blank" class="btn btn-sm btn-success text-white" title="Send WhatsApp">
                                <i class="fab fa-whatsapp fw-bold"></i> Send
                            </a>
                        </div>
                    </td>
                </tr>`;
            });
        }
        $('#billingTableBody').html(rows);
    }, 'json').fail(function () {
        $('#billingTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load data.</td></tr>');
    });
}