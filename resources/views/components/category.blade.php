<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Category</h1>
        <small class="text-muted">Overview and Category of all farming operations</small>
    </div>
    <a href="#" class="btn btn-dark btn-sm">
        <i class="align-middle" data-feather="list"></i>
        <span class="align-middle ms-1">View All</span>
    </a>
</div>

<div class="card flex-fill shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            {{-- <i data-feather="activity" class="me-2"></i>Category List --}}
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
                            {{-- <input type="checkbox" id="selectAll"> --}}
                        </th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Train Category</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @forelse ($categories as $cat)
                        <tr>
                            <td>
                                {{-- <input type="checkbox" class="operation-checkbox" value="{{ $op['id'] }}"> --}}
                            </td>
                            <td>{{ $cat->id }}</td>
                            <td>{{ $cat->name }}</td>
                            <td>{{ $cat->type }}</td>

                            {{-- <td>{{ $op['total_acres'] }}</td>
                            <td>{{ $op['season_start'] }}</td>
                            <td>{{ $op['season_end'] }}</td>
                            <td>{{ $op['status'] }}</td> --}}
                            {{-- <td>{{ number_format($op['total_costs'], 2) }}</td> --}}

                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                                    <button type="button" class="btn btn-outline-info"
                                        onclick="category.cat_train({{ $cat->id }})" title="Train Category">
                                        <i data-feather="eye"></i>
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



        </div>


        <!-- 🔘 Compare Button -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div>
                {{ $categories->appends(request()->query())->links() }}
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="category.train_all_cat()">
                <i data-feather="bar-chart-2" class="me-1"></i> Train All Categories
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
