<?php
/**
 * Created by PhpStorm.
 * User: ByteLab
 * Date: 8/9/2018
 * Time: 12:22 PM
 */
namespace App\Model\Api;

use App\Enumaration\PaymentTypes;
use App\Enumaration\SaleStatus;
use App\Enumaration\SaleTypes;
use App\Model\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProcessOrder extends Model
{
    private $data;
    private $averageTaxRate;
    private $averageTaxAmount;
    private $profit;
    /**
     * ProcessOrder constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->averageTaxRate = $this->getTaxRate();
        $this->averageTaxAmount = $this->getTaxAmount();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }


    public function processCustomerAndGetId() {

        if(isset($this->data->customer_id) && CheckNull($this->data->customer_id))
            return $this->data->customer_id;

        if(CheckNull($this->data->customer_name)) {
            $customer_id = DB::table('customers')->InsertGetId([
                'first_name' => $this->data->customer_name,
                'address_1' => $this->data->customer_address,
                'phone' => $this->data->customer_phone
            ]);

            return $customer_id;
        }
        return 0;
    }

    public function processPaymentInfo() {
        return array(array(
            "payment_type" => $this->data->payment_method,
            "paid_amount" => $this->data->paid
        ));
    }

    public function processItems() {
        $processedItems = array();

        foreach( $this->data->items as $anItem ) {

            $anItem = (object) $anItem;

            $currentProcessedItem = array(
                'item_id' => $anItem->id,
                'unit_price' => $anItem->perUnitPrice,
                'cost_price' => $anItem->stock_price,
                'total_price' => $anItem->totalPrice,
                'quantity' => $anItem->quantity,
                'discount_amount' => $this->getItemDiscountAmountByItemId($anItem),
                'price_rule_id' => $this->getPriceRuleIdByItemId($anItem->id),
                'item_type' => $this->getItemTypeByItemId($anItem->id),
                'item_discount_percentage' => $this->getItemDiscountPercentageByItemId($anItem),
                'sale_discount_amount' =>  $this->getSaleDiscountAmountByItemId($anItem),
                'item_profit' => $this->getItemProfitByItemId($anItem),
                'tax_amount' => $this->averageTaxAmount,
                'tax_rate' => $this->averageTaxRate,
                'is_price_taken_from_barcode' => false
            );
            array_push($processedItems, $currentProcessedItem);
        }

        return $processedItems;
    }

    public function processSaleInfo()
    {
        return array(
            "customer_id" => $this->processCustomerAndGetId(),
            "subtotal" => $this->data->sub_total,
            "tax" => $this->getTaxAmount(),
            "total" => $this->data->total,
            "discount" => $this->data->discount,
            "due" => $this->data->due,
            "profit" => $this->profit,
            "items_sold" => $this->getTotalNumberOfItems(),
            "sale_type" => SaleTypes::$SALE,
            "counter_id" => $this->data->counter_id,
            "comment" => "",
            "cash_register_id" => $this->data->cash_register_id,
            "total_sales_discount" => $this->data->discount
        );
    }

    private function getItemDiscountAmountByItemId($item) {
        return ($item->perUnitPrice * $item->quantity) - $item->totalPrice;
    }

    private function getPriceRuleIdByItemId($item_id) {

        $itemSearch = DB::table('item_price_rule')
                    ->join('price_rules','item_price_rule.price_rule_id','=','price_rules.id')
                    ->where("item_id",$item_id)
                    ->whereDate("start_date",'<=',date('Y-m-d'))
                    ->whereDate("end_date",'>=',date('Y-m-d'));

        if($itemSearch->exists())
            return $itemSearch->orderBy('price_rules.created_at','desc')->first()->price_rule_id;
        return 0;
    }

    private function getItemTypeByItemId($item_id) {
        return "item";
    }

    private function getItemDiscountPercentageByItemId($item_id) {


        $itemDiscountPercentage = 0.0;
        return $itemDiscountPercentage;
    }

    private function getSaleDiscountAmountByItemId($item_id) {
        $saleDiscountPercentage = 0.0;
        return $saleDiscountPercentage;
    }

    private function getItemProfitByItemId($item_id) {

        /**
         * Get profit for selected item
         */

        $itemProfit = 0.0;
        $this->profit += $itemProfit;
        return $itemProfit;
    }

    private function getTaxAmount() {

        if($this->tax_id==0)
            return 0.00;

        $tax = DB::connection('mysql_restaurant')->table('taxes')->where('id', $this->tax_id)->first();

        if(!is_null($tax)) {
            if($tax->type == "Percent")
            {

                $taxAmountTotal = ($this->data->sub_total * $tax->amount) / 100;
                return $taxAmountTotal / $this->getTotalNumberOfItems();
            }
            else{
                $averageTaxAmountPerItem = ($tax->amount) / $this->getTotalNumberOfItems();
                return $averageTaxAmountPerItem;
            }
        }
        return 0.0;
    }

    private function getTaxRate() {

        if($this->tax_id==0)
            return 0.00;

        $tax = DB::connection('mysql_restaurant')->table('taxes')->where('id', $this->tax_id)->first();

        if(!is_null($tax)) {
            if($tax->type == "Percent")
                return $tax->amount;
            else{
                $averageTaxAmountPerItem = ($tax->amount) / $this->getTotalNumberOfItems();

                return ($averageTaxAmountPerItem / ($this->data->total+ $tax->amount)) * 100;
            }
        }
        return 0.0;
    }

    public function getTotalNumberOfItems() {
        $totalQuantity = 0;

        foreach ($this->data->items as $item) {
            $totalQuantity += $item['quantity'];
        }
        return $totalQuantity;
    }

}