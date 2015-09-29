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
 * Class OnePica_AvaTax16_Config
 */
class OnePica_AvaTax16_Calculation
{
    /**
     * Url path for calculations
     */
    const CALCULATION_URL_PATH = '/calculations';

    /**
     * Config
     *
     * @var OnePica_AvaTax16_Config
     */
    protected $_config;

    /**
     * Construct
     *
     * @param OnePica_AvaTax16_Config $config
     */
    public function __construct($config)
    {
        $this->_config = $config;
    }

    /**
     * Get config
     *
     * @return OnePica_AvaTax16_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Get Curl Object with headers from config
     *
     * @return OnePica_AvaTax16_IO_Curl
     */
    protected function _getCurlObjectWithHeaders()
    {
        $curl = new OnePica_AvaTax16_IO_Curl();
        $config = $this->getConfig();
        $curl->setHeader('Authorization', $config->getAuthorizationHeader());
        $curl->setHeader('Accept', $config->getAcceptHeader());
        $curl->setHeader('Content-Type', $config->getContentTypeHeader());
        $curl->setHeader('User-Agent', $config->getUserAgent());
        return $curl;
    }

    /**
     * Create Calculation
     *
     * @param OnePica_AvaTax16_Document_Request $documentRequest
     * @return StdClass $data
     */
    public function createCalculation($documentRequest)
    {
        $curl = $this->_getCurlObjectWithHeaders();
        $postUrl = $this->_config->getBaseUrl() . self::CALCULATION_URL_PATH;
        $postData = $documentRequest->toArray();
        $curl->post($postUrl, $postData);
        $data = $curl->response;
        return $data;
    }

    /**
     * Get Calculation
     *
     * @param string $transactionType
     * @param string $documentCode
     * @return StdClass $data
     */
    public function getCalculation($transactionType, $documentCode)
    {
        $curl = $this->_getCurlObjectWithHeaders();
        $config = $this->getConfig();
        $getUrl = $config->getBaseUrl()
                . self::CALCULATION_URL_PATH
                . '/account/'
                . $config->getAccountId()
                . '/company/'
                . $config->getCompanyCode()
                . '/'
                . $transactionType
                . '/'
                . $documentCode;

        $curl->get($getUrl);
        $data = $curl->response;
        return $data;
    }

    /**
     * Get List Of Calculations
     *
     * @param string $transactionType
     * @param int $limit
     * @param string $startDate
     * @param string $endDate
     * @param string $startCode (not implemented)
     * @return StdClass|array $result
     */
    public function getListOfCalculation($transactionType, $limit = null, $startDate = null, $endDate = null,
        $startCode = null)
    {
        $curl = $this->_getCurlObjectWithHeaders();
        $config = $this->getConfig();
        $getUrl = $config->getBaseUrl()
                . self::CALCULATION_URL_PATH
                . '/account/'
                . $config->getAccountId()
                . '/company/'
                . $config->getCompanyCode()
                . '/'
                . $transactionType;
        $filterData = array(
            'limit' => $limit,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startCode' => $startCode,
        );

        $curl->get($getUrl, $filterData);
        $data = $curl->response;

        $result = null;
        if (is_array($data)) {
            foreach ($data as $dataItem) {
                $listOfCalculations = new OnePica_AvaTax16_Calculation_ListOfCalculationsResponse();
                $result[] = $listOfCalculations->fillData($dataItem);
            }
        }
        return $result;
    }
}
