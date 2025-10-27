<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RawMaterial AS RM;
use App\Models\Wip AS WP;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Laratrust;
use File;
use PDF;

class WipentryController extends Controller
{
    // Only Authenticated User have access to Dashboard
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            \App\Models\BaseModel::setPlantContext();
            return $next($request);
        });
    }

    // Show Dashboard Page
    public function index(Request $request)
    {
        $n_users = User::all()->count();
        $n_roles = Role::all()->count();
        $n_perms = Permission::all()->count();

        $user = Auth::user();

        $data = [
            'user' => $user,
            'role' => implode(', ', array_map('ucfirst', $user->roles->pluck('name')->toArray())),
            'n_users' => $n_users,
            'n_roles' => $n_roles,
            'n_perms' => $n_perms,
        ];

        if (!Laratrust::hasRole([
            'admin', 'super-admin', 'manager', 'superintendent', 
            'senior-supervisor', 'supervisor', 'senior-staff', 'staff'
        ])) {
            return view('error.403');
        }

        if ($user->hasRole(['admin', 'super-admin'])) {
            $plants = DB::table('m_plant')->select('code_3', 'code_2')->get();

            $selectedPlant = $request->query('plant');

            if ($selectedPlant) {
                session(['selected_plant' => $selectedPlant]);
            } else {
                $selectedPlant = session('selected_plant');
            }

            $data['plants'] = $plants;
            $data['selectedPlant'] = $selectedPlant;

            return view('user.trans_wip.index', $data);
        }

        $userPlant = DB::table('m_plant_user')
            ->join('m_plant', 'm_plant_user.id_plant', '=', 'm_plant.code_3')
            ->where('m_plant_user.user_id', $user->id)
            ->select('m_plant.code_3', 'm_plant.code_2')
            ->first();

        $data['plants'] = $userPlant ? [$userPlant] : [];
        $data['selectedPlant'] = $userPlant ? $userPlant->code_3 : null;
        
        return view('user.trans_wip.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->hasPermission('task-update')) {
            $flag       = $request->input('flag');
            $mode       = $request->input('mode');
            $user       = Auth::user()->name;

            if ($flag == 'post_matlDocNumber'){
                $return = WP::post_matlDocNumber($user, $request);
                $data = $this->returnResponse($return, 'MATL DOC NO', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_materialFeed'){
                $feature = $request->input('feature');

                DB::beginTransaction();
                try {

                    $return = WP::post_materialFeed($user, $request);
                    DB::commit();

                    $data = $this->returnResponse($return, $feature, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_cancelFeed'){
                $traceNo = $request->input('traceNo');

                DB::beginTransaction();
                try {

                    $return = WP::post_cancelFeed($user, $request);
                    DB::commit();

                    $data = $this->returnResponse($return, 'FEED ' . $traceNo, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {

                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);

                }

            } elseif ($flag == 'post_cancelRundown'){
                $traceNo = $request->input('traceNo');

                DB::beginTransaction();
                try {

                    $return = WP::post_cancelRundown($user, $request);
                    DB::commit();

                    $data = $this->returnResponse($return, 'RUNDOWN ' . $traceNo, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_materialRundown'){
                $feature = $request->input('feature');

                DB::beginTransaction();
                try {

                    $return = WP::post_materialRundown($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, $feature, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }
            }


        } else {
            return view('error.403');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $flag = $request->input('flag');

        if ($flag == 'get_dtBalance'){
            $rundownId = $request->input('rundownId');
            $db = WP::get_dtBalance($request, $rundownId);

            return DataTables::of($db)
                    ->addColumn('material', function($row){
                        $batches = explode('|', $row->material);
                        $output = '';
                        foreach ($batches as $material) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $material . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('supplier', function($row){
                        $batches = explode('|', $row->supplier);
                        $output = '';
                        foreach ($batches as $supplier) {
                            $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $supplier . '</span> ';
                        }
                        return $output;
                    })
                    ->rawColumns(['supplier', 'material'])
                    ->make(true);
        } elseif ($flag == 'get_dtFeed'){
            $mode = $request->input('mode');
            $feedId = $request->input('feedId');

            $db = WP::get_dtFeed($request, $feedId);

            if ($mode == 'LATEST'){
                return DataTables::of($db)
                        ->addColumn('material_document', function($data) {
                            return view('user.trans_wip.datatables.__actionMatlDocFeed', [
                                'model'=> $data
                            ]);
                        })
                        ->addColumn('supplier', function($row){
                            $batches = explode('|', $row->supplier);
                            $output = '';
                            foreach ($batches as $supplier) {
                                $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $supplier . '</span> ';
                            }
                            return $output;
                        })
                        ->rawColumns(['supplier', 'material_document'])
                        ->make(true);

            } elseif ($mode == 'LOG'){
                return DataTables::of($db)
                        ->addColumn('material', function($row){
                            $batches = explode('|', $row->material);
                            $output = '';
                            foreach ($batches as $material) {
                                $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $material . '</span> ';
                            }
                            return $output;
                        })
                        ->addColumn('action', function($data){
                            return view('user.trans_wip.datatables.__actionCancelFeed', [
                                'model'=> $data,
                                'update_url'=>route('wipentry.store')
                            ]);
                        })
                        ->addColumn('material_document', function($data) {
                            return view('user.trans_wip.datatables.__actionMatlDocFeed', [
                                'model'=> $data
                            ]);
                        })
                        ->addColumn('supplier', function($row){
                            $batches = explode('|', $row->supplier);
                            $output = '';
                            foreach ($batches as $supplier) {
                                $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $supplier . '</span> ';
                            }
                            return $output;
                        })
                        ->rawColumns(['supplier', 'material_document', 'action', 'material'])
                        ->make(true);
            }

        } elseif ($flag == 'get_dtRundown'){
            $mode = $request->input('mode');
            $rundownId = $request->input('rundownId');
            $db = WP::get_dtRundown($request, $rundownId);

            if ($mode == 'LATEST'){
                return DataTables::of($db)
                        ->addColumn('material_document', function($data) {
                            return view('user.trans_wip.datatables.__actionMatlDocFeed', [
                                'model'=> $data
                            ]);
                        })
                        ->addColumn('supplier', function($row){
                            $batches = explode('|', $row->supplier);
                            $output = '';
                            foreach ($batches as $supplier) {
                                $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $supplier . '</span> ';
                            }
                            return $output;
                        })
                        ->rawColumns(['supplier', 'material_document'])
                        ->make(true);

            } elseif ($mode == 'LOG'){
                return DataTables::of($db)
                        ->addColumn('material', function($row){
                            $batches = explode('|', $row->material);
                            $output = '';
                            foreach ($batches as $material) {
                                $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $material . '</span> ';
                            }
                            return $output;
                        })
                        ->addColumn('action', function($data){
                            return view('user.trans_wip.datatables.__actionCancelRundown', [
                                'model'=> $data,
                                'update_url'=>route('wipentry.store')
                            ]);
                        })
                        ->addColumn('material_document', function($data) {
                            return view('user.trans_wip.datatables.__actionMatlDocFeed', [
                                'model'=> $data
                            ]);
                        })
                        ->addColumn('supplier', function($row){
                            $batches = explode('|', $row->supplier);
                            $output = '';
                            foreach ($batches as $supplier) {
                                $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $supplier . '</span> ';
                            }
                            return $output;
                        })
                        ->rawColumns(['supplier', 'material_document', 'action', 'material'])
                        ->make(true);
            }

        } elseif ($flag == 'get_cmbActiveTank_trf'){
            $txtData['data'] = WP::get_cmbActiveTank_trf($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank_rundown'){
            $txtData['data'] = WP::get_cmbActiveTank_rundown($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_feedNewBatchNumber'){
            $txtData['data'] = WP::get_feedNewBatchNumber($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_rundownNewBatchNumber'){
            $txtData['data'] = WP::get_rundownNewBatchNumber($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_feedLastBatch'){
            $txtData['data'] = WP::get_feedLastBatch($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_rundownLastBatch'){
            $txtData['data'] = WP::get_rundownLastBatch($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_quantifierData'){
            $txtData['data'] = WP::get_quantifierData($request);
            echo json_encode($txtData);
            exit;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = [];
        $n_users = User::all()->count();
        $n_roles = Role::all()->count();
        $n_perms = Permission::all()->count();
        $data = [
            'user' => $n_users,
            'role' => $n_roles,
            'permission' => $n_perms,
        ];


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user()->name;

        if (Auth::user()->hasPermission('task-update')) {
            $parts  = explode(",", $id);
            $id     = trim($parts[0]);
            $flag   = trim($parts[1]);


        } else {
            return view('error.403');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user()->name;

        if (Laratrust::hasRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor'])) {
            $parts  = explode(",", $id);
            $id     = trim($parts[0]);
            $flag   = trim($parts[1]);

        } else {
            return view('error.403');
        }
    }

    /**
     * Return response as JSON to AJAX call.
     *
     * @param  $return $feature $mode
     * @return \Illuminate\Http\Response
     */
    protected function returnResponse($return, $feature, $mode){

        if ($return == null){
            $data = [
                'status'  => 0,
                'message' => $feature ];
        } else {
            if ($return[0]->response == '1'){
                $data = [
                    'status'  => 1,
                    'message' => 'Success ' . $mode . ' ' . $feature ];
            } elseif ($return[0]->response == '0'){
                $data = [
                    'status'  => 0,
                    'message' => 'Failed ' . $mode . ' ' . $feature ];
            } elseif ($return[0]->response == '2'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' already exists' ];
            } elseif ($return[0]->response == '3'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Not Enough Reserve!' ];
            } elseif ($return[0]->response == '4'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Feed N/A!' ];
            } elseif ($return[0]->response == '5'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Feed Qty undefined!' ];
            } elseif ($return[0]->response == '6'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' No Supplier Traced!' ];
            } elseif ($return[0]->response == '7'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Double Trace no!' ];
            } elseif ($return[0]->response == '99'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Period Locked!' ];
            };
        }
        return $data;
    }


}
