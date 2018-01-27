<?php
/**
 * Venustheme
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Venustheme.com license that is
 * available through the world-wide-web at this URL:
 * http://www.venustheme.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Venustheme
 * @package    Ves_Brand
 * @copyright  Copyright (c) 2014 Venustheme (http://www.venustheme.com/)
 * @license    http://www.venustheme.com/LICENSE-1.0.html
 */
namespace Ves\PageBuilder\Controller\Adminhtml\Pagebuilder;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
/**
 * Class MassDisable
 */
class ConvertTemplate extends \Magento\Backend\App\Action
{
    /**
     * Entity type code
     */
    const ENTITY_TYPE = 'cms-page';
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $dataProcessor;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        PostDataProcessor $dataProcessor,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->dataProcessor = $dataProcessor;
        $this->resultLayoutFactory = $resultLayoutFactory;
        parent::__construct($context);
    }
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('block_id');
        if($id) {
            try {
                $profile = $this->_objectManager->create('Ves\PageBuilder\Model\Block');
                $profile->load($id);
                $collection = [];
                $collection[] = $profile;
                $this->convertProfiles($collection);
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */    
                $this->messageManager->addSuccess(__('The page builder profile <strong>%1</strong> have been convert to html content of CMS Page: Ves_Template_%2', $profile->getTitle(), $profile->getTitle()));

            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
            }
        }
        
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    public function convertProfiles($collection) {
        foreach ($collection as $item) {
            //$profile = $this->_objectManager->create('Ves\PageBuilder\Model\Block');
            //$profile->load($item['block_id']);

            $resultLayout = $this->resultLayoutFactory->create();
            $builder_content_block = $resultLayout->getLayout()->createBlock(
                    'Ves\PageBuilder\Block\Builder\Template',
                    'builder_page_template'
                );
            $builder_content_block->setPageProfile($item);
            $builder_content_block->setTemplate("builder/page.phtml");
            $template_content = $builder_content_block->toHtml();
            $this->createCMSPage($item, $template_content);
        }
    }
    public function createCMSPage($profile, $html_content = '') {
        // Start Create Or Updated CMS Page
        $data = array();
        $prefix_title = "Ves_Template_";
        $prefix_alias = "ves_template_";
        $alias = $prefix_alias;
        $alias .= $profile->getAlias();

        $model = $this->_objectManager->create('Ves\PageBuilder\Model\Block');
        $page = $model->loadCMSPage($alias, "identifier", $profile->getStoreId());
        $first_store_id = $profile->getData("_first_store_id");
        if(!$first_store_id) {
            $first_store_id = isset($page_stores[0])?$page_stores[0]:0;
        }
        $page_stores = $profile->getStoreId();
        $data['page_id'] = $page->getPageId();
        $data['title'] = $prefix_title."".$profile->getTitle();
        $data['identifier'] = $alias;
        $data['is_active'] = $profile->getStatus();
        $data['stores'] = $profile->getStoreId();
        $data['content_heading'] = "";
        $data['store_id'] = $profile->getStoreId();
        $data['_first_store_id'] = $first_store_id;

        $data['content'] = $html_content;
        $data['page_layout'] = $profile->getPageLayout();
        $data['layout_update_xml'] = $profile->getLayoutUpdateXml();
        $data['meta_keywords'] = $profile->getMetaKeywords();
        $data['meta_description'] = $profile->getMetaDescription();
        $data['custom_theme_from'] = $profile->getCustomThemeFrom();
        $data['custom_theme_to'] = $profile->getCustomThemeTo();
        $data['custom_theme'] = $profile->getCustomTheme();
        $data['custom_root_template'] = $profile->getCustomRootTemplate();
        $data['custom_layout_update_xml'] = $profile->getCustomLayoutUpdateXml();

        $data = $this->dataProcessor->filter($data);
        //init model and set data
        $page_model = $this->_objectManager->create('Magento\Cms\Model\Page');
        if ($id = $data['page_id']) {
            $page_model->load($id);
        }
        $page_model->setData($data);

        $this->_eventManager->dispatch(
            'cms_page_prepare_save',
            ['page' => $page_model, 'request' => $this->getRequest()]
        );

        //Delete old rewrite url
        if(!$page_model->getId()) {
            $this->removeCMSUrlRewrite($data['stores'], $data['identifier']);
        }
        
        if (!$this->dataProcessor->validate($data)) {
            return false;
        }

        try {
            $page_model->save();
            $this->messageManager->addSuccess(__('The Page Builde Profile Was Converted To CMS Html Content.'));
            $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\RuntimeException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while convert the page profile.'));
        }
        return true;
    }
    /**
     * Create url rewrite object
     *
     * @param int $storeId
     * @param int $redirectType
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite
     */
    protected function removeCMSUrlRewrite($storeId, $identifier)
    {
        $urlRewrite = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite');
        $collection = $urlRewrite->getCollection();
        $collection->addFieldToFilter("entity_type", self::ENTITY_TYPE)
                   ->addFieldToFilter("request_path", $identifier);
        if($storeId && (count($storeId) > 1 || (count($storeId) == 1 && $storeId[0] != 0))){
           $collection->addFieldToFilter("store_id", array("in"=> $storeId)); 
        }
    
        if(0 < $collection->getSize()) {
            foreach($collection->getItems() as $item) {
                $urlRewriteItem = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite');
                $urlRewriteItem->load($item->getId());
                $urlRewriteItem->delete();
            }
        }
    }
    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ves_PageBuilder::page_convert_template');
    }
}
