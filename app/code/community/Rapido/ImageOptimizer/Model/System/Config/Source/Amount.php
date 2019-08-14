<?php

class Rapido_ImageOptimizer_Model_System_Config_Source_Amount
{

    public function toOptionArray()
    {
        return
            array(
                array('value' => 5, 'label' => 5),
                array('value' => 10, 'label' => 10),
                array('value' => 25, 'label' => 25),
                array('value' => 50, 'label' => 50),
            );
    }
}