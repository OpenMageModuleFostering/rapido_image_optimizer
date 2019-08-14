<?php

class Rapido_ImageOptimizer_Model_Status
{

    const STATUS_NEW = 0;
    const STATUS_CONVERTED = 1;
    const STATUS_PENDING = 2;
    const STATUS_FAILED = 3;

    public function toOptionArray()
    {
        return
            array(
                array('value' => self::STATUS_NEW, 'label' => Mage::helper('rapido_imageoptimizer')->__('New')),
                array('value' => self::STATUS_PENDING, 'label' => Mage::helper('rapido_imageoptimizer')->__('Converting')),
                array('value' => self::STATUS_CONVERTED, 'label' => Mage::helper('rapido_imageoptimizer')->__('Converted')),
                array('value' => self::STATUS_FAILED, 'label' => Mage::helper('rapido_imageoptimizer')->__('Failed')),
            );
    }

    public function toOptionHash()
    {
        return array(
            self::STATUS_NEW => Mage::helper('rapido_imageoptimizer')->__('New'),
            self::STATUS_PENDING => Mage::helper('rapido_imageoptimizer')->__('Converting'),
            self::STATUS_CONVERTED => Mage::helper('rapido_imageoptimizer')->__('Converted'),
            self::STATUS_FAILED => Mage::helper('rapido_imageoptimizer')->__('Failed'),
        );
    }
}