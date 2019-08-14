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

    protected $_excludedir = array();
    const MAX_FILE_SIZE = 16000000;

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
                    $this->debugMsg('Uploading image: %s (ID: %s)', $file->getImageName(), $file->getId());
                    if ($helper->convertImage($file)) {
                        $this->debugMsg('Waiting for conversion: %s (ID: %s)', $file->getImageName(), $file->getId());
                        $converted++;
                    }
                }

                // Download files
                $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection')
                    ->addFilter('status', array('eq' => Rapido_ImageOptimizer_Model_Status::STATUS_PENDING))
                    ->setOrder('original_size', 'DESC');

                foreach ($collection as $file) {
                    if ($helper->downloadImage($file)) {
                        $this->debugMsg('Conversion completed: %s (ID: %s)', $file->getImageName(), $file->getId());
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
        } elseif (isset($this->_args['testcollect'])) {
            $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection');
            $hashData = array();

            while ($images = $collection->fetchItem()) {
                $hashData[$images->getFullPath()] = array(
                    'o' => $images->getOriginalChecksum(),
                    'c' => $images->getConvertedChecksum()
                );
            }

            $ext = '{*.'.implode(',*.', explode(",", $this->getConfig('extensions'))).'}';

            $dirs = unserialize($this->getConfig('directories'));
            $basePaths = array();
            foreach ($dirs as $dir) {
                $path = trim($dir['path']);
                $path = trim($path, '\\/');
                switch ($dir['action']) {
                    case 0: // Disabled
                        break;
                    case 1: // Include path
                        $basePaths[] = array(
                            'path' =>rtrim(Mage::getBaseDir(), '\\/') . DS . $path,
                            'recur' => $dir['recur'],
                        );
                        break;
                    case 2: // Exclude path
                        $this->_excludedir[] = rtrim(Mage::getBaseDir(), '\\/') . DS . $path . DS;
                }
            }
            $imgCount = 0;
            $imgModel = Mage::getModel('rapido_imageoptimizer/images');
            foreach ($basePaths as $basePath) {
                if ($basePath['recur']) {
                    $dirs = $this->findAllDirs($basePath['path']);
                } else {
                    $dirs[] = $basePath['path'];
                }
                foreach ($dirs as $dir) {
                    $match = glob($dir . $ext, GLOB_NOSORT|GLOB_BRACE);
                    if (!$match) {
                        continue;
                    }
                    foreach ($match as $image) {
                        $file = array();
                        $file['path'] = $dir;
                        $file['file'] = str_replace($dir, '', $image);
                        $file['crc']  = sha1_file($image);
                        $file['size'] = filesize($image);

                        if (isset($hashData[$file['path'] . $file['file']])) {
                            if ($hashData[$file['path'] . $file['file']]['o'] == $file['crc'] ||
                                $hashData[$file['path'] . $file['file']]['c'] == $file['crc']
                            ) {
                                continue;
                            }
                        }
                        $img = $imgModel
                            ->setCreatedate(now())
                            ->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_NEW)
                            ->setFullPath($file['path'] . $file['file'])
                            ->setImageName($file['file'])
                            ->setOriginalChecksum($file['crc'])
                            ->setOriginalSize($file['size']);

                        if ($file['size'] > self::MAX_FILE_SIZE) {
                            $img->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_TOBIG);
                        }

                        try {
                            $img->save();

                            $hashData[$img->getFullPath()] = array(
                                'o' => $img->getOriginalChecksum(),
                                'c' => $img->getConvertedChecksum()
                            );
                            $imgCount++;
                        } catch (Exception $ex) {
                            Mage::log($ex->getMessage());
                        }

                        $img->unsetData();
                    }
                }
            }

            echo Mage::helper('rapido_imageoptimizer')->__('%s new image files collected!', $imgCount) . "\n\n";

        } else {
            echo $this->usageHelp();
        }
    }


    protected function debugMsg()
    {
        if (!isset($this->_args['debug'])) {
            return;
        }

        echo call_user_func_array("sprintf", func_get_args());
        echo "\n";
    }
    protected function getConfig($key)
    {
        return Mage::getStoreConfig('cms/rapido_imageoptimizer/' . $key);
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
