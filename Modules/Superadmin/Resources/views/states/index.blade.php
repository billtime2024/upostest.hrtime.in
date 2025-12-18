@extends('layouts.app')
@section('title', "State")

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">State</h1>
</section>

<!-- Main content -->
<section class="content">
    <button class="btn btn-primary"><a style="color: white;" href="{{route('states.create')}}">Create State</a></button>
    <table style="width: 100%;">
        <thead>
            <tr>
                <th>Sr.No.</th>
                <th>State Name</th>
                <th>State Code</th>
                <th>Short Code</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($states as $state)
                <tr >
                    <td>{{$loop->iteration}}</td>
                    <td>{{$state->state_name}}</td>
                    <td>{{$state->state_code}}</td>
                    <td>{{$state->short_code}}</td>
                    <td><button style="margin: 1rem;" id="edit" class="btn btn-primary" type="buton"><a  style="color: #fff;" href="{{route('states.edit', $state-> id)}}">Edit</a></button></td>
                    <td>
                        <form action="{{route('states.destroy', $state->id)}}" method="post">
                            @method("DELETE")
                            @csrf
                            <input type="hidden" name="id" value="{{$state->id}}" />
                            <button style="margin: 1rem;" class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                    <!--<td><button style="margin: 1rem;" id="delete" class="btn btn-danger" type="button">Delete</button></td>-->
                </tr>
            @endforeach
        </tbody>
    </table>

</section>
<!-- /.content -->
@stop
