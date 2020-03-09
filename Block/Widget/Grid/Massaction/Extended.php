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

namespace Lengow\Connector\Block\Widget\Grid\Massaction;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Massaction\Extended as MagentoMassactionExtended;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\Data\Collection;
use Magento\Framework\Json\EncoderInterface;

/**
 * Class Extended
 * @package Lengow\Connector\Block\Widget\Grid\Massaction
 */
class Extended extends MagentoMassactionExtended
{
    /**
     * Backend data
     *
     * @var BackendHelper Magento backend helper instance
     */
    protected $_backendData = null;

    /**
     * @var EncoderInterface Magento json encoder interface instance
     */
    protected $_jsonEncoder;

    /**
     * @param Context $context Magento context instance
     * @param EncoderInterface $jsonEncoder Magento json encoder interface instance
     * @param BackendHelper $backendData Magento backend helper instance
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        BackendHelper $backendData,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_backendData = $backendData;
        parent::__construct($context, $jsonEncoder, $backendData, $data);
    }

    /**
     * @return string
     */
    public function getGridIdsJson()
    {
        if (!$this->getUseSelectAll()) {
            return '';
        }

        /** @var Collection $allIdsCollection */
        $allIdsCollection = clone $this->getParentBlock()->getCollection();
        $gridIds = $allIdsCollection->clear()->setPageSize(0)->getAllIds();

        if (!empty($gridIds)) {
            return join(',', $gridIds);
        }
        return '';
    }
}
