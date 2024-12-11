<script>
    <?php if (!empty($project_statuses)): ?>
    var ctx1 = document.getElementById('projectStatusChart').getContext('2d');
    var projectStatusChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($project_statuses, 'status')); ?>,
            datasets: [{
                label: 'Projects',
                data: <?php echo json_encode(array_column($project_statuses, 'count')); ?>,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Project Status Overview'
            },
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($investment_types)): ?>
    var ctx2 = document.getElementById('investmentTypesChart').getContext('2d');
    var investmentTypesChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($investment_types, 'investment_type')); ?>,
            datasets: [{
                label: 'Investments',
                data: <?php echo json_encode(array_column($investment_types, 'count')); ?>,
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Investment Types Distribution'
            },
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

// Function to verify project
async function verifyProject(projectId) {
    try {
        const formData = new FormData();
        formData.append('action', 'verify_project');
        formData.append('project_id', projectId);

        const response = await fetch('./functions/dashboardpost.php', {  // Changed to use dashboard.php instead
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.status) {
            // Redirect to Paystack payment page if available
            if (data.authorization_url) {
                window.location.href = data.authorization_url;
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Project verification initiated successfully'
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to verify project'
            });
        }
    } catch (error) {
        console.error('Error details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred during verification'
        });
    }
}

function editProject(projectId) {
    fetch('./ajax/get_project.php?id=' + projectId)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Server response:', text);
                    throw new Error('Server response was not ok');
                });
            }
            return response.json();
        })
        .then(project => {
            // Populate project details
            document.getElementById('edit_project_id').value = project.id;
            document.getElementById('edit_title').value = project.title;
            document.getElementById('edit_description').value = project.description;
            document.getElementById('edit_location').value = project.location;
            document.getElementById('edit_investment_goal').value = project.investment_goal;
            document.getElementById('edit_project_category').value = project.project_category;
            document.getElementById('edit_total_project_cost').value = project.total_project_cost;
            document.getElementById('edit_projected_revenue').value = project.projected_revenue;
            document.getElementById('edit_projected_profit').value = project.projected_profit;
            document.getElementById('edit_developer_info').value = project.developer_info;

            // Set checkboxes for investment types
            document.getElementById('edit_interest_bond').checked = project.investment_types.includes('interest_bond');
            document.getElementById('edit_profit_sharing').checked = project.investment_types.includes('profit_sharing');
            document.getElementById('edit_equity').checked = project.investment_types.includes('equity');

            // Clear existing materials in the table
            const materialsTableBody = document.getElementById('materials_table_body');
            materialsTableBody.innerHTML = '';

            // Populate materials in the table
            project.building_materials.forEach(material => {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${material.material_name}</td>
        <td>${material.material_category}</td>
        <td>${material.quantity}</td>
        <td>${material.unit}</td>
        <td>
            <button class="btn btn-sm btn-warning" onclick="editMaterial(this)" data-id="${material.id}" disabled>Edit</button>
            <button class="btn btn-sm btn-danger" onclick="deleteMaterial(${material.id})" disabled>Delete</button>
        </td>
    `;
    materialsTableBody.appendChild(row);
});

            // Show the modal
            new bootstrap.Modal(document.getElementById('editProjectModal')).show();
        })
        .catch(error => {
            console.error('Error details:', error);
            Swal.fire('Error', 'Failed to load project details', 'error');
        });
}


function deleteMaterial(materialId) {
    console.log('Material ID:', materialId); // Debugging line

    // Confirm deletion
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete this material. This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request to the server
            fetch('./ajax/delete_material.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${materialId}` // Send only the material ID
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', 'The material has been deleted.', 'success');
                    // Optionally, remove the row from the table if you have the row reference
                    const row = document.querySelector(`tr[data-id="${materialId}"]`);
                    if (row) {
                        row.remove(); // Remove the row from the table
                    }
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to delete material', 'error');
            });
        }
    });
}

// function editMaterial(button) {
//     const materialId = button.getAttribute('data-id'); // Get the ID from the button's data attribute

