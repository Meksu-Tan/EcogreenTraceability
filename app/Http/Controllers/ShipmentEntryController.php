<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Shipment AS SP;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Laratrust;
use File;
use PDF;

class ShipmentEntryController extends Controller
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

            return view('user.trans_shipment.index',$data);
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

            if ($flag == 'post_cancelShip'){
                $traceNo = $request->input('traceNo');

                DB::beginTransaction();
                try {
                    $return = SP::post_cancelShip($user, $request);
                    DB::commit();

                    $data = $this->returnResponse($return, 'SHIPMENT ' . $traceNo, $mode);
                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_entryShip'){
                if ($request->hasFile('doc')) {
                    $file = $request->file('doc');
                    $soNo = $request->input('soNo');

                    // Pastikan file adalah PDF
                    if ($file->getClientOriginalExtension() !== 'pdf') {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Invalid file type! Only PDF is allowed.'
                        ]);
                    }

                    // Tentukan lokasi penyimpanan
                    $destinationPath = public_path('pdf');

                    // Buat nama unik untuk file
                    $fileName = 'shipment_'. $soNo . '.pdf';

                    // Pindahkan file ke lokasi penyimpanan
                    $file->move($destinationPath, $fileName);

                } else {
                    $fileName = null;
                }
                // Tambahkan nama file ke dalam request
                $request->merge(['filename' => $fileName]);

                DB::beginTransaction();
                try {
                    $return = SP::post_entryShip($user, $request);
                    DB::commit();
                    $data = $this->returnResponse($return, 'SHIPMENT ENTRY', $mode);
                    return response()->json($data);

                } catch (\Exception $e) {
                    DB::rollback();
                    $data = $this->returnResponse(null, $e->getMessage(), 500);
                    return response()->json($data);
                }

            } elseif ($flag == 'post_shipEntry_soNo'){

                $return = SP::post_shipEntry_soNo($user, $request);
                $data = $this->returnResponse($return, 'SO ENTRY', $mode);
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

        if ($flag == 'get_dtShipEntry'){
            $db = SP::get_dtShipEntry();

            return DataTables::of($db)
                    ->addColumn('so_no', function($data) {
                            return view('user.trans_shipment.datatables.__actionSoEntry', [
                                'model'=> $data
                            ]);
                        })
                    ->addColumn('action', function($data){
                        return view('user.trans_shipment.datatables.__actionShipEntry', [
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
                    ->rawColumns(['supplier', 'action', 'so_no'])
                    ->make(true);
        } elseif ($flag == 'get_activeFgProduct'){
            $txtData['data'] = SP::get_activeFgProduct();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_wipMaterialByFgProduct'){
            $txtData['data'] = SP::get_wipMaterialByFgProduct($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_activeBatchProduct'){
            $txtData['data'] = SP::get_activeBatchProduct($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_shipmentBatchPackaging'){
            $txtData['data'] = SP::get_shipmentBatchPackaging($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_csmark'){
            $txtData['data'] = SP::get_csmark($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_splabel'){
            $txtData['data'] = SP::get_splabel($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_label'){
            $txtData['data'] = SP::get_label($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtPreparationRecord'){
            $db = SP::get_dtPreparationRecord($request);

            return DataTables::of($db)
                    ->make(true);
        } elseif ($flag == 'get_datShipment'){
            $batchNo = $request->input('batchNo');
            $soNo = $request->input('soNo');
            $soItem = $request->input('soItem');
            $flag = 'ZFM_EUDR_SHIPMENT';

            // Calculate the volume using the getDataFromSap function
            $jsonResponse = $this->getDataFromSap($flag, $soNo, $soItem, $batchNo);
            $txtData = $jsonResponse->original;

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
                    'message' => $feature . ' Packaging Trace Entry Not Found!' ];
            };
        }

        return $data;
    }

    /**
    * Inquiry PO data from SAP API.
    *
    * @return \Illuminate\Http\Response
    */
    public function getDataFromSAP($flag=null, $soNo=null, $soItem=null, $batchNo=null)
    {
        if ($flag == 'ZFM_EUDR_SHIPMENT'){
            $sapClient = 'Client=PRD-300';
            $sapReqUrl = 'http://eows.ecogreenoleo.co.id/general.php?';
            $sapFm     = '&FM='.$flag;
            $input_1   = '&SO_NUM='.$soNo;
            $input_2   = '&SO_ITEM='.$soItem;
            $input_3   = '&BATCH='.$batchNo;

            if ($batchNo == "FB"){
                $eobUrl = $sapReqUrl.$sapClient.$sapFm.$input_1.$input_2;
            } elseif ($batchNo == "IS"){
                $eobUrl = $sapReqUrl.$sapClient.$sapFm.$input_1.$input_2;
            } elseif ($batchNo == "VS"){
                $eobUrl = $sapReqUrl.$sapClient.$sapFm.$input_1.$input_2;
            } else {
                $eobUrl = $sapReqUrl.$sapClient.$sapFm.$input_1.$input_2.$input_3;
            }
        }

        $ch = curl_init($eobUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL request
        $response = curl_exec($ch);
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_message = curl_error($ch);
            // Handle cURL error
            return response()->json(['error' => $error_message], 500);
        }
        // Close cURL resource
        curl_close($ch);
        // Process the SAP response
        $data = json_decode($response, true);
        // Return the retrieved data
        return response()->json(['data' => $data], 200);
    }

}
