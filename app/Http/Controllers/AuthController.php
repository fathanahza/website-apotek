<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(){
        return view('auth.register');
    }

    public function registerAction(Request $request){
        $request->validate([
            'name' => 'required',
            'email'=> 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);

        User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'

        ]);

        return redirect('/') ->with('success', 'Register berhasil, silahkan login');
    }

    function login(){
        return view('auth.login');

    }
    function loginAction(Request $request){
        $datalogin = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

    if(auth()->attempt($datalogin)){
        $request->session()->regenerate();

        return redirect('/obat')->with('success', 'Login berhasil');
        }
        else{
            return back()->with('gagal', 'Login gagal, silahkan cek email dan password anda');
        }
    }
    function logout(){
        Auth::logout();
        return redirect('/')->with('success', 'Logout berhasil');
    }
}

