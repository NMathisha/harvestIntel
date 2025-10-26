var cost = {
    loadCost: function (id) {
        //    alert('Hi');
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: "/getCost/" + id,
            type: "GET",
            cache: false,
            data: {
                id: id,
            },
            success: function (response) {
                if (response) {
                    console.log("loadcost", response);
                    // $("#table_data tr").hide();
                    // $("#table_data").append(response);
                    // $("#btnSave").prop("disabled", false);
                    //console.log(response.data.s_start);

                    $("#far_ope").append(
                        $("<option>", {
                            value: response.data.category.id,
                            text: response.data.category.description, // Assuming the text for the option is also in the response
                        })
                    );
                    $("#des").val(response.data.description);
                    // $("#cost_cat").val(response.data.cost_category_id);

                    $("#cost_cat").append(
                        $("<option>", {
                            value: response.data.category.id,
                            text: response.data.category.description, // Assuming the text for the option is also in the response
                        })
                    );
                    $("#to_acres").val(response.data.des);
                    $("#i_date").val(
                        response.season_start
                            ? response.incurred_date.data.split("T")[0]
                            : ""
                    );

                    $("#uni_price").val(response.data.unit_price);
                    $("#c_price").val(response.data.commodity_price);
                    $("#qty").val(response.data.quantity);
                    $("#unit").val(response.data.unit);
                    // $("#unit").val(response.unit);
                    $("#amount").val(response.data.amount);
                    $("#btnSave").text("Update");

                    $("#btnSave")
                        .removeAttr("onclick")
                        .attr(
                            "onclick",
                            'opeFarming.editOpe("' + response.data.id + '")'
                        );
                } else {
                    {
                        swal("Sorry", "Data Not found", "info");
                        $("#btnSave").prop("disabled", true);
                    }
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

    getCost:function(id)
    {

       $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

           $.ajax({
            url: "/getCost/" + id,
            type: "GET",
            cache: false,
            data: {
                id: id,
            },
            success: function (response) {
                if (response) {
                    console.log( response);

  $("#cost_data").empty();

                // Create new row with fetched data
                const row = `
                    <tr>
                        <td>${response.data.id}</td>
                        <td>${response.data.description || '-'}</td>
                        <td>${response.data.operation?.name || '-'}</td>
                        <td>${response.data.amount || '-'}</td>
                        <td>${new Date(response.data.incurred_date).toLocaleDateString('en-GB') || '-'}</td>
                        <td>${response.data.quantity || '-'}</td>
                        <td>${response.data.unit || '-'}</td>
                        <td>Rs. ${response.data.unit_price ? parseFloat(response.data.unit_price).toFixed(2) : '-'}</td>
                    </tr>
                `;

                // Add the new row to the table
                $("#cost_data").html(row);

                // Optional: Highlight the row briefly
                $("#cost_data tr").addClass('bg-light-success');
                setTimeout(() => {
                    $("#cost_data tr").removeClass('bg-light-success');
                }, 1000);






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



            // Show error message in table
            $("#cost_data").html(`
                <tr>
                    <td colspan="8" class="text-danger">
                        Failed to load cost data. Please try again.
                    </td>
                </tr>
            `);
            },
        });

    }
};
