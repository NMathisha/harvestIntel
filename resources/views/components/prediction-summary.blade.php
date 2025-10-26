@if(isset($error))
    <div class="alert alert-danger">{{ $error }}</div>
@else
    <div class="card">
        <div class="card-header position-relative">
             <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="cutPrediction()">X</button>
            <h4>{{ $operation['name'] }} ({{ $operation['type'] }})</h4>
            <small>Season: {{ $operation['season_start'] }} to {{ $operation['season_end'] }}</small>

        </div>

        <div class="card-body">
            <p><strong>Total Acres:</strong> {{ $operation['acres'] }}</p>
            <p><strong>Total Predicted Cost:</strong> LKR {{ number_format($predictions['total_predicted_cost'], 2) }}</p>
            <p><strong>Cost per Acre:</strong> LKR {{ number_format($predictions['predicted_cost_per_acre'], 2) }}</p>

            <table class="table table-sm table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount (LKR)</th>
                        <th>Confidence</th>
                        <th>Base/acre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($predictions['predictions'] as $category => $data)
                        <tr>
                            <td>{{ $category }}</td>
                            <td>{{ number_format($data['predicted_amount'], 2) }}</td>
                            <td>{{ $data['confidence_score'] }}</td>
                            <td>{{ $data['base_amount_per_acre'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="mt-3"><strong>Generated:</strong> {{ \Carbon\Carbon::parse($metadata['generated_at'])->format('F j, Y H:i') }}</p>
            <p><strong>Method:</strong> {{ $metadata['prediction_method'] }}</p>

            {{-- <h5 class="mt-4">Failed Categories</h5> --}}
            {{-- <ul>
                @foreach ($predictions['errors'] as $key => $error)
                    <li><strong>{{ $key }}:</strong> {{ $error }}</li>
                @endforeach
            </ul> --}}
        </div>
    </div>
@endif


<script>
    function cutPrediction() {
        const container = document.getElementById('prediction-container');
        if (container) {
            container.innerHTML = '<div class="alert alert-warning">Prediction view removed.</div>';
        }
    }
</script>
