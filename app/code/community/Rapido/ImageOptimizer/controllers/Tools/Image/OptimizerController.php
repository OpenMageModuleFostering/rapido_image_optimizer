<?php

class Rapido_ImageOptimizer_Tools_Image_OptimizerController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()
            ->_addContent(
                $this->getLayout()
                    ->createBlock('rapido_imageoptimizer/images_list')
            )
            ->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->createBlock('rapido_imageoptimizer/images_list_grid')
            ->toHtml());
    }

    public function checkAction()
    {
        $response = Mage::helper('rapido_imageoptimizer')->checkApi();

        $session = Mage::getSingleton('adminhtml/session');
        if (is_array($response) && !$response['error']) {
            $expireDate = Mage::helper('core')->formatDate($response['expire_date'], 'medium');
            $session->addSuccess($this->__('Account valid! (Expires: %s)', $expireDate));
        } else {
            $session->addError($this->__('Account invalid!'));
        }
        $this->_redirectReferer();
    }
}