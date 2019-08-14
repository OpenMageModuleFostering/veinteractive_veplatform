<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL) version 3
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/
 *
 * @author    Ve Interactive <info@veinteractive.com>
 * @copyright 2016 Ve Interactive
 * @license   http://www.gnu.org/licenses/ GNU General Public License (GPL) version 3
 */
class ExceptionHandler
{
    public $error = array();
    public $level = 'ERROR';

    private $telemetryClient;
    private $eCommerceName = 'Magento';
    private $moduleVersion = '16.3.5.0';
    private $phpVersion;
    private $eCommerceVersion;
    private $file = 'veplatform.log';

    public function __construct()
    {
        $filePath = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
        if (file_exists($filePath)) {
            require_once $filePath;
        }

        try {
            $this->phpVersion = phpversion();
            $this->eCommerceVersion = Mage::getVersion();
            if ($this->validate()) {
                $filePath = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
                if (file_exists($filePath) && class_exists('\ApplicationInsights\Telemetry_Client')) {
                    require_once $filePath;

                    $this->telemetryClient = new \ApplicationInsights\Telemetry_Client();
                    $this->telemetryClient->getContext()->setInstrumentationKey('7bab193b-a2ac-42a5-a07e-84943b8d2279');
                } else {
                    $this->error['error'] = 'Ve module could not be installed - Composer files could not be loaded';
                    Mage::log($this->error['error'], $this->level, $this->file);
                }
            } else {
                Mage::log($this->error['error'], $this->level, $this->file);
            }
        } catch (Exception $exception) {
            Mage::log($exception, $this->level, $this->file);
        }
    }

    public function validate()
    {
        if (version_compare($this->phpVersion, '5.5.0', '<=')) {
            $this->error['error'] = 'Ve module could not be installed - PHP version needs to be at least 5.5';
        } else if (!$this->checkCurlExtension() || !$this->checkGdExtension()) {
            $this->error['error'] = 'Ve module could not be installed - CURL or Gd extensions are missing';
        }

        return empty($this->error);
    }

    public function checkCurlExtension()
    {
        return extension_loaded('curl') && function_exists('curl_init') && function_exists('curl_reset');
    }

    public function checkGdExtension()
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    private function getContainerInfo($level)
    {
        return array(
            'Level' => $level,
            'Shop' => $this->eCommerceName,
            'Version' => $this->eCommerceVersion,
            'URL' => $this->getCurrentUrl(),
            'PHPVersion' => $this->phpVersion,
            'ModuleVersion' => $this->moduleVersion
        );
    }

    public function logMessage($message, $level = 'INFO')
    {
        try {
            if (isset($this->telemetryClient) && isset($message)) {
                $this->telemetryClient->trackMessage($message, $this->getContainerInfo($level));
                $this->telemetryClient->flush();
            }
        } catch (Exception $ex) {
            Mage::log($message, $level, $this->file);
        }

    }

    public function logException(\Exception $exception, $level = 'ERROR')
    {
        try {
            if (isset($this->telemetryClient) && isset($exception)) {
                $this->telemetryClient->trackException($exception, $this->getContainerInfo($level));
                $this->telemetryClient->flush();
            }
        } catch (Exception $exception) {
            Mage::log($exception, $level, $this->file);
        }
    }

    private function getCurrentUrl()
    {
        $url = Mage::helper('core/url')->getCurrentUrl();
        return $url;
    }
}
