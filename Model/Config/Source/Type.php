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

namespace Lengow\Connector\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Type implements ArrayInterface
{
    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'configurable', 'label' => __('Configurable')],
            ['value' => 'simple', 'label' => __('Simple')],
            ['value' => 'downloadable', 'label' => __('Downloadable')],
            ['value' => 'grouped', 'label' => __('Grouped')],
            ['value' => 'virtual', 'label' => __('Virtual')],
            ['value' => 'bundle', 'label' => __('Bundle')]
            
        ];
    }
}
