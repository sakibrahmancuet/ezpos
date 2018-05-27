@extends('layouts.master')

@section('pageTitle','Sales')

@section('breadcrumbs')
    {!! Breadcrumbs::render('new_sale') !!}
    <span><label class="label label-primary pull-right counter-name"><b>{{ \Illuminate\Support\Facades\Cookie::get('counter_name') }}</b></label></span>
    <br><br>
    <a href="javascript:void(0)"  onclick="changeCounter()" class="pull-right">Change Location</a>
    <br>
@stop

@section('content')
    <style>
        .input-group {
            padding-left:0px
        }
        .card{
            margin-top:0px;
            margin-bottom: 10px;
        }
    </style>
    {{--Sale config--}}
    <?php $tax_rate = $settings['tax_rate'] ; ?>
    {{--Sale config--}}

    <div id="app" class="row">
        <div class="col-sm-7" >
            <div class = "search section">
                <div class="input-group">
                    <a href="{{route('new_item')}}" target="_blank" class="input-group-addon" id="sizing-addon2" style="background-color:#337ab7;color:white;border:solid #337ab7 1px; "><strong>+</strong></a>
                    <auto-complete @set-autocomplete-result="setAutoCompleteResult" :auto-select="auto_select"></auto-complete>
					
					<div class="input-group-btn bs-dropdown-to-select-group">
                        <button type="button" class="btn btn-primary dropdown-toggle as-is bs-dropdown-to-select" data-toggle="dropdown">
                            <span data-bind="bs-drp-sel-label">Sale</span>
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" role="menu" style="" >
                            <li data-value="1"><a onclick="convertToSale()"  href="#">Sale</a></li>
                            <li data-value="2"><a onclick="convertToReturn()" href="#">Return</a></li>{{--
                            <li data-value="3"><a href="#">Store Account Payment</a></li>--}}
                        </ul>
                    </div>
                </div>
				
				
				
                <input type="checkbox" checked  id="auto_select" v-model="auto_select"> <b>Add automatically to cart when item found.</b>

            </div>

            <br>

            <div class="card table-responsive" >
                <table class="table table-hover table-responsive">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Discount(%)</th>
                        <th class="text-center">Total</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody class = "product-descriptions">
						<tr v-for="(anItem,index) in itemList" class="product-specific-description">
							<td class="col-sm-8 col-md-6">
								<div class="media">
									<div class="media-body"> 
										<h6 class="media-heading"><a href="#">@{{itemList[index].item_name}}</a></h6>
										<h6 v-if="itemList[index].company_name" class="media-heading">
										by <a href="#">@{{itemList[index].company_name}}</a></h6>
										<span>Status: </span>
										<span v-if="itemList[index].item_quantity>10" class="text-success"><strong>In Stock</strong>
										</span>
										<span v-else-if="itemList[index].item_quantity<=0" class="text-success"><strong>Out of Stock</strong>
										</span>
										<span v-else class="text-success"><strong>Soon will be out of Stock</strong>
										</span>
									</div>
								</div>
							</td> 
							<td class="col-sm-1 col-md-1" style="text-align: center"> 
								<input min="0" class="form-control quantity" value="1" v-model="itemList[index].bought_quantity">
							</td>
							<td class="col-sm-1 col-md-1 text-center">
								<inline-edit v-model="itemList[index].price" if-user-permitted="{{UserHasPermission("edit_sale_cost_price")}}" ></inline-edit>
							</td>
							<td>
								<input class="form-control discount-amount" v-model="itemList[index].discount_percentage">
							</td>
							<td class="col-sm-1 col-md-1 text-center">
								<strong class="total-price">@{{GetLineTotal(index)}}</strong>
							</td>
							<td class="col-sm-1 col-md-1">
								<button type="button" class="btn btn-danger" @click="Remove(itemList[index].item_id)"><span class="pe-7s-trash"></span> Remove</button>
							</td>
						</tr>
                    
						<tr v-if="itemList.length<=0" class="no-items"> <td colspan="6"><div class="jumbotron text-center"> <h3>There are no items in the cart [Sales]</h3> </div></td> </tr>
					</tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>

        </div>
        <div class ="col-sm-4">
            <div class="form-group">
                <div class="row">
                    <div class = "card">

                        <div class="sale-buttons input-group" style = "border-bottom:solid #ddd 1px; padding:10px;max-width: 100%;display: inline-block;">
                            <div class="btn-group input-group-btn">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <strong>...</strong>
                                </button>
                                <ul class="dropdown-menu sales-dropdown" role="menu">
                                    <li>
                                        <a href="{{route('suspended_sale_list')}}" class="" title="Suspended Sales"><i class="ion-ios-list-outline"></i> Suspended Sales</a>								</li>
                                    <li>
                                        <a href="{{route('search_sale')}}" class="" title="Search Sales"><i class="ion-search"></i> Search Sales</a>
                                    </li>

                                    <li>
                                        <a href="#look-up-receipt" class="look-up-receipt" data-toggle="modal"><i class="ion-document"></i> Lookup Receipt</a>						</li>

                                    <li><a href="{{route('sale_last_receipt')}}"  target="_blank" class="look-up-receipt" title="Lookup Receipt"><i class="ion-document"></i> Show last sale receipt</a></li>
                                    <li><a href="{{route('pop_open_cash_drawer')}}"  class="look-up-receipt" title="Lookup Receipt"><i class="ion-document"></i> Pop Open Cash Drawer</a></li>
                                    <li><a href="{{ route('add_cash_to_register') }}">Add cash to register</a></li>
                                    <li><a href="{{ route('subtract_cash_from_register') }}">Remove cash from register</a></li>
                                    <li><a href="{{ route('customer_balance_add') }}">Add Customer Balance</a></li>
                                    <li><a href="{{ route('close_cash_register') }}">Close register</a></li>
                                </ul>
                                <form action="" id="cancel_sale_form" autocomplete="off" method="post" accept-charset="utf-8">

                                    <div class="btn-group input-group-btn"  >
                                        <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                            <i class="ion-pause"></i>
                                            Suspend Sale								</button>
                                        <ul class="dropdown-menu sales-dropdown" id = "sale-type" data-selected-type="sale" role="menu">
                                            <li><a href="#" onclick = "layAwaySale()" id="layaway_sale_button"><i class="ion-pause"></i> Charge Account </a></li>
                                            <li><a href="#" onclick = "estimateSale()" id="estimate_sale_button"><i class="ion-help-circled"></i> Estimate</a></li>

                                        </ul>
                                    </div>
                                    <a href="" class="btn btn-danger input-group-addon" id="cancel_sale_button">
                                        <i class="ion-close-circled"></i>
                                        Cancel Sale				</a>

                                </form>
                            </div>
                        </div>

                        <!-- If customer is added to the sale -->

                        <div class="customer-form">

                            <!-- if the customer is not set , show customer adding form -->
                            <form action="" id="select_customer_form" autocomplete="off" class="form-inline" method="post" accept-charset="utf-8">
                                <div class="input-group contacts" style="padding-top:10px;padding-left:10px">

                                    <a href="{{route('new_customer')}}" target="_blank" class="input-group-addon" id="sizing-addon2" style="background-color:#337ab7;color:white;border:solid #337ab7 1px; "><strong>+</strong></a>
                                    <select2 v-model="customer_id">
                                        <option value ="0" selected>Select Customer for sale</option>
                                        @foreach($customerList as $aCustomer)
                                            <option value = "{{$aCustomer->id}}">{{$aCustomer->first_name}} {{$aCustomer->last_name}}</option>
                                        @endforeach
                                    </select2>
                                </div>
                            </form>

                        </div>
                    </div></div>

                <div class="row"><div class = "card" >
                        <h4 class="text-center"><strong>Receipt</strong></h4>
                        <hr>
                        <div class="card">
                            <strong>Subtotal</strong> <span style="float: right"><strong data-subtotal="0" class="subtotal">$@{{GetSubtotal}}</strong></span><br>
                            <strong>+Tax({{ $tax_rate }}%)</strong><span style="float: right"><strong data-tax="0" id="tax">$@{{GetTax}}</strong></span><br>
                            <strong>Discount all items by percent</strong><span style="float: right"><strong id=""><input id ="allDiscountAmount" type ="number" v-model="allDiscountAmountPercentage" style="max-width:45px;float: right"></strong></span><br><br>
							
                            <strong>Discount entire sale</strong><span style="float: right"><strong id=""><input id ="saleFlatDiscountAmount" style="max-width:45px;float: right" v-model="saleFlatDiscountAmount"></strong></span>
                        </div>

                        <div class = "card" style="background-color: #778a9b;color:whitesmoke;font-size:20px;">
                            Total <span style="float: right"><strong data-total="0" id = "total"> $@{{GetTotalSale}}</strong></span>
                        </div>
                        <div class = "card" style="background-color: #778a9b;color:whitesmoke;font-size:20px;">
                            Due <span style="float: right"><strong data-due="0" id = "due"> $0.00</strong></span>
                        </div><br>
                        <div class="row">
                            {{--<input type="number" id = "paid-amount" name="paid-amount" class="col-md-8 form-control" style="float:left">
                            <button type="button" class="col-md-4 btn btn-success" style="float:right" onclick = "SubmitSales()">
                                Checkout <span class="pe-7s-cart"></span>
                            </button><br><br>--}}


                            <div class="add-payment">

                                <div class="payment-history">

                                </div>
                                <input type = "hidden" name="total-paid-amount" data-value="0">
                                <div style="padding:20px">

                                    <div class="side-heading">Add Payment</div>
									
                                    <a tabindex="-1" v-for="aPaymentType in paymentTypeList" href="javascript: void(0);" :class="GetPaymentButtonClass(aPaymentType)" @click="SetActivePaymentType(aPaymentType)" >
                                        @{{aPaymentType}}</a>

                                </div>


                                <div class="input-group add-payment-form">
                                    <input type="number" name="amount_tendered" value="0.00" id="amount_tendered" class="add-input numKeyboard form-control" v-model="amountTendered">
                                    
									
									<span class="input-group-addon" style="background: #5cb85c; border-color: #4cae4c;">
										<input v-show="activePaymentType=='Gift Card'" class="form-control" type="text" name="gift_card_number"  id="gift_card_number" class="add-input numKeyboard form-control" />
										
										<input v-show="activePaymentType=='Loyalty Card'" class="form-control" type="text" name="loyalty_card_number"  id="loyalty_card_number" class="add-input numKeyboard form-control" />
									</span>
									
									<span class="input-group-addon" style="background: #5cb85c; border-color: #4cae4c;">
										<a href="javascript:void(0)" class="hidden" id="add_payment_button" onclick = "addPayment()" style=" color:white;text-decoration:none;">Add Payment</a>
										<a class="javascript:void(0)" id="finish_sale_alternate_button" style=" color:white;text-decoration:none;" onclick = "completeSales()">Complete Sale</a>
									</span>


                                </div>

                                <div style="padding:20px">
                                    <div class="side-heading">Comments</div>
                                    <input type="text" name="comment" id="comment" class="form-control" />
                                </div>

                            </div>
                        </div>

                        <form id = "saleSubmit" method = "post" action = "{{route('new_sale')}}">

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        </form>
                    </div></div>

            </div>
        </div>
    </div>


    <!-- Look up receipt Modal -->
    <div id="look-up-receipt" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Lookup Receipt</h4>
                </div>
                <div class="modal-body">
                    <input type = "text" class="form-control" name = "receipt-id" id = "receipt-id" placeholder="Sale Id">
                </div>
                <div class="modal-footer">
                    <button onclick ="lookUpReceipt()" type="button" class="btn btn-info" data-dismiss="modal">Look Up Receit</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="choose_counter_modal" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="chooseCounter">Choose Counter</h4>
                </div>
                <div class="modal-body">
                    <ul class="list-inline choose-counter-home">

                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('additionalJS')
	<style>
  .autocomplete-results {
		position: absolute;
		z-index: 1000;
		margin: 0;
		margin-top: 34px;
		padding: 0;

		border: 1px solid #eee;
		list-style: none;
		border-radius: 4px;
		background-color: #fff;
		box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
  }

  .autocomplete-result {
    list-style: none;
    text-align: left;
    padding: 4px 2px;
    cursor: pointer;
  }

  .autocomplete-result.is-active,.autocomplete-result.is-active a{
    background-color: #3c8dbc;
    color: #fff;
  }

