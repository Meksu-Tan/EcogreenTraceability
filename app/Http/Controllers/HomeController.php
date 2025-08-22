<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Dashboard AS DB;
use App\Models\OeeDetail AS OD;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Laratrust;


class HomeController extends Controller
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
                'n_user' => $n_users,
                'n_role' => $n_roles,
                'n_permission' => $n_perms,
                'user' => Auth::user(),
                'role' => implode(array_map('ucfirst', Auth::user()->roles->pluck('name')->toArray())),
            ];

            return view('user.home',$data);

        } else {
            // To make sure user don't have permission.
            $user_permissions = [
                'user-create',
                'user-read',
                'user-update',
                'user-delete'
            ];
            Auth::user()->removePermissions($user_permissions);

            if (Laratrust::hasRole('staff')) {
                // To make sure user don't have permission.
                $task_permissions = [
                    'task-delete',
                    'task-approve',
                    'task-acknowledge'];
                Auth::user()->removePermissions($task_permissions);
            } elseif (Laratrust::hasRole(['supervisor','senior-staff'])) {
                // To make sure user don't have permission.
                $task_permissions = [
                    'task-acknowledge'];
                Auth::user()->removePermissions($task_permissions);
            } elseif (Laratrust::hasRole(['superintendent','senior-supervisor'])) {
                // To make sure user don't have permission.

            }

            $data = [
                'user' => Auth::user(),
                'role' => implode(array_map('ucfirst', Auth::user()->roles->pluck('name')->toArray())),
            ];

            return view('user.home',$data);
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
        $user = Auth::user()->name;

        if ($flag == 'get_cmbActiveSection'){
            $txtData['data'] = DB::getActiveSection();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_activeBatch_allProcess'){
            $txtData['data'] = DB::getActiveBatchAllProcess();
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_updateStatusBatch_byProcess'){
            $txtData['data'] = DB::getUpdateStatusBatchByProcess($request);
            echo json_encode($txtData);
            exit;
        } elseif ($flag == 'get_oeeRecord_total'){
            $batch_no = $request->input('batch_no');

            $ngDat    = OD::getSummaryNgRecord_total($request);
            $prdDat   = OD::getSummaryPrdRecord_total($request);
            $dtDat    = OD::getSummaryDtRecord_total($request);
            //$firstPrd = OD::getFirstPrdRecord($request);
            $startDat = OD::getPrdExecRecord($request);
            $ctDat    = OD::getCycleTime($request);

            $prd_qty = $prdDat[0]->total;
            $ng_qty = $ngDat[0]->total;
            $down_time = $dtDat[0]->total_min;

            date_default_timezone_set('Asia/Jakarta'); // Set the default timezone to Indonesia
            $currentTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta')); // Get the current time
            if ($startDat[0]->started_at !== '0'){
                $startTime = new \DateTime($startDat[0]->started_at, new \DateTimeZone('Asia/Jakarta')); // Define the start time
            } else {
                $startTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta')); // Define the start time
            }
            $interval = $startTime->diff($currentTime); // Calculate the interval between the start time and the current time
            $ope_time = (($interval->days * 24 * 60 * 60) +
                        ($interval->h * 60 * 60) +
                        ($interval->i * 60)) / 60; // Convert the interval to minutes

            $avail_time = ($ope_time) - $down_time;
            $perf = $avail_time / ($ctDat[0]->ct_sec / 60);

            if ($prd_qty == 0){
                if ($batch_no == '-'){
                    $txtData['data'][0]['ar'] = 0 ;
                } else {
                    $txtData['data'][0]['ar'] = ceil($avail_time / $ope_time * 100) ;
                }
                $txtData['data'][0]['qr'] = 100;
                $txtData['data'][0]['pr'] = 0;
                $txtData['data'][0]['oee'] = 0;

            } else {
                $qr = ceil(($prd_qty - $ng_qty) / $prd_qty * 100);
                $pr = ceil($prd_qty / $perf * 100);
                $ar = ceil($avail_time / $ope_time * 100);
                $oee = ceil(($ar/100) * ceil($prd_qty / $perf * 100) * ($qr/100));

                $txtData['data'][0]['ar'] = $ar;
                $txtData['data'][0]['qr'] = $qr;
                $txtData['data'][0]['pr'] = $pr;
                $txtData['data'][0]['oee'] = $oee;
            }

            $txtData['data'][0]['avail_time'] = ceil($avail_time) ;
            $txtData['data'][0]['ope_time'] = ceil($ope_time);

            echo json_encode($txtData);
            exit;
        }

    }

}
