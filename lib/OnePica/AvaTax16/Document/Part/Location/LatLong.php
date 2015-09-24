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
 * Class OnePica_AvaTax16_Document_Part_Location_LatLong
 */
class OnePica_AvaTax16_Document_Part_Location_LatLong extends OnePica_AvaTax16_Document_Part
{
    /**
     * Latitude
     *
     * @var string
     */
    private $_latitude;

    /**
     * Longitude
     *
     * @var string
     */
    private $_longitude;

    /**
     * Set Latitude
     *
     * @param string $value
     * @return $this
     */
    public function setLatitude($value)
    {
        $this->_latitude = $value;
        return $this;
    }

    /**
     * Get Latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->_latitude;
    }

    /**
     * Set Longitude
     *
     * @param string $value
     * @return $this
     */
    public function setLongitude($value)
    {
        $this->_longitude = $value;
        return $this;
    }

    /**
     * Get Longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->_longitude;
    }
}
