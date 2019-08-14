<?php
$filePath = dirname(dirname(__FILE__)) . '/Helper/ExceptionHandler.php';
if (file_exists($filePath)) {
    require_once $filePath;
}

class VeInteractive_VePlatform_Block_Vemaster extends VeInteractive_VePlatform_Block_Template
{

    private $data;
    private $exceptionHandler;


    public function __construct()
    {
        parent::__construct();

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
        $this->exceptionHandler = new ExceptionHandler();
    }

    public function getMasterData()
    {
        try {
            $masterData = array(
                'currency' => $this->data->getCurrency(),
                'language' => $this->data->getLanguage(),
                'culture' => $this->data->getCultureInformation(),
                'user' => $this->data->getCustomer(),
                'currentPage' => $this->data->getCurrentPage(),
                'history' => $this->data->getUserHistory(),
                'cart' => $this->data->getCurrentOrder()
            );
        } catch (Exception $ex) {
            $this->exceptionHandler->logException($ex);

            $masterData = array(
                'currency' => null,
                'language' => null,
                'culture' => null,
                'user' => null,
                'currentPage' => null,
                'history' => null,
                'cart' => null
            );
        }

        return $masterData;
    }

    public function showVeData()
    {
        $isInstalled = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::MODULE_INSTALLED);  
        $isVeDataActive = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::VEDATAACTIVE) == "1";
      
        return $isInstalled && $isVeDataActive;
    }

    public function _toHtml()
    {
        return $this->renderLocalTemplate('vemaster');
    }
}