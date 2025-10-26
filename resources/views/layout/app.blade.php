<!doctype html>
<html lang="en">


<!-- In your app.blade.php head section -->

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin & Dashboard Template">
    <meta name="author" content="AdminKit">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('assets/img/icons/icon-48x48.png') }}" />

    <title>Profile | Harvest Intel</title>

    <!-- Make sure these paths are correct -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/pagination.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- jQuery (must be loaded before Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- If using AdminKit template, you might need additional CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css" rel="stylesheet">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <!-- Include Select2 CSS and JS in your Blade layout (once) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Select2 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

</head>


<body>
    <div class="wrapper">
        @include('layout.sidebar')

        <div class="main">

            @include('layout.navbar')
            <main class="content">
                <div class="container-fluid p-0">

                    @yield('content')

                </div>
            </main>

            @include('layout.footer')
        </div>


    </div>


    <script src="{{ asset('assets/js/app.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
    var chartLineEl = document.getElementById("chartjs-dashboard-line");
    if (chartLineEl) {
        var ctx = chartLineEl.getContext("2d");
        var gradient = ctx.createLinearGradient(0, 0, 0, 225);
        gradient.addColorStop(0, "rgba(215, 227, 244, 1)");
        gradient.addColorStop(1, "rgba(215, 227, 244, 0)");

        // Line chart
        new Chart(ctx, {
            type: "line",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "Sales ($)",
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: window.theme?.primary || '#3B7DDD',
                    data: [2115, 1562, 1584, 1892, 1587, 1923, 2566, 2448, 2805, 3438, 2917, 3327]
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        intersect: false,
                        mode: 'index'
                    },
                    filler: {
                        propagate: false
                    }
                },
                hover: {
                    intersect: true
                },
                scales: {
                    x: {
                        reverse: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        ticks: {
                            stepSize: 1000
                        },
                        display: true,
                        grid: {
                            borderDash: [3, 3],
                            color: "rgba(0,0,0,0.0)"
                        }
                    }
                }
            }
        });
    }
});
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
    // Pie chart
    var pieEl = document.getElementById("chartjs-dashboard-pie");
    if (pieEl) {
        new Chart(pieEl, {
            type: "pie",
            data: {
                labels: ["Chrome", "Firefox", "IE"],
                datasets: [{
                    data: [4306, 3801, 1689],
                    backgroundColor: [
                        window.theme?.primary || '#3B7DDD',
                        window.theme?.warning || '#FFC107',
                        window.theme?.danger || '#DC3545'
                    ],
                    borderWidth: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                cutout: '75%' // Replace deprecated cutoutPercentage
            }
        });
    }
});
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
    // Bar chart
    var barEl = document.getElementById("chartjs-dashboard-bar");
    if (barEl) {
        new Chart(barEl, {
            type: "bar",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "This year",
                    backgroundColor: window.theme?.primary || '#3B7DDD',
                    borderColor: window.theme?.primary || '#3B7DDD',
                    hoverBackgroundColor: window.theme?.primary || '#3B7DDD',
                    hoverBorderColor: window.theme?.primary || '#3B7DDD',
                    data: [54, 67, 41, 55, 62, 45, 55, 73, 60, 76, 48, 79],
                    barPercentage: 0.75,
                    categoryPercentage: 0.5
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        grid: {
                            display: false
                        },
                        stacked: false,
                        ticks: {
                            stepSize: 20
                        }
                    },
                    x: {
                        stacked: false,
                        grid: {
                            color: "transparent"
                        }
                    }
                }
            }
        });
    }
}); // Fixed missing closing bracket
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    var mapEl = document.getElementById("world_map");
    if (mapEl && typeof jsVectorMap !== 'undefined') {
        var markers = [
            { coords: [31.230391, 121.473701], name: "Shanghai" },
            { coords: [28.704060, 77.102493], name: "Delhi" },
            { coords: [6.524379, 3.379206], name: "Lagos" },
            { coords: [35.689487, 139.691711], name: "Tokyo" },
            { coords: [23.129110, 113.264381], name: "Guangzhou" },
            { coords: [40.7127837, -74.0059413], name: "New York" },
            { coords: [34.052235, -118.243683], name: "Los Angeles" },
            { coords: [41.878113, -87.629799], name: "Chicago" },
            { coords: [51.507351, -0.127758], name: "London" },
            { coords: [40.416775, -3.703790], name: "Madrid" }
        ];

        var map = new jsVectorMap({
            map: "world",
            selector: "#world_map",
            zoomButtons: true,
            markers: markers,
            markerStyle: {
                initial: {
                    r: 9,
                    strokeWidth: 7,
                    strokeOpacity: 0.4,
                    fill: window.theme.primary
                },
                hover: {
                    fill: window.theme.primary,
                    stroke: window.theme.primary
                }
            },
            zoomOnScroll: false
        });

        window.addEventListener("resize", function () {
            map.updateSize();
        });
    }
});

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dateEl = document.getElementById("datetimepicker-dashboard");
            if (dateEl && typeof flatpickr !== 'undefined') {
                var date = new Date(Date.now() - 5 * 24 * 60 * 60 * 1000);
                var defaultDate = date.getUTCFullYear() + "-" + (date.getUTCMonth() + 1) + "-" + date.getUTCDate();
                dateEl.flatpickr({
                    inline: true,
                    prevArrow: "<span title=\"Previous month\">&laquo;</span>",
                    nextArrow: "<span title=\"Next month\">&raquo;</span>",
                    defaultDate: defaultDate
                });
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize sidebar functionality
            const sidebar = document.querySelector('.js-sidebar');
            const sidebarContent = document.querySelector('.js-simplebar');

            if (sidebar && sidebarContent) {
                // Initialize SimpleBar if needed
                if (typeof SimpleBar !== 'undefined') {
                    new SimpleBar(sidebarContent);
                }

                // Add hover event listeners
                const sidebarItems = document.querySelectorAll('.sidebar-item');

                sidebarItems.forEach(item => {
                    item.addEventListener('mouseenter', function() {
                        this.classList.add('hover');
                    });

                    item.addEventListener('mouseleave', function() {
                        this.classList.remove('hover');
                    });
                });
            }
        });
    </script>
    {{-- <script>
        const dateInput = document.querySelector("#datePicker");
        if (dateInput) {
            flatpickr(dateInput);
        }
    </script> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/opeFarming.js') }}"></script>
    <script src="{{ asset('assets/js/costController.js') }}"></script>

    <script src="{{ asset('assets/js/costPre.js') }}"></script>
    <script src="{{ asset('assets/js/cost.js') }}"></script>
    <script src="{{ asset('assets/js/opePre.js') }}"></script>
    <script src="{{ asset('assets/js/analysis_cost.js') }}"></script>
    <script src="{{ asset('assets/js/category.js') }}"></script>
</body>

</html>
