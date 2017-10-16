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
    // const LENGOW_API_URL = 'http://api.lengow.io:80';
    // const LENGOW_API_URL = 'http://api.lengow.net:80';
    const LENGOW_API_URL = 'http://api.lengow.rec:80';
    // const LENGOW_API_URL = 'http://10.100.1.82:8081';

    /**
     * @var string url of the SANDBOX Lengow
     */
    const LENGOW_API_SANDBOX_URL = 'http://api.lengow.net:80';

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
     * @var integer ID account
     */
    protected $_accountId;

    /**
     * @var integer the user Id
     */
    protected $_userId;

    /**
     * @var array lengow url for curl timeout
     */
    protected $_lengowUrls = [
        '/v3.0/orders' => 15,
        '/v3.0/orders/moi/' => 5,
        '/v3.0/orders/actions/' => 10,
        '/v3.0/marketplaces' => 10,
        '/v3.0/plans' => 3,
        '/v3.0/stats' => 3,
        '/v3.1/cms' => 3,
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
     * Connection to the API
     *
     * @param string $userToken the user token if is connected
     *
     * @return array|false
     */
    public function connect($userToken = '')
    {
        $data = $this->callAction(
            '/access/get_token',
            [
                'access_token' => $this->_accessToken,
                'secret' => $this->_secret,
                'user_token' => $userToken
            ],
            'POST'
        );
        if (isset($data['token'])) {
            $this->_token = $data['token'];
            $this->_accountId = $data['account_id'];
            $this->_userId = $data['user_id'];
            return $data;
        } else {
            return false;
        }
    }

    /**
     * The API method
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function call($method, $array = [], $type = 'GET', $format = 'json', $body = '')
    {
        try {
            $this->connect();
            if (!array_key_exists('account_id', $array)) {
                $array['account_id'] = $this->_accountId;
            }
            $data = $this->callAction($method, $array, $type, $format, $body);
        } catch (LengowException $e) {
            return $e->getMessage();
        }
        return $data;
    }

    /**
     * Get API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function get($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'GET', $format, $body);
    }

    /**
     * Post API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function post($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'POST', $format, $body);
    }

    /**
     * Head API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function head($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'HEAD', $format, $body);
    }

    /**
     * Put API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function put($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PUT', $format, $body);
    }

    /**
     * Delete API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function delete($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'DELETE', $format, $body);
    }

    /**
     * Patch API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function patch($method, $array = [], $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PATCH', $format, $body);
    }

    /**
     * Call API action
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function callAction($api, $args, $type, $format = 'json', $body = '')
    {
        $result = $this->_makeRequest($type, $api, $args, $this->_token, $body);
        return $this->_format($result, $format);
    }

    /**
     * Get data in specific format
     *
     * @param mixed $data Curl response data
     * @param string $format return format of API
     *
     * @return array
     */
    private function _format($data, $format)
    {
        switch ($format) {
            case 'json':
                return json_decode($data, true);
            case 'csv':
                return $data;
            case 'xml':
                return simplexml_load_string($data);
            case 'stream':
                return $data;
            default:
                return $data;
        }
    }

    /**
     * Make Curl request
     *
     * @param string $type Lengow method API call
     * @param string $url Lengow API url
     * @param array $args Lengow method API parameters
     * @param string $token temporary access token
     * @param string $body body datas for request
     *
     * @throws LengowException get Curl error
     *
     * @return array
     */
    private function _makeRequest($type, $url, $args, $token, $body = '')
    {
        // Define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $ch = curl_init();
        // Default curl Options
        $opts = [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'lengow-php-sdk',
        ];
        // get special timeout for specific Lengow API
        if (array_key_exists($url, $this->_lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->_lengowUrls[$url];
        }
        // get url for a specific environment
        $url = self::LENGOW_API_URL . $url;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        $opts[CURLOPT_PORT] = $url['port'];
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = ['Authorization: ' . $token];
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        switch ($type) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $this->_dataHelper->log(
                    'Connector',
                    $this->_dataHelper->setLogMessage('call %1', [$opts[CURLOPT_URL]])
                );
                break;
            case 'PUT':
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body)
                        ]
                    );
                }
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            case 'PATCH':
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
        // Execute url request
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorText = curl_error($ch);
        if (in_array($errorNumber, [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED])) {
            $timeout = $this->_dataHelper->setLogMessage('API call blocked due to a timeout');
            $errorMessage = $this->_dataHelper->setLogMessage(
                'API call failed - %1',
                [$this->_dataHelper->decodeLogMessage($timeout, false)]
            );
            $this->_dataHelper->log('Connector', $errorMessage);
            throw new LengowException($timeout);
        }
        curl_close($ch);
        if ($result === false) {
            $errorCurl = $this->_dataHelper->setLogMessage('Curl error %1 - %2', [$errorNumber, $errorText]);
            $errorMessage = $this->_dataHelper->setLogMessage(
                'API call failed - %1',
                [$this->_dataHelper->decodeLogMessage($errorCurl, false)]
            );
            $this->_dataHelper->log('Connector', $errorMessage);
            throw new LengowException($errorCurl);
        }
        return $result;
    }

    /**
     * Get result for a query Api
     *
     * @param string $type request type (GET / POST / PUT / PATCH)
     * @param string $url request url
     * @param array $params request params
     * @param string $body body datas for request
     *
     * @return mixed
     */
    public function queryApi($type, $url, $params = [], $body = '')
    {
        if (!in_array($type, ['get', 'post', 'put', 'patch'])) {
            return false;
        }
        try {
            list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
            $this->init(['access_token' => $accessToken, 'secret' => $secretToken]);
            $results = $this->$type(
                $url,
                array_merge(['account_id' => $accountId], $params),
                'stream',
                $body
            );
        } catch (LengowException $e) {
            return false;
        }
        return json_decode($results);
    }

    /**
     * Check API Authentication
     *
     * @return boolean
     */
    public function isValidAuth()
    {
        if (!$this->isCurlActivated()) {
            return false;
        }
        list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
        if (is_null($accountId) || $accountId == 0 || !is_numeric($accountId)) {
            return false;
        }
        try {
            $this->init(['access_token' => $accessToken, 'secret' => $secretToken]);
            $result = $this->connect();
        } catch (LengowException $e) {
            return false;
        }
        if (isset($result['token'])) {
            return true;
        } else {
            return false;
        }
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
}
