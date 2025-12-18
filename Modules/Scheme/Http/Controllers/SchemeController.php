<?php

namespace Modules\Scheme\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Scheme\Entities\Scheme;
use Yajra\DataTables\Facades\DataTables;

class SchemeController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Calculate total eligible amount for a scheme
     */
    private function calculateTotalEligibleAmount($scheme)
    {
        $business_id = request()->session()->get('user.business_id');
        $variation_ids = $scheme->variations->pluck('id')->toArray();

        if (empty($variation_ids) && $scheme->product_id) {
            $variation_ids = DB::table('variations')->where('product_id', $scheme->product_id)->pluck('id')->toArray();
        }

        // Get total purchase amount (inclusive of tax)
        $total_purchase_amount = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('tsl.variation_id', $variation_ids)
            ->whereBetween('t.transaction_date', [$scheme->starts_at, $scheme->ends_at])
            ->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));

        // Get total sold quantity for eligible amount calculation
        $total_sold_quantity = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('tsl.variation_id', $variation_ids)
            ->whereBetween('t.transaction_date', [$scheme->starts_at, $scheme->ends_at])
            ->sum('tsl.quantity');

        // Get total purchase value (exclusive of tax) for sold quantities
        $total_purchase_value = DB::table('transaction_sell_lines_purchase_lines as tslpl')
            ->join('purchase_lines as pl', 'tslpl.purchase_line_id', '=', 'pl.id')
            ->join('transaction_sell_lines as tsl', 'tslpl.sell_line_id', '=', 'tsl.id')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('tsl.variation_id', $variation_ids)
            ->whereBetween('t.transaction_date', [$scheme->starts_at, $scheme->ends_at])
            ->sum(DB::raw('tslpl.quantity * pl.purchase_price'));

        $eligible_amount = 0;

        if ($scheme->multi_level && $scheme->enable_slab && $scheme->slabs->count() > 0) {
            // Multi-level slab calculation: per product incentives
            $selected_variations = collect();
            foreach ($scheme->slabs as $slab) {
                if (!empty($slab->variation_ids)) {
                    $selected_variations = $selected_variations->merge($slab->variation_ids);
                }
            }
            $selected_variations = $selected_variations->unique()->values()->all();

            $variation_quantities = [];
            if (!empty($selected_variations)) {
                $variation_quantities = DB::table('transaction_sell_lines as tsl')
                    ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'sell')
                    ->where('t.status', 'final')
                    ->whereIn('tsl.variation_id', $selected_variations)
                    ->whereBetween('t.transaction_date', [$scheme->starts_at, $scheme->ends_at])
                    ->select('tsl.variation_id', DB::raw('SUM(tsl.quantity) as qty'))
                    ->groupBy('tsl.variation_id')
                    ->pluck('qty', 'variation_id')
                    ->toArray();
            }

            foreach ($variation_quantities as $var_id => $qty) {
                $applicable_slabs = $scheme->slabs->filter(function($slab) use ($var_id, $qty) {
                    return in_array($var_id, $slab->variation_ids ?? []) &&
                           $qty >= $slab->from_amount &&
                           ($slab->to_amount === null || $qty <= $slab->to_amount);
                });

                $total_incentive = $applicable_slabs->sum('value'); // Assuming fixed incentives
                $eligible_amount += $qty * $total_incentive;
            }
        } elseif ($scheme->enable_slab && $scheme->slabs->count() > 0) {
            // Slab calculation
            if ($scheme->slab_calculation_type == 'flat') {
                // Flat slab: find which slab the total quantity falls into
                $slab = $scheme->slabs->filter(function($s) use ($total_sold_quantity) {
                    return $total_sold_quantity >= $s->from_amount &&
                           ($s->to_amount === null || $total_sold_quantity <= $s->to_amount);
                })->first();

                if ($slab) {
                    if ($slab->commission_type == 'fixed') {
                        $eligible_amount = $slab->value;
                    } else {
                        // Eligible amount = (Sold Qty * Purchase Price Inclusive Tax) * (Slab Value / 100)
                        $eligible_amount = ($total_purchase_value * $slab->value) / 100;
                    }
                }
            } else {
                // Incremental slab: calculate across ranges
                foreach ($scheme->slabs->sortBy('from_amount') as $slab) {
                    if ($total_purchase_amount >= $slab->from_amount) {
                        $upper_limit = $slab->to_amount ?? $total_purchase_amount;
                        $range_amount = min($total_purchase_amount, $upper_limit) - $slab->from_amount;

                        if ($range_amount > 0) {
                            if ($slab->commission_type == 'fixed') {
                                $eligible_amount += $slab->value;
                            } else {
                                $eligible_amount += ($range_amount * $slab->value) / 100;
                            }
                        }
                    }
                }
            }
        } else {
            // Normal calculation (non-slab schemes)
            if ($scheme->scheme_type == 'fixed') {
                // For fixed schemes: Eligible Amount = Scheme Amount × Sold Quantity
                $eligible_amount = $scheme->scheme_amount * $total_sold_quantity;
            } else {
                // For percentage schemes: Eligible Amount = (Sold Qty × Purchase Price Inclusive) × Scheme Percentage
                $eligible_amount = ($total_purchase_value * $scheme->scheme_amount) / 100;
            }
        }

        return $eligible_amount;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('scheme.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $schemes = Scheme::leftJoin('contacts as s', 'schemes.supplier_id', '=', 's.id')
                ->leftJoin('products as p', 'schemes.product_id', '=', 'p.id')
                ->leftJoin('users as u', 'schemes.created_by', '=', 'u.id')
                ->where('schemes.business_id', $business_id)
                ->with(['variations', 'variations.product', 'variations.product_variation', 'slabs'])
                ->select(
                    'schemes.id',
                    'schemes.scheme_name',
                    'schemes.scheme_type',
                    'schemes.multi_level',
                    'schemes.enable_slab',
                    'schemes.slab_calculation_type',
                    'schemes.scheme_amount',
                    'schemes.total_eligible_amount',
                    'schemes.starts_at',
                    'schemes.ends_at',
                    DB::raw("COALESCE(s.supplier_business_name, s.name) as supplier_name"),
                    'p.name as product_name',
                    DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as created_by_name")
                );

            return Datatables::of($schemes)
                ->addColumn('scheme_type_display', function ($row) {
                    if ($row->enable_slab) {
                        return '-';
                    }
                    return __('scheme::lang.' . $row->scheme_type);
                })
                ->addColumn('multi_level', function ($row) {
                    return $row->multi_level ? 'Yes' : 'No';
                })
                ->addColumn('slab_type_display', function ($row) {
                    if ($row->enable_slab) {
                        return __('scheme::lang.' . $row->slab_calculation_type . '_slab');
                    } else {
                        return __('No Slab');
                    }
                })
                ->addColumn('slab_details', function ($row) {
                    if (!$row->enable_slab || $row->slabs->isEmpty()) {
                        return '-';
                    }

                    $details = [];
                    foreach ($row->slabs->sortBy('from_amount') as $slab) {
                        $range = intval($slab->from_amount) . '-' . (isset($slab->to_amount) ? intval($slab->to_amount) : '');
                        $value = number_format($slab->value, 2) . ($slab->commission_type == 'percentage' ? '%' : '');
                        $details[] = $range . ' | ' . $value;
                    }

                    return implode('<br>', $details);
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">' . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can('scheme.view')) {
                        $html .= '<li><a href="' . action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'show'], [$row->id]) . '"><i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '</a></li>';
                    }

                    if (auth()->user()->can('scheme.edit')) {
                        $html .= '<li><a href="' . action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }

                    if (auth()->user()->can('scheme.delete')) {
                        $html .= '<li><a href="#" data-href="' . action([\Modules\Scheme\Http\Controllers\SchemeController::class, 'destroy'], [$row->id]) . '" class="delete-scheme"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->addColumn('products', function ($row) {
                    return count($row->variations);
                })
                ->editColumn('scheme_amount', function ($row) {
                    $util = new \App\Utils\Util();
                    return '<span class="display_currency" data-currency_symbol="true">' . $util->num_f($row->scheme_amount, true) . '</span>';
                })
                ->editColumn('starts_at', function ($row) {
                    $util = new \App\Utils\Util();
                    return $util->format_date($row->starts_at);
                })
                ->editColumn('ends_at', function ($row) {
                    $util = new \App\Utils\Util();
                    return $util->format_date($row->ends_at);
                })
                ->addColumn('status', function ($row) {
                    $now = now();
                    if ($row->starts_at <= $now && $row->ends_at->endOfDay() >= $now) {
                        return '<span class="label bg-green">Active</span>';
                    } else {
                        return '<span class="label bg-red">Closed</span>';
                    }
                })
                ->addColumn('sold_qty', function ($row) {
                    $business_id = request()->session()->get('user.business_id');
                    $variation_ids = $row->variations->pluck('id')->toArray();

                    if (empty($variation_ids) && $row->product_id) {
                        $variation_ids = DB::table('variations')->where('product_id', $row->product_id)->pluck('id')->toArray();
                    }

                    if ($row->multi_level) {
                        // For multi-level, sum quantities from level-selected products (variations in slabs)
                        $selected_variations = collect();
                        foreach ($row->slabs as $slab) {
                            if (!empty($slab->variation_ids)) {
                                $selected_variations = $selected_variations->merge($slab->variation_ids);
                            }
                        }
                        $selected_variations = $selected_variations->unique()->values()->all();

                        $total_sold_quantity = 0;
                        if (!empty($selected_variations)) {
                            $total_sold_quantity = DB::table('transaction_sell_lines as tsl')
                                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                                ->where('t.business_id', $business_id)
                                ->where('t.type', 'sell')
                                ->where('t.status', 'final')
                                ->whereIn('tsl.variation_id', $selected_variations)
                                ->whereBetween('t.transaction_date', [$row->starts_at, $row->ends_at])
                                ->sum('tsl.quantity');
                        }
                    } else {
                        // Get total sold quantity
                        $total_sold_quantity = DB::table('transaction_sell_lines as tsl')
                            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                            ->where('t.business_id', $business_id)
                            ->where('t.type', 'sell')
                            ->where('t.status', 'final')
                            ->whereIn('tsl.variation_id', $variation_ids)
                            ->whereBetween('t.transaction_date', [$row->starts_at, $row->ends_at])
                            ->sum('tsl.quantity');
                    }

                    return number_format($total_sold_quantity, 0);
                })
                ->addColumn('eligible_amount', function ($row) {
                    $util = new \App\Utils\Util();
                    return '<span class="display_currency" data-currency_symbol="true">' . $util->num_f($row->total_eligible_amount, true) . '</span>';
                })
                ->rawColumns(['action', 'scheme_amount', 'status', 'eligible_amount', 'scheme_type_display', 'slab_type_display', 'slab_details'])
                ->make(true);
        }

        return view('scheme::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if (!auth()->user()->can('scheme.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $suppliers = Contact::suppliersDropdown($business_id, false, true);
        $products = Product::forDropdown($business_id, false);

        return view('scheme::create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('scheme.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->all();
            if (!empty($input['starts_at'])) {
                $input['starts_at'] = \Carbon\Carbon::createFromFormat('d-m-Y', $input['starts_at'])->format('Y-m-d');
            }
            if (!empty($input['ends_at'])) {
                $input['ends_at'] = \Carbon\Carbon::createFromFormat('d-m-Y', $input['ends_at'])->format('Y-m-d');
            }
            $request->merge($input);

            $request->validate([
                'scheme_name' => 'required|string|max:255',
                'scheme_amount' => 'nullable|required_if:enable_slab,false|numeric|min:0',
                'scheme_type' => 'nullable|required_if:enable_slab,false|in:fixed,percentage',
                'enable_slab' => 'boolean',
                'multi_level' => 'boolean',
                'slab_calculation_type' => 'nullable|required_if:enable_slab,true|in:flat,incremental',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            $data = $request->only([
                'scheme_name',
                'scheme_amount',
                'scheme_type',
                'enable_slab',
                'multi_level',
                'slab_calculation_type',
                'supplier_id',
                'product_id',
                'starts_at',
                'ends_at',
                'scheme_note'
            ]);

            $variation_ids = $request->input('variation_ids');

            if (! empty($variation_ids)) {
                unset($data['product_id']);
            }

            // If slab is enabled, remove scheme_amount and scheme_type as they are not needed
            if ($request->input('enable_slab')) {
                unset($data['scheme_amount']);
                unset($data['scheme_type']);
            }

            $data['business_id'] = $business_id;
            $data['created_by'] = $request->session()->get('user.id');

            $scheme = Scheme::create($data);

            if (! empty($variation_ids)) {
                $scheme->variations()->sync($variation_ids);
            }

            // Handle slabs if enabled
            if ($request->input('enable_slab') && $request->has('slabs')) {
                $slabs = $request->input('slabs', []);
                $is_multi_level = $request->input('multi_level', false);
                foreach ($slabs as $slab) {
                    if (!empty($slab['from_amount']) && !empty($slab['commission_type']) && isset($slab['value'])) {
                        $variation_ids = $is_multi_level ? ($slab['variation_ids'] ?? []) : [];
                        $scheme->slabs()->create([
                            'from_amount' => $slab['from_amount'],
                            'to_amount' => $slab['to_amount'] ?? null,
                            'commission_type' => $slab['commission_type'],
                            'value' => $slab['value'],
                            'variation_ids' => $variation_ids
                        ]);
                    }
                }
            }

            // Calculate and store total eligible amount
            $scheme->load(['variations', 'slabs']);
            $total_eligible_amount = $this->calculateTotalEligibleAmount($scheme);
            $scheme->update(['total_eligible_amount' => $total_eligible_amount]);

            $output = [
                'success' => 1,
                'msg' => __('scheme::lang.scheme_add_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect('schemes')->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if (!auth()->user()->can('scheme.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $scheme = Scheme::where('business_id', $business_id)
            ->with(['variations', 'variations.product', 'variations.product_variation', 'supplier', 'product', 'creator', 'slabs'])
            ->findOrFail($id);

        // Get sales data for products in this scheme
        if ($scheme->multi_level && $scheme->enable_slab) {
            // For multi-level slab schemes, collect variation_ids from all slab levels
            $variation_ids = collect();
            foreach ($scheme->slabs as $slab) {
                if (!empty($slab->variation_ids)) {
                    $variation_ids = $variation_ids->merge($slab->variation_ids);
                }
            }
            $variation_ids = $variation_ids->unique()->values()->all();
        } else {
            // For non-multi-level schemes, use scheme variations or product variations
            $variation_ids = $scheme->variations->pluck('id')->toArray();

            if (empty($variation_ids) && $scheme->product_id) {
                $variation_ids = DB::table('variations')->where('product_id', $scheme->product_id)->pluck('id')->toArray();
            }
        }

        $sales_data = DB::table('transaction_sell_lines as tsl')
            ->leftJoin('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->leftJoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('purchase_lines as pl', 'tsl.lot_no_line_id', '=', 'pl.id')
            ->leftJoin('tax_rates as tr', 'p.tax', '=', 'tr.id')
            ->leftJoin('variation_location_details as vld', function($join) {
                $join->on('vld.variation_id', '=', 'v.id')
                     ->on('vld.location_id', '=', 't.location_id');
            })
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereIn('tsl.variation_id', $variation_ids)
            ->whereBetween('t.transaction_date', [$scheme->starts_at, $scheme->ends_at])
            ->select(
                'bl.name as business_location',
                'p.name as product',
                'v.sub_sku as sku',
                'pl.lot_number',
                'c.name as customer_name',
                't.invoice_no',
                't.transaction_date',
                'tsl.quantity as qty',
                'tsl.unit_price',
                'tsl.line_discount_amount as discount',
                'tsl.item_tax as tax',
                'tsl.unit_price_inc_tax as price_inc_tax',
                DB::raw('tsl.quantity * tsl.unit_price_inc_tax as total'),
                DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as service_staff"),
                'pl.purchase_price',
                'tr.amount as tax_rate',
                'tsl.variation_id'
            )
            ->get();

        // Calculate total sold quantity for eligible slab determination
        $total_sold_quantity = $sales_data->sum('qty');

        // Determine eligible slab if conditions are met
        $eligible_slab = null;
        if ($scheme->enable_slab && !$scheme->multi_level && $scheme->slab_calculation_type == 'flat') {
            $eligible_slab = $scheme->slabs->filter(function($slab) use ($total_sold_quantity) {
                return $total_sold_quantity >= $slab->from_amount &&
                       ($slab->to_amount === null || $total_sold_quantity <= $slab->to_amount);
            })->first();
        }

        // Calculate variation quantities
        $variation_quantities = $sales_data->groupBy('variation_id')->map(function($group) {
            return $group->sum('qty');
        });


        // Calculate eligible amount for each sale
        $sales_data = $sales_data->map(function($sale) use ($scheme, $eligible_slab, $variation_quantities) {
            $eligible_amount = 0;
            $purchase_price = $sale->purchase_price ?? 0;
            $tax_rate = $sale->tax_rate ?? 0;
            if ($scheme->enable_slab) {
                if ($scheme->multi_level) {
                    $qty = $variation_quantities[$sale->variation_id] ?? 0;
                    $applicable_slabs = $scheme->slabs->filter(function($slab) use ($sale, $qty) {
                        return in_array($sale->variation_id, $slab->variation_ids ?? []) &&
                               $qty >= $slab->from_amount &&
                               ($slab->to_amount === null || $qty <= $slab->to_amount);
                    });
                    $eligible_amount = 0;
                    foreach ($applicable_slabs as $slab) {
                        if ($slab->commission_type == 'fixed') {
                            $eligible_amount += $sale->qty * $slab->value;
                        } elseif ($slab->commission_type == 'percentage') {
                            $eligible_amount += ($sale->qty * $purchase_price * $slab->value / 100) / (1 + ($tax_rate / 100));
                        }
                    }
                } elseif ($scheme->slab_calculation_type == 'flat') {
                    if ($eligible_slab) {
                        if ($eligible_slab->commission_type == 'fixed') {
                            $eligible_amount = $sale->qty * $eligible_slab->value;
                        } elseif ($eligible_slab->commission_type == 'percentage' && $purchase_price > 0) {
                            $eligible_amount = ($sale->qty * $purchase_price * $eligible_slab->value / 100) / (1 + ($tax_rate / 100));
                        }
                    }
                } else {
                    // Incremental
                    $eligible_amount = 0;
                }
            } else {
                if ($scheme->scheme_type == 'fixed') {
                    $eligible_amount = $scheme->scheme_amount * $sale->qty;
                } elseif ($scheme->scheme_type == 'percentage') {
                    $eligible_amount = (($purchase_price * $sale->qty * $scheme->scheme_amount) / 100) / (1 + ($tax_rate / 100));
                }
            }
            $sale->eligible_amount = $eligible_amount;
            return $sale;
        });

        // Calculate total eligible amount
        if ($scheme->enable_slab && $scheme->multi_level) {
            $total_eligible_amount = $sales_data->sum('eligible_amount');
        } else {
            $total_eligible_amount = $scheme->total_eligible_amount;
        }

        return view('scheme::show', compact('scheme', 'sales_data', 'eligible_slab', 'total_eligible_amount'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if (!auth()->user()->can('scheme.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $scheme = Scheme::where('business_id', $business_id)->with(['variations', 'variations.product', 'variations.product_variation', 'slabs'])->findOrFail($id);

        $suppliers = Contact::suppliersDropdown($business_id, false, true);
        $products = Product::forDropdown($business_id, false);

        $variations = [];

        foreach ($scheme->variations as $variation) {
            $variations[$variation->id] = $variation->full_name;
        }

        // Get slab variations for edit form
        $slabVariations = [];
        foreach ($scheme->slabs as $slab) {
            $slabVariations[$slab->id] = $slab->variations->pluck('full_name', 'id')->toArray();
        }

        return view('scheme::edit', compact('scheme', 'suppliers', 'products', 'variations', 'slabVariations'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('scheme.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->all();
            if (!empty($input['starts_at'])) {
                $input['starts_at'] = \Carbon\Carbon::createFromFormat('d-m-Y', $input['starts_at'])->format('Y-m-d');
            }
            if (!empty($input['ends_at'])) {
                $input['ends_at'] = \Carbon\Carbon::createFromFormat('d-m-Y', $input['ends_at'])->format('Y-m-d');
            }
            $request->merge($input);

            $request->validate([
                'scheme_name' => 'required|string|max:255',
                'scheme_amount' => 'nullable|required_if:enable_slab,false|numeric|min:0',
                'scheme_type' => 'nullable|required_if:enable_slab,false|in:fixed,percentage',
                'enable_slab' => 'boolean',
                'multi_level' => 'boolean',
                'slab_calculation_type' => 'nullable|required_if:enable_slab,true|in:flat,incremental',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
            ]);

            $scheme = Scheme::where('business_id', $business_id)->findOrFail($id);

            $data = $request->only([
                'scheme_name',
                'scheme_amount',
                'scheme_type',
                'enable_slab',
                'multi_level',
                'slab_calculation_type',
                'supplier_id',
                'product_id',
                'starts_at',
                'ends_at',
                'scheme_note'
            ]);

            $variation_ids = $request->input('variation_ids');

            if (! empty($variation_ids)) {
                unset($data['product_id']);
            }

            // If slab is enabled, remove scheme_amount and scheme_type as they are not needed
            if ($request->input('enable_slab')) {
                unset($data['scheme_amount']);
                unset($data['scheme_type']);
            }

            $scheme->update($data);

            $scheme->variations()->sync($variation_ids);

            // Handle slabs - delete existing and create new ones
            $scheme->slabs()->delete();
            if ($request->input('enable_slab') && $request->has('slabs')) {
                $slabs = $request->input('slabs', []);
                $is_multi_level = $request->input('multi_level', false);
                foreach ($slabs as $slab) {
                    if (!empty($slab['from_amount']) && !empty($slab['commission_type']) && isset($slab['value'])) {
                        $variation_ids = $is_multi_level ? ($slab['variation_ids'] ?? []) : [];
                        $scheme->slabs()->create([
                            'from_amount' => $slab['from_amount'],
                            'to_amount' => $slab['to_amount'] ?? null,
                            'commission_type' => $slab['commission_type'],
                            'value' => $slab['value'],
                            'variation_ids' => $variation_ids
                        ]);
                    }
                }
            }

            // Calculate and store total eligible amount
            $scheme->load(['variations', 'slabs']);
            $total_eligible_amount = $this->calculateTotalEligibleAmount($scheme);
            $scheme->update(['total_eligible_amount' => $total_eligible_amount]);

            $output = [
                'success' => 1,
                'msg' => __('scheme::lang.scheme_update_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect('schemes')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('scheme.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $scheme = Scheme::where('business_id', $business_id)->findOrFail($id);
                $scheme->delete();

                $output = [
                    'success' => true,
                    'msg' => __('scheme::lang.scheme_delete_success')
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }

            return $output;
        }
    }
}
