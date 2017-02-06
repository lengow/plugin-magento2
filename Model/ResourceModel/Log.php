<?php


namespace Lengow\Connector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    /**
     * Initialize orders resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('lengow_log', 'id');
    }
}
