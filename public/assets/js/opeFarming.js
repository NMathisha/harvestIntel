var opeFarming = {
    createOperation: function () {
    var name = $("#op_name").val();
    var type = $("#op_type").val();
    var total_acres = $("#to_acres").val();
    var season_start = $("#s_start").val();
    var season_end = $("#s_end").val();
    var expected_yield = $("#ex_yield").val();
    var yield_unit = $("#un_yield").val();
    var commodity_price = $("#c_price").val();
    var location = $("#loca").val();

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    $.ajax({
        url: "/operation/create",
        type: "POST",
        cache: false,
        data: {
            name: name,
            type: type,
            total_acres: total_acres,
            season_start: season_start,
            season_end: season_end,
            expected_yield: expected_yield,
            yield_unit: yield_unit,
            commodity_price: commodity_price,
            location: location,
        },
        success: function (response) {
            if (response && response.message) {
                swal(response.message, { icon: "success" });
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                swal(response.error || "No changes applied", { icon: "warning" });
            }
        },
        error: function (xhr) {
            console.error("Error:", xhr.status, xhr.statusText, xhr.responseText);
            swal("Something went wrong. Please try again.", { icon: "error" });
        },
    });
},

    editOpe(id) {
        var name = $("#op_name").val();
        var type = $("#op_type").val();
        var t_acres = $("#to_acres").val();
        var s_start = $("#s_start").val();
        var s_end = $("#s_end").val();
        var un_yield = $("#un_yield").val();
        var c_price = $("#c_price").val();
        var loca = $("#loca").val();
        var ex_yield = $("#ex_yield").val();

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });
        $.ajax({
            url: "/operations/update",
            type: "POST",
            cache: false,
            data: {
                id: id,
                name: name,
                type: type,
                t_acres: t_acres,
                s_start: s_start,
                s_end: s_end,
                un_yield: un_yield,
                c_price: c_price,
                loca: loca,
                // ex_yield:ex_yield
            },
            success: function (response) {
                if (response.success) {
                    swal(response.message, {
                        icon: "success",
                    });
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                } else {
                    swal(response.error || "No changes applied", {
                        icon: "warning",
                    });
                }
            },
            error: function (xhr) {
                console.log(
                    "Request Status: " +
                        xhr.status +
                        " Status Text: " +
                        xhr.statusText +
                        " " +
                        xhr.responseText
                );
                swal("Something went wrong. Please try again.", {
                    icon: "error",
                });
            },
        });
    },

    delete: function(id) {
        // Add debug logging
        console.log('Delete operation called with ID:', id);

        try {
            // Show confirmation dialog using SweetAlert2
            Swal.fire({
                title: 'Are you sure?',
                text: "This operation cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Make DELETE request
                    $.ajax({
                        url: `/operations/${id}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Show success message
                            new Swal({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Operation has been deleted.',
                                timer: 2000
                            });

                            // Refresh the operations list
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        },
                        error: function(xhr) {
                            // Show error message
                            new Swal({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to delete operation.'
                            });
                            console.error('Delete failed:', xhr.responseText);
                        }
                    });
                }
            });
        } catch (err) {
            console.error('Delete operation error:', err);
            throw err;
        }
    },

    //load operaions in operation page
    loadOperations: function(id) {

       alert(id);
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: "/ope/" + id,
            type: "GET",
            cache: false,
            data: {
                id: id,
            },
            success: function (response) {
                if (response) {
                    console.log(response);
                    // $("#table_data tr").hide();
                    // $("#table_data").append(response);
                    // $("#btnSave").prop("disabled", false);
                    //console.log(response.data.s_start);

                    $("#op_name").val(response.name);
                    $("#op_type").val(response.type);
                    $("#to_acres").val(response.total_acres);
                    $("#s_start").val(
                        response.season_start
                            ? response.season_start.split("T")[0]
                            : ""
                    );
                    $("#s_end").val(
                        response.season_end
                            ? response.season_end.split("T")[0]
                            : ""
                    );
                    $("#un_yield").val(response.yield_unit);
                    $("#c_price").val(response.commodity_price);




                    $("#ex_yield").val(response.expected_yield);
                    // $('#ex_yield').val(response.yield_unit);
                    // $("#btnSave").prop("disabled", false);
                    $("#btnSave").text("Update");

                    $("#btnSave")
                        .removeAttr("onclick")
                        .attr(
                            "onclick",
                            'opeFarming.editOpe("' + response.id + '")'
                        );

                        // Update location with proper Select2 handling
            if ($("#loca").hasClass('select2-hidden-accessible')) {
                $("#loca").val(response.location).trigger('change');
                // Force Select2 to update its display
                setTimeout(() => {
                    $("#loca").select2('destroy');
                    $("#loca").select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: 'Select an option',
                        allowClear: true
                    });
                }, 100);
            } else {
                $("#loca").val(response.location);
            }

            // Add debug logging
            console.log('Location update:', {
                expectedValue: response.location,
                selectValue: $("#loca").val(),
                isSelect2: $("#loca").hasClass('select2-hidden-accessible')
            });

                    // ...existing code...
                }
            },
            error: function (xhr) {
                console.log(
                    "Request Status: " +
                        xhr.status +
                        " Status Text: " +
                        xhr.statusText +
                        " " +
                        xhr.responseText
                );
            },
        });
    },

    view: function (id) {
        $.ajax({
            url: `/operations/${id}`,
            method: "GET",
            success: function (data) {
                console.log(data);
                let html = `
                <!-- ====== Operation Info ====== -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="tag" class="me-1"></i>Operation Name</h6>
                        <p class="text-muted mb-0">${data.name}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="layers" class="me-1"></i>Type</h6>
                        <p class="text-muted mb-0 text-capitalize">${
                            data.type
                        }</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="crop" class="me-1"></i>Total Acres</h6>
                        <p class="text-muted mb-0">${data.total_acres}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="map-pin" class="me-1"></i>Location</h6>
                        <p class="text-muted mb-0">${data.location}</p>
                    </div>
                </div>

                <!-- ====== Yield & Price ====== -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="trending-up" class="me-1"></i>Expected Yield</h6>
                        <p class="text-muted mb-0">${data.expected_yield}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="package" class="me-1"></i>Unit Yield</h6>
                        <p class="text-muted mb-0">${data.yield_unit}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="dollar-sign" class="me-1"></i>Commodity Price</h6>
                        <p class="text-muted mb-0">Rs. ${parseFloat(
                            data.commodity_price
                        ).toFixed(2)}</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="calendar" class="me-1"></i>Season Start</h6>
                        <p class="text-muted mb-0">${data.season_start}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="calendar" class="me-1"></i>Season End</h6>
                        <p class="text-muted mb-0">${data.season_end}</p>
                    </div>
                </div>

                <!-- ====== Weather Info ====== -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="thermometer" class="me-1"></i>Avg Temperature</h6>
                        <p class="text-muted mb-0">${
                            data.operation.weather_data.avg_temperature
                        } °C</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="droplet" class="me-1"></i>Avg Humidity</h6>
                        <p class="text-muted mb-0">${
                            data.operation.weather_data.humidity_avg
                        } %</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="sun" class="me-1"></i>Sunshine Hours</h6>
                        <p class="text-muted mb-0">${
                            data.operation.weather_data.sunshine_hours
                        } h</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-2">
                        <h6 class="fw-bold mb-1"><i data-feather="cloud-rain" class="me-1"></i>Total Rainfall</h6>
                        <p class="text-muted mb-0">${
                            data.operation.weather_data.total_rainfall
                        } mm</p>
                    </div>
                </div>`;

                $("#operationDetails").html(html);
                $("#operationViewModal").modal("show");

                // Refresh Feather icons
                if (typeof feather !== "undefined") {
                    feather.replace();
                }
            },
            error: function () {
                alert("Failed to load operation details.");
            },
        });
    },
    compareOpe: function () {
        // alert("hi");

        var selectedIds = [];
        $(".operation-checkbox:checked").each(function () {
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
            url: "/operations/compare",
            type: "GET",
            cache: false,
            data: {
                operation_ids: selectedIds,
            },
            success: function (response) {
                console.log(response);
                const query = selectedIds
                    .map((id) => `operation_ids[]=${id}`)
                    .join("&");
                window.location.href = `/operations/compare?${query}`;
            },
            error: function (xhr) {
                console.log(
                    "Request Status: " +
                        xhr.status +
                        " Status Text: " +
                        xhr.statusText +
                        " " +
                        xhr.responseText
                );
            },
        });
    },
};
