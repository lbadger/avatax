<?php

/**
 * OnePica_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2009 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * The AvaTax Address Estimate model
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 */
class OnePica_AvaTax_Model_Avatax_Estimate extends OnePica_AvaTax_Model_Avatax_Abstract
{
    /**
     * Length of time in minutes for cached rates
     *
     * @var int
     */
    const CACHE_TTL = 120;

    /**
     * An array of rates that acts as a cache
     * Example: $_rates[$cachekey] = array(
     *     'timestamp' => 1325015952
     *     'summary' => array(
     *         array('name'=>'NY STATE TAX', 'rate'=>4, 'amt'=>6),
     *         array('name'=>'NY CITY TAX', 'rate'=>4.50, 'amt'=>6.75),
     *         array('name'=>'NY SPECIAL TAX', 'rate'=>4.375, 'amt'=>0.56)
     *     ),
     *     'items' => array(
     *         5 => array('rate'=>8.875, 'amt'=>13.31),
     *         'Shipping' => array('rate'=>0, 'amt'=>0)
     *     )
     * )
     *
     * @var array
     */
    protected $_rates = array();

    /**
     * An array of line items
     *
     * @var array
     */
    protected $_lines = array();

    /**
     * An array of line numbers to quote item ids
     *
     * @var array
     */
    protected $_lineToLineId = array();

    /**
     * Product gift pair
     *
     * @var array
     */
    protected $_productGiftPair = array();

    /**
     * Loads any saved rates in session
     */
    protected function _construct()
    {
        $rates = Mage::getSingleton('avatax/session')->getRates();
        if (is_array($rates)) {
            foreach ($rates as $key => $rate) {
                if ($rate['timestamp'] < $this->_getDateModel()->timestamp('-' . self::CACHE_TTL . ' minutes')) {
                    unset($rates[$key]);
                }
            }
            $this->_rates = $rates;
        }
        return parent::_construct();
    }

    /**
     * Estimates tax rate for one item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    public function getItemRate($item)
    {
        if ($this->isProductCalculated($item)) {
            return 0;
        } else {
            $key = $this->_getRates($item);
            $id = $item->getSku();
            return isset($this->_rates[$key]['items'][$id]['rate']) ? $this->_rates[$key]['items'][$id]['rate'] : 0;
        }
    }

    /**
     * Get item tax
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    public function getItemGiftTax($item)
    {
        if ($item->getParentItemId()) {
            return 0;
        }
        $key = $this->_getRates($item);
        $id = $item->getSku();
        return isset($this->_rates[$key]['gw_items'][$id]['amt']) ? $this->_rates[$key]['gw_items'][$id]['amt'] : 0;
    }

    /**
     * Estimates tax amount for one item. Does not trigger a call if the shipping
     * address has no postal code, or if the postal code is set to "-" (OneStepCheckout)
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    public function getItemTax($item)
    {
        if ($item->getAddress()->getPostcode() && $item->getAddress()->getPostcode() != '-') {
            if ($this->isProductCalculated($item)) {
                $tax = 0;
                foreach ($item->getChildren() as $child) {
                    $child->setAddress($item->getAddress());
                    $tax += $this->getItemTax($child);
                }
                return $tax;
            } else {
                $key = $this->_getRates($item);
                $id = $item->getSku();
                return isset($this->_rates[$key]['items'][$id]['amt']) ? $this->_rates[$key]['items'][$id]['amt'] : 0;
            }
        }
        return 0;
    }

    /**
     * Get tax detail summary
     *
     * @param int|null $addressId
     * @return array
     */
    public function getSummary($addressId = null)
    {
        $summary = null;

        if ($addressId) {
            $timestamp = 0;
            foreach ($this->_rates as $row) {
                if (isset($row['address_id']) && $row['address_id'] == $addressId && $row['timestamp'] > $timestamp) {
                    $summary = $row['summary'];
                    $timestamp = $row['timestamp'];
                }
            }
        }

        if ($summary === null) {
            $requestKey = Mage::getSingleton('avatax/session')->getLastRequestKey();
            $summary = isset($this->_rates[$requestKey]['summary']) ? $this->_rates[$requestKey]['summary'] : array();
        }

        return $summary;
    }

