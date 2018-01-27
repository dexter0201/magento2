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
namespace Ves\PageBuilder\Block\Adminhtml\Pagebuilder\Edit\Tab;

/**
 * Customer account form block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Settings extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Ves_PageBuilder::page_edit')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('block_');

        $model = $this->_coreRegistry->registry('ves_pagebuilder');

        $wrapperFieldset = $form->addFieldset(
            'wrapper_setting',
            ['legend' => __('Wrapper For Page Builder'), 'class' => 'fieldset-wide']
        );

        $wrapperFieldset->addField(
            'enable_wrapper',
            'select',
            [
                'label' => __('Enable Wrapper Block'),
                'title' => __('Enable Wrapper Block'),
                'name' => 'enable_wrapper',
                'options' => [
                                '2' => __('Disabled'),
                                '1' => __('Enabled')
                            ],
                'disabled' => $isElementDisabled
            ]
        );

        $wrapperFieldset->addField(
            'select_wrapper_class',
            'select',
            [
                'label' => __('Select Wrapper Class'),
                'title' => __('Select Wrapper Class'),
                'note' => __('Choose a container size'),
                'name' => 'select_wrapper_class',
                'options' => [
                                '' => __('-- Select A Class --'),
                                'container' => __('container'),
                                'container-small' => __('container-small'),
                                'container-large' => __('container-large'),
                                'container-fluid' => __('container-fluid'),
                                'main-container' => __('main-container')
                            ],
                'disabled' => $isElementDisabled
            ]
        );

        $wrapperFieldset->addField(
            'wrapper_class',
            'text',
            [
                'name' => 'wrapper_class',
                'label' => __('Custom Wrapper Class'),
                'title' => __('Custom Wrapper Class'),
                'note' => __('For example: <strong>container</strong>'),
                'disabled' => $isElementDisabled
            ]
            );

        $pageSettingFieldset = $form->addFieldset(
            'page_setting',
            ['legend' => __('Page Builder Widget Settings'), 'class' => 'fieldset-wide']
        );

        $pageSettingFieldset->addField(
            'template',
            'text',
            [
                'name' => 'template',
                'label' => __('Custom Template'),
                'title' => __('Custom Template'),
                'note' => __('Input custom module template file path. For example: pagebuilder/default.phtml Empty for default'),
                'disabled' => $isElementDisabled
            ]
            );

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
