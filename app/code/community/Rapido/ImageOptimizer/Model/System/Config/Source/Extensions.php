<?php

class Rapido_ImageOptimizer_Model_System_Config_Source_Extensions
{

    public function toOptionArray()
    {
        return
            array(
                array('value' => 'gif', 'label' => 'gif'),
                array('value' => 'jpg', 'label' => 'jpg'),
                array('value' => 'jpeg', 'label' => 'jpeg'),
                array('value' => 'png', 'label' => 'png'),
            );
    }
}