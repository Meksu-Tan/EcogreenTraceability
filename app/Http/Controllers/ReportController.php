<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Report AS RPT;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Response;
use Laratrust;
use File;
use PDF;

class ReportController extends Controller
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

        if (Laratrust::hasRole(['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff', 'viewer'])) {
            $data = [
                'user' => $n_users,
                'role' => $n_roles,
                'permission' => $n_perms,
                'user' => Auth::user(),
                'role' => implode(array_map('ucfirst', Auth::user()->roles->pluck('name')->toArray())),
            ];

            return view('user.inquiry_report.index',$data);
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

        if ($flag == 'get_dtTsReport'){

            $db = RPT::get_dtTsReport($request);

            return DataTables::of($db)
                    ->addColumn('from_trace_no', function($row){
                        $batches = explode('|', $row->from_trace_no);
                        $output = '';
                        foreach ($batches as $from_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('to_trace_no', function($row){
                        $batches = explode('|', $row->to_trace_no);
                        $output = '';
                        foreach ($batches as $to_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $to_trace_no . '</span> ';
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
                    ->rawColumns(['supplier', 'from_trace_no', 'to_trace_no'])
                    ->make(true);

        } elseif ($flag == 'get_dtTsReportRm'){

            $db = RPT::get_dtTsReportRm($request);
            return DataTables::of($db)
                    ->addColumn('from_trace_no', function($row){
                        $batches = explode('|', $row->from_trace_no);
                        $output = '';
                        foreach ($batches as $from_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('to_trace_no', function($row){
                        $batches = explode('|', $row->to_trace_no);
                        $output = '';
                        foreach ($batches as $to_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $to_trace_no . '</span> ';
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
                    ->rawColumns(['supplier', 'from_trace_no', 'to_trace_no'])
                    ->make(true);

        } elseif ($flag == 'get_dtTsReportPck'){

            $db = RPT::get_dtTsReportPck($request);
            return DataTables::of($db)
                    ->addColumn('from_trace_no', function($row){
                        $batches = explode('|', $row->from_trace_no);
                        $output = '';
                        foreach ($batches as $from_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('to_trace_no', function($row){
                        $batches = explode('|', $row->to_trace_no);
                        $output = '';
                        foreach ($batches as $to_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $to_trace_no . '</span> ';
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
                    ->rawColumns(['supplier', 'from_trace_no', 'to_trace_no'])
                    ->make(true);

        } elseif ($flag == 'get_dtTsReportShip'){

            $db = RPT::get_dtTsReportShip($request);
            return DataTables::of($db)
                    ->addColumn('from_trace_no', function($row){
                        $batches = explode('|', $row->from_trace_no);
                        $output = '';
                        foreach ($batches as $from_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('to_trace_no', function($row){
                        $batches = explode('|', $row->to_trace_no);
                        $output = '';
                        foreach ($batches as $to_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $to_trace_no . '</span> ';
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
                    ->rawColumns(['supplier', 'from_trace_no', 'to_trace_no'])
                    ->make(true);

        } elseif ($flag == 'get_dtTsReportTrf'){

            $db = RPT::get_dtTsReportTrf($request);
            return DataTables::of($db)
                    ->addColumn('from_trace_no', function($row){
                        $batches = explode('|', $row->from_trace_no);
                        $output = '';
                        foreach ($batches as $from_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $from_trace_no . '</span> ';
                        }
                        return $output;
                    })
                    ->addColumn('to_trace_no', function($row){
                        $batches = explode('|', $row->to_trace_no);
                        $output = '';
                        foreach ($batches as $to_trace_no) {
                            $output .= '<span class="badge badge-light" style="color:black;margin-top:2px">' . $to_trace_no . '</span> ';
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
                    ->rawColumns(['supplier', 'from_trace_no', 'to_trace_no'])
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
