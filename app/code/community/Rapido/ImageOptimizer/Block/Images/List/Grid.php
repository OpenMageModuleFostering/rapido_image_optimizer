<?php

class Rapido_ImageOptimizer_Block_Images_List_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('RapidoImageOptimizerGrid');
        $this->_controller = 'images';
        $this->setUseAjax(true);
        $this->setDefaultSort('converted_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setEmptyText(Mage::helper('rapido_imageoptimizer')->__('No Images to Optimize in the Queue!'));
    }

    protected function _getCollectionClass()
    {
        return 'rapido_imageoptimizer/images';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel($this->_getCollectionClass())->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }


    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array('header' => Mage::helper('rapido_imageoptimizer')->__('ID'),
            'align' => 'right',
            'width' => '80px',
            'filter_index' => 'entity_id',
            'index' => 'entity_id',));

        $this->addColumn('createdate', array('header' => Mage::helper('rapido_imageoptimizer')
                ->__('Created at'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'datetime',
            'filter_index' => 'createdate',
            'index' => 'createdate',));

        $this->addColumn('image_name', array('header' => Mage::helper('rapido_imageoptimizer')->__('Image Name'),
            'filter_index' => 'image_name',
            'index' => 'image_name',));

        $this->addColumn('status', array('header' => Mage::helper('rapido_imageoptimizer')->__('Status'),
            'index' => 'status',
            'width' => '100px',
            'type' => 'options',
            'options' => Mage::getModel('rapido_imageoptimizer/status')
                    ->toOptionHash(),));

        $this->addColumn('converted_date', array('header' => Mage::helper('rapido_imageoptimizer')
                ->__('Converted at'),
            'align' => 'left',
            'width' => '160px',
            'type' => 'datetime',
            'filter_index' => 'converted_date',
            'index' => 'converted_date',));

        $this->addColumn('original_size', array('header' => Mage::helper('rapido_imageoptimizer')->__('Original Size'),
            'align' => 'right',
            'width' => '120px',
            'type' => 'number',
            'filter_index' => 'original_size',
            'index' => 'original_size',));

        $this->addColumn(
            'converted_size',
            array(
                'header' => Mage::helper('rapido_imageoptimizer')->__('Converted Size'),
                'align' => 'right',
                'width' => '120px',
                'type' => 'number',
                'filter_index' => 'converted_size',
                'index' => 'converted_size',
            )
        );

        $this->addColumn(
            'converted_saved',
            array(
                'header' => Mage::helper('rapido_imageoptimizer')->__('Saved Size'),
                'align' => 'right',
                'width' => '120px',
                'type' => 'number',
                'filter_index' => 'converted_saved',
                'index' => 'converted_saved',
            )
        );

        $this->addColumn('converted_saved_percent', array(
            'header' => Mage::helper('rapido_imageoptimizer')->__('Saved %'),
            'align' => 'right',
            'width' => '120px',
            'type' => 'number',
            'filter_index' => 'converted_saved_percent',
            'index' => 'converted_saved_percent',));

        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('image_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);

        $this->getMassactionBlock()->addItem(
            'revert',
            array(
                'label' => Mage::helper('rapido_imageoptimizer')
                    ->__('Revert Image'),
                'url' => $this->getUrl('adminhtml/tools_image_optimizer/revert'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'retry',
            array(
                'label' => Mage::helper('rapido_imageoptimizer')
                    ->__('Queue to optimize'),
                'url' => $this->getUrl('adminhtml/tools_image_optimizer/retry'),
            )
        );

        return $this;
    }

    public function getRowUrl($row)
    {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}