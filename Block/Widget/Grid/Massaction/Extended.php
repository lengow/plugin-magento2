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

use Magento\Backend\Block\Widget\Grid\Massaction\Extended as MagentoMassactionExtended;
use Magento\Framework\Data\Collection;

class Extended extends MagentoMassactionExtended
{
    /**
     * Override MagentoMassactionExtended::getGridIdsJson
     *
     * @return string
     */
    public function getGridIdsJson(): string
    {
        if (!$this->getUseSelectAll()) {
            return '';
        }

        /** @var Collection $allIdsCollection */
        $allIdsCollection = clone $this->getParentBlock()->getCollection();
        $gridIds = $allIdsCollection->clear()->setPageSize(0)->getAllIds();

        if (!empty($gridIds)) {
            return implode(',', $gridIds);
        }
        return '';
    }
}
