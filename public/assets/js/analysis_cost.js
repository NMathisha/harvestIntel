var analysis_cost = {
    analyse: function(id) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        // Show loading alert
        Swal.fire({
            title: 'Loading analysis...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "/operations/" + id + "/analysis",
            type: "GET",
            dataType: "json",
            success: function(response) {
                Swal.close(); // Close loading alert
    // console.log(response);
                if (response.success && response.analysis) {
                    const analysis = response.analysis;

                    // Use correct property names from the API response
                    $("#predictionContainer").html(`
                        <div class="card">
                            <div class="card-body">
                                <h5>Total Actual Costs: Rs ${analysis.total_actual_costs?.toFixed(2) ?? 'N/A'}</h5>
                                <h5>Predicted Costs: Rs ${analysis.total_predicted_costs?.toFixed(2) ?? 'N/A'}</h5>
                                <h5>Variance: Rs ${analysis.variance?.toFixed(2) ?? 'N/A'}</h5>
                                <h5>Variance Percentage: ${analysis.variance_percentage?.toFixed(2) ?? 'N/A'}%</h5>
                                <h5>Risk Level: ${analysis.risk_level ?? 'N/A'}</h5>
                                <canvas id="costChart" height="100"></canvas>
                            </div>
                        </div>
                    `);

                    // Create chart with correct data properties
                    new Chart(document.getElementById('costChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: ['Actual', 'Predicted'],
                            datasets: [{
                                label: 'Cost (Rs)',
                                data: [
                                    analysis.total_actual_costs,
                                    analysis.total_predicted_costs
                                ],
                                backgroundColor: [
                                    '#007bff',  // Blue for actual
                                    analysis.risk_level === 'High' ? '#dc3545' :  // Red for high risk
                                        analysis.risk_level === 'Medium' ? '#ffc107' :  // Yellow for medium
                                            '#28a745'  // Green for low
                                ]
                            }]
                        },
                        options: {
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Actual vs Predicted Costs'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `Rs ${context.raw.toFixed(2)}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rs ' + value.toFixed(2);
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $("#predictionContainer").html(
                        `<div class="alert alert-warning">
                            ${response.error || 'No analysis data found.'}
                        </div>`
                    );
                }
            },
            error: function(xhr) {
                Swal.close();
                const errorMsg = xhr.responseJSON?.error || 'Failed to load prediction data.';
                console.error("Prediction failed:", errorMsg);
                $("#predictionContainer").html(
                    `<div class="alert alert-danger">${errorMsg}</div>`
                );
            },
        });
    }
};
