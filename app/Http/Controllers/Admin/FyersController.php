<?php
            namespace App\Http\Controllers\Admin;
            use App\Http\Controllers\Controller;
            use App\adminmodel\FyersModal;
            use Illuminate\Support\Facades\DB; // Import the DB facade
            use App\Models\Admin\AdminSidebar1;
            use Illuminate\Http\Request;
            use Illuminate\Support\Facades\Hash;
            use Illuminate\Support\Facades\Storage;
            use Illuminate\Support\Facades\Auth;
            use Illuminate\Support\Facades\Http;
            


            class FyersController extends Controller
            {

                public function index(Request $req)
                {
                    
                    $foreachData = FyersModal::wherenull('deleted_at')->latest()->get();
                    $title =  "Fyers";
                    return view('admin/Fyers.index', compact('foreachData','title'));
                
                }


                public function create(Request $req)
                {
              
                    
                    $data = new FyersModal();
                    $title =  "Add Fyers";
                    return view('admin/Fyers.create', compact('data','title'));
               
                }

                public function store(Request $request)
                {
                    if ($request->id === null) {
                        $this->validate($request, [
                        	 'phone' =>'string|required',
	 'login_id' =>'string|required',
	 'pin' =>'string|required',
	 'amount' =>'string|required',
	 'option_ce' =>'string|required',
	 'option_pe' =>'string|required',
	 'trading_type' =>'string|required',

                        ]);
                    } else {
                        $this->validate($request, [
                        	 'phone' =>'string|required',
	 'login_id' =>'string|required',
	 'pin' =>'string|required',
	 'amount' =>'string|required',
	 'option_ce' =>'string|required',
	 'option_pe' =>'string|required',
	 'trading_type' =>'string|required',

                        ]);
                    }
                    if ($request->id === null) {
                        $Fyers = new FyersModal();
                        
                    } else {
                        $Fyers = FyersModal::where('id', $request->id)->first();
                    }
                   
                    

                    $userId = Auth::id();
                     	 $Fyers->phone= $request->phone; 
 	 $Fyers->login_id= $request->login_id; 
 	 $Fyers->pin= $request->pin; 
 	 $Fyers->amount= $request->amount; 
 	 $Fyers->option_ce= $request->option_ce; 
 	 $Fyers->option_pe= $request->option_pe; 
 	 $Fyers->trading_type= $request->trading_type; 

                    $Fyers->ip = $request->ip();
                    $Fyers->is_active = 1;
                    $Fyers->date = now()->getTimestamp();
                    $Fyers->added_by = $userId;
                    $Fyers->save();
                    if ($Fyers) {
                        if ($request->id === null) {
                            return redirect()->route('Fyers.index')->with('success', 'Fyers Added Successfully!');
                        } else {
                            return redirect()->route('Fyers.index')->with('success', 'Fyers Updated Successfully');
                        }
                    } else {
                        return redirect()->back()->with('error', 'Something Went Wrong');
                    }
                }

                public function show(Request $req, $idd)
                {
                    if ($req->session()->get('position') == "Super Admin") {
                        $id = base64_decode($idd);
                        $Fyers = FyersModal::findOrFail($id);
                        $Fyers->is_active = !$Fyers->is_active;
                        $Fyers->save();
                     
                        return redirect()->route('Fyers.index')->with('success', 'Status updated Successfully!');
                    } else {
                        return redirect()->back()->with('error', 'Sorry you dont have Permission to update admin, Only Super admin can change status');
                    }
                }

                public function edit(Request $req, $idd)
                {
                    $id = base64_decode($idd);
                    
                    $title = "Fyers";
                    $data = FyersModal::where('id', $id)->first();
                    return view('admin/Fyers.create', compact('data','title'));
                }

                    public function destroy(Request $req, $idd)
                    {
                        if ($req->session()->get('position') == "Super Admin") {
                        $id = base64_decode($idd);
                        $Fyers = FyersModal::findOrFail($id);
                        $Fyers->delete();
                        return redirect()->route('Fyers.index')->with('success', 'Fyers deleted Successfully!');
                    } else {
                        return redirect()->back()->with('error', 'Sorry you dont have Permission to delete admin, Only Super admin can change status');
                    }
                    }

                    public function getPrice(Request $request)
                {
                    $isl = $request->input('isl');
                    
                  
                        // Get the authorization code (implement this method as needed)
                        $authCode = $this->authCode(); 
                
                        // Prepare the API endpoint
                        $url = 'https://api-t1.fyers.in/data/quotes?symbols=' . $isl;
                
                        // Make the GET request
                        $response = Http::withHeaders([
                            'Authorization' => 'TB70PSUQ00-100:' . $authCode,
                        ])->get($url);
                
                        // Decode the JSON response
                        $data = json_decode($response->getBody()->getContents());
                
                        // Check for errors in the response
                        if (isset($data->s) && $data->s == "error") {
                            return "err"; // Return error if API response indicates an error
                        }
                
                        // Process the response
                        $quoteData = $data->d[0] ?? null; // Get the first data item
                
                        if ($quoteData) {
                            $v = $quoteData->v; // Access the nested 'v' object
                            $ask = $v->ask ?? null; // Get the ask price
                            $bid = $v->bid ?? null; // Get the bid price
                            
                            // Handle NIFTY case
                            if ($isl == 'NIFTY') {
                                return $v->cmd->c; // Return command c for NIFTY
                            }
                
                            // Return ask price or bid price if ask is 0
                            return $ask == 0 ? "Price - ₹".$bid : "Price - ₹".$ask;
                        }
                
                        return null; // Return null if no data found
                    
                }

                public function authCode()
                {
                    // Fetch the latest auth_code from the tbl_config table
                    $result = DB::table('tbl_config')
                                ->select('auth_code')
                                ->orderBy('id', 'DESC')
                                ->first(); // Get the first record
                
                    // Return the auth_code if available, otherwise return null
                    return $result ? $result->auth_code : null;
                }
                

                }

        