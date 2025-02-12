document.addEventListener('DOMContentLoaded', function () {
    const redirectButton = document.getElementById("redirectButton");
    if (redirectButton != null) {
        let countdownValue = 5;
        const countdownElement = document.getElementById("payout-countdown");
        const messageElement = document.querySelector(".payout-redirect-message p");
        const cancelButton = document.getElementById("cancelButton");
        const redirectUrl = redirectButton.getAttribute("data-url");
        const interval = setInterval(() => {
            countdownValue--;
            countdownElement.textContent = countdownValue.toString();

            if (countdownValue <= 0) {
                clearInterval(interval);
                window.location.href = redirectUrl;
            }
        }, 1000);

        cancelButton.addEventListener("click", () => {
            clearInterval(interval);
            messageElement.style.display = "none";
            cancelButton.style.display = "none";
            redirectButton.style.margin = "0 auto";
        });

        redirectButton.addEventListener("click", () => {
            window.location.href = redirectUrl;
        });
    }
});
