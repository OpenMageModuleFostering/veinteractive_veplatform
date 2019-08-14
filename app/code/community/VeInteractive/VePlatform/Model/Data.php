<?php

class VeInteractive_VePlatform_Model_Data extends Mage_Core_Model_Abstract
{

    private $locale;
    private $store;
    private $request;
    private $coreSession;
    private $catalogCategory;
    private $catalogLayer;
    private $checkoutSession;
    private $coreHelper;
    private $taxHelper;
    private $currentProductRegistry;
    private $currentCategoryRegistry;
    private $customerSession;
    private $coreUrlHelper;
    private $checkoutHelper;
    private $directoryCurrency;
    private $rule;
    private $catalogMedia;
    private $frontController;

    private $exceptionHandler;
    private $storeDateTimeFormat;

    public function __construct($parameters)
    {
        $filePath = dirname(dirname(__FILE__)) . '/Helper/ExceptionHandler.php';
        if (file_exists($filePath)) {
            require_once $filePath;
        }

        $this->locale = $parameters['locale'];
        $this->store = $parameters['store'];
        $this->request = $parameters['request'];
        $this->coreSession = $parameters['coreSession'];
        $this->catalogCategory = $parameters['catalogCategory'];
        $this->catalogLayer = $parameters['catalogLayer'];
        $this->checkoutSession = $parameters['checkoutSession'];
        $this->coreHelper = $parameters['coreHelper'];
        $this->taxHelper = $parameters['taxHelper'];
        $this->currentProductRegistry = $parameters['currentProductRegistry'];
        $this->currentCategoryRegistry = $parameters['currentCategoryRegistry'];
        $this->customerSession = $parameters['customerSession'];
        $this->coreUrlHelper = $parameters['coreUrlHelper'];
        $this->checkoutHelper = $parameters['checkoutHelper'];
        $this->directoryCurrency = $parameters['directoryCurrency'];
        $this->rule = $parameters['rule'];
        $this->catalogMedia = $parameters['catalogMedia'];
        $this->frontController = $parameters['frontController'];
        $this->exceptionHandler = new ExceptionHandler();
        $this->storeDateTimeFormat = Mage_Core_Model_Locale::FORMAT_TYPE_LONG;

    }

