<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Packaging AS PCK;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Laratrust;
use File;
use PDF;

class PackageEntryController extends Controller
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

        \App\Models\BaseModel::setPlantContext();

        $data = [
            'user' => $user,
            'role' => implode(', ', array_map('ucfirst', $user->roles->pluck('name')->toArray())),
            'n_users' => $n_users,
            'n_roles' => $n_roles,
            'n_perms' => $n_perms
        ];

        if (!Laratrust::hasRole([
            'admin', 'super-admin', 'manager', 'superintendent', 
            'senior-supervisor', 'supervisor', 'senior-staff', 'staff'
        ])) {
            return view('error.403');
        }

        if ($user->hasRole(['admin', 'super-admin'])) {
            $selectedPlant = $request->query('plant') ?? session('selected_plant');

            if ($selectedPlant) {
                session(['selected_plant' => $selectedPlant]);
            }

            $data['plants'] = DB::table('m_plant')->select('code_3', 'code_2')->get();
            $data['selectedPlant'] = $selectedPlant;
    
            if (empty($selectedPlant)) {
                return view('user.trans_package.index', $data);
            }
    
            if ((string)$selectedPlant !== '1002') {
                return response()->view('error.403', [], 403);
            }
    
            return view('user.trans_package.index', $data);
        }

        $plantData = DB::table('m_plant_user')
            ->join('m_plant', 'm_plant_user.id_plant', '=', 'm_plant.code_3')
            ->where('m_plant_user.user_id', $user->id)
            ->select('m_plant.code_3', 'm_plant.code_2')
            ->first();

        if (!$plantData) {
            return response()->view('error.403', [], 403);
        }

        $userPlant = (string)$plantData->code_3;
        $data['plants'] = [$plantData];
        $data['selectedPlant'] = $userPlant;

        if ($userPlant !== '1002') {
            return response()->view('error.403', [], 403);
        }

        return view('user.trans_package.index', $data);
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

            if ($flag == 'post_cancelPck'){
                $traceNo = $request->input('traceNo');
                DB::beginTransaction();
                try {

                    $return = PCK::post_cancelPck($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, 'PACKAGING ' . $traceNo, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {

                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);

                }

            } elseif ($flag == 'post_entryPck'){

                DB::beginTransaction();
                try {

                    $return = PCK::post_entryPck($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, 'PACKAGING ENTRY', $mode);
                    return response()->json($data);

                } catch (\Exception $e) {

                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);

                }

            } elseif ($flag == 'post_pckEntry_poNo'){
                $return = PCK::post_pckEntry_poNo($user, $request);
                $data = $this->returnResponse($return, 'PO ENTRY', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_pckEntry_batchNo'){
                $return = PCK::post_pckEntry_batchNo($user, $request);
                $data = $this->returnResponse($return, 'BATCH ENTRY', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_updateEntrySubTank'){
                $return = PCK::post_updateEntrySubTank($user, $request);
                $data = $this->returnResponse($return, 'SUBTANK', $mode);
                return response()->json($data);
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

        if ($flag == 'get_dtPckEntry'){
            $db = PCK::get_dtPckEntry();

            return DataTables::of($db)
                    ->addColumn('po_no', function($data) {
                            return view('user.trans_package.datatables.__actionPoEntry', [
                                'model'=> $data
                            ]);
                        })
                    ->addColumn('batch_no', function($data) {
                            return view('user.trans_package.datatables.__actionBatchEntry', [
                                'model'=> $data
                            ]);
                        })
                    ->addColumn('action', function($data){
                        return view('user.trans_package.datatables.__actionPckEntry', [
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
                    ->rawColumns(['supplier', 'action', 'po_no', 'batch_no'])
                    ->make(true);
        } elseif ($flag == 'get_activeFgProduct'){
            $txtData['data'] = PCK::get_activeFgProduct();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_wipMaterialByFgProduct'){
            $txtData['data'] = PCK::get_wipMaterialByFgProduct($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank_pck'){
            $txtData['data'] = PCK::get_cmbActiveTank_pck($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveWarehouse_pck'){
            $txtData['data'] = PCK::get_cmbActiveWarehouse_pck($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveSpecificTank'){
            $txtData['data'] = PCK::get_cmbActiveSpecificTank($request);
            echo json_encode($txtData);
            exit;
        };

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

        if ($id == 'pck_entry'){
            return view('user.trans_package.index', $data);
        } elseif ($id == 'pck_warehouse'){
            return view('user.trans_package.pages.__warehouse', $data);
        }
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
                    'message' => $feature . ' Entry Error' ];
            } elseif ($return[0]->response == '4'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Stock is not Enough!' ];
            } elseif ($return[0]->response == '99'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Period Locked!' ];
            };
        }
        return $data;
    }


}
