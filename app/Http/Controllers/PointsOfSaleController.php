<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Attribute;
use App\PointOfSale;
use App\AddSupply;
use App\BuyingTransaction;

class PointsOfSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        #$pos = PointOfSale::orderBy('created_at','desc')->paginate(10); //pagination   
        #$pos = PointOfSale::all();
        //$products = Product::where('pos_id',$id)->get();
        $pos = PointOfSale::where('user_id', auth()->user()->id)->get();
        //print_r(auth()->user()->id);
        //print_r($pos);
        return view('pointsofsale.index')->with('points_of_sale',$pos);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pointsofsale.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*
        $this->validate($request, [
            'pos_name' => 'required',
            'description' => 'required',
            'cover_image' => 'image|nullable|max:1999|required'
        ]); */

        //Cover Image upload
        if($request->hasFile('cover_image')){
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            $path = $request->file('cover_image')->storeAs('public/cover_images', $fileNameToStore);
        }else{
            $fileNameToStore = 'noimage.jpg';
        }

        #Create POS Object        
        $pos = new PointOfSale;
        $pos->name = $request->input('pos_name');
        $pos->description = $request->input('description');
        $pos->cover_image = $fileNameToStore;
        $pos->user_id = auth()->user()->id;
        $pos->save();

        #Get all Product Names
        $products = array();
        for($x = 0; $request->input('product'.$x) !== null ; $x++){
            array_push($products, $request->input('product'.$x));
        }

        for($i = 0; $i < count($products); $i++){
            $product = new Product; //Create Product Object
            $product->name = $products[$i]; //Product Name
            $product->pos_id = $pos->id;
            $product->price = $request->input('productprice'.$i);
            $product->save();

            for($x = 0; $x < 20; $x++){ //20 attributes max?
                #Create Attributes
                if($request->input('product'.$i.'a'.$x.'name') != '' 
                && $request->input('product'.$i.'a'.$x.'value') != ''){
                    $attribute = new Attribute;
                    $attribute->name = $request->input('product'.$i.'a'.$x.'name');
                    $attribute->value = $request->input('product'.$i.'a'.$x.'value');
                    $attribute->product_id = $product->id;
                    $attribute->save();  
                }
            }
        } 
        
        $pos = PointOfSale::where('user_id', auth()->user()->id)->get();
        return view('pointsofsale.index')->with('points_of_sale',$pos);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $point_of_sale = PointOfSale::find($id);
        $products = Product::where('pos_id',$id)->get();
        
        foreach($products as $product)
        {
            
            $supply = 0;
            $buying= 0;

            $addsupply = AddSupply::where('product_id', $product->id)->get();
            $buyingtrans = BuyingTransaction::where('product_id', $product->id)->get();

            foreach($addsupply as $sup)
            {
                $supply = (float)$supply + (float)$sup->quantity;
            }
            
            foreach($buyingtrans as $buy)
            {
                $buying = (float)$buying + (float)$buy->quantity;
            }
            
            $available_supply = $supply - $buying;
            $product->allsupply = $supply;
            $product->allbuying = $buying;

            if(is_numeric($available_supply))
            {
                $product->supply = $available_supply;
            }
            else
            {
                $product->supply = 0;
            } 

            
        }

        $total_income = 0;

        foreach($products as $product)
        {
            $total_income = $total_income + ($product->allbuying * $product->price);
        }

        $new_products = array();
        foreach($products as $product)
        {
            $new_products[] = 
                array(
                    "ProductName" =>  $product->name, 
                    "ProductPrice" =>  $product->price,
                    "Attributes" => Attribute::where('product_id', $product->id)->get(),
                    "CurrentSupply" => $product->supply,
                    "AllSupply" => $product->allsupply,
                    "AllBuying" => $product->allbuying
                );
        }

        

        return view('pointsofsale.show')->with('point_of_sale', $point_of_sale)->with('products',  $new_products)->with('total_income', $total_income); 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
