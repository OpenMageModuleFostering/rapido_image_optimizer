<?php

class Rapido_ImageOptimizer_Block_Images_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('rapido/imageoptimizer/widget/grid/container.phtml');
        $this->_removeButton('add');

        $this->_headerText = Mage::helper('rapido_imageoptimizer')->__('Image Optimizer Queue');
        $this->_blockGroup = 'rapido_imageoptimizer';
        $this->_controller = 'images_list';
    }

    protected function _prepareLayout()
    {
        $this->setChild('totals',
            $this->getLayout()->createBlock( $this->_blockGroup.'/' . $this->_controller . '_totals',
                $this->_controller . '.totals') );
        return parent::_prepareLayout();
    }

    public function getTotalsHtml()
    {
        return $this->getChildHtml('totals');
    }
}