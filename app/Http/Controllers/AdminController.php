<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserMgmt AS UM;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Laratrust;

class AdminController extends Controller
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

        if (Laratrust::hasRole(['admin', 'super-admin', 'manager'])) {
            $data = [
                'user' => $n_users,
                'role' => $n_roles,
                'permission' => $n_perms,
            ];

            return view('admin.users.index',$data);

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
        if (Auth::user()->hasPermission('user-update')) {
            $email = $request->input('email');
            $password = $request->input('password');
            $user = Auth::user()->name;

            $return = UM::updatePassword($email, $password, $user);

                if ($return[0]->response == '1'){
                    $data = [
                        'status'  => 1,
                        'message' => 'Success update password' ];
                } elseif ($return[0]->response == '0'){
                    $data = [
                        'status'  => 0,
                        'message' => 'Failed update password' ];
                } elseif ($return[0]->response == '2'){
                    $data = [
                        'status'  => 0,
                        'message' => 'Password already exists' ];
                }
                return response()->json($data);
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

        if ($flag == 'get_userData'){
            $db = UM::getUserData();

            return DataTables::of($db)
                    ->addColumn('action', function($data){
                        return view('admin.users.datatables._actionUser', [
                            'model'=> $data,
                            'destroy_url'=> route('admin.destroy', $data->id),
                            'reset_url'=> route('admin.edit', $data->email),
                            'update_url'=>route('admin.store')
                        ]);
                    })
                    ->rawColumns(['action'])
                    ->make(true);
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

        $data = [
            'email' =>  $id
        ];
        return view('admin.users.pages.reset', $data);

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

        $return = UM::destroyUser($id, $user);
        if ($return[0]->response == '1'){
            $data = [
                'status'  => 1,
                'message' => 'Success destroy user' ];
        } elseif ($return[0]->response == '0'){
            $data = [
                'status'  => 0,
                'message' => 'Failed destroy user' ];
        } elseif ($return[0]->response == '2'){
            $data = [
                'status'  => 0,
                'message' => 'User already exists' ];
        }
        return response()->json($data);

    }


}
