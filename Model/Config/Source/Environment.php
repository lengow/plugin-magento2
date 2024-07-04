<?php
/**
 * Copyright 2022 Lengow SAS
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
 * @copyright   2022 Lengow SAS
 *
 */
namespace Lengow\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    /**
     * @const PRE_PROD_ENVIRONMENT
     */
    public const PRE_PROD_ENVIRONMENT = 'pre-prod';

    /**
     * @const PROD_ENVIRONMENT
     *
     */
    public const PROD_ENVIRONMENT = 'prod';

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => static::PRE_PROD_ENVIRONMENT, 'label' => __('Pre-Prod')],
            ['value' => static::PROD_ENVIRONMENT, 'label' => __('Production')]
        ];
    }
}
