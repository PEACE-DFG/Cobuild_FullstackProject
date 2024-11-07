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