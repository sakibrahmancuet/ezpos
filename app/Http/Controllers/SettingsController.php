<?php

namespace App\Http\Controllers;

use App\Libraries\ConfigUpdater;
use App\Library\SettingsSingleton;
use App\Model\CurrencyDenomination;
use App\Model\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;


class SettingsController extends Controller
{

    public function GetSettings(){
        $denominators = CurrencyDenomination::all();
        return view("settings",["settings","denominators"=>$denominators]);
    }


    public function SaveSettings(Request $request){


        $requestArray = [
            'upc_code_prefix' => 'required_with:scan_price_from_barcode,on',
            "tax_rate" => "required",
            "customer_loyalty_percentage" => "required"
        ];

        if(UserHasPermission("update_settings_table_data")) {
            $request["company_name"] = $request->company_name;
        }

        $this->validate($request,$requestArray);



        $settingsChange =$request->except(['_token','image','denomination_name','denomination_value']);

        foreach($settingsChange as $key=>$value){
            if($key=="company_name"){
                if(isset($request->company_name))
                    SettingsSingleton::set($key,$value);
            }
            else
                SettingsSingleton::set($key,$value);
        }

        $file = $request->file('image');

        if ($file) {
            $image = Image::make($file)->stream();
            Storage::disk('images')->put("logo" . '.png', $image);
        }

        CurrencyDenomination::truncate();

        $denomintaionNames = $request->denomination_name;
        $denomintaionValues = $request->denomination_value;

        if(!empty($denomintaionNames))
        for($i = 0; $i<sizeof($denomintaionNames); $i++){
            if(is_null($denomintaionNames[$i]))
                $denomintaionNames[$i] = "";
            $array = array(
                "denomination_name"=>$denomintaionNames[$i],
                "denomination_value"=>$denomintaionValues[$i]
            );
            CurrencyDenomination::create($array);
        }

        $redirectUrl = route('change_settings');

        ConfigUpdater::updateDotEnv('session','lifetime',$settingsChange['session_lifetime']);
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('config:cache');

        return redirect($redirectUrl);
    }

}
