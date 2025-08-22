<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\SupplierSetup AS SS;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;

class SupplierSetupController extends Controller
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

            return view('user.setup_supplier.index',$data);
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

            if ($flag == 'post_storeSupplier'){
                $return = SS::post_storeSupplier($user, $request);
                $data = $this->returnResponse($return, 'SUPPLIER', $mode);
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

        if ($flag == 'get_dtSupplier'){
            $db = SS::get_dtSupplier();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_supplier.datatables.__actionSupplier', [
                            'model'=> $data,
                            'destroy_url'=> route('suppliersetup.destroy', $data->id_supplier . ',supplier_deactivate'),
                            'activate_url'=> route('suppliersetup.destroy', $data->id_supplier . ',supplier_activate'),
                            'update_url'=>route('suppliersetup.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
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

            if ($flag == 'supplier_deactivate'){
                $return = SS::post_destroySupplier($id, $user);
                $data = $this->returnResponse($return, 'SUPPLIER', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'supplier_activate'){
                $return = SS::post_activateSupplier($id, $user);
                $data = $this->returnResponse($return, 'SUPPLIER', 'activate');
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
    protected function returnResponse($return, $feature, $mode){

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
                'message' => $feature . ' Batch Number Error' ];
        };

        return $data;
    }

    // Only Authenticated User have access to Dashboard
}
