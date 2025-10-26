<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Costs Categories</h1>
        <small class="text-muted">Overview of all farming cost Categories </small>
    </div>
    <a href="#" class="btn btn-dark btn-sm">
        <i class="align-middle" data-feather="list"></i>
        <span class="align-middle ms-1">View All</span>
    </a>
</div>

<div class="card flex-fill shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i data-feather="activity" class="me-2"></i>Cost Category List
        </h5>
        {{-- <button class="btn btn-outline-light btn-sm" onclick="opeFarming.addNew()">
            <i data-feather="plus"></i> Add Operation
        </button> --}}
    </div>

    <div class="card-body">
        <!-- 🔍 Search Bar -->
        <div class="row mb-3">
            <div class="col-md-4 ms-auto">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i data-feather="search"></i>
                    </span>
                    <input type="text" id="searchOperation" class="form-control border-start-0"
                        placeholder="Search cost category...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="ope_table">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Predictable</th>
                        <th>Typical Percentage</th>
                        {{-- <th>Season End</th>
                        <th>Expected Yield</th>
                        <th>Unit Yield</th>
                        <th>Commodity Price</th>
                        <th>Location</th>
                        <th>Actions</th> --}}
                    </tr>
                </thead>
                <tbody class="text-center">
                    @forelse ($cost_categories as $cat)
                        <tr>
                            <td>{{ $cat->id }}</td>
                            <td class="fw-semibold">{{ $cat->name }}</td>
                            <td class="fw-semibold">{{ $cat->type ?? 'N/A' }}</td>
                            <td class="fw-semibold">{{ $cat->description ?? 'N/A' }}</td>

                            <td>
                                <span
                                    class="badge
    @if ($cat->is_predictable === true) bg-success
    @elseif($cat->is_predictable === false) bg-warning text-dark
    @else bg-info text-dark @endif
    d-flex align-items-center">
                                    @if ($cat->is_predictable === true)
                                        <i data-feather="check-circle" class="me-1"></i> Predictable
                                    @elseif($cat->is_predictable === false)
                                        <i data-feather="alert-triangle" class="me-1"></i> Not Predictable
                                    @else
                                        <i data-feather="info" class="me-1"></i> Unknown
                                    @endif
                                </span>
                            </td>
                            <td>{{ $cat->typical_percentage }}</td>



                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i data-feather="alert-circle" class="me-1"></i> No operations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>



        </div>

        {{-- <div class="mt-3">
            {{ $cost_categories->links() }}
        </div> --}}




    </div>
</div>

<script>
    // Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // 🔍 Search filter
    $(document).ready(function() {
        $("#searchOperation").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#ope_table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
