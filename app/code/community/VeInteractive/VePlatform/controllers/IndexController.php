<?php
class VeInteractive_VePlatform_IndexController extends Mage_Core_Controller_Front_Action {
    
    private $data;
    
    public function updateCartAction()
    {
        sleep(1);
        $this->data = Mage::getModel('VeInteractive_VePlatform/data');
        die(Mage::helper('core')->jsonEncode($this->data->getCurrentOrder()));
    }
}