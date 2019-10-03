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
use Mail;

class FaqsController extends Controller
{
    protected $idReclamo = '';
    protected $aux = '';
    protected $emailCopy = '';
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
        $model = $model->offset($offSet)->limit($perPage)->orderByDesc('id')->get();

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
      $fotoreclamo = config('myconfig.ruta_fotos_faqs')  . $usuario->imagen_reclamo;
      $fotorespuesta = config('myconfig.ruta_fotos_faqs')  . $usuario->imagen_respuesta;

      return view('faqs.view', [
          'usuario' => $usuario,
          'fotoreclamo' => $fotoreclamo,
          'fotorespuesta' => $fotorespuesta
      ]);
    }

    public function save(Request $request){
      if($request->usuario == 0){
        $usuario = new Faqs();

        $usuario->reclamo = $request->reclamo;
        $usuario->tipo = $request->tipo;
        $usuario->nic = $request->nic;
        $usuario->email_usuario = $request->email_usuario;
        $usuario->celular_usuario = $request->celular_usuario;
        $usuario->observacion = $request->observacion;

        $usuario->estado = 1;
        $usuario->id_user = Auth::user()->id;
        $usuario->save();
        if ($request->hasFile('image')) {
          $image = $request->file('image');
          $name = $usuario->id.'.'.$image->getClientOriginalExtension();
          $destinationPath = public_path('/images/faqs');
          $image->move($destinationPath, $name);

          $usuario->imagen_reclamo = $name;
          $usuario->save();
        }

        //enviar a los correos
        $nombreGestor = Auth::user()->name;
        if($usuario->reclamo == 'LECTURA'){
          $this->idReclamo = $usuario->id;
          $this->aux = 'RECLAMO DE ' . $usuario->tipo;
          $this->emailCopy = Auth::user()->email;
          $data = array(
            'from' => $nombreGestor . ' - ' . $this->emailCopy,
            'asunto' => $this->aux,
            'nic' => $usuario->nic,
            'usuario' => 'Email: ' . $usuario->email_usuario . ' - CEL: ' . $usuario->celular_usuario,
            'comentario' => $usuario->observacion,
          );

          Mail::send('faqs.email', $data, function ($message) {

            $message->from(\Config::get('myconfig.email_noreply'), 'Reclamo de Lectura #' . $this->idReclamo);

            $message->to(\Config::get('myconfig.email_notificacion_lectura'))->cc($this->emailCopy)->subject($this->aux);

          });
        }elseif($usuario->reclamo == 'REPARTO'){
          $this->idReclamo = $usuario->id;
          $this->aux = 'RECLAMO DE ' . $usuario->tipo;
          $this->emailCopy = Auth::user()->email;
          $data = array(
            'from' => $nombreGestor . ' - ' . $this->emailCopy,
            'asunto' => $this->aux,
            'nic' => $usuario->nic,
            'usuario' => 'Email: ' . $usuario->email_usuario . ' - CEL: ' . $usuario->celular_usuario,
            'comentario' => $usuario->observacion,
          );

          Mail::send('faqs.email', $data, function ($message) {

            $message->from(\Config::get('myconfig.email_noreply'), 'Reclamo de Reparto #' . $this->idReclamo);

            $message->to(\Config::get('myconfig.email_notificacion_reparto'))->cc($this->emailCopy)->subject($this->aux);

          });
        }
      }else{
        $usuario = Faqs::find($request->usuario);

        $usuario->respuesta = $request->respuesta;

        $usuario->estado = 2;
        $usuario->save();

        if ($request->hasFile('image')) {
          $image = $request->file('image');
          $name = $usuario->id.'b.'.$image->getClientOriginalExtension();
          $destinationPath = public_path('/images/faqs');
          $image->move($destinationPath, $name);

          $usuario->imagen_respuesta = $name;
          $usuario->save();
        }

        //enviar a los correos
        $nombreGestor = Auth::user()->name;
        if($usuario->reclamo == 'LECTURA'){
          $this->idReclamo = $usuario->id;
          $this->aux = 'RESPUESTA RECLAMO DE ' . $usuario->tipo;
          $this->emailCopy = $usuario->user->email;
          $data = array(
            'from' => $nombreGestor . ' - ' . Auth::user()->email,
            'asunto' => $this->aux,
            'nic' => $usuario->nic,
            'usuario' => 'Email: ' . $usuario->email_usuario . ' - CEL: ' . $usuario->celular_usuario,
            'comentario' => $usuario->observacion,
            'respuesta' => $usuario->respuesta,
          );

          Mail::send('faqs.email', $data, function ($message) {

            $message->from(\Config::get('myconfig.email_noreply'), 'Respuesta a Reclamo de Lectura #' . $this->idReclamo);

            $message->to($this->emailCopy)->cc(\Config::get('myconfig.email_notificacion_lectura'))->subject($this->aux);

          });
        }elseif($usuario->reclamo == 'REPARTO'){
          $this->idReclamo = $usuario->id;
          $this->aux = 'RESPUESTA RECLAMO DE ' . $usuario->tipo;
          $this->emailCopy = $usuario->user->email;
          $data = array(
            'from' => $nombreGestor . ' - ' . Auth::user()->email,
            'asunto' => $this->aux,
            'nic' => $usuario->nic,
            'usuario' => 'Email: ' . $usuario->email_usuario . ' - CEL: ' . $usuario->celular_usuario,
            'comentario' => $usuario->observacion,
            'respuesta' => $usuario->respuesta,
          );

          Mail::send('faqs.email', $data, function ($message) {

            $message->from(\Config::get('myconfig.email_noreply'), 'Respuesta a Reclamo de Reparto #' . $this->idReclamo);

            $message->to($this->emailCopy)->cc(\Config::get('myconfig.email_notificacion_reparto'))->subject($this->aux);

          });
        }
      }

      return redirect()->route('faqs', ['usuario' => $usuario->id]);
    }
}
