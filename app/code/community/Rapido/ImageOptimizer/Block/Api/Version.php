<?php

class Rapido_ImageOptimizer_Block_Api_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return Mage::getConfig()->getModuleConfig("Rapido_ImageOptimizer")->version;
    }




}
