@extends('layout.app')

@section('content')
<h1 class="h3 mb-4 text-primary fw-bold"><i class="fa-solid fa-chart-line me-2"></i>Analytics Dashboard</h1>

{{-- Stats Cards --}}
<div class="row g-4">
    @php
        $cards = [
            ['id' => 'total-operations', 'title' => 'Total Operations', 'icon' => 'fa-seedling', 'link' => '#monthlyChartSection', 'bg' => 'bg-gradient-primary'],
            ['id' => 'new-operations', 'title' => 'New Operations (30d)', 'icon' => 'fa-calendar-plus', 'link' => '#typeChartSection', 'bg' => 'bg-gradient-success'],
            ['id' => 'total-spending', 'title' => 'Total Spending', 'icon' => 'fa-dollar-sign', 'link' => '#categoryChartSection', 'bg' => 'bg-gradient-warning'],
            ['id' => 'new-costs', 'title' => 'New Costs (30d)', 'icon' => 'fa-coins', 'link' => '#mlAccuracyChartSection', 'bg' => 'bg-gradient-danger'],
        ];
    @endphp

    @foreach ($cards as $card)
    <div class="col-12 col-sm-6 col-md-3">
        <a href="{{ $card['link'] }}" class="text-decoration-none">
            <div class="card shadow-sm border-0 hover-card {{ $card['bg'] }} text-white">
                <div class="card-body text-center py-4">
                    <i class="fa-solid {{ $card['icon'] }} fa-2x mb-2"></i>
                    <h6 class="card-title fw-semibold mb-1">{{ $card['title'] }}</h6>
                    <h2 id="{{ $card['id'] }}" class="fw-bold display-6">0</h2>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Charts --}}
@php
    $charts = [
        ['id' => 'monthlyChart', 'title' => '📈 Monthly Spending Trend', 'section' => 'monthlyChartSection'],
        ['id' => 'typeChart', 'title' => '📊 Operations by Type', 'section' => 'typeChartSection'],
        ['id' => 'mlAccuracyChart', 'title' => '🤖 ML Prediction Accuracy', 'section' => 'mlAccuracyChartSection'],
        ['id' => 'categoryChart', 'title' => '💰 Top Spending Categories', 'section' => 'categoryChartSection'],
        ['id' => 'pieChart', 'title' => '🥧 Fixed vs Variable Costs', 'section' => 'pieChartSection'],
    ];
@endphp

@foreach ($charts as $chart)
<div class="row mt-5" id="{{ $chart['section'] }}">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light fw-semibold">{{ $chart['title'] }}</div>
            <div class="card-body">
                <div class="chart-wrapper" style="height:360px;">
                    <canvas id="{{ $chart['id'] }}"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Interactive Sri Lanka Map --}}
{{-- <div class="row mt-5" id="sriLankaMapSection">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light fw-semibold">🗺️ Sri Lanka Operations Map</div>
            <div class="card-body p-0">
                <div id="sriLankaMap" class="map-fill"></div>
            </div>
        </div>
    </div>
</div> --}}
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-/+0m...replace-with-valid-integrity..." crossorigin="anonymous" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* Card hover */
    .hover-card {
        transition: transform 0.28s cubic-bezier(.2,.8,.2,1), box-shadow 0.28s ease;
        border-radius: 0.6rem;
    }
    .hover-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }

    /* Gradients */
    .bg-gradient-primary { background: linear-gradient(135deg, #0d6efd, #0056b3); }
    .bg-gradient-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .bg-gradient-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
    .bg-gradient-danger  { background: linear-gradient(135deg, #dc3545, #bd2130); }

    /* Chart wrapper ensures canvas fills container */
    .chart-wrapper { position: relative; width: 100%; }
    .chart-wrapper canvas { width: 100% !important; height: 100% !important; }

    /* Map full-width and responsive */
    .map-fill { width: 100%; height: 520px; border-radius: 0 0 .6rem .6rem; }
    @media (max-width: 992px) { .map-fill { height: 420px; } }
    @media (max-width: 576px) { .map-fill { height: 320px; } }

    /* Improve card header spacing */
    .card-header { font-size: 1rem; }
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // global chart storage
    window._dashCharts = window._dashCharts || {};

    // Fetch stats and render
    fetch('/dashboard/stats')
        .then(res => res.json())
        .then(payload => {
            if (!payload || !payload.success || !payload.stats) {
                console.error('Invalid stats payload', payload);
                return;
            }
            const stats = payload.stats;

            // Update stat cards (safe guards)
            safeText('total-operations', stats?.overview?.total_operations ?? 0);
            safeText('new-operations', stats?.recent_activity?.new_operations_30d ?? 0);
            safeText('total-spending', `$${formatNumber(stats?.financial_summary?.total_spending ?? 0)}`);
            safeText('new-costs', stats?.recent_activity?.new_costs_30d ?? 0);

            // Charts
            renderChart('monthlyChart', 'line',
                (stats.monthly_trend || []).map(i => i.month),
                (stats.monthly_trend || []).map(i => i.spending),
                'Monthly Spending');

            renderChart('typeChart', 'bar',
                Object.keys(stats.operations_by_type || {}),
                Object.values(stats.operations_by_type || {}),
                'Operations');

            const accurate = stats?.ml_performance?.accurate_predictions ?? 0;
            const totalPred = stats?.ml_performance?.total_predictions ?? 0;
            renderChart('mlAccuracyChart', 'doughnut',
                ['Accurate', 'Inaccurate'],
                [accurate, Math.max(0, totalPred - accurate)],
                'ML Accuracy');

            renderChart('categoryChart', 'bar',
                (stats.top_spending_categories || []).map(c => c.name),
                (stats.top_spending_categories || []).map(c => c.total),
                'Top Categories');

            renderChart('pieChart', 'pie',
                ['Fixed Costs', 'Variable Costs'],
                [stats?.financial_summary?.fixed_costs ?? 0, stats?.financial_summary?.variable_costs ?? 0],
                'Cost Breakdown');

            // Initialize map with optional markers from stats if available
            initSriLankaMap(stats);
        })
        .catch(err => console.error('Dashboard stats error:', err));

    // Safe text set
    function safeText(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = value;
    }

    // Number formatting
    function formatNumber(v) {
        if (typeof v === 'number') return v.toLocaleString(undefined, { maximumFractionDigits: 2 });
        if (!v) return '0';
        return Number(v).toLocaleString();
    }

    // Chart renderer
    function renderChart(id, type, labels, data, label) {
        const container = document.getElementById(id);
        if (!container) return;
        const ctx = container.getContext('2d');

        // Destroy existing chart safely
        if (window._dashCharts[id] && typeof window._dashCharts[id].destroy === 'function') {
            window._dashCharts[id].destroy();
        }

        // Color settings
        const palette = ['#0d6efd','#28a745','#ffc107','#dc3545','#6f42c1','#17a2b8'];
        const bg = (type === 'pie' || type === 'doughnut') ? palette : '#0d6efd';

        const dataset = {
            label: label,
            data: data,
            backgroundColor: Array.isArray(bg) ? bg.slice(0, data.length) : bg,
            borderColor: '#333',
            borderWidth: 1,
            fill: type === 'line' ? false : true
        };

        const config = {
            type: type,
            data: { labels: labels, datasets: [dataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const v = context.parsed;
                                return `${context.dataset.label}: $${Number(v || 0).toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: (type !== 'pie' && type !== 'doughnut') ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return `$${Number(value).toLocaleString()}`; }
                        },
                        title: { display: true, text: 'Amount ($)', font: { weight: 'bold' } }
                    },
                    x: {
                        title: { display: true, text: (type === 'bar' ? 'Category / Month' : 'Month / Label'), font: { weight: 'bold' } }
                    }
                } : {}
            }
        };

        window._dashCharts[id] = new Chart(ctx, config);
    }

    // // Initialize Leaflet map and optionally add markers from stats
    // function initSriLankaMap(stats) {
    //     // Create map
    //     const map = L.map('sriLankaMap', { preferCanvas: true }).setView([7.8731, 80.7718], 7);

    //     // Add tile layer
    //     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    //         attribution: '&copy; OpenStreetMap contributors'
    //     }).addTo(map);

    //     // Add default marker (Colombo) as example
    //     const defaultMarker = L.marker([6.9271, 79.8612]).addTo(map).bindPopup('<strong>Colombo</strong><br />Default marker');

    //     // If controller provided geo data in stats.map_points (array), use it
    //     // Format expected: [{name:'Kandy', lat:7.29, lng:80.63, info:'3 ops, $7,500'}]
    //     const mapPoints = stats?.map_points ?? null;
    //     if (Array.isArray(mapPoints) && mapPoints.length) {
    //         // remove default marker
    //         map.removeLayer(defaultMarker);
    //         const group = L.featureGroup();
    //         mapPoints.forEach(p => {
    //             if (p.latitude == null || p.longitude == null) return;
    //             const m = L.marker([p.latitude, p.longitude])
    //                 .bindPopup(`<strong>${escapeHtml(p.name || 'Location')}</strong><br/>${escapeHtml(p.info || '')}`)
    //                 .addTo(map);
    //             group.addLayer(m);
    //         });
    //         if (group.getLayers().length) {
    //             group.addTo(map);
    //             map.fitBounds(group.getBounds().pad(0.2));
    //         }
    //     }
    // }

    // Simple escape for popup content
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
```


@stack('scripts')
