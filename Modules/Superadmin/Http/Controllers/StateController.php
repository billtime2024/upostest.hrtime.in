<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\State;

class StateController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $states = State::all();
        return view("superadmin::states.index", compact("states"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('superadmin::states.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'state_name' => 'required|string',
            'state_code' => 'required|integer',
            'short_code' => 'required|string|min:2|max:2',
        ]);
        
        $state = new State();
        $state->state_name = $request->state_name;
        $state->state_code = $request->state_code;
        $state->short_code = $request->short_code;
        $state->save();
        return redirect()->route("states.index")->with('status', ['success' => 1, 'msg' => "State Created successfully"]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(State $state)
    {
        return view("superadmin::states.edit", compact('state'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, State $state)
    {
         $validatedData = $request->validate([
            'state_name' => 'required|string',
            'state_code' => 'required|integer',
            'short_code' => 'required|string|min:2|max:2',
        ]);

        $state->state_name = $request->state_name;
        $state->state_code = $request->state_code;
        $state->short_code = $request->short_code;
        $state->save();
        return redirect()->back()->with('status', ['success' => 1, 'msg' => "State Updated successfully"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(State $state)
    {
        $state->delete();
         return redirect()->back()->with('status', ['success' => 1, 'msg' => "State Deleted successfully"]);
    }
}
