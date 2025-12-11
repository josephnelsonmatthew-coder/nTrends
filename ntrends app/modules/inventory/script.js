$(document).ready(function() {
    loadInventory();

    // Handle Form Submit
    $('#inventoryForm').submit(function(e) {
        e.preventDefault();
        
        let $btn = $('#submitBtn');
        let originalText = $btn.text();
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    $('#inventoryModal').modal('hide');
                    loadInventory();
                    Swal.fire('Success', 'Inventory updated successfully!', 'success');
                } else {
                    Swal.fire('Error', 'Operation failed.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Server connection failed.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});

// Load Inventory Data
function loadInventory() {
    $.post('api.php', { action: 'fetch' }, function(data) {
        let rows = '';
        data.forEach(function(item) {
            
            // Optional: Color code low stock (Red if less than 5)
            let stockClass = item.quantity < 5 ? 'text-danger fw-bold' : '';

            rows += `
                <tr>
                    <td><strong>${item.product_name}</strong></td>
                    <td class="${stockClass}">${item.quantity}</td>
                    <td>₹${parseFloat(item.price).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-info text-white" onclick='editProduct(${JSON.stringify(item)})'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#inventoryTableBody').html(rows);
    }, 'json');
}

// Open Modal for New Entry
function openModal() {
    $('#inventoryForm')[0].reset();
    $('#prodId').val('');
    $('#formAction').val('create');
    $('#modalTitle').text('Add New Product');
    $('#submitBtn').text('Save Product').removeClass('btn-warning').addClass('btn-success');
    $('#inventoryModal').modal('show');
}

// Open Modal for Edit
function editProduct(item) {
    $('#prodId').val(item.id);
    $('#prodName').val(item.product_name);
    $('#prodQty').val(item.quantity);
    $('#prodPrice').val(item.price);
    
    $('#formAction').val('update');
    $('#modalTitle').text('Edit Product');
    $('#submitBtn').text('Update Product').removeClass('btn-success').addClass('btn-warning');
    $('#inventoryModal').modal('show');
}

// Delete Logic
function deleteProduct(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This item will be permanently removed!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api.php', { action: 'delete', id: id }, function(res) {
                if(res.status === 'success') {
                    loadInventory();
                    Swal.fire('Deleted!', 'Product removed.', 'success');
                } else {
                    Swal.fire('Error', 'Could not delete product.', 'error');
                }
            }, 'json');
        }
    });
}