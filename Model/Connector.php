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

use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Lengow connector
 */
class Connector
{
    /**
     * @var string url of the API Lengow
     */
    // const LENGOW_API_URL = 'https://api.lengow.io';
    // const LENGOW_API_URL = 'https://api.lengow.net';
    const LENGOW_API_URL = 'http://api.lengow.rec';
    // const LENGOW_API_URL = 'http://10.100.1.82:8081';

    /**
     * @var string url of access token API
     */
    const API_ACCESS_TOKEN = '/access/get_token';

    /**
     * @var string url of order API
     */
    const API_ORDER = '/v3.0/orders';

    /**
     * @var string url of order merchant order id API
     */
    const API_ORDER_MOI = '/v3.0/orders/moi/';

    /**
     * @var string url of order action API
     */
    const API_ORDER_ACTION = '/v3.0/orders/actions/';

    /**
     * @var string url of marketplace API
     */
    const API_MARKETPLACE = '/v3.0/marketplaces';

    /**
     * @var string url of plan API
     */
    const API_PLAN = '/v3.0/plans';

    /**
     * @var string url of statistic API
     */
    const API_STATISTIC = '/v3.0/stats';

    /**
     * @var string url of cms API
     */
    const API_CMS = '/v3.1/cms';

    /**
     * @var string request GET
     */
    const GET = 'GET';

    /**
     * @var string request POST
     */
    const POST = 'POST';

    /**
     * @var string request PUT
     */
    const PUT = 'PUT';

    /**
     * @var string request PATCH
     */
    const PATCH = 'PATCH';

    /**
     * @var string json format return
     */
    const FORMAT_JSON = 'json';

    /**
     * @var string stream format return
     */
    const FORMAT_STREAM = 'stream';

    /**
     * @var string success code
     */
    const CODE_200 = 200;

    /**
     * @var string forbidden access code
     */
    const CODE_403 = 403;

    /**
     * @var string error server code
     */
    const CODE_500 = 500;

    /**
     * @var string timeout server code
     */
    const CODE_504 = 504;

    /**
     * @var integer Authorization token lifetime
     */
    protected $_tokenLifetime = 3000;

