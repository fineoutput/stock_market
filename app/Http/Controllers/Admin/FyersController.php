<?php
            namespace App\Http\Controllers\Admin;
            use App\Http\Controllers\Controller;
            use App\adminmodel\FyersModal;
            
            use App\Models\Admin\AdminSidebar1;
            use Illuminate\Http\Request;
            use Illuminate\Support\Facades\Hash;
            use Illuminate\Support\Facades\Storage;
            use Illuminate\Support\Facades\Auth;

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
                    return view('admin/Fyers.create', compact('data''title'));
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
                

                }

        