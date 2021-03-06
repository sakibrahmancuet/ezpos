
@extends('layouts.master')

@section('pageTitle','Payment Detail Report')

@section('breadcrumbs')
    {!! Breadcrumbs::render('report_payment_detail') !!}
@stop

@section('content')
    <?php $dateTypes = new \App\Enumaration\DateTypes(); ?>

    <div class="filter-box">
        <div class="row">
            <div class="col-md-12">

                <div class="form-inline">

                    <div class="form-group" style="float:right">
                        <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input id="end_date_formatted" name="end_date_formatted" type="text" class="form-control" value="{{ date('Y-m-d') }}">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="float:right">
                        <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
                            <input id="start_date_formatted" name="start_date_formatted" type="text" class="form-control" value="{{date('Y-m-d')}}">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                            </div>
                        </div>
                        To
                    </div>

                </div>
            </div>
        </div>
    </div>


    <div class="box box-primary" style="padding:20px">

        <div class="se-pre-con text-center hide">
            <img height="30%" width="30%"  src = "{{ asset('img/loader.gif') }}" >
        </div>

        <div class="data">
            <div class="row">

                <div class="col-md-3 col-xs-12 col-sm-6 summary-data">
                    <div class="info-seven primarybg-info">
                        <div class="logo-seven"><i class="glyphicon glyphicon-globe"></i></div>
                        <span class="count" id="subtotal">{{$info["subtotal"]}}</span>
                        <p>Subtotal</p>
                    </div>
                </div>
                <div class="col-md-3 col-xs-12 col-sm-6 summary-data">
                    <div class="info-seven primarybg-info">
                        <div class="logo-seven"><i class="glyphicon glyphicon-globe"></i></div>
                        <span class="count" id="total">{{$info["total"]}}</span>
                        <p>Total</p>
                    </div>
                </div>
                <div class="col-md-3 col-xs-12 col-sm-6 summary-data">
                    <div class="info-seven primarybg-info">
                        <div class="logo-seven"><i class="glyphicon glyphicon-globe"></i></div>
                        <span class="count" id="tax">{{$info["tax"]}}</span>
                        <p>Tax</p>
                    </div>
                </div>
                <div class="col-md-3 col-xs-12 col-sm-6 summary-data">
                    <div class="info-seven primarybg-info">
                        <div class="logo-seven"><i class="glyphicon glyphicon-globe"></i></div>
                        <span class="count" id ="profit">{{$info["profit"]}}</span>
                        <p>Profit</p>
                    </div>
                </div>

            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-reports tablesorter stacktable small-only">
                    <thead>
                    <tr>
                        <th align="left" class="header">Sale Id</th>
                        <th align="left" class="header">Sale Date</th>
                        <th align="left" class="header">Payment Date</th>
                        <th align="right" class="header">Payment Type</th>
                        <th align="right" class="header">Paid Amount</th>
                    </tr>
                    </thead>
                    <tbody id="data-table">
                    @foreach($sales as $aSale)

                        <tr>
                            <td><a href="{{route('sale_receipt',['sale_id'=>$aSale->sale_id])}}">
                                <span class="glyphicon glyphicon-print"></span></a>
                            <a href="{{route('sale_edit',['sale_id'=>$aSale->sale_id])}}"><span class="glyphicon glyphicon-edit"></span></a>
                                EZPOS {{$aSale->sale_id}}</td>
                            <td>{{ $aSale->sale_date }}</td>
                            <td>{{ $aSale->payment_date }}</td>
                            <td>{{ $aSale->item_name }}</td>
                            <td>
                                @if($aSale->total_amount>=0)
                                    ${{$aSale->total_amount}}
                                @else
                                    -${{ (-1) * $aSale->total_amount }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>







@endsection



@section('additionalJS')

    <script>

        var table;
        $(document).ready(function(e) {

            countAnimate();
            table = $('.table').DataTable({
                pageLength:10,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'print',
                ],
            });



            $('#order_by,  #start_date_formatted, #end_date_formatted').change(function() {
                $('.data').addClass('hide');
                $('.se-pre-con').removeClass('hide');


                var order_by = $("#order_by").val();
                var start_date_formatted = $("#start_date_formatted").val();
                var end_date_formatted = $("#end_date_formatted").val();



                $.ajax({
                    method: "POST",
                    url: "{{ route('report_payment_ajax') }}",
                    data: {

                        report_name:"{{ $report_name }}",
                        modifier:"{{ $modifier }}",
                        order_by: "{{ $report_type }}",
                        start_date_formatted: start_date_formatted,
                        end_date_formatted: end_date_formatted
                    }
                }).done(function( data ) {

                    //console.log(data);

                    $("#subtotal").text(data.info['subtotal']);
                    $("#total").text(data.info['total']);
                    $("#tax").text(data.info['tax']);
                    $("#profit").text(data.info['profit']);

                    /* animateNumber($("#sub_total_amount"),data.info['sub_total_amount']);*/

                    tableData="";

                    data.sale.forEach(function(item){

                        var receipt_url = '{{ route("sale_receipt", ":sale_id") }}';
                        receipt_url = receipt_url.replace(':sale_id', item.sale_id);

                        var edit_url = '{{ route("sale_edit", ":sale_id") }}';
                        edit_url = edit_url.replace(':sale_id', item.sale_id);

                        tableData += "<tr>";
                        tableData += "<td><a href='"+ receipt_url +"'><span class='glyphicon glyphicon-print'></span></a>  <a   href='"+ edit_url +"'><span class='glyphicon glyphicon-edit'></span></a>EZPOS "+ item.sale_id +"</td>";
                        tableData += "<td>"+ item.sale_date +"</td>";
                        tableData += "<td>"+ item.payment_date +"</td>";
                        tableData += "<td>"+ item.item_name +"</td>";
                        tableData+="<td>";
                        if(item.total_amount>=0)
                            tableData+="$"+item.total_amount;
                        else
                            tableData+="-$"+ (-1) * item.total_amount;
                        tableData+="</td>";
                        tableData+="</tr>";
                    });

                    table.destroy();
                    $("#data-table").html(tableData);
                    table = $('.table').DataTable({
                        pageLength:10,
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 'csv', 'excel', 'print',
                        ],
                    });

                    $('.se-pre-con').addClass('hide');
                    $('.data').removeClass('hide');
                    countAnimate();

                });


            });

        });

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function countAnimate(){

            $('.count').each(function () {
                var value = $(this).text();

                var unformatted = value.replace(",", "");
                /*var value = parseInt($(this).text());*/

                $(this).prop('Counter',0).animate({
                    Counter: unformatted
                }, {
                    duration: 500,
                    easing: 'swing',
                    step: function (now) {
                        if(now>=0)
                            $(this).text("$"+numberWithCommas(now.toFixed(2)));
                        else
                            $(this).text("-$"+numberWithCommas((-1) * now.toFixed(2)));
                    }
                });
            });

        }
    </script>



@stop