<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required'],
            'plant' => ['required'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        DB::insert('INSERT INTO m_plant_user (user_id, id_plant) VALUES (?, ?)', [
            $user->id,
            $data['plant'],
        ]);

        event(new Registered($user));
        Auth::login($user);
        if ($data['type'] == 'admin'){
            $user->addRole('admin');
            $user->givePermission('task-create');
            $user->givePermission('task-read');
            $user->givePermission('task-update');
            $user->givePermission('task-delete');
            $user->givePermission('task-approve');
            $user->givePermission('task-acknowledge');
            //return redirect('writer/articles');
        }
        if ($data['type'] == 'manager'){
            $user->addRole('manager');
            $user->givePermission('task-create');
            $user->givePermission('task-read');
            $user->givePermission('task-update');
            $user->givePermission('task-delete');
            $user->givePermission('task-approve');
            $user->givePermission('task-acknowledge');
            //return redirect('writer/articles');
        }
        if ($data['type'] == 'superintendent'){
            $user->addRole('superintendent');
            $user->givePermission('task-create');
            $user->givePermission('task-read');
            $user->givePermission('task-update');
            $user->givePermission('task-delete');
            $user->givePermission('task-approve');
            //return redirect('articles');
        }
        if ($data['type'] == 'supervisor'){
            $user->addRole('supervisor');
            $user->givePermission('task-create');
            $user->givePermission('task-read');
            $user->givePermission('task-update');
            $user->givePermission('task-delete');
            //return redirect('articles');
        }
        if ($data['type'] == 'staff'){
            $user->addRole('staff');
            $user->givePermission('task-create');
            $user->givePermission('task-read');
            $user->givePermission('task-update');
            //return redirect('articles');
        }
        if ($data['type'] == 'viewer'){
            $user->addRole('viewer');
            $user->givePermission('task-read');
        }

        return $user;
    }

    public function showRegistrationForm()
    {
        $plants = DB::select('SELECT id_plant, description FROM m_plant WHERE status = 1 ORDER BY description ASC');
        return view('auth.register', compact('plants'));
    }
}
