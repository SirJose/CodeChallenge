<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\Each;


class PagesController extends Controller
{

    // Resize image to specified width in px
    function resizeImageToWidth( $imgURL, $width ){

        $imgSize = getimagesize($imgURL);

        $originalWidth = $imgSize[0];
        $originalHeight = $imgSize[1];

        // We keep aspect ratio
        $resizeRatio = $width/$originalWidth;
        $resizeWidth = $width;
        $resizeHeight = ceil($originalHeight*$resizeRatio);

        $originalImg = imagecreatefromstring(file_get_contents($imgURL));
        $resizedImg = imagecreatetruecolor($resizeWidth, $resizeHeight);

        // Resize image
        imagecopyresampled(
            $resizedImg, $originalImg,
            0, 0, 0, 0,
            $resizeWidth, $resizeHeight,
            $originalWidth, $originalHeight
        );

        // Get resized image data
        ob_start();
            imagejpeg($resizedImg);
            $imgData = ob_get_contents();
        ob_end_clean();

        return base64_encode( $imgData );
    }



    // Here we can redirect the user to different pages
    // Since we only have the main page we only define index
    public function index(){

        // POST request to retrieve list of designs
        $designs = Http::asForm()->post('https://appdsapi-6aa0.kxcdn.com/content.php?json=1', [
            'lang'          => 'de',
            'search_text'   => 'berlin',
            'currencyiso'   => 'EUR'
        ]);

        // Limit to 25 designs
        $designsList = array_slice($designs["content"], 0, 25);

        // Resizing and caching thumbnails
        foreach($designsList as $key => $design){
            $cacheDesignKey = "design".$design["id"];

            if(!Cache::has($cacheDesignKey)){
                Cache::put($cacheDesignKey, $this->resizeImageToWidth($design["thumb_url"], 200), now()->addDay());
            }

            $designsList[$key]["thumb_base64"] = Cache::get($cacheDesignKey);
        }

        // POST request to retrieve price for a Greeting Card with envelope
        // We pool 15 requests at a time to avoid errors
        // Doing this is faster than 1 by 1
        $designsProducts = array();
        for($i=0; $i<count($designsList); $i+=15){
            $requestList = array_slice($designsList, $i, 15);
            $designsProducts = array_merge($designsProducts, Http::pool(function (Pool $pool) use ($requestList) {
                foreach($requestList as $design){
                    $pool->as("DESIGN_".$design["id"])
                        ->asForm()
                        ->post('https://www.mypostcard.com/mobile/product_prices.php?json=1', [
                            'type'          => 'get_postcard_products',
                            'currencyiso'   => 'EUR',
                            'store_id'      => $design["id"]
                        ]);
                }
            }));
        }

        // Product options list
        $productOptions = array();

        foreach($designsList as $key => $design){
            // Save product options info for each design
            $designId = $design["id"];
            $designsList[$key]["greetcard_options"] = $designsProducts["DESIGN_".$designId]["products"][0]["product_options"];

            // Set product options list
            if(!count($productOptions)){
                foreach($designsProducts["DESIGN_".$designId]["products"][0]["product_options"] as $optionId => $option){
                    $productOptions[$optionId] = array(
                        "name" => $optionId,
                        "code" => $option["option_code"]
                    );
                }
            }
        }

        // Set currency (in this case only EURO)
        $currency = array(
            "name" => "Euro",
            "sign" => "€",
            "html" => "&euro;"
        );

        return view('table', [
            'designsList'       => $designsList,
            'productOptions'    => $productOptions,
            'selectedOption'    => "Envelope",
            'currency'          => $currency
        ]);
    }



    public function optionPrice(Request $request){

        $storeId = $request->input('store_id');
        $optionName = $request->input('option_name');

        $optionPrice = Http::asForm()->post('https://www.mypostcard.com/mobile/product_prices.php?json=1', [
            'type'          => 'get_postcard_products',
            'currencyiso'   => 'EUR',
            'store_id'      => $storeId
        ])["products"][0]["product_options"][$optionName]["price"];

        $response = array(
            "design_id" => $storeId,
            "option_price" => $optionPrice
        );

        return json_encode($response);
    }

}
