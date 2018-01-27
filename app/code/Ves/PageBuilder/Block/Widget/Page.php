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
 * @package    Ves_PageBuilder
 * @copyright  Copyright (c) 2016 Venustheme (http://www.venustheme.com/)
 * @license    http://www.venustheme.com/LICENSE-1.0.html
 */
namespace Ves\PageBuilder\Block\Widget;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Json\EncoderInterface;

class Page extends AbstractWidget
{


    /**
     * Block Collection
     */
    protected $_blockCollection;

	/**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Ves\PageBuilder\Helper\Data
     */
    protected $_blockHelper;

    /**
    * @var \Magento\Store\Model\StoreManagerInterface
    */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @var string $_config
     * 
     * @access protected
     */
    protected $_listDesc = array();
    
    /**
     * @var string $_config
     * 
     * @access protected
     */
    protected $_show = 0;
    protected $_theme = "";

    protected $_banner = null;
    protected $jsonEncoder;

     /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context         
     * @param \Magento\Framework\Registry                      $registry 
     * @param \Magento\Framework\Filesystem                    $filesystem,
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager,       
     * @param \Ves\PageBuilder\Helper\Data                    $blockHelper     
     * @param \Ves\PageBuilder\Model\Block                    $blockCollection 
     * @param array                                            $data            
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
       /*\Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,*/
        \Ves\PageBuilder\Helper\Data $blockHelper,
        \Ves\PageBuilder\Model\Block $blockCollection,
        \Magento\Framework\App\Http\Context $httpContext,
        EncoderInterface $jsonEncoder,
        \Ves\PageBuilder\Helper\MobileDetect $mobileDetectHelper,
        array $data = []
        ) {
        $this->_blockCollection = $blockCollection;
        $this->_blockHelper = $blockHelper;
        $this->_coreRegistry = $registry;
        /*$this->_storeManager = $storeManager;*/
        /*$this->_filesystem = $filesystem;*/
        $this->jsonEncoder = $jsonEncoder;
        $this->httpContext = $httpContext;

        parent::__construct($context, $blockHelper, $mobileDetectHelper, $data);

        $this->setTemplate("pagebuilder/default.phtml");
    }

    
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData([
            'cache_lifetime' => 86400,
            'cache_tags' => [\Ves\PageBuilder\Model\Block::CACHE_TAG,
            ], ]);
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $block_id = $this->getConfig("block_id");
        $block_id = $block_id?$block_id:0;
        $code = $this->getConfig('code');

        $device = "desktop";

        if($this->getMobileDetect()->isMobile() && !$this->getMobileDetect()->isTablet()) { //If current are mobile devices
            $device = "mobile";
        } elseif($this->getMobileDetect()->isTablet()) { //If current are mobile devices
            $device = "tablet";
        }

        $conditions = $code.".".$block_id.".".$device;

        return [
        'VES_PAGEBUILDER_BUILDER_WIDGET',
        $this->_storeManager->getStore()->getId(),
        $this->_design->getDesignTheme()->getId(),
        $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
        $conditions
        ];
    }

    public function getJsonEncoder() {
        return $this->jsonEncoder;
    }

    public function _prepareLayout()
    {
        
        if(!$this->_blockHelper->getConfig('general/show')) return parent::_prepareLayout();
       
        $block_id = $this->getConfig("block_id");
        $block_id = $block_id?$block_id:0;
        $code = $this->getConfig('code');
        $this->_banner = null;
        if($block_id) {
            $this->_banner  = $this->_blockCollection->load( $block_id );
        }

        if(!$this->_banner && $code) {
            $this->_banner = $this->_blockCollection->getBlockByAlias($code);
        }

        if($this->_banner && !$this->_blockCollection->checkBlockProfileAvailable($this->_banner)) {
            $this->_banner = null;
        }

        if($this->_banner) {
            $params = $this->_banner->getParams();
            $params = \Zend_Json::decode($params);

            $settings = $this->_banner->getSettings();
            $settings = unserialize($settings);
            $this->setSettings($settings);

            $this->assign("layouts", $params);
            $this->assign("settings", $settings);
            $this->assign("is_container", $this->_banner->getContainer());
            $this->assign("class", $this->_banner->getPrefixClass());
            $this->assign("show_title", $this->getConfig("show_title"));
            $this->assign("disable_wrapper", $this->getConfig("disable_wrapper"));
            $this->assign("heading", $this->_banner->getTitle());

            if(1 == $this->_banner->getContainer()) {
                $this->setTemplate("pagebuilder/default_container.phtml");
            }
        }
        return parent::_prepareLayout();
    }

    public function _toHtml() {
        $settings = $this->getSettings();
        $html = "";
        if($settings && isset($settings['enable_wrapper']) && $settings['enable_wrapper'] == 1) {
            $wrapper_class = (isset($settings['select_wrapper_class'])?$settings['select_wrapper_class']." ":'');
            $wrapper_class .= (isset($settings['wrapper_class'])?$settings['wrapper_class']:'');

            if(isset($this->_banner) && $this->_banner) {
                $wrapper_class .= " ".$this->_banner->getAlias();
            }
            $html = '<div class="'.$wrapper_class.'">'.parent::_toHtml().'</div>';
        } else {
            $html = parent::_toHtml();
        }

        if($this->_blockHelper->getConfig('general/minify_html')) {
            $html = $this->_blockHelper->minify_html( $html );
        }

        return $html;

    }

    public function renderWidgetShortcode( $shortcode = "") {
        if($shortcode) {
            return $this->_blockHelper->filter($shortcode);
        }
        return;
    }

    public function getBaseMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getBaseMediaDir() {
        return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    public function getImageUrl($image = "") {
        $base_media_url = $this->getBaseMediaUrl();
        $base_media_dir = $this->getBaseMediaDir();

        $_imageUrl = $base_media_dir.$image;

        if (file_exists($_imageUrl)){
            return $base_media_url.$image;
        }
        return false;
    }
}