    /**
     * Get information related to date format for masterdata
     */
    public function getCultureInformation()
    {
        try {
            $dateFormat = $this->locale->getDateTimeFormat($this->storeDateTimeFormat);
            $cultureInfo = array('dateFormatFull' => $dateFormat);

            return $cultureInfo;
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);

            $cultureInfo = array('dateFormatFull' => null);
        }
        return $cultureInfo;
    }

    /**
     * Get language related information for masterdata
     */
    public function getLanguage()
    {
        try {
            $languageCode = $this->locale->getLocaleCode();

            $language = array(
                'isoCode' => null,
                'languageCode' => $languageCode,
                'name' => $this->getLanguageName($languageCode)
            );
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);

            $language = array(
                'isoCode' => null,
                'languageCode' => null,
                'name' => null
            );
        }
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

        try {
            $optionLocales = $this->locale->getOptionLocales();
            foreach ($optionLocales as $oL) {
                if (strtolower($oL['value']) == strtolower($code)) {
                    return $oL['label'];
                }
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
            return null;
        }

    }

    /**
     * Get currency related information for masterdata
     */
    public function getCurrency()
    {
        try {
            $currencyCode = $this->store->getCurrentCurrencyCode();
            $currencyObj = $this->locale->currency($currencyCode);
            $currency = array(
                'isoCode' => $currencyCode,
                'isoCodeNum' => null,
                'name' => $currencyObj->getName(),
                'sign' => $currencyObj->getSymbol()
            );
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);

            $currency = array(
                'isoCode' => null,
                'isoCodeNum' => null,
                'name' => null,
                'sign' => null
            );
        }

        return $currency;
    }

    /**
     * Get currentPage URL, orderId and product information (if available)
     * Store in session last visited category and product history
     */
    public function getCurrentPage()
    {
        try {
            $orderId = $this->request->getActionName() === 'success' ? $this->checkoutSession->getData('last_real_order_id') : null;
            $module = $this->request->getModuleName();

            $productId = 0;

            //Product page
            if ($module === 'catalog' && $this->currentProductRegistry) {
                $productId = $this->currentProductRegistry->getId();
                $latestViewedProducts = $this->coreSession->getUserViewedProducts();
                if (!empty($latestViewedProducts)) {
                    $latestViewedProducts .= ',' . $productId;
                    $this->coreSession->setUserViewedProducts($latestViewedProducts);
                } else {
                    $this->coreSession->setUserViewedProducts($productId);
                }
                $this->coreSession->setHistory(true);
            }

            // Category page
            if ($module === 'catalog' && $this->currentCategoryRegistry) {
                // check if the category is a root category to avoid it
                if ($this->catalogLayer->getCurrentCategory()->getLevel() !== "1") {

                    $catName = $this->coreSession->getLastVisitedCategoryName();
                    $catLink = $this->coreSession->getLastVisitedCategoryLink();

                    $currentCategoryName = $this->catalogLayer->getCurrentCategory()->getName();
                    $currentCategoryLink = $this->catalogLayer->getCurrentCategory()->getUrl();

                    if (!empty($catName) && !empty($catLink)) {
                        $catName .= ',' . $currentCategoryName;
                        $catLink .= ',' . $currentCategoryLink;
                        $this->coreSession->setLastVisitedCategoryName($catName);
                        $this->coreSession->setLastVisitedCategoryLink($catLink);
                    } else {
                        $this->coreSession->setLastVisitedCategoryName($currentCategoryName);
                        $this->coreSession->setLastVisitedCategoryLink($currentCategoryLink);
                    }
                    $this->coreSession->setHistory(true);
                }
            }

            $current = array(
                'currentUrl' => $this->coreUrlHelper->getCurrentUrl(),
                'orderId' => $orderId,
                'currentPageType' => $this->getPageType(),
                'product' => $this->getProductInformation($productId)
            );
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);

            $current = array(
                'currentUrl' => null,
                'orderId' => null,
                'currentPageType' => null,
                'product' => $this->getDefaultProduct()
            );
        }

        return $current;
    }

    /**
     * Get currentPage type
     */
    public function getPageType()
    {
        $type = null;
        try {
            $controller = $this->request->getControllerName();
            $action = $this->request->getActionName();
            $actionFullName = $this->frontController->getAction()->getFullActionName();

            switch ($controller) {
                case 'index':
                    if ($actionFullName == 'cms_index_index') {
                        $type = 'home';
                    } else {
                        $type = 'other';
                    }
                    break;
                case 'account':
                    if ($action == 'login') {
                        $type = 'login';
                    } else if ($action == 'create') {
                        $type = 'register';
                    } else {
                        $type = 'other';
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

        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $type;
    }

    /**
     * @return array
     */
    private function getDefaultCart()
    {
        return array(
            'dateUpd' => null,
            'totalPrice' => null,
            'totalProducts' => null,
            'products' => $this->getDefaultProduct(),
            'taxes' => array(),
            'promocode' => $this->getDefaultPromocode(),
            'totalPromocodeDiscount' => null
        );
    }

    /**
     * Get current order, all cart information
     */
    public function getCurrentOrder()
    {
        $cart = $this->getDefaultCart();
        try {
            if ($this->getPageType() == 'complete') {
                return null;
            }

            $order = $this->checkoutSession->getQuote();

            $products = array();
            $tax = array();
            $discountAmount = 0;
            $totalPrice = 0;
            $totalProducts = 0;
            $orderTotals = null;

            if (isset($order)) {
                $orderData = $order->getData();
                $dateUpd = array_key_exists('updated_at', $orderData) ? $orderData['updated_at'] : null;
                if ($order->hasItems()) {
                    $i = 0;
                    foreach ($order->getAllVisibleItems() as $item) {
                        $prodId = $item->getProductId();
                        $products[$i] = $this->getProductInformation($prodId);
                        $products[$i]['quantity'] = $item->getQty();
                        $products[$i]['productId'] = $item->getSku();

                        $prodObj = Mage::getModel('catalog/product')->load((int)$prodId);
                        if (empty($prodObj)) {
                            $this->exceptionHandler->logMessage('Product information is missing for id=' . print_r($prodId, true), 'WARNING');
                        }
                        $finalPrice = $this->taxHelper->getPrice($prodObj, $prodObj->getFinalPrice(), true);
                        $products[$i]['productSubTotal'] = $this->coreHelper->currency($products[$i]['quantity'] * (float)$finalPrice, true, false);
                        $i++;
                    }

                    $orderTotals = $order->getTotals();
                    $rate = $order->getData('base_to_quote_rate');
                    $discountAmount = isset($orderTotals['discount']) ? $orderTotals['discount']->getValue() / $rate : 0;
                    $totalPrice = $orderTotals['grand_total']->getValue() / $rate;
                    $shippingExclTaxes = 0;
                    if(isset($orderTotals['shipping'])){
                        $shippingExclTaxes = $orderTotals['shipping']->getValue();
                    }
                    $totalProducts = $totalPrice - $shippingExclTaxes;

                    if (isset($orderTotals['tax'])) {
                        $taxValue = $orderTotals['tax']->getValue() / $rate;
                        $totalTax = array(
                            'name' => 'Total Tax',
                            'taxValue' => $this->coreHelper->currency($taxValue, true, false)
                        );

                        $tax[] = $totalTax;
                    }
                }

                $cart['dateUpd'] = $dateUpd;
                $cart['totalPrice'] = ($totalPrice != 0 && $totalProducts != 0) ? $this->coreHelper->currency($totalPrice, true, false) : null;
                $cart['totalProducts'] = ($totalPrice != 0 && $totalProducts != 0) ? strip_tags($this->checkoutHelper->formatPrice($totalProducts)) : null;
                $cart['products'] = $products;
                $cart['taxes'] = $tax;
                $cart['promocode'] = $this->getPromocode($order);
                $cart['totalPromocodeDiscount'] = ($totalPrice != 0 && $totalProducts != 0) ? $this->coreHelper->currency(abs($discountAmount), true, false) : null;
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $cart;
    }

    /**
     * @return array
     */
    private function getDefaultPromocode()
    {
        return array(
            'code' => null,
            'name' => null,
            'type' => null,
            'value' => null
        );
    }

    /**
     * Get promocodes applied for the current order
     */
    public function getPromocode($order)
    {
        $currentPromocode = $this->getDefaultPromocode();

        try {
            $appliedRuleIds = $order->getAppliedRuleIds();
            if (!isset($appliedRuleIds) || empty($appliedRuleIds)) {
                return $currentPromocode;
            }

            $ruleIds = explode(',', $appliedRuleIds);
            $ruleIds = array_unique($ruleIds);

            foreach ($ruleIds as $ruleId) {
                $rule = Mage::getModel('salesrule/rule');
                $rule->load($ruleId);
                $ruleId = $rule->getId();
                $ruleCouponCode = $rule->getData('coupon_code');
                if (!empty($ruleId) && !empty($ruleCouponCode)) {
                    $type = $rule->getData('simple_action');
                    $promoCodeValue = $rule->getData('discount_amount');
                    if (strrpos($type, 'fixed') !== false) {
                        $promoCodeValue = $this->coreHelper->currency(abs($promoCodeValue), true, false);
                    }

                    $currentPromocode = array(
                        'code' => $ruleCouponCode,
                        'name' => $rule->getData('name'),
                        'type' => $type,
                        'value' => $promoCodeValue
                    );

                    //return first promocode with a valid coupon code
                    return $currentPromocode;
                }
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        // no promocode with a valid coupon code was found
        return $currentPromocode;
    }

    /**
     * @return array
     */
    private function getDefaultProduct()
    {
        return array(
            'productId' => null,
            'description' => null,
            'descriptionShort' => null,
            'images' => array(
                'fullImagePath' => null,
                'partialImagePath' => null,
            ),
            'manufacturerName' => null,
            'name' => null,
            'priceCurrent' => null,
            'priceDiscount' => null,
            'priceWithoutDiscount' => null,
            'productLink' => null,
            'category' => null
        );
    }

    /**
     * Build product structure used used in currentPage and cart
     */
    public function getProductInformation($id)
    {
        $prod = $this->getDefaultProduct();
        try {
            if (empty($id)) {
                return $prod;
            }

            $product = Mage::getModel('catalog/product')->load((int)$id);
            $category = null;
            // get top parent category of current product
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds) > 0) {
                $category = $this->catalogCategory->load((int)$categoryIds[0]);
                $catParent = $this->getTopParentCategory($category);
                $category = empty($catParent) ? $category->getName() : $catParent->getName();
            }

            $imgFullPath = $this->catalogMedia->getMediaUrl($product->getImage());
            $imgPartialPath = $this->catalogMedia->getMediaShortUrl($product->getImage());

            $finalPrice = $this->taxHelper->getPrice($product, $product->getFinalPrice(), true);
            $originalPrice = $this->taxHelper->getPrice($product, $product->getPrice(), true);
            $productSku = $product->getData('sku');
            $prod = array(
                'productId' => !empty($productSku) ? $productSku : $id,
                'description' => $product->getData('description'),
                'descriptionShort' => $product->getData('short_description'),
                'images' => array(
                    'fullImagePath' => $imgFullPath,
                    'partialImagePath' => $imgPartialPath,
                ),
                'manufacturerName' => null,
                'name' => $product->getName(),
                'priceCurrent' => $this->coreHelper->currency($finalPrice, true, false),
                'priceDiscount' => $this->coreHelper->currency(($originalPrice - $finalPrice), true, false),
                'priceWithoutDiscount' => $this->coreHelper->currency($originalPrice, true, false),
                'productLink' => $product->getProductUrl(true),
                'category' => $category
            );
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $prod;
    }

    /**
     * Get top-most parent of a specific category
     *
     * @param $category
     * @return null | instance of category entity
     */
    private function getTopParentCategory($category)
    {
        $topParent = null;
        try {
            $path = $category->getPath(); // gets list of category ids separated by slashes, eg: rootCategoryId/defaultCategoryId/womenCategoryId/newarrivalsCategoryId
            $ids = explode('/', $path);

            /*
                As "Root Category" and "Default Category" are not displayed in the breadcrumb, so we look the following category in the hierarchy
                If none exists, it means that $category is already a top level one, so it doesn't have a parent
            */
            if (isset($ids[2])) {
                $topParent = $this->catalogCategory->setStoreId($this->store->getId())->load($ids[2]);
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $topParent;
    }

    /**
     * Get formatted array for lastVisitedCategory
     * @return array
     */
    private function getLastVisitedcategory()
    {
        try {
            $session = Mage::getSingleton("core/session", array("name" => "frontend"));

            // update category on product and category page
            if ($this->currentProductRegistry || $this->currentCategoryRegistry) {

                $category = null;
                //product page
                if ($this->currentProductRegistry) {
                    //get categories of current product
                    $productId = $this->currentProductRegistry->getId();
                    $product = Mage::getModel('catalog/product')->load((int)$productId);
                    $categoryIds = $product->getCategoryIds();

                    if (count($categoryIds) > 0) {
                        $category = $this->catalogCategory->load((int)$categoryIds[0]);
                        $catParent = $this->getTopParentCategory($category);
                        $category = empty($catParent) ? $category : $catParent;
                    }
                } else {
                    //category page
                    $catId = $this->currentCategoryRegistry->getId();
                    $category = $this->catalogCategory->load((int)$catId);
                }

                if (isset($category)) {
                    $categoryName = $category->getName();
                    $categoryLink = $category->getUrl();

                    // save category name and url in session
                    $session->setData('lastVisitedCategoryName', $categoryName);
                    $session->setData('lastVisitedCategoryLink', $categoryLink);
                } else {
                    // if product has no category, retrieve information from session
                    $categoryName = $session->getData('lastVisitedCategoryName');
                    $categoryLink = $session->getData('lastVisitedCategoryLink');
                }
            } else {
                // retrieve category information from session, if not on product or category page
                $categoryName = $session->getData('lastVisitedCategoryName');
                $categoryLink = $session->getData('lastVisitedCategoryLink');
            }

            $lastVisitedCategory = array(
                'name' => isset($categoryName) ? $categoryName : null,
                'link' => isset($categoryLink) ? $categoryLink : null
            );
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);

            $lastVisitedCategory = array(
                'name' => null,
                'link' => null
            );
        }

        return $lastVisitedCategory;
    }

    /**
     * Get user history - products history and last visited category
     */
    public function getUserHistory()
    {
        try {
            $history = array();
            $hasHistory = $this->coreSession->getHistory();
            if (!$hasHistory) {
                $history['productHistory'] = array();
                $history['lastVisitedCategory'] = array(
                    'name' => null,
                    'link' => null
                );
            } else {
                $latestViewedProducts = $this->coreSession->getUserViewedProducts();
                $productHistory = array();
                if (!empty($latestViewedProducts)) {
                    $productList = array_unique(explode(',', $latestViewedProducts));
                    foreach ($productList as $productId) {
                        $productHistory[] = $this->getProductInformation($productId);
                    }
                }

                $history['productHistory'] = $productHistory;
                $history['lastVisitedCategory'] = $this->getLastVisitedcategory();
            }

        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
            $history = array(
                'productHistory' => array(),
                'lastVisitedCategory' => array(
                    'name' => null,
                    'link' => null
                )
            );
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

        try {
            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                $customerMiddleName = $customer->getMiddlename();
                $customerInfo = array(
                    'email' => $customer->getEmail(),
                    'firstName' => $customer->getFirstname() . (!empty($customerMiddleName) ? ' ' . $customerMiddleName : ''),
                    'lastName' => $customer->getLastname()
                );
            }
        } catch (Exception $exception) {
            $this->exceptionHandler->logException($exception);
        }

        return $customerInfo;
    }

}

