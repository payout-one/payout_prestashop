function refund(amount) {
    clearRefundMessage();
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
        },
    });
}

function updateRefundableAmount() {
    const payoutRefundModalLoadingQuerySelector = $('#payout-refund-modal-loading');
    showPayoutRefundDivElement(payoutRefundModalLoadingQuerySelector);
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
        complete: async function () {
            hidePayoutRefundDivElement(payoutRefundModalLoadingQuerySelector);
        },
    });
}

function updateRefundRecords() {
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
            if (response.result) {
                const recordsObject = JSON.parse(response.message);
                $('#payout-refund').html(recordsObject.records_html);
                const payoutRefundLinkQuerySelector = $('a[href=\'#payout-refund\']');
                const badgeQuerySelector = payoutRefundLinkQuerySelector.children('.badge'); // on prestashop 1.6
                if (badgeQuerySelector.length === 1) {
                    badgeQuerySelector.html(recordsObject.records_count);
                } else {
                    payoutRefundLinkQuerySelector
                        .html(payoutRefundLinkQuerySelector
                            .html()
                            .replace(/(.*\()(\d+)(\).*)/, `$1${recordsObject.records_count}$3`));
                }
            }
        },
    });
}

function formatPrice(price) {
    return (Math.round(price * currencyPrecisionUnits) / currencyPrecisionUnits).toFixed(currencyPrecision);
}

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
