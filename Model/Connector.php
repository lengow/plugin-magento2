<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model;

use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import as LengowImport;

/**
 * Lengow connector
 */
class Connector
{
    /**
     * @var string url of Lengow solution
     */
    // public const LENGOW_URL = 'lengow.io';
    public const LENGOW_URL = 'lengow.net';

    /**
     * @var string url of the Lengow API
     */
    // private const LENGOW_API_URL = 'https://api.lengow.io';
    private const LENGOW_API_URL = 'https://api.lengow.net';

    /* Lengow API routes */
    public const API_ACCESS_TOKEN = '/access/get_token';
    public const API_ORDER = '/v3.0/orders';
    public const API_ORDER_MOI = '/v3.0/orders/moi/';
    public const API_ORDER_ACTION = '/v3.0/orders/actions/';
    public const API_MARKETPLACE = '/v3.0/marketplaces';
    public const API_PLAN = '/v3.0/plans';
    public const API_CMS = '/v3.1/cms';
    public const API_CMS_CATALOG = '/v3.1/cms/catalogs/';
    public const API_CMS_MAPPING = '/v3.1/cms/mapping/';
    public const API_PLUGIN = '/v3.0/plugins';

    /* Request actions */
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';

    /* Return formats */
    public const FORMAT_JSON = 'json';
    public const FORMAT_STREAM = 'stream';

    /* Http codes */
    public const CODE_200 = 200;
    public const CODE_201 = 201;
    public const CODE_401 = 401;
    public const CODE_403 = 403;
    public const CODE_404 = 404;
    public const CODE_500 = 500;
    public const CODE_504 = 504;

    /**
     * @var array success HTTP codes for request
     */
    private $successCodes = [
        self::CODE_200,
        self::CODE_201,
    ];

    /**
     * @var array authorization HTTP codes for request
     */
    private $authorizationCodes = [
        self::CODE_401,
        self::CODE_403,
    ];

    /**
     * @var integer Authorization token lifetime
     */
    private $tokenLifetime = 3000;

