<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

ini_set('max_execution_time', 600);

/*
|--------------------------------------------------------------------------
| ApiController
|--------------------------------------------------------------------------
|
| This controller fetches products from 'brandsgateway' and pushes them
| to 'shopwoo'
|
*/

class ApiController extends Controller
{

    // Initialize variables
    private $minNumberOfProducts = 150; // Minimum number of products to transfer
    private $productsPerPage = 100; // The brandsgateway API returns maximum of 100 products per page
    const ShopwooURL = 'https://shopwoo-developer-test.myshopify.com/admin/products.json';
    const ShopwooUser = '8675c061ca380a43a66b30a5f4a0ff82';
    const ShopwooPass = '09dbbf87a47d5f3e52fbada1012adb55';
    const BrandsgatewayUser = '7733';
    const BrandsgatewayPass = '52zAcz%2WkBR';
    const BrandsgatewayOptions = [
        "headers" => [
            "content-type" => "application/json",
            "Accept" => "application/json"
        ],
        "verify" => false // Only for local testing purpose
    ];

    /*
    |--------------------------------------------------------------------------
    | Transfer Products
    |--------------------------------------------------------------------------
    |
    | Entry method that controls the transfer of products
    | 
    | @returns - Success message 'success', if transfer succeeds
    |          - Error array containing failure messages in case of any 
    |              transfer failures
    |
    */
    public function transferProducts() {
        
        $cycles = ceil($this->minNumberOfProducts / $this->productsPerPage);
        $errors = [];

        // We have to visit $cycles number of pages to get $minNumberOfProducts products
        // Note: we use $minNumberOfProducts as a minimum (not exact or maximum) number of products to be fetched
        for ($i=1; $i <= $cycles; $i++) { 

            $data = self::getBrandsgatewayData($i, $this->productsPerPage);
            
            if (is_array($data)){
                // Prepare and send brandsgateway products to shopwoo
                foreach ($data as $product) {
                    $result = self::sendShopwooProductuct(self::buildShopwooProductuct($product));
                    if ($result !== '201') array_push($errors, $result);
                }
            }
            else array_push($errors, $res->getStatusCode().': '.$res->getReasonPhrase());
        }

        // Return results
        return sizeof($errors) > 0 ? sizeof($errors)." errors!\n".json_encode($errors) : 'Success!';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Brandsgateway Data (Products)
    |--------------------------------------------------------------------------
    |
    | Fetches products from brandsgateway
    |
    | @param $page(1): The page from which to fetch products
    | @param $perPage(100): How many products to fetch from given page
    | 
    | @returns - An array of products if fetch was successful
    |          - A string containing the response code and reason phrase if 
    |              fetching failed
    |
    */
    public function getBrandsgatewayData($page = 1, $perPage = 1) {
        
        // $perPage must be an integer between 1 and 100
        if (!is_int($perPage) || $perPage > 100 || $perPage < 1) $perPage = 1;
        if (!is_int($page) || $page < 1) $page = 1; // $page must be an integer greater than 0

        $BrandsgatewayURL = 'https://v1.api.brandsgateway.com/products?per_page='.$perPage.'&page='.$page;
        
        // Create request
        $client = new Client(self::BrandsgatewayOptions);
        $res = $client->get($BrandsgatewayURL, ['auth' => [self::BrandsgatewayUser, self::BrandsgatewayPass]]);

        // Return results
        return $res->getStatusCode() === 200 ? json_decode($res->getBody())
            : $res->getStatusCode().': '.$res->getReasonPhrase();
    }

    /*
    |--------------------------------------------------------------------------
    | Send Shopwoo Productuct
    |--------------------------------------------------------------------------
    |
    | Posts a given product to shopwoo
    |
    | @param $shopwooProduct: The product to be posted
    | 
    | @returns - '201' if post was successful
    |          - A message containing the response code and reason
    |             phrase if posting failed
    |
    */
    public function sendShopwooProductuct($shopwooProduct) {

        // A valid $shopwooProduct should be an array of size 1! 
        if (!is_array($shopwooProduct) || sizeof($shopwooProduct) !== 1) return 'Invalid Product';

        // Post product
        $client = new Client();
        $res = $client->request('POST', self::ShopwooURL, [
            'auth' => [self::ShopwooUser, self::ShopwooPass],
            'json' => $shopwooProduct
            ]);

        // Return results
        return $res->getStatusCode() === 201 ? '201' : $res->getStatusCode().': '.$res->getReasonPhrase();
    }

    /*
    |--------------------------------------------------------------------------
    | Build Shopwoo Productuct
    |--------------------------------------------------------------------------
    |
    | Converts a 'brandsgateway' product object to a compartible 'shopify'
    | product object
    |
    | @param $brandsgatewayProduct: The product object to be converted
    | 
    | @returns - A 'shopwoo' product (ass as associative array) if convertion
    |              was successful
    |          - 'Invalid Product' if wrong parameter is passed in
    |
    */
    public function buildShopwooProductuct($brandsgatewayProduct) {

        // A valid $brandsgatewayProduct should be an object! 
        if (!is_object($brandsgatewayProduct)) return 'Invalid Product';

        $variants = [];
        $options = [];
        $images = [];
        $productVariations = $brandsgatewayProduct->variations;

        // Map images from brandsgateway to shopwoo
        $productImages = $brandsgatewayProduct->images;

        if (sizeof($productImages) > 0){
            foreach ($productImages as $image) {
                array_push($images, (object)["src"=> $image->src]);
            }
        }

        // Map attributes from brandsgateway to shopwoo options
        $productAttributes = $brandsgatewayProduct->attributes;
        $productAttributesSize = sizeof($productAttributes);

        if ($productAttributesSize > 0){
            // shopify product variants are only allowed 3 options so we map the first 3 attributes
            $count = $productAttributesSize < 3 ? $productAttributesSize : 3;
            
            for ($j=0; $j < $count ; $j++) {
                array_push($options, (object)[
                    "name"=> $productAttributes[$j]->name,
                    "values"=> $productAttributes[$j]->options
                    ]);
            }
        }

        // Map variantions from brandsgateway to shopwoo product variants
        if (sizeof($productVariations) > 0){
            foreach ($productVariations as $variation) {

                $variant = [
                    "created_at"=> $variation->date_created,
                    "inventory_quantity"=> $variation->stock_quantity,
                    "price"=> $variation->price,
                    "sku"=> $variation->sku,
                    "option1"=> null,
                    "option2"=> null,
                    "option3"=> null,
                    "updated_at"=> $variation->date_modified
                ];

                // If there is at least 1 option, we set the current variant's values for the option(s)
                $optionsSize = sizeof($options);

                if ($optionsSize > 0) {
                    $variationAttributes = $variation->attributes;
                    $variationAttributesSize = sizeof($variationAttributes);

                    // A shopify varaint must have values for all options
                    for ($k=0; $k < $optionsSize ; $k++) {
                        $valueNotSet = true;

                        // For each option, we check that this variant has a value (corresponding attribute)
                        // for it. 
                        for ($l=0; $l < $variationAttributesSize ; $l++) {
                            if ($options[$k]->name === $variationAttributes[$l]->name){

                                $variant["option".($k + 1)] = $variationAttributes[$l]->option;
                                $valueNotSet = false;
                                break;
                            }
                        }

                        // If this variation doesn't have a value for this option, set the default value
                        if ($valueNotSet) $variant["option".($k + 1)] = $options[$k]->values[0];
                    }
                }

                array_push($variants, (object)$variant);
            }
        }

        // Complete shopwoo product
        $shopwooProduct = array(
            "product"=> (object)array(
                "title"=> $brandsgatewayProduct->name,
                "body_html"=> $brandsgatewayProduct->description,
                "vendor"=> "Brandsgateway",
                "product_type"=> $brandsgatewayProduct->type,
                "variants"=> $variants,
                "images"=> $images,
                "options"=> $options
            )
        );

        // Return built product (as an associative array)
        return $shopwooProduct;
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */
    //Set $minNumberOfProducts
    public function setMinProducts($minNumberOfProducts) {
        if (!is_int($minNumberOfProducts)) return false;
        $this->minNumberOfProducts = $minNumberOfProducts;
    }
    
    //Set $productsPerPage
    public function setProductsPerPage($productsPerPage) {
        if (!is_int($productsPerPage)) return false;
        $this->productsPerPage = $productsPerPage;
    }
    
    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */
    //Get $minNumberOfProducts
    public function getMinProducts() {
        return $this->minNumberOfProducts;
    }
    
    //Get $productsPerPage
    public function getProductsPerPage() {
        return $this->productsPerPage;
    }
}
