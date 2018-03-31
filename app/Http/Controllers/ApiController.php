<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class ApiController extends Controller
{
    public function getData(){

        // Initialize variables
        $brandsGUser = '7733';
        $brandsGPass = '52zAcz%2WkBR';
        $options = [
            "headers" => [
                "content-type" => "application/json",
                "Accept" => "application/json"
                ]
        ];
        $errors = [];

        // The brandsgateway API returns maximum of 100 products per page
        // so we have to visit 2 pages to get 150 products
        for ($i=1; $i < 3; $i++) { 
            $brandsGURL = 'https://v1.api.brandsgateway.com/products?per_page=75&page='.$i;
            
            // Create request
            $client = new Client($options);
            $res = $client->get($brandsGURL, ['auth' => [$brandsGUser, $brandsGPass]]);
    
            // Handle response
            if ($res->getStatusCode() === 200){
    
                $data = json_decode($res->getBody());
    
                // Prepare and send brandsgateway products to shopwoo
                foreach ($data as $product) { // 30 products per page
                    $result = ApiController::sendShopwooProductuct(ApiController::buildShopwooProductuct($product));
                    if ($result !== '201') array_push($errors, $result);
                }
            }
            else array_push($errors, $res->getStatusCode().': '.$res->getReasonPhrase());
        }

        return sizeof($errors) > 0 ? sizeof($errors)." errors!\n".json_encode($errors) : 'Success!';
    }

    public function buildShopwooProductuct($product) {

        // $product should be an object! 
        if (!is_object($product)) return 'Invalid Product';

        $variants = [];
        $options = [];
        $images = [];
        $productVariations = $product->variations;

        // Map variantions from brandsgateway to shopwoo product variants
        if (sizeof($productVariations) > 0){
            foreach ($productVariations as $variation) {
                array_push($variants, (object)[
                    "created_at"=> $variation->date_created,
                    "inventory_quantity"=> $variation->stock_quantity,
                    "price"=> $variation->price,
                    "sku"=> $variation->sku,
                    "updated_at"=> $variation->date_modified
                ]);

                // If current variation has attributes, we map those to shopwoo options
                $variationAttributes = $variation->attributes;
                if (sizeof($variationAttributes) > 0){
                    foreach ($variationAttributes as $attribute) {
                        // brandsgateway products can have multiple variations with same
                        // attribute name. shopify product options however must have unique
                        // names, so we only add unique attributes
                        $unique = true;
                        for ($i=0; $i < sizeof($options); $i++) { 
                            if ($options[$i]->name === $attribute->name){
                                $unique = false;
                                break;
                            }
                        }
                        if ($unique){
                            array_push($options, (object)[
                                "name"=> $attribute->name,
                                "values"=> $attribute->option
                            ]);
                        }
                    }
                }
            }
        }

        // Map images from brandsgateway to shopwoo
        $productImages = $product->images;
        if (sizeof($productImages) > 0){
            foreach ($productImages as $image) {
                array_push($images, (object)[
                    "src"=> $image->src,
                ]);
            }
        }

        // Complete shopwoo product
        $shopwooProduct = array(
            "product"=> (object)array(
                "title"=> $product->name,
                "body_html"=> $product->description,
                "vendor"=> "Brandsgateway",
                "product_type"=> $product->type,
                "variants"=> $variants,
                "options"=> $options,
                "images"=> $images
            )
        );

        // Return built product
        return $shopwooProduct;
    }

    public function sendShopwooProductuct($shopwooProduct){

        // A valid product should be an array of size 1! 
        if (!is_array($shopwooProduct) || sizeof($shopwooProduct) !== 1) return 'Invalid Product';

        // Initialize variables
        $shopwooURL = 'https://shopwoo-developer-test.myshopify.com/admin/products.json';
        $shopwooUser = '8675c061ca380a43a66b30a5f4a0ff82';
        $shopwooPass = '09dbbf87a47d5f3e52fbada1012adb55';

        // Post product
        $client = new Client();
        $res = $client->request('POST', $shopwooURL, [
            'auth' => [$shopwooUser, $shopwooPass],
            'json' => $shopwooProduct
            ]);

        // Return results
        return $res->getStatusCode() === 201 ? '201' : $res->getStatusCode().': '.$res->getReasonPhrase();
    }
}
