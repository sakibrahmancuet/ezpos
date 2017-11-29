<?php

namespace App\Http\Controllers;

use App\Enumaration\ItemStatus;
use App\Enumaration\PriceRuleTypes;
use App\Model\Category;
use App\Model\File;
use App\Model\Item;
use App\Model\ItemKit;
use App\Model\ItemsImage;
use App\Model\Manufacturer;
use App\Model\Supplier;
use Faker\Provider\zh_CN\DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Excel;

class ItemController extends Controller
{
    public function GetItemForm()
    {

        //Load all permissions from database
        $categoryList = Category::orderBy('category_name')->get();

        $supplierList = Supplier::all();

        $manufacturerList = Manufacturer::all();

        return view('items.new_item',['categoryList'=>$categoryList,'supplierList'=>$supplierList,'manufacturerList'=>$manufacturerList]);
    }

    public function AddItem(Request $request)
    {

        $this->validate($request, [
            'item_name' => 'required',
            'item_category' => 'required',
            'item_supplier' => 'required',
            'reorder_level' => 'sometimes|nullable|integer',
            'replenish_level' => 'sometimes|nullable|integer',
            'expire_days' => 'sometimes|nullable|integer',
            'cost_price' => 'required|numeric',
            'unit_price' => 'required|numeric'
        ]);

        $item = new Item();
        $item->InsertItem($request);

        return redirect()->route('item_list');

    }


    public function GetItemList()
    {


        $allItems = DB::table('items')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')->leftJoin('suppliers', 'items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'items.manufacturer_id', '=', 'manufacturers.id')
            ->select('items.id as item_id', 'items.*', 'suppliers.*','categories.*','manufacturers.*')
            ->where('product_type',0)
            ->where('product_type','<>',2)
            ->whereNull('items.deleted_at')
            ->get();

