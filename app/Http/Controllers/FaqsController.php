<?php

namespace App\Http\Controllers;

use App\Faqs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class FaqsController extends Controller
{
    public function index(){
        $nombre_filtro = '';

        $perPage = 150;
        $page = Input::get('page');
        $pageName = 'page';
        $page = Paginator::resolveCurrentPage($pageName);
        $offSet = ($page * $perPage) - $perPage;

        $model = Faqs::where('id', '>', 0);

        if(Input::has('nombre_filtro')){
            $nombre_filtro = Input::get('nombre_filtro');
            if($nombre_filtro != ''){
                $model = $model->where('nic', 'like', DB::raw("'%$nombre_filtro%'"));
            }
        }
        $modelAux = $model;
        $total_registros = $modelAux->count();
        $model = $model->offset($offSet)->limit($perPage)->orderBy('id')->get();

        $posts = new LengthAwarePaginator($model, $total_registros, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return view('faqs.index', [
            'usuarios' => $posts,
            'nombre_filtro' => $nombre_filtro
        ]);
    }

    public function new(){
      $usuario = new Faqs();
      $usuario->id = 0;
      return view('faqs.new', [
          'usuario' => $usuario
      ]);
    }

    public function view($faq){
      $usuario = Faqs::find($faq);
      return view('faqs.view', [
          'usuario' => $usuario
      ]);
    }

    public function save(Request $request){
      if($request->usuario == 0){
        $usuario = new Faqs();

        $usuario->reclamo = $request->reclamo;
        $usuario->tipo = $request->tipo;
        $usuario->nic = $request->nic;
        $usuario->email_usuario = $request->email_usuario;
        $usuario->celular_usuaio = $request->celular_usuaio;
        $usuario->email_usuario = $request->email_usuario;
        $usuario->observacion = $request->observacion;

        $usuario->estado = 1;
        $usuario->id_user = Auth::user()->id;
        $usuario->save();
      }else{
        $usuario = Faqs::find($request->usuario);

        $usuario->respuesta = $request->respuesta;

        $usuario->estado = 2;
        $usuario->save();
      }

      return redirect()->route('faqs', ['usuario' => $usuario->id]);
    }
}
