<?php

class Rapido_ImageOptimizer_Model_System_Config_Backend_Serialized extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{

    protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        $this->setValue(empty($value) ? false : unserialize($value));
    }

    protected function _beforeSave()
    {

        if (is_array($this->getValue())) {
            $val = $this->getValue();
            unset($val['__empty']);
            $this->setValue(serialize($val));
        }
    }

}