//     // Fetch the material details using the materialId
//     fetch('./ajax/get_material.php?id=' + materialId)
//         .then(response => {
//             if (!response.ok) {
//                 return response.text().then(text => {
//                     console.error('Server response:', text);
//                     throw new Error('Server response was not ok');
//                 });
//             }
//             return response.json();
//         })
//         .then(material => {
//             // Populate material details in the edit form
//             document.getElementById('edit_material_id').value = material.id;
//             document.getElementById('edit_material_name').value = material.material_name;
//             document.getElementById('edit_material_category').value = material.material_category;
//             document.getElementById('edit_material_quantity').value = material.quantity;
//             document.getElementById('edit_material_unit').value = material.unit;

//             // Show the modal for editing
//             new bootstrap.Modal(document.getElementById('editMaterialModal')).show();
//         })
//         .catch(error => {
//             console.error('Error details:', error);
//             Swal.fire('Error', 'Failed to load material details', 'error');
//         });
// }
// document.getElementById('editMaterialForm').addEventListener('submit', function(event) {
//     event.preventDefault(); // Prevent the default form submission

//     const formData = new FormData(this); // Get form data

//     // Send the update request to the server
//     fetch('./ajax/update_material.php', {
//         method: 'POST',
//         body: formData // Send the form data
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             Swal.fire('Updated!', 'The material has been updated.', 'success');
//             // Optionally, update the material row in the table
//             const row = document.querySelector(`tr[data-id="${data.material.id}"]`);
//             if (row) {
//                 row.cells[0].innerText = data.material.material_name;
//                 row.cells [1].innerText = data.material.material_category;
//                 row.cells[2].innerText = data.material.quantity;
//                 row.cells[3].innerText = data.material.unit;
//             }
//             // Close the modal
//             bootstrap.Modal.getInstance(document.getElementById('editMaterialModal')).hide();
//         } else {
//             Swal.fire('Error', data.error, 'error');
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         Swal.fire('Error', 'Failed to update material', 'error');
//     });
// });

