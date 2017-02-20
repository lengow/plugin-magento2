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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Lengow\Connector\Model\LogFactory as LogFactory;

class Data extends AbstractHelper
{
    /**
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList Magento directory list instance
     */
    protected $_directoryList;

    /**
     * @var \Magento\Framework\App\ResourceConnection Magento resource connection instance
     */
    protected $_resource;

    /**
     * @var \Lengow\Connector\Model\LogFactory Lengow log factory instance
     */
    protected $_logFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList Magento directory list instance
     * @param \Magento\Framework\App\ResourceConnection $resource Magento resource connection instance
     * @param \Lengow\Connector\Model\LogFactory $logFactory Lengow log factory instance
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        ResourceConnection $resource,
        LogFactory $logFactory
    ){
        $this->_storeManager = $storeManager;
        $this->_directoryList = $directoryList;
        $this->_resource = $resource;
        $this->_logFactory = $logFactory;
        parent::__construct($context);
    }

    /**
     * Write log
     *
     * @param string  $category       Category
     * @param string  $message        log message
     * @param boolean $display        display on screen
     * @param string  $marketplaceSku lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = "", $display = false, $marketplaceSku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decodedMessage = $this->decodeLogMessage($message, false);
        $finalMessage = ''.(empty($marketplaceSku) ? '' : 'order '.$marketplaceSku.' : ');
        $finalMessage.= $decodedMessage;
        if ($display) {
            echo '['.$category.'] '.$finalMessage.'<br />';
            flush();
        }
        $log = $this->_logFactory->create();
        return $log->createLog(['message' => $finalMessage, 'category' => $category]);
    }

    /**
     * Set message with parameters for translation
     *
     * @param string $key    log key
     * @param array  $params log parameters
     *
     * @return string
     */
    public function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $allParams = [];
        foreach ($params as $value) {
            $value = str_replace('|', '', $value);
            $allParams[] = $value;
        }
        $message = $key.'['.join('|', $allParams).']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string  $message        log message
     * @param boolean $useTranslation use Magento translation
     * @param array   $params         log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $useTranslation = true, $params = null)
    {
        if (preg_match('/^([^\[\]]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[3]) && is_null($params)) {
                    $strParam = $result[3];
                    $params = explode('|', $strParam);
                }
                if ($useTranslation) {
                    $phrase = __($key, $params);
                    $message = $phrase->__toString();
                } else {
                    if (count($params) > 0) {
                        $ii = 1;
                        foreach ($params as $param) {
                            $key = str_replace('%'.$ii, $param, $key);
                            $ii++;
                        }
                    }
                    $message = $key;
                }
            }
        }
        return $message;
    }

    /**
     * Delete log files when too old
     *
     * @param integer $nbDays
     */
    public function cleanLog($nbDays = 20)
    {
        if ($nbDays <= 0) {
            $nbDays = self::LOG_LIFE;
        }
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $table = $connection->getTableName('lengow_log');
        $query = 'DELETE FROM '.$table.' WHERE `date` < DATE_SUB(NOW(),INTERVAL '.$nbDays.' DAY)';
        $connection->query($query);
    }

    /**
     * Get media url for export file
     *
     *  @return string
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get media path for export file
     *
     *  @return string
     */
    public function getMediaPath()
    {
        return $this->_directoryList->getPath('media');
    }

    /**
     * Get host for generated email
     *
     * @param integer $storeId Magento store id
     *
     * @return string
     */
    public function getHost($storeId)
    {
        $domain = $this->_storeManager->getStore($storeId)->getBaseUrl();
        preg_match('`([a-zàâäéèêëôöùûüîïç0-9-]+\.[a-z]+)`', $domain, $out);
        if ($out[1]) {
            return $out[1];
        }
        return $domain;
    }

    /**
     * Clean data
     *
     * @param string $str the content
     *
     * @return string
     */
    public function cleanData($str)
    {
        $str = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]
			|[\x00-\x7F][\x80-\xBF]+
			|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*
			|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})
			|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $str
        );
        $str = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S',
            '',
            $str
        );
        $str = preg_replace('/[\s]+/', ' ', $str);
        $str = trim($str);
        $str = str_replace(
            [
                '&nbsp;',
                '|',
                '"',
                '’',
                '&#39;',
                '&#150;',
                chr(9),
                chr(10),
                chr(13),
                chr(31),
                chr(30),
                chr(29),
                chr(28),
                "\n",
                "\r"
            ],
            [
                ' ',
                ' ',
                '\'',
                '\'',
                ' ',
                '-',
                ' ',
                ' ',
                ' ',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            $str
        );
        return $str;
    }

    /**
     * Clean html content
     *
     * @param string $str the html content
     *
     * @return string
     */
    public function cleanHtml($str)
    {
        $str = str_replace('<br />', ' ', nl2br($str));
        $str = trim(strip_tags(htmlspecialchars_decode($str)));
        $str = preg_replace('`[\s]+`sim', ' ', $str);
        $str = preg_replace('`"`sim', '', $str);
        $str = nl2br($str);
        $pattern = '@<[\/\!]*?[^<>]*?>@si';
        $str = preg_replace($pattern, ' ', $str);
        $str = preg_replace('/[\s]+/', ' ', $str);
        $str = trim($str);
        $str = str_replace('&nbsp;', ' ', $str);
        $str = str_replace('|', ' ', $str);
        $str = str_replace('"', '\'', $str);
        $str = str_replace('’', '\'', $str);
        $str = str_replace('&#39;', '\' ', $str);
        $str = str_replace('&#150;', '-', $str);
        $str = str_replace(chr(9), ' ', $str);
        $str = str_replace(chr(10), ' ', $str);
        $str = str_replace(chr(13), ' ', $str);
        return $str;
    }

    /**
     * Replace all accented chars by their equivalent non accented chars
     *
     * @param string $str the content
     *
     * @return string
     */
    public function replaceAccentedChars($str)
    {
        /* One source among others:
            http://www.tachyonsoft.com/uc0000.htm
            http://www.tachyonsoft.com/uc0001.htm
            http://www.tachyonsoft.com/uc0004.htm
        */
        $patterns = [
            /* Lowercase */
            /* a  */
            '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}\x{0430}\x{00C0}-\x{00C3}\x{1EA0}-\x{1EB7}]/u',
            /* b  */
            '/[\x{0431}]/u',
            /* c  */
            '/[\x{00E7}\x{0107}\x{0109}\x{010D}\x{0446}]/u',
            /* d  */
            '/[\x{010F}\x{0111}\x{0434}\x{0110}]/u',
            /* e  */
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}\x{0435}\x{044D}\x{00C8}-\x{00CA}\x{1EB8}-\x{1EC7}]/u',
            /* f  */
            '/[\x{0444}]/u',
            /* g  */
            '/[\x{011F}\x{0121}\x{0123}\x{0433}\x{0491}]/u',
            /* h  */
            '/[\x{0125}\x{0127}]/u',
            /* i  */
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}\x{0438}\x{0456}\x{00CC}\x{00CD}\x{1EC8}-\x{1ECB}\x{0128}]/u',
            /* j  */
            '/[\x{0135}\x{0439}]/u',
            /* k  */
            '/[\x{0137}\x{0138}\x{043A}]/u',
            /* l  */
            '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}\x{043B}]/u',
            /* m  */
            '/[\x{043C}]/u',
            /* n  */
            '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}\x{043D}]/u',
            /* o  */
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}\x{043E}\x{00D2}-\x{00D5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u',
            /* p  */
            '/[\x{043F}]/u',
            /* r  */
            '/[\x{0155}\x{0157}\x{0159}\x{0440}]/u',
            /* s  */
            '/[\x{015B}\x{015D}\x{015F}\x{0161}\x{0441}]/u',
            /* ss */
            '/[\x{00DF}]/u',
            /* t  */
            '/[\x{0163}\x{0165}\x{0167}\x{0442}]/u',
            /* u  */
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}\x{0443}\x{00D9}-\x{00DA}\x{0168}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u',
            /* v  */
            '/[\x{0432}]/u',
            /* w  */
            '/[\x{0175}]/u',
            /* y  */
            '/[\x{00FF}\x{0177}\x{00FD}\x{044B}\x{1EF2}-\x{1EF9}\x{00DD}]/u',
            /* z  */
            '/[\x{017A}\x{017C}\x{017E}\x{0437}]/u',
            /* ae */
            '/[\x{00E6}]/u',
            /* ch */
            '/[\x{0447}]/u',
            /* kh */
            '/[\x{0445}]/u',
            /* oe */
            '/[\x{0153}]/u',
            /* sh */
            '/[\x{0448}]/u',
            /* shh*/
            '/[\x{0449}]/u',
            /* ya */
            '/[\x{044F}]/u',
            /* ye */
            '/[\x{0454}]/u',
            /* yi */
            '/[\x{0457}]/u',
            /* yo */
            '/[\x{0451}]/u',
            /* yu */
            '/[\x{044E}]/u',
            /* zh */
            '/[\x{0436}]/u',

            /* Uppercase */
            /* A  */
            '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}\x{0410}]/u',
            /* B  */
            '/[\x{0411}]/u',
            /* C  */
            '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}\x{0426}]/u',
            /* D  */
            '/[\x{010E}\x{0110}\x{0414}]/u',
            /* E  */
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}\x{0415}\x{042D}]/u',
            /* F  */
            '/[\x{0424}]/u',
            /* G  */
            '/[\x{011C}\x{011E}\x{0120}\x{0122}\x{0413}\x{0490}]/u',
            /* H  */
            '/[\x{0124}\x{0126}]/u',
            /* I  */
            '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}\x{0418}\x{0406}]/u',
            /* J  */
            '/[\x{0134}\x{0419}]/u',
            /* K  */
            '/[\x{0136}\x{041A}]/u',
            /* L  */
            '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}\x{041B}]/u',
            /* M  */
            '/[\x{041C}]/u',
            /* N  */
            '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}\x{041D}]/u',
            /* O  */
            '/[\x{00D3}\x{014C}\x{014E}\x{0150}\x{041E}]/u',
            /* P  */
            '/[\x{041F}]/u',
            /* R  */
            '/[\x{0154}\x{0156}\x{0158}\x{0420}]/u',
            /* S  */
            '/[\x{015A}\x{015C}\x{015E}\x{0160}\x{0421}]/u',
            /* T  */
            '/[\x{0162}\x{0164}\x{0166}\x{0422}]/u',
            /* U  */
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}\x{0423}]/u',
            /* V  */
            '/[\x{0412}]/u',
            /* W  */
            '/[\x{0174}]/u',
            /* Y  */
            '/[\x{0176}\x{042B}]/u',
            /* Z  */
            '/[\x{0179}\x{017B}\x{017D}\x{0417}]/u',
            /* AE */
            '/[\x{00C6}]/u',
            /* CH */
            '/[\x{0427}]/u',
            /* KH */
            '/[\x{0425}]/u',
            /* OE */
            '/[\x{0152}]/u',
            /* SH */
            '/[\x{0428}]/u',
            /* SHH*/
            '/[\x{0429}]/u',
            /* YA */
            '/[\x{042F}]/u',
            /* YE */
            '/[\x{0404}]/u',
            /* YI */
            '/[\x{0407}]/u',
            /* YO */
            '/[\x{0401}]/u',
            /* YU */
            '/[\x{042E}]/u',
            /* ZH */
            '/[\x{0416}]/u'
        ];

        // ö to oe
        // å to aa
        // ä to ae
        $replacements = [
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            'ss',
            't',
            'u',
            'v',
            'w',
            'y',
            'z',
            'ae',
            'ch',
            'kh',
            'oe',
            'sh',
            'shh',
            'ya',
            'ye',
            'yi',
            'yo',
            'yu',
            'zh',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'Y',
            'Z',
            'AE',
            'CH',
            'KH',
            'OE',
            'SH',
            'SHH',
            'YA',
            'YE',
            'YI',
            'YO',
            'YU',
            'ZH'
        ];
        return preg_replace($patterns, $replacements, $str);
    }
}
