var category =
{
    cat_train :function(id){
        // alert(id);
          $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        $.ajax({
            url: "/categories/"+id+"/train",
            type: "GET",
            cache: false,
            data: {

            },
            success: function (response) {
                if (response) {
                      $('#training-summary').show();
            $('#training-message').text(response.message);

            const metrics = response.training_results;
            const metricRows = `
                <tr><th>Model Type</th><td>${metrics.model_type}</td></tr>
                <tr><th>MAE</th><td>${metrics.mae.toFixed(2)}</td></tr>
                <tr><th>RMSE</th><td>${metrics.rmse.toFixed(2)}</td></tr>
                <tr><th>MAPE</th><td>${metrics.mape.toFixed(2)}%</td></tr>
                <tr><th>Avg Absolute Error</th><td>${metrics.avg_absolute_error.toFixed(2)}</td></tr>
                <tr><th>Sample Count</th><td>${metrics.sample_count}</td></tr>
                <tr><th>Valid Predictions</th><td>${metrics.valid_predictions}</td></tr>
                <tr><th>Trained At</th><td>${new Date(metrics.trained_at).toLocaleString()}</td></tr>
                <tr><th>Reliability Level</th><td>${metrics.reliability_level}</td></tr>
                <tr><th>Is Reliable</th><td>${metrics.is_reliable ? 'Yes' : 'No'}</td></tr>
                <tr><th>Confidence Baseline</th><td>${metrics.confidence_baseline}</td></tr>
            `;
            $('#training-metrics tbody').html(metricRows);

            const recommendations = response.recommendations || [];
            $('#recommendations-list').empty();
            recommendations.forEach(function(rec) {
                $('#recommendations-list').append(`<li class="list-group-item">${rec}</li>`);
            });

                } else {
                    {
                        swal("Sorry", "Data Not found", "info");
                        // $("#btnSave").prop("disabled", true);
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
    train_all_cat:function(){
          $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        Swal.fire({
            title: 'Training ...',
            html: 'Please wait while we fetch the data.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "/ml/train-all-models",
            type: "POST",
            cache: false,
            data: {

            },


            success: function (response) {
                if (response) {
              setTimeout(() => {
  Swal.close();
}, 5000);
// $("#unit").val(response.unit);


                 swal({
                        title: `Training Summary for All Categories`,
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
                } else {

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
    }

}