    /**
     * @var array default options for curl
     */
    protected $_curlOpts = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'lengow-cms-magento2',
    ];

    /**
     * @var string the access token to connect
     */
    protected $_accessToken;

    /**
     * @var string the secret to connect
     */
    protected $_secret;

    /**
     * @var string temporary token for the authorization
     */
    protected $_token;

    /**
     * @var array Lengow url for curl timeout
     */
    protected $_lengowUrls = [
        self::API_ORDER => 20,
        self::API_ORDER_MOI => 10,
        self::API_ORDER_ACTION => 15,
        self::API_MARKETPLACE => 15,
        self::API_PLAN => 5,
        self::API_STATISTIC => 5,
        self::API_CMS => 5,
    ];

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Constructor
     *
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     */
    public function __construct(
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
    }

    /**
     * Init a new connector
     *
     * @param array $params optional options for init
     * string access_token Lengow access token
     * string secret       Lengow secret
     */
    public function init($params)
    {
        $this->_accessToken = $params['access_token'];
        $this->_secret = $params['secret'];
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public function isCurlActivated()
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
    public function isValidAuth($logOutput = false)
    {
        if (!$this->isCurlActivated()) {
            return false;
        }
        list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
        if (is_null($accountId) || (int)$accountId === 0 || !is_numeric($accountId)) {
            return false;
        }
        try {
            $this->init(['access_token' => $accessToken, 'secret' => $secretToken]);
            $this->connect(false, $logOutput);
        } catch (LengowException $e) {
            $message = $this->_dataHelper->decodeLogMessage($e->getMessage(), false);
            $error = $this->_dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
            $this->_dataHelper->log('Connector', $error, $logOutput);
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
    public function queryApi($type, $api, $args = [], $body = '', $logOutput = false)
    {
        if (!in_array($type, [self::GET, self::POST, self::PUT, self::PATCH])) {
            return false;
        }
        try {
            list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
            if ($accountId === null) {
                return false;
            }
            $this->init(['access_token' => $accessToken, 'secret' => $secretToken]);
            $type = strtolower($type);
            $results = $this->$type(
                $api,
                array_merge(['account_id' => $accountId], $args),
                'stream',
                $body,
                $logOutput
            );
        } catch (LengowException $e) {
            $message = $this->_dataHelper->decodeLogMessage($e->getMessage(), false);
            $error = $this->_dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
            $this->_dataHelper->log('Connector', $error, $logOutput);
            return false;
        }
        return json_decode($results);
    }

    /**
     * Connection to the API
     *
     * @param boolean $force Force cache Update
     * @param boolean $logOutput see log or not
     *
     * @throws LengowException
     */
    public function connect($force = false, $logOutput = false)
    {
        $token = $this->_configHelper->get('authorization_token');
        $updatedAt = $this->_configHelper->get('last_authorization_token_update');
        if (!$force
            && $token !== null
            && strlen($token) > 0
            && $updatedAt !== null
            && (time() - $updatedAt) < $this->_tokenLifetime
        ) {
            $authorizationToken = $token;
        } else {
            $authorizationToken = $this->_getAuthorizationToken($logOutput);
            $this->_configHelper->set('authorization_token', $authorizationToken);
            $this->_configHelper->set('last_authorization_token_update', time());
        }
        $this->_token = $authorizationToken;
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
     * @throws LengowException
     *
     * @return mixed
     */
    public function get($api, $args = [], $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::GET, $format, $body, $logOutput);
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
     * @throws LengowException
     *
     * @return mixed
     */
    public function post($api, $args = [], $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::POST, $format, $body, $logOutput);
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
     * @throws LengowException
     *
     * @return mixed
     */
    public function put($api, $args = [], $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::PUT, $format, $body, $logOutput);
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
     * @throws LengowException
     *
     * @return mixed
     */
    public function patch($api, $args = [], $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::PATCH, $format, $body, $logOutput);
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
     * @throws LengowException
     *
     * @return mixed
     */
    private function _call($api, $args, $type, $format, $body, $logOutput)
    {
        try {
            $this->connect(false, $logOutput);
            $data = $this->_callAction($api, $args, $type, $format, $body, $logOutput);
        } catch (LengowException $e) {
            if ($e->getCode() === self::CODE_403) {
                $this->_dataHelper->log(
                    'Connector',
                    $this->_dataHelper->setLogMessage(
                        'API call failed - authorization token expired - attempt to recover a new token'
                    ),
                    $logOutput
                );
                $this->connect(true, $logOutput);
                $data = $this->_callAction($api, $args, $type, $format, $body, $logOutput);
            } else {
                    throw new LengowException($e->getMessage(), $e->getCode());
                };
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
     * @throws LengowException
     *
     * @return mixed
     */
    private function _callAction($api, $args, $type, $format, $body, $logOutput)
    {
        $result = $this->_makeRequest($type, $api, $args, $this->_token, $body, $logOutput);
        return $this->_format($result, $format);
    }

    /**
     * Get authorization token from Middleware
     *
     * @param boolean $logOutput see log or not
     *
     * @throws LengowException
     *
     * @return string
     */
    private function _getAuthorizationToken($logOutput) {
        $data = $this->_callAction(
            self::API_ACCESS_TOKEN,
            [
                'access_token' => $this->_accessToken,
                'secret' => $this->_secret,
            ],
            self::POST,
            self::FORMAT_JSON,
            '',
            $logOutput
        );
        // return a specific error for get_token
        if (!isset($data['token'])) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('no authorization token returned'),
                self::CODE_500
            );
        } elseif (strlen($data['token']) === 0) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('the returned authorization token is empty'),
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
     * @param string $token temporary access token
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws LengowException get Curl error
     *
     * @return mixed
     */
    private function _makeRequest($type, $api, $args, $token, $body, $logOutput)
    {
        // Define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $ch = curl_init();
        // Default curl Options
        $opts = $this->_curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($api, $this->_lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->_lengowUrls[$api];
        }
        // get url for a specific environment
        $url = self::LENGOW_API_URL . $api;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        if (isset($url['port'])) {
            $opts[CURLOPT_PORT] = $url['port'];
        }
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = ['Authorization: ' . $token];
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        switch ($type) {
            case self::GET:
                $opts[CURLOPT_URL] = $url . (!empty($args) ? '?' . http_build_query($args) : '');
                break;
            case self::PUT:
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body),
                        ]
                    );
                }
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            case self::PATCH:
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        ['Content-Type: application/json']
                    );
                }
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = json_encode($args);
                break;
            default:
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
                break;
        }
        $this->_dataHelper->log(
            'Connector',
            $this->_dataHelper->setLogMessage('call %1 %2', [$type, $opts[CURLOPT_URL]]),
            $logOutput
        );
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrorNumber = curl_errno($ch);
        curl_close($ch);
        $this->_checkReturnRequest($result, $httpCode, $curlError, $curlErrorNumber);
        return $result;
    }

    /**
     * Check return request and generate exception if needed
     *
     * @param string $result Curl return call
     * @param integer $httpCode request http code
     * @param string $curlError Curl error
     * @param string $curlErrorNumber Curl error number
     *
     * @throws LengowException
     *
     */
    private function _checkReturnRequest($result, $httpCode, $curlError, $curlErrorNumber) {
        if ($result === false) {
            // recovery of Curl errors
            if (in_array($curlErrorNumber, [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED])) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage('API call blocked due to a timeout'),
                    self::CODE_504
                );
            } else {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage('Curl error %1 - %2', [$curlErrorNumber, $curlError]),
                    self::CODE_500
                );
            }
        } else {
            if ($httpCode !== self::CODE_200) {
                $result = $this->_format($result);
                // recovery of Lengow Api errors
                if (isset($result['error'])) {
                    throw new LengowException($result['error']['message'], $httpCode);
                } else {
                    throw new LengowException(
                        $this->_dataHelper->setLogMessage('Lengow APIs are not available'),
                        $httpCode
                    );
                }
            }
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
    private function _format($data, $format = self::FORMAT_JSON)
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
