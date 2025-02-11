<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__) . '/PayoutConnection.php');

class PayoutClient
{
    /**
     * @var array $config API client configuration
     * @var PayoutConnection $connection Connection instance
     */
    private $config;
    private $connection;

    /**
     * Construct the Payout API Client.
     *
     * @param array $config
     *
     * @throws RuntimeException
     */
    public function __construct(array $config = [])
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Payout needs the CURL PHP extension.');
        }
        if (!function_exists('json_decode')) {
            throw new RuntimeException('Payout needs the JSON PHP extension.');
        }

        $this->config = $config;
    }

    /**
     * Construct the Payout API Client.
     *
     * @param array $data
     *
     * @return mixed
     * @throws Exception
     */
    public function refund(array $data, string $externalId, string $currency)
    {
        $nonce = $this->generateNonce();
        $data['nonce'] = $nonce;

        $message = [
            $data['amount'],
            $currency,
            $externalId,
            $data['iban']
        ];

        $data['signature'] = $this->getSignature($message, $nonce, $this->config[Payout::PAYOUT_SECRET]);
        $response = $this->createConnection()->post('refunds', $data, [], Payout::REFUND_CHECKOUT_TIMEOUT);

        if (
            !$this->verifySignature(
                [$response->amount, $response->currency, $response->external_id, $response->iban ?? ''],
                $response->nonce,
                $this->config[Payout::PAYOUT_SECRET],
                $response->signature
            )
        ) {
            throw new Exception('Payout error: Invalid signature in API response.');
        }

        return $response;
    }

    /**
     * Verify signature obtained in API response.
     *
     * @param array $message to be signed
     * @param string $nonce to be signed
     * @param string $secret to be signed
     * @param string $signature from response
     *
     * @return bool
     */
    public static function verifySignature(array $message, string $nonce, string $secret, string $signature): bool
    {
        if (strcmp(self::getSignature($message, $nonce, $secret), $signature) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create checkout and post signed data to API.
     *
     * @param array $data
     *
     * @return mixed
     * @throws Exception
     */
    public function createCheckout(array $data)
    {
        $nonce = $this->generateNonce();
        $data['nonce'] = $nonce;

        $message = [
            $data['amount'],
            $data['currency'],
            $data['external_id'],
        ];
        $signature = $this->getSignature($message, $nonce, $this->config[Payout::PAYOUT_SECRET]);
        $data['signature'] = $signature;

        $headers = [];
        if (isset($data['idempotency_key'])) {
            $headers['Idempotency-Key'] = $data['idempotency_key'];
        }

        $prepared_checkout = json_encode($data);

        $response = $this->createConnection()->post('checkouts', $prepared_checkout, $headers, Payout::CREATE_CHECKOUT_TIMEOUT);

        if (
            !$this->verifySignature(
                [$response->amount, $response->currency, $response->external_id],
                $response->nonce,
                $this->config[Payout::PAYOUT_SECRET],
                $response->signature
            )
        ) {
            throw new Exception('Payout error: Invalid signature in API response.');
        }

        return $response;
    }

    /**
     * create payout connection
     * @return PayoutConnection
     * @throws Exception
     */
    private function createConnection(): PayoutConnection
    {
        try {
            return $this->connection();
        } catch (Exception $e) {
            throw new Exception("Error while creating or authenticating connection: " . $e->getMessage());
        }
    }

    /**
     * Get checkout details from API.
     *
     * @param integer $checkout_id
     *
     * @return mixed
     * @throws Exception
     */
    public function retrieveCheckout(int $checkout_id)
    {
        $url = 'checkouts/' . $checkout_id;
        $response = $this->createConnection()->get($url, Payout::RETRIEVE_CHECKOUT_TIMEOUT);

        if (
            !$this->verifySignature(
                [$response->amount, $response->currency, $response->external_id],
                $response->nonce,
                $this->config[Payout::PAYOUT_SECRET],
                $response->signature
            )
        ) {
            throw new Exception('Payout error: Invalid signature in API response.');
        }

        return $response;
    }

    /**
     * Get an instance of the HTTP connection object. Initializes
     * the connection if it is not already active.
     * Authorize connection and obtain access token.
     *
     * @return PayoutConnection
     * @throws Exception
     */
    private function connection(): PayoutConnection
    {
        if (!$this->connection) {
            $api_url = ($this->config[Payout::PAYOUT_SANDBOX_MODE]) ? Payout::API_URL_SANDBOX : Payout::API_URL;
            $this->connection = new PayoutConnection($api_url);
            $this->connection->authenticate(
                'authorize',
                $this->config[Payout::PAYOUT_CLIENT_ID],
                $this->config[Payout::PAYOUT_SECRET],
                Payout::AUTHENTICATE_TIMEOUT
            );
        }

        return $this->connection;
    }

    /**
     * Create signature as SHA256 hash of message.
     *
     * @param array $message
     * @param string $nonce
     * @param string $secret
     *
     * @return string
     */
    public static function getSignature(array $message, string $nonce, string $secret): string
    {
        $message[] = $nonce;
        $message[] = $secret;
        $message = implode('|', $message);

        return hash('sha256', pack('A*', $message));
    }

    /**
     * Generate nonce string. In cryptography, a nonce is an arbitrary number
     * that can be used just once in a cryptographic communication.
     * https://en.wikipedia.org/wiki/Cryptographic_nonce
     *
     * @return string
     */
    private function generateNonce(): string
    {
        // TODO use more secure nonce https://secure.php.net/manual/en/function.random-bytes.php
        $bytes = openssl_random_pseudo_bytes(32);
        $hash = base64_encode($bytes);

        return $hash;
    }
}