    /**
     * Get rates from Avalara
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    protected function _getRates($item)
    {
        if (self::$_hasError) {
            return 'error';
        }

        /** @var OnePica_AvaTax_Model_Sales_Quote_Address $address */
        $address = $item->getAddress();
        $this->_lines = array();

        //set up request
        $quote = $address->getQuote();
        $this->_request = new GetTaxRequest();
        $this->_request->setDocType(DocumentType::$SalesOrder);
        $this->_request->setDocCode('quote-' . $address->getId());
        $this->_addGeneralInfo($address);
        $this->_setOriginAddress($quote->getStoreId());
        $this->_setDestinationAddress($address);
        $this->_request->setDetailLevel(DetailLevel::$Line);
        $this->_addItemsInCart($item);
        $this->_addShipping($address);
        //Added code for calculating tax for giftwrap items
        $this->_addGwOrderAmount($address);
        $this->_addGwPrintedCardAmount($address);
        //check to see if we can/need to make the request to Avalara
        $requestKey = $this->_genRequestKey();
        $makeRequest = empty($this->_rates[$requestKey]['items']);
        //@startSkipCommitHooks
        $makeRequest &= count($this->_lineToLineId) ? true : false;
        $makeRequest &= $this->_request->getDestinationAddress() == '' ? false : true;
        $makeRequest &= $address->getId() ? true : false;
        $makeRequest &= !isset($this->_rates[$requestKey]['failure']);
        //@finishSkipCommitHooks

        //make request if needed and save results in cache
        if ($makeRequest) {
            $result = $this->_send($quote->getStoreId());
            $this->_rates[$requestKey] = array(
                'timestamp' => $this->_getDateModel()->timestamp(),
                'address_id' => $address->getId(),
                'summary' => array(),
                'items' => array(),
                'gw_items' => array()
            );

            //success
            /** @var GetTaxResult $result */
            if ($result->getResultCode() == SeverityLevel::$Success) {
                foreach ($result->getTaxLines() as $ctl) {
                    /** @var TaxLine $ctl */
                    $id = $this->_getItemIdByLine($ctl);
                    $code = $this->_getTaxArrayCodeByLine($ctl);
                    $this->_rates[$requestKey][$code][$id] = array(
                        'rate' => ($ctl->getTax() ? $ctl->getRate() : 0) * 100,
                        'amt' => $ctl->getTax(),
                    );
                }

                foreach ($result->getTaxSummary() as $row) {
                    $this->_rates[$requestKey]['summary'][] = array(
                        'name' => $row->getTaxName(),
                        'rate' => $row->getRate() * 100,
                        'amt' => $row->getTax()
                    );
                }
            //failure
            } else {
                $this->_rates[$requestKey]['failure'] = true;
                self::$_hasError = true;
            }

            Mage::getSingleton('avatax/session')->setRates($this->_rates);
        }

        if (isset($this->_rates[$requestKey]['failure'])) {
            $address->getQuote()->setData('estimate_tax_error', true);
        }

