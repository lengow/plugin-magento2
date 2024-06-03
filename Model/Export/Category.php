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

use Exception;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product\Interceptor as ProductInterceptor;
use Magento\Store\Model\Store\Interceptor as StoreInterceptor;

/**
 * Lengow export category
 */
class Category
{
    /**
     * @var CategoryRepository Magento category repository instance
     */
    private $categoryRepository;

    /**
     * @var ProductInterceptor Magento product instance
     */
    private $product;

    /**
     * @var StoreInterceptor Magento store instance
     */
    private $store;

    /**
     * @var array cache category names
     */
    private $cacheCategoryNames = [];

    /**
     * @var array cache category breadcrumb
     */
    private $cacheCategoryBreadcrumbs = [];

    /**
     * @var string category breadcrumb
     */
    private $categoryBreadcrumb;

    /**
     * Constructor
     *
     * @param CategoryRepository $categoryRepository Magento category repository instance
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Init a new category
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store Magento store instance
     */
    public function init(array $params): void
    {
        $this->store = $params['store'];
    }

    /**
     * Load a new category with a specific params
     *
     * @param array $params optional options for load a specific category
     * ProductInterceptor product Magento product instance
     *
     * @throws Exception
     */
    public function load(array $params): void
    {
        $this->product = $params['product'];
        $defaultCategory = $this->getDefaultCategory();
        $this->categoryBreadcrumb = $defaultCategory['id'] > 0
            ? $this->getBreadcrumb((int) $defaultCategory['id'], $defaultCategory['path'])
            : '';
    }

    /**
     * Get category breadcrumb for a product
     *
     * @return string
     */
    public function getCategoryBreadcrumb(): string
    {
        return $this->categoryBreadcrumb;
    }

    /**
     * Clean category for a next product
     */
    public function clean(): void
    {
        $this->product = null;
        $this->categoryBreadcrumb = null;
    }

    /**
     * Get default category id and path
     *
     * @throws Exception
     *
     * @return array
     */
    private function getDefaultCategory(): array
    {
        $currentLevel = 0;
        $defaultCategory = [];
        // get category collection for one product
        $categoryCollection = $this->product->getCategoryCollection()
            ->addPathsFilter('1/' . $this->store->getRootCategoryId() . '/')
            ->exportToArray();
        if (!empty($categoryCollection)) {
            // select category with max level by default
            foreach ($categoryCollection as $categoryArray) {
                if ($categoryArray['level'] > $currentLevel) {
                    $currentLevel = $categoryArray['level'];
                    $defaultCategory = $categoryArray;
                }
            }
        }
        return [
            'id' => isset($defaultCategory['entity_id']) ? (int) $defaultCategory['entity_id'] : 0,
            'path' => $defaultCategory['path'] ?? '',
        ];
    }

    /**
     * Get category breadcrumb
     *
     * @param integer $categoryId Magento category id
     * @param string $categoryPath Magento category path
     *
     * @return string
     *
     * @throws Exception
     */
    private function getBreadcrumb(int $categoryId, string $categoryPath): string
    {
        if ($categoryId === 0 || $categoryPath === '') {
            return '';
        }
        $categoryNames = [];
        if (isset($this->cacheCategoryBreadcrumbs[$categoryId])) {
            return $this->cacheCategoryBreadcrumbs[$categoryId];
        }
        // create breadcrumb with categories
        $categoryIds = explode('/', $categoryPath);
        foreach ($categoryIds as $id) {
            // no root category in breadcrumb
            if ((int) $id !== 1) {
                $categoryNames[] = $this->getName((int) $id);
            }
        }
        $categoryBreadcrumb = implode(' > ', $categoryNames);
        // set breadcrumb in category cache
        $this->cacheCategoryBreadcrumbs[$categoryId] = $categoryBreadcrumb;
        return $categoryBreadcrumb;
    }

    /**
     * Get category name
     *
     * @param integer $categoryId Magento category id
     *
     * @throws Exception
     *
     * @return string
     */
    protected function getName(int $categoryId): string
    {
        if ($categoryId === 0) {
            return '';
        }
        if (isset($this->cacheCategoryNames[$categoryId])) {
            $categoryName = $this->cacheCategoryNames[$categoryId];
        } else {
            $category = $this->categoryRepository->get($categoryId, $this->store->getId());
            $name = $category->getName();
            $categoryName = $name;
            $this->cacheCategoryNames[$categoryId] = $name;
        }
        return $categoryName;
    }
}
