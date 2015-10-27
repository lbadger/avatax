<?php
/**
 * OnePica_AvaTax
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2015 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Class OnePica_AvaTax_Helper_RequestFilter
 */
class OnePica_AvaTax_Helper_RequestFilter extends Mage_Core_Helper_Abstract
{
    /**
     * Checks if request is filtered
     *
     * @param Mage_Core_Model_Store|int $store
     */
    public function isRequestFiltered($store)
    {
    }

    /**
     * Returns a config value from the admin.
     *
     * @param string $path
     * @param Mage_Core_Model_Store|int $store
     * @return string
     */
    protected function _getConfig ($path, $store = null)
    {
        return Mage::getSingleton('avatax/config')->getConfig($path, $store);
    }
}
