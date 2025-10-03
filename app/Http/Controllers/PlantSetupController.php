<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PlantSetup AS SS;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;

class PlantSetupController extends Controller
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
    
                return view('user.setup_plant.index',$data);
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
    
                if ($flag == 'post_plant_store'){
                    $return = SS::post_plant_store($user, $request);
                    $data = $this->returnResponse($return, 'PLANT', $mode);
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
    
            if ($flag == 'get_plant_dt'){
                $db = SS::get_plant_dt();
    
                return DataTables::of($db)
                        ->addColumn('action', function($data){
                            return view('user.setup_plant.datatables.__actionPlant', [
                                'model'=> $data,
                                'destroy_url'=> route('plantsetup.destroy', $data->id_plant . ',plant_deactivate'),
                                'activate_url'=> route('plantsetup.destroy', $data->id_plant . ',plant_activate'),
                                'update_url'=>route('plantsetup.store')
                            ]);
                        })
                        ->rawColumns(['action'])
                        ->make(true);
    
            }
    
            // } elseif ($flag == 'get_plantDetail_dt'){
            //     $db = SS::get_plantDetail_dt($request);
    
            //     return DataTables::of($db)
            //             ->addColumn('action', function($data){
            //                 return view('user.setup_plant.datatables.__actionPlantDetail', [
            //                     'model'=> $data,
            //                     'destroy_url'=> route('plantsetup.destroy', $data->id_plant_tail . ',plantDetail_deactivate'),
            //                     'activate_url'=> route('plantsetup.destroy', $data->id_plant_tail . ',plantDetail_activate'),
            //                     'update_url'=>route('plantsetup.store')
            //                 ]);
            //             })
            //             ->rawColumns(['action'])
            //             ->make(true);
    
            // }
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
                if ($ids[0] == 'plant_detail') {
                    $data['detail'] = $ids[1];
                    $data['storage'] = $ids[2];
                    return view('user.setup_storage.pages.__storageDetail', compact('data'));
                }
            } else {
                if ($id == 'plant'){
                    return view('user.setup_plant.index', $data);
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
    
                if ($flag == 'plant_deactivate'){
                    $return = SS::post_plant_destroy($id, $user);
                    $data = $this->returnResponse($return, 'PLANT', 'de-activate');
                    return response()->json($data);
                } elseif ($flag == 'plant_activate'){
                    $return = SS::post_plant_activate($id, $user);
                    $data = $this->returnResponse($return, 'PLANT', 'activate');
                    return response()->json($data);
                }
                // } elseif ($flag == 'storageDetail_activate'){
                //     $return = SS::post_plantDetail_activate($id, $user);
                //     $data = $this->returnResponse($return, 'TANK', 'activate');
                //     return response()->json($data);
                // } elseif ($flag == 'storageDetail_deactivate'){
                //     $return = SS::post_plantDetail_destroy($id, $user);
                //     $data = $this->returnResponse($return, 'TANK', 'de-activate');
                //     return response()->json($data);
                // }
    
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
