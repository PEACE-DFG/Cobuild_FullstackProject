<?php
// Ensure this file is not accessed directly
if (!defined('MAIN_DASHBOARD')) {
    die('Direct access not permitted');
}

// Fetch investor-specific data
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_investments,
    SUM(CASE WHEN investment_type = 'cash' THEN investment_value ELSE 0 END) as total_cash_invested,
    COUNT(DISTINCT project_id) as total_projects,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_investments
    FROM investments 
    WHERE investor_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$investment_stats = $result->fetch_assoc();

// Fetch labor hours
$stmt = $conn->prepare("SELECT 
    SUM(lt.hours_worked) as total_labor_hours 
    FROM labor_tracking lt 
    JOIN investments i ON lt.investment_id = i.id 
    WHERE i.investor_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$labor_stats = $result->fetch_assoc();

// Fetch recent investments with project details
$stmt = $conn->prepare("SELECT 
    i.*, p.title as project_title, p.status as project_status, p.verification_status
    FROM investments i 
    JOIN projects p ON i.project_id = p.id 
    WHERE i.investor_id = ? 
    ORDER BY i.created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_investments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch investment types distribution
$stmt = $conn->prepare("SELECT 
    investment_type, 
    COUNT(*) as count,
    SUM(investment_value) as total_value 
    FROM investments 
    WHERE investor_id = ? 
    GROUP BY investment_type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$investment_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch monthly investment trends
$stmt = $conn->prepare("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(CASE WHEN investment_type = 'cash' THEN investment_value ELSE 0 END) as cash_value,
    COUNT(CASE WHEN investment_type = 'labor' THEN 1 ELSE NULL END) as labor_count,
    COUNT(CASE WHEN investment_type = 'materials' THEN 1 ELSE NULL END) as materials_count
    FROM investments 
    WHERE investor_id = ? 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC LIMIT 6");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Modify your existing projects query to include a JOIN with project_images
$stmt = $conn->prepare("SELECT 
    p.*,
    GROUP_CONCAT(pi.image_path) as additional_images
    FROM projects p
    LEFT JOIN project_images pi ON p.id = pi.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC");
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<style>
.project-additional-image {
    width: 200px;
    height: 200px;
    object-fit: cover;
    margin: 5px;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.2s;
}

.project-additional-image:hover {
    transform: scale(1.05);
}

#modal_additional_images_container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: start;
    margin-top: 10px;
}
#modal_featured_image {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: start;
    margin-top: 10px;
}
#modal_land_document {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: start;
    margin-top: 10px;
}
.zoomable-img {
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .zoomable-img:hover {
            opacity: 0.8;
        }
        .image-zoom-modal {
            z-index: 1060;
        }
        .image-zoom-modal .modal-dialog {
            max-width: 90vw;
            margin: 1.75rem auto;
        }
        .image-zoom-modal .modal-content {
            background-color: transparent;
            border: none;
        }
        .image-zoom-modal img {
            max-width: 100%;
            max-height: 90vh;
            margin: auto;
            display: block;
        }
        .zoom-close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1070;
        }
</style>
<h2 class="mb-4">Investor Dashboard</h2>

<!-- Widgets Section -->
<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Investments</h4>
                <p class="display-4"><?php echo $investment_stats['total_investments']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Cash Invested</h4>
                <p class="display-4">$<?php echo number_format($investment_stats['total_cash_invested'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Projects Involved</h4>
                <p class="display-4"><?php echo $investment_stats['total_projects']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Labor Hours</h4>
                <p class="display-4"><?php echo number_format($labor_stats['total_labor_hours'], 1); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Investment Distribution</h5>
                <canvas id="investmentDistributionChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Monthly Investment Trends</h5>
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<h2 class="mb-4">Projects Overview</h2>

<!-- Projects Table -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">All Projects</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                    <th>Developer Id</th>
                    <th>Title</th>
                    <th>Description</th>
                        <th>Date Added</th>
                        <th>Verification Status</th>
                        <th>Current Investment Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($projects as $project): ?>
    <tr onclick="showProjectDetails(<?php echo htmlspecialchars(json_encode($project)); ?>)" style="cursor:pointer">
        <td><?php echo htmlspecialchars($project['builder_id']); ?></td>
        <td><?php echo htmlspecialchars($project['title']); ?></td>
        <td><?php echo htmlspecialchars($project['description']); ?></td>
        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
        <td><?php echo ucfirst(htmlspecialchars($project['verification_status'])); ?></td>
        <td><?php echo htmlspecialchars($project['current_investment_amount']); ?></td>
        <td><button class="btn btn-success">Invest</button></td>
    </tr>
    <?php endforeach; ?>
</tbody>

            </table>
        </div>
    </div>
</div>
<!-- Project Details Modal -->
<div class="modal fade" id="projectDetailsModal" tabindex="-1" aria-labelledby="projectDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="overflow:auto;height:500px">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectDetailsModalLabel">Project Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Project Details -->
                    <p><strong>Project Title:</strong> <span id="modal_project_title"></span></p>
                    <p><strong>Category:</strong> <span id="modal_project_category"></span></p>
                    <p><strong>Location:</strong> <span id="modal_location"></span></p>
                    <p><strong>Description:</strong> <span id="modal_description"></span></p>
                    <p><strong>Total Project Cost:</strong> <span id="modal_total_project_cost"></span></p>
                    <p><strong>Investment Goal:</strong> <span id="modal_investment_goal"></span></p>
                    <p><strong>Projected Revenue:</strong> <span id="modal_projected_revenue"></span></p>
                    <p><strong>Projected Profit:</strong> <span id="modal_projected_profit"></span></p>
                    <p><strong>Developer Info:</strong> <span id="modal_developer_info"></span></p>
                    <p><strong>Building Materials:</strong> <span id="modal_building_materials"></span></p>
                    <p><strong>Investment Types Available:</strong> <span id="modal_investment_types"></span></p>
                    <p><strong>Verification Status:</strong> <span id="modal_verification_status"></span></p>

                    <!-- Image Section -->
                    <div class="mt-4">
                        <h6>Images</h6>
                        <p><strong>Featured Image:</strong></p>
    <img id="modal_featured_image" src="" alt="Featured Image" class="img-fluid mb-3 zoomable-img" style="max-height: 200px; max-width: 100%;" onclick="openZoomModal(this.src)">
    
    <p><strong>Additional Images:</strong></p>
    <div id="modal_additional_images_container" class="d-flex flex-wrap gap-2">
        <!-- Images will be dynamically inserted here -->
    </div>
    
    <p><strong>Land Title Document:</strong></p>
    <img id="modal_land_document" src="" alt="Land Document" class="img-fluid zoomable-img" style="max-height: 200px; max-width: 100%;" onclick="openZoomModal(this.src)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="verifyProject()">Verify Project</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade image-zoom-modal" id="imageZoomModal" tabindex="-1" aria-hidden="true">
        <button type="button" class="zoom-close-btn" onclick="closeZoomModal()">×</button>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img id="zoomedImage" src="" alt="Zoomed Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>



<!-- Recent Investments Table -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Recent Investments</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_investments as $investment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($investment['project_title']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($investment['investment_type'])); ?></td>
                        <td>
                            <?php 
                            if ($investment['investment_type'] == 'cash') {
                                echo '$' . number_format($investment['investment_value'], 2);
                            } else if ($investment['investment_type'] == 'labor') {
                                echo number_format($investment['investment_value'], 1) . ' hours';
                            } else {
                                echo number_format($investment['investment_value']) . ' units';
                            }
                            ?>
                        </td>
                        <td><?php echo ucfirst(htmlspecialchars($investment['status'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($investment['created_at'])); ?></td>
                        <td>
                            <?php if ($investment['certificate_url']): ?>
                                <a href="<?php echo htmlspecialchars($investment['certificate_url']); ?>" target="_blank">View</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Investment Distribution Chart
const distributionCtx = document.getElementById('investmentDistributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($investment_types, 'investment_type')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($investment_types, 'total_value')); ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
            hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
    },
    options: {
        responsive: true,
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Investment Type Distribution'
        }
    }
});

// Monthly Trends Chart
const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($item) {
            return date('M Y', strtotime($item['month'] . '-01'));
        }, array_reverse($monthly_trends))); ?>,
        datasets: [{
            label: 'Cash Investments ($)',
            data: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'cash_value')); ?>,
            borderColor: '#FF6384',
            fill: false
        }, {
            label: 'Labor Investments',
            data: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'labor_count')); ?>,
            borderColor: '#36A2EB',
            fill: false
        }, {
            label: 'Material Investments',
            data: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'materials_count')); ?>,
            borderColor: '#FFCE56',
            fill: false
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});


