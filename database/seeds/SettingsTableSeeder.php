<?php

use Illuminate\Database\Seeder;
use App\Model\PermissionCategory;
use \App\Model\Setting;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */



    public function run()
    {
		$settingsArr = [
            "company_name" => "EZ POS",
            "company_logo" => "logo.png",
            "tax_rate" => 15,
            "address_line_1" => "",
            "address_line_2" => "",
            "email_address" => "",
            "phone" => "",
            "website" => "",
            "customer_loyalty_percentage" => "1",
            "negative_inventory" => false,
            "scan_price_from_barcode" => false,
            "upc_code_prefix" => "200",
            "item_size" => "lbs",
            "default_opening_amount" => "100",
            "session_lifetime" => 15
        ];

		foreach( $settingsArr as $key=>$value )
		{
			$aSettings = Setting::where("key",$key)->first();
			if( !$aSettings )
			{
				$setting = new \App\Model\Setting();
				$setting->key = $key;
				$setting->value = $value;
				$setting->save();

                \App\Libraries\ConfigUpdater::updateDotEnv('session','lifetime',$settingsArr['session_lifetime']);
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('config:cache');
			}
		}
    }
}
