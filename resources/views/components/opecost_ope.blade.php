<div class="container">
    <h3 class="fw-bold text-dark mb-3">Farming Dashboard</h3>

    {{-- Operations Table --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i data-feather="activity" class="me-2"></i> Operations</h5>
        </div>
        <div class="card-body" id="operationsTableContainer">
            <table class="table table-striped table-hover text-center align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Total Acres</th>
                        <th>Season Start</th>
                        <th>Season End</th>
                        <th>Status</th>
                        <th>Total Costs</th>
                        <th>Cost</th>
                        <td>test</td>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $op)
                        <tr>
                            <td>{{ $op->id }}</td>
                            <td>{{ $op->name }}</td>
                            <td>{{ ucfirst($op->type) }}</td>
                            <td>{{ $op->location }}</td>
                            <td>{{ $op->total_acres }}</td>
                            <td>{{ $op->season_start }}</td>
                            <td>{{ $op->season_end }}</td>
                            <td>{{ $op->status }}</td>
                            <td>{{ number_format($op->total_costs, 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-outline-info btn-sm"
                                    onclick="cost.getCost({{ $op->id }})">
                                    <i data-feather="eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-muted">No operations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">

                {{ $operations->appends(['operations_page' => request('operations_page')])->links() }}
            </div>
        </div>
    </div>

    {{-- Costs Table --}}

</div>
