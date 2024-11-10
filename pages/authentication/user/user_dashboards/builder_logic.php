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
            document.getElementById('edit_building_materials').value = project.building_materials;

            // Set checkboxes for investment types
            document.getElementById('edit_interest_bond').checked = project.investment_types.includes('interest_bond');
            document.getElementById('edit_profit_sharing').checked = project.investment_types.includes('profit_sharing');
            document.getElementById('edit_equity').checked = project.investment_types.includes('equity');

// Update file inputs for images and land title document (these should only be handled if necessary, as they cannot be programmatically assigned for security reasons)

            
            new bootstrap.Modal(document.getElementById('editProjectModal')).show();
        })
        .catch(error => {
            console.error('Error details:', error);
            Swal.fire('Error', 'Failed to load project details', 'error');
        });
}

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

</script>