        //return $requestKey so it doesn't have to be calculated again
        return $requestKey;
    }

    /**
     * Generates a hash key for the exact request
     *
     * @return string
     */
    protected function _genRequestKey()
    {
        $hash = sprintf("%u", crc32(serialize($this->_request)));
        Mage::getSingleton('avatax/session')->setLastRequestKey($hash);
        return $hash;
    }

    /**
     * Adds shipping cost to request as item
     *
     * @param OnePica_AvaTax_Model_Sales_Quote_Address $address
     * @return int
     */
    protected function _addShipping($address)
    {
        $lineNumber = count($this->_lines);
        $storeId = $address->getQuote()->getStore()->getId();
        $taxClass = Mage::helper('tax')->getShippingTaxClass($storeId);
        $shippingAmount = max(
            0.0, (float)$address->getBaseShippingAmount() - $address->getBaseShippingDiscountAmount()
        );

        $line = new Line();
        $line->setNo($lineNumber);
        $shippingSku = $this->_getDataHelper()->getShippingSku($storeId);
        $line->setItemCode($shippingSku ?: 'Shipping');
        $line->setDescription('Shipping costs');
        $line->setTaxCode($taxClass);
        $line->setQty(1);
        $line->setAmount($shippingAmount);
        $line->setDiscounted($address->getBaseShippingDiscountAmount() ? true : false);

        $this->_lines[$lineNumber] = $line;
        $this->_request->setLines($this->_lines);
        $this->_lineToLineId[$lineNumber] = $this->_getDataHelper()->getShippingSku($storeId);

        return $lineNumber;
    }

    /**
     * Adds giftwraporder cost to request as item
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return int|bool
     */
    protected function _addGwOrderAmount($address)
    {
        if (!$address->getGwPrice()) {
            return false;
        }
        $lineNumber = count($this->_lines);
        $storeId = $address->getQuote()->getStore()->getId();
        //Add gift wrapping price(for entire order)
        $gwOrderAmount = $address->getGwBasePrice();

        $line = new Line();
        $line->setNo($lineNumber);
        $gwOrderSku = Mage::helper('avatax')->getGwOrderSku($storeId);
        $line->setItemCode($gwOrderSku ? $gwOrderSku : 'GwOrderAmount');
        $line->setDescription('Gift Wrap Order Amount');
        $line->setTaxCode($this->_getGiftTaxClassCode($storeId));
        $line->setQty(1);
        $line->setAmount($gwOrderAmount);
        $line->setDiscounted(false);

        $this->_lines[$lineNumber] = $line;
        $this->_request->setLines($this->_lines);
        $this->_lineToLineId[$lineNumber] = Mage::helper('avatax')->getGwOrderSku($storeId);
        return $lineNumber;
    }

    /**
     * Adds giftwrapitems cost to request as item
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int|bool
     */
    protected function _addGwItemsAmount($item)
    {
        if (!$item->getGwId()) {
            return false;
        }
        $lineNumber = count($this->_lines);
        $storeId = $item->getQuote()->getStoreId();
        //Add gift wrapping price(for individual items)
        $gwItemsAmount = $item->getGwBasePrice() * $item->getQty();

        $line = new Line();
        $line->setNo($lineNumber);
        $gwItemsSku = Mage::helper('avatax')->getGwItemsSku($storeId);
        $line->setItemCode($gwItemsSku ? $gwItemsSku : 'GwItemsAmount');
        $line->setDescription('Gift Wrap Items Amount');
        $line->setTaxCode($this->_getGiftTaxClassCode($storeId));
        $line->setQty($item->getQty());
        $line->setAmount($gwItemsAmount);
        $line->setDiscounted(false);

        $this->_lines[$lineNumber] = $line;
        $this->_request->setLines($this->_lines);
        $this->_lineToLineId[$lineNumber] = Mage::helper('avatax')->getGwItemsSku($storeId);
        $this->_productGiftPair[$lineNumber] = $item->getSku();

        return $lineNumber;
    }

     /**
     * Adds giftwrap printed card cost to request as item
     *
     * @param Mage_Sales_Model_Quote
     * @return int|bool
     */
    protected function _addGwPrintedCardAmount($address)
    {
        if (!$address->getGwPrintedCardPrice()) {
            return false;
        }
        $lineNumber = count($this->_lines);
        $storeId = $address->getQuote()->getStore()->getId();
        //Add printed card price
        $gwPrintedCardAmount = $address->getGwPrintedCardBasePrice();

        $line = new Line();
        $line->setNo($lineNumber);
        $gwPrintedCardSku = Mage::helper('avatax')->getGwPrintedCardSku($storeId);
        $line->setItemCode($gwPrintedCardSku ? $gwPrintedCardSku : 'GwPrintedCardAmount');
        $line->setDescription('Gift Wrap Printed Card Amount');
        $line->setTaxCode($this->_getGiftTaxClassCode($storeId));
        $line->setQty(1);
        $line->setAmount($gwPrintedCardAmount);
        $line->setDiscounted(false);

        $this->_lines[$lineNumber] = $line;
        $this->_request->setLines($this->_lines);
        $this->_lineToLineId[$lineNumber] = Mage::helper('avatax')->getGwPrintedCardSku($storeId);
        return $lineNumber;
    }

    /**
     * Adds all items in the cart to the request
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    protected function _addItemsInCart($item)
    {
        if ($item->getAddress() instanceof Mage_Sales_Model_Quote_Address) {
            $items = $item->getAddress()->getAllItems();
        } elseif ($item->getQuote() instanceof Mage_Sales_Model_Quote) {
            $items = $item->getQuote()->getAllItems();
        } else {
            $items = array();
        }

        if (count($items) > 0) {
            $this->_initProductCollection($items);
            $this->_initTaxClassCollection($item->getAddress());
            foreach ($items as $item) {
                /** @var Mage_Sales_Model_Quote_Item $item */
                $this->_newLine($item);
            }
            $this->_request->setLines($this->_lines);
        }

        return count($this->_lines);
    }

    /**
     * Makes a Line object from a product item object
     *
     * @param Varien_Object|Mage_Sales_Model_Quote_Item $item
     * @return int|bool
     */
    protected function _newLine($item)
    {
        $this->_addGwItemsAmount($item);
        if ($this->isProductCalculated($item)) {
            return false;
        }
        $product = $this->_getProductByProductId($item->getProductId());
        $taxClass = $this->_getTaxClassCodeByProduct($product);
        $price = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
        $lineNumber = count($this->_lines);
        $line = new Line();
        $line->setNo($lineNumber);
        $line->setItemCode($this->_getItemCode($item));
        $line->setDescription($item->getName());
        $line->setQty($item->getQty());
        $line->setAmount($price);
        $line->setDiscounted($item->getDiscountAmount() ? true : false);

        if ($taxClass) {
            $line->setTaxCode($taxClass);
        }
        $ref1Value = $this->_getRefValueByProductAndNumber($product, 1, $item->getStoreId());
        if ($ref1Value) {
            $line->setRef1($ref1Value);
        }
        $ref2Value = $this->_getRefValueByProductAndNumber($product, 2, $item->getStoreId());
        if ($ref2Value) {
            $line->setRef2($ref2Value);
        }

        $this->_lines[$lineNumber] = $line;
        $this->_lineToLineId[$lineNumber] = $item->getSku();
        return $lineNumber;
    }

    /**
     * Get item code
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    protected function _getItemCode($item)
    {
        $product = $this->_getProductByProductId($item->getProductId());
        $itemCode = $this->_getUpcCode($product);
        if (empty($itemCode)) {
            $itemCode = $item->getSku();
        }
        return substr($itemCode, 0, 50);
    }

    /**
     * Get item id/code for given line
     *
     * @param TaxLine $line
     * @return string|int
     */
    protected function _getItemIdByLine($line)
    {
        return isset($this->_productGiftPair[$line->getNo()])
            ? $this->_productGiftPair[$line->getNo()]
            : $this->_lineToLineId[$line->getNo()];
    }

    /**
     * Get tax array code for given line
     *
     * @param TaxLine $line
     * @return string
     */
    protected function _getTaxArrayCodeByLine($line)
    {
        return isset($this->_productGiftPair[$line->getNo()]) ? 'gw_items' : 'items';
    }
}
