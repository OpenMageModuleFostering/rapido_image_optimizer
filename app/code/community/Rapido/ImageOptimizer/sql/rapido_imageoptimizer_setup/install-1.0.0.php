<?php

$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()->newTable($installer->getTable('rapido_imageoptimizer/images'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        'auto_increment' => true,), 'Entity Id')
    ->addColumn('createdate', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Create date')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Status')
    ->addColumn('full_path', Varien_Db_Ddl_Table::TYPE_TEXT, 512, array(), 'Full Image Path')
    ->addColumn('image_name', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(), 'Image Filename')
    ->addColumn('original_checksum', Varien_Db_Ddl_Table::TYPE_TEXT, 40, array(), 'Original File Checksum')
    ->addColumn('original_size', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Original File Size')
    ->addColumn('converted_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Conversion Date')
    ->addColumn('converted_checksum', Varien_Db_Ddl_Table::TYPE_TEXT, 40, array(), 'Converted File Checksum')
    ->addColumn('converted_size', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Converted File Size')
    ->addColumn('converted_saved', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Converted File Size Saved')
    ->addColumn('converted_saved_percent', Varien_Db_Ddl_Table::TYPE_DECIMAL, '5,2', array(), 'Converted File Size Saved Percentage');

$installer->getConnection()->createTable($table);

$installer->getConnection()
    ->addIndex($installer->getTable('rapido_imageoptimizer/images'), $installer->getIdxName('rapido_imageoptimizer/images', array('original_checksum')), array('original_checksum'));

$installer->getConnection()
    ->addIndex($installer->getTable('rapido_imageoptimizer/images'), $installer->getIdxName('rapido_imageoptimizer/images', array('converted_checksum')), array('converted_checksum'));

$installer->endSetup();