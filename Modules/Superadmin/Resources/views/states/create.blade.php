@extends('layouts.app')
@section('title', "Create State")

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Create State</h1>
</section>

<!-- Main content -->
<section class="content">
 <form action="{{route('states.store')}}" method="post">
     @csrf
      <div class="form-group">
          <label>State Name: </label>
          <input type="text" name="state_name" placeholder="Enter State Name"  />
          @error("state_name")
            <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <div class="form-group">
          <label>State Code: </label>
          <input type="text" name="state_code" placeholder="Enter State Code" />
          @error("state_code")
            <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <div class="form-group">
          <label>Short Code: </label>
          <input type="text" name="short_code" placeholder="Enter Sort Code" />
          @error("short_code")
               <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <button type="submit" class="btn btn-primary" >Submit</button>
 </form>
</section>
<!-- /.content -->
@stop
