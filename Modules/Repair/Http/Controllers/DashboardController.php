<?php

namespace Modules\Repair\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Repair\Utils\RepairUtil;
use App\BusinessLocation;
use Carbon;



class DashboardController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $repairUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(RepairUtil $repairUtil)
    {
        $this->repairUtil = $repairUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    // public function index(Request $request)
    // {
    //     $business_id = $request->session()->get('user.business_id');
    //     $location_id = $request->get('location_id', null);

    //     $job_sheets_by_status = $this->repairUtil->getRepairByStatus($business_id, $location_id);
    //     $job_sheets_by_service_staff = $this->repairUtil->getRepairByServiceStaff($business_id, $location_id);
    //     $trending_brand_chart = $this->repairUtil->getTrendingRepairBrands($business_id);
    //     $trending_devices_chart = $this->repairUtil->getTrendingDevices($business_id);
    //     $trending_dm_chart = $this->repairUtil->getTrendingDeviceModels($business_id);
    //     $business_locations = BusinessLocation::forDropdown($business_id);

    //     return view('repair::dashboard.index')
    //     ->with(compact('job_sheets_by_status', 'job_sheets_by_service_staff', 'trending_devices_chart', 'trending_dm_chart', 'trending_brand_chart', 'business_locations', 'location_id'));
    // }

    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);
        // $date_range = $request->get('dashboard_filter_date');
        $date_range = request()->get('dashboard_filter_date') ?? '';
        // dd($date_range);


        if (!empty($date_range)) {
            // Check for different delimiter formats to avoid datatable errors
            if (strpos($date_range, ' - ') !== false) {
                $date_parts = explode(' - ', $date_range);
            } elseif (strpos($date_range, ' ~ ') !== false) {
                $date_parts = explode(' ~ ', $date_range);
            } else {
                $date_parts = explode(' - ', $date_range);
            }

            if (count($date_parts) == 2) {
                try {
                    $start_date = Carbon::createFromFormat('d-m-Y', trim($date_parts[0]))->startOfDay();
                    $end_date = Carbon::createFromFormat('d-m-Y', trim($date_parts[1]))->endOfDay();
                } catch (\Exception $e) {
                    dd('Error parsing date range: ', $e->getMessage(), $date_range, $date_parts[0], $date_parts[1]);
                }
            } else {
                $start_date = Carbon::now()->startOfYear()->startOfDay();
                $end_date = Carbon::now()->endOfDay();
            }
        } else {
            $start_date = Carbon::now()->startOfYear()->startOfDay();
            $end_date = Carbon::now()->endOfDay();
        }

        // Ensure start_date and end_date are set correctly
        if (is_null($start_date) || is_null($end_date)) {
            dd('Start date or end date is null', $start_date, $end_date, $date_range);
        }


        // dd($start_date);
        // dd($end_date);
        $job_sheets_by_status = $this->repairUtil->getRepairByStatus($business_id, $location_id, $start_date, $end_date);
        $job_sheets_by_service_staff = $this->repairUtil->getRepairByServiceStaff($business_id, $location_id, $start_date, $end_date);
        $trending_brand_chart = $this->repairUtil->getTrendingRepairBrands($business_id, $start_date, $end_date);
        $trending_devices_chart = $this->repairUtil->getTrendingDevices($business_id, $start_date, $end_date);
        $trending_dm_chart = $this->repairUtil->getTrendingDeviceModels($business_id, $start_date, $end_date);
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('repair::dashboard.index')
        ->with(compact('job_sheets_by_status', 'job_sheets_by_service_staff', 'trending_devices_chart', 'trending_dm_chart', 'trending_brand_chart', 'business_locations', 'location_id','date_range'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('repair::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('repair::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('repair::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}