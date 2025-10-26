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
                        <th>Predict</th>
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
                                    onclick="analysis_cost.analyse({{ $op->id }})">
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
            {{ $operations->appends(['costs_page' => request('costs_page')])->links() }}
        </div>

        </div>
    </div>
    <div id="predictionContainer" class="mt-4"></div>




</div>
<script>
     $(document).on('click', '#operationsTableContainer .pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const $container = $('#operationsTableContainer');
            $container.css('opacity', 0.6);
            $.get(url).done(function(data) {
                $container.html($(data).find('#operationsTableContainer').html());
                feather.replace();
            }).always(() => $container.css('opacity', 1));
        });

</script>
