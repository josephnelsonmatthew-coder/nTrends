// Global AJAX Setup for CSRF
$.ajaxSetup({
    headers: { 'X-CSRF-Token': CSRF_TOKEN }
});

$(document).ready(function () {
    // Initial Load
    loadEmployees();

    // Handle Form Submit (Create/Update)
    $('#employeeForm').submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#employeeModal').modal('hide');
                    loadEmployees();
                    Swal.fire('Success', 'Operation completed successfully!', 'success');
                } else {
                    Swal.fire('Error', 'Operation failed.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Server connection failed.', 'error');
            }
        });
    });
});

// Fetch Employees via AJAX
function loadEmployees() {
    $.post('api.php', { action: 'fetch' }, function (data) {
        let rows = '';
        data.forEach(function (emp) {
            let badge = emp.status === 'Active'
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>';

            rows += `
                <tr>
                    <td>${emp.id}</td>
                    <td><strong>${emp.name}</strong></td>
                    <td>${emp.role}</td>
                    <td>${emp.phone}</td>
                    <td>${badge}</td>
                    <td>
                        <button class="btn btn-sm btn-info text-white" onclick='editEmployee(${JSON.stringify(emp)})'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(${emp.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#employeeTableBody').html(rows);
    }, 'json').fail(function (xhr) {
        console.error('Failed to load employees:', xhr.responseText);
        Swal.fire('Error', 'Failed to load employees. Please refresh.', 'error');
    });
}

// Open Modal for New Entry
function openModal() {
    $('#employeeForm')[0].reset();
    $('#empId').val('');
    $('#formAction').val('create');
    $('#modalTitle').text('Add New Employee');
    $('#submitBtn').text('Save Employee').removeClass('btn-warning').addClass('btn-success');
    $('#employeeModal').modal('show');
}

// Open Modal for Editing
function editEmployee(emp) {
    $('#empId').val(emp.id);
    $('#name').val(emp.name);
    $('#phone').val(emp.phone);
    $('#role').val(emp.role);
    $('#status').val(emp.status);

    $('#formAction').val('update');
    $('#modalTitle').text('Edit Employee');
    $('#submitBtn').text('Update Employee').removeClass('btn-success').addClass('btn-warning');
    $('#employeeModal').modal('show');
}

// Delete Logic
function deleteEmployee(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { action: 'delete', id: id }, function (res) {
                if (res.status === 'success') {
                    loadEmployees();
                    Swal.fire('Deleted!', 'Employee has been removed.', 'success');
                } else {
                    Swal.fire('Error', 'Could not delete employee.', 'error');
                }
            }, 'json');
        }
    });
}