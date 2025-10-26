<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Operations</h1>
        <small class="text-muted">Overview and management of all farming operations</small>
    </div>
    <a href="#" class="btn btn-dark btn-sm">
        <i class="align-middle" data-feather="list"></i>
        <span class="align-middle ms-1">View All</span>
    </a>
</div>

<div class="card flex-fill shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i data-feather="activity" class="me-2"></i>Operations List
        </h5>
        {{-- <button class="btn btn-outline-light btn-sm" onclick="opeFarming.addNew()">
            <i data-feather="plus"></i> Add Operation
        </button> --}}
    </div>

    <div class="card-body" id="operationTableContainer">
        <!-- 🔍 Search Bar -->
        <div class="row mb-3">
            <div class="col-md-4 ms-auto">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i data-feather="search"></i>
                    </span>
                    <input type="text" id="searchOperation" class="form-control border-start-0"
                        placeholder="Search operations...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="ope_table">
                <thead class="table-light text-center">
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Total Acres</th>
                        <th>Season Start</th>
                        <th>Season End</th>
                        <th>Status</th>
                        <th>Total Costs</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @forelse ($operations as $op)
                        <tr>
                            <td>
                                <input type="checkbox" class="operation-checkbox" value="{{ $op['id'] }}">
                            </td>
                            <td>{{ $op['id'] }}</td>
                            <td>{{ $op['name'] }}</td>
                            <td>{{ ucfirst($op['type']) }}</td>
                            <td>{{ $op['location'] }}</td>
                            <td>{{ $op['total_acres'] }}</td>
                            <td>{{ $op['season_start'] }}</td>
                            <td>{{ $op['season_end'] }}</td>
                            <td>{{ $op['status'] }}</td>
                            <td>{{ number_format($op['total_costs'], 2) }}</td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                                    <button type="button" class="btn btn-outline-info"
                                        onclick="opeFarming.view({{ $op['id'] }})" title="View Details">
                                        <i data-feather="eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="opeFarming.loadOperations({{ $op['id'] }})"
                                        title="Edit Operation">
                                        <i data-feather="edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="opeFarming.delete({{ $op['id'] }})" title="Delete Operation">
                                        <i data-feather="trash-2"></i>
                                    </button>
                                </div>
                            </td>
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

            <!-- 🌾 Operation Details Modal -->
            <div class="modal fade" id="operationViewModal" tabindex="-1" aria-labelledby="operationViewLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div
                            class="modal-header bg-dark text-white rounded-top-4 d-flex justify-content-between align-items-center">
                            <h5 class="modal-title fw-bold" id="operationViewLabel">
                                <i data-feather="eye" class="me-2"></i> Operation Details
                            </h5>
                            <!-- Improved Close Button -->
                            <button type="button"
                                class="btn btn-dark btn-sm p-1 d-flex align-items-center justify-content-center"
                                data-bs-dismiss="modal" aria-label="Close" style="border-radius:50%;">
                                <i data-feather="x" class="text-white"></i>
                            </button>
                        </div>

                        <div class="modal-body bg-light">
                            <div class="row g-2" id="operationDetails"></div>
                        </div>

                        <div class="modal-footer bg-white rounded-bottom-4">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                <i data-feather="x-circle" class="me-1"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Comparison Modal -->
            <div class="modal fade" id="comparisonModal" tabindex="-1" aria-labelledby="comparisonModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="comparisonModalLabel">📊 Operation Comparison Overview
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="comparisonModalBody">
                            <!-- Content will be injected here -->
                        </div>
                    </div>
                </div>
            </div>





        </div>


        <!-- 🔘 Compare Button -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div>
                {{ $operations->appends(request()->query())->links() }}
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="opeFarming.compareOpe()">
                <i data-feather="bar-chart-2" class="me-1"></i> Compare Selected
            </button>
        </div>
        {{-- <div class="mt-3">
            {{ $operations->links() }}
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

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');

        // Show simple loading state
        var $container = $('#operationTableContainer');
        var originalHtml = $container.html();
        $container.css('opacity', 0.6);

        // Use $.get so cookies and session are preserved
        $.get(url).done(function(data) {
            // Server may return a full page or only the fragment.
            // Try to extract the fragment first, fallback to entire response.
            var newContent = null;
            try {
                newContent = $(data).find('#operationTableContainer').html();
            } catch (err) {
                newContent = null;
            }

            if (!newContent || newContent.trim().length === 0) {
                // If no fragment found, assume response is fragment already
                newContent = data;
            }

            $container.html(newContent);

            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Update browser URL for shareable links without reloading
            if (window.history && window.history.pushState) {
                var parsed = new URL(url, window.location.origin);
                window.history.pushState({}, '', parsed.href);
            }
        }).fail(function() {
            // revert and alert
            $container.html(originalHtml);
            alert('Failed to load page. Please try again.');
        }).always(function() {
            $container.css('opacity', 1);
        });
    });
</script>
