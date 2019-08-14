<?php

class Rapido_ImageOptimizer_Tools_Image_OptimizerController extends Mage_Adminhtml_Controller_Action
{

    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/tools/imageoptimizer');
    }

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

    public function retryAction()
    {
        $ids = $this->getRequest()->getPost('image_ids');

        $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
            ->addFieldToFilter('status', array('neq' => Rapido_ImageOptimizer_Model_Status::STATUS_CONVERTED))
            ->addFieldToFilter('entity_id', array('IN' => $ids));

        $collection->setDataToAll('status', Rapido_ImageOptimizer_Model_Status::STATUS_NEW);

        try {
            $collection->save();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Images queued for retry'));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Error changing status'));
        }

        $this->_redirectReferer();
    }

    public function revertAction()
    {
        $ids = $this->getRequest()->getPost('image_ids');

        $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
            ->addFieldToFilter('entity_id', array('in' => $ids));

        $error = 0;
        $done = 0;
        foreach ($collection as $image) {
            if ($image->getStatus() == Rapido_ImageOptimizer_Model_Status::STATUS_CONVERTED) {
                // Check if there is a backup file created
                $realFileName = $image->getFullPath();
                $originalFileName = $image->getFullPath() . '.original';
                $tmpFileName = $image->getFullPath() . '.tmp';
                if (!file_exists($originalFileName)) {
                    $error++;
                    Mage::log('Original file '.$originalFileName.' not found');
                    continue;
                }
                // Rename real file for extra backup (removed after rename)
                try {
                    rename($realFileName, $tmpFileName);
                } catch (Exception $ex) {
                    Mage::log($ex->getMessage());
                    $error++;
                    continue;
                }

                // Rename backup file to real filename
                try {
                    rename($originalFileName, $realFileName);
                } catch (Exception $ex) {
                    // If rename failed restore previous image
                    rename($tmpFileName, $realFileName);
                    Mage::log($ex->getMessage());
                    $error++;
                }

                // Remove tmp backup file
                try {
                    unlink($tmpFileName);
                } catch (Exception $ex) {
                    Mage::log($ex->getMessage());
                }
                $image->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_NEW);
                $done++;
            } else {
                Mage::log($image->getData());
                $error++;
            }
        }

        try {
            $collection->save();
        } catch (Exception $ex) {
            Mage::log($ex->getMessage());
        }
        if ($error>0) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('%s images failed to revert', $error));
        }

        if ($done>0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('%s images reverted', $done));
        }

        $this->_redirectReferer();
    }
}
