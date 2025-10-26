<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i data-feather="dollar-sign" class="me-2"></i> Costs</h5>
    </div>
    <div class="card-body" id="costsTableContainer">
        <table class="table table-striped table-hover text-center align-middle mb-0" id="costs_table">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Operation</th>
                    <th>Amount</th>
                    <th>Incurred Date</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                </tr>
            </thead>
            <tbody id="cost_data">
                @forelse($costs as $cost)
                    <tr>
                        <td>{{ $cost->id }}</td>
                        <td>{{ $cost->description }}</td>
                        <td>{{ $cost->operation->name ?? '-' }}</td>
                        <td>{{ $cost->amount ?? '-' }}</td>
                        <td>{{ $cost->incurred_date->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $cost->quantity ?? '-' }}</td>
                        <td>{{ $cost->unit ?? '-' }}</td>
                        <td>Rs. {{ number_format($cost->unit_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-muted">No costs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $costs->appends(['costs_page' => request('costs_page')])->links() }}
        </div>
    </div>
</div>
