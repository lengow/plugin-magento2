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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Export as LengowExport;
use phpDocumentor\Reflection\Types\Boolean;

class Header extends Template
{
    /**
     * @var StoreInterface Magento store instance
     */
    private $store;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    private $export;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowExport $export Lengow export instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowExport $export,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->export = $export;
        $this->store = $this->dataHelper->getStore();
        parent::__construct($context, $data);
    }

    /**
     * Selection is enabled
     *
     * @return boolean
     */
    public function selectionIsEnabled(): bool
    {
        return (bool) $this->configHelper->get(ConfigHelper::SELECTION_ENABLED, (int) $this->store->getId());
    }

    /**
     * Get Magento store instance
     *
     * @return StoreInterface
     */
    public function getStore(): StoreInterface
    {
        return $this->store;
    }

    /**
     * Get Lengow export instance
     *
     * @return LengowExport
     */
    public function getExport(): LengowExport
    {
        $this->export->init([LengowExport::PARAM_STORE_ID => $this->store->getId()]);
        return $this->export;
    }

    /**
     * Get export url
     *
     * @return string
     */
    public function getExportUrl(): string
    {
        return $this->dataHelper->getExportUrl(
            $this->store->getId(),
            [
                LengowExport::PARAM_STREAM => 1,
                LengowExport::PARAM_UPDATE_EXPORT_DATE => 0,
            ]
        );
    }
}
