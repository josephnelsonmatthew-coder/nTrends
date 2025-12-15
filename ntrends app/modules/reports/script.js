// ================================
// EMPLOYEE REPORT (NEW TAB ONLY)
// ================================
$('#btnEmployeeReport').click(function () {

    const empId = $('#employeeFilter').val();

    let url = 'employee_report_view.php';
    if (empId) {
        url += '?employee_id=' + empId;
    }

    // Open in new tab
    window.open(url, '_blank');

    // Update Excel download link
    let excelUrl = 'export.php?type=employee';
    if (empId) {
        excelUrl += '&employee_id=' + empId;
    }
    $('#downloadEmployeeExcel').attr('href', excelUrl);
});


// ================================
// SERVICE REPORT (NEW TAB ONLY)
// ================================
$('#btnServiceReport').click(function () {
    window.open('service_report_view.php', '_blank');
});


// ================================
// LOAD EMPLOYEES DROPDOWN
// ================================
// Global AJAX Setup for CSRF
$.ajaxSetup({
    headers: { 'X-CSRF-Token': CSRF_TOKEN }
});

$(document).ready(function () {

    $.post('../appointments/api.php', { action: 'fetch_dropdowns' }, function (res) {

        if (!res || !res.employees) {
            console.error('No employees returned');
            return;
        }

        const $empSelect = $('#employeeFilter');
        $empSelect.empty();
        $empSelect.append('<option value="">All Employees</option>');

        res.employees.forEach(emp => {
            $empSelect.append(
                `<option value="${emp.id}">${emp.name}</option>`
            );
        });

    }, 'json')
        .fail(function (xhr) {
            console.error('Employee fetch failed:', xhr.responseText);
            Swal.fire('Error', 'Failed to load employees for report.', 'error');
        });

});
