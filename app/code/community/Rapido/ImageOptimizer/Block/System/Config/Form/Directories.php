<?php

class Rapido_ImageOptimizer_Block_System_Config_Form_Directories
    extends Rapido_ImageOptimizer_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('path', array(
            'label' => Mage::helper('rapido_imageoptimizer')->__('Path'),
        ));

        $this->addColumn('recur', array(
            'label' => Mage::helper('rapido_imageoptimizer')->__('Recursive'),
            'type' => 'select',
            'options' => array("1" => "Yes", "0" => "No"),
            'values' => 1,
            'style' => 'width:50px'
        ));

        $this->addColumn('action', array(
            'label' => Mage::helper('rapido_imageoptimizer')->__('Action'),
            'type' => 'select',
            'options' => array("1" => "Include", "2" => "Exclude", "0" => "Disabled"),
            'values' => 1,
            'style' => 'width:50px'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('rapido_imageoptimizer')->__('Add new');
        $this->setTemplate('rapido/imageoptimizer/system/config/form/field/array.phtml');
        parent::__construct();
    }

}
