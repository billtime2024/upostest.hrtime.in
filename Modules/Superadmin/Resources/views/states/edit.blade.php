@extends('layouts.app')
@section('title', "Edit State")

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Edit State</h1>
</section>

<!-- Main content -->
<section class="content">
 <form action="{{route('states.update', $state->id)}}" method="post">
     @method('PUT')
     @csrf
      <div class="form-group">
          <label>State Name: </label>
          <input type="text" name="state_name" placeholder="Enter State Name" value="{{$state->state_name}}" />
          @error("state_name")
            <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <div class="form-group">
          <label>State Code: </label>
          <input type="text" name="state_code" placeholder="Enter State Code" value="{{$state->state_code}}" />
          @error("state_code")
            <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <div class="form-group">
          <label>Short Code: </label>
          <input type="text" name="short_code" placeholder="Enter Sort Code" value="{{$state->short_code}}" />
          @error("short_code")
               <br /><span style="color: #f00;">{{$message}}</span>
          @enderror
      </div>
      <button type="submit" class="btn btn-primary" >Submit</button>
 </form>
</section>
<!-- /.content -->
@stop
