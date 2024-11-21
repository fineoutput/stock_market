 

            @extends('admin.base_template')
            @section('title',$title)
            @section('main')
            <!-- Start content -->
                <div class="content">
                <div class="container-fluid">
                <div class="row">
                <div class="col-sm-12">
                <div class="page-title-box">
                <h4 class="page-title">View {{$title}}</h4>
                <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0);">{{$title}}</a></li>
                <li class="breadcrumb-item active">View {{$title}}</li>
                </ol>
                <div class="state-information d-none d-sm-block">
                </div>
                </div>
                </div>
                </div>
            <!-- end row -->
            <div class="page-content-wrapper">
            <div class="row">
            <div class="col-12">
            <div class="card m-b-20">
            <div class="card-body">
            <!-- show success and error messages -->
                                @if (session('success'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                    </div>
                                @endif
                                @if (session('error'))
                                <div class="alert alert-danger" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                    </div>
                                @endif
                                <!-- End show success and error messages -->
                                <div class="row">
                                <div class="col-md-10">
                                <h4 class="mt-0 header-title">View {{$title}} List</h4>
                                </div>
                                    @if(session()->get('position') == "Super Admin" || session()->get('position') == "Admin")
                                    <div class="col-md-2"> <a class="btn btn-info cticket" href="{{route('Fyers.create')}}" role="button" style="margin-left: 20px;"> Add {{$title}}</a></div>
                                    @endif
                                    </div>
                                    <hr style="margin-bottom: 50px;background-color: darkgrey;">
                                    <div class="table-rep-plugin">
                                    <div class="table-responsive b-0" data-pattern="priority-columns">
                                    <table id="userTable" class="table  table-striped">
                                    <thead>
                                    <tr>
                                    <th>#</th>
                                             	 <th>Phone Number</th> 
 	 <th>Login Id</th> 
 	 <th>Pin</th> 
 	 <th>Amount</th> 
 	 <th>Option CE</th> 
 	 <th>Option PE</th> 
 	 <th>Trading</th> 

                                            <th>Status</th>
                                            @if(session()->get('position') == "Super Admin" || session()->get('position') == "Admin")
                                            <th data-priority="6">Action</th>
                                            @endif
                                            </tr>
                                            </thead>
                                            <tbody>
                                        @if(!empty($foreachData))
                                        @foreach($foreachData as $data)
                                        <tr>
                                        <th>{{$loop->iteration}}</th>
                                            	 <td>{{$data->phone }} </td> 
 	 <td>{{$data->login_id }} </td> 
 	 <td>{{$data->pin }} </td> 
 	 <td>{{$data->amount }} </td> 
 	 <td>{{$data->option_ce }} </td> 
 	 <td>{{$data->option_pe }} </td>
      @if($data->trading_type == "1")
      <td>
          <p class="label  status-inactive">Testing</p>
     </td>
     @else
    <td>
        <p class="label  status-active">Live</p>
    </td> 
    @endif
 
                                            @if($data->is_active == "1")
                                            <td>
                                            <p class="label  status-active">Active</p>
                                            </td>
                                            @else
                                            <td>
                                            <p class="label  status-inactive">Inactive</p>
                                            </td>
                                            @endif
                                            @if(session()->get('position') == "Super Admin" || session()->get('position') == "Admin")
                                            <td>
                                            <div class="btn-group" id="btns{{$loop->iteration}}">
                                                    @if($data->is_active == 0)
                                                    <a href="{{route('Fyers.show',base64_encode($data->id))}}"><i class="fas fa-check success-icon" data-toggle="tooltip" data-placement="top" title="Active"></i></a>
                                                    @else
                                                    <a href="{{route('Fyers.show',base64_encode($data->id))}}"><i class="fas fa-times danger-icon" data-toggle="tooltip" data-placement="top" title="Inactive"></i></a>
                                                    @endif
                                                    <a href="{{route('Fyers.edit',base64_encode($data->id))}}"><i class="fas fa-pencil-alt info-icon" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>
                                                    @if(session()->get('position') == "Super Admin")
                                                    <a href="javascript:();" class="dCnf" mydata="{{$loop->iteration}}" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fas fa-trash danger-icon"></i></a>
                                                    @endif
                                                    </div>
                                                    <div style="display:none" id="cnfbox{{$loop->iteration}}">
                                                    <p> Are you sure delete this </p>
                                                    <form method="post" action="{{ route('Fyers.destroy', base64_encode($data->id)) }}" style="display:inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Yes</button>
                                                        </form>
                                                        <a href="javascript:();" class="cans btn btn-default" mydatas="{{$loop->iteration}}">No</a>

                                                        </div>
                                                        </td>
                                            @endif
                                            </tr>   
                                         @endforeach
                                         @endif
                                         </tbody>
                                         </table>
                                         </div>
                                         </div>
                                         </div>
                                         </div>
                                         </div> <!-- end col -->
                                         </div> <!-- end row -->
                                         </div>
                                         <!-- end page content-->
                                         </div> <!-- container-fluid -->
                                         </div> <!-- content -->
                                    @endsection
        