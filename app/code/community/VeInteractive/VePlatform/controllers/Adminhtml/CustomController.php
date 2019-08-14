<?php
$filePath = dirname(dirname(dirname(__FILE__))) . '/Helper/ExceptionHandler.php';
if (file_exists($filePath)) {
    require_once $filePath;
}

class VeInteractive_VePlatform_Adminhtml_CustomController extends Mage_Adminhtml_Controller_Action
{
    private $helper;
    private $exceptionHandler;

    public function installVePlatformAction()
    {
        $this->exceptionHandler = new ExceptionHandler();

        if ($this->exceptionHandler->validate()) {
            $this->helper = Mage::helper('VeInteractive_VePlatform');
            $params = $this->getDataAction();
            $config = Mage::getConfig();

            $jsonResponse = $this->helper->install($params);
            if ($jsonResponse) {
                $response = json_decode($jsonResponse);
                if ($response && isset($response->HtmlView)) {

                    //if we received the tag, token, pixel - we save them in db
                    if (isset($response->URLTag) && $response->URLTag != ""
                        && isset($response->URLPixel) && $response->URLPixel != ""
                        && isset($response->Token) && $response->Token != ""
                    ) {

                        $modulesInstalled = Mage::getConfig()->getNode('modules')->children();
                        $modulesInstalled = json_encode($modulesInstalled);
                        $this->exceptionHandler->logMessage('Modules installed '. print_r($modulesInstalled, true), 'INFO');

                        $config->saveConfig(VeInteractive_VePlatform_Helper_Data::TOKEN, $response->Token, 'default', 0);
                        $config->saveConfig(VeInteractive_VePlatform_Helper_Data::TAG_URL, $response->URLTag, 'default', 0);
                        $config->saveConfig(VeInteractive_VePlatform_Helper_Data::PIXEL_URL, $response->URLPixel, 'default', 0);

                        if ( isset($response->VeDataActive) && $response->VeDataActive != "") {
                            $config->saveConfig(VeInteractive_VePlatform_Helper_Data::VEDATAACTIVE, $response->VeDataActive, 'default', 0);
                        }
                    }

                    $config->saveConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL, 0, 'default', 0);

                    Mage::dispatchEvent('adminhtml_cache_flush_all');
                    Mage::app()->getCacheInstance()->flush();

                    echo json_encode($response);
                } else {
                    $this->showError();
                    $this->exceptionHandler->logMessage('Ve module was not activated. Response from WS after install call: ' . print_r($response, true), $this->exceptionHandler->level);
                }
            } else {
                $this->showError();
                $this->exceptionHandler->logMessage('Ve module was not activated. Install call to WS failed: ' . print_r($jsonResponse, true), $this->exceptionHandler->level);
            }
        } else {
            $this->showError(true);
            $this->exceptionHandler->logMessage('Ve module was not activated. ' . print_r($this->exceptionHandler->error['error'], true), $this->exceptionHandler->level);
        }

    }

    private function showError($specialMessage = false)
    {
        $optionsUrl = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/veplatform_options");
        if ($specialMessage) {
            $message = Mage::helper('adminhtml')->__($this->exceptionHandler->error['error'], $optionsUrl);
        } else {
            $message = Mage::helper('adminhtml')->__('Ve - Oops! An error has occured. <a href="%s">Please try again!</a>', $optionsUrl);
        }
        Mage::getSingleton('adminhtml/session')->addWarning($message);

        $response = array(
            'redirectUrl' => Mage::helper('adminhtml')->getUrl('/')
        );

        echo json_encode($response);
    }

    private function getDataAction()
    {
        $firstInstall = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::FIRST_INSTALL);

        // get data to send
        $data = array(
            'ecommerce' => $this->helper->getPlatformName(),
            'domain' => $this->helper->getBaseUrl(),
            'language' => $this->helper->getLang(),
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
