<?php

namespace App\Model;

use App\Enumaration\PaymentTransactionTypes;
use App\Enumaration\PaymentTypes;
use App\Http\Controllers\CashRegisterController;
use Illuminate\Database\Eloquent\Model;
use function PHPSTORM_META\elementType;

class PaymentLog extends Model
{
    private $cashRegister;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

    }


    public function Sales(){
        return $this->belongsToMany('App\Model\Sale');
    }

    public function addNewPaymentLog($payment_type, $paid_amount,

        $sale = null, $customer_id , $comments, $invoice_id=0) {

        $paymentLog = new PaymentLog();

        $paymentLog->payment_type = $payment_type;
        $paymentLog->paid_amount = $paid_amount;
        if(!is_null($sale)) {
            $paymentLog->sale_id = $sale->id;
            $paymentLog->sale_status = $sale->sale_status;
        }
        
		$cash_register = new CashRegister();

        if(!is_null($cash_register->getCurrentActiveRegister())) {
            if($sale->cash_register_id != $cash_register->getCurrentActiveRegister()->id) {
                $paymentLog->cash_register_id = $cash_register->getCurrentActiveRegister()->id;
            }
            else{
                if(!is_null($sale) && !is_null($sale->cash_register_id)) {
                    $paymentLog->cash_register_id = $sale->cash_register_id;
                }
                else
                    $paymentLog->cash_register_id = 0;
            }
        } else {
            if(!is_null($sale) && !is_null($sale->cash_register_id)) {
                $paymentLog->cash_register_id = $sale->cash_register_id;
            }
            else
                $paymentLog->cash_register_id = 0;
        }

        $paymentLog->comments = $comments;

        $paymentLog->invoice_id = $invoice_id;
        $paymentLog->save();

        if(!is_null($sale)){
            $sale->paymentLogs()->attach($paymentLog);
        }

        return $paymentLog;
    }
}
