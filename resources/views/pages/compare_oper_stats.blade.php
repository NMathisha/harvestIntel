@extends('layout.app')

@section('content')
    {{-- @php
        dd($comparison);
    @endphp --}}
    <div class="container-fluid" id="comparisonContainer">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary mb-3">
            ← Back
        </a>

        <h2 class="fw-bold mb-4">📊 Operation Comparison Overview</h2>

        <!-- ✅ Benchmark Summary -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title fw-bold">Benchmark Summary</h5>
                <p class="mb-1">Average Cost per Acre:
                    <strong>{{ number_format($comparison['benchmarks']['average_cost_per_acre'], 2) }}</strong>
                </p>
                <p class="mb-1">Average Total Costs:
                    <strong>{{ number_format($comparison['benchmarks']['average_total_costs'], 2) }}</strong>
                </p>
                <p class="mb-0">Operations Compared:
                    <strong>{{ $comparison['benchmarks']['operation_count'] }}</strong>
                </p>
            </div>
        </div>

        <!-- ✅ Insights -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white fw-bold">Most Efficient Operation</div>
                    <div class="card-body">
                        <p><strong>{{ $comparison['insights']['most_efficient']['name'] }}</strong></p>
                        <p>Cost/Acre: {{ number_format($comparison['insights']['most_efficient']['cost_per_acre'], 2) }}</p>
                        <p>Profit Margin:
                            {{ number_format($comparison['insights']['most_efficient']['profit_margin'], 2) }}%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white fw-bold">Highest Cost Operation</div>
                    <div class="card-body">
                        <p><strong>{{ $comparison['insights']['highest_cost']['name'] }}</strong></p>
                        <p>Cost/Acre: {{ number_format($comparison['insights']['highest_cost']['cost_per_acre'], 2) }}</p>
                        <p>Profit Margin: {{ number_format($comparison['insights']['highest_cost']['profit_margin'], 2) }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Comparison Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Operation</th>
                        <th>Type</th>
                        <th>Season</th>
                        <th>Acres</th>
                        <th>Total Costs</th>
                        <th>Cost/Acre</th>
                        <th>Fixed Costs</th>
                        <th>Variable Costs</th>
                        <th>Profit Margin (%)</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @foreach ($comparison['operations'] as $op)
                        <tr>
                            <td>{{ $op['name'] }}</td>
                            <td>{{ ucfirst($op['type']) }}</td>
                            <td>{{ $op['season'] }}</td>
                            <td>{{ $op['acres'] }}</td>
                            <td>{{ number_format($op['total_costs'], 2) }}</td>
                            <td>{{ number_format($op['cost_per_acre'], 2) }}</td>
                            <td>{{ number_format($op['fixed_costs'], 2) }}</td>
                            <td>{{ number_format($op['variable_costs'], 2) }}</td>
                            <td class="{{ $op['profit_margin'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($op['profit_margin'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ✅ Charts Section -->
        <div class="row g-4">
            <!-- Cost per Acre Chart -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Cost per Acre Comparison</h5>
                        <canvas id="costChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Profit Margin Chart -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Profit Margin (%)</h5>
                        <canvas id="profitChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Fixed vs Variable Cost Breakdown -->
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Fixed vs Variable Cost Breakdown</h5>
                        <canvas id="costBreakdownChart" height="140"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const operations = @json($comparison['operations']);

            const labels = operations.map(op => op.name);
            const costPerAcre = operations.map(op => op.cost_per_acre);
            const profitMargins = operations.map(op => op.profit_margin);
            const fixedCosts = operations.map(op => op.fixed_costs);
            const variableCosts = operations.map(op => op.variable_costs);

            // Cost per Acre Chart
            new Chart(document.getElementById('costChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cost per Acre',
                        data: costPerAcre,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'LKR'
                            }
                        }
                    }
                }
            });

            // Profit Margin Chart
            new Chart(document.getElementById('profitChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Profit Margin (%)',
                        data: profitMargins,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: '%'
                            }
                        }
                    }
                }
            });

            // Fixed vs Variable Cost Breakdown
            new Chart(document.getElementById('costBreakdownChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Fixed Costs',
                            data: fixedCosts,
                            backgroundColor: 'rgba(255, 206, 86, 0.6)',
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Variable Costs',
                            data: variableCosts,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'LKR'
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
