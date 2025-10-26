<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Available Operations</h1>
        <small class="text-muted">Overview and management of all Available operations</small>
    </div>
    <a href="#" class="btn btn-dark btn-sm">
        <i class="align-middle" data-feather="list"></i>
        <span class="align-middle ms-1">View All</span>
    </a>
</div>

<div class="card flex-fill shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i data-feather="activity" class="me-2"></i>Available Operations List
        </h5>
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

        {{-- <form id="compareForm"> --}}
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="ope_table">
                <thead class="table-light text-center">
                    <tr>
                        {{-- <th>
                            <input type="checkbox" id="selectAll">
                        </th> --}}
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
                    @forelse ($availableOpe as $op)
                        <tr>
                            {{-- <td>
                                <input type="checkbox" class="operation-checkbox" value="{{ $op['id'] }}">
                            </td> --}}
                            <td>{{ $op['id'] }}</td>
                            <td>{{ $op['name'] }}</td>
                            <td>{{ ucfirst($op['type']) }}</td>
                            <td>{{ $op['location'] }}</td>
                            <td>{{ $op['total_acres'] }}</td>
                            <td>{{ $op['season_start'] }}</td>
                            <td>{{ $op['season_end'] }}</td>
                            <td>{{ $op['status'] }}</td>
                            <td>{{ number_format($op['total_costs'], 2) }}</td>
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

        <!-- 🔘 Compare Button -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div>
                {{ $availableOpe->appends(request()->query())->links() }}
            </div>
            {{-- <button type="button" class="btn btn-outline-primary btn-sm" onclick="opeCompare.compareOpe()">
                <i data-feather="bar-chart-2" class="me-1"></i> Compare Selected
            </button> --}}
        </div>

    </div>
</div>

<script>
    $(document).ready(function() {
        // 🔍 Search filter
        $("#searchOperation").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#ope_table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });



        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');

            var $container = $('#operationTableContainer');
            var originalHtml = $container.html();
            $container.css('opacity', 0.6);

            $.get(url).done(function(data) {
                var newContent = null;
                try {
                    newContent = $(data).find('#operationTableContainer').html();
                } catch (err) {
                    newContent = null;
                }

                if (!newContent || newContent.trim().length === 0) {
                    newContent = data;
                }

                $container.html(newContent);

                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                if (window.history && window.history.pushState) {
                    var parsed = new URL(url, window.location.origin);
                    window.history.pushState({}, '', parsed.href);
                }
            }).fail(function() {
                $container.html(originalHtml);
                alert('Failed to load page. Please try again.');
            }).always(function() {
                $container.css('opacity', 1);
            });
        });

        // 🧠 Collect selected IDs for comparison
        window.opeCompare = {
            compareOpe() {
                let selectedIds = [];
                $(".operation-checkbox:checked").each(function() {
                    selectedIds.push(parseInt($(this).val()));
                });

                if (selectedIds.length < 2) {
                    alert("Please select at least two operations to compare.");
                    return;
                }

                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                });

                $.ajax({
                    url: "/compare-operations",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        operation_ids: selectedIds,
                    }),
                    success: function(response) {
                        console.log("Comparison result:", response.comparison);
                        // You can show results in a modal or redirect
                    },
                    error: function(xhr) {
                        console.error("Error:", xhr.responseJSON.errors || xhr.responseJSON
                            .error);
                    },
                });
            }
        };

        // (removed duplicate handler)
    });
</script>
