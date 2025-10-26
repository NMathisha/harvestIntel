var opePre = {
    predict: function (id) {
        // alert('predict');
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

         Swal.fire({
            title: 'Loading prediction...',
            html: 'Please wait while we fetch the data.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "/operations/"+id+"/predict",
            type: "GET",
            dataType: "html",
            data: {
                // operation_id: id,
            },
            success: function (response) {
                console.log(response);// Replace entire tbody content
                 Swal.close(); // ✅ Close the loading alert
                 $("#prediction-container").html(response);
            },
            error: function (xhr) {
                console.error(
                    "Request Status: " +
                        xhr.status +
                        " | Status Text: " +
                        xhr.statusText +
                        " | Response: " +
                        xhr.responseText
                );
                  $("#prediction-container").html("<div class='alert alert-danger'>Failed to load prediction view.</div>");
            },
        });
    },
};
