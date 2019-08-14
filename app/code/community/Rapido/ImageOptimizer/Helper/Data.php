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
        $this->_ext = explode(",", $this->getConfig('extensions'));

        $excluded = explode("\n", $this->getConfig('exclude_dirs'));
        foreach ($excluded as $dir) {
            $dir = trim($dir, '\\/');
            $this->_excludedir[] = rtrim(Mage::getBaseDir(), '\\/') . '/' . $dir . '/';
        }
        $basepaths = array();
        $included = explode("\n", $this->getConfig('include_dirs'));
        foreach ($included as $dir) {
            $dir = trim($dir, '\\/ ');
            if ($dir) {
                $basepaths[] = rtrim(Mage::getBaseDir(), '\\/') . '/' . $dir . '/';
            }
        }

        foreach ($basepaths as $basepath) {
            $this->getDirectoryFiles($basepath);
        }

        $collection = Mage::getResourceModel('rapido_imageoptimizer/images_collection');
        $hashData = array();
        foreach ($collection as $images) {
            $hashData[$images->getFullPath()] = array(
                'original_checksum' => $images->getOriginalChecksum(),
                'converted_checksum' => $images->getConvertedChecksum()
            );
        }

        $imgModel = Mage::getModel('rapido_imageoptimizer/images');
        foreach ($this->_files as $id => $file) {
            $storeNewFile = true;
            if (isset($hashData[$file['path'] . $file['file']])) {
                if ($hashData[$file['path'] . $file['file']]['original_checksum'] == $file['checksum'] ||
                    $hashData[$file['path'] . $file['file']]['converted_checksum'] == $file['checksum']
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
                    ->setOriginalChecksum($file['checksum'])
                    ->setOriginalSize($file['size']);

                if ($file['size']>self::MAX_FILE_SIZE) {
                    $img->setStatus(Rapido_ImageOptimizer_Model_Status::STATUS_TOBIG);
                }

                try {
                    $img->save();
                } catch (Exception $ex) {
                    Mage::log($ex->getMessage());
                }
                $img->unsetData();
            }
        }
        return count($this->_files);
    }

    public function getDirectoryFiles($path)
    {
        if (in_array($path, $this->_excludedir)) {
            return;
        }
        $tmpFiles = scandir($path);
        foreach ($tmpFiles as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (is_dir($path . $file)) {
                $this->getDirectoryFiles($path . $file . '/');
            } else {
                $ext = substr($file, strrpos($file, '.') + 1);
                if (in_array($ext, $this->_ext)) {
                    $this->_files[] = array(
                        'path' => $path,
                        'file' => $file,
                        'checksum' => sha1_file($path . $file),
                        'size' => filesize($path . $file),
                    );
                }
            }
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