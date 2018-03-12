<?php

namespace App\Model;

use App\CashRegisterTransaction;
use App\Enumaration\CashRegisterTransactionType;
use App\Enumaration\InventoryReasons;
use App\Enumaration\InventoryTypes;
use App\Enumaration\LotyaltyTransactionType;
use App\Enumaration\SaleStatus;
use App\Library\SettingsSingleton;
use App\Model\Item;
use App\Model\PaymentLog;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Model\LoyaltyTransaction;

class Sale extends Model
{
    use SoftDeletes;

    public function Items(){
        return $this->belongsToMany('App\Model\Item')->withPivot('quantity','unit_price',
            'total_price','discount_amount','item_discount_percentage')->with('pricerule', 'Category','Supplier');
    }

    public function ItemKits(){
        return $this->belongsToMany('App\Model\ItemKit','item_kit_sale','item_kit_id','sale_id')->withPivot('quantity','unit_price',
            'total_price','discount_amount','item_discount_percentage')->with('PriceRule');
    }

    public function PaymentLogs(){
        return $this->belongsToMany('App\Model\PaymentLog');
    }

    public function Customer(){
        return $this->belongsTo('App\Model\Customer');
    }

    public function Employee(){
        return $this->belongsTo('App\Model\User');
    }

    public function counter(){
        return $this->belongsTo('App\Model\Counter');
    }

    public function InsertSale($saleInfo, $productInfos,$paymentInfos , $saleStatus){

        if($saleStatus!=1)
            session()->put('success','Sale has been successfully suspended');

        $sale = new Sale();
        $sale->employee_id = Auth::user()->id;
        $sale->customer_id = $saleInfo['customer_id'];
        $sale->sub_total_amount = $saleInfo['subtotal'];
        $sale->tax_amount = $saleInfo['tax'];
        $sale->total_amount = $saleInfo['total'];
        $sale->sales_discount = $saleInfo['discount'];
        $sale->sale_status = $saleStatus;
        $sale->due = $saleInfo['due'];
        $sale->profit = $saleInfo['profit'];
        $sale->items_sold = $saleInfo['items_sold'];
        $sale->sale_type = $saleInfo['sale_type'];
        $sale->counter_id = Cookie::get("counter_id");
        $sale->comment = $saleInfo["comment"];
        $sale->save();

        $sale_id = $sale->id;

        foreach($productInfos as $aProductInfo){

            $item_id = $aProductInfo['item_id'];
            $item_quantity = $aProductInfo['quantity'];
            $item_type = $aProductInfo["item_type"];
            if($item_id==0){
                $keyId = "discount-01!XcQZc003ab";
                $item = Item::where("product_id",$keyId)->first();

                if(is_null($item)){

                        $item = new Item();
                        $item->product_id=$keyId;
                        $item->item_name = "discount";
                        $item->category_id = 0;
                        $item->supplier_id = 0;
                        $item->product_type = 2;
                        $item->cost_price = $aProductInfo["unit_price"];
                        $item->selling_price = $aProductInfo["unit_price"];
                        $item->save();
                        $itemId = $item->id;

                }else
                    $itemId = $item->id;

                    $aProductInfo['item_id'] = $itemId;
                    $sale->Items()->attach([$itemId=>$aProductInfo]);



            }else{
                if($saleStatus!=SaleStatus::$ESTIMATE){
                    if($item_type=="item"){
                        $item = Item::where("id",$item_id)->first();
                        $previous_item_quantity = $item->item_quantity;
                        $item->item_quantity -= $item_quantity;
                        $item->save();

                        $current_item_quantity = $item->item_quantity;
                        $quantity_change = $current_item_quantity - $previous_item_quantity;

                        if($quantity_change!=0){
                            $inventoryLog = new InventoryLog();
                            $inventoryLog->item_id = $item->id;
                            $inventoryLog->in_out_quantity = $quantity_change;
                            if($quantity_change>0)
                                $inventoryLog->type = InventoryTypes::$ADD_INVENTORY;
                            else
                                $inventoryLog->type = InventoryTypes::$SUBTRACT_INVENTORY;

                            $inventoryLog->reason = InventoryReasons::$SALEORRETURN." (<a href=". route('sale_receipt',["sale_id"=>$sale_id]) .">EZPOS ".$sale_id."</a>)";
                            $inventoryLog->user_id = Auth::user()->id;
                            $inventoryLog->save();
                        }

                    }else if($item_type=="item-kit"){
                        $itemKit = ItemKit::where("id",$item_id)->first();
                        $itemKitProduct = ItemKit::where("id",$itemKit->id)->get();
                        foreach($itemKitProduct as $anItem){

                            $item = Item::where("id",$anItem->item_id)->where('deleted_at','null')->first();
                            if(!is_null($item)&&!isEmpty($item)){
                                $previous_item_quantity = $item->item_quantity;
                                $item->item_quantity -= $item_quantity;
                                $item->save();

                                $current_item_quantity = $item->item_quantity;
                                $quantity_change = $current_item_quantity - $previous_item_quantity;

                                if($quantity_change!=0){
                                    $inventoryLog = new InventoryLog();
                                    $inventoryLog->item_id = $item->id;
                                    $inventoryLog->in_out_quantity = $quantity_change;
                                    if($quantity_change>0)
                                        $inventoryLog->type = InventoryTypes::$ADD_INVENTORY;
                                    else
                                        $inventoryLog->type = InventoryTypes::$SUBTRACT_INVENTORY;

                                    $inventoryLog->reason = InventoryReasons::$SALEORRETURN." (<a href=". route('sale_receipt',["sale_id"=>$sale_id]) .">EZPOS ".$sale_id."</a>)";
                                    $inventoryLog->user_id = Auth::user()->id;
                                    $inventoryLog->save();
                                }

                            }


                        }

                    }


                }

                if($item_type=='item')
                    $sale->Items()->attach([$item_id=>$aProductInfo]);
                else if($item_type=='item-kit'){
                    unset($aProductInfo['item_id']);
                    $sale->ItemKits()->attach([$item_id=>$aProductInfo]);
                }

            }

        }

        if(!is_null($paymentInfos))
        foreach($paymentInfos as $aPaymentInfo){

            $paymentLog = new PaymentLog();


            $paymentLog->payment_type = $aPaymentInfo["payment_type"];
            $paymentLog->paid_amount = $aPaymentInfo["paid_amount"];

            $paymentLog->save();

            $sale->paymentLogs()->attach($paymentLog);


            if($aPaymentInfo["payment_type"]=="Cash"){

                $cashRegisterTransaction = new CashRegisterTransaction();
                $cashRegister = new CashRegister();

                $activeCashRegiser = $cashRegister->getCurrentActiveRegister();
                $cashRegisterToChange = CashRegister::where("id",$activeCashRegiser->id)->first();
                $cashRegisterToChange->current_balance += $aPaymentInfo["paid_amount"];

                if($cashRegisterToChange->save()){
                    $cashRegisterTransaction->cash_register_id = $activeCashRegiser->id;
                    $cashRegisterTransaction->amount = $aPaymentInfo["paid_amount"];
                    $cashRegisterTransaction->transaction_type = CashRegisterTransactionType::$CASH_SALES;
                    $cashRegister->comments = "Cash Sales for sale: ".$sale_id;
                    $cashRegisterTransaction->save();
                }

            }

            if(strpos($aPaymentInfo["payment_type"],"Loyalty Card")!==false){
                    $loyaltyTransaction = new LoyaltyTransaction();
                    $loyaltyTransaction->NewLoyaltyTransaction($saleInfo["customer_id"],$aPaymentInfo["paid_amount"],LotyaltyTransactionType::$DEBIT_BALANCE,$sale_id);
            }

        }

        if( $saleInfo['customer_id'] != 0){
            //Check if customer has a loyalty card or not
            if($this->CustomerHasLoyalty($saleInfo['customer_id'])){
                $creditAmount = $this->IncreaseCustomerLoyalty($saleInfo["customer_id"],$saleInfo["total"]);
                $loyaltyTransaction = new LoyaltyTransaction();
                $loyaltyTransaction->NewLoyaltyTransaction($saleInfo["customer_id"],$creditAmount,LotyaltyTransactionType::$CREDIT_BALANCE,$sale_id);
            }
        }


        return $sale_id;


    }

    public function IncreaseCustomerLoyalty($customer_id,$total_amount){

        $settings = SettingsSingleton::get();
        $loyalty_incentive_percentage = $settings["customer_loyalty_percentage"];
        $creditLoyalty = ($total_amount * $loyalty_incentive_percentage)/ 100;
        $customer = Customer::where("id",$customer_id)->first();
        $customer->balance+=$creditLoyalty;
        if($customer->save())
            return $creditLoyalty;
        return 0;
    }

    public function DeductCustomerLoyalty($customer_id,$amountToDeduct){
        $customer = Customer::where("id",$customer_id)->first();
        $customer->balance-=$amountToDeduct;
        if($customer->save())
            return 1;
        return 0;
    }


    public function CustomerHasLoyalty($customer_id){
        $customer = Customer::where("id",$customer_id)->first();
        if(!is_null($customer->loyalty_card_number)){
            return true;
        }
        return false;
    }

    public function editSale($saleInfo, $productInfos,$paymentInfos , $saleStatus, $sale_id){

        if($saleStatus!=1)
            session()->put('success','Sale has been successfully suspended');

        $sale = Sale::where('id',$sale_id)->first();

        $sale->employee_id = Auth::user()->id;
        $sale->customer_id = $saleInfo['customer_id'];
        $sale->sub_total_amount = $saleInfo['subtotal'];
        $sale->tax_amount = $saleInfo['tax'];
        $sale->total_amount = $saleInfo['total'];
        $sale->sales_discount = $saleInfo['discount'];
        $sale->sale_status = $saleStatus;
        $sale->due = $saleInfo['due'];
        $sale->profit = $saleInfo['profit'];
        $sale->items_sold = $saleInfo['items_sold'];
        $sale->sale_type = $saleInfo['sale_type'];
        $sale->counter_id = Cookie::get("counter_id");
        $sale->comment = $saleInfo["comment"];

        $sale->save();

        $sale_id = $sale->id;

        foreach($productInfos as $aProductInfo){

            $item_id = $aProductInfo['item_id'];
            $item_quantity = $aProductInfo['quantity'];
            $item_type = $aProductInfo["item_type"];
            if($item_id==0){
                $keyId = "discount-01!XcQZc003ab";
                $item = Item::where("product_id",$keyId)->first();

                if(is_null($item)){

                    $item = new Item();
                    $item->product_id=$keyId;
                    $item->item_name = "discount";
                    $item->category_id = 0;
                    $item->supplier_id = 0;
                    $item->product_type = 2;
                    $item->cost_price = $aProductInfo["unit_price"];
                    $item->selling_price = $aProductInfo["unit_price"];
                    $item->save();
                    $itemId = $item->id;

                }else
                    $itemId = $item->id;

                $aProductInfo['item_id'] = $itemId;

                $productInfos[0]['item_id'] = $itemId;

            }else{
                if($saleStatus!=SaleStatus::$ESTIMATE){
                    echo $item_id;
                    if($item_type=="item"){

                        $item = Item::where("id",$item_id)->first();
                        $previous_item_quantity = $item->item_quantity;
                        $item->item_quantity -= $item_quantity;
                        $item->save();

                        $current_item_quantity = $item->item_quantity;
                        $quantity_change = $current_item_quantity - $previous_item_quantity;

                        if($quantity_change!=0){
                            $inventoryLog = new InventoryLog();
                            $inventoryLog->item_id = $item->id;
                            $inventoryLog->in_out_quantity = $quantity_change;
                            if($quantity_change>0)
                                $inventoryLog->type = InventoryTypes::$ADD_INVENTORY;
                            else
                                $inventoryLog->type = InventoryTypes::$SUBTRACT_INVENTORY;

                            $inventoryLog->reason = InventoryReasons::$SALEORRETURN." (<a href=". route('sale_receipt',["sale_id"=>$sale_id]) .">EZPOS ".$sale_id."</a>)";
                            $inventoryLog->user_id = Auth::user()->id;
                            $inventoryLog->save();
                        }

                    }else if($item_type=="item-kit"){
                        $itemKit = ItemKit::where("id",$item_id)->first();
                        $itemKitProduct = ItemKit::where("id",$itemKit->id)->get();
                        foreach($itemKitProduct as $anItem){

                            $item = Item::where("id",$anItem->item_id)->where('deleted_at','null')->first();
                            if(!is_null($item)&&!isEmpty($item)){

                                $previous_item_quantity = $item->item_quantity;
                                $item->item_quantity -= $item_quantity;
                                $item->save();

                                $current_item_quantity = $item->item_quantity;
                                $quantity_change = $current_item_quantity - $previous_item_quantity;

                                if($quantity_change!=0){
                                    $inventoryLog = new InventoryLog();
                                    $inventoryLog->item_id = $item->id;
                                    $inventoryLog->in_out_quantity = $quantity_change;
                                    if($quantity_change>0)
                                        $inventoryLog->type = InventoryTypes::$ADD_INVENTORY;
                                    else
                                        $inventoryLog->type = InventoryTypes::$SUBTRACT_INVENTORY;

                                    $inventoryLog->reason = InventoryReasons::$SALEORRETURN." (<a href=". route('sale_receipt',["sale_id"=>$sale_id]) .">EZPOS ".$sale_id."</a>)";
                                    $inventoryLog->user_id = Auth::user()->id;
                                    $inventoryLog->save();
                                }
                            }


                        }

                    }


                }


            }

        }

        $sale->items()->sync($productInfos);


        if(!is_null($paymentInfos)){

            $payments = array();
            foreach($paymentInfos as $aPaymentInfo){

                $payment_id = (int) $aPaymentInfo["payment_id"];
               if($payment_id==0){

                    $paymentLog = new PaymentLog();

                    $paymentLog->payment_type = $aPaymentInfo["payment_type"];
                    $paymentLog->paid_amount = $aPaymentInfo["paid_amount"];

                    $paymentLog->save();

                    $payment_id = $paymentLog->id;
                }

                array_push($payments, $payment_id);
            }

            $sale->paymentLogs()->sync($payments);
        }

        return $sale_id;


    }

    public function DeleteSale($saleId){
        $sale = $this::where("id",$saleId)->first();
        $sale->delete();
    }
}
