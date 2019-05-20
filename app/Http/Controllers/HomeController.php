<?php

namespace App\Http\Controllers;

use App\Usuarios;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      if (isset(\Illuminate\Support\Facades\Auth::user()->id)){
        $usuarios = Usuarios::orderBy('nombre')->get();
        return view('home', [
            'usuarios' => $usuarios
        ]);
      }else{
          return view('auth.login');
      }
    }
}
