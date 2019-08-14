<?php

class Rapido_ImageOptimizer_Block_Images_List_Totals extends Mage_Core_Block_Template
{
    protected $stats = false;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('rapido/imageoptimizer/totals.phtml');
    }

    protected function getStatistics()
    {
        if (!$this->stats) {
            $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection');

            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('status')
                ->columns('COUNT(status) AS amount_images')
                ->columns('SUM(original_size) AS original_size')
                ->columns('SUM(converted_size) AS converted_size')
                ->columns('SUM(converted_saved) AS converted_saved')
                ->columns('AVG(converted_saved_percent) AS converted_saved_percent')
                ->group(array('status'));

            if ($collection->count()>0) {
                $this->stats = new Varien_Object();
                foreach ($collection as $row) {
                    $this->stats->setImagesCollected((int)$this->stats->getImagesCollected()+$row->getAmountImages());
                    $this->stats->setSizeImages((int)$this->stats->getSizeImages()+$row->getOriginalSize());

                    if ($row->getStatus()==Rapido_ImageOptimizer_Model_Status::STATUS_NEW) {
                        $this->stats->setImagesWaiting($row->getAmountImages());
                    }

                    if ($row->getStatus()==Rapido_ImageOptimizer_Model_Status::STATUS_CONVERTED) {
                        $this->stats->setImagesConverted($row->getAmountImages());

                        $this->stats->setSizeOriginal($row->getOriginalSize());
                        $this->stats->setSizeConverted($row->getConvertedSize());
                        $this->stats->setSizeSaved($row->getConvertedSaved());

                        $this->stats->setPercentageSaved(round($row->getConvertedSavedPercent(), 2).'%');
                    }
                }
            }
        }
        return $this->stats;
    }

    protected function getReadableSize($bytes, $decimals = 2)
    {

        $size   = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes/pow(1024, $factor)).@$size[$factor];
    }
}