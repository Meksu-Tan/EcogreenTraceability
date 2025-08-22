<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifikasiEmail;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email'
        ]);

        $data = [
            'name' => $request->input('name'),
            'message' => 'Ini adalah notifikasi dari aplikasi Anda.'
        ];

        Mail::to($request->input('email'))->send(new NotifikasiEmail($data));

        return response()->json(['success' => true, 'message' => 'Email berhasil dikirim!']);
    }
}
