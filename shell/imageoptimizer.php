<?php

require_once 'abstract.php';

/**
 * Rapido ImageOptimizer script
 *
 * @category    Rapido
 * @package     Rapido_ImageOptimizer
 * @author      Rapido <support@rapido.nu>
 */
class Rapido_Shell_ImageOptimizer extends Mage_Shell_Abstract
{

    /**
     * Run script
     *
     */
    public function run()
    {
        $helper = Mage::helper('rapido_imageoptimizer');
        if (isset($this->_args['collect'])) {
            $amount = $helper->collectFiles();
            echo Mage::helper('rapido_imageoptimizer')->__('%s new image files collected!', $amount) . "\n\n";
        } elseif (isset($this->_args['convert'])) {
            $amount = $this->getArg('amount');
            $id = $this->getArg('id');

            if (!$amount || $amount>500) {
                $amount = 500;
            }

            $step = $amount;
            if ($amount>5) {
                $step = 5;
            }

            $converted = 0;
            $downloaded = 0;
            for ($i=0; $i<($amount/$step); $i++) {
                // Convert files
                $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
                    ->addFilter('status', array('eq' => Rapido_ImageOptimizer_Model_Status::STATUS_NEW))
                    ->setOrder('original_size', 'DESC')
                    ->setPageSize($step);

                if ($id) {
                    $collection->addFilter('entity_id', $id);
                }

                foreach ($collection as $file) {
                    if ($helper->convertImage($file)) {
                        $converted++;
                    }
                }

                // Download files
                $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
                    ->addFilter('status', array('eq' => Rapido_ImageOptimizer_Model_Status::STATUS_PENDING))
                    ->setOrder('original_size', 'DESC');

                foreach ($collection as $file) {
                    if ($helper->downloadImage($file)) {
                        $downloaded++;
                    }
                }
            }
                echo Mage::helper('rapido_imageoptimizer')
                        ->__(
                            '%s image uploaded and %s images downloaded!',
                            $converted,
                            $downloaded
                        ) . "\n\n";

        } elseif (isset($this->_args['test'])) {
            var_dump($helper->checkApi());
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f imageoptimzer.php -- [options]

  collect       Collect files to convert
  convert       Convert Images in queue
  --amount [x]  Amount of images to convert per run
  help          This help

USAGE;
    }
}

$shell = new Rapido_Shell_ImageOptimizer();
$shell->run();
