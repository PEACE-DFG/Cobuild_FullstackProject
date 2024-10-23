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
?>

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
</script>