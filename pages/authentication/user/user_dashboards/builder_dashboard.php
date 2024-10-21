<?php
// Ensure this file is not accessed directly
if (!defined('MAIN_DASHBOARD')) {
    die('Direct access not permitted');
}

// Fetch builder-specific data
$stmt = $conn->prepare("SELECT COUNT(*) as total_projects FROM projects WHERE builder_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_projects = $result->fetch_assoc()['total_projects'];

$stmt = $conn->prepare("SELECT COUNT(*) as live_projects FROM projects WHERE builder_id = ? AND status = 'live'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$live_projects = $result->fetch_assoc()['live_projects'];

$stmt = $conn->prepare("SELECT COUNT(*) as verified_projects FROM projects WHERE builder_id = ? AND verification_status = 'verified'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$verified_projects = $result->fetch_assoc()['verified_projects'];

$stmt = $conn->prepare("SELECT COUNT(*) as featured_projects FROM featured_projects fp JOIN projects p ON fp.project_id = p.id WHERE p.builder_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$featured_projects = $result->fetch_assoc()['featured_projects'];

// Fetch recent projects
$stmt = $conn->prepare("SELECT p.*, COUNT(i.id) as investment_count 
                        FROM projects p 
                        LEFT JOIN investments i ON p.id = i.project_id 
                        WHERE p.builder_id = ? 
                        GROUP BY p.id 
                        ORDER BY p.id DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch project statuses for chart
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM projects WHERE builder_id = ? GROUP BY status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project_statuses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch investment types for chart
$stmt = $conn->prepare("SELECT i.investment_type, COUNT(*) as count 
                        FROM investments i 
                        JOIN projects p ON i.project_id = p.id 
                        WHERE p.builder_id = ? 
                        GROUP BY i.investment_type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$investment_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h2 class="mb-4">Builder Dashboard</h2>

<!-- Widgets Section -->
<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Projects</h4>
                <p class="display-4"><?php echo $total_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Live Projects</h4>
                <p class="display-4"><?php echo $live_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Verified Projects</h4>
                <p class="display-4"><?php echo $verified_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Featured Projects</h4>
                <p class="display-4"><?php echo $featured_projects; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Project Status Overview</h5>
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Investment Types</h5>
                <canvas id="investmentTypesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects Table -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Recent Projects</h5>
        <div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Project Title</th>
                <th>Status</th>
                <th>Verification Status</th>
                <th>Location</th>
                <th>Investments</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_projects as $project): ?>
            <tr>
                <td><?php echo htmlspecialchars($project['title']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($project['status'])); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($project['verification_status'])); ?></td>
                <td><?php echo htmlspecialchars($project['location']); ?></td>
                <td><?php echo $project['investment_count']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    </div>
</div>

<script>
// Project Status Chart
const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
new Chart(projectStatusCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($project_statuses, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($project_statuses, 'count')); ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
            hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
        }]
    },
    options: {
        responsive: true,
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Project Status Distribution'
        }
    }
});

// Investment Types Chart
const investmentTypesCtx = document.getElementById('investmentTypesChart').getContext('2d');
new Chart(investmentTypesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($investment_types, 'investment_type')); ?>,
        datasets: [{
            label: 'Number of Investments',
            data: <?php echo json_encode(array_column($investment_types, 'count')); ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
            borderColor: ['#FF6384', '#36A2EB', '#FFCE56'],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                stepSize: 1
            }
        },
        legend: {
            display: false
        },
        title: {
            display: true,
            text: 'Investment Types Distribution'
        }
    }
});
</script>