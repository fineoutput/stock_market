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
                <div class="col-xl-2 col-md-2">
                    <div class="card bg-danger mini-stat position-relative">
                        <a href="#">
                            <div class="card-body">
                                <div class="mini-stat-desc">
                                    <h6 class="text-uppercase verti-label text-white-50">Today Orders</h6>
                                    <div class="text-white">
                                        <h6 class="text-uppercase mt-0 text-white-50">Today Orders</h6>
                                        <h3 class="mb-3 mt-0">{{$count['todayOrdersCount']}}</h3>
                                        <div class="">
                                            <span class="ml-2">Today Orders</span>
                                        </div>
                                    </div>
                                    <div class="mini-stat-icon">
                                        <i class="mdi mdi-shopping display-2"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-xl-2 col-md-2">
                    <div class="card bg-warning mini-stat position-relative">
                        <a href="#">
                            <div class="card-body">
                                <div class="mini-stat-desc">
                                    <h6 class="text-uppercase verti-label text-white-50">Today P/L Status</h6>
                                    <div class="text-white">
                                        <h6 class="text-uppercase mt-0 text-white-50">Today P/L Status</h6>
                                        <h3 class="mb-3 mt-0">{{$count['profitOrLoss']}}</h3>
                                        <div class="">
                                            <span class="ml-2">Today P/L Status</span>
                                        </div>
                                    </div>
                                    <div class="mini-stat-icon">
                                        <i class="mdi mdi-shopping display-2"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="col-xl-2 col-md-2">
                    <div class="card bg-success mini-stat position-relative">
                        <a href="#">
                            <div class="card-body">
                                <div class="mini-stat-desc">
                                    <h6 class="text-uppercase verti-label text-white-50">Today P/L</h6>
                                    <div class="text-white">
                                        <h6 class="text-uppercase mt-0 text-white-50">Today P/L</h6>
                                        <h3 class="mb-3 mt-0">{{$count['todayProfitLoss']}}</h3>
                                        <div class="">
                                            <span class="ml-2">Today P/L</span>
                                        </div>
                                    </div>
                                    <div class="mini-stat-icon">
                                        <i class="mdi mdi-shopping display-2"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
                                <div class="row">
                                <div class="col-md-10">
                                <h4 class="mt-0 header-title">View {{$title}} List</h4>
                                </div>
                                    
                                    </div>
                                    <hr style="margin-bottom: 50px;background-color: darkgrey;">
                                    <div class="table-rep-plugin">
                                    <div class="table-responsive b-0" data-pattern="priority-columns">
                                    <table id="userTable" class="table  table-striped">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Stock Name</th>
                                        <th>Buy Price</th>
                                        <th>Order Price</th>
                                        <th>SL</th>
                                        <th>Exit Price</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>P/L Status</th>
                                        <th>Qty</th>
                                        <th>Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data  as $data1)
                                        <tr>
                                           
                                            <td >{{ $loop->index+1 }}</td>
                                            <td >{{ $data1->stock_name }}</td>
                                            <td >₹{{ $data1->buy_price }}</td>
                                            <td >₹{{ $data1->order_price }}</td>
                                            <td >₹{{ $data1->sl }}</td>
                                            <td >₹{{ $data1->exit_price }}</td>
                                            <td >{{ date('d F Y  h:i:s A', strtotime($data1->start_time)) }}</td>
                                            <td >{{ date('d F Y  h:i:s A', strtotime($data1->end_time)) }}</td>
                                            <td style="color: {{ $data1->status == 1 ? 'green' : 'red' }}">
                                                {{ $data1->status == 1 ? 'Completed' : 'In Process' }}
                                            </td>
                                            <td style="color: {{ 
                                            $data1->profit_loss_status == 1 ? 'red' : 'green' }}">
                                                {{ $data1->profit_loss_status == 1 ? 'Loss' : 'Profit' }}
                                            </td>
                                            <td >{{ $data1->qty }}</td>
                                            <td >₹{{ $data1->profit_loss_amt }}</td>
                                          
                                           
                                            </tr>  
                                         @endforeach
                                    
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
        