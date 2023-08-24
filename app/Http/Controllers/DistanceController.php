<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DistanceController extends Controller
{
    //
    protected $allAddress = array(
                        "Adchieve HQ"=>"Sint Janssingel 92, 5211 DA 's-Hertogenbosch, The Netherlands",
                        "Eastern Enterprise B.V."=>"Deldenerstraat 70, 7551AH Hengelo, The Netherlands",
                        "Eastern Enterprise"=>"46/1 Office no 1 Ground Floor, Dada House, Inside dada silk mills compound, Udhana Main Rd, near Chhaydo Hospital, Surat, 394210, India",
                        "Adchieve Rotterdam"=>"Weena 505, 3013 AL Rotterdam, The Netherlands",
                        "Sherlock Holmes"=>"221B Baker St., London, United Kingdom",
                        "The White House"=>"1600 Pennsylvania Avenue, Washington, D.C., USA",
                        "The Empire State Building"=>"350 Fifth Avenue, New York City, NY 10118",
                        "The Pope"=>"Saint Martha House, 00120 Citta del Vaticano, Vatican City",
                        "Neverland"=>"5225 Figueroa Mountain Road, Los Olivos, Calif. 93441, USA",
    );
    
    protected $finalDataDownLoad;    
    public function index(){        
             
        $allLocationGQDetails = $this->getLocation();
        if(is_array($allLocationGQDetails)){

            $finalData = $this->getFormattedResponse($allLocationGQDetails);
            
        }
        //echo "<pre>";
        //print_r($finalData);
        asort($finalData);
        $this->finalDataDownLoad = $finalData;
        return View::make("distance/index")->with("finaldata", $finalData);
        
    }
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2){
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Distance in kilometers
        $distance = round($distance,2);

        return $distance;
    }

    protected function getLocation(){
        try {
            $apiResData = [];
            foreach($this->allAddress as $name=>$address){
                $url = env('POSITION_STACK').'/v1/forward?access_key='.env('POSITION_STACK_API_KEY').'&query='.$address.'&limit=1';
                $response = Http::get($url);
                $jsonData = $response->json();
                $apiResData[$name]=$jsonData['data'][0];                
                
                
            }
            
        } catch (\Exception $e) {
            //throw new HttpException(500, $e->getMessage());
            abort(403, "There was an error while accesing api. PLease refresh again");
        }
        return $apiResData;
        
    }
    protected function getFormattedResponse($allLocationGQDetails){
        $finalArrayObj = [];
        foreach($allLocationGQDetails as $key => $val){
            $hq_lat = $allLocationGQDetails["Adchieve HQ"]['latitude'];
            $hq_long = $allLocationGQDetails["Adchieve HQ"]['longitude'];
            if($key !="Adchieve HQ"){
      
                $distance = $this->calculateDistance(
                                $hq_lat,
                                $hq_long,
                                $val['latitude'],
                                $val['longitude']
                );
                $finalArrayObj[]= array(
                        "Distance"=>$distance,
                        "Name"=>$key,                        
                        "Address"=>$this->allAddress[$key],
                );
            } 
        }
        return $finalArrayObj;
    }

    protected function download(Request $request){
        $fileName = 'office.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=office-' . date("Y-m-d-h-i-s") . '.csv');
        $output = fopen('php://output', 'w');
      
        fputcsv($output, array('Sortnumber', 'Distance(kilometers)', 'Name','Address'));
        //die("Test download ");
        $allLocationGQDetails = $this->getLocation();
        if(is_array($allLocationGQDetails)){

            $finalData = $this->getFormattedResponse($allLocationGQDetails);
            
        }
        
        asort($finalData);
       
        //dd($finalData);
        
        if (count($finalData) > 0) {
            $i=1;
            foreach ($finalData as $data) {

                $data_row = [
                    $i,
                    $data['Distance'],
                    ucfirst($data['Name']),
                    $data['Address']
                ];

                fputcsv($output, $data_row);
                $i++;
            }
        }
    }
    

}
