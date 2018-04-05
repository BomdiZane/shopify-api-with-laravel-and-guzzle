# PRODUCT TRANSFER

Transfer products from brandsgateway to shopwoo's shopify store

## SETUP

This is project uses Laravel framework so - I am assuming I don't have to walk through how to set that
up. If you would like to see the important files immediately you can find the main file (contains all
the logic) and test file in the following directories

- Main file -> app/Http/Controllers/ApiController.php
- Test file -> tests/Unit/APIControllerTest.php

## FIRST RUN

Once the environment is setup you can point your server to the public folder where you will find a
simple homepage with a plain button. Clicking this button will initialise the transfer process. 
You will get a success message (when the transfer is complete) or failure message
(describibg what went wrong). Alternatively, you can navigate to "/transferProducts" from the public 
folder to initialise the transfer.

## METHODS

The ApiController class has the following 4 main methods (not including getters and setters).

### transferProducts()

Initialises the transfer
* **@Returns** a success string if transfer succeeds or and array of error messages with details of problems encountered

### getBrandsgatewayData($page, $perPage)

Fetches products from brandsgateway
* **@Params**
    * **$page** [int]: The brandsgateway page from which to fetch products
    * **$perPage** [int]: The minimum number of products to fetch
 Both parameters have defaults set to 1.
* **@Returns** an array of brandsgateway products or an error string if failures occur

### buildShopwooProductuct($brandsgatewayProduct)

Converts/maps brandsgateway products to shopify products
* **@Params**
    * **$brandsgatewayProduct** [JSON Object]: The brandsgateway product to convert/map
* **@Returns** a shopify product [as an associative array] or an error string if failures occur

### sendShopwooProductuct($shopwooProduct)

Posts the shopify product to shopwoo's store
* **@Params**
    * **$shopwooProduct** [Array]: The shopify product to post
* **@Returns** a '201' success code string if post is successful or an error message otherwise

Details of these functions can be observed in their definitions in the main file


