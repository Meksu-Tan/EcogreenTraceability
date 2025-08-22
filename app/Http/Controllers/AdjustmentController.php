<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Wip AS WP;
use App\Models\Adjustment AS AD;
use App\Imports\ExcelFile;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Laratrust;
use File;
use PDF;

class AdjustmentController extends Controller
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

            return view('user.setup_adjustment.index',$data);
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

            if ($flag == 'post_matlDocNumber'){
                $return = WP::post_matlDocNumber($user, $request);
                $data = $this->returnResponse($return, 'MATL DOC NO', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_storeAdjustment'){
                $id_material = $request->input('id_material');
                $adjustQty = $request->input('qty');
                $entryDate = $request->input('entryDate');
                $id_tank = $request->input('idTank');

                DB::beginTransaction();
                try {
                    $lockReturn = AD::get_lockStatus($entryDate);
                    if ($lockReturn[0]->response == 99){
                        $data = $this->returnResponse($lockReturn, 'ADJUSTMENT WIP', $mode);
                        return response()->json($data);
                    } else {
                        $return = AD::post_storeAdjustment($user, $id_material, $adjustQty, $entryDate, $id_tank);
                        DB::commit();
                        $data = $this->returnResponse($return, 'ADJUSTMENT WIP', $mode);
                        return response()->json($data);
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_storeAdjustmentWhx'){
                $return = AD::post_storeAdjustmentWhx($user, $request);
                $data = $this->returnResponse($return, 'ADJUSTMENT WHx', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_adjustmentInitSupplier'){
                $idTank = $request->input('idTank');
                $entryDate = $request->input('entryDate');
                $adjNumber = $request->input('adjNumber');
                $idHead = $request->input('idHead');
                $materialDoc = $request->input('materialDoc');
                $idMaterial = $request->input('idMaterial');
                $flagHead = $request->input('flagHead');
                $batchNo = $request->input('batchNo');
                $poNo = $request->input('poNo');

                $return = AD::post_adjEntrySupplier($user, $request);
                $data = $this->returnResponse($return, 'SUPPLIER', $mode, $flagHead, $idTank, $entryDate, $adjNumber, $idHead,
                                              $materialDoc, $idMaterial, $batchNo, $poNo);
                return response()->json($data);
            } elseif ($flag == 'post_adjustmentInit'){
                $mode = $request->input('mode');
                $idHead = $request->input('idHead');
                $entry_no = $request->input('entry_no');
                $entry_date = $request->input('entry_date');
                $id_tank = $request->input('tank');
                $qty = $request->input('qty');
                $id_material = $request->input('idMaterial');
                $qty = floatval(str_replace(',', '', $qty));
                $materialDoc = $request->input('material_doc');

                $lockReturn = AD::get_lockStatus($entry_date);
                if ($lockReturn[0]->response == 99){
                    $data = $this->returnResponse($lockReturn, 'ADJUSTMENT WIP', $mode);
                    return response()->json($data);
                } else {
                    $return = AD::post_adjustmentInit($user, $mode, $idHead, $entry_no, $entry_date, $id_tank, $qty, $id_material, $materialDoc);
                    $data = $this->returnResponse($return, 'ADJUSTMENT WIP', $mode);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_adjustmentInitWhx'){
                $return = AD::post_adjustmentInitWhx($user, $request);
                $data = $this->returnResponse($return, 'ADJUSTMENT WHx', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_adjPeriodHeader'){
                $return = AD::post_adjPeriodHeader($user, $request);
                $data = $this->returnResponse($return, 'ADJUSTMENT PERIOD', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_adjPeriodHeader_uploadExcel'){
                $request->validate([
                    'file' => 'required|mimes:xlsx,xls,csv|max:2048',
                ]);

                $data = Excel::toArray(new ExcelFile, $request->file('file'));
                $totalRows = count($data[0]);

                /* CHECK OVERALL DATA INTEGRITY */
                    for ($i = 1; $i < $totalRows; $i++) {
                        $plantSource = $data[0][$i][0];
                        $tfNumber = $data[0][$i][1];
                        $materialCode = $data[0][$i][2];
                        $materialDescription = $data[0][$i][3];
                        $capacity = $data[0][$i][4];
                        $sounding = $data[0][$i][5];
                        $temperature = $data[0][$i][6];
                        $volume = $data[0][$i][7];
                        $density = $data[0][$i][8];
                        $qty = $data[0][$i][9];

                        $return = AD::get_adjPeriodHeader_integrityCheck($tfNumber, $materialCode);
                        if ($return[0]->response == '0'){
                            if ($return[0]->feature == 0){
                                $output = $this->returnResponse($return, $tfNumber . ' & ' . $materialCode . ' not exist' , $mode);
                                return response()->json($output);
                            } elseif ($return[0]->feature == 1){
                                $output = $this->returnResponse($return, $materialCode . ' not exist' , $mode);
                                return response()->json($output);
                            } else if ($return[0]->feature == 2){
                                $output = $this->returnResponse($return, $tfNumber . ' not exist' , $mode);
                                return response()->json($output);
                            }
                        };
                    }
                /* SET TO NON-ACTIVE OLD DATA */
                    AD::post_adjPeriodHeader_delete($user, $request);

                /* SAVING UPLOADED DATA */
                    for ($i = 1; $i < $totalRows; $i++) {
                        $plantSource = $data[0][$i][0];
                        $tfNumber = $data[0][$i][1];
                        $materialCode = $data[0][$i][2];
                        $materialDescription = $data[0][$i][3];
                        $capacity = $data[0][$i][4];
                        $sounding = $data[0][$i][5];
                        $temperature = $data[0][$i][6];
                        $volume = $data[0][$i][7];
                        $density = $data[0][$i][8];
                        $qty = $data[0][$i][9];

                        $return = AD::post_adjPeriodHeader_uploadExcel($user, $request, $plantSource, $tfNumber, $materialCode, $materialDescription,
                                                                       $capacity, $sounding, $temperature, $volume, $density, $qty);
                    }


                $output = $this->returnResponse($return, 'UPLOAD EXCEL', $mode);
                return response()->json($output);

            } elseif ($flag == 'post_adjPeriodView_onHand'){
                $idHead = $request->input('idHead');

                $adjData = AD::get_adjPeriodView_dt($request);
                $lenData = count($adjData);
                for ($i = 0; $i < $lenData; $i++){
                    $idMaterial = $adjData[$i]->id_material;
                    $plant = $adjData[$i]->plant;
                    $idSloc = $adjData[$i]->id_sloc;
                    $qty = $adjData[$i]->qty_pspa;

                    $return = AD::post_adjPeriodView_onHand($user, $request, $idMaterial, $plant, $idSloc, $qty);
                }

                $data = $this->returnResponse($return, 'ON-HAND CALCULATION', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_adjPeriodView_adjustment'){
                $adjData = AD::get_adjPeriodView_dt($request);
                $lenData = count($adjData);

                for ($i = 0; $i < $lenData; $i++){
                    $idMaterial = $adjData[$i]->id_material;
                    $plant = $adjData[$i]->plant;
                    $idSloc = $adjData[$i]->id_sloc;
                    $qty = $adjData[$i]->qty_pspa;
                    $qtyOnhand = $adjData[$i]->qty_onhand;
                    $adjType = $adjData[$i]->adj_type;
                    $adjStatus = $adjData[$i]->adjust_status;

                    $return = AD::post_adjPeriodView_adjustment($user, $request, $idMaterial, $plant, $idSloc, $qty, $qtyOnhand, $adjType, $adjStatus);
                }
                $data = $this->returnResponse($return, 'PERIOD ADJUSTMENT', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_adjPeriodHeader_lock'){
                $lockStatus = $request->input('lockStatus');

                $return = AD::post_adjPeriodHeader_lock($user, $request);
                if ($lockStatus == 1){
                    $data = $this->returnResponse($return, 'LOCK PERIOD', $mode);
                } else {
                    $data = $this->returnResponse($return, 'UN-LOCK PERIOD', $mode);
                }

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

        if ($flag == 'get_dtAdjustment'){
            $db = AD::get_dtAdjustment($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                            return view('user.setup_adjustment.datatables.__actionAdjustment', [
                                'model'=> $data,
                                'destroy_url'=> route('adjustment.destroy', $data->id_adjust_head . ',adjustment_deactivate'),
                            ]);
                        })
                    ->addColumn('material_document', function($data) {
                            return view('user.setup_adjustment.datatables.__actionMatlDocAdjustment', [
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
                    ->rawColumns(['supplier', 'action', 'material_document'])
                    ->make(true);
        } elseif ($flag == 'get_cmbActiveMaterial'){
            $txtData['data'] = AD::get_cmbActiveMaterial();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_adjNewEntryNumber'){
            $txtData['data'] = AD::get_adjNewEntryNumber();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtSupplierList'){
            $db = AD::get_dtSupplierList($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_adjustment.datatables.__actionAdjSupplier', [
                            'model'=> $data,
                            'destroy_url'=> route('adjustment.destroy', $data->idTail . ',supplier_destroy'),
                            'update_url'=>route('adjustment.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_activeSupplier_bySelect2'){
            $tags = AD::get_activeSupplier_bySelect2($request);
            $formatted_tags = [];
            foreach ($tags as $tag) {
                $formatted_tags[] = ['id' => $tag->id_supplier, 'text' => $tag->supplier];
            };

            return \Response::json($formatted_tags);
        } elseif ($flag == 'get_totalQtySupplier'){
            $txtData['data'] = AD::get_totalQtySupplier($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank'){
            $txtData['data'] = AD::get_cmbActiveTank();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtAdjustmentWhx'){
            $db = AD::get_dtAdjustmentWhx($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                            return view('user.setup_adjustment.datatables.__actionAdjustment', [
                                'model'=> $data,
                                'destroy_url'=> route('adjustment.destroy', $data->id_adjust_head . ',adjustmentWhx_deactivate'),
                            ]);
                        })
                    ->addColumn('material_document', function($data) {
                            return view('user.setup_adjustment.datatables.__actionMatlDocAdjustment', [
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
                    ->rawColumns(['supplier', 'action', 'material_document'])
                    ->make(true);
        } elseif ($flag == 'get_cmbActiveMaterialWhx'){
            $txtData['data'] = AD::get_cmbActiveMaterialWhx();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveWhx'){
            $txtData['data'] = AD::get_cmbActiveWhx();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_adjNewEntryNumberWhx'){
            $txtData['data'] = AD::get_adjNewEntryNumberWhx();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_adjustmentPeriodHeader_dt'){
            $db = AD::get_adjustmentPeriodHeader_dt($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_adjustment.datatables.__actionAdjPeriodHeader', [
                            'model'=> $data,
                            'destroy_url'=> route('adjustment.destroy', $data->id_report_head . ',adjPeriodHeader_destroy'),
                            'update_url'=>route('adjustment.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_adjPeriodView_dt'){
            $db = AD::get_adjPeriodView_dt($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_adjustment.datatables.__actionAdjPeriodView', [
                            'model'=> $data,
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_adjustStatus'){
            $txtData['data'] = AD::get_adjustStatus($request);
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

        if ($id == 'adjustment_wip'){
            return view('user.setup_adjustment.index', $data);
        } elseif ($id == 'adjustment_warehouse'){
            return view('user.setup_adjustment.pages.__adjWarehouse', $data);
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

            if ($flag == 'adjustment_deactivate'){
                $return = AD::post_destroyAdjustment($id, $user);
                $data = $this->returnResponse($return, 'ADJUSTMENT WIP', 'delete');
                return response()->json($data);
            } elseif ($flag == 'supplier_destroy'){
                $return = AD::deleteSupplier($id, $user);
                $data = $this->returnResponse($return, 'SUPPLIER', 'delete');
                return response()->json($data);
            } elseif ($flag == 'adjustmentWhx_deactivate'){
                $return = AD::post_destroyAdjustmentWhx($id, $user);
                $data = $this->returnResponse($return, 'ADJUSTMENT WHx', 'delete');
                 return response()->json($data);
            } elseif ($flag == 'adjPeriodHeader_destroy'){
                $return = AD::post_destroyAdjustmentPeriod($id, $user);
                $data = $this->returnResponse($return, 'ADJUSTMENT PERIOD WIP', 'delete');
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
    protected function returnResponse($return, $feature, $mode, $flagHead=NULL, $idTank=null, $entryDate=null,
                                      $adjNumber=null, $idHead=null, $materialDoc=null, $idMaterial=null,
                                      $batchNo=null, $poNo=null
                                      ){
        if ($return == null){
            $data = [
                'status'  => 0,
                'message' => $feature ];
        } else {
            if ($return[0]->response == '1'){
                $data = [
                    'status'  => 1,
                    'flag' => $flagHead,
                    'mode' => $mode,
                    'idTank' => $idTank,
                    'entryDate' => $entryDate,
                    'adjNumber' => $adjNumber,
                    'idHead' => $idHead,
                    'materialDoc' => $materialDoc,
                    'idMaterial' => $idMaterial,
                    'batchNo' => $batchNo,
                    'poNo' => $poNo,
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
                    'message' => $feature . ' No Reserve WIP' ];
            } elseif ($return[0]->response == '5'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Stok Qty <= 0, use Stock Initialization! ' ];
            } elseif ($return[0]->response == '6'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' No Supplier Entry! ' ];
            } elseif ($return[0]->response == '7'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Stock Qty After Adjust = 0, use Transfer Out! ' ];
            } elseif ($return[0]->response == '8'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' No On-Hand Calc! ' ];
            } elseif ($return[0]->response == '9'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Last Transaction Balance Qty <= 0, use Stock Initialization! ' ];
            } elseif ($return[0]->response == '10'){
                $data = [
                    'status'  => 0,
                    'message' => 'Stock Balance = Adjustment Qty, use Transfer Out!' ];
            } elseif ($return[0]->response == '11'){
                $data = [
                    'status'  => 0,
                    'message' => 'Last record is Adjustment record, cannot adjust!' ];
            } elseif ($return[0]->response == '99'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Period Locked!' ];
            };
        }
        return $data;
    }


}
