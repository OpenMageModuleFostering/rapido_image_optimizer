<?php

class Rapido_ImageOptimizer_Model_Resource_Images extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('rapido_imageoptimizer/images', 'entity_id');
    }
}