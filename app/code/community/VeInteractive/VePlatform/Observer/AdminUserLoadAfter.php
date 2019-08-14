<?php

class VeInteractive_VePlatform_Observer_AdminUserLoadAfter {

  public function checkInstallation($observer) {
    $isAjax = Mage::app()->getRequest()->isXmlHttpRequest();
    if(!$isAjax) {
      $this->checkModuleInstallation();
    }
  }

  private function checkModuleInstallation() {
    $module_installed = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::MODULE_INSTALLED);

    if(!$module_installed) {
      Mage::getConfig()->saveConfig(VeInteractive_VePlatform_Helper_Data::MODULE_INSTALLED, true);
      Mage::getConfig()->saveConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL, 1, 'default', 0);
      Mage::app()->getCacheInstance()->cleanType("config");
      Mage::getSingleton('admin/session')->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());

      $optionsUrl = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/veplatform_options");

      $response = Mage::app()->getResponse();

      $response->setRedirect($optionsUrl)->sendResponse();
      exit();
    }
  }
}