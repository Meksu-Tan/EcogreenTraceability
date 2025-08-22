<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\MaterialSetup AS MS;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;

class MaterialSetupController extends Controller
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

            return view('user.setup_material.index',$data);
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

            if ($flag == 'post_storeMaterial'){
                $return = MS::post_storeMaterial($user, $request);
                $data = $this->returnResponse($return, 'MATERIAL', $mode);
                return response()->json($data);
            } elseif ($flag == 'post_storeMatlPck'){
                $return = MS::post_storeMatlPck($user, $request);
                $data = $this->returnResponse($return, 'MATERIAL PCK', $mode);
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

        if ($flag == 'get_dtMaterial'){
            $db = MS::get_dtMaterial();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_material.datatables.__actionMaterial', [
                            'model'=> $data,
                            'destroy_url'=> route('materialsetup.destroy', $data->id_material . ',material_deactivate'),
                            'activate_url'=> route('materialsetup.destroy', $data->id_material . ',material_activate'),
                            'update_url'=>route('materialsetup.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        } elseif ($flag == 'get_cmbActiveSourceProduct'){
            $txtData['data'] = MS::get_cmbActiveSourceProduct();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_dtMatlPck'){
            $db = MS::get_dtMatlPck();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('user.setup_material.datatables.__actionMaterialPck', [
                            'model'=> $data,
                            'destroy_url'=> route('materialsetup.destroy', $data->id_materialpck . ',materialPck_deactivate'),
                            'activate_url'=> route('materialsetup.destroy', $data->id_materialpck . ',materialPck_activate')
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

        if ($id == 'matl_wip'){
            return view('user.setup_material.index', $data);
        } elseif ($id == 'matl_packaging'){
            return view('user.setup_material.pages.__matlPackaging', $data);
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

            if ($flag == 'material_deactivate'){
                $return = MS::post_destroyMaterial($id, $user);
                $data = $this->returnResponse($return, 'MATERIAL', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'material_activate'){
                $return = MS::post_activateMaterial($id, $user);
                $data = $this->returnResponse($return, 'MATERIAL', 'activate');
                return response()->json($data);
            } elseif ($flag == 'materialPck_deactivate'){
                $return = MS::post_destroyMaterialPck($id, $user);
                $data = $this->returnResponse($return, 'PACKAGING PRD', 'de-activate');
                return response()->json($data);
            } elseif ($flag == 'materialPck_activate'){
                $return = MS::post_activateMaterialPck($id, $user);
                $data = $this->returnResponse($return, 'PACKAGING PRD', 'activate');
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


}
