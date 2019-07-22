<?php

namespace App\Http\Controllers;

use App\AdminTable as Users;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class UsuariosController extends Controller
{
    public function index(){
        $nombre_filtro = '';

        $perPage = 150;
        $page = Input::get('page');
        $pageName = 'page';
        $page = Paginator::resolveCurrentPage($pageName);
        $offSet = ($page * $perPage) - $perPage;

        $model = Users::where('id', '>', 0);
        $modelAux1 = Users::where('id', '>', 0);
        $modelAux2 = Users::where('id', '>', 0);

        if(Input::has('nombre_filtro')){
            $nombre_filtro = Input::get('nombre_filtro');
            if($nombre_filtro != ''){
                $model = $model->where('name', 'like', DB::raw("'%$nombre_filtro%'"));
                $modelAux1 = $modelAux1->where('name', 'like', DB::raw("'%$nombre_filtro%'"));
                $modelAux2 = $modelAux2->where('name', 'like', DB::raw("'%$nombre_filtro%'"));
            }
        }
        $modelAux = $model;
        $total_registros = $modelAux->count();
        $model = $model->offset($offSet)->limit($perPage)->orderBy('id')->get();

        $posts = new LengthAwarePaginator($model, $total_registros, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return view('users.index', [
            'usuarios' => $posts,
            'nombre_filtro' => $nombre_filtro
        ]);
    }

    public function view($usuario){
      $usuario = Users::find($usuario);
      return view('users.view', [
          'usuario' => $usuario
      ]);
    }

    public function save(Request $request){
      $usuario = Users::find($request->usuario);

      $usuario->name = $request->name;
      $usuario->email = $request->email;
      $usuario->password = $request->password;
      $usuario->puesto = $request->puesto;
      $usuario->estado = $request->estado;
      $usuario->save();

      return redirect()->route('usuarios.view', ['usuario' => $request->usuario]);
    }

    public function deleteServicio($agenda, $servicio){
      $agenda = Agenda::where('id', $agenda)->first();

      Auditoria::where('id', $servicio)->where('estado', 1)->delete();

      return redirect()->route('agenda.detalle', ['id' => $agenda]);
    }
}
