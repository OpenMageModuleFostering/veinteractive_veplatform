<?php

class VeInteractive_VePlatform_Block_Template extends Mage_Core_Block_Template {

  public function __construct() {
    parent::__construct();
  }

  public function renderLocalTemplate($template_name, $params = array()) {
    $template_path = Mage::getModuleDir('', $this->getModuleName()) . DS . 'templates' . DS . $template_name . '.phtml';
    
    ob_start();
    require $template_path;
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }
}