    /**
     * @var array default options for curl
     */
    private $curlOpts = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'lengow-cms-magento2',
    ];

    /**
     * @var string the access token to connect
     */
    private $accessToken;

    /**
     * @var string the secret to connect
     */
    private $secret;

    /**
     * @var string temporary token for the authorization
     */
    private $token;

    /**
     * @var array Lengow url for curl timeout
     */
    private $lengowUrls = [
        self::API_ORDER => 20,
        self::API_ORDER_MOI => 10,
        self::API_ORDER_ACTION => 15,
        self::API_MARKETPLACE => 15,
        self::API_PLAN => 5,
        self::API_CMS => 5,
        self::API_CMS_CATALOG => 10,
        self::API_CMS_MAPPING => 10,
        self::API_PLUGIN => 5,
    ];

    /**
     * @var array API requiring no arguments in the call url
     */
    private $apiWithoutUrlArgs = [
        self::API_ACCESS_TOKEN,
        self::API_ORDER_ACTION,
        self::API_ORDER_MOI,
    ];

    /**
     * @var array API requiring no authorization for the call url
     */
    private $apiWithoutAuthorizations = [
        self::API_PLUGIN,
    ];

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * Constructor
     *
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Init a new connector
     *
     * @param array $params optional options for init
     * string access_token Lengow access token
     * string secret       Lengow secret
     */
    public function init(array $params = []): void
    {
        $this->accessToken = $params['access_token'];
        $this->secret = $params['secret'];
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public function isCurlActivated(): bool
    {
        return function_exists('curl_version');
    }

    /**
     * Check API Authentication
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function isValidAuth(bool $logOutput = false): bool
    {
        if (!$this->isCurlActivated()) {
            return false;
        }
        list($accountId, $accessToken, $secret) = $this->configHelper->getAccessIds();
        if ($accountId === null) {
            return false;
        }
        try {
            $this->init(['access_token' => $accessToken, 'secret' => $secret]);
            $this->connect(false, $logOutput);
        } catch (LengowException $e) {
            $message = $this->dataHelper->decodeLogMessage($e->getMessage(), false);
            $error = $this->dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
            $this->dataHelper->log(DataHelper::CODE_CONNECTOR, $error, $logOutput);
            return false;
        }
        return true;
    }

    /**
     * Get result for a query Api
     *
     * @param string $type request type (GET / POST / PUT / PATCH)
     * @param string $api request url
     * @param array $args request params
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     */
    public function queryApi(string $type, string $api, array $args = [], string $body = '', bool $logOutput = false)
    {
        if (!in_array($type, [self::GET, self::POST, self::PUT, self::PATCH])) {
            return false;
        }
        try {
            $authorizationRequired = !in_array($api, $this->apiWithoutAuthorizations, true);
            list($accountId, $accessToken, $secret) = $this->configHelper->getAccessIds();
            if ($accountId === null && $authorizationRequired) {
                return false;
            }
            $this->init(['access_token' => $accessToken, 'secret' => $secret]);
            $type = strtolower($type);
            $args = $authorizationRequired ? array_merge([LengowImport::ARG_ACCOUNT_ID => $accountId], $args) : $args;
            $results = $this->$type($api, $args, self::FORMAT_STREAM, $body, $logOutput);
        } catch (LengowException $e) {
            $message = $this->dataHelper->decodeLogMessage($e->getMessage(), false);
            $error = $this->dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
            $this->dataHelper->log(DataHelper::CODE_CONNECTOR, $error, $logOutput);
            return false;
        }
        // don't decode into array as we use the result as an object
        return json_decode($results);
    }

    /**
     * Get the account id from the API
     *
     * @param string $accessToken Lengow api access token
     * @param string $secret Lengow api secret token
     * @param false $logOutput see log or not
     *
     * @return int|null
     */
    public function getAccountIdByCredentials(string $accessToken, string $secret, bool $logOutput = false): ?int
    {
        $this->init(['access_token' => $accessToken, 'secret' => $secret]);
        try {
            $data = $this->callAction(
                self::API_ACCESS_TOKEN,
                [
                    'access_token' => $accessToken,
                    'secret' => $secret,
                ],
                self::POST,
                self::FORMAT_JSON,
                '',
                $logOutput
            );
        } catch (LengowException $e) {
            $message = $this->dataHelper->decodeLogMessage($e->getMessage(), false);
            $error = $this->dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
            $this->dataHelper->log(DataHelper::CODE_CONNECTION, $error, $logOutput);
            return null;
        }
        return $data['account_id'] ? (int) $data['account_id'] : null;
    }

    /**
     * Connection to the API
     *
     * @param boolean $force Force cache Update
     * @param boolean $logOutput see log or not
     *
     * @throws LengowException
     */
    public function connect(bool $force = false, bool $logOutput = false): void
    {
        $token = $this->configHelper->get(ConfigHelper::AUTHORIZATION_TOKEN);
        $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_AUTHORIZATION_TOKEN);
        if (!$force
            && $token !== null
            && $updatedAt !== null
            && $token !== ''
            && (time() - $updatedAt) < $this->tokenLifetime
        ) {
            $authorizationToken = $token;
        } else {
            $authorizationToken = $this->getAuthorizationToken($logOutput);
            $this->configHelper->set(ConfigHelper::AUTHORIZATION_TOKEN, $authorizationToken);
            $this->configHelper->set(ConfigHelper::LAST_UPDATE_AUTHORIZATION_TOKEN, time());
        }
        $this->token = $authorizationToken;
    }

    /**
     * Get API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    public function get(
        string $api,
        array $args = [],
        string $format = self::FORMAT_JSON,
        string $body = '',
        bool $logOutput = false
    ) {
        return $this->call($api, $args, self::GET, $format, $body, $logOutput);
    }

    /**
     * Post API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    public function post(
        string $api,
        array $args = [],
        string $format = self::FORMAT_JSON,
        string $body = '',
        bool $logOutput = false
    ) {
        return $this->call($api, $args, self::POST, $format, $body, $logOutput);
    }

    /**
     * Put API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    public function put(
        string $api,
        array $args = [],
        string $format = self::FORMAT_JSON,
        string $body = '',
        bool $logOutput = false
    ) {
        return $this->call($api, $args, self::PUT, $format, $body, $logOutput);
    }

    /**
     * Patch API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    public function patch(
        string $api,
        array $args = [],
        string $format = self::FORMAT_JSON,
        string $body = '',
        bool $logOutput = false
    ) {
        return $this->call($api, $args, self::PATCH, $format, $body, $logOutput);
    }

    /**
     * The API method
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    private function call(
        string $api,
        array $args = [],
        string $type = self::GET,
        string $format = self::FORMAT_JSON,
        string $body = '',
        bool $logOutput = false
    ) {
        try {
            if (!in_array($api, $this->apiWithoutAuthorizations, true)) {
                $this->connect(false, $logOutput);
            }
            $data = $this->callAction($api, $args, $type, $format, $body, $logOutput);
        } catch (LengowException $e) {
            if (in_array($e->getCode(), $this->authorizationCodes, true)) {
                $this->dataHelper->log(
                    DataHelper::CODE_CONNECTOR,
                    $this->dataHelper->setLogMessage(
                        'API call failed - authorization token expired - attempt to recover a new token'
                    ),
                    $logOutput
                );
                if (!in_array($api, $this->apiWithoutAuthorizations, true)) {
                    $this->connect(true, $logOutput);
                }
                $data = $this->callAction($api, $args, $type, $format, $body, $logOutput);
            } else {
                throw new LengowException($e->getMessage(), $e->getCode());
            }
        }
        return $data;
    }

    /**
     * Call API action
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|PATCH
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     *
     * @throws LengowException
     */
    private function callAction(string $api, array $args, string $type, string $format, string $body, bool $logOutput)
    {
        $result = $this->makeRequest($type, $api, $args, $this->token, $body, $logOutput);
        return $this->format($result, $format);
    }

    /**
     * Get authorization token from Middleware
     *
     * @param boolean $logOutput see log or not
     *
     * @return string
     *
     * @throws LengowException
     */
    private function getAuthorizationToken(bool $logOutput): string
    {
        // reset temporary token for the new authorization
        $this->token = null;
        $data = $this->callAction(
            self::API_ACCESS_TOKEN,
            [
                'access_token' => $this->accessToken,
                'secret' => $this->secret,
            ],
            self::POST,
            self::FORMAT_JSON,
            '',
            $logOutput
        );
        // return a specific error for get_token
        if (!isset($data['token'])) {
            throw new LengowException(
                $this->dataHelper->setLogMessage('no authorization token returned'),
                self::CODE_500
            );
        }
        if ($data['token'] === '') {
            throw new LengowException(
                $this->dataHelper->setLogMessage('the returned authorization token is empty'),
                self::CODE_500
            );
        }
        return $data['token'];
    }

    /**
     * Make Curl request
     *
     * @param string $type Lengow method API call
     * @param string $api Lengow API url
     * @param array $args Lengow method API parameters
     * @param string|null $token temporary access token
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return bool|string
     *
     * @throws LengowException
     */
    private function makeRequest(
        string $type,
        string $api,
        array $args = [],
        string $token = null,
        string $body = '',
        bool $logOutput = false
    ) {
        // Define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $ch = curl_init();
        // get default curl options
        $opts = $this->curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($api, $this->lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->lengowUrls[$api];
        }
        // get base url for a specific environment
        $url = self::LENGOW_API_URL . $api;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        if (isset($url['port'])) {
            $opts[CURLOPT_PORT] = $url['port'];
        }
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_VERBOSE] = false;
        if (!empty($token)) {
            $opts[CURLOPT_HTTPHEADER] = ['Authorization: ' . $token];
        }
        // get call url with the mandatory parameters
        $opts[CURLOPT_URL] = $url['scheme'] . '://' . $url['host'] . $url['path'];
        if (!empty($args) && ($type === self::GET || !in_array($api, $this->apiWithoutUrlArgs, true))) {
            $opts[CURLOPT_URL] .= '?' . http_build_query($args);
        }
        if ($type !== self::GET) {
            if (!empty($body)) {
                // sending data in json format for new APIs
                $opts[CURLOPT_HTTPHEADER] = array_merge(
                    $opts[CURLOPT_HTTPHEADER],
                    [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($body),
                    ]
                );
                $opts[CURLOPT_POSTFIELDS] = $body;
            } else {
                // sending data in string format for legacy APIs
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
            }
        }
        $this->dataHelper->log(
            DataHelper::CODE_CONNECTOR,
            $this->dataHelper->setLogMessage('call %1 %2', [$type, $opts[CURLOPT_URL]]),
            $logOutput
        );
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrorNumber = curl_errno($ch);
        curl_close($ch);
        $this->checkReturnRequest($result, $httpCode, $curlError, $curlErrorNumber);
        return $result;
    }

    /**
     * Check return request and generate exception if needed
     *
     * @param string|false $result Curl return call
     * @param integer $httpCode request http code
     * @param string $curlError Curl error
     * @param string $curlErrorNumber Curl error number
     *
     * @throws LengowException
     */
    private function checkReturnRequest($result, int $httpCode, string $curlError, string $curlErrorNumber): void
    {
        if ($result === false) {
            // recovery of Curl errors
            if (in_array($curlErrorNumber, [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED], true)) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage('API call blocked due to a timeout'),
                    self::CODE_504
                );
            }
            throw new LengowException(
                $this->dataHelper->setLogMessage('Curl error %1 - %2', [$curlErrorNumber, $curlError]),
                self::CODE_500
            );
        }
        if (!in_array($httpCode, $this->successCodes, true)) {
            $result = $this->format($result);
            // recovery of Lengow Api errors
            if (isset($result['error']['message'])) {
                throw new LengowException($result['error']['message'], $httpCode);
            }
            throw new LengowException($this->dataHelper->setLogMessage('Lengow APIs are not available'), $httpCode);
        }
    }

    /**
     * Get data in specific format
     *
     * @param mixed $data Curl response data
     * @param string $format return format of API
     *
     * @return mixed
     */
    private function format($data, string $format = self::FORMAT_JSON)
    {
        switch ($format) {
            case self::FORMAT_STREAM:
                return $data;
            default:
            case self::FORMAT_JSON:
                return json_decode($data, true);
        }
    }
}
