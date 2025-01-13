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

class PayoutConnection
{
    const TYPE_JSON = 'application/json';

    /**
     * @var string $base_url API base URL
     * @var $curl
     * @var array $headers HTTP request headers
     * @var mixed $response HTTP response
     */
    private $base_url;
    private $curl;
    private $headers = [];
    private $response;

    /**
     * Connection constructor.
     *
     * @param string $base_url
     */
    public function __construct(string $base_url)
    {
        $this->base_url = $base_url;
        $this->curl = curl_init();
        $this->initializeConnection();

        curl_setopt_array(
            $this->curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            ]
        );
    }

    /**
     * Add a custom header to the request.
     *
     * @param string $header HTTP request field name
     * @param string $value HTTP request field value
     */
    private function addHeader(string $header, string $value)
    {
        $this->headers[$header] = "$header: $value";
    }

    /**
     * Add a custom header to the request.
     *
     * @param string $header HTTP request field name
     * @param string $value HTTP request field value
     */
    private function removeHeader(string $header, string $value)
    {
        if ($index = array_search("$header: $value", $this->headers)) {
            unset($this->headers[$index]);
        }
    }

    /**
     * Authenticate API connection. Make an HTTP POST request to the
     * authorization endpoint  and obtain access token.
     *
     * @param string $url
     * @param string $client_id Payout client ID
     * @param string $client_secret Payout client secret
     * @param int|null $timeout timeout in seconds
     *
     * @return mixed
     * @throws Exception
     */
    public function authenticate(string $url, string $client_id, string $client_secret, int $timeout = null)
    {
        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }

        $credentials = json_encode(
            [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ]
        );

        $this->setupCurlOpts("POST", $url);

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $credentials);

        $this->response = curl_exec($this->curl);

        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
        }
        return $this->handleResponse();
    }

    /**
     * Make an HTTP POST request to the specified endpoint.
     *
     * @param string $url URL to which we send the request
     * @param mixed $body Data payload (JSON string or raw data)
     * @param array $headers additional headers
     * @param int|null $timeout timeout in seconds
     *
     * @return mixed
     * @throws Exception
     */
    public function post(string $url, $body, array $headers = [], int $timeout = null)
    {
        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }

        foreach ($headers as $key => $header) {
            $this->addHeader($key, $header);
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

        if (!is_string($body)) {
            $body = json_encode($body);
        }

        $this->setupCurlOpts("POST", $url);

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);

        $this->response = curl_exec($this->curl);

        $handledResponse = $this->handleResponse();

        foreach ($headers as $key => $header) {
            $this->removeHeader($key, $header);
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
        }
        return $handledResponse;
    }

    /**
     * Make an HTTP GET request to the specified endpoint.
     *
     * @param string $url URL to retrieve
     * @param array|bool $query Optional array of query string parameters
     * @param int|null $timeout timeout in seconds
     *
     * @return mixed
     * @throws Exception
     */
    public function get(string $url, $query = false, int $timeout = null)
    {
        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }
        if (is_array($query)) {
            $url .= '?' . http_build_query($query);
        }

        $this->setupCurlOpts("GET", $url);

        $this->response = curl_exec($this->curl);

        if (isset($timeout)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
        }
        return $this->handleResponse();
    }

    /**
     * set curl opts based on http method
     *
     * @param string $httpMethod
     * @param string $url
     * @return void
     */
    private function setupCurlOpts(string $httpMethod, string $url): void
    {
        if ($httpMethod == "GET") {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($this->curl, CURLOPT_POST, false);
            curl_setopt($this->curl, CURLOPT_PUT, false);
            curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        } else if ($httpMethod == "POST") {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_PUT, false);
            curl_setopt($this->curl, CURLOPT_HTTPGET, false);
        }
        curl_setopt($this->curl, CURLOPT_URL, $this->base_url . $url);
    }

    /**
     * initialize connection
     *
     * @return void
     */
    private function initializeConnection(): void
    {
        $this->response = '';
        $this->addHeader('Content-Type', self::TYPE_JSON);
        $this->addHeader('Accept', self::TYPE_JSON);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
    }

    /**
     * Check the response for possible errors and handle the response body returned.
     *
     * @return mixed the value encoded in json in appropriate PHP type.
     * @throws Exception
     */
    private function handleResponse()
    {
        if (curl_error($this->curl)) {
            throw new Exception('Payout connection curl error: ' . curl_error($this->curl));
        }

        $response = json_decode($this->response);

        if (isset($response->errors)) {
            throw new Exception('Payout api response error: ' . json_encode($response));
        }

        $responseHttpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if (!in_array($responseHttpCode, [200, 201])) {
            throw new Exception('Payout api response error, non ok / created http response code: ' . $responseHttpCode);
        }

        if (isset($response->token)) {
            $this->addHeader('Authorization', 'Bearer ' . $response->token);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        return $response;
    }
}
