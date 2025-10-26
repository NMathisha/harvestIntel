var costController = {
    createCost() {
        // 1. Collect form data
        var ope_id = $("#far_ope").val();
        var cat = $("#cost_cat").val();
        var desc = $("#des").val();
        var i_date = $("#i_date").val();
        var quantity = $("#qty").val();
        var unit = $("#unit").val();
        var unit_price = $("#uni_price").val();
        var amount = $("#amount").val(); // Corrected ID

        // Basic Form Validation
        // if (
        //     !ope_id ||
        //     !cat ||
        //     !desc ||
        //     !i_date ||
        //     !amount ||
        //     !quantity ||
        //     !unit ||
        //     !unit_price
        // ) {
        //     swal("Missing Information", "Please fill in all required fields.", {
        //         icon: "warning",
        //     });
        //     return;
        // }

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: "/operations/" + ope_id + "/costs",
            type: "POST",
            cache: false,
            data: {
                ope_id: ope_id,
                cost_category_id: cat,
                description: desc,
                incurred_date: i_date,
                quantity: quantity,
                unit: unit,
                unit_price: unit_price,
                amount: amount,
                external_factors: {
                    fuel_price: 350,
                    usd_rate: 310,
                    inflation: 8.5,
                    labor_rate_daily: 1627.5,
                },
            },
            success: function (response) {
                // console.log(response);

                if (response.success) {
                    // --- MODIFICATION START: Build Success Content with Recommendations ---

                    var operationName = response.operation_summary.name;
                    var costAmount = parseFloat(
                        response.cost.amount
                    ).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                    var totalAfter = parseFloat(
                        response.operation_summary.total_costs_after
                    ).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });

                    // 1. Core Success Message
                    var coreMessage = `Successfully added **${response.cost.description}** cost of **LKR ${costAmount}** to operation **${operationName}**.
                                   <br><br>New total cost for the operation is **LKR ${totalAfter}**.`;

                    // 2. Recommendations Section
                    var recommendationContent = "";
                    if (
                        response.recommendations &&
                        response.recommendations.length > 0
                    ) {
                        recommendationContent += '<hr class="my-3">';
                        recommendationContent +=
                            '<h6 class="text-danger fw-bold mb-2"><i class="bi bi-lightbulb me-2"></i>Actionable Insights:</h6>';
                        recommendationContent +=
                            '<ul class="list-unstyled mb-0">';

                        response.recommendations.forEach(function (rec) {
                            // Use icons and color for priority/type
                            var priorityClass =
                                rec.priority === "high"
                                    ? "text-danger fw-bold"
                                    : "text-warning";
                            var icon =
                                rec.priority === "high"
                                    ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>'
                                    : '<i class="bi bi-info-circle me-1"></i>';

                            recommendationContent += `<li class="${priorityClass}">${icon} ${rec.message}</li>`;
                        });
                        recommendationContent += "</ul>";
                    }

                    // 3. Combine content for the SweetAlert modal
                    var combinedContent = coreMessage + recommendationContent;

                    swal({
                        title: `Cost Recorded!`,
                        icon: "success",
                        buttons: {
                            confirm: {
                                text: "Close",
                                value: true,
                                visible: true,
                                className: "btn-success",
                                closeModal: true,
                            },
                        },
                        // Use a div element for complex HTML content
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: combinedContent.replace(
                                    /\*\*(.*?)\*\*/g,
                                    "<strong>$1</strong>"
                                ),
                            },
                        },
                    });

                    // Clear the form and select fields
                    $("#operationForm")[0].reset();
                    $(".select2").val(null).trigger("change");

                    // --- MODIFICATION END ---
                } else {
                    swal(response.error || "Operation failed to save.", {
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
                swal(
                    "Error",
                    "Something went wrong. Please check your network and try again.",
                    {
                        icon: "error",
                    }
                );
            },
        });
    },
};
