<?php

namespace Lengow\Connector\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Lengow\Connector\Model\Log', 'Lengow\Connector\Model\ResourceModel\Log');
    }
}