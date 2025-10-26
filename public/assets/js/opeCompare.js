var opeCompare = {
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
