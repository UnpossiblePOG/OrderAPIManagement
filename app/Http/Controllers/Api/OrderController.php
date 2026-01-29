<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,$page_no=1)
    {
        //echo $page_no;exit;
        if($page_no==0)
        {
            $page_no=1;
        }
        $page_no=($page_no-1)*5;
        $filter = $request->all();
        $where_array=array();
        $where_args=array();
        if(isset($filter['status']) && trim($filter['status'])!='')
        {
            $where_array[]="orders.status=?";
            $where_args[]=$filter['status'];
        }
        if(isset($filter['from_date']) && trim($filter['from_date'])!='')
        {
            $where_array[]="date(orders.created_at)>=?";
            $where_args[]=$filter['from_date'];
            
        }
        if(isset($filter['to_date']) && trim($filter['to_date'])!='')
        {
            $where_array[]="date(orders.created_at)<=?";
            $where_args[]=$filter['to_date'];
            
        }
        $where_string = "";
        if(sizeof($where_array)>0)
        {
            $where_string = " where " .implode(" AND ",$where_array);
        }
        $select_query=array();
        $response = array();
        $http_response = 200;
        try {
            //$count_query = $query = Order::join('order_items', function ($join) {
                //$join->on('orders.id', '=', 'order_items.order_id');
            //});
            $count_query = Order::query();
            $query = Order::query();
            
        if(isset($filter['status']) && trim($filter['status'])!='')
        {
            $query->where('orders.status', '=', $filter['status']);
            $count_query->where('orders.status', '=', $filter['status']);
        }
        if(isset($filter['from_date']) && trim($filter['from_date'])!='')
        {
            $query->where('orders.created_at', '>=', $filter['from_date']);
            $count_query->where('orders.created_at', '>=', $filter['from_date']);
            
        }
        if(isset($filter['to_date']) && trim($filter['to_date'])!='')
        {
            $query->where('orders.created_at', '<=', $filter['to_date']);
            $count_query->where('orders.created_at', '<=', $filter['to_date']);
            
        }

        //$count_query->select('count(id)');//get();
        $cnt = $count_query->count();
        
        $query->select('orders.id',
        'orders.order_number',
        'orders.customer_name',
        'orders.customer_email',
        'orders.total_amount',
        'orders.status',
        'orders.created_at',
        //'order_items.product_name',
        //'order_items.quantity',
        //'order_items.price'
        );
        $select_query = $query->orderBy('id', 'desc')->offset($page_no)->limit(5)->get();
        $response['status']='success';
        $response['total_count']=$cnt;
        foreach ($select_query as $key => $value) {
            //echo "<pre>";print_r($value->id);exit;
            $select_query_2 = DB::select('SELECT order_items.product_name,order_items.quantity,order_items.price FROM order_items where order_id=?',[$value->id]);
            //echo "<pre>";print_r($select_query_2);exit;
            $select_query[$key]['products']=$select_query_2;
            
        }
        //echo "<pre>";print_r($select_query);exit;

        $response['data']=$select_query;
        } catch (\Throwable $th) {
            $select_query=array();
            $http_response = 500;
            $response['status']='error';
            $response['data']=$select_query;
        }
        $response['response_code']=$http_response;
        return response()->json($response,$http_response);    
    }

    /**
     * Store a newly created resource in storage.
     */
    public function calculateOrderTotal($items)
    {
        $total = 0;
        foreach ($items as $key=>$item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function store(Request $request)
    {
        $http_response = 200;
        $response['status']='success';
        $response['message']='';
        if(Str::isJson($request->getContent())=="1")
        {
            $json_data = json_decode($request->getContent(),true);
            $response['data']=array();
            
            $name =  htmlspecialchars(filter_var(trim($json_data['customer_name']),FILTER_SANITIZE_STRING));
            $email =  $json_data['customer_email'];

            $email_sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
            $all_products_valid = 1;
            $product_array=array();
            $products_amount_qty = array();
            if (filter_var($email_sanitized, FILTER_VALIDATE_EMAIL)) 
            {
                $email = $email_sanitized;
                if(!isset($json_data['items']) || sizeof($json_data['items'])==0)
                {
                    $http_response = 500;
                    $response['status']='error';
                    $response['message']='Product items are not presents.';    
                }
                for($i=0;$i<sizeof($json_data['items']);$i++)
                {
                    $product_name = trim($json_data['items'][$i]['product_name']);
                    $product_qty = trim($json_data['items'][$i]['quantity']);
                    $product_price = trim($json_data['items'][$i]['price']);
                    if($product_name=="" || $product_qty=="" || $product_price=="")
                    {
                        $http_response = 500;
                        $response['status']='error';
                        $response['message']='Product details are not valid.';   
                        $all_products_valid = 0;break; 
                    }
                    else if($product_qty<=0)
                    {
                        $http_response = 500;
                        $response['status']='error';
                        $response['message']='Product quantity is not valid.';    
                        $all_products_valid = 0;break;
                    }
                    else if($product_price<=0)
                    {
                        $http_response = 500;
                        $response['status']='error';
                        $response['message']='Product amount is not valid.';    
                        $all_products_valid = 0;break;
                    }
                    else
                    {
                        //$product_total=$product_total+($product_price*$product_qty);
                        $product_array[]=[
                            'order_id'=>0,
                            'product_name'=>$product_name,
                            'quantity'=>$product_qty,
                            'price'=>$product_price,
                        ];
                    }
                }
                if($all_products_valid==1)
                {
                        $product_total = $this->calculateOrderTotal($product_array);
                        DB::beginTransaction();
                        try {
                        
                        $insert_array = [
                            'order_number' => 'ORD'.strtotime(date("Y-m-d H:i:s")).rand(9999,99999),
                            'customer_name' => $name,
                            'customer_email' => $email,
                            'total_amount' => $product_total,
                            'created_at' => date("Y-m-d H:i:s")
                        ];
                        //DB::table('orders')->insert($insert_array);
                        //$order = Order::create($insert_array);exit;
                        //$lastId = DB::getPdo()->lastInsertId();
                        $order = new Order;
                        $order->order_number = 'ORD'.strtotime(date("Y-m-d H:i:s")).rand(9999,99999);
                        $order->customer_name = $name;
                        $order->customer_email = $email;
                        $order->total_amount = $product_total;
                        $order->created_at = date("Y-m-d H:i:s");
                        $order->save();
                        $lastId = $order->id;
                        for($i=0;$i<sizeof($product_array);$i++)
                        {
                            $product_array[$i]['order_id']=$lastId;
                        }
                        DB::table('order_items')->insert($product_array);
                        DB::commit();    
                        } catch (\Throwable $th) {
                        DB::rollBack(); 
                        $http_response = 500;
                        $response['status']='error';
                        $response['message']="Something went wrong.";    
                        }
                }
            
            } else {
                $http_response = 500;
                $response['status']='error';
                $response['message']='Email is not valid.';
            }
        }
        else
        {
            $http_response = 500;
            $response['status']='error';
            $response['data']="JSON is not valid";
        }
        if($http_response==200)
        {
            $response['message']='Order is generated successfully.';    
        }
        $response['response_code']=$http_response;
        return response()->json($response,$http_response);    
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $http_response = 200;
        $response['status']='success';
        $response['message']='Order is updated successfully.';
        if(Str::isJson($request->getContent())=="1")
        {
            //$select_query = DB::select('SELECT orders.id FROM orders where id=?',[$id]);
            $select_query = Order::find($id);
            //echo "<pre>";print_r($select_query->id);exit;
            if(!isset($select_query->id))
            {
                $http_response = 500;
                $response['status']='error';
                $response['message']="Order ID is not present.";    
            }
            else
            {
                $json_data = json_decode($request->getContent(),true);
                
                try
                {
                    DB::beginTransaction();
                    DB::table('orders')->where('id', $id)->update(['status' => $json_data['status']]);   
                    DB::commit();
                } 
                catch (\Throwable $th) 
                {
                    DB::rollBack(); 
                    $http_response = 500;
                    $response['status']='error';
                    $response['message']='Something went wrong.';    
                }
            }
        }
        else
        {
            $http_response = 500;
            $response['status']='error';
            $response['data']=$select_query;
        }
        
        $response['response_code']=$http_response;
        return response()->json($response,$http_response);    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
