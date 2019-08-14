<?php
$filePath = dirname(dirname(__FILE__)) . '/Helper/ExceptionHandler.php';
if (file_exists($filePath)) {
    require_once $filePath;
}

class VeInteractive_VePlatform_Helper_Data extends Mage_Core_Helper_Abstract
{

    const TAG_URL = 'veplatform/journey/tag';
    const PIXEL_URL = 'veplatform/journey/pixel';
    const TOKEN = 'veplatform/journey/token';
    const MODULE_INSTALLED = 'veplatform/adminhtml/module_installed';
    const FLOW_TYPE = 'veplatform/adminhtml/flow_type';
    const FIRST_INSTALL = 'veplatform/adminhtml/first_install';
    const ECOMMERCE_NAME = 'Magento';
    const VEDATAACTIVE = 'veplatform/journey/vedataactive';

    private $baseUrl;
    private $exceptionHandler;

    public function __construct()
    {
        $this->baseUrl = Mage::getConfig()->getNode('default/veplatform/service/url');
        $this->exceptionHandler = new ExceptionHandler();
    }

    private function httpPost($url, $parameters)
    {
        $this->exceptionHandler->logMessage("BEGIN[httpPost] - Send(" . $url . ") = " . var_export($parameters, true), 'INFO');

        $result = false;

        try {
            $client = new Varien_Http_Client($url);
            $client->setMethod(Varien_Http_Client::POST);
            $client->setConfig(array(
                'timeout' => 25,
            ));

            $client->setRawData(json_encode($parameters), "application/json;charset=UTF-8");

            $response = $client->request();
            $result = $response->getBody();
            $this->exceptionHandler->logMessage("END[httpPost] - Receive = Code" . var_export($result, true), 'INFO');

        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $result;
    }

    public function install($data)
    {
        return $this->httpPost($this->baseUrl . '/api/veconnect/install', $data);
    }

    public function getLang()
    {
        $lang = Mage::getStoreConfig('general/locale/code');
        if(isset($lang)) {
            $subDashPositionInLang = strpos($lang, '_');
            if ($subDashPositionInLang > 0) {
                $lang = substr($lang, 0, $subDashPositionInLang);
            }
        }
        return $lang;
    }

    public function getBaseUrl()
    {
        return preg_replace("(^https?://)", "", Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false));
    }

    public function getPlatformName()
    {
        return self::ECOMMERCE_NAME;
    }
}
