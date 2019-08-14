<?php

class Rapido_ImageOptimizer_Model_Observer
{

    public function collectFiles()
    {
        if (Mage::getStoreConfigFlag('cms/rapido_imageoptimizer/daily_collect_files')) {
            Mage::helper('rapido_imageoptimizer')->collectFiles();
        }
    }

    public function convertFiles()
    {
        if (Mage::getStoreConfigFlag('cms/rapido_imageoptimizer/hourly_convert_files')) {
            $helper = Mage::helper('rapido_imageoptimizer');
            $amount = Mage::getStoreConfig('cms/rapido_imageoptimizer/max_conversion_amount');

            $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
                ->addFilter('status', array('eq' => Rapido_ImageOptimizer_Model_Status::STATUS_NEW));
                //->setOrder('original_size', 'DESC');
            if ($amount > 0) {
                $collection->setPageSize($amount);
            }

            $converted = 0;
            foreach ($collection as $file) {
                if ($helper->convertImage($file)) {
                    $converted++;
                }
            }

            // Download converted images
            $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
                ->addFilter('status', array('eq' => Rapido_ImageOptimizer_Model_Status::STATUS_PENDING));

            if ($amount > 0) {
                $collection->setPageSize($amount);
            }

            $converted = 0;
            foreach ($collection as $file) {
                if ($helper->downloadImage($file)) {
                    $converted++;
                }
            }
        }
    }
}