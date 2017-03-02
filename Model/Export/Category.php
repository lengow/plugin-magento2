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

namespace Lengow\Connector\Model\Export;

use Magento\Catalog\Model\CategoryRepository;

/**
 * Lengow export category
 */
class Category
{
    /**
     * @var \Magento\Catalog\Model\CategoryRepository Magento category repository instance
     */
    protected $_categoryRepository;

    /**
     * @var \Magento\Catalog\Model\Product\Interceptor Magento product instance
     */
    protected $_product;

    /**
     * @var \Magento\Store\Model\Store\Interceptor Magento store instance
     */
    protected $_store;

    /**
     * @var array cache categories
     */
    protected $_cacheCategoryBreadcrumbs = [];

    /**
     * @var string category breadcrumb
     */
    protected $_categoryBreadcrumb;

    /**
     * Constructor
     *
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository Magento category repository instance
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->_categoryRepository = $categoryRepository;
    }

    /**
     * init a new category
     *
     * @param array $params optional options for load a specific product
     */
    public function init($params)
    {
        $this->_store = $params['store'];
    }

    /**
     * Load a new category with a specific params
     *
     * @param array $params optional options for load a specific category
     */
    public function load($params)
    {
        $this->_product = $params['product'];
        $this->_categoryBreadcrumb = $this->_getCategoryBreadcrumb();
    }

    /**
     * Get category breadcrumb for a product
     *
     * @return string
     */
    public function getCategoryBreadcrumb()
    {
        return $this->_categoryBreadcrumb;
    }

    /**
     * Clean category for a next product
     */
    public function clean()
    {
        $this->_product = null;
    }

    /**
     * Get category breadcrumb
     *
     * @return string
     */
    protected function _getCategoryBreadcrumb()
    {
        $defaultCategory = false;
        $categoryIds = [];
        $categoryNames = [];
        $currentLevel = 0;
        // Get category collection for one product
        $categoryCollection = $this->_product->getCategoryCollection()
            ->addPathsFilter('1/'.$this->_store->getRootCategoryId().'/')
            ->exportToArray();
        if (count($categoryCollection) == 0) {
            return '';
        }
        // Select category with max level by default
        foreach ($categoryCollection as $categoryArray) {
            if ($categoryArray['level'] > $currentLevel) {
                $currentLevel = $categoryArray['level'];
                $defaultCategory = $categoryArray;
            }
        }
        // Get category breadcrumb directly if exist
        $defaultCategoryId = (int)$defaultCategory['entity_id'];
        if (isset($this->_cacheCategoryBreadcrumbs[$defaultCategoryId])) {
            return $this->_cacheCategoryBreadcrumbs[$defaultCategoryId];
        }
        // Create breadcrumb with categories
        if (isset($defaultCategory['path']) && $defaultCategory['path'] != '') {
            $categoryIds = explode('/', $defaultCategory['path']);
        }
        foreach ($categoryIds as $categoryId) {
            // No root category in breadcrumb
            if ((int)$categoryId != 1) {
                $category = $this->_categoryRepository->get((int)$categoryId, $this->_store->getId());
                $categoryNames[] = $category->getName();
            }
        }
        $categoryBreadcrumb = implode(' > ', $categoryNames);
        // Set breadcrumb in category cache
        $this->_cacheCategoryBreadcrumbs[$defaultCategoryId] = $categoryBreadcrumb;
        return $categoryBreadcrumb;
    }
}
