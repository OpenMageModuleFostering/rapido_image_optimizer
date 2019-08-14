<?php

class Rapido_ImageOptimizer_Model_Resource_Images_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected $_eventPrefix = 'rapido_imageoptimizer_images_collection';
    protected $_eventObject = 'rapido_imageoptimizer_images_collection';

    protected function _construct()
    {
        $this->_init('rapido_imageoptimizer/images');
    }
}