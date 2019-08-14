<?php

class VeInteractive_VePlatform_Block_Vetag extends VeInteractive_VePlatform_Block_Template
{

    public function journeyTag()
    {
        $journeyTag = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::TAG_URL);

        return $journeyTag;
    }

    public function _toHtml()
    {
        return $this->renderLocalTemplate('vetag');
    }
}