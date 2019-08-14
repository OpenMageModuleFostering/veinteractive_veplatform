<?php

class VeInteractive_VePlatform_Observer_ThanksPage
{

    public function showPixel()
    {
        $journeyPixel = Mage::getStoreConfig(VeInteractive_VePlatform_Helper_Data::PIXEL_URL);
        if ($journeyPixel) {
            Mage::getSingleton('core/session')->setShowPixel(true);
        }
    }
}
