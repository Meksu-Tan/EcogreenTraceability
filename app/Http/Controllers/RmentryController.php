<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RawMaterial AS RM;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;
use Illuminate\Support\Facades\DB;

class RmentryController extends Controller
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

            return view('user.trans_rm.index', $data);
        }

        $userPlant = DB::table('m_plant_user')
            ->join('m_plant', 'm_plant_user.id_plant', '=', 'm_plant.code_3')
            ->where('m_plant_user.user_id', $user->id)
            ->select('m_plant.code_3', 'm_plant.code_2')
            ->first();

        $data['plants'] = $userPlant ? [$userPlant] : [];
        $data['selectedPlant'] = $userPlant ? $userPlant->code_3 : null;
        
        return view('user.trans_rm.index', $data);
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

            if ($flag == 'post_rmEntrySupplier'){
                $idTank = $request->input('idTank');
                $entryDate = $request->input('entryDate');
                $rmNumber = $request->input('rmNumber');
                $idHead = $request->input('idHead');
                $materialDoc = $request->input('materialDoc');
                $idMaterial = $request->input('idMaterial');
                $po = $request->input('po');

                $return = RM::post_rmEntrySupplier($user, $request);
                $data = $this->returnResponse($return, 'SUPPLIER', $mode, $idTank, $entryDate, $rmNumber, $idHead, $materialDoc, $idMaterial, $po);
                return response()->json($data);

            } elseif ($flag == 'post_rmEntryMaterial'){
                $idTankStorage = $request->input('idTankStorage');
                $idTankFeed = $request->input('idTankFeed');
                $entryDate = $request->input('entryDate');
                $entryNo = $request->input('entryNo');
                $idHead = $request->input('idHead');
                $materialDoc = $request->input('materialDoc');

                $return = RM::post_rmEntryMaterial($user, $request);
                $data = $this->returnResponse($return, 'MATERIAL', $mode, $idTankStorage, $idTankFeed, $entryDate, $entryNo, $idHead, $materialDoc);
                return response()->json($data);

            } elseif ($flag == 'post_rmEntry'){
                $return = RM::post_rmEntry($user, $request);
                $data = $this->returnResponse($return, 'RM', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_rmTrfEntry'){
                $return = RM::post_rmTrfEntry($user, $request);
                $data = $this->returnResponse($return, 'RM TRF', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_matlDocNumber'){
                $return = RM::post_matlDocNumber($user, $request);
                $data = $this->returnResponse($return, 'MATL DOC NO', $mode);
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

        if ($flag == 'get_dtRmList'){
            $db = RM::get_dtRmList($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.trans_rm.datatables.__actionRmList', [
                            'model'=> $data,
                            'destroy_url'=> route('rmentry.destroy', $data->id_balance_head . ',rm_deactivate'),
                            'activate_url'=> route('rmentry.destroy', $data->id_balance_head . ',rm_activate'),
                            'update_url'=>route('rmentry.store')
                        ]);
                    })
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
                    ->addColumn('material_document', function($data) {
                        return view('user.trans_rm.datatables.__actionMatlDocRm', [
                            'model'=> $data
                        ]);
                    })
                    ->addColumn('po_so', function($data) {
                        return view('user.trans_rm.datatables.__actionPoSoRm', [
                            'model'=> $data
                        ]);
                    })
                    ->rawColumns(['supplier','action','material_document','po_so', 'material'])
                    ->make(true);

        } elseif ($flag == 'get_dtRmListTrf'){
            $db = RM::get_dtRmListTrf($request);

            return DataTables::of($db)
                    ->addColumn('material', function($row){
                        $batches = explode('|', $row->material);
                        $output = '';
                        foreach ($batches as $material) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $material . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('trace_no', function($row){
                        $batches = explode('|', $row->trace_no);
                        $output = '';
                        foreach ($batches as $trace_no) {
                            $output .= '<span class="badge badge-primary" style="color:white;margin-top:2px">' . $trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('action', function($data){
                        return view('user.trans_rm.datatables.__actionRmTrfList', [
                            'model'=> $data,
                            'destroy_url'=> route('rmentry.destroy', $data->trace_nos . ',rmtrf_deactivate'),
                            'activate_url'=> route('rmentry.destroy', $data->trace_nos . ',rmtrf_activate'),
                            'update_url'=>route('rmentry.store')
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
                    ->addColumn('material_document', function($data) {
                        return view('user.trans_rm.datatables.__actionMatlDocRm', [
                            'model'=> $data
                        ]);
                    })
                    ->rawColumns(['supplier','action','material_document','trace_no','material'])
                    ->make(true);

        } elseif ($flag == 'get_rmNewEntryNumber'){
            $txtData['data'] = RM::get_rmNewEntryNumber();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank'){
            $txtData['data'] = RM::get_cmbActiveTank();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank_trf'){
            $txtData['data'] = RM::get_cmbActiveTank_trf($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_activeSupplier_bySelect2'){
            $tags = RM::get_activeSupplier_bySelect2($request);
            $formatted_tags = [];
            foreach ($tags as $tag) {
                $formatted_tags[] = ['id' => $tag->id_supplier, 'text' => $tag->supplier];
            };

            return \Response::json($formatted_tags);
        } elseif ($flag == 'get_dtSupplierList'){
            $db = RM::get_dtSupplierList($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.trans_rm.datatables.__actionRmSupplier', [
                            'model'=> $data,
                            'destroy_url'=> route('rmentry.destroy', $data->idTail . ',supplier_destroy'),
                            'update_url'=>route('rmentry.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_dtMaterialList'){
            $db = RM::get_dtMaterialList($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.trans_rm.datatables.__actionRmMaterial', [
                            'model'=> $data,
                            'destroy_url'=> route('rmentry.destroy', $data->idTail . ',material_destroy'),
                            'update_url'=>route('rmentry.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_totalQtySupplier'){
            $txtData['data'] = RM::get_totalQtySupplier($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveMaterial'){
            $txtData['data'] = RM::get_cmbActiveMaterial();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_rmNewEntryNumberTrf'){
            $txtData['data'] = RM::get_rmNewEntryNumberTrf();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_totalQtyMaterial'){
            $txtData['data'] = RM::get_totalQtyMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_totalStockMaterial'){
            $txtData['data'] = RM::get_totalStockMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_batchCode_bySupplier'){
            $txtData['data'] = RM::get_batchCode_bySupplier($request);
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

            if ($flag == 'rm_deactivate'){
                $return = RM::deactivateRmEntry($id, $user);
                $data = $this->returnResponse($return, 'RM ENTRY', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'rm_activate'){
                $return = RM::activateRmEntry($id, $user);
                $data = $this->returnResponse($return, 'RM ENTRY', 'activate');
                return response()->json($data);
            } elseif ($flag == 'supplier_destroy'){
                $return = RM::deleteSupplier($id, $user);
                $data = $this->returnResponse($return, 'SUPPLIER', 'delete');
                return response()->json($data);
            } elseif ($flag == 'rmtrf_deactivate'){
                $return = RM::deactivateRmEntryTrf($id, $user);
                $data = $this->returnResponse($return, 'RM TRF ENTRY', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'material_destroy'){
                $return = RM::deleteMaterial($id, $user);
                $data = $this->returnResponse($return, 'MATERIAL', 'delete');
                return response()->json($data);
            }

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
    protected function returnResponse($return, $feature, $mode, $input_1=null, $input_2=null,
                                      $input_3=null, $input_4=null, $input_5=null,
                                      $input_6=null, $input_7=null){

        if ($return[0]->response == '1'){
            if ($feature == 'SUPPLIER'){
                $data = [
                    'status'  => 1,
                    'mode' => $mode,
                    'idTank' => $input_1,
                    'entryDate' => $input_2,
                    'rmNumber' => $input_3,
                    'idHead' => $input_4,
                    'materialDoc' => $input_5,
                    'idMaterial' => $input_6,
                    'po' => $input_7,
                    'message' => 'Success ' . $mode . ' ' . $feature ];

            } elseif ($feature == 'MATERIAL'){
                $data = [
                    'status'  => 1,
                    'mode' => $mode,
                    'idTankStorage' => $input_1,
                    'idTankFeed' => $input_2,
                    'entryDate' => $input_3,
                    'entryNo' => $input_4,
                    'idHead' => $input_5,
                    'materialDoc' => $input_6,
                    'message' => 'Success ' . $mode . ' ' . $feature ];
            } else {
                $data = [
                    'status'  => 1,
                    'mode' => $mode,
                    'message' => 'Success ' . $mode . ' ' . $feature ];
            }
        } elseif ($return[0]->response == '0'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => 'Failed ' . $mode . ' ' . $feature ];
        } elseif ($return[0]->response == '2'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => $feature . ' already exists' ];
        } elseif ($return[0]->response == '3'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => $feature . ' has been used' ];
        } elseif ($return[0]->response == '4'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => $feature . ' cannot be activated.' ];
        } elseif ($return[0]->response == '5'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => $feature . ' Stock Not Enough.' ];
        } elseif ($return[0]->response == '6'){
            $data = [
                'status'  => 0,
                'mode' => $mode,
                'message' => $feature . ' No RM Data.' ];
        } elseif ($return[0]->response == '99'){
            $data = [
                'status'  => 0,
                'message' => $feature . ' Period Locked!' ];
        };

        return $data;
    }


}
