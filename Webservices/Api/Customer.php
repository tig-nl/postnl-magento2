<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Webservices\Api;

use TIG\PostNL\Config\Provider\AccountConfiguration;
use TIG\PostNL\Config\Provider\AddressConfiguration;

class Customer
{
    const ADDRESS_TYPE_SENDER = '02';

    /**
     * @var AccountConfiguration
     */
    private $accountConfiguration;

    /**
     * @var AddressConfiguration
     */
    private $addressConfiguration;

    /**
     * @param AccountConfiguration $accountConfiguration
     * @param AddressConfiguration $addressConfiguration
     */
    public function __construct(
        AccountConfiguration $accountConfiguration,
        AddressConfiguration $addressConfiguration
    ) {
        $this->accountConfiguration = $accountConfiguration;
        $this->addressConfiguration = $addressConfiguration;
    }

    /**
     * @return array
     */
    public function get()
    {
        $customer = [
            'CustomerCode'   => $this->accountConfiguration->getCustomerCode(),
            'CustomerNumber' => $this->accountConfiguration->getCustomerNumber(),
        ];

        return $customer;
    }

    /**
     * @return mixed
     */
    public function blsCode()
    {
        return $this->accountConfiguration->getBlsCode();
    }

    /**
     * @return array
     */
    public function address()
    {
        $addressArray = [
            'AddressType' => self::ADDRESS_TYPE_SENDER,
            'FirstName'   => $this->addressConfiguration->getFirstname(),
            'Name'        => $this->addressConfiguration->getLastname(),
            'CompanyName' => $this->addressConfiguration->getCompany(),
            'Street'      => $this->addressConfiguration->getStreetname(),
            'HouseNr'     => $this->addressConfiguration->getHousenumber(),
            'HouseNrExt'  => $this->addressConfiguration->getHousenumberAddition(),
            'Zipcode'     => strtoupper(str_replace(' ', '', $this->addressConfiguration->getPostcode())),
            'City'        => $this->addressConfiguration->getCity(),
            'Countrycode' => 'NL',
            'Department'  => $this->addressConfiguration->getDepartment(),
        ];

        return $addressArray;
    }
}