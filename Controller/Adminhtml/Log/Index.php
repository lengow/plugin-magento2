<?php

namespace Lengow\Connector\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Lengow\Connector\Helper\Data as HelperData;
use Magento\Backend\App\Action\Context;

class Index extends Action
{
    /**
     * Lengow HelperData
     *
     * @param HelperData $_dataHelper
     */
    protected $_dataHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param HelperData $dataHelper
     */
    public function __construct(Context $context, HelperData $dataHelper)
    {
        $this->_dataHelper = $dataHelper;
        parent::__construct($context);
    }

    public function execute()
    {

        $this->_dataHelper->log("macategory", "yes we can !");
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}