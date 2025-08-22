<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\StorageSetup AS SS;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;

class StorageSetupController extends Controller
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

            return view('user.setup_storage.index',$data);
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

            if ($flag == 'post_storageTank_store'){
                $return = SS::post_storageTank_store($user, $request);
                $data = $this->returnResponse($return, 'STORAGE', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_storageDetail_store'){
                $return = SS::post_storageDetail_store($user, $request);
                $data = $this->returnResponse($return, 'TANK', $mode);
                return response()->json($data);

            } elseif ($flag == 'post_warehouse_store'){
                $return = SS::post_warehouse_store($user, $request);
                $data = $this->returnResponse($return, 'WAREHOUSE', $mode);
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

        if ($flag == 'get_storageTank_dt'){
            $db = SS::get_storageTank_dt();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_storage.datatables.__actionStorageTank', [
                            'model'=> $data,
                            'destroy_url'=> route('storagesetup.destroy', $data->id_tank . ',storageTank_deactivate'),
                            'activate_url'=> route('storagesetup.destroy', $data->id_tank . ',storageTank_activate'),
                            'update_url'=>route('storagesetup.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);

        } elseif ($flag == 'get_storageDetail_dt'){
            $db = SS::get_storageDetail_dt($request);

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_storage.datatables.__actionStorageDetail', [
                            'model'=> $data,
                            'destroy_url'=> route('storagesetup.destroy', $data->id_tank_tail . ',storageDetail_deactivate'),
                            'activate_url'=> route('storagesetup.destroy', $data->id_tank_tail . ',storageDetail_activate'),
                            'update_url'=>route('storagesetup.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);

        } elseif ($flag == 'get_warehouse_dt'){
            $db = SS::get_warehouse_dt();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_storage.datatables.__actionWarehouse', [
                            'model'=> $data,
                            'destroy_url'=> route('storagesetup.destroy', $data->id_warehouse . ',warehouse_deactivate'),
                            'activate_url'=> route('storagesetup.destroy', $data->id_warehouse . ',warehouse_activate'),
                            'update_url'=>route('storagesetup.store')
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

        if (strpos($id, ',') !== false) {
            $ids = explode(',', $id);
            $ids = array_map('trim', $ids); // Trim each element to remove any extra spaces
            if ($ids[0] == 'storage_tank_detail') {
                $data['detail'] = $ids[1];
                $data['storage'] = $ids[2];
                return view('user.setup_storage.pages.__storageDetail', compact('data'));
            }
        } else {
            if ($id == 'storage_tank'){
                return view('user.setup_storage.index', $data);
            } elseif ($id == 'storage_warehouse'){
                return view('user.setup_storage.pages.__warehouse', $data);
            }
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

            if ($flag == 'storageTank_deactivate'){
                $return = SS::post_storageTank_destroy($id, $user);
                $data = $this->returnResponse($return, 'STORAGE', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'storageTank_activate'){
                $return = SS::post_storageTank_activate($id, $user);
                $data = $this->returnResponse($return, 'STORAGE', 'activate');
                return response()->json($data);
            } elseif ($flag == 'storageDetail_activate'){
                $return = SS::post_storageDetail_activate($id, $user);
                $data = $this->returnResponse($return, 'TANK', 'activate');
                return response()->json($data);
            } elseif ($flag == 'storageDetail_deactivate'){
                $return = SS::post_storageDetail_destroy($id, $user);
                $data = $this->returnResponse($return, 'TANK', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'warehouse_activate'){
                $return = SS::post_warehouse_activate($id, $user);
                $data = $this->returnResponse($return, 'WAREHOUSE', 'activate');
                return response()->json($data);
            } elseif ($flag == 'warehouse_deactivate'){
                $return = SS::post_warehouse_destroy($id, $user);
                $data = $this->returnResponse($return, 'WAREHOUSE', 'de-activate');
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
                'message' => $feature . ' Entry Error' ];
        };

        return $data;
    }


}
