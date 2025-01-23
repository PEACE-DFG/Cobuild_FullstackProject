<?php
// Ensure this file is not accessed directly
if (!defined('MAIN_DASHBOARD')) {
    die('Direct access not permitted');
}

// Fetch investor-specific data
$stmt = $conn->prepare("SELECT 
    COUNT(*) AS total_investments,
        COALESCE(SUM(CASE WHEN investment_type = 'cash' AND status = 'accepted' THEN amount ELSE 0 END), 0) AS total_cash_invested,
        COUNT(DISTINCT project_id) AS total_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_investments
    FROM 
        investment_intentions
    WHERE 
        user_id = ?");
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

 // Then use your existing query
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
    pc.category_name,
    GROUP_CONCAT(pi.image_path) as additional_images
    FROM projects p
    LEFT JOIN project_categories pc ON p.project_category = pc.id
    LEFT JOIN project_images pi ON p.id = pi.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC");
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch project materials
$stmt = $conn->prepare("SELECT 
    id,
    project_id,
    material_name,
    material_category,
    quantity,
    unit
    FROM project_materials
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);  // Assuming $project_id is defined earlier
$stmt->execute();
$project_materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<script>
    var globalProjectMaterials = " . json_encode($projects) . ";
</script>";

// Fetch project services
$stmt = $conn->prepare("SELECT 
   id,
    project_id,
    service_type,
    total_hours
    FROM project_services
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);  // Assuming $project_id is defined earlier
$stmt->execute();
$project_services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch project skills
$stmt = $conn->prepare("SELECT 
    id,
    project_id,
    skill_type,
    total_hours
    FROM project_skills
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$query = "
    SELECT 
        i.project_id, 
        p.title, 
        i.investment_type, 
        i.amount AS investment_value, 
        i.status, 
        i.created_at, 
        i.certificate_number, 
        i.hours, 
        i.investment_details
    FROM 
        investment_intentions i
    JOIN 
        projects p ON i.project_id = p.id
    WHERE 
        i.status IN ('accepted', 'rejected')
    ORDER BY 
        i.created_at DESC
";


// Execute the query
$result = $conn->query($query);

// Check if any rows are returned
if ($result->num_rows > 0) {
    // Store the results in an array
    $recent_investments = [];
    while ($row = $result->fetch_assoc()) {
        $recent_investments[] = $row;
    }
} else {
    $recent_investments = [];
}
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
        
        .modal-body {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .detail-card strong {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
            display: block;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .detail-card span {
            color: #34495e;
            font-weight: 500;
            word-break: break-word;
        }

        .images-section {
            margin-top: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }

        #modal_featured_image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        #modal_additional_images_container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        #modal_additional_images_container img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        #modal_additional_images_container img:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .verification-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .status-verified {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .material-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 16px;
    margin: 8px 0;
    background: #fff;
}

.material-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.material-header h3 {
    font-size: 1.2rem;
    margin: 0;
    color: #333;
}

.material-category {
    font-size: 0.9rem;
    color: #555;
    background: #f4f4f4;
    padding: 4px 8px;
    border-radius: 12px;
}

.material-details p {
    margin: 0;
    font-size: 1rem;
    color: #666;
}

.material-card:nth-child(even) {
    background: #f9f9f9; /* Alternating background for cards */
}

.service-card, .skill-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 16px;
  margin: 8px 0;
  background: #fff;
}

.service-header, .skill-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.service-header h3, .skill-header h3 {
  font-size: 1.2rem;
  margin: 0;
  color: #333;
}

.service-category, .skill-category {
  font-size: 0.9rem;
  color: #555;
  background: #f4f4f4;
  padding: 4px 8px;
  border-radius: 12px;
}

.service-details p, .skill-details p {
  margin: 0;
  font-size: 1rem;
  color: #666;
}

.service-card:nth-child(even), .skill-card:nth-child(even) {
  background: #f9f9f9; /* Alternating background for cards */
}

</style>

<h2 class="mb-4">Investor Dashboard</h2>
<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#notificationPreferencesModal">
    <i class="bi bi-bell"></i> Manage Notification Preferences
</button>

<!-- Widgets Section -->
<div class="row mt-4">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Investments</h4>
                <p class="display-5"><?php echo $investment_stats['total_investments']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Cash Invested</h4>
                <p class="display-5">₦<?php echo number_format($investment_stats['total_cash_invested'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Projects Involved</h4>
                <p class="display-5"><?php echo $investment_stats['total_projects']; ?></p>
            </div>
        </div>
    </div>

    <!-- <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Labor Hours</h4>
                <p class="display-4"><?php echo number_format($labor_stats['total_labor_hours'], 1); ?></p>
            </div>
        </div>
    </div> -->
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
                    <!-- <th>Developer Id</th> -->
                    <th>Title</th>
                    <th>Description</th>
                        <th>Date Added</th>
                        <th>Verification Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($projects as $project): ?>
    <tr onclick="showProjectDetails(<?php echo htmlspecialchars(json_encode($project)); ?>)" style="cursor:pointer">
        <!-- <td><?php echo htmlspecialchars($project['builder_id']); ?></td> -->
        <td><?php echo htmlspecialchars($project['title']); ?></td>
        <td><?php echo htmlspecialchars($project['description']); ?></td>
        <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
        <td><?php echo ucfirst(htmlspecialchars($project['status'])); ?></td>
        <td><button class="btn btn-success">Details</button></td>
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
        <div class="details-grid">
            <div class="detail-card">
                <strong>Project Title</strong>
                <span id="modal_project_title"></span>
            </div>
            <div class="detail-card">
                <strong>Category</strong>
                <span id="modal_project_category"></span>
            </div>
            <div class="detail-card">
                <strong>Location</strong>
                <span id="modal_location"></span>
            </div>
            <div class="detail-card">
                <strong>Total Project Cost</strong>
                <span id="modal_total_project_cost"></span>
            </div>
            <div class="detail-card">
                <strong>Investment Goal</strong>
                <span id="modal_investment_goal"></span>
            </div>
            <div class="detail-card">
                <strong>Projected Revenue</strong>
                <span id="modal_projected_revenue"></span>
            </div>
            <div class="detail-card">
                <strong>Projected Profit</strong>
                <span id="modal_projected_profit"></span>
            </div>
            <div class="detail-card">
                <strong>Developer Info</strong>
                <span id="modal_developer_info"></span>
            </div>
            <div class="detail-card">
                <strong>Building Materials</strong>

                <span id="modal_building_materials"></span>
            </div>
            <div class="detail-card">
                <strong>Investment Types</strong>
                <span id="modal_investment_types"></span>
            </div>
            <div class="detail-card">
                <strong>Verification Status</strong>
                <span 
                    id="modal_verification_status" 
                    class="verification-status"
                ></span>
            </div>
            <div class="detail-card">
                <strong>Description</strong>
                <span id="modal_description"></span>
            </div>
        </div>
        <div class="detail-card">
    <strong>Project Services</strong>
    <div id="modal_project_services"></div>
</div>
<div class="detail-card">
    <strong>Required Skills</strong>
    <div id="modal_project_skills"></div>
</div>

        <div class="images-section">
            <h2 style="margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">Project Images</h2>
            
            <h3 style="margin-bottom: 15px; color: #34495e;">Featured Image</h3>
            <img 
                id="modal_featured_image" 
                src="" 
                alt="Featured Image" 
                onclick="openZoomModal(this.src)"
            >
            
            <h3 style="margin: 20px 0 15px; color: #34495e;">Additional Images</h3>
            <div id="modal_additional_images_container">
                <!-- Images will be dynamically inserted here -->
            </div>
            
            <h3 style="margin: 20px 0 15px; color: #34495e;">Land Title Document</h3>
            <div 
                id="modal_land_document" 
                style="
                    background-color: #f1f3f5; 
                    padding: 15px; 
                    border-radius: 8px;
                "
            >
                <!-- Content will be dynamically inserted here by JavaScript -->
            </div>
        </div>
    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" onclick="verifyProject()">Invest</button>
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



<!-- Add this modal definition after your existing modals but before the closing body tag -->
<div class="modal fade" id="notificationPreferencesModal" tabindex="-1" aria-labelledby="notificationPreferencesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationPreferencesModalLabel">Project Notification Preferences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="notificationPreferencesForm">
                    <div class="mb-3">
                        <h6>Select Project Types for Notifications:</h6>
                        <?php
                        // Fetch current preferences
                        $stmt = $conn->prepare("
                            SELECT pc.id, pc.category_name, 
                                   CASE WHEN inp.id IS NOT NULL AND inp.is_active = 1 
                                        THEN 1 ELSE 0 END as is_subscribed
                            FROM project_categories pc
                            LEFT JOIN investor_notification_preferences inp 
                                ON pc.id = inp.category_id 
                                AND inp.investor_id = ?
                        ");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $preferences = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($preferences as $pref):
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   name="project_categories[]" 
                                   value="<?php echo $pref['id']; ?>"
                                   id="pref_<?php echo $pref['id']; ?>"
                                   <?php echo $pref['is_subscribed'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="pref_<?php echo $pref['id']; ?>">
                                <?php echo ucfirst($pref['category_name']); ?> Projects
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="savePreferences()">Save Preferences</button>
            </div>
        </div>
    </div>
</div>

<script>
function showNotification(message, isSuccess = true, callback = null) {
    const toast = document.getElementById('notificationToast');
    toast.textContent = message;
    toast.style.backgroundColor = isSuccess ? '#4CAF50' : '#f44336'; // Green for success, red for error
    toast.classList.add('show');
    toast.style.display = 'block';

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.style.display = 'none';
            if (callback) callback(); // Call the callback after hiding the notification
        }, 200);
    }, 3000); // Show for 3 seconds
}

function savePreferences() {
    const form = document.getElementById('notificationPreferencesForm');
    const formData = new FormData(form);

    fetch('save_preferences.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Preferences saved successfully:", data);
        showNotification("Preferences saved successfully!", true, () => {
            location.reload(); // Reload the page after showing the notification
        });
    })
    .catch(error => {
        console.error("Fetch error:", error);
        showNotification("An error occurred while saving preferences. Please try again.", false);
    });
}
</script>

    <!-- Recent Investments Table -->
    <div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Recent  Investments</h5>
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
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
    <?php 
    // Get the current user's ID from session
    $investor_id = $_SESSION['user_id'];
    
    // Fetch investments for this specific investor
    $stmt = $conn->prepare("
        SELECT 
            i.id,
            i.project_id,
            i.investment_type,
            i.amount as investment_value,
            i.status,
            i.created_at,
            i.certificate_number,
            i.investment_details,
            p.title,
            CASE 
                WHEN i.investment_type IN ('skill', 'service') THEN i.hours
                WHEN i.investment_type = 'materials' THEN i.quantity
                ELSE i.amount
            END as display_value
        FROM investment_intentions i 
        JOIN projects p ON i.project_id = p.id
        WHERE i.investor_id = ?
        ORDER BY i.created_at DESC"
    );
    $stmt->bind_param("i", $investor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $investments = $result->fetch_all(MYSQLI_ASSOC);
    ?>

    <?php if (!empty($investments)): ?>
        <?php foreach ($investments as $investment): ?>
            <tr>
                <!-- Project Title -->
                <td><?php echo htmlspecialchars($investment['title']); ?></td>
                
                <!-- Investment Type -->
                <td><?php echo ucfirst(htmlspecialchars($investment['investment_type'])); ?></td>
                
                <!-- Investment Value -->
                <td>
                    <?php 
                    switch($investment['investment_type']) {
                        case 'cash':
                            echo '₦' . number_format($investment['display_value'], 2);
                            break;
                        case 'skill':
                        case 'service':
                            echo number_format($investment['display_value'], 1) . ' hours';
                            break;
                        case 'materials':
                            echo number_format($investment['display_value']) . ' units';
                            break;
                        default:
                            echo 'N/A';
                    }
                    ?>
                </td>
                
                <!-- Status -->
                <td>
                    <span class="badge <?php echo $investment['status'] === 'pending' ? 'bg-warning' : 'bg-success'; ?>">
                        <?php echo ucfirst(htmlspecialchars($investment['status'])); ?>
                    </span>
                </td>
                
                <!-- Date -->
                <td><?php echo date('M d, Y', strtotime($investment['created_at'])); ?></td>
                
                <!-- Certificate -->
                <td>
                    <?php if (!empty($investment['certificate_number'])): ?>
                        <span><?php echo htmlspecialchars($investment['certificate_number']); ?></span>
                    <?php else: ?>
                        <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </td>
                
                <!-- Investment Details -->
                <td>
                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#investmentDetailsModal<?php echo $investment['id']; ?>">View Details</button>
                </td>
            </tr>

            <!-- Modal for Viewing Investment Details -->
            <div class="modal fade" id="investmentDetailsModal<?php echo $investment['id']; ?>" tabindex="-1" aria-labelledby="investmentDetailsLabel<?php echo $investment['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="investmentDetailsLabel<?php echo $investment['id']; ?>">
                                Investment Details for <?php echo htmlspecialchars($investment['title']); ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Investment Type:</strong> <?php echo ucfirst(htmlspecialchars($investment['investment_type'])); ?></p>
                            <p><strong>Investment Value:</strong> 
                                <?php 
                                switch($investment['investment_type']) {
                                    case 'cash':
                                        echo '₦' . number_format($investment['display_value'], 2);
                                        break;
                                    case 'skill':
                                    case 'service':
                                        echo number_format($investment['display_value'], 1) . ' hours';
                                        break;
                                    case 'materials':
                                        echo number_format($investment['display_value']) . ' units';
                                        break;
                                    default:
                                        echo 'N/A';
                                }
                                ?>
                            </p>
                            <?php 
                            $details = json_decode($investment['investment_details'], true);
                            if ($details): 
                            ?>
                                <div class="investment-details">
                                    <?php if ($investment['investment_type'] === 'cash'): ?>
                                        <p><strong>Cash Amount:</strong> ₦<?php echo number_format($details['amount'], 2); ?></p>
                                    <?php else: ?>
                                        <?php if (isset($details['items']) && is_array($details['items'])): ?>
                                            <p><strong>Selected Items:</strong></p>
                                            <ul>
                                                <?php foreach ($details['items'] as $item): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                        <?php if (isset($item['total_hours'])): ?>
                                                            (<?php echo $item['total_hours']; ?> hours)
                                                        <?php elseif (isset($item['quantity'])): ?>
                                                            (<?php echo $item['quantity'] . ' ' . $item['unit']; ?>)
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($investment['certificate_number'])): ?>
                                <p><strong>Certificate Number:</strong> <?php echo htmlspecialchars($investment['certificate_number']); ?></p>
                            <?php endif; ?>
                            <p><strong>Date of Investment:</strong> <?php echo date('M d, Y', strtotime($investment['created_at'])); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="text-center text-muted">No investments found.</td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>
    </div>
</div>


<script>
// Investment Distribution Chart
const distributionCtx = document.getElementById('investmentDistributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'bar',
    data: {
        labels: [
            'Total Projects Involved',
            'Total Investments',
        ],
        datasets: [{
            data: [
                <?php echo json_encode($investment_stats['total_projects'], JSON_NUMERIC_CHECK); ?>, 
                <?php echo json_encode($investment_stats['total_investments'], JSON_NUMERIC_CHECK); ?>, 
            ],
            backgroundColor: ['#FF6384', '#36A2EB'],
            hoverBackgroundColor: ['#FF6384', '#36A2EB']
        }]
    },
    options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Investment Distribution'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Investment Metrics'
                    }
                }
            }
        }
});

