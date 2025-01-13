<?php

class AdminPayoutConfigurationController extends ModuleAdminController
{
    /**
     * check api credentials
     *
     * @return void
     * @throws PrestaShopException
     */
    public function displayAjaxCheckApiCredentials(): void
    {
        if (!($this->module instanceof Payout)) {
            $this->renderJsonResult(false, $this->module->l('Module instance is not payout', 'configuration'));
            return;
        }

        if (!Tools::getIsset("PAYOUT_SANDBOX_MODE") || !Tools::getIsset("PAYOUT_CLIENT_ID") || !Tools::getIsset("PAYOUT_SECRET")) {
            $this->renderJsonResult(false, $this->module->l('Some of parameters for credentials check are not available in request', 'configuration'));
            return;
        }
        
        $sandbox = Tools::getValue("PAYOUT_SANDBOX_MODE") === 'true';
        $baseUrl = $sandbox ? Payout::API_URL_SANDBOX : Payout::API_URL;
        $clientId = Tools::getValue("PAYOUT_CLIENT_ID");
        $clientSecret = Tools::getValue("PAYOUT_SECRET");

        $response = $this->checkApiCredentials($baseUrl, $clientId, $clientSecret);

        $this->renderJsonResult($response === true, $response === true ? $this->module->l('Credentials are valid', 'configuration') : ($this->module->l("Credentials can not be verified, reason:", 'configuration') . ' ' . $response));
    }

    /**
     * render json result
     *
     * @param bool $result
     * @param string $message
     *
     * @return void
     * @throws PrestaShopException
     */
    private function renderJsonResult(bool $result, string $message): void
    {
        ob_end_clean();
        header('Content-Type: application/json');

        $responseArray = [
            'result' => $result,
            'message' => $message
        ];

        if ($this->module->isPrestashop1_6) {
            /** @noinspection PhpDeprecationInspection */
            die(Tools::jsonEncode($responseArray));
        } else {
            $this->ajaxRender(json_encode($responseArray));
        }
    }

    /**
     * check api credentials
     *
     * @param string $baseUrl
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return bool|string
     */
    private function checkApiCredentials(string $baseUrl, string $clientId, string $clientSecret)
    {
        require(dirname(__FILE__) . '/../../classes/PayoutConnection.php');
        try {
            $payoutConnection = new PayoutConnection($baseUrl);
            $payoutConnection->authenticate(
                'authorize',
                $clientId,
                $clientSecret,
                Payout::AUTHENTICATE_TIMEOUT
            );
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
