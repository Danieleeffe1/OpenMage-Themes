<?php
/**
* aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Easycategories
 * @version    1.1
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE-COMMUNITY.txt
 */

class AW_Easycategories_Block_View extends Mage_Core_Block_Template
{
    const COLUMN_VIEW_TEMPLATE = 'easycategories/column_view.phtml';
    const LINE_VIEW_TEMPLATE = 'easycategories/line_view.phtml';

    const HOMEPAGE_DISPLAY = 'easycategories/homepagesettings/display';
    const HOMEPAGE_TEMPLATE = 'easycategories/homepagesettings/template';
    const HOMEPAGE_THUMBNAIL_DISPLAY = 'easycategories/homepagesettings/thumbnail';
    const HOMEPAGE_THUMBNAIL_INSTEAD_TEXT = 'easycategories/homepagesettings/thumbnail_instead_text';
    const HOMEPAGE_THUMBNAIL_WIDTH = 'easycategories/homepagesettings/thumbnail_width';

    const CATEGORY_DISPLAY = 'easycategories/categorysettings/display';
    const CATEGORY_TEMPLATE = 'easycategories/categorysettings/template';
    const CATEGORY_THUMBNAIL_DISPLAY = 'easycategories/categorysettings/thumbnail';
    const CATEGORY_THUMBNAIL_INSTEAD_TEXT = 'easycategories/categorysettings/thumbnail_instead_text';
    const CATEGORY_THUMBNAIL_WIDTH = 'easycategories/categorysettings/thumbnail_width';

    const DEFAULT_THUMBNAIL_WIDTH_BLOCK = '100';

    var $block_type; /* 1. CMS or Static Block; 2. Home page; 3. Category page */

    protected function _toHtml()
    {
        if ($this->getTemplate()) $this->block_type=1;
        else if (Mage::getStoreConfig(self::HOMEPAGE_DISPLAY)&&$this->isInRootCategory()) $this->block_type=2;
        else if (Mage::getStoreConfig(self::CATEGORY_DISPLAY)&&!$this->isInRootCategory()) $this->block_type=3;
        else return parent::_toHtml();

        if ($this->block_type==1)
        {
            if ($this->getTemplate()=='columns')
                $this->setTemplate(self::COLUMN_VIEW_TEMPLATE);
            if ($this->getTemplate()=='lines')
                $this->setTemplate(self::LINE_VIEW_TEMPLATE);
        }

        if ($this->block_type==2)
        {
           if (Mage::getStoreConfig(self::HOMEPAGE_TEMPLATE)=='columns')
               $this->setTemplate(self::COLUMN_VIEW_TEMPLATE);
           if (Mage::getStoreConfig(self::HOMEPAGE_TEMPLATE)=='lines')
               $this->setTemplate(self::LINE_VIEW_TEMPLATE);
        }

        if ($this->block_type==3)
        {
            if (Mage::getStoreConfig(self::CATEGORY_TEMPLATE)=='columns')
                $this->setTemplate(self::COLUMN_VIEW_TEMPLATE);
            if (Mage::getStoreConfig(self::CATEGORY_TEMPLATE)=='lines')
                $this->setTemplate(self::LINE_VIEW_TEMPLATE);
        }
        
        return parent::_toHtml();
    }

    public function isInRootCategory()
    {
        if (!Mage::registry('current_category')) return true;
        return false;
    }

    public function getSubcategoriesForCategory()
    {
        $categoryId = $this->getRootCategory();
        
        if (!$categoryId)
        {
            if (!$this->isInRootCategory())
                $catId = Mage::registry('current_category')->getId();
            else
                $catId = Mage::app()->getStore()->getRootCategoryId();
        }
        else $catId = $categoryId;

        $category = Mage::getModel('catalog/category');

        $tree = $category->getTreeModel();

        $nodes = $tree->loadNode($catId)
                ->loadChildren(2)
                ->getChildren();
        
        $collection = $category->getCollection()
                ->setStoreId(Mage::app()->getStore()->getId())
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('is_active');

        $tree->addCollectionData($collection, false, $catId, true, false);

        foreach ($nodes as $key => $node)
            if (!$node->getIsActive()) $nodes->delete($node);

        return $nodes;
    }

    public function getActiveSubcategories($category)
    {
        $subCategories = clone $category->getChildren();
        foreach ($subCategories as $key => $subCategory)
            if (!$subCategory->getIsActive()) $subCategories->delete($subCategory);
        return $subCategories;
    }

    public function getThumbnailImg($category)
    {
        if ($this->block_type==1)
        {
           if ($this->getDisplayThumbnail()=='yes')
               return Mage::getModel('catalog/category')->load($category->getId())->getThumbnail();
        }

        if ($this->block_type==2)
        {
           if (Mage::getStoreConfig(self::HOMEPAGE_THUMBNAIL_DISPLAY))
                return Mage::getModel('catalog/category')->load($category->getId())->getThumbnail();
        }

        if ($this->block_type==3)
        {
           if (Mage::getStoreConfig(self::CATEGORY_THUMBNAIL_DISPLAY))
                return Mage::getModel('catalog/category')->load($category->getId())->getThumbnail();
        }

        return false;
    }

    public function getThumbnailSize()
    {
        $repl = array("px", "PX", "Px", "pX");

        if ($this->block_type==1)
        {
           if ($this->getThumbnailWidth())
                return str_replace($repl, '', $this->getThumbnailWidth());
            else
                return self::DEFAULT_THUMBNAIL_WIDTH_BLOCK;
        }

        if ($this->block_type==2)
        {
            if ($width = Mage::getStoreConfig(self::HOMEPAGE_THUMBNAIL_WIDTH))
                return str_replace($repl, '', $width);
        }
        
        if ($this->block_type==3)
        {
            if ($width = Mage::getStoreConfig(self::CATEGORY_THUMBNAIL_WIDTH))
            return str_replace($repl, '',$width);
        }
        
        return false;
    }

    public function getThumbnailInsteadofText($category)
    {
        if ($this->block_type==1)
        {
           if ($this->getDisplayThumbnail()=='yes'&&$this->getThumbnailInsteadText()=='yes'&&$this->getThumbnailImg($category))
               return false;
        }

        if ($this->block_type==2)
        {
           if (Mage::getStoreConfig(self::HOMEPAGE_THUMBNAIL_DISPLAY)&&Mage::getStoreConfig(self::HOMEPAGE_THUMBNAIL_INSTEAD_TEXT)&&$this->getThumbnailImg($category))
                return false;
        }

        if ($this->block_type==3)
        {
           if (Mage::getStoreConfig(self::CATEGORY_THUMBNAIL_DISPLAY)&&Mage::getStoreConfig(self::CATEGORY_THUMBNAIL_INSTEAD_TEXT)&&$this->getThumbnailImg($category))
                return false;
        }

        return true;
    }

    public function scanSubSubCategories($subCategories)
    {
        foreach ($subCategories as $category)
        {
            if ($this->getActiveSubcategories($category)->getNodes()) return true;
        }
        return false;
    }
}
