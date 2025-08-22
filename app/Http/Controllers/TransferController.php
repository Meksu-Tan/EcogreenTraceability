<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Transfer AS TRF;
use App\Models\Adjustment AS AD;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
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

            return view('user.trans_transfer.index',$data);
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

            if ($flag == 'post_transferEntry'){

                DB::beginTransaction();

                try {

                    $entryNo = $request->input('entry_no');
                    $entryDate = $request->input('entry_date');
                    $idMaterial = $request->input('id_material');
                    $materialDoc = $request->input('material_doc');
                    $trfQty = $request->input('trf_qty');
                    $trfSource = $request->input('source_sloc');
                    $trfDestination = $request->input('trf_sloc');
                    $supplierCode = $request->input('supplierCode');
                    $idSupplier = $request->input('idSupplier');
                    $trfType = $request->input('trf_type');
                    //dd($supplierCode);

                    $lockReturn = TRF::get_lockStatus($entryDate);

                    if ($lockReturn[0]->response == 99){
                        $data = $this->returnResponse($lockReturn, 'TRANSFER', $mode);
                        return response()->json($data);

                    } else {
                        if ($trfType !== 'all'){
                            if (in_array($trfSource, [7, 8, 9, 12, 13])) {
                                /* AUTO STOCK IN SOURCE SLOC */
                                $idHead = null;
                                $entryAdjNo_dat = AD::get_adjNewEntryNumber();
                                $entryAdjNo = $entryAdjNo_dat[0]->adj_number;
                                /* ENTRY TO t_balance_temporary */
                                TRF::post_adjEntrySupplier($user, $entryAdjNo, $idSupplier, $idMaterial, $trfQty, $supplierCode);
                                /* ENTRY TO t_adjustment */
                                AD::post_adjustmentInit($user, $mode, $idHead, $entryAdjNo, $entryDate, $trfSource, $trfQty, $idMaterial, $materialDoc);
                            }
                        }

                        $return = TRF::post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination);
                        $data = $this->returnResponse($return, 'TRANSFER', $mode);

                        if ($trfType !== 'all'){
                            if (in_array($trfDestination, [7, 8, 9, 12, 13])) {
                                /* AUTOMATE TRF TO ADJUSTMENT OUT */
                                $trfSource = $trfDestination;
                                $trfDestination = 10;
                                $entryNo = $entryNo + 1;

                                TRF::post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination);
                            }
                        }

                        DB::commit();
                    }

                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error' => 'Transaction failed: ' . $e->getMessage()], 500);
                }

            } elseif ($flag == 'post_matlDocNumber'){
                $return = TRF::post_matlDocNumber($user, $request);
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
            $txtData['data'] = TRF::get_cmbActiveMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_newTransferEntryNo'){
            $txtData['data'] = TRF::get_newTransferEntryNo($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_cmbActiveTank_rundown'){
            $txtData['data'] = TRF::get_cmbActiveTank_rundown($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_totalStockMaterial'){
            $txtData['data'] = TRF::get_totalStockMaterial($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtTransferList'){
            $db = TRF::get_dtTransferList($request);
            return DataTables::of($db)
                        ->addColumn('sloc', function($row){
                            $batches = explode('|', $row->sloc);
                            $output = '';
                            foreach ($batches as $sloc) {
                                $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $sloc . '</span> ';
                            }
                            return $output;
                        })
                        ->addColumn('action', function($data){
                            return view('user.trans_transfer.datatables.__actionTransferList', [
                                'model'=> $data,
                                'destroy_url'=> route('transfer.destroy', $data->idHead . '|' . $data->idTraceHead . ',transfer_destroy'),
                                'update_url'=>route('transfer.store')
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
                        ->rawColumns(['supplier', 'material_document', 'action', 'sloc'])
                        ->make(true);
        } elseif ($flag == 'get_updateSupplierMaterial'){
            $txtData['data'] = TRF::get_updateSupplierMaterial($request);
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

            if ($flag == 'transfer_destroy'){
                DB::beginTransaction();
                try {

                    $return = TRF::transfer_destroy($id, $user);
                    DB::commit();

                    $data = $this->returnResponse($return, 'TRANSFER', 'delete');
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
                    'message' => $feature . ' Stock Not Enough' ];
            } elseif ($return[0]->response == '99'){
                $data = [
                    'status'  => 0,
                    'message' => $feature . ' Period Locked!' ];
            } elseif ($return[0]->response == '98'){
                $data = [
                    'status'  => 0,
                    'message' => 'Entry data not found!' ];
            };
        }
        return $data;
    }


}
