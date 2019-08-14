<?php

class VeInteractive_VePlatform_Block_Vemaster extends VeInteractive_VePlatform_Block_Template
{
    
    protected $data;
    
    public function __construct()
    {
        parent::__construct();
        $this->data = Mage::getModel('VeInteractive_VePlatform/data');
    }

    public function getMasterData(){
        $masterData = array(
            'currency'    => $this->data->getCurrency(),
            'language'    => $this->data->getLanguage(),
            'culture'     => $this->data->getCultureInformation(),
            'user'        => $this->data->getCustomer(),
            'currentPage' => $this->data->getCurrentPage(),
            'history'     => $this->data->getUserHistory(),
            'cart'        => $this->data->getCurrentOrder()
        );


        return $masterData;
    }

    public function _toHtml()
    {
        return $this->renderLocalTemplate( 'vemaster' );
    }
}