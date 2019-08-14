<?php

class VeInteractive_VePlatform_Observer_AdminUserLoadAfter
{
    private $exceptionHandler;
    public function __construct()
    {
        $filePath = dirname(dirname(__FILE__)) . '/Helper/ExceptionHandler.php';
        if (file_exists($filePath)) {
            require_once $filePath;
        }

        $this->exceptionHandler = new ExceptionHandler();
    }

    public function checkInstallation($observer)
    {
        try {
            $isAjax = Mage::app()->getRequest()->isXmlHttpRequest();
            if (!$isAjax) {
                $this->checkModuleInstallation();
            }
        } catch (Exception $exception) {

            $this->exceptionHandler->logException($exception);
        }
    }

    private function checkModuleInstallation()
    {
        try {
            $module_installed = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::MODULE_INSTALLED);

            if (!$module_installed && $this->exceptionHandler->validate()) {
                Mage::getConfig()->saveConfig(VeInteractive_VePlatform_Helper_Data::MODULE_INSTALLED, true);
                Mage::getConfig()->saveConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL, 1, 'default', 0);
                Mage::app()->getCacheInstance()->cleanType("config");
                Mage::getSingleton('admin/session')->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());

                $optionsUrl = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/veplatform_options");

                $response = Mage::app()->getResponse();

                $response->setRedirect($optionsUrl)->sendResponse();
                exit();
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }
    }
}