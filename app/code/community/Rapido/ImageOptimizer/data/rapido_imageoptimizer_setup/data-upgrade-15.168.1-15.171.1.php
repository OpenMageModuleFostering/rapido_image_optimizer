<?php

$installer=$this;

$includeDir = explode("\n", Mage::getStoreConfig('cms/rapido_imageoptimizer/include_dirs'));
$excludeDir = explode("\n", Mage::getStoreConfig('cms/rapido_imageoptimizer/exclude_dirs'));

$dirs = array();
foreach ($includeDir as $dir) {
    $dir = trim($dir);
    if (!$dir) {
        continue;
    }
    $dirs[] = array(
        'path' => $dir,
        'recur' => 1,
        'action' => 1 // Include path
        );
}

foreach ($excludeDir as $dir) {
    $dir = trim($dir);
    if (!$dir) {
        continue;
    }
    $dirs[] = array(
        'path' => $dir,
        'recur' => 1,
        'action' => 2 // Exclude path
    );
}

$setup = new Mage_Core_Model_Config();
$setup->saveConfig('cms/rapido_imageoptimizer/directories', serialize($dirs), 'default', 0);