// Monthly Trends Chart
const trendsCanvas = document.getElementById('monthlyTrendsChart');
if (trendsCanvas) {
    const trendsCtx = trendsCanvas.getContext('2d');
    
    // Log the data to verify
    console.log('Total Cash Invested:', 
        <?php echo json_encode($investment_stats['total_cash_invested'] ?? 0, JSON_NUMERIC_CHECK); ?>
    );

    new Chart(trendsCtx, {
        type: 'bar',
        data: {
            labels: ['Total Cash Invested'], // Fixed labels
            datasets: [{
                label: 'Total Cash Invested',
                data: [
                    <?php echo json_encode($investment_stats['total_cash_invested'] ?? 0, JSON_NUMERIC_CHECK); ?>
                ],
                backgroundColor: ['#FFCE56'],
                hoverBackgroundColor: ['#FFCE56']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Total Cash Invested'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Value (NGN)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Investment Metrics'
                    }
                }
            }
        }
    });
} else {
    console.error('Canvas element not found');
}



function showProjectDetails(project) {

      // Ensure the project object is fully populated
      console.log("Setting current project:", project);
    window.currentSelectedProject = project;
    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.innerText = value || "N/A";
        }
    };

    setText('modal_project_title', project.title);
    setText('modal_project_category', project.category_name); // Now using category_name instead of project_category
    setText('modal_location', project.location);
    setText('modal_description', project.description);
    setText('modal_total_project_cost', project.total_project_cost);
    setText('modal_investment_goal', project.investment_goal);
    setText('modal_projected_revenue', project.projected_revenue);
    setText('modal_projected_profit', project.projected_profit);
    setText('modal_developer_info', project.developer_info);
    //setText('modal_building_materials', project.building_materials);
    // Assuming $project_materials is passed to the JavaScript context
    $.ajax({
        url: './ajax/get_project_materials.php',
        method: 'POST',
        data: { project_id: project.id },
        dataType: 'json',
        success: function(materials) {
            let materialsHTML = '';
            materials.forEach(material => {
                materialsHTML += `
                        <div class="material-card">
            <div class="material-header">
                <h3>${material.material_name}</h3>
                <span class="material-category">${material.material_category}</span>
            </div>
            <div class="material-details">
                <p><strong>Quantity:</strong> ${material.quantity} ${material.unit}</p>
            </div>
        </div>
                `;
            });
            document.getElementById('modal_building_materials').innerHTML = materialsHTML;
        },
        error: function(xhr, status, error) {
            console.error("Error fetching materials:", error);
        }

        
    });

     // Add these AJAX calls after the materials fetch
     $.ajax({
        url: './ajax/get_project_services.php',
        method: 'POST',
        data: { project_id: project.id },
        dataType: 'json',
        success: function(services) {
            let servicesHTML = '';
            services.forEach(service => {
                servicesHTML += `
                    <div class="service-card">
                        <div class="service-header">
                            <h3>${service.service_type}</h3>
                            <span class="service-category">${service.service_type}</span>
                        </div>
                        <div class="service-details">
                            <p><strong>Total Hours:</strong> ${service.total_hours || 'No description available'}</p>
                        </div>
                    </div>
                `;
            });
            document.getElementById('modal_project_services').innerHTML = servicesHTML;
        },
        error: function(xhr, status, error) {
            console.error("Error fetching services:", error);
        }
    });

    $.ajax({
        url: './ajax/get_project_skills.php',
        method: 'POST',
        data: { project_id: project.id },
        dataType: 'json',
        success: function(skills) {
            let skillsHTML = '';
            skills.forEach(skill => {
                skillsHTML += `
                    <div class="skill-card">
                        <div class="skill-header">
                            <h3>${skill.skill_type}</h3>
                            <span class="skill-category">${skill.skill_type}</span>
                        </div>
                        <div class="skill-details">
                            <p><strong>Total Hours:</strong> ${skill.total_hours}</p>
                        </div>
                    </div>
                `;
            });
            document.getElementById('modal_project_skills').innerHTML = skillsHTML;
        },
        error: function(xhr, status, error) {
            console.error("Error fetching skills:", error);
        }
    });

    // document.getElementById('modal_building_materials').innerHTML = materialsHTML;
//     console.log('Building Materials:', project.material_name);
    setText('modal_verification_status', project.verification_status || "Unverified");

    // Investment types handling
    const investmentTypes = project.investment_types;
    setText('modal_investment_types', Array.isArray(investmentTypes) ? investmentTypes.join(', ') : investmentTypes);

    // Handle land title document based on file type
    const landDocContainer = document.getElementById('modal_land_document');
    landDocContainer.innerHTML = ''; // Clear existing content

    if (project.land_title_document) {
        const fileExtension = project.land_title_document.split('.').pop().toLowerCase();
        
        if (fileExtension === 'pdf') {
            // Create container for PDF and download button
            const docWrapper = document.createElement('div');
            docWrapper.className = 'd-flex flex-column align-items-start gap-2';
            
            // Add PDF preview iframe
            const iframe = document.createElement('iframe');
            iframe.src = project.land_title_document;
            iframe.className = 'w-100';
            iframe.style.height = '200px';
            iframe.title = 'Land Title Document';
            
            // Add download button
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'btn btn-primary';
            downloadBtn.innerHTML = '<i class="bi bi-download"></i> View Document';
            downloadBtn.onclick = () => {
                window.open(project.land_title_document, '_blank');
            };
            
            docWrapper.appendChild(iframe);
            docWrapper.appendChild(downloadBtn);
            landDocContainer.appendChild(docWrapper);
        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Create container for image and download button
            const docWrapper = document.createElement('div');
            docWrapper.className = 'd-flex flex-column align-items-start gap-2';
            
            // Add image with zoom functionality
            const img = document.createElement('img');
            img.src = project.land_title_document;
            img.alt = 'Land Title Document';
            img.className = 'img-fluid zoomable-img';
            img.style.maxHeight = '200px';
            img.style.maxWidth = '100%';
            img.onclick = () => openZoomModal(project.land_title_document);
            
            // Add download button
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'btn btn-primary';
            downloadBtn.innerHTML = '<i class="bi bi-download"></i> Download Document';
            downloadBtn.onclick = () => {
                window.open(project.land_title_document, '_blank');
            };
            
            docWrapper.appendChild(img);
            docWrapper.appendChild(downloadBtn);
            landDocContainer.appendChild(docWrapper);
        } else {
            landDocContainer.textContent = 'Unsupported file format';
        }
    } else {
        landDocContainer.textContent = 'No document available';
    }

    // Handle additional images (existing code)
    const additionalImagesContainer = document.getElementById('modal_additional_images_container');
    additionalImagesContainer.innerHTML = '';
    
    if (project.additional_images) {
        const imageUrls = project.additional_images.split(',');
        imageUrls.forEach(imageUrl => {
            const imgWrapper = document.createElement('div');
            imgWrapper.className = 'position-relative';
            
            const img = document.createElement('img');
            img.src = imageUrl || 'path/to/placeholder-image.jpg';
            img.alt = 'Project Image';
            img.className = 'project-additional-image zoomable-img';
            img.onclick = () => openZoomModal(img.src);
            
            imgWrapper.appendChild(img);
            additionalImagesContainer.appendChild(imgWrapper);
        });
    }

    // Set featured image
    const featuredImage = document.getElementById('modal_featured_image');
    if (featuredImage) {
        if (project.featured_image) {
            featuredImage.src = project.featured_image;
            featuredImage.style.display = 'block';
        } else {
            featuredImage.src = 'path/to/placeholder-image.jpg';
        }
    }

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
    // alert("Project verification in progress...");

    const investmentTypeModal = `
    <div class="modal fade" id="investmentTypeModal" tabindex="-1" aria-labelledby="investmentTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="investmentTypeModalLabel">Select Investment Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" onclick="showInvestmentModal('cash')">Cash Investment</button>
                        <span><i>The functionalities below is coming soon,Join the waitlist</i></span>
                         <button class="btn btn-success btn-lg" onclick="showInvestmentModal('skill')" disabled>Skill Investment</button>
                         <button class="btn btn-info btn-lg" onclick="showInvestmentModal('service')" disabled>Service Investment</button>
                         <button class="btn btn-warning btn-lg" onclick="showInvestmentModal('materials')" disabled>Building Materials Investment</button>

                        





                    </div>
                </div>
            </div>
        </div>
    </div>
    `;

    // Removed this buttons first
    // to be added back after the cash investment button
    // ##########################################################################################################################################
   // <button class="btn btn-success btn-lg" onclick="showInvestmentModal('skill')">Skill Investment</button>
     //               <button class="btn btn-info btn-lg" onclick="showInvestmentModal('service')">Service Investment</button>
    //  <button class="btn btn-success btn-lg" onclick="showInvestmentModal('skill')">Skill Investment</button>
    //                     <button class="btn btn-info btn-lg" onclick="showInvestmentModal('service')">Service Investment</button>
    //                     <button class="btn btn-warning btn-lg" onclick="showInvestmentModal('materials')">Building Materials Investment</button>

    // #####################################################################################################################################

        // Create and show the cash investment modal
        const cashInvestmentModal = `
    <div class="modal fade" id="cashInvestmentModal" tabindex="-1" aria-labelledby="cashInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashInvestmentModalLabel">Cash Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="totalProjectCost" class="form-label">Total Project Cost</label>
                        <input type="text" class="form-control" id="totalProjectCost" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="remainingInvestment" class="form-label">Remaining Investment Needed</label>
                        <input type="text" class="form-control" id="remainingInvestment" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="investmentAmount" class="form-label">Your Investment Amount</label>
                        <input type="number" class="form-control" id="investmentAmount" placeholder="Enter investment amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processCashInvestment()">Invest</button>
                </div>
            </div>
        </div>
    </div>
    `;

     // Create and show the skill investment modal
     const skillInvestmentModal = `
    <div class="modal fade" id="skillInvestmentModal" tabindex="-1" aria-labelledby="skillInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="skillInvestmentModalLabel">Skill Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="availableSkillsContainer">
                        <!-- Skills will be dynamically populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processSkillInvestment()">Submit Skill Investment</button>
                </div>
            </div>
        </div>
    </div>
    `;

    // Create and show the service investment modal
    const serviceInvestmentModal = `
    <div class="modal fade" id="serviceInvestmentModal" tabindex="-1" aria-labelledby="serviceInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceInvestmentModalLabel">Service Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="availableServicesContainer">
                        <!-- Services will be dynamically populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processServiceInvestment()">Submit Service Investment</button>
                </div>
            </div>
        </div>
    </div>
    `;


    const materialsInvestmentModal = `
<div class="modal fade" id="materialsInvestmentModal" tabindex="-1" aria-labelledby="materialsInvestmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materialsInvestmentModalLabel">Building Materials Investment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="availableMaterialsContainer">
                    <!-- Materials will be dynamically populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processMaterialInvestment()">Submit Material Investment</button>
            </div>
        </div>
    </div>
</div>
`;

// Append the materials investment modal to the body
$('body').append(materialsInvestmentModal);


      // Append modals to body
      $('body').append(investmentTypeModal + cashInvestmentModal + skillInvestmentModal + serviceInvestmentModal);

// Show the investment type modal
const investmentTypeModalInstance = new bootstrap.Modal(document.getElementById('investmentTypeModal'));
investmentTypeModalInstance.show();



}
function showInvestmentModal(type) {
    // Close the investment type modal
    const investmentTypeModalInstance = bootstrap.Modal.getInstance(document.getElementById('investmentTypeModal'));
    investmentTypeModalInstance.hide();

    // Check if a project is selected
    const currentProject = window.currentSelectedProject;
    if (!currentProject) {
        console.error("No project selected");
        showNotification("Please select a project first", false);
        return;
    }

    // Check for project details
    if (!currentProject.total_project_cost) {
        console.error("Project details incomplete", currentProject);
        showNotification("Project details are incomplete", false);
        return;
    }

    if (type === 'cash') {
        // Populate cash investment modal with project details
        $('#totalProjectCost').val(currentProject.total_project_cost);
        $('#remainingInvestment').val(currentProject.investment_goal);

        const cashInvestmentModalInstance = new bootstrap.Modal(document.getElementById('cashInvestmentModal'));
        cashInvestmentModalInstance.show();
    } else if (type === 'materials') {
        // Fetch and populate available materials
        $.ajax({
            url: './ajax/get_available_materials.php',
            method: 'POST',
            data: { project_id: currentProject.id },
            dataType: 'json',
            success: function(data) {
                let itemsHTML = '';
                data.forEach(item => {
                    itemsHTML += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   name="materials[]" 
                                   value="${item.id}" 
                                   id="material_${item.id}">
                            <label class="form-check-label" for="material_${item.id}">
                                ${item.material_name} (${item.quantity} ${item.unit})
                            </label>
                        </div>
                    `;
                });
                $('#availableMaterialsContainer').html(itemsHTML);

                const materialsInvestmentModalInstance = new bootstrap.Modal(document.getElementById('materialsInvestmentModal'));
                materialsInvestmentModalInstance.show();
            },
            error: function(xhr, status, error) {
                console.error(`Error fetching materials:`, error);
                showNotification(`Failed to load available materials`, false);
            }
        });
    } else {
        // Determine URL and container based on type
        let url, containerId;
        if (type === 'skill') {
            url = './ajax/get_available_skills.php';
            containerId = '#availableSkillsContainer';
        } else if (type === 'service') {
            url = './ajax/get_available_services.php';
            containerId = '#availableServicesContainer';
        } else {
            console.error("Invalid investment type");
            return;
        }

        // Fetch and populate available skills/services
        $.ajax({
            url: url,
            method: 'POST',
            data: { project_id: currentProject.id },
            dataType: 'json',
            success: function(data) {
                let itemsHTML = '';
                data.forEach(item => {
                    itemsHTML += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   name="${type}s[]" 
                                   value="${item.id}" 
                                   id="${type}_${item.id}">
                            <label class="form-check-label" for="${type}_${item.id}">
                                ${item[type + '_type']} (${item.total_hours} hours)
                            </label>
                        </div>
                    `;
                });
                $(containerId).html(itemsHTML);

                const investmentModalInstance = new bootstrap.Modal(document.getElementById(`${type}InvestmentModal`));
                investmentModalInstance.show();
            },
            error: function(xhr, status, error) {
                console.error(`Error fetching ${type}s:`, error);
                showNotification(`Failed to load available ${type}s`, false);
            }
        });
    }
}



// Add this function to check user type
function getUserType() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: './ajax/get_user_type.php',
            method: 'GET',
            success: function(response) {
                resolve(response.user_type);
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

function processCashInvestment() {
    const investmentAmount = parseFloat($('#investmentAmount').val());
    const remainingInvestment = parseFloat($('#remainingInvestment').val());
    const currentProject = window.currentSelectedProject;

    if (isNaN(investmentAmount) || investmentAmount <= 0) {
        showNotification("Please enter a valid investment amount", false);
        return;
    }

    if (investmentAmount > remainingInvestment) {
        showNotification("Investment amount exceeds project needs", false);
        return;
    }

    $.ajax({
        url: './ajax/process_investment_intention.php',
        method: 'POST',
        data: {
            project_id: currentProject.id,
            investment_type: 'cash',
            amount: investmentAmount,
            investment_details: JSON.stringify({ amount: investmentAmount }) // Include investment details
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification("Investment intention submitted successfully! Awaiting developer approval.", true);
                const cashInvestmentModalInstance = bootstrap.Modal.getInstance(document.getElementById('cashInvestmentModal'));
                cashInvestmentModalInstance.hide();
            } else {
                showNotification(response.message || "Failed to submit investment intention", false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error submitting investment intention:", error);
            showNotification("Failed to submit investment intention", false);
        }
    });
}

function processSkillInvestment() {
    const selectedSkills = $('input[name="skills[]"]:checked').map(function() {
        return this.value;
    }).get();
    const currentProject = window.currentSelectedProject;

    if (selectedSkills.length === 0) {
        showNotification("Please select at least one skill", false);
        return;
    }

    $.ajax({
        url: './ajax/process_investment_intention.php',
        method: 'POST',
        data: {
            project_id: currentProject.id,
            investment_type: 'skill',
            investment_details: JSON.stringify(selectedSkills), // Send selected skills as investment details
            selected_skills: selectedSkills
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification("Skill investment intention submitted successfully! Awaiting developer approval.", true);
                const skillInvestmentModalInstance = bootstrap.Modal.getInstance(document.getElementById('skillInvestmentModal'));
                skillInvestmentModalInstance.hide();
            } else {
                showNotification(response.message || "Failed to submit skill investment intention", false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error submitting skill investment intention:", error);
            showNotification("Failed to submit skill investment intention", false);
        }
    });
}

function processServiceInvestment() {
    const selectedServices = $('input[name="services[]"]:checked').map(function() {
        return this.value;
    }).get();
    const currentProject = window.currentSelectedProject;

    if (selectedServices.length === 0) {
        showNotification("Please select at least one service", false);
        return;
    }

    $.ajax({
        url: './ajax/process_investment_intention.php',
        method: 'POST',
        data: {
            project_id: currentProject.id,
            investment_type: 'service',
            investment_details: JSON.stringify(selectedServices), // Send selected services as investment details
            selected_services: selectedServices
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification("Service investment intention submitted successfully! Awaiting developer approval.", true);
                const serviceInvestmentModalInstance = bootstrap.Modal.getInstance(document.getElementById('serviceInvestmentModal'));
                serviceInvestmentModalInstance.hide();
            } else {
                showNotification(response.message || "Failed to submit service investment intention", false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error submitting service investment intention:", error);
            showNotification("Failed to submit service investment intention", false);
        }
    });
}

function processMaterialInvestment() {
    const selectedMaterials = $('input[name="materials[]"]:checked').map(function() {
        return this.value;
    }).get();
    const currentProject = window.currentSelectedProject;

    if (selectedMaterials.length === 0) {
        showNotification("Please select at least one material", false);
        return;
    }

    $.ajax({
        url: './ajax/process_investment_intention.php',
        method: 'POST',
        data: {
            project_id: currentProject.id,
            investment_type: 'materials',
            investment_details: JSON.stringify(selectedMaterials) // Send selected materials as investment details
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification("Material investment intention submitted successfully! Awaiting developer approval.", true);
                const materialsInvestmentModalInstance = bootstrap.Modal.getInstance(document.getElementById('materialsInvestmentModal'));
                materialsInvestmentModalInstance.hide();
            } else {
                showNotification(response.message || "Failed to submit material investment intention", false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error submitting material investment intention:", error);
            showNotification("Failed to submit material investment intention", false);
        }
    });
}


</script>