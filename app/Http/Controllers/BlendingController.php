<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Blending AS BL;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Laratrust;
use File;
use PDF;

class BlendingController extends Controller
{
    // Only Authenticated User have access to Dashboard
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Show Dashboard Page
    public function index()
    {
        $data = [];
        $n_users = User::all()->count();
        $n_roles = Role::all()->count();
        $n_perms = Permission::all()->count();

        if (Laratrust::hasRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff'])) {
            $data = [
                'user' => $n_users,
                'role' => $n_roles,
                'permission' => $n_perms,
                'user' => Auth::user(),
                'role' => implode(array_map('ucfirst', Auth::user()->roles->pluck('name')->toArray())),
            ];

            return view('user.trans_blending.index',$data);
        } else {
            return view('error.403');
        }
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

            if ($flag == 'post_blendingEntryMaterial'){
                $idMaterial = $request->input('idMaterial');
                $entryDate = $request->input('entryDate');
                $entryNo = $request->input('entryNo');
                $idHead = $request->input('idHead');
                $materialDoc = $request->input('materialDoc');

                DB::beginTransaction();
                try {
                    $return = BL::post_blendingEntryMaterial($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, 'BLEND MATERIAL', $mode, $idMaterial, $entryDate, $entryNo, $idHead, $materialDoc);
                    return response()->json($data);
                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_blendingEntry'){
                DB::beginTransaction();
                try {
                    $return = BL::post_blendingEntry($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, 'BLENDING', $mode);
                    return response()->json($data);
                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }
            } elseif ($flag == 'post_matlDocNumber'){
                $return = BL::post_matlDocNumber($user, $request);
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

        if ($flag == 'get_cmbActiveMaterial'){
            $txtData['data'] = BL::get_cmbActiveMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_newBlendingEntryNo'){
            $txtData['data'] = BL::get_newBlendingEntryNo($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_totalStockMaterial'){
            $txtData['data'] = BL::get_totalStockMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_totalQtyMaterial'){
            $txtData['data'] = BL::get_totalQtyMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtMaterialList'){
            $db = BL::get_dtMaterialList($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.trans_blending.datatables.__actionBlendMaterial', [
                            'model'=> $data,
                            'destroy_url'=> route('blending.destroy', $data->idTail . ',blendingMaterial_destroy'),
                            'update_url'=>route('blending.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_dtBlendingList'){
            $db = BL::get_dtBlendingList($request);
            return DataTables::of($db)
                        ->addColumn('from_trace_no', function($row){
                            $batches = explode('|', $row->from_trace_no);
                            $output = '';
                            foreach ($batches as $from_trace_no) {
                                $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                            }
                            return $output;
                        })
                        ->addColumn('action', function($data){
                            return view('user.trans_blending.datatables.__actionBlendList', [
                                'model'=> $data,
                                'destroy_url'=> route('blending.destroy', $data->idHead . '|' . $data->idTraceHead . ',blending_destroy'),
                                'update_url'=>route('blending.store')
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
                        ->rawColumns(['supplier', 'material_document', 'action', 'from_trace_no'])
                        ->make(true);
        } elseif ($flag == 'get_cmbActiveTank_rundown'){
            $txtData['data'] = BL::get_cmbActiveTank_rundown($request);
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

            if ($flag == 'blendingMaterial_destroy'){
                DB::beginTransaction();
                try {
                    $return = BL::blendingMaterial_destroy($id, $user);
                    DB::commit();
                    $data = $this->returnResponse($return, 'MATERIAL', 'delete');
                    return response()->json($data);
                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'blending_destroy'){
                DB::beginTransaction();
                try {
                    $return = BL::blending_destroy($id, $user);
                    DB::commit();
                    $data = $this->returnResponse($return, 'BLENDING', 'delete');
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
     * Return response as JSON to AJAX call.
     *
     * @param  $return $feature $mode
     * @return \Illuminate\Http\Response
     */
    protected function returnResponse($return, $feature, $mode, $input_1=null, $input_2=null,
                                      $input_3=null, $input_4=null, $input_5=null,
                                      $input_6=null, $input_7=null){

        if ($return == null){
            $data = [
                'status'  => 0,
                'message' => $feature ];
        } else {
            if ($return[0]->response == '1'){
                $data = [
                    'status'  => 1,
                    'mode' => $mode,
                    'idMaterial' => $input_1,
                    'entryDate' => $input_2,
                    'entryNo' => $input_3,
                    'idHead' => $input_4,
                    'materialDoc' => $input_5,
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
                    'message' => $feature . ' No Blend Material!' ];
            } elseif ($return[0]->response == '99'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Period Locked!' ];
            };
        }
        return $data;
    }


}
