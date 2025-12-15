// modules/services/script.js

// Global AJAX Setup for CSRF
$.ajaxSetup({
    headers: { 'X-CSRF-Token': CSRF_TOKEN }
});

$(document).ready(function () {
    loadServices();

    // Handle Form Submit
    $('#serviceForm').submit(function (e) {
        e.preventDefault();

        let $btn = $('#submitBtn');
        let originalText = $btn.text();
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#serviceModal').modal('hide');
                    loadServices();
                    Swal.fire('Success', 'Saved successfully!', 'success');
                } else {
                    Swal.fire('Error', 'Operation failed.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Server connection failed.', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});


// Load Data
function loadServices() {
    $.post('api.php', { action: 'fetch' }, function (data) {
        let rows = '';
        data.forEach(function (svc) {
            rows += `
                <tr>
                    <td><strong>${svc.service_name}</strong></td>
                    
                    <td>₹${parseFloat(svc.price).toFixed(2)}</td>
                    
                    <td>
                        <button class="btn btn-sm btn-info text-white" onclick='editService(${JSON.stringify(svc)})'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteService(${svc.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#serviceTableBody').html(rows);
    }, 'json').fail(function (xhr) {
        console.error('Failed to load services:', xhr.responseText);
        Swal.fire('Error', 'Failed to load services. Please refresh.', 'error');
    });
}

// Open Modal for New Entry
function openModal() {
    $('#serviceForm')[0].reset();
    $('#serviceId').val('');
    $('#formAction').val('create');
    $('#modalTitle').text('Add New Service');
    $('#submitBtn').text('Save Service').removeClass('btn-warning').addClass('btn-success');
    $('#serviceModal').modal('show');
}

// Open Modal for Edit
function editService(svc) {
    $('#serviceId').val(svc.id);
    $('#serviceName').val(svc.service_name);
    $('#servicePrice').val(svc.price);

    $('#formAction').val('update');
    $('#modalTitle').text('Edit Service');
    $('#submitBtn').text('Update Service').removeClass('btn-success').addClass('btn-warning');
    $('#serviceModal').modal('show');
}

// Delete Logic
function deleteService(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { action: 'delete', id: id }, function (res) {
                if (res.status === 'success') {
                    loadServices();
                    Swal.fire('Deleted!', 'Service has been removed.', 'success');
                } else {
                    Swal.fire('Error', 'Could not delete service.', 'error');
                }
            }, 'json');
        }
    });
}