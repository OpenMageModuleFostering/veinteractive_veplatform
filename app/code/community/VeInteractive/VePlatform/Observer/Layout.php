<?php

class VeInteractive_VePlatform_Observer_Layout
{

    private $blocks;

    public function __construct()
    {
        $this->blocks = array(
            array(
                'block' => 'veplatform/vetag',
                'name' => 'veplatform.vetag',
                'parent' => 'before_body_end'
            ),
            array(
                'block' => 'veplatform/vepixel',
                'name' => 'veplatform.vepixel',
                'parent' => 'content'
            ),
            array(
                'block' => 'veplatform/vemaster',
                'name' => 'veplatform.vemaster',
                'parent' => 'content'
            )
        );
    }

    public function updateLayout(Varien_Event_Observer $observer)
    {
        // Only for FrontEnd.
        if ($observer->getLayout()->getArea() === 'frontend') {

            $layout = $observer->getLayout();
            if ($layout) {

                foreach ($this->blocks as $setting) {
                    // Avoid adding the block twice.

                    if (!$layout->getBlock($setting['block'])) {

                        $block = $layout->createBlock($setting['block'], $setting['name']);

                        if ($block) {
                            $parent = $layout->getBlock($setting['parent']);
                            if ($parent) {
                                $parent->append($block);
                            }
                        }
                    }
                }

            }

        }
    }
}
