
@extends('layouts.master')

@section('pageTitle','Sales Receipt')

@section('breadcrumbs')
    {!! Breadcrumbs::render('sale_receipt',$sale->id) !!}
@stop

@section('content')

        <div class="box box-primary" id="receipt_wrapper_inner">
            <div class="panel panel-piluku">
                <div class="panel-body panel-pad">

                    <div class="row">
                        <div class="panel-body panel-pad pull-right">
                            <a  href="{{route('sale_edit',['sale_id'=>$sale->id])}}" class="btn btn-primary">Edit Sale</a>
                            <a  href="{{route('print_sale',['sale_id'=>$sale->id, "print_type"=>1])}}" class="btn btn-primary">Print</a>
                            <a  href="{{route('print_sale',['sale_id'=>$sale->id, "print_type"=>2])}}" class="btn btn-primary">Print Pickup</a>
                            <a  href="{{route('download_sale_receipt',['sale_id'=>$sale->id])}}" class="btn btn-primary">Download as PDF</a>
                            <a  href="{{route('mail_sale_receipt',['sale_id'=>$sale->id])}}" class="btn btn-primary">Email to customer</a>
                        </div>
                    </div><br>

                    <div class="row">
                        <!-- from address-->
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <ul class="list-unstyled invoice-address" style="margin-bottom:2px;">
                                <li class="company-title">{{$settings['company_name']}}, Inc</li>

                                <li>{{$settings['company_name']}}</li>
								@if($settings['address_line_1']!=""||$settings['address_line_1']!=null)
								<li><?php echo substr($settings['address_line_1']) ?></li>
								@endif
								@if($settings['address_line_2']!=""||$settings['address_line_2']!=null)
								<li><?php echo substr($settings['address_line_2']) ?></li>
								@endif
								@if($settings['email_address']!=""||$settings['email_address']!=null)
								<li><?php echo substr($settings['email_address']) ?></li>
								@endif
								@if($settings['phone']!=""||$settings['phone']!=null)
								<li><?php echo substr($settings['phone']) ?></li>
								@endif
                            </ul>
                        </div>
                        <!--  sales-->
                        <div class="col-md-4 col-sm-4 col-xs-12 text-right" style="float:right" >
                            <ul class="list-unstyled invoice-detail" style="margin-bottom:2px;">
                                <li class="big-screen-title">
                                    Sales Receipt						 <br>
                                    <strong>{{date('m/d/Y h:i:s a', time()) }}</strong>
                                </li>
                                <li><span>Sale ID: </span>EZPOS No. {{$sale->id}}</li>
                                <li><span>Counter Name: </span><b>{{ $sale->counter->name }}</b></li>
                                <li><span>Cashier: </span>{{\Illuminate\Support\Facades\Auth::user()->name }}</li>
                                @if(isset($sale->customer->id))
                                    <li><span>Customer:</span>{{$sale->customer->first_name}} {{$sale->customer->last_name}}</li>
									@if($sale->Customer->loyalty_card_number && strlen($sale->Customer->loyalty_card_number)>0)
										<li>
										@php
											$loyalityCarNumber = $sale->Customer->loyalty_card_number;
											$loyalityCarNumberMasked = str_repeat('X', strlen($loyalityCarNumber) - 4) . substr($loyalityCarNumber, -4);
											echo $loyalityCarNumberMasked;
										@endphp
										</li>
									@endif
                                @endif
                            </ul>
                        </div>
                        <!-- to address-->
                        <div class="col-md-4 col-sm-4 col-xs-12">

                        </div>

                        <!-- delivery address-->
                        <div class="col-md-12 col-sm-12 col-xs-12">


                        </div>

                    </div>

                    <br>
                    <!-- invoice items-->

                    <table  class="table table-hover table-responsive" >
                        <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Discount Percentage</th>

                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sale->items as $anItem)
                            <tr>
                                <td>
                                 @if($anItem->pivot->is_price_taken_from_barcode)
                                    <span>{{ $anItem->item_name }}</span><br>@<span>{{ $anItem->pivot->unit_price }}</span>{{ $anItem->item_size==null ? "" : "/".$anItem->item_size }}
                                 @else
                                    {{ $anItem->item_name }}
                                 @endif
                                </td>
                                <td> ${{ $anItem->pivot->unit_price }} </td>
                                <td> {{ $anItem->pivot->quantity }} </td>
                                <td> ${{ $anItem->pivot->total_price }} </td>
                                <td> {{ $anItem->pivot->item_discount_percentage }}% </td>

                            </tr>

                            @if(!is_null($anItem->PriceRule))
                                @foreach($anItem->PriceRule as $aPriceRule)
                                    @if ($aPriceRule->active)

                                        @if($aPriceRule->unlimited||$aPriceRule->num_times_to_apply>0)

                                            @if($aPriceRule->type==1)

                                                @if($aPriceRule->percent_off>0)

                                                    @php
                                                    $current_date = new \DateTime('today');
                                                    $rule_start_date = new \DateTime($aPriceRule->start_date);
                                                    $rule_expire_date = new \DateTime($aPriceRule->end_date);
                                                    @endphp

                                                    @if(($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) )
                                                        <tr><td colspan="5" style="padding-left:23px;font-size: 80%;background: aliceblue;"> Discount Offer: <strong>{{$aPriceRule->name}}</strong><br>Item Discount Amount: $<strong>{{$anItem->pivot->discount_amount}}</strong></td></tr>
                                                    @endif

                                                @elseif($aPriceRule->fixed_of>0)

                                                    @php
                                                    $current_date = new \DateTime('today');
                                                    $rule_start_date = new \DateTime($aPriceRule->start_date);
                                                    $rule_expire_date = new \DateTime($aPriceRule->end_date);
                                                    @endphp

                                                    @if(($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) )
                                                        <tr><td colspan="5" style="padding-left:23px;font-size: 80%;background: aliceblue;"> Discount Offer: <strong>{{$aPriceRule->name}}</strong><br>Item Discount Amount: $<strong>{{$anItem->pivot->discount_amount}}</strong></td></tr>
                                                    @endif

                                                @endif
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                        @foreach($sale->itemkits as $anItem)
                            <tr>
                                <td> {{ $anItem->item_kit_name }} </td>
                                <td> ${{ $anItem->selling_price }} </td>
                                <td> {{ $anItem->pivot->quantity }} </td>
                                <td> ${{ $anItem->pivot->total_price }} </td>
                                <td> {{ $anItem->pivot->item_discount_percentage }}% </td>


                            </tr>
                            @if(!is_null($anItem->PriceRule))

                                @foreach($anItem->PriceRule as $aPriceRule)

                                        @if ($aPriceRule->active)

                                            @if($aPriceRule->unlimited||$aPriceRule->num_times_to_apply>0)

                                                @if($aPriceRule->type==1)

                                                    @if($aPriceRule->percent_off>0)

                                                        @php
                                                        $current_date = new \DateTime('today');
                                                        $rule_start_date = new \DateTime($aPriceRule->start_date);
                                                        $rule_expire_date = new \DateTime($aPriceRule->end_date);
                                                        @endphp

                                                        @if(($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) )
                                                            <tr><td colspan="5" style="padding-left:23px;font-size: 80%;background: aliceblue;"> Discount Offer: <strong>{{$aPriceRule->name}}</strong><br>Item Discount Amount: $<strong>{{$anItem->pivot->discount_amount}}</strong></td></tr>
                                                        @endif

                                                    @elseif($aPriceRule->fixed_of>0)

                                                        @php
                                                        $current_date = new \DateTime('today');
                                                        $rule_start_date = new \DateTime($aPriceRule->start_date);
                                                        $rule_expire_date = new \DateTime($aPriceRule->end_date);
                                                        @endphp

                                                        @if(($current_date>=$rule_start_date) && ($current_date<=$rule_expire_date) )
                                                            <tr><td colspan="5" style="padding-left:23px;font-size: 80%;background: aliceblue;"> Discount Offer: <strong>{{$aPriceRule->name}}</strong><br>Item Discount Amount: $<strong>{{$anItem->pivot->discount_amount}}</strong></td></tr>
                                                        @endif

                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                @endforeach
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                    <hr>
                    <div class="invoice-footer gift_receipt_element">

                        <div class="row">
                            <div class="col-md-offset-4 col-sm-offset-4 col-md-6 col-sm-6 col-xs-8">
                                <div class="invoice-footer-heading">Sub Total</div>
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-4">
                                <div class="invoice-footer-value">
                                    <strong>
                                    <span style="white-space:nowrap;"></span>
                                         @if($sale->sub_total_amount>=0)
                                            ${{ number_format($sale->sub_total_amount, 2)}}
                                         @else
                                            -${{ number_format((-1) * $sale->sub_total_amount, 2) }}
                                         @endif
                                    </strong>
                                </div>
                            </div>
                        </div>
                        @if( $settings['tax_rate'] >0 )
                        <div class="row">
                            <div class="col-md-offset-4 col-sm-offset-4 col-md-6 col-sm-6 col-xs-8">
                                    <div class="invoice-footer-heading">Tax({{ $settings['tax_rate']  }}%)</div>
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-4">
                                <div class="invoice-footer-value">
                                    <strong>
                                        <span style="white-space:nowrap;"></span>
                                        @if($sale->tax_amount>=0)
                                            ${{ number_format($sale->tax_amount, 2)}}
                                        @else
                                            ${{ number_format((-1) * $sale->tax_amount, 2) }}
                                        @endif
                                    </strong>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-offset-4 col-sm-offset-4 col-md-6 col-sm-6 col-xs-8">
                                <div class="invoice-footer-heading">Total</div>
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-4">
                                <div class="invoice-footer-value invoice-total">

                                    <strong>
                                    <span style="white-space:nowrap;"></span>
                                        @if($sale->total_amount>=0)
                                            ${{ number_format($sale->total_amount, 2)}}
                                        @else
                                            -${{ number_format((-1) * $sale->total_amount, 2) }}
                                        @endif
                                    </strong>

                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-2 col-md-2 col-sm-2 col-xs-6">
                                <div class="invoice-footer-heading">{{$sale->due>=0?'Due': 'Change Due'}}</div>
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-4">
                                <div class="invoice-footer-value invoice-total">

                                    @if($sale->due>=0)
                                         ${{  number_format($sale->due, 2) }}
                                    @else
                                        -${{ (-1) * number_format($sale->due, 2) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-2 col-md-2 col-sm-2 col-xs-6"><br>
                                <div class="invoice-footer-heading"><strong>Payments</strong></div>
                            </div>
                        </div>

                        @foreach($sale->paymentlogs as $aPayment)
                        <div class="row">
                            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-2 col-md-2 col-sm-2 col-xs-6">
                                <div class="invoice-footer-heading">{{$aPayment->payment_type}} Tendered:</div>
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-4">
                                <div class="invoice-footer-value invoice-total">
                                    @if($aPayment->paid_amount>0)
                                        ${{$aPayment->paid_amount}}
                                    @else
                                        -${{number_format((-1) * $aPayment->paid_amount, 2)}}
                                    @endif
                                </div>
                            </div>
                        </div>


                        @endforeach

                        <div class="row">
                            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-2 col-md-2 col-sm-2 col-xs-6"><br>
                                <div class="invoice-footer-heading"><strong>Comments</strong></div>
                            </div>
                            <div class="col-md-offset-8 col-sm-offset-8 col-xs-offset-2 col-md-2 col-sm-2"><br>
                                <div class="invoice-footer-heading">{{ $sale->comment }}</div><br>
                            </div><br><br>
                        </div>

                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <div class="text-center">
                                </div>
                            </div>
                        </div>
                    </div>

                    <center>CUSTOMER COPY</center>
                    <!-- invoice footer-->
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="invoice-policy">
                                Change return policy			            </div>
                            <div id="receipt_type_label" style="display: none;" class="receipt_type_label invoice-policy">
                                Merchant Copy						</div>
                            <div id="barcode" class="invoice-policy">
                               <?php echo DNS1D::getBarcodeHTML($sale->id , "C39");	?>					</div>
                                <p style="padding-left: 28px">EZPOS {{ $sale->id }}</p>
                            <div id="announcement" class="invoice-policy">
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 col-md-offset-3 col-sm-offset-3">
                            <div id="signature">


                            </div>
                        </div>
                    </div>
                    <center>THANK YOU!</center>
                </div>
                <!--container-->
            </div>
        </div>
        <br><br>








@endsection



@section('additionalJS')


@stop