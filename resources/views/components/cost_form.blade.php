<div class="container-fluid mt-4">
    <div class="card border-0 shadow-lg rounded-4">
        {{-- Applying the green gradient header style --}}
        {{-- card-header bg-gradient bg-dark text-white rounded-top-4 py-3 --}}
        <div
            class="card-header bg-gradient bg-dark text-white rounded-top-4 py-3 d-flex align-items-center justify-content-between border-0">
            <h4 class="card-title mb-0 fw-bold">
                <i class="bi bi-cash-stack me-2"></i>Record New Cost
            </h4>
        </div>

        <div class="card-body bg-white p-5">
            <form id="operationForm" class="needs-validation" novalidate>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="far_ope" class="form-label fw-bold text-dark">Farming Operation</label>
                        {{-- Applied border-primary and shadow-sm to selects --}}
                        <select class="form-select form-select-lg select2 border-primary shadow-sm" id="far_ope"
                            required>
                            <option value="" selected disabled>Choose operation</option>
                            @if (!is_null($farmingCost))
                                @foreach ($farmingCost as $cost)
                                    <option value="{{ $cost->id }}">{{ $cost->description ?? 'N/A' }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="cost_cat" class="form-label fw-bold text-dark">Cost Category</label>
                        {{-- Applied border-primary and shadow-sm to selects --}}
                        <select class="form-select form-select-lg select2 border-primary shadow-sm" id="cost_cat"
                            required>
                            <option value="" selected disabled>Choose category</option>
                            @if (!is_null($costCategories))
                                @foreach ($costCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name ?? 'N/A' }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="des" class="form-label fw-bold text-dark">Description</label>
                        {{-- Changed to a proper textarea, applied styling --}}
                        <textarea id="des" class="form-control form-control-lg border-primary shadow-sm" rows="1"
                            placeholder="Provide a brief description of the expense" required></textarea>
                    </div>
                </div>

                {{-- Added row separator style --}}
                <div class="row g-4 mt-4 border-top pt-4">

                    <div class="col-md-3">
                        <label for="uni_price" class="form-label fw-bold text-dark">Unit Price</label>
                        {{-- Applied input-group for visual cue --}}
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light-subtle"><i class="bi bi-tag"></i></span>
                            <input type="number" id="uni_price" class="form-control border-primary shadow-sm"
                                placeholder="Price per unit" min="0.01" step="0.01" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="i_date" class="form-label fw-bold text-dark">Incurred Date</label>
                        <input type="date" id="i_date"
                            class="form-control form-control-lg border-primary shadow-sm" required>
                    </div>

                    <div class="col-md-3">
                        <label for="qty" class="form-label fw-bold text-dark">Quantity</label>
                        <input type="number" id="qty"
                            class="form-control form-control-lg border-primary shadow-sm" placeholder="Enter Quantity"
                            min="0" step="any" required>
                    </div>

                    <div class="col-md-3">
                        <label for="unit" class="form-label fw-bold text-dark">Unit </label>
                        <input type="text" id="unit"
                            class="form-control form-control-lg border-primary shadow-sm"
                            placeholder="e.g., Liters, Hours" required>
                    </div>
                </div>

                <div class="row g-4 mt-3">


                    <div class="col-md-3">
                        <label for="s_start" class="form-label fw-bold text-dark">Total Amount</label>
                        {{-- Applied input-group for currency symbol --}}
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light-subtle"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" id="amount" class="form-control border-primary shadow-sm"
                                placeholder="0.00" min="0.01" step="0.01" required>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-end">
                    {{-- Applied the successful button style --}}
                    <button type="button" onclick="costController.createCost()" id="btnSave"
                        class="btn btn-primary btn-lg px-5 shadow-lg">
                        <i class="bi bi-check-circle me-2"></i>Save Cost Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Check if Select2 is loaded
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded! Please check your dependencies.');
            return;
        }

        try {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Optional UX: Auto-calculate Total Amount based on Quantity * Unit Price
            $('#qty, #uni_price').on('input', function() {
                let qty = parseFloat($('#qty').val()) || 0;
                let price = parseFloat($('#uni_price').val()) || 0;
                let total = (qty * price).toFixed(2);
                $('#amount').val(total > 0 ? total : '');
            });
        } catch (error) {
            console.error('Error initializing Select2:', error);
        }
    });
</script>
