@extends('admin.base_template')
            @section('title',$title)
@section('main')
<!-- Start content -->
<div class="content">
<div class="container-fluid">

<div class="row">
<div class="col-sm-12">
<div class="page-title-box">
<h4 class="page-title">{{$title}}</h4>
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="{{route('Fyers.index')}}">Back</a></li>
<li class="breadcrumb-item active">{{$title}}</li>
</ol>
<div class="state-information d-none d-sm-block">
</div>
</div>
</div>
</div>
<!-- end row -->
<style>
            .form-control {
                border-top: none !important;
                border-left: none !important;
                border-right: none !important;
                border-radius: 0 !important;
            }

            .form-floating>.form-control,
            .form-floating>.form-control-plaintext,
            .form-floating>.form-select {
                height: calc(2.7rem + 2px) !important;
            }
            </style>
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
                                    <span aria-hidden="true"></span>
                                    </div>
                                @endif
                                @if (session('error'))
                                <div class="alert alert-danger" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true"></span>
                                    </div>
                                @endif
                                <!-- End show success and error messages -->
    
                                <h4 class="mt-0 header-title">{{$title}} Form</h4>
                                <hr style="margin-bottom: 50px;background-color: darkgrey;">
                                <form action="{{route('Fyers.store')}}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="id" value="{{$data->id}}">
                                    <div class="form-group row">
                                    	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='phone' type='number' class="form-control @error('phone') is-invalid @enderror" name='phone' value="{{old('phone') ? old('phone') : $data->phone}}" placeholder="Enter Phone Number" required>
 <label for="Phone Number">Enter Phone Number  <span style="color:red;">*</span></label>
</div>
 @error('phone')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='login_id' type='text' class="form-control @error('login_id') is-invalid @enderror" name='login_id' value="{{old('login_id') ? old('login_id') : $data->login_id}}" placeholder="Enter Login Id" required>
 <label for="Login Id">Enter Login Id  <span style="color:red;">*</span></label>
</div>
 @error('login_id')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='pin' type='number' class="form-control @error('pin') is-invalid @enderror" name='pin' value="{{old('pin') ? old('pin') : $data->pin}}" placeholder="Enter Pin" required>
 <label for="Pin">Enter Pin  <span style="color:red;">*</span></label>
</div>
 @error('pin')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='amount' type='number' class="form-control @error('amount') is-invalid @enderror" name='amount' value="{{old('amount') ? old('amount') : $data->amount}}" placeholder="Enter Amount" required>
 <label for="Amount">Enter Amount  <span style="color:red;">*</span></label>
</div>
 @error('amount')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='option_ce' type='text' class="form-control @error('option_ce') is-invalid @enderror" name='option_ce' value="{{old('option_ce') ? old('option_ce') : $data->option_ce}}" placeholder="Enter Option CE" required>
 <label for="Option CE">Enter Option CE  <span style="color:red;">*</span></label>
</div>
<span id="option_ce_Span"></span>
 @error('option_ce')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3" style="margin-top: 23px!important">
<div class="form-floating">
<input id='option_pe' type='text' class="form-control @error('option_pe') is-invalid @enderror" name='option_pe' value="{{old('option_pe') ? old('option_pe') : $data->option_pe}}" placeholder="Enter Option PE" required>
 <label for="Option PE">Enter Option PE  <span style="color:red;">*</span></label>
</div>
<span id="option_pe_Span"></span>
 @error('option_pe')
<div style="color:red">{{$message}}</div>
@enderror
</div>
	   <div class="col-sm-6 my-3">
<div class='form-group has-float-label'>
<label for='Trading'>Trading Type <span style='color:red'> *</span></label>
<select id='Trading' name='Trading' required class="form-control @error('Trading') is-invalid @enderror">
<option value="1">Testing</option>
<option value="2">Live</option>
</select>
</div>
</div>

                                    <div class="form-group">
                                    <div class="w-100 text-center">
                                    <button type="submit" style="margin-top: 10px;" class="btn btn-danger"><i class="fa fa-user"></i> Submit</button>

                                    </div>
                                    </div>
                                    </div>
                                    </form>

                                    </div>
                                    </div>
                                    </div> 
                                    </div> 
    
                                    </div>
                                    <!-- end page content-->
    
                                    </div> 
    
                                    </div> 
                                    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                                    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function(){

  	$("#option_ce").change(function(){
		var vf=$(this).val();
		if(vf==""){
			return false;

		}else{
      // Clear the span text
      $('#option_ce_Span').text('');
			$.ajax({
				url:"{{ url('getPrice') }}?isl=" + vf;
				data : '',
				type: "get",
				success : function(html){
						if(html!="NA")
						{
						 $('#option_ce_Span').text(html); // Using the title from the response
						}
						else
						{
							alert('No Option Found');
							return false;
						}

					}

				})
		}


	})
  });

    </script>

<script>
    $(document).ready(function(){

  	$("#option_pe").change(function(){
		var vf=$(this).val();
		if(vf==""){
			return false;

		}else{
      // Clear the span text
      $('#option_pe_Span').text('');
			$.ajax({
				url:"{{ url('getPrice') }}?isl=" + vf;
				data : '',
				type: "get",
				success : function(html){
						if(html!="NA")
						{
						 $('#option_pe_Span').text(html); // Using the title from the response
						}
						else
						{
							alert('No Option Found');
							return false;
						}

					}

				})
		}


	})
  });

    </script>

@endsection
            
        