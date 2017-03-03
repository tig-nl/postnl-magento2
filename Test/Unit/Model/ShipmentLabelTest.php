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
namespace TIG\PostNL\Unit\Model;

use TIG\PostNL\Model\ShipmentLabel;
use TIG\PostNL\Test\TestCase;

class ShipmentLabelTest extends TestCase
{
    /**
     * @param array $args
     *
     * @return object
     */
    public function getInstance(array $args = [])
    {
        return $this->objectManager->getObject(ShipmentLabel::class, $args);
    }

    /**
     * @return array
     */
    public function getIdentitiesProvider()
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
        ];
    }

    /**
     * @param $id
     *
     * @dataProvider getIdentitiesProvider
     */
    public function testGetIdentities($id)
    {
        $instance = $this->getInstance();
        $instance->setId($id);

        $result = $instance->getIdentities();
        $expected = ShipmentLabel::CACHE_TAG . '_' . $id;

        $this->assertInternalType('array', $result);
        $this->assertEquals([$expected], $result);
    }
}
