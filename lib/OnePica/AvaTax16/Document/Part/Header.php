<?php
/**
 * OnePica
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to codemaster@onepica.com so we can send you a copy immediately.
 *
 * @category  OnePica
 * @package   OnePica_AvaTax
 * @copyright Copyright (c) 2015 One Pica, Inc. (http://www.onepica.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class OnePica_AvaTax16_Document_Part_Header
 */
class OnePica_AvaTax16_Document_Part_Header extends OnePica_AvaTax16_Document_Part
{
    /**
     * Required properties
     *
     * @var array
     */
    protected $_requiredProperties = array('_accountId', '_companyCode', '_transactionType', '_documentCode',
        '_customerCode', '_vendorCode', '_transactionDate', '_defaultLocations');

    /**
     * Account Id
     * (Required)
     *
     * @var string
     */
    protected $_accountId;

    /**
     * Company Code
     * (Required)
     *
     * @var string
     */
    protected $_companyCode;

    /**
     * Transaction Type
     * (Required)
     *
     * @var string
     */
    protected $_transactionType;

    /**
     * Document Code
     * (Required)
     *
     * @var string
     */
    protected $_documentCode;

    /**
     * Customer Code
     * (Required)
     *
     * @var string
     */
    protected $_customerCode;

    /**
     * Vendor Code
     * (Required)
     *
     * @var string
     */
    protected $_vendorCode;

    /**
     * Transaction Date
     * (Required)
     *
     * @var string
     */
    protected $_transactionDate;

    /**
     * Currency
     * (Not currently supported)
     *
     * @var string
     */
    protected $_currency;

    /**
     * Total Tax Override Amount
     * (Not currently supported)
     *
     * @var float
     */
    protected $_totalTaxOverrideAmount;

    /**
     * Tax Calculation Date
     *
     * @var string
     */
    protected $_taxCalculationDate;

    /**
     * Default Avalara Goods And Services Modifier Type
     * (Not currently supported)
     *
     * @var string
     */
    protected $_defaultAvalaraGoodsAndServicesModifierType;

    /**
     * Default locations
     * (Required)
     *
     * @var OnePica_AvaTax16_Document_Part_Locations
     */
    protected $_defaultLocations;

    /**
     * Default Tax Payer Code
     * (Not currently supported)
     *
     * @var string
     */
    protected $_defaultTaxPayerCode;

    /**
     * Default Tax Payer Code
     * (Not currently supported)
     *
     * @var string
     */
    protected $_defaultEntityUseType;

    /**
     * Purchase Order Number
     *
     * @var string
     */
    protected $_purchaseOrderNumber;

    /**
     * Metadata
     *
     * @var array
     */
    protected $_metadata;

    /**
     * Construct
     */
    public function __construct()
    {
        // init document parts
        $this->_defaultLocations = new OnePica_AvaTax16_Document_Part_Locations();
    }
}
