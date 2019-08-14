<?php

class Rapido_ImageOptimizer_Model_Images extends Mage_Core_Model_Abstract
{

    protected $_eventPrefix = 'rapido_imageoptimizer_images';
    protected $_eventObject = 'images';

    protected function _construct()
    {
        $this->_init('rapido_imageoptimizer/images');
    }

    public function loadByAttribute($attribute, $value)
    {
        $collection = $this->getCollection()->addFilter($attribute, $value);

        return $collection;
    }
}