function showProjectDetails(project) {
    // Set text details
    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerText = value || "N/A";
        }
    };

    setText('modal_project_title', project.title);
    setText('modal_project_category', project.project_category);
    setText('modal_location', project.location);
    setText('modal_description', project.description);
    setText('modal_total_project_cost', project.total_project_cost);
    setText('modal_investment_goal', project.investment_goal);
    setText('modal_projected_revenue', project.projected_revenue);
    setText('modal_projected_profit', project.projected_profit);
    setText('modal_developer_info', project.developer_info);
    setText('modal_building_materials', project.building_materials);
    setText('modal_verification_status', project.verification_status || "Unverified");

    // Investment types (array or string)
    const investmentTypes = project.investment_types;
    setText('modal_investment_types', Array.isArray(investmentTypes) ? investmentTypes.join(', ') : investmentTypes);

    // Set image sources with default fallback
    const setImage = (id, src) => {
        const img = document.getElementById(id);
        if (img) {
            img.src = src || 'https://www.creativefabrica.com/wp-content/uploads/2020/07/03/Nothing-to-See-Here-Illustration-Graphics-4531211-1.png'; // Path to your placeholder image
        }
    };


    // Handle multiple project images
    const additionalImagesContainer = document.getElementById('modal_additional_images_container');
    additionalImagesContainer.innerHTML = ''; // Clear existing images
    
    if (project.additional_images) {
        const imageUrls = project.additional_images.split(',');
        imageUrls.forEach(imageUrl => {
            const imgWrapper = document.createElement('div');
            imgWrapper.className = 'position-relative';
            
            const img = document.createElement('img');
            img.src = imageUrl || 'https://www.creativefabrica.com/wp-content/uploads/2020/07/03/Nothing-to-See-Here-Illustration-Graphics-4531211-1.png';
            img.alt = 'Project Image';
            img.className = 'project-additional-image zoomable-img';
            img.onclick = () => openZoomModal(img.src);
            
            imgWrapper.appendChild(img);
            additionalImagesContainer.appendChild(imgWrapper);
        });
    }

    // Set featured image - assuming it's stored in project.featured_image
    setImage('modal_featured_image', project.featured_image);
    setImage('modal_additional_image', project.project_images);
    setImage('modal_land_document', project.land_title_document);
    // console.log(project_images)
    

    // Show the modal
    const projectDetailsModal = new bootstrap.Modal(document.getElementById('projectDetailsModal'));
    projectDetailsModal.show();
}


function openZoomModal(imageSrc) {
            const zoomModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
            document.getElementById('zoomedImage').src = imageSrc;
            zoomModal.show();
        }

        function closeZoomModal() {
            const zoomModal = bootstrap.Modal.getInstance(document.getElementById('imageZoomModal'));
            zoomModal.hide();
        }

function verifyProject() {
    // Implement verification functionality here
    alert("Project verification in progress...");
}

</script>