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
namespace Ves\PageBuilder\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Customer\Model\Session as CustomerSession;
/**
 * PageBuilder Model
 */
class Block extends \Magento\Framework\Model\AbstractModel
{   
    /**
     * Block's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**
     * Block cache tag
     */
    const CACHE_BLOCK_TAG = 'ves_pagebuilder_block';

    /**
     * Page cache tag
     */
    const CACHE_TAG = 'ves_pagebuilder_page';

     /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * URL Model instance
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_blockHelper;

    protected $_localeDate;

    protected $_core_write_connection;
    protected $_core_resource;
    protected $_objectManager;

    /**
     * @param \Magento\Framework\Model\Context                          $context                  
     * @param \Magento\Framework\Registry                               $registry                 
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager             
     * @param \Ves\PageBuilder\Model\ResourceModel\Block|null                      $resource                 
     * @param \Ves\PageBuilder\Model\ResourceModel\Block\Collection|null           $resourceCollection       
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory 
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager    
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface      $localeDate             
     * @param \Magento\Framework\UrlInterface                           $url                      
     * @param \Ves\PageBuilder\Helper\Data                             $_blockHelper              
     * @param array                                                     $data                     
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CustomerSession $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ves\PageBuilder\Model\ResourceModel\Block $resource = null,
        \Ves\PageBuilder\Model\ResourceModel\Block\Collection $resourceCollection = null,
        \Magento\Framework\App\ResourceConnection $core_resource,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ves\PageBuilder\Helper\Data $blockHelper,
        array $data = []
        ) {
        $this->customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_url = $url;
        $this->_blockHelper = $blockHelper;
        $this->_localeDate = $localeDate;
        $this->_core_resource = $core_resource;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Ves\PageBuilder\Model\ResourceModel\Block');
    }

    protected function getConnection()
    {
        if (!$this->_core_write_connection) {
            $this->_core_write_connection = $this->_core_resource->getConnection('core_write');
        }
        return $this->_core_write_connection;
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Check if page identifier exist for specific store
     * return page id if page exists
     *
     * @param string $identifier
     * @param int $storeId
     * @return int
     */
    public function checkIdentifier($identifier, $storeId)
    {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }


    public function getUrl()
    {
        $url = $this->_storeManager->getStore()->getBaseUrl();
        $route = $this->_blockHelper->getConfig('general_settings/route');
        $url_prefix = $this->_blockHelper->getConfig('general_settings/url_prefix');
        $urlPrefix = '';
        if($url_prefix){
            $urlPrefix = $url_prefix.'/';
        }
        $url_suffix = $this->_blockHelper->getConfig('general_settings/url_suffix');
        return $url.$urlPrefix.$this->getUrlKey().$url_suffix;
    }

    /**
     * Retrive image URL
     *
     * @return string
     */
    public function getImageUrl()
    {
        $url = false;
        $image = $this->getImage();
        if ($image) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $image;
        };
        return $url;
    }

    /**
     * Retrive thumbnail URL
     *
     * @return string
     */
    public function getThumbnailUrl()
    {
        $url = false;
        $thumbnail = $this->getThumbnail();
        if ($thumbnail) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $thumbnail;
        };
        return $url;
    }

    public function getBlockByAlias($alias = "", $is_page = false) {
        $customer_group_id = (int)$this->customerSession->getCustomerGroupId();
        if($alias) {
            $today_date_time  = new \DateTime('today');
            $today_date = $this->_localeDate->formatDateTime(
                                $today_date_time,
                                \IntlDateFormatter::MEDIUM,
                                \IntlDateFormatter::MEDIUM
                            );
            $todayDateTime = strtotime($today_date);
            $todayDate = date("Y-m-d", $todayDateTime);


            $collection = $this->getCollection()
                                ->addFieldToFilter('alias', $alias)
                                ->addFieldToFilter('status', 1)
                                ->addFieldToFilter('show_from', array('or'=> array(
                                    0 => array('date' => true, 'lt' => $todayDate),
                                    1 => array('is' => new \Zend_Db_Expr('null')))
                                ), 'left')
                                ->addFieldToFilter('show_to', array('or'=> array(
                                    0 => array('date' => true, 'gteq' => $todayDate),
                                    1 => array('is' => new \Zend_Db_Expr('null')))
                                ), 'left');

            if($is_page) {
                $collection->addFieldToFilter('block_type', "page");
            }
            $block_entity = $collection->getFirstItem();
                    
            if($block_entity) {
                $customer_group = $block_entity->getCustomerGroup();
                $array_groups = explode(",",$customer_group);
                if($array_groups && in_array(0, $array_groups)){
                    return $block_entity;
                } elseif( $array_groups && in_array($customer_group_id, $array_groups)) {
                    return $block_entity;
                }
            }
            
        }
        return null;
    }

    public function checkBlockProfileAvailable( $block_profile = null ){
        $checked = true;
        if($block_profile) {
            if($block_profile->getStatus() != "1") {
                $checked = false;   
            } else {
                $customer_group_id = (int)$this->customerSession->getCustomerGroupId();
                $customer_group =  $block_profile->getCustomerGroup();
                $array_groups = explode(",",$customer_group);
                if($array_groups && !in_array(0, $array_groups) && !in_array($customer_group_id, $array_groups)){
                    $checked = false;
                } else {
                    $today_date_time  = new \DateTime('today');

                    $todayDate = $this->_localeDate->formatDateTime(
                                $today_date_time,
                                \IntlDateFormatter::MEDIUM,
                                \IntlDateFormatter::MEDIUM
                            );

                    $todayDateTime = strtotime($todayDate);

                    $date_from = $block_profile->getShowFrom();

                    if($date_from) {
                        $date_from = strtotime($date_from);
                    } else {
                        $date_from = 0;
                    }
                    
                    $date_to = $block_profile->getShowTo();
                    if($date_to) {
                        $date_to = strtotime($date_to);
                    } else {
                        $date_to = 0;
                    }

                    if($date_from > $todayDateTime || ($date_to > 0 && $date_to < $todayDateTime)) {
                        $checked = false;
                    }
                }
                
            }
        }
        return $checked;
    }

    public function loadCMSPage($field_value, $field_name = "identifier", $stores = array(), $withAdmin = true) {
        $is_single_store = false;
        $tmp_stores = $stores;
        $isSingleStoreMode = $this->_storeManager->isSingleStoreMode();
        if($isSingleStoreMode || !$stores) {
            $is_single_store = true;
            if ($stores instanceof \Magento\Store\Model\Store) {
                $stores = [$stores->getId()];
            }

            if (!is_array($stores)) {
                $stores = [$stores];
            }

            if ($withAdmin) {
                $stores[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }
        } else {
            if (!is_array($stores)) {
                $stores = [$stores];
            }
        }

        $readConnection = $this->getConnection();
        $cms_page_table = $this->_core_resource->getTableName('cms_page');
        $cms_page_store_table = $this->_core_resource->getTableName('cms_page_store');;
        $core_store = $this->_core_resource->getTableName('store');;

        $select = $readConnection->select()
            ->from(['cp' => $cms_page_table])
            ->join(
                ['cps' => $cms_page_store_table],
                'cp.page_id = cps.page_id',
                [])
            ->where('cp.'.$field_name.' = ?', $field_value)
            ->where('cps.store_id IN (?)', $stores)
            ->limit(1);

        $model = $this->_objectManager->create('Magento\Cms\Model\Page');

        if($page = $readConnection->fetchRow($select)) {
            $page_id = $page['page_id'];
            $page = $model->load($page_id);
        } elseif($is_single_store || (!$is_single_store && !$tmp_stores)) {
            $page = $model->load($field_value, $field_name);
        } else {
            $page = $model->load(0);
        }
        return $page;
        
    }
}