<?php


namespace Lengow\Connector\Model;

use Lengow\Connector\Model\ResourceModel\Log as ResourceLog;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;

class Log extends AbstractModel
{
    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'message' => array('required' => true, 'updated' => false),
        'category' => array('required' => true, 'updated' => false),
    );

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    public function __construct(
        Context $context,
        Registry $registry
    ) {
        $this->_objectManager = ObjectManager::getInstance();
        parent::__construct($context, $registry);
    }

    /**
     * Initialize logs model
     **
     * @return void
     */
    protected function _construct() {
        $this->_init(ResourceLog::class);
    }

    /**
     * Create Lengow log
     *
     * @param array $params
     *
     * @return Log|bool
     */
    public function createLog($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $magentoDateObject = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $this->setData('date', $magentoDateObject->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }
}
