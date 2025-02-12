function checkCredentials() {
    let querySelector = $('#check-filled-credentials-message');

    querySelector.hide();
    $.ajax({
        url: payoutConfigurationControllerUrl,
        type: 'post',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'CheckApiCredentials',
            PAYOUT_SANDBOX_MODE: document.getElementById("PAYOUT_SANDBOX_MODE_on").checked,
            PAYOUT_CLIENT_ID: document.getElementById("PAYOUT_CLIENT_ID").value,
            PAYOUT_SECRET: document.getElementById("PAYOUT_SECRET").value,
        },
        success(response) {
            querySelector.removeClass("alert-danger");
            querySelector.removeClass("alert-info");
            if (response.result) {
                querySelector.addClass("alert-info");
            } else {
                querySelector.addClass("alert-danger");
            }

            querySelector.html(response.message);
            querySelector.show();
        },
    });
}
