$(document).ready(function () {
          
    // 1. Department Bar Chart Configuration (Match index green)
    var dLabels = (window.deptLabels && window.deptLabels.length > 0) ? window.deptLabels : ["No Data"];
    var dCounts = (window.deptCounts && window.deptCounts.length > 0) ? window.deptCounts : [0];
    
    var barData = {
        labels: dLabels,
        datasets: [{
            label: "Number of Employees",
            backgroundColor: "rgba(28, 200, 138, 0.6)", // Match #1cc88a
            borderColor: "#1cc88a",
            borderWidth: 2,
            maxBarThickness: 80,
            data: dCounts
        }]
    };

    var barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
        }
    };

    var ctxBar = document.getElementById("departmentBarChart").getContext("2d");
    new Chart(ctxBar, { type: "bar", data: barData, options: barOptions });


    // 2. Gender Doughnut Chart Configuration (Mixed Theme Colors)
    var gLabels = (window.genderLabels && window.genderLabels.length > 0) ? window.genderLabels : ["No Data"];
    var gCounts = (window.genderCounts && window.genderCounts.length > 0) ? window.genderCounts : [0];
    
    var doughnutData = {
        labels: gLabels,
        datasets: [{
            data: gCounts,
            backgroundColor: ["#1cc88a", "#4e73df", "#36b9cc", "#e74a3b"],
            borderWidth: 0
        }]
    };
    var doughnutOptions = { 
        responsive: true,
        maintainAspectRatio: false,
        cutoutPercentage: 70
    };
    var ctxDoughnut = document.getElementById("genderDoughnutChart").getContext("2d");
    new Chart(ctxDoughnut, { type: "doughnut", data: doughnutData, options: doughnutOptions });


    // 3. Area of Assignment Bar Chart
    var aLabels = (window.areaLabels && window.areaLabels.length > 0) ? window.areaLabels : ["No Data"];
    var aCounts = (window.areaCounts && window.areaCounts.length > 0) ? window.areaCounts : [0];
    
    var areaBarData = {
        labels: aLabels,
        datasets: [{
            label: "Number of Employees",
            backgroundColor: "rgba(78, 115, 223, 0.6)", // Match #4e73df (blue theme)
            borderColor: "#4e73df",
            borderWidth: 2,
            maxBarThickness: 60,
            data: aCounts
        }]
    };

    var areaBarOptions = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
        }
    };

    var ctxArea = document.getElementById("areaBarChart").getContext("2d");
    new Chart(ctxArea, { type: "bar", data: areaBarData, options: areaBarOptions });


    // 4. Status Pie Chart Configuration (Mixed Theme Colors)
    var sLabels = (window.statusLabels && window.statusLabels.length > 0) ? window.statusLabels : ["No Data"];
    var sCounts = (window.statusCounts && window.statusCounts.length > 0) ? window.statusCounts : [0];

    var statusTotal = sCounts.reduce(function(sum, value) {
        return sum + (parseFloat(value) || 0);
    }, 0);

    var pieData = {
        labels: sLabels,
        datasets: [{
            data: sCounts,
            backgroundColor: ["#f6c23e", "#4e73df", "#1cc88a", "#e74a3b"],
            borderWidth: 0
        }]
    };

    var pieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.labels[tooltipItem.index] || '';
                    var value = data.datasets[0].data[tooltipItem.index] || 0;
                    var total = data.datasets[0].data.reduce(function(sum, val) {
                        return sum + (parseFloat(val) || 0);
                    }, 0);
                    var percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
                    return label + ': ' + value + ' (' + percentage + '%)';
                }
            }
        }
    };

    var statusPercentagePlugin = {
        afterDatasetsDraw: function(chart) {
            if (chart.config.type !== 'pie' && chart.config.type !== 'doughnut') {
                return;
            }
            var ctx = chart.chart.ctx;
            var meta = chart.getDatasetMeta(0);
            var total = chart.data.datasets[0].data.reduce(function(sum, value) {
                return sum + (parseFloat(value) || 0);
            }, 0);

            meta.data.forEach(function(segment, index) {
                var value = chart.data.datasets[0].data[index];
                if (total === 0 || !value) {
                    return;
                }
                var percentage = ((value / total) * 100).toFixed(0);
                var model = segment._model;
                var midAngle = model.startAngle + (model.endAngle - model.startAngle) / 2;
                var radius = model.outerRadius - (model.outerRadius - model.innerRadius) / 2;
                var x = model.x + Math.cos(midAngle) * radius;
                var y = model.y + Math.sin(midAngle) * radius;

                ctx.save();
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(percentage + '%', x, y);
                ctx.restore();
            });
        }
    };

    Chart.plugins.register(statusPercentagePlugin);

    var ctxPie = document.getElementById("statusPieChart").getContext("2d");
    new Chart(ctxPie, { type: "pie", data: pieData, options: pieOptions });

    
    // 5. Clickable Compliance Cards
    $('.ibox.clickable').on('click', function () {
        var status = $(this).data('status');
        var isCompliant = status === 1 || status === '1';
        var modalTitle = isCompliant ? 'Compliant Attendees This Monday' : 'Non-Compliant Attendees This Monday';
        var loadingRow = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading list...</em></td></tr>';

        $('#complianceModalLabel').text(modalTitle);
        $('#compliance_list_body').html(loadingRow);
        $('#complianceModal').modal('show');

        $.ajax({
            url: 'dashboard.php',
            method: 'POST',
            data: {
                action: 'fetch_compliance_list',
                status: status
            },
            success: function(response) {
                $('#compliance_list_body').html(response);
            },
            error: function() {
                $('#compliance_list_body').html('<tr><td colspan="5" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Unable to load employees. Please refresh and try again.</em></td></tr>');
            }
        });
    });


    // 6. SweetAlert Logout Confirmation
    $('#logout-btn').on('click', function(e) {
        e.preventDefault(); 
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of your current session.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1cc88a',
            cancelButtonColor: '#e74a3b',
            confirmButtonText: 'Yes, log out!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php'; 
            }
        });
    });

});