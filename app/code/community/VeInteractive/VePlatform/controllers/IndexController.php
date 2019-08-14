<?php
$filePath = dirname(dirname(__FILE__)) . '/Helper/ExceptionHandler.php';
if (file_exists($filePath)) {
    require_once $filePath;
}

class VeInteractive_VePlatform_IndexController extends Mage_Core_Controller_Front_Action
{

    private $data;
    private $exceptionHandler;

    public function updateCartAction()
    {
        $this->exceptionHandler = new ExceptionHandler();
        try {
            sleep(1);
            $this->data = Mage::getModel('VeInteractive_VePlatform_Model_Data',
                array(
                    'locale' => Mage::app()->getLocale(),
                    'store' => Mage::app()->getStore(),
                    'request' => Mage::app()->getRequest(),
                    'coreSession' => Mage::getSingleton('core/session'),
                    'catalogCategory' => Mage::getModel('catalog/category'),
                    'catalogLayer' => Mage::getModel('catalog/layer'),
                    'checkoutSession' => Mage::getModel('checkout/session'),
                    'coreHelper' => Mage::helper('core'),
                    'taxHelper' => Mage::helper('tax'),
                    'currentProductRegistry' => Mage::registry('current_product'),
                    'currentCategoryRegistry' => Mage::registry('current_category'),
                    'customerSession' => Mage::getModel('customer/session'),
                    'coreUrlHelper' => Mage::helper('core/url'),
                    'checkoutHelper' => Mage::helper('checkout'),
                    'directoryCurrency' => Mage::getModel('directory/currency'),
                    'rule' => Mage::getModel('salesrule/rule'),
                    'catalogMedia' => Mage::getModel('catalog/product_media_config'),
                    'frontController' => Mage::app()->getFrontController()
                )
            );

            $currentOrder = $this->data->getCurrentOrder();
            $currentOrder = Mage::helper('core')->jsonEncode($currentOrder);
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
            $currentOrder = Mage::helper('core')->jsonEncode(null);
        }
        die($currentOrder);
    }
}