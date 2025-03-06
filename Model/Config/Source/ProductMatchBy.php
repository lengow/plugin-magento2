<?php
/**
 * Copyright 2025 Lengow SAS
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
 * @copyright   2025 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ProductMatchBy implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Default --')],
            ['value' => 'id', 'label' => __('ID')],
            ['value' => 'sku', 'label' => __('SKU')]
        ];
    }
}
