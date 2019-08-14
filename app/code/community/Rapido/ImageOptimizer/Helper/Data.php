<?php

class Rapido_ImageOptimizer_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected $_files = array();
    protected $_ext = array();
    protected $_excludedir = array();
    protected $auth = false;

    const MAX_FILE_SIZE = 16000000;

    public function collectFiles()
    {
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
        return $imgCount;
    }

    protected function findAllDirs($start)
    {
        $dirStack=[$start];
        while ($dir=array_shift($dirStack)) {
            $ar=glob($dir.'*', GLOB_ONLYDIR|GLOB_NOSORT|GLOB_MARK);
            if (!$ar) {
                continue;
            }
            foreach ($ar as $id => $path) {
                if (in_array($path, $this->_excludedir)) {
                    unset($ar[$id]);
                }
            }

            $dirStack=array_merge($dirStack, $ar);
            foreach ($ar as $DIR) {
                yield $DIR;
            }
        }
    }

    public function collectFilesOld()
    {
        $this->_ext = explode(",", $this->getConfig('extensions'));

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
                            'path' =>rtrim(Mage::getBaseDir(), '\\/') . DS . $path . DS,
                            'recur' => $dir['recur'],
                        );
                    break;
                case 2: // Exclude path
                    $this->_excludedir[] = rtrim(Mage::getBaseDir(), '\\/') . DS . $path . DS;
            }
        }
        $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection');
        $hashData = array();

        while ($images = $collection->fetchItem()) {
            $hashData[$images->getFullPath()] = array(
                'o' => $images->getOriginalChecksum(),
                'c' => $images->getConvertedChecksum()
            );
        }
        $imgCount = 0;
        $imgModel = Mage::getModel('rapido_imageoptimizer/images');
        foreach ($basePaths as $basePath) {
            $this->_files = array();
            $this->getDirectoryFiles($basePath['path'], $basePath['recur']);

            foreach ($this->_files as $id => $file) {
                $storeNewFile = true;
                if (isset($hashData[$file['path'] . $file['file']])) {
                    if ($hashData[$file['path'] . $file['file']]['o'] == $file['crc'] ||
                        $hashData[$file['path'] . $file['file']]['c'] == $file['crc']
                    ) {
                        unset($this->_files[$id]);
                        $storeNewFile = false;
                    }
                }
                if ($storeNewFile) {
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
        return $imgCount;
    }

    public function getDirectoryFiles($path, $recur = true)
    {
        if (in_array($path, $this->_excludedir)) {
            return;
        }
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_dir($path . $file)) {
                    if ($recur) {
                        $this->getDirectoryFiles($path . $file . DS, $recur);
                    }
                } else {
                    $ext = substr($file, strrpos($file, '.') + 1);
                    if (in_array($ext, $this->_ext)) {
                        $this->_files[] = array(
                            'path' => $path,
                            'file' => $file,
                            'crc' => sha1_file($path . $file),
                            'size' => filesize($path . $file),
                        );
                    }
                }
            }
            closedir($handle);
        }
    }

    public function convertImage($object)
    {
        if (!$this->getAuth()) {
            return false;
        }

        $params = array();
        $params['file'] = $object->getFullPath();

        // Upload file to ImageConverter
        $converted = $this->upload($params);
        $return = true;
        if ($converted['success'] == 1) {
            $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_PENDING);

            if (isset($converted['process_id'])) {
                // Store conversion process_id
                $object->setConvertedChecksum($converted['process_id'])
                    ->setConvertedDate(now());
            }
        } else {
            $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_FAILED);
            $return=false;
        }
        try {
            // Save converted file details
            $object->save();
        } catch (Exception $ex) {
            // Error saving details
            $return=false;
        }
        return $return;
    }

    public function downloadImage($object)
    {
        $params = array();
        $params['process_id'] = $object->getConvertedChecksum();

        // Check if file conversion has completed
        $converted = $this->download($params);
        if ($converted['success'] == 1) {
            // Conversion completed, download file and store on local filesystem

            $converted_size = 0;
            $saved_bytes = 0;
            $saved_percent = 0;

            if (isset($converted['optimized_url'])) {
                try {
                    // Retrieve new/converted file content
                    $newFile = $this->getFile($converted['optimized_url']);

                    if (strlen($newFile) > 0) {
                        if ($this->getConfig('keep_original') == 1) {
                            // Rename old file to keep original
                            rename($object->getFullPath(), $object->getFullPath() . '.original');
                        }

                        // Store new file
                        file_put_contents($object->getFullPath(), $newFile);
                    } else {
                        return false;
                    }
                    $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_CONVERTED);

                    // Generate/Store new Checksum
                    $object->setConvertedChecksum(sha1_file($object->getFullPath()))
                        ->setConvertedDate(now());

                    $converted_size = $converted['optimized_size'];
                    $saved_bytes = $converted['saved_bytes'];
                    $saved_percent = ($converted['saved_bytes'] / $converted['original_size']) * 100;

                } catch (Exception $ex) {
                    $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_FAILED);
                    // Error saving new file
                }
            } else {
                $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_FAILED);
            }
            // Store converted size and savings
            $object->setConvertedSize($converted_size)
                ->setConvertedSaved($saved_bytes)
                ->setConvertedSavedPercent($saved_percent);

        } elseif ($converted['error'] == 1) {
            // Conversion failed (no retry to download the file
            $object->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_FAILED);
        } else {
            // Conversion still pending
            return false;
        }
        try {
            // Save details
            $object->save();
            return true;
        } catch (Exception $ex) {
            // Error saving details
            return false;
        }
    }

    protected function upload($opts = array())
    {

        if (!isset($opts['file'])) {
            return array(
                "success" => false,
                "error" => "File parameter was not provided"
            );
        }

        if (!file_exists($opts['file'])) {
            return array(
                "success" => false,
                "error" => "File `" . $opts['file'] . "` does not exist"
            );
        }

        $file = $opts['file'];
        unset($opts['file']);

        $opts['base_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        $data = array_merge(array(
            "file" => $file,
            "data" => json_encode(
                array_merge(
                    $this->getAuth(),
                    $opts
                )
            )
        ));

        $response = self::request($data, $this->getConfig('api_url'));

        return $response;
    }

    public function download($opts)
    {
        $data = array_merge(array(
            "data" => json_encode(
                array_merge(
                    $this->getAuth(),
                    $opts
                )
            )
        ));
        $response = self::request($data, $this->getConfig('status_url'));

        return $response;
    }


    public function checkApi()
    {
        $data = array_merge(array(
                "data" => json_encode(
                    array_merge(
                        $this->getAuth()
                    )
                )
            ));
        $response = self::request($data, $this->getConfig('check_url'));
        return $response;
    }

    public function request($data, $url)
    {
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setMethod('POST');
        $client->setAdapter('Zend_Http_Client_Adapter_Curl');
        $adapter = $client->getAdapter();
        $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);

        $client->setConfig(
            array(
                'strict' => false,
                'maxredirects' => 0,
                'timeout'      => 10,
            )
        );

        if (isset($data['file'])) {
            $client->setFileUpload($data['file'], 'file');
            unset($data['file']);
        }

        foreach ($data as $param => $val) {
            $client->setParameterPost($param, $val);
        }
        $response = $client->request();
        return json_decode($response->getBody(), true);
    }

    public function getFile($url)
    {
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setMethod('GET');
        $client->setAdapter('Zend_Http_Client_Adapter_Curl');
        $adapter = $client->getAdapter();
        $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);

        $client->setConfig(
            array(
                'strict' => false,
                'maxredirects' => 0,
                'timeout'      => 120,
            )
        );

        $response = $client->request();
        return $response->getBody();
    }

    protected function getAuth()
    {
        if (!$this->auth) {
            // Initialize API Authentication
            if ($this->getConfig('api_user') && $this->getConfig('api_secret')) {
                $this->auth = array(
                    "auth" => array(
                        "api_key" => $this->getConfig('api_user'),
                        "api_secret" => $this->getConfig('api_secret')
                    )
                );
            } else {
                return false;
            }
        }
        return $this->auth;
    }
    protected function getConfig($key)
    {
        return Mage::getStoreConfig('cms/rapido_imageoptimizer/' . $key);
    }
}