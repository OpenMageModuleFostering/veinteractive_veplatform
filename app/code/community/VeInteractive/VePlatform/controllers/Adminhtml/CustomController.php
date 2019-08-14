<?php

class VeInteractive_VePlatform_Adminhtml_CustomController extends Mage_Adminhtml_Controller_Action {

  public function installVePlatformAction() {
    $params = $this->getDataAction();
    $helper = Mage::helper('VeInteractive_VePlatform');
    $config = Mage::getConfig();

    $jsonResponse = $helper->install($params);
    if($jsonResponse) {
      $response = json_decode($jsonResponse);
      if($response && isset($response->HtmlView)) {

        //if we received the tag, token, pixel - we save them in db
        if(isset($response->URLTag) && $response->URLTag != ""
          && isset($response->URLPixel) && $response->URLPixel != ""
          && isset($response->Token) && $response->Token != ""
        ) {
          $config->saveConfig(VeInteractive_VePlatform_Helper_Data::TOKEN, $response->Token, 'default', 0);
          $config->saveConfig(VeInteractive_VePlatform_Helper_Data::TAG_URL, $response->URLTag, 'default', 0);
          $config->saveConfig(VeInteractive_VePlatform_Helper_Data::PIXEL_URL, $response->URLPixel, 'default', 0);
        }

        $config->saveConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL, 0, 'default', 0);

        Mage::dispatchEvent('adminhtml_cache_flush_all');
        Mage::app()->getCacheInstance()->flush();

        echo json_encode($response);
      } else {
        $this->showError();
      }
    } else {
      $this->showError();
    }

  }

  private function showError() {
    $optionsUrl = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/veplatform_options");
    $message = Mage::helper('adminhtml')->__('Ve - Oops! An error has occured. <a href="%s">Please try again!</a>', $optionsUrl);
    Mage::getSingleton('adminhtml/session')->addWarning($message);

    $response = array(
      'redirectUrl' => Mage::helper('adminhtml')->getUrl('/')
    );

    echo json_encode($response);
  }

private function getDataAction() {
  $helper = Mage::helper('VeInteractive_VePlatform');
  $firstInstall = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL);

  // get data to send
  $data = array(
      'ecommerce' => $helper->getPlatformName(),
      'domain' => $helper->getBaseUrl(),
      'language' => $helper->getLang(),
      'email' => Mage::getSingleton('admin/session')->getUser()->getEmail(),
      'phone' => Mage::getStoreConfig('general/store_information/phone'),
      'merchant' => Mage::app()->getWebsite()->getName(),
      'country' => Mage::getStoreConfig('general/country/default'),
      'currency' => Mage::app()->getBaseCurrencyCode(),
      'isInstallFLow' => ($firstInstall == 1) ? 'true' : 'false'
  );

  return $data;
}

}