function refund(amount) {
    // let querySelector = $('#check-filled-credentials-message');
    clearRefundMessage();
    // querySelector.hide();
    const loaderQuerySelector = $('#payoutRefundLoader');
    showPayoutRefundDivElement(loaderQuerySelector);
    $.ajax({
        url: payoutRefundControllerUrl,
        type: 'post',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'Refund',
            id_order: orderId,
            amount,
        },
        success(response) {
            // querySelector.removeClass("alert-danger");
            // querySelector.removeClass("alert-info");
            // if (response.result) {
            //     querySelector.addClass("alert-info");
            // } else {
            //     querySelector.addClass("alert-danger");
            // }

            // querySelector.html(response.message);
            // querySelector.show();
            hidePayoutRefundDivElement(loaderQuerySelector);
            if (response.result) {
                const message = JSON.parse(response.message);
                showRefundMessage('success', message.message);
                updateRefundableAmount();
                updateRefundRecords();
                if (message.fullRefundAchieved) {
                    location.reload();
                }
            } else {
                showRefundMessage('danger', response.message);
            }
            // updateRefundableAmount();
            // alert(response.message);
        },
    });
}

function updateRefundableAmount() {
    // let querySelector = $('#check-filled-credentials-message');
    // querySelector.hide();
    $.ajax({
        url: payoutRefundControllerUrl,
        type: 'post',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'RefundableAmount',
            id_order: orderId,
        },
        success(response) {
            // querySelector.removeClass("alert-danger");
            // querySelector.removeClass("alert-info");
            // if (response.result) {
            //     querySelector.addClass("alert-info");
            // } else {
            //     querySelector.addClass("alert-danger");
            // }

            // querySelector.html(response.message);
            // querySelector.show();
            const message = JSON.parse(response.message);
            const payoutRefundFormQuerySelector = $('.payoutRefundForm');
            const payoutRefundNotPossibleQuerySelector = $('#payoutRefundNotPossible');
            const payoutRefundAmountQuerySelector = $('#payoutRefundAmount');
            $('#payoutOrderAmount').html(formatPrice(message.total_amount));
            $('#payoutRefundedAmount').html(formatPrice(message.refunded));
            $('#payoutMaxRefundAmount').html(formatPrice(message.refundable));
            if (message.refundPossible) {
                payoutRefundAmountQuerySelector.attr('max', formatPrice(message.refundable));
                payoutRefundAmountQuerySelector.val(formatPrice(message.refundable));
                hidePayoutRefundDivElement(payoutRefundNotPossibleQuerySelector);
                showPayoutRefundDivElement(payoutRefundFormQuerySelector);
            } else {
                hidePayoutRefundDivElement(payoutRefundFormQuerySelector);
                showPayoutRefundDivElement(payoutRefundNotPossibleQuerySelector);
            }
        },
    });
}

function updateRefundRecords() {
    // let querySelector = $('#check-filled-credentials-message');
    // querySelector.hide();
    $.ajax({
        url: payoutRefundControllerUrl,
        type: 'post',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'RefundRecords',
            id_order: orderId,
        },
        success(response) {
            // querySelector.removeClass("alert-danger");
            // querySelector.removeClass("alert-info");
            // if (response.result) {
            //     querySelector.addClass("alert-info");
            // } else {
            //     querySelector.addClass("alert-danger");
            // }

            // querySelector.html(response.message);
            // querySelector.show();
            if (response.result) {
                const recordsObject = JSON.parse(response.message);
                $('#payout-refund').html(recordsObject.records_html);
                const payoutRefundLinkQuerySelector = $('a[href=\'#payout-refund\']');
                payoutRefundLinkQuerySelector
                    .html(payoutRefundLinkQuerySelector
                        .html()
                        .replace(/(.*\()(\d+)(\).*)/, `$1${recordsObject.records_count}$3`));
            }
        },
    });
}

function formatPrice(price) {
    return (Math.round(price * currencyPrecisionUnits) / currencyPrecisionUnits).toFixed(currencyPrecision);
}

// $("#refundModal").on("hidden.bs.modal", function () {
//     clearRefundMessage();
// });

document.addEventListener("DOMContentLoaded", function () {
    $('#confirmRefund').on('click', function () {
        if (confirm(refundConfirmText)) {
            refund($('#payoutRefundAmount').val())
        }
    });
});

function showRefundMessage(type, message) {
    const payoutRefundMessages = $('#payoutRefundMessages');
    showPayoutRefundDivElement(payoutRefundMessages);
    if (type === "success") {
        payoutRefundMessages.addClass('success');
        payoutRefundMessages.removeClass('danger');
        $('#payoutRefundMessages div.alert-success p').html(message);
        $('#payoutRefundMessages div.alert-danger p').html('');
    } else {
        payoutRefundMessages.removeClass('success');
        payoutRefundMessages.addClass('danger');
        $('#payoutRefundMessages div.alert-success p').html('');
        $('#payoutRefundMessages div.alert-danger p').html(message);
    }
}

function clearRefundMessage() {
    const payoutRefundMessages = $('#payoutRefundMessages');
    hidePayoutRefundDivElement(payoutRefundMessages);
    payoutRefundMessages.removeClass('success');
    payoutRefundMessages.removeClass('danger');
    $('#payoutRefundMessages div.alert-success p').html('');
    $('#payoutRefundMessages div.alert-danger p').html('');
}

function showPayoutRefundDivElement(element) {
    element.addClass('payout-refund-div-enabled');
    element.removeClass('payout-refund-div-disabled');
}

function hidePayoutRefundDivElement(element) {
    element.removeClass('payout-refund-div-enabled');
    element.addClass('payout-refund-div-disabled');
}