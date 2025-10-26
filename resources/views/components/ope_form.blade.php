<div class="container-fluid mt-4">
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-header bg-gradient bg-dark text-white rounded-top-4 py-3">
            <h4 class="card-title mb-0">
                <i class="bi bi-gear-fill me-2"></i>Add Farming Operation
            </h4>
        </div>

        <div class="card-body bg-light p-4">
            <form id="operationForm" class="needs-validation" novalidate>
                <!-- Row 1 -->
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="op_name" class="form-label fw-semibold">Operation Name</label>
                        <input type="text" id="op_name"
                            class="form-control form-control-lg border-primary shadow-sm"
                            placeholder="Enter operation name" required>
                    </div>

                    <div class="col-md-3">
                        <label for="op_type" class="form-label fw-semibold">Operation Type</label>
                        <select class="form-select form-select-lg border-primary shadow-sm" id="op_type" required>
                            <option value="" selected disabled>Choose type</option>
                            <option value="crops">Crops</option>
                            <option value="livestock">Livestock</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="to_acres" class="form-label fw-semibold">Total Acres</label>
                        <input type="number" id="to_acres"
                            class="form-control form-control-lg border-primary shadow-sm"
                            placeholder="Enter total acres" required>
                    </div>

                    <div class="col-md-3">
                        <label for="loca" class="form-label fw-semibold">Location</label>
                        <select id="loca" class="form-select form-select-lg select2 border-primary shadow-sm"
                            required>
                            <option value="">Select a region...</option>
                            <option value="Western">Western</option>
                            <option value="Central">Central</option>
                            <option value="Southern">Southern</option>
                            <option value="Northern">Northern</option>
                            <option value="North Western">North Western</option>
                            <option value="North Central">North Central</option>
                            <option value="Uva">Uva</option>
                            <option value="Sabaragamuwa">Sabaragamuwa</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="row g-4 mt-3">
                    <div class="col-md-3">
                        <label for="s_start" class="form-label fw-semibold">Season Start</label>
                        <input type="date" id="s_start"
                            class="form-control form-control-lg border-primary shadow-sm" required>
                    </div>

                    <div class="col-md-3">
                        <label for="s_end" class="form-label fw-semibold">Season End</label>
                        <input type="date" id="s_end"
                            class="form-control form-control-lg border-primary shadow-sm" required>
                    </div>

                    <div class="col-md-3">
                        <label for="ex_yield" class="form-label fw-semibold">Expected Yield</label>
                        <input type="number" id="ex_yield"
                            class="form-control form-control-lg border-primary shadow-sm"
                            placeholder="Enter expected yield" required>
                    </div>

                    <div class="col-md-3">
                        <label for="un_yield" class="form-label fw-semibold">Unit Yield</label>
                        <input type="text" id="un_yield"
                            class="form-control form-control-lg border-primary shadow-sm" placeholder="Per unit yield"
                            required>
                    </div>
                </div>

                <!-- Row 3 -->
                <div class="row g-4 mt-3">
                    <div class="col-md-3">
                        <label for="c_price" class="form-label fw-semibold">Commodity Price</label>
                        <input type="number" id="c_price"
                            class="form-control form-control-lg border-primary shadow-sm" placeholder="Enter price"
                            required>
                    </div>
                </div>

                <!-- Submit -->
                <div class="mt-5 text-end">
                    <button type="button" onclick="opeFarming.createOperation()" id="btnSave"
                        class="btn btn-primary btn-lg px-5 shadow-sm">
                        <i class="bi bi-save me-2"></i>Submit Operation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        // Destroy any existing Select2 instance
        if ($("#loca").hasClass('select2-hidden-accessible')) {
            $("#loca").select2('destroy');
        }

        // Reinitialize Select2
        $("#loca").select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true,
            // Add this to help with value setting
            sorter: function(data) {
                return data;
            }
        });

        // Debug listener
        $("#loca").on('change', function(e) {
            console.log('Location changed:', {
                value: $(this).val(),
                text: $(this).find("option:selected").text()
            });
        });
    });
</script>