</style>

	<script src="{{asset("js/vue/vue.min.js")}}"></script>
	<script src="{{asset("js/axios/axios.min.js")}}"></script>
	<script src="{{asset("js/lodash/lodash.min.js")}}"></script>

    <script>
		/********autocomplete starts*******/
		Vue.component('auto-complete', {
			template: `<span>
						<input type="text" ref="inlineTextBox"  class="form-control" id ="item-names" v-model="item_names" @keyup.down="onArrowDown" @keyup.up="onArrowUp" @keyup.enter="onEnter">
						<ul ref="autoSuggestion" id="autocomplete-results"
							  v-show="isOpen"
							  class="autocomplete-results"
							  @mouseleave="ClearSelection"
							>
							  <li
								class="loading"
								v-if="isLoading"
							  >
								Loading results...
							  </li>
							  <li v-else v-for="(item, i) in results" :key="i" @click="setResult(item)" class="autocomplete-result" :class="{ 'is-active': i === arrowCounter }"  @mouseover="SetCounter(i)">
								<img height="50px" :src="GetImageUrl(item)" width="50px" style="margin-right:10px" />
								<a href="javascript:void(0);">@{{item.item_name}}</a>
							</li>
						</ul>
					</span>`,
			props: ['autoSelect'],
			data: function(){
				return {
					isOpen: false,
					item_names: "",
					results: [],
					arrowCounter: 0,
					isLoading: false,
				}
			},
			methods: {
				SearchProduct() {
					var that = this;
					if(this.item_names=="")
						return;
					if(this.autoSelect)
					{
						axios.get("{{route('item_list_autocomplete')}}", {
							params: { q: this.item_names, autoselect: true }
							})
							.then(function (response) {
								if( response.data.length==1 )
								{
									that.setResult(response.data[0])
								}
							})
							.catch(function (error) {
							console.log(error);
							});
					}
					else
					{
						axios.get("{{route('item_list_autocomplete')}}", {
							params: { q: this.item_names, autoselect: false }
							})
							.then(function (response) {
								that.isOpen = true;
								that.results = response.data
							})
							.catch(function (error) {
							console.log(error);
							});
					}
				},
				setResult(selectedItem) {
					this.search = selectedItem;
					this.$emit('set-autocomplete-result', selectedItem);
					this.isOpen = false;
					this.results = [];
					this.arrowCounter = -1;
					this.item_names = "";
					document.getElementById("item-names").focus();
				},
				onArrowDown(evt) {
					if (this.arrowCounter < this.results.length) {
					  this.arrowCounter = this.arrowCounter + 1;
					}
				},
				onArrowUp() {
					if (this.arrowCounter > 0) {
					  this.arrowCounter = this.arrowCounter -1;
					}
				},
				onEnter() {
					this.setResult(this.results[this.arrowCounter]);
				},
				handleClickOutside(evt) {
					if (!this.$el.contains(evt.target)) {
					  this.isOpen = false;
					  this.arrowCounter = -1;
					}
				},
				SetCounter(index)
				{
					this.arrowCounter = index;
				},
				ClearSelection()
				{
					this.arrowCounter = -1;
				},
				GetImageUrl(item)
				{
					var img_src = "default-product.jpg";

                    if(item.new_name!=null){
                        img_src = item.new_name;
                    }
					
					var imageUrl = "";
					
					if(item.product_type==1){
                        imageUrl = '{{asset('img')}}/' + "item-kit.png";
                    } else{
						imageUrl = '{{asset('img')}}/' + img_src;
                    }
					return imageUrl;
				}
			},
			created: function () {
				this.debouncedSearch = _.debounce(this.SearchProduct, 1500)
			},
			watch: {
				items: function (val, oldValue) {
					if (val.length !== oldValue.length) {
					  this.results = val;
					  this.isLoading = false;
					}
				},
				item_names: function (newValue, oldValue) {
					if(newValue!=oldValue && newValue!="")
						this.debouncedSearch()
				}
			},
			mounted() {
				document.addEventListener('click', this.handleClickOutside);
				var inputBoxWidth = this.$refs.inlineTextBox.offsetWidth;
				this.$refs.autoSuggestion.style.width = inputBoxWidth+'px';
			},
			destroyed() {
				document.removeEventListener('click', this.handleClickOutside)
			}
		})
	/********autocomplete ends*******/
	
	/********** inline edit starts**************/
	Vue.component('inline-edit', {
		template: `<span>
						<a v-if="!editMode" @click="setEditMode()" href="javascript: void(0);">@{{value}}</a>
						<span v-else>
							<input type="text" v-model="editedValue" sytle="width: 50%;">
							<i class="fa fa-check" @click="setValue"></i>
							<i class="fa fa-times-circle" @click="closeEdit"></i>
						</span>
					</span>`,
		props: ['value','ifUserPermitted'],
		data: function()
		{
			return {editMode: false,editedValue:""};
		},
		methods:{
			setEditMode: function(){
				if(!this.ifUserPermitted)
					return;
				this.editMode = true;
				this.editedValue = this.value;
			},
			setValue()
			{
				this.editMode = false;
				this.$emit('input', this.editedValue)
			},
			closeEdit()
			{
				this.editMode = false;
			}
		}
	});
	/********** inline edit ends**************/
	
	/*************customer select2************/
	Vue.component('select2', {
	  props: ['options', 'value'],
	  template: `<select>
		<slot></slot>
	  </select>`,
	  mounted: function () {
		  console.log("ki hoise");
		var vm = this
		$(this.$el)
		  // init select2
		  .select2({ data: this.options })
		  .val(this.value)
		  .trigger('change')
		  // emit event on change.
		  .on('change', function () {
			vm.$emit('input', this.value)
		  });
	  },
	  watch: {
		value: function (value) {
		  // update value
		  $(this.$el)
			.val(value)
			.trigger('change')
		},
		options: function (options) {
		  // update options
		  $(this.$el).empty().select2({ data: options })
		}
	  },
	  destroyed: function () {
		$(this.$el).off().select2('destroy')
	  }
	})
	/***********customer select2 ends***********************/
	
	var app = new Vue({
		el: '#app',
		data: {
			itemList: [],
			auto_select: true,
			customer_id: 0,
			options: [],
			tax: {{$tax_rate}},
			negativeInventory: {{$settings['negative_inventory']}},
			allDiscountAmountPercentage: 0,
			saleFlatDiscountAmount: 0,
			activePaymentType: "Cash",
			paymentList: [],
			paymentTypeList: ['Cash', 'Check','Debit Card', 'Credit Card', 'Gift Card', 'Loyalty Card'],
			amountTendered: 0.0
		},
		methods:
		{
			setAutoCompleteResult: function(selectedItem)
			{
				if( selectedItem.quantity<=0 && !this.negativeInventory )
				{	
					alert("Sorry, product is out of stock.");
					return;
				}
				var found = false;
				for(var index=0;index<this.itemList.length; index++)
				{
					if(this.itemList[index].item_id==selectedItem.item_id)
					{
						found = true;
						this.itemList[index].bought_quantity++;
					}
				}
				
				if(found)
					return;
				
				var that = this
				
				this.GetItemPrice(selectedItem.item_id)
					.then(function (response) {
						var itemDetails = {
									item_id : selectedItem.item_id,
									item_name : selectedItem.item_name,
									company_name : selectedItem.company_name,
									item_quantity : selectedItem.item_quantity,
									price : response.data.price,
									cost_price: selectedItem.cost_price,
									bought_quantity : 1
								}
						if(selectedItem.discountApplicable)
						{
							itemDetails.discount_applicable = true;
							if(this.allDiscountAmountPercentage==0)
								itemDetails.discount_percentage = selectedItem.discountPercentage;
							else
								itemDetails.discount_percentage = this.allDiscountAmountPercentage;
						}
						else
						{
							itemDetails.discountApplicable = false;
							itemDetails.discount_percentage = 0;
						}
						that.itemList.push(itemDetails);
					})
					.catch(function (error) {
					console.log(error);
					});
			},
			GetItemPrice: function(itemId){
				return axios.post("{{route('item_price')}}", 
					{ 
							item_id: itemId,
							customer_id: this.customer_id 
					})
			},
			Remove: function(itemId){
				for(var index=0;index<this.itemList.length;index++)
				{
					if(this.itemList[index].item_id == itemId)
					{
						this.itemList.splice(index, 1);
					}
				}
			},
			GetLineTotal: function(index)
			{
				return this.itemList[index].bought_quantity * this.itemList[index].price * (100 -  this.itemList[index].discount_percentage)/100;
			},
			SetActivePaymentType: function(activePaymentType)
			{
				this.activePaymentType = activePaymentType;
			},
			GetPaymentButtonClass(paymentType)
			{
				return { 
					btn: true, 
					'btn-pay': true,
					'select-payment': true,
					active: paymentType==this.activePaymentType
				}
			}
			
		},
		watch:{
			customer_id: function (newVal, oldValue) {
				console.log(newVal);
				if(newVal==oldValue || newVal=="" || this.itemList.length<=0)
					return;
				var that = this;
				this.itemList.forEach(function(anItem) {
					this.GetItemPrice(selectedItem.item_id)
					.then(function (response) {
						anItem.price =response.data.price;
					})
					.catch(function (error) {
						console.log(error);
					});
				});
			},
			allDiscountAmountPercentage: function (newVal, oldValue){
				if(newVal==oldValue || newVal==0)
					return;
				this.itemList.forEach(function(anItem){
					anItem.discount_percentage = newVal;
				})
				
			}
		},
		computed:{
			GetSubtotal()
			{
				var subtotal = 0;
				for(var index=0;index<this.itemList.length;index++)
				{
					subtotal += this.GetLineTotal(index);
				}
				return subtotal;
			},
			GetTax()
			{
				return  this.tax * this.GetSubtotal / 100; 
			},
			GetTotalSale()
			{
				return this.GetSubtotal + this.GetTax - this.saleFlatDiscountAmount;
			}
		},
		created: function(){
		},
		mounted() {
			document.getElementById("item-names").focus();
		}
	});
    </script>
@stop