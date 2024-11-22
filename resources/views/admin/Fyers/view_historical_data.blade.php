 

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
                                    
                                    </div>
                                    <hr style="margin-bottom: 50px;background-color: darkgrey;">
                                    <div class="table-rep-plugin">
                                    <div class="table-responsive b-0" data-pattern="priority-columns">
                                    <table id="userTable" class="table  table-striped">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Open</th>
                                        <th>Close</th>
                                        <th>High</th>
                                        <th>Low</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historicalData  as $data)
                                        <tr>
                                            <td>{{ $loop->index+1 }}</td>
                                            {{-- <td>{{ $data->date }}</td> --}}
                                            <td>{{ date('d F Y  h:i:s A', strtotime($data->date)) }}</td>
                                            <td>₹{{ $data->open }}</td>
                                            <td>₹{{ $data->close }}</td>
                                            <td>₹{{ $data->high }}</td>
                                            <td>₹{{ $data->low }}</td>
                                            @if($data->open_status == "1")
                                            <td><p class="label  status-inactive">NO Entry</p></td>
                                            @elseif ($data->open_status == "0")
                                            <td><p class="label  status-active">Entry</p></td> 
                                            @else
                                            <td></td> 
                                            @endif
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
        