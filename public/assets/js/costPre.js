var costPre = {
    predict: function (id) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: "/operations/" + id + "/predict",
            type: "GET",
            dataType: "json",
            success: function (response) {
                console.log(response);
                $("#predictionContainer").html(response.html);
            },
            error: function (xhr) {
                console.error("Prediction failed:", xhr.responseText);
                $("#predictionContainer").html(
                    `<div class="alert alert-danger">Failed to load prediction data.</div>`
                );
            },
        });
    },
};