        $activeItems = DB::table('items')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')->leftJoin('suppliers', 'items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'items.manufacturer_id', '=', 'manufacturers.id')
            ->select('items.id as item_id', 'items.*', 'suppliers.*','categories.*','manufacturers.*')
            ->where('item_status',ItemStatus::$ACTIVE)
            ->where('product_type',0)
            ->where('product_type','<>',2)
            ->whereNull('items.deleted_at')
            ->get();

        $inactiveItems = DB::table('items')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')->leftJoin('suppliers', 'items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'items.manufacturer_id', '=', 'manufacturers.id')
            ->select('items.id as item_id', 'items.*', 'suppliers.*','categories.*','manufacturers.*')
            ->where('item_status',ItemStatus::$INACTIVE)
            ->where('product_type',0)
            ->where('product_type','<>',2)
            ->whereNull('items.deleted_at')
            ->get();

        $draftItems = DB::table('items')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')->leftJoin('suppliers', 'items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'items.manufacturer_id', '=', 'manufacturers.id')
            ->select('items.id as item_id', 'items.*', 'suppliers.*','categories.*','manufacturers.*')
            ->where('item_status',ItemStatus::$DRAFTED)
            ->where('product_type',0)
            ->where('product_type','<>',2)
            ->whereNull('items.deleted_at')
            ->get();

        return view('items.item_list', ["allItems" => $allItems, "activeItems"=>$activeItems,
                                  "inactiveItems"=>$inactiveItems, "draftItems"=>$draftItems]);
    }

    public function EditItemGet($itemId)
    {


        $categoryList = Category::orderBy('category_name')->get();

        $supplierList = Supplier::all();

        $manufacturerList = Manufacturer::all();

        $itemInfo = DB::table('items')
            ->leftJoin('categories', 'items.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'items.supplier_id', '=', 'suppliers.id')
            ->leftJoin('manufacturers', 'items.manufacturer_id', '=', 'manufacturers.id')
            ->where('items.id', '=', $itemId)->select('items.id as item_id', 'items.*', 'suppliers.*','categories.*','manufacturers.*')
            ->first();

        $item = new Item();
        $images = $item->getItemImages($itemId);

       return view('items.item_edit', ['item' => $itemInfo, 'categoryList' => $categoryList,'supplierList'=>$supplierList,'manufacturerList'=>$manufacturerList,'images'=>$images]);
    }

    public function DeleteItemImage($item_id,$image_id){


        $item_image = ItemsImage::where('item_id','=',$item_id)->where('file_id','=',$image_id)->first();
        $item_image->delete();

        return redirect()->route('item_edit',['item_id'=>$item_id]);
    }


    public function  EditItemPost(Request $request, $itemId)
    {


        /* var_dump($item);*/
        $this->validate($request, [
            'item_name' => 'required',
            'item_category' => 'required',
            'item_supplier' => 'required',
            'reorder_level' => 'sometimes|nullable|integer',
            'replenish_level' => 'sometimes|nullable|integer',
            'expire_days' => 'sometimes|nullable|integer',
            'cost_price' => 'required|numeric',
            'unit_price' => 'required|numeric'
        ]);

        $item = new Item();
        $item->editItem($request,$itemId);
        return redirect()->route('item_list');

    }


    public function GetItemsAutocomplete(){

        $search_param = (string) '%'.Input::get('q').'%';

        // Get all items with images

        $items =  DB::table('items')
                ->leftJoin('items_images', 'items.id', '=', 'items_images.item_id')
                ->leftJoin('files', 'files.id', '=', 'items_images.file_id')
                ->leftJoin('item_price_rule','items.id','=','item_price_rule.item_id')
                ->leftJoin('price_rules','item_price_rule.price_rule_id','=','price_rules.id')
                ->leftJoin('suppliers','suppliers.id','=','items.supplier_id')
                ->where(function($query) use ($search_param) {
                    $query->where('item_name','LIKE',$search_param)
                        ->orWhere('isbn','LIKE',$search_param);
                })
                ->where('items.deleted_at',null)
                ->where('items.item_status',ItemStatus::$ACTIVE)
                ->select('items.id as item_id','items.*','files.*','price_rules.*','suppliers.*')
                ->groupBy('items.item_name')
                ->where('items.product_type','<>',2)
                ->get()->toArray();


        // Get all item kits


        //dd($itemKits);

        //Merge Item Kits with Items
        $itemsWithItemKits =$items;


        $current_date = new \DateTime('today');
        // Check price rules on specific items
        foreach($itemsWithItemKits as $anItem) {

                if(isset($anItem->id)){

                    if ($anItem->active){

                        if($anItem->unlimited||$anItem->num_times_to_apply>0)
                        {

                            if($anItem->type==1){

                                if($anItem->percent_off>0){

                                    $rule_start_date = new \DateTime($anItem->start_date);
                                    $rule_expire_date = new \DateTime($anItem->end_date);

                                    if(($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) ) {
                                        $discountPercentage = $anItem->percent_off;
                                        if($discountPercentage>100){
                                            $anItem->discountPercentage = 100;
                                            $anItem->itemPrice = $anItem->selling_price;
                                            $anItem->discountName = $anItem->name;
                                            $anItem->discountAmount = $anItem->itemPrice*($discountPercentage/100);
                                            $anItem->itemPriceAfterDiscount = $anItem->itemPrice-$anItem->discountAmount;
                                            $anItem->discountApplicable = true;
                                        }else{
                                            $anItem->discountPercentage = $discountPercentage;
                                            $anItem->itemPrice = $anItem->selling_price;
                                            $anItem->discountName = $anItem->name;
                                            $anItem->discountAmount = $anItem->itemPrice*($discountPercentage/100);
                                            $anItem->itemPriceAfterDiscount = $anItem->itemPrice-$anItem->discountAmount;
                                            $anItem->discountApplicable = true;
                                        }

                                    }else{
                                        $anItem->discountApplicable = false;
                                    }

                                    //echo "Item should be discounted by ".$anItem->percent_off." percent";

                                }else if($anItem->fixed_of>0){

                                    $rule_start_date = new \DateTime($anItem->start_date);
                                    $rule_expire_date = new \DateTime($anItem->end_date);

                                    if( ($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) ) {
                                        $discountPercentage = ($anItem->fixed_of/$anItem->selling_price)*100;
                                        if($discountPercentage>100){
                                            $anItem->discountPercentage = 100;
                                            $anItem->discountAmount = $anItem->selling_price;
                                            $anItem->discountName = $anItem->name;
                                            $anItem->itemPrice = $anItem->selling_price;
                                            $anItem->itemPriceAfterDiscount = $anItem->itemPrice - $anItem->itemPrice;
                                            $anItem->discountApplicable = true;
                                        }
                                        else{
                                            $anItem->discountPercentage = $discountPercentage;
                                            $anItem->discountAmount = $anItem->fixed_of;
                                            $anItem->discountName = $anItem->name;
                                            $anItem->itemPrice = $anItem->selling_price;
                                            $anItem->itemPriceAfterDiscount = $anItem->itemPrice - $anItem->discountAmount;
                                            $anItem->discountApplicable = true;
                                        }

                                    }else{
                                        $anItem->discountApplicable = false;
                                    }
                                   // echo "Item should be discounted by ".$anItem->fixed_of." dollar";
                                }

                            }
                        }

                    }


                }
        }


     /*   if(!is_null($anItem->active)){

            if($anItem->unlimited||$anItem->num_times_to_apply){

                if($anItem->type==1){
                    echo "Simple Discount";


                }else{

                }

            }

        }*/



        // Check price rules on categories



        // Check price rules on item kits



         // return response()->json($itemsWithItemKits);
           echo json_encode($itemsWithItemKits);
       // return response()->json(['success' => true,'items'=>$items], 200);

    }



    public function DeleteItemGet($item_id){
        $item = new Item();
        $item->DeleteItem($item_id);

        return redirect()->route('item_list');
    }

    public function importExcelGet(){

        return view('items.item_import_excel');
    }

    public function importExcel(Request $request)
    {

        if(Input::hasFile('import_file')){
            $path = Input::file('import_file')->getRealPath();
            $extension = $request->import_file->getClientOriginalExtension();
            if ($extension == "xlsx" || $extension == "xls" || $extension == "csv") {

                $data = Excel::load($path, function($reader) {
                })->get();
                if(!empty($data) && $data->count()){
                   /* dd($data);*/
                    $insert = array();
                    foreach ($data as $key => $value) {

                        $supplier_id = 0; $category_id=0;$manufacturer_id=0;
                        if(isset($value->supplier)){

                            $supplier = Supplier::where("company_name",$value->supplier)->first();
                            if(!is_null($supplier)){
                                $supplier_id = $supplier->id;
                            }
                            else{
                                $supplier = new Supplier();
                                $supplier->company_name = $value->supplier;
                                $supplier->save();
                                $supplier_id = $supplier->id;
                            }

                        }if(isset($value->category)){
                            $category = Category::where("category_name",$value->category)->first();
                            if(!is_null($category)){
                                $category_id = $category->id;
                            }
                            else{
                                $category = new Category();
                                $category->category_name = $value->category;
                                $category->save();
                                $category_id = $category->id;
                            }

                        }if(isset($value->manufacturer)){
                            $manufacturer = Manufacturer::where("manufacturer_name",$value->manufacturer)->first();
                            if(!is_null($manufacturer)){
                                $manufacturer_id = $manufacturer->id;
                            }else{
                                $manufacturer = new Manufacturer();
                                $manufacturer->manufacturer_name = $value->manufacturer;
                                $manufacturer->save();
                                $manufacturer_id = $manufacturer->id;
                            }

                        }


                        if($value->isbn!=null&&$value->name!=null
                            &&$value->cost!=null&&$value->sale!=null){

                           $data = [
                                'isbn' => $value->isbn, 'product_id' => $value->product_id,
                                'item_name'=> $value->name, 'category_id' => $category_id,
                                'supplier_id' => $supplier_id,'manufacturer_id' => $manufacturer_id,
                                'item_size'=>$value->size, "item_quantity"=>$value->quantity,
                                "cost_price"=>$value->cost, "selling_price"=>$value->sale,

                            ];
                           array_push($insert, $data);
                        }



                       // echo $value->cost;
                    }

                    if(!empty($insert)){
                        DB::table('items')->insert($insert);
                        return redirect()->route('item_list');
                    }
                }

            }else {
                return redirect()->route('item_import_excel')->withErrors("Only xls or csv files are allowed.");
            }

        }
        return back();
    }

    public function DeleteItems(Request $request){

        $item_list = $request->id_list;
        if(DB::table('items')->whereIn('id',$item_list)->delete())
            return response()->json(["success"=>true],200);
        return response()->json(["success"=>false],200);

    }
    
}
