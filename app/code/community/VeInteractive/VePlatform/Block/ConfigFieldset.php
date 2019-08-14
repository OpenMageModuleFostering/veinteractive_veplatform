<?php

class VeInteractive_VePlatform_Block_ConfigFieldset
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    const MY_SECTION = "veplatform_options";
    const MY_PACKAGE = "veplatform";
    const MY_THEME = "default";
    const PUBLIC_DESIGN_AREA = "skin";
    const IMAGES_DIR = "images";
    const FONTS_DIR = "fonts";
    const CSS_DIR = "css";
    const SCRIPTS_DIR = "scripts";
    const HEADER_TEMPLATE_PATH = "systemconfig/%s/formfieldset/header.phtml";
    const FOOTER_TEMPLATE_PATH = "systemconfig/%s/formfieldset/footer.phtml";

    private $on_my_section;

    public function getMyImagesUrl($image)
    {
        return $this->getSkinUrl() . self::IMAGES_DIR . DS . $image;
    }

    public function getMyFontsUrl($font)
    {
        return $this->getSkinUrl() . self::FONTS_DIR . DS . $font;
    }

    public function getMyCssUrl($css)
    {
        return $this->getSkinUrl() . self::CSS_DIR . DS . $css;
    }

    public function getMyScriptsUrl($script)
    {
        return $this->getSkinUrl() . self::SCRIPTS_DIR . DS . $script;
    }

    protected function _construct()
    {
        parent::_construct();
        $section = $this->getAction()->getRequest()->getParam('section', false);
        $this->on_my_section = ($section === self::MY_SECTION);
    }

    /* To use this function remove fieldset from template.
    protected function _getHeaderHtml( $element ) {

        $header = parent::_getHeaderHtml( $element );

        if( $this->on_my_section ) {

            $header_without_table = $this->removeTable( $header, true );
            if( $header_without_table !== false ) {
                $this->found_table = true;
                $this->setData( "parent_html_id", $element->getHtmlId() );
                $template_path = sprintf( self::HEADER_TEMPLATE_PATH, $element->getHtmlId() );
                $header = $header_without_table. $this->generateHtml( $template_path );
            }

        }

        return $header;

    }
    */

    protected function _getHeaderHtml($element)
    {
        if (!$this->on_my_section) {
            $header = parent::_getHeaderHtml($element);
        } else {
            $this->setData("parent_html_id", $element->getHtmlId());
            $template_path = sprintf(self::HEADER_TEMPLATE_PATH, $element->getHtmlId());
            $header = $this->generateHtml($template_path);
        }
        return $header;
    }

    /* To use this function remove fieldset from template.
    protected function _getFooterHtml( $element ) {

        $footer = parent::_getFooterHtml( $element );

        if( $this->on_my_section && $this->found_table ) {

            $footer_without_table = $this->removeTable( $footer, false );
            if( $footer_without_table !== false ) {
                $template_path = sprintf( self::FOOTER_TEMPLATE_PATH, $element->getHtmlId() );
                $footer = $footer_without_table. $this->generateHtml( $template_path );
            }

        }

        return $footer;
    }
    */

    protected function _getFooterHtml($element)
    {
        if (!$this->on_my_section) {
            $footer = parent::_getFooterHtml($element);
        } else {
            $template_path = sprintf(self::FOOTER_TEMPLATE_PATH, $element->getHtmlId());
            $footer = $this->generateHtml($template_path);
        }
        return $footer;
    }

    private function generateHtml($template_path)
    {
        $this->setTemplate($template_path);

        // Change Theme only for this Block.
        $design = Mage::getDesign();
        $package_name = $design->getPackageName();
        $theme_name = $design->getTheme(self::PUBLIC_DESIGN_AREA);
        $design->setPackageName(self::MY_PACKAGE)->setTheme(self::MY_THEME);

        $html = $this->toHtml();

        // Restore Theme.
        $design->setPackageName($package_name)->setTheme($theme_name);

        return $html;

    }
}