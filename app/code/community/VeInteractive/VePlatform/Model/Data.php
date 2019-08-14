<?php

class VeInteractive_VePlatform_Model_Data extends Mage_Core_Model_Abstract
{

    private $locale;

    /**
     * Get information related to date format for masterdata
     */
    public function getCultureInformation()
    {
        $dateFormat = $this->locale->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG);
        return array('dateFormatFull' => $dateFormat);
    }

    /**
     * Get language related information for masterdata
     */
    public function getLanguage()
    {
        if (empty($this->locale)) {
            $this->locale = Mage::app()->getLocale();
        }

        $languageCode = $this->locale->getLocaleCode();

        $language = array(
            'isoCode' => null,
            'languageCode' => $languageCode,
            'name' => $this->getLanguageName($languageCode)
        );

        return $language;
    }

    /**
     * Find language name, based on locale's code
     */
    private function getLanguageName($code)
    {
        if (empty($code)) {
            return null;
        }

        $optionLocales = $this->locale->getOptionLocales();
        foreach ($optionLocales as $oL) {
            if (strtolower($oL['value']) == strtolower($code)) {
                return $oL['label'];
            }
        }

        return null;
    }

    /**
     * Get currency related information for masterdata
     */
    public function getCurrency()
    {
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $currencyObj = Mage::app()->getLocale()->currency($currency_code);

        $currency = array(
            'isoCode' => $currency_code,
            'isoCodeNum' => null,
            'name' => $currencyObj->getName(),
            'sign' => $currencyObj->getSymbol()
        );
        return $currency;

    }

    /**
     * Get currentPage URL, orderId and product information (if available)
     * Store in session last visited category and product history
     */
    public function getCurrentPage()
    {
        $orderId = Mage::app()->getFrontController()->getRequest()->getActionName() === 'success' ? Mage::getModel('checkout/session')->getData('last_real_order_id') : 0;
        $module = Mage::app()->getFrontController()->getRequest()->getModuleName();
        $productId = 0;

        //Product page
        if ($module === 'catalog' && Mage::registry('current_product')) {
            $productId = Mage::registry('current_product')->getId();
            $latestViewedProducts = Mage::getSingleton('core/session')->getUserViewedProducts();
            if (!empty($latestViewedProducts)) {
                $latestViewedProducts .= ',' . $productId;
                Mage::getSingleton('core/session')->setUserViewedProducts($latestViewedProducts);
            } else {
                Mage::getSingleton('core/session')->setUserViewedProducts($productId);
            }
            Mage::getSingleton('core/session')->setHistory(true);
        }

        // Category page
        if ($module === 'catalog' && Mage::registry('current_category')) {
            // check if the category is a root category to avoid it
            if (Mage::getModel('catalog/layer')->getCurrentCategory()->getLevel() !== "1") {

                $catName = Mage::getSingleton('core/session')->getLastVisitedCategoryName();
                $catLink = Mage::getSingleton('core/session')->getLastVisitedCategoryLink();

                $currentCategoryName = Mage::getModel('catalog/layer')->getCurrentCategory()->getName();
                $currentCategoryLink = Mage::getModel('catalog/layer')->getCurrentCategory()->getUrl();

                if(!empty($catName) && !empty($catLink)) {
                    $catName .= ',' . $currentCategoryName;
                    $catLink .= ',' . $currentCategoryLink;
                    Mage::getSingleton('core/session')->setLastVisitedCategoryName($catName);
                    Mage::getSingleton('core/session')->setLastVisitedCategoryLink($catLink);
                }else{
                    Mage::getSingleton('core/session')->setLastVisitedCategoryName($currentCategoryName);
                    Mage::getSingleton('core/session')->setLastVisitedCategoryLink($currentCategoryLink);
                }
                Mage::getSingleton('core/session')->setHistory(true);
            }
        }

        $current = array(
            'currentUrl' => Mage::helper('core/url')->getCurrentUrl(),
            'orderId' => $orderId,
            'currentPageType' => $this->getPageType(),
            'product' => $this->getProductInformation($productId)
        );

        return $current;
    }

    /**
     * Get currentPage type
     */
    public function getPageType()
    {
        $controller = Mage::app()->getRequest()->getControllerName();
        $action = Mage::app()->getRequest()->getActionName();

        $type = null;
        switch ($controller) {
            case 'index':
                $type = 'home';
                break;
            case 'account':
                if ($action == 'login') {
                    $type = 'login';
                } else if ($action == 'create') {
                    $type = 'register';
                }
                break;
            case 'product':
                $type = 'product';
                break;
            case 'cart':
                $type = 'basket';
                break;
            case 'category':
                $type = 'category';
                break;
            case 'onepage':
                if ($action != 'success') {
                    $type = 'checkout';
                } else {
                    $type = 'complete';
                }
                break;
            case 'multishipping':
                if ($action != 'success') {
                    $type = 'checkout';
                } else {
                    $type = 'complete';
                }
                break;
            default:
                $type = 'other';
        }

        return $type;
    }

    /**
     * Get current order, all cart information
     */
    public function getCurrentOrder()
    {
        $taxes = array(
            'name' => null,
            'taxValue' => null
        );

        $promocode = array(
            'name' => null,
            'type' => null,
            'value' => null
        );

        $cart = array(
            'dateUpd' => null,
            'promocode' => $promocode,
            'totalPromocodeDiscount' => Mage::helper('core')->currency(0, true, false),
            'totalPrice' => Mage::helper('core')->currency(0, true, false),
            'totalProducts' => Mage::helper('core')->currency(0, true, false),
            'products' => array(),
            'taxes' => $taxes
        );

        $order = Mage::getModel('checkout/session')->getQuote();
        if ($order !== null) {
            $dateUpd = $order->getUpdatedAt();
            if ($order->hasItems()) {
                $products = array();
                $i = 0;
                foreach ($order->getAllVisibleItems() as $item) {
                    $products[$i] = $this->getProductInformation($item->getProductId());
                    $products[$i]['qty'] = $item->getQty();
                    $i++;
                }

                $grandTotal = $order->getGrandTotal();
                $subtotal = $order->getSubtotal();

                if ($order->isVirtual()) {
                    $totalTax = array(
                        'name' => 'Total Tax',
                        'taxValue' => Mage::helper('core')->currency($order->getBillingAddress()->getData('tax_amount'), true, false)
                    );
                    $discountAmount = $order->getBillingAddress()->getData('discount_amount');
                } else {
                    $totalTax = array(
                        'name' => 'Total Tax',
                        'taxValue' => Mage::helper('core')->currency($order->getShippingAddress()->getData('tax_amount'), true, false)
                    );
                    $discountAmount = $order->getShippingAddress()->getData('discount_amount');
                }

                $tax[] = $totalTax;
            }

            $cart['dateUpd'] = $dateUpd;
            $cart['totalPrice'] = Mage::helper('core')->currency($grandTotal, true, false);
            $cart['totalProducts'] = Mage::helper('core')->currency($subtotal, true, false);
            $cart['products'] = $products;
            $cart['taxes'] = $tax;
            $cart['promocode'] = $this->getPromocode($order);
            $cart['totalPromocodeDiscount'] = Mage::helper('core')->currency(abs($discountAmount), true, false);
        }

        return $cart;
    }


    /**
     * Get promocodes applied for the current order
     */
    public function getPromocode($order)
    {
        $promocode = array();

        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        foreach ($ruleIds as $ruleId) {
            $rule = Mage::getModel('salesrule/rule');
            $rule->load($ruleId);
            if ($rule->getId()) {
                $type = $rule->getData('simple_action');

                $currentPromocode = array(
                    'name' => $rule->getData('name'),
                    'type' => $type,
                    'value' => $rule->getData('discount_amount')
                );

                $promocode[] = $currentPromocode;
            }
        }

        return !empty($promocode) ? $promocode :
            array(
                'name' => null,
                'type' => null,
                'value' => null
            );
    }

    /**
     * Build product structure used used in currentPage and cart
     */
    public function getProductInformation($id)
    {
        $prod = array(
            'productId' => null,
            'description' => null,
            'description_short' => null,
            'images' => array(
                'fullImagePath' => null,
                'partialImagePath' => null,
            ),
            'manufacturerName' => null,
            'name' => null,
            'priceCurrent' => null,
            'priceDiscount' => null,
            'priceWithoutDiscount' => null,
            'productLink' => null
        );

        if (empty($id)) {
            return $prod;
        }

        $product = Mage::getModel('catalog/product');
        $product->load((int)$id);

        $imgFullPath = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
        $imgPartialPath = Mage::getModel('catalog/product_media_config')->getMediaShortUrl($product->getImage());

        $finalPrice = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true);
        $originalPrice = Mage::helper('tax')->getPrice($product, $product->getPrice(), true);
        $prod = array(
            'productId' => $id,
            'description' => $product->getData('description'),
            'description_short' => $product->getData('short_description'),
            'images' => array(
                'fullImagePath' => $imgFullPath,
                'partialImagePath' => $imgPartialPath,
            ),
            'manufacturerName' => null,
            'name' => $product->getName(),
            'priceCurrent' => Mage::helper('core')->currency($finalPrice, true, false),
            'priceDiscount' => Mage::helper('core')->currency(($originalPrice - $finalPrice), true, false),
            'priceWithoutDiscount' => Mage::helper('core')->currency($originalPrice, true, false),
            'productLink' => $product->getProductUrl(true)
        );

        return $prod;
    }

    /**
     * Get user history - products history and last visited category
     */
    public function getUserHistory()
    {   $history = array();
        $hasHistory = Mage::getSingleton('core/session')->getHistory();
        if (!$hasHistory) {
            $history['productHistory'] = array();
            $history['lastVisitedCategory'] = array(
                'name' => null,
                'link' => null
            );
        } else {
            $latestViewedProducts = Mage::getSingleton('core/session')->getUserViewedProducts();

            $categoryName = Mage::getSingleton('core/session')->getLastVisitedCategoryName();
            $categoryLink = Mage::getSingleton('core/session')->getLastVisitedCategoryLink();
            if (!empty($latestViewedProducts)) {
                $productHistory = array();
                $productList = array_unique(explode(',', $latestViewedProducts));
                foreach ($productList as $productId) {
                    $productHistory[] = $this->getProductInformation($productId);
                }
                $history['productHistory'] = $productHistory;
            }
            if (!empty($categoryName) && !empty($categoryLink)) {
                $catNameList = explode(',', $categoryName);
                $catLinkList = explode(',', $categoryLink);

                if(sizeof($catNameList) > 1) {
                    $length = sizeof($catNameList);
                    $history['lastVisitedCategory'] = array(
                        'name' => $catNameList[$length-2],
                        'link' => $catLinkList[$length-2]
                    );
                }
            }else{
                $history['lastVisitedCategory'] = array(
                    'name' => null,
                    'link' => null
                );
            }
        }
        return $history;
    }

    /**
     * Get customer info - email, firstName, lastName - if the user is logged in
     */
    public function getCustomer()
    {
        $customerInfo = array(
            'email' => null,
            'firstName' => null,
            'lastName' => null
        );

        $customer = Mage::getModel('customer/session');
        if ($customer->isLoggedIn()) {
            $customer = $customer->getCustomer();
            $customerInfo = array(
                'email' => $customer->getEmail(),
                'firstName' => $customer->getFirstname() . (!empty($customer->getMiddlename()) ? ' ' . $customer->getMiddlename() : ''),
                'lastName' => $customer->getLastname()
            );
        }

        return $customerInfo;
    }
}
