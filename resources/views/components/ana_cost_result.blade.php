@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">← Back</a>

    <h2 class="mb-4">Cost Analysis</h2>

    <div class="row mb-4">
        <div class="col-md-3"><strong>Fixed Costs:</strong> Rs {{ number_format($analysis['fixed_costs'], 2) }}</div>
        <div class="col-md-3"><strong>Variable Costs:</strong> Rs {{ number_format($analysis['variable_costs'], 2) }}</div>
        <div class="col-md-3"><strong>Total Costs:</strong> Rs {{ number_format($analysis['total_costs'], 2) }}</div>
        <div class="col-md-3"><strong>Cost per Acre:</strong> Rs {{ number_format($analysis['cost_per_acre'], 2) }}</div>
    </div>

    <canvas id="distributionChart" height="100"></canvas>
    <canvas id="breakdownChart" height="150" class="mt-5"></canvas>

    <h4 class="mt-5">Cost Breakdown</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category</th>
                <th>Type</th>
                <th>Total (Rs)</th>
                <th>Transactions</th>
                <th>Avg/Transaction</th>
                <th>Date Range</th>
            </tr>
        </thead>
        <tbody>
            @foreach($analysis['breakdown'] as $category => $data)
            <tr>
                <td>{{ $category }}</td>
                <td>{{ ucfirst($data['type']) }}</td>
                <td>{{ number_format($data['total'], 2) }}</td>
                <td>{{ $data['transaction_count'] }}</td>
                <td>{{ number_format($data['average_per_transaction'], 2) }}</td>
                <td>{{ $data['date_range']['first'] }} to {{ $data['date_range']['last'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fixed Costs', 'Variable Costs'],
            datasets: [{
                data: [{{ $analysis['cost_distribution']['fixed_percentage'] }}, {{ $analysis['cost_distribution']['variable_percentage'] }}],
                backgroundColor: ['#007bff', '#28a745']
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Cost Distribution (%)'
                }
            }
        }
    });

    const breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
    new Chart(breakdownCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($analysis['breakdown'])) !!},
            datasets: [{
                label: 'Total Cost (Rs)',
                data: {!! json_encode(array_map(fn($item) => $item['total'], $analysis['breakdown'])) !!},
                backgroundColor: '#17a2b8'
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Cost Breakdown by Category'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: { ticks: { autoSkip: false } },
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endsection
