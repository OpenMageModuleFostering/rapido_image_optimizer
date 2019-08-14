<?php

class Rapido_ImageOptimizer_Block_Api_Check extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $buttonHtml = $this->_getAddRowButtonHtml($this->__('Check Account'));
        return $buttonHtml;
    }


    protected function _getAddRowButtonHtml($title)
    {
        $buttonBlock = $this->getElement()->getForm()->getParent()->getLayout()->createBlock('adminhtml/widget_button');
        $url = $this->getUrl("adminhtml/tools_image_optimizer/check");

        $buttonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("window.location.href='".$url."'")
            ->toHtml();

        return $buttonHtml;
    }



}
