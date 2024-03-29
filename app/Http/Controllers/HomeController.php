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
        $usuarioLog = \Illuminate\Support\Facades\Auth::user();
        if($usuarioLog->tipo_usuario == 1){
            $usuarios = Usuarios::orderBy('nombre')->get();
            return view('home', [
                'usuarios' => $usuarios
            ]);
        }else{
            return redirect()->route('agenda.consultas.servicios');
        }

      }else{
          return view('auth.login');
      }
    }
}
