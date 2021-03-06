<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Mobile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search(Request $request){
        $this->validate($request, [
            'search_text' => 'required|min:1|max:20',
            'radius' => 'min:1|max:50'
        ]);

        $searchText = $request->input('search_text');
        //if user specifies the market location
        $marketLocation = $request->input('market_location');
        //if user specifies his/her location
        $userLocation = $request->input('user_location');
        $radius = $request->input('radius');
        $lat = $request->input('lat');
        $long = $request->input('long');
        $userLat = $request->input('user_lat');
        $userLong = $request->input('user_long');
        $offset = $request->input('page');
        $offset = 10*($offset - 1);
        $locationController = new LocationController();
        $controller = new Controller();
        //get the first mobile and then get the first mobile data
        $mobiles = Mobile::where('title', 'LIKE', '%' . $searchText . '%')
            ->select('title')
            ->groupBy('title')
            ->offset($offset)
            ->limit(20)
            ->get();
        /*$newMobiles = null;
        $brandMobiles = null;
        if($mobiles->count() == 0){
            $search = explode(' ', $searchText);
            if(isset($search[0])){
                if(!empty($search[0]) || $search[0] != null){
                    $brand = Brand::where('name', ucwords($search[0]))->first();
                    if($brand){
                        $brandMobilesCount = $brand->mobiles()->count();
                        if($brandMobilesCount > 0){
                            $brandMobiles = $brand->mobiles()->where(function ($q) use ($search){
                                $count = 1;
                                foreach ($search as $s){
                                    if($count == 1){
                                        continue;
                                    }
                                    $q->orWhere('title', 'LIKE', '%' . $s . '%');
                                    ++$count;
                                }
                            })->select('title')
                                ->get();
                            dd($brandMobiles);
                        }
                    }
                }
            }
            if($brandMobiles->count() == 0){
                $newMobiles = Mobile::where(function ($q) use ($search){
                    foreach ($search as $s){
                        $q->orWhere('title', 'LIKE', '%' . $s . '%');
                    }
                })
                    ->select('title')
                    ->offset($offset)
                    ->get();
            }
        }
        $mobiles = $brandMobiles == null ? $mobiles : $mobiles->merge($brandMobiles);
        $mobiles = $newMobiles == null ? $mobiles : $mobiles->merge($newMobiles);*/
        $data = array();
        $marketLocationMatchedCount = 0;
        foreach ($mobiles as $index => $m){
            $mobile = Mobile::where('title', $m->title)->first();


            //get mobile data
            //contains the shop id as well
            //would return collection
            $mobileData = $mobile->data;
            $price = 999999999999;
            $onlineShopPrice = 999999999999;
            $distance = 999999999999;
            $addMobileSinceWithinRadiusLimit = false;
            $l = null;
            $o = null;
            $available = null;
            $shopLat = null;
            $shopLong = null;
            $marketLocationMatched = false;
            $distanceForUserSpecifiedLocation = null;

            //there would be multiple rows for one iphone 7 say,
            //10 shops having iphone 7 so we need to get the min
            //price only
            foreach ($mobileData as $item){
                $shopPrice = preg_replace("/[^\\d]+/", "", $item->current_price);
                //if user has specified a radius then
                //the shops within that radius must show
                //irrespective of the min priced product
                //which is further than the specified radius

                if ($item->local_online == 'l'){
                    if($radius !== '0'){
                        $temp = $locationController->getDistance($item->shop->lat, $item->shop->long, $controller->generalLat, $controller->generalLong);

                        //if shop distance is less than the
                        //radius specified and shop price is
                        //also less than the previous price
                        if($temp < $distance && $temp < $radius){
                            if($shopPrice < $price) {
                                $l = $item->shop->location;
                                $distance = $temp;
                                $shopLat = $item->shop->lat;
                                $shopLong = $item->shop->long;
                                $price = $shopPrice;
                            }
                        }
                    }
                    elseif ($radius == '0'){
                        //get the minimum price and
                        //get the location of that specific shop
                        if($shopPrice < $price){
                            $price = $shopPrice;

                            $distance = $locationController->getDistance($item->shop->lat, $item->shop->long, $controller->generalLat, $controller->generalLong);
                            $l = $item->shop->location;
                            $shopLat = $item->shop->lat;
                            $shopLong = $item->shop->long;
                        }
                        //shops have same value then
                        //show that shop which has min
                        //distance from user
                        elseif ($shopPrice == $price){
                            //check if the item is available
                            //online or local or both
                            $temp = $locationController->getDistance($item->shop->lat, $item->shop->long, $controller->generalLat, $controller->generalLong);

                            //if new shop which has same price
                            //has less distance then update the location
                            if($temp < $distance){
                                $price = $shopPrice;
                                $l = $item->shop->location;
                                $distance = $temp;
                                $shopLat = $item->shop->lat;
                                $shopLong = $item->shop->long;
                            }
                        }
                    }

                    //check if the shop location matched with the user specified location
                    //if user specified the location
                    if ($lat != null && $long != null && round($item->shop->lat, 4) == round($lat, 4) && round($item->shop->long, 4) == round($long, 4)){
                        //dd("I am matched", $item->mobile->title, $item->mobile->id);
                        $l = $marketLocation;
                        $marketLocationMatched = true;
                        ++$marketLocationMatchedCount;


                        //if user has specified target location then
                        //calculate the distance from that shop
                        $lat2 = $userLat == null ? $controller->generalLat : $userLat;
                        $long2 = $userLong == null ? $controller->generalLong : $userLong;
                        $distanceForUserSpecifiedLocation = $locationController->getDistance($item->shop->lat, $item->shop->long, $lat2, $long2);
                    }
                }else {
                    $o = 'online';
                    if($shopPrice < $onlineShopPrice) {
                        $onlineShopPrice = $shopPrice;
                    }
                }

            }

            //if user has specified radius
            //which is not equal to 0
            //if distance is null then
            //mobile is not available on
            //local shops
            if($radius != "0" && isset($distance)){
                //if shop and user distance
                //if greater than
                if(!($distance <= $radius)){
                    $addMobileSinceWithinRadiusLimit = false;
                }
                else{
                    $addMobileSinceWithinRadiusLimit = true;
                }
            }

            if(!empty($l) && !empty($o)){
                $available = 'both';
            }
            elseif (!empty($o) && $o == 'online')
                $available = 'online';
            elseif (!empty($l))
                $available = 'local';

            //defaults to true if radius was
            //not specified or mobile location
            //falls within radius range
            if($radius !== '0' && $addMobileSinceWithinRadiusLimit){
                $mobile->shop_lat = $shopLat;
                $mobile->shop_long = $shopLong;
                $data[$index]['mobile'] = $mobile;
                $data[$index]['data'] = $mobileData;
                $data[$index]['price'] = $price;
                $data[$index]['online_price'] = $onlineShopPrice == 999999999999 ? null : $onlineShopPrice;
                $data[$index]['available'] = $available;
                $data[$index]['location'] = $l;
                $data[$index]['distance'] = $distance;
            }
            //user did not specify the radius
            elseif ($radius == '0'){
                //user has specified market location
                //then add only those results
                //which are matched
                if($marketLocation != null && $marketLocationMatched){
                    $mobile->shop_lat = $shopLat;
                    $mobile->shop_long = $shopLong;
                    $data[$index]['mobile'] = $mobile;
                    $data[$index]['data'] = $mobileData;
                    $data[$index]['price'] = $price;
                    $data[$index]['online_price'] = $onlineShopPrice == 999999999999 ? null : $onlineShopPrice;
                    $data[$index]['available'] = $available;
                    $data[$index]['location'] = $marketLocation;
                    $data[$index]['distance'] = $distanceForUserSpecifiedLocation;
                }
                elseif ($marketLocation == null){
                    $mobile->shop_lat = $shopLat;
                    $mobile->shop_long = $shopLong;
                    $data[$index]['mobile'] = $mobile;
                    $data[$index]['data'] = $mobileData;
                    $data[$index]['price'] = $price;
                    $data[$index]['online_price'] = $onlineShopPrice == 999999999999 ? null : $onlineShopPrice;
                    $data[$index]['available'] = $available;
                    $data[$index]['location'] = $l;
                    $data[$index]['distance'] = $distance;
                }
            }
        }
        //Get current page form url e.g. &page=6
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        //Create a new Laravel collection from the array data
        $collection = new Collection($data);

        //Define how many items we want to be visible in each page
        $perPage = 20;

        //Slice the collection to get the items to display in current page
        $currentPageSearchResults = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        //Create our paginator and pass it to the view
        $count = count($data);
        $paginatedSearchResults = new LengthAwarePaginator($data, $count, $perPage);
        $this->storeLatLong($userLat, $userLong);
        return view('frontend.search-results', compact('searchText'))->withMobiles($paginatedSearchResults)
            ->with([
                'userLat' => $userLat,
                'userLong' => $userLong,
                'marketLat' => $lat,
                'marketLong' => $long,
                'marketLocation' => $marketLocation,
                'userLocation' => $userLocation,
                'marketLocationMatchedCount' => $marketLocationMatchedCount,
                'resultsCount' => $count,
                'radius' => $radius
            ]);
    }



    /**
     * @param $shopLocation
     * @param $userLocation
     * @return mixed
     */
    public function getDistance($shopLocation, $userLocation){

        $coordinates1 = $this->get_coordinates($userLocation);
        $coordinates2 = $this->get_coordinates($shopLocation);
        if ( !$coordinates1 || !$coordinates2 )
        {
            echo 'Bad address.';
        }
        else
        {
            $dist = $this->GetDrivingDistance($coordinates1['lat'], $coordinates2['lat'], $coordinates1['long'], $coordinates2['long']);
            return $dist['distance'];
        }
    }



    /**
     * returns the driving distance from
     * user location to the shop
     * @param $lat1
     * @param $lat2
     * @param $long1
     * @param $long2
     * @return array
     */
    function GetDrivingDistance($lat1, $lat2, $long1, $long2)
    {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$lat1.",".$long1."&destinations=".$lat2.",".$long2."&mode=driving&language=pl-PL";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response, true);
        $dist = $response_a['rows'][0]['elements'][0]['distance']['text'];
        $time = $response_a['rows'][0]['elements'][0]['duration']['text'];

        return array('distance' => $dist, 'time' => $time);
    }



    /**
     * get the coordinates for
     * calculating the distance
     * @param $city
     * @return array|bool
     */
    function get_coordinates($city)
    {
        $address = urlencode($city);
        $url = "http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=Poland";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);
        $status = $response_a->status;

        if ( $status == 'ZERO_RESULTS' )
        {
            return FALSE;
        }
        else
        {
            $return = array('lat' => $response_a->results[0]->geometry->location->lat, 'long' => $long = $response_a->results[0]->geometry->location->lng);
            return $return;
        }
    }


    /**
     * return mobile titles for search box
     * @param Request $request
     */
    public function liveSearch(Request $request){
        $value = Cache::remember($request->input('q'), 30, function () use ($request){
            return Mobile::where('title', 'LIKE', '%' . $request->input('q') . '%')->pluck('title', 'id')->toArray();
        });

        return $value;
    }


    /**
     * stores the lat long
     * for user location
     * to be used in map
     * on single phone pages
     *
     * @param $userLat
     * @param $userLong
     */
    public function storeLatLong($userLat, $userLong){

        //if user lat long is null
        //remove the data from session
        if(!($userLat && $userLong)){
            session()->forget('user_lat');
            session()->forget('user_long');
        }
        else{
            session([
                'user_lat' => $userLat,
                'user_long' => $userLong
            ]);
        }
    }
}