function saveProjectChanges() {
    const form = document.getElementById('editProjectForm');
    const formData = new FormData(form);

    fetch('ajax/update_project.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server response:', text);
                throw new Error('Server response was not ok');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Project updated successfully',
                icon: 'success'
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        Swal.fire('Error', error.message, 'error');
    });
}

    function deleteProject(projectId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/delete_project.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'project_id=' + projectId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Deleted!',
                            'Project has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Deletion failed');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', error.message, 'error');
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
    // Predefined material data
    const materialData = {
        categories: {
            'Construction': ['Cement', 'Sand', 'Gravel', 'Building Blocks', 'Reinforcement Bars','Granite','Bricks','Planks','Nails','Window Frames','Doors','iton','Roofing Sheet','Kitchen Cabinet','Wardrobes'],
            'Tools': ['Wheelbarrow', 'Shovel', 'Headpan', 'Trowel', 'Measuring Tape'],
            'Electrical': ['Electrical Wire', 'PVC Conduit Pipes', 'Electrical Cable', 'Electrical Sockets', 'Electrical Switches','Bulbs'],
            'Plumbing': ['PVC Pipes', 'Pipe Fittings', 'Water Valves', 'Drainage Pipes'],
            'Finishing': ['Tiles', 'Paint', 'Primer', 'Ceiling Boards', 'Flooring Materials','Pop Cement']
        },
        units: {
            'Cement': 'bags', 'Sand': 'tons', 'Gravel': 'tons',
            'Building Blocks': 'pieces', 'Reinforcement Bars': 'kg',
            'Wheelbarrow': 'pieces', 'Shovel': 'pieces', 'Headpan': 'pieces',
            'Trowel': 'pieces', 'Measuring Tape': 'pieces',
            'Electrical Wire': 'meters', 'PVC Conduit Pipes': 'meters',
            'Electrical Cable': 'meters', 'Electrical Sockets': 'pieces',
            'Electrical Switches': 'pieces', 'PVC Pipes': 'meters',
            'Pipe Fittings': 'pieces', 'Water Valves': 'pieces',
            'Drainage Pipes': 'meters', 'Tiles': 'square meters',
            'Paint': 'liters', 'Primer': 'liters',
            'Ceiling Boards': 'pieces', 'Flooring Materials': 'square meters',
            'Bulbs': 'pieces','Nails': 'pieces','Planks': 'pieces','Bricks': 'pieces',
            'Window Frames': 'pieces','Doors': 'pieces','Roofing Sheets': 'pieces',
            'Granite': 'tons','Kitchen Cabinet': 'pieces', 'Wardrobes': 'pieces',
            'Pop Cement': 'bags',
        }
    };

    // DOM Element Selectors
    const elements = {
        categorySelect: document.getElementById('material_category'),
        materialSelect: document.getElementById('material_name'),
        materialList: document.getElementById('selected_materials_list'),
        addMaterialBtn: document.getElementById('add_material_btn'),
        materialQuantityInput: document.getElementById('material_quantity')
    };

    // Validation Utility
    const validation = {
        validateInput() {
            const { categorySelect, materialSelect, materialQuantityInput } = elements;
            const category = categorySelect.value;
            const materialName = materialSelect.value;
            const quantity = materialQuantityInput.value;

            return category && materialName && quantity && parseFloat(quantity) > 0;
        }
    };

    // UI Manipulation Utilities
    const uiUtils = {
        populateCategoryDropdown() {
            const { categorySelect } = elements;
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            
            Object.keys(materialData.categories).forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categorySelect.appendChild(option);
            });
        },

        updateMaterialDropdown() {
            const { categorySelect, materialSelect } = elements;
            
            categorySelect.addEventListener('change', function() {
                materialSelect.innerHTML = '<option value="">Select Material</option>';
                
                if (this.value) {
                    materialData.categories[this.value].forEach(material => {
                        const option = document.createElement('option');
                        option.value = material;
                        option.textContent = material;
                        materialSelect.appendChild(option);
                    });
                }
            });
        },

        resetMaterialForm() {
            const { categorySelect, materialSelect, materialQuantityInput } = elements;
            categorySelect.value = '';
            materialSelect.innerHTML = '<option value="">Select Material</option>';
            materialQuantityInput.value = '';
        }
    };

    // Material List Management
    const materialListManager = {
        addMaterialToList() {
            const { categorySelect, materialSelect, materialQuantityInput, materialList, addMaterialBtn } = elements;

            addMaterialBtn.addEventListener('click', function() {
                if (!validation.validateInput()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Input',
                        text: 'Please fill all material details correctly',
                    });
                    return;
                }

                const materialDetails = {
                    name: materialSelect.value ,
                    category: categorySelect.value,
                    quantity: parseFloat(materialQuantityInput.value),
                    unit: materialData.units[materialSelect.value]
                };

              const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                listItem.innerHTML = `
                    ${materialDetails.name} (${materialDetails.quantity} ${materialDetails.unit})
                    <div>
                        <button type="button" class="btn btn-warning btn-sm edit-material me-2">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm remove-material">Remove</button>
                    </div>
                    <input type="hidden" name="materials[]" value='${JSON.stringify(materialDetails)}'>
                `;
                
                materialList.appendChild(listItem);
                uiUtils.resetMaterialForm();
            });
        },

        handleListActions() {
            const { materialList, categorySelect, materialSelect, materialQuantityInput } = elements;

            materialList.addEventListener('click', function(e) {
                const target = e.target;
                const listItem = target.closest('li');
                
                if (target.classList.contains('remove-material')) {
                    Swal.fire({
                        title: 'Remove Material?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Yes, remove it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            listItem.remove();
                        }
                    });
                } 
                else if (target.classList.contains('edit-material')) {
                    const materialData = JSON.parse(listItem.querySelector('input[name="materials[]"]').value);
                    
                    categorySelect.value = materialData.category;
                    categorySelect.dispatchEvent(new Event('change'));
                    
                    setTimeout(() => {
                        materialSelect.value = materialData.name;
                        materialQuantityInput.value = materialData.quantity;
                    }, 50);
                    
                    listItem.remove();
                }
            });
        }
    };

    // Initialization
    function init() {
        uiUtils.populateCategoryDropdown();
        uiUtils.updateMaterialDropdown();
        materialListManager.addMaterialToList();
        materialListManager.handleListActions();

        // Input validation
        elements.materialQuantityInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });
    }

    // Start the application
    init();
});



</script>