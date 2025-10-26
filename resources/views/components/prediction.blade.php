<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Predicted Cost Breakdown</h5>
    </div>
    <div class="card-body">
        <p><strong>Total Predicted Cost:</strong> Rs. {{ number_format($prediction['total_predicted_cost'], 2) }}</p>
        <p><strong>Cost per Acre:</strong> Rs. {{ number_format($prediction['predicted_cost_per_acre'], 2) }}</p>
        <p><strong>Prediction Date:</strong>
            {{ \Carbon\Carbon::parse($prediction['prediction_date'])->format('d-m-Y H:i') }}</p>
        <p><strong>Success Rate:</strong> {{ $prediction['success_rate'] }}%</p>
        <p><strong>Prediction Method:</strong>
            {{ $prediction['data_status']['fallback_used'] ? 'Industry Standards (Fallback)' : 'Machine Learning Models' }}
        </p>

        <table class="table table-bordered table-hover mt-3">
            <thead class="table-light">
                <tr>
                    <th>Category</th>
                    <th>Amount (Rs.)</th>
                    <th>Confidence</th>
                    <th>Method</th>
                    <th>Base/acre</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prediction['predictions'] as $category => $details)
                    <tr>
                        <td>{{ $category }}</td>
                        <td>{{ number_format($details['predicted_amount'], 2) }}</td>
                        <td>{{ $details['confidence_score'] }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $details['prediction_method'])) }}</td>
                        <td>{{ $details['base_amount_per_acre'] }}</td>
                        <td>{{ $details['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (!empty($prediction['errors']))
            <div class="mt-4">
                <h6 class="text-danger">Prediction Errors</h6>
                <ul class="list-group">
                    @foreach ($prediction['errors'] as $category => $error)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $category }}
                            <span class="text-danger">{{ $error }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
