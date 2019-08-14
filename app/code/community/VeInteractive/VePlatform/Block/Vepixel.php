<?php

class VeInteractive_VePlatform_Block_Vepixel extends VeInteractive_VePlatform_Block_Template
{

    public function showPixel()
    {
        $show = Mage::getSingleton('core/session')->getShowPixel();
        if ($show) {
            Mage::getSingleton('core/session')->unsShowPixel();
        }

        return $show;
    }

    public function journeyPixel()
    {
        $journeyPixel = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::PIXEL_URL);

        return $journeyPixel;
    }

    public function _toHtml()
    {
        return $this->renderLocalTemplate('vepixel');
    }
}