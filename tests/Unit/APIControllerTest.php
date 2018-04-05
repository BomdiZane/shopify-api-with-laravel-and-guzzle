<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class APIControllerTest extends TestCase
{
    /**
     * Testing functions in APIController
     *
     * @return void
     */

    protected $controller;

    protected function setUp()
    {
        $this->controller = new \App\Http\Controllers\ApiController();
    }

    protected function tearDown()
    {
        $this->controller = null;
    }
    
    public function testGetBrandsgatewayDataWithInvalidParameter()
    {
        $this->assertCount(1, $this->controller->getBrandsgatewayData());
        $this->assertCount(1, $this->controller->getBrandsgatewayData(-0.1, 'u'));
        $this->assertCount(1, $this->controller->getBrandsgatewayData('u', -0.1));
    }

    public function testGetBrandsgatewayDataWithValidParameter()
    {
        $perPage = 5;
        $result = $this->controller->getBrandsgatewayData(1, $perPage);
        $this->assertInternalType('array', $result);
        $this->assertCount($perPage, $result);
        $this->assertObjectHasAttribute('id', $result[0]);
    }

    public function testBuildShopwooProductuctWithInvalidParameter()
    {
        $this->assertInternalType('string', $this->controller->buildShopwooProductuct([5]));
        $this->assertInternalType('string', $this->controller->buildShopwooProductuct(5));
        $this->assertInternalType('string', $this->controller->buildShopwooProductuct('5'));
    }
    
    public function testBuildShopwooProductuctWithValidParameter()
    {
        $perPage = 1;
        $page = 1;
        $brandsgatewayProduct = $this->controller->getBrandsgatewayData($page, $perPage);
        $shopwooProduct = $this->controller->buildShopwooProductuct($brandsgatewayProduct[0]);
        $this->assertInternalType('array', $shopwooProduct);
        $this->assertArrayHasKey('product', $shopwooProduct);
    }

    public function testSendShopwooProductuctWithInvalidParameter()
    {
        $this->assertEquals('Invalid Product', $this->controller->sendShopwooProductuct((object)[5]));
        $this->assertEquals('Invalid Product', $this->controller->sendShopwooProductuct(5));
        $this->assertEquals('Invalid Product', $this->controller->sendShopwooProductuct('5'));
    }

    // public function testSendShopwooProductuctWithValidParameter()
    // {
    //     $this->assertTrue(true);
    // }

    // public function testTransferProducts()
    // {
    //     $this->assertTrue(true);
    // }
}
