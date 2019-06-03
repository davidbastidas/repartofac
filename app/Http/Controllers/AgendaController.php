<?php

namespace App\Http\Controllers;

use App\AdminTable;
use App\Agenda;
use App\Anomalias;
use App\Auditoria;
use App\AuditoriaTemp;
use App\ObservacionesRapidas;
use App\User;
use App\Usuarios;
use App\MultifamiliarSuministros;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel;


class AgendaController extends Controller
{
    public function index()
    {
        $perPage = 6;
        $page = Input::get('page');
        $pageName = 'page';
        $page = Paginator::resolveCurrentPage($pageName);
        $offSet = ($page * $perPage) - $perPage;

        $agenda = new Agenda();

        $agendas = $agenda->offset($offSet)->limit($perPage)->orderByDesc('id')->get();

        $total_registros = Agenda::all()->count();
        $array = [];
        $agendaCollection = null;

        foreach ($agendas as $agenda) {

            $user = User::where('id', $agenda->admin_id)->first()->name;

            $pendientes = Auditoria::where('estado', 1)->where('agenda_id', $agenda->id)->count();
            $realizados = Auditoria::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
            $cargasPendientes = AuditoriaTemp::where('agenda_id', $agenda->id)->count();

            $flag = false;

            if ($pendientes > 0){
                $flag = true;
            }
            if ($cargasPendientes > 0){
                $flag = true;
            }
            if ($realizados > 0){
                $flag = true;
            }

            array_push($array, (object)array(
                'id' => $agenda->id,
                'codigo' => $agenda->codigo,
                'fecha' => $agenda->fecha,
                'tipo_lectura_id' => '',
                'usuario' => $user,
                'pendientes' => $pendientes,
                'realizados' => $realizados,
                'cargasPendientes' => $cargasPendientes,
                'flag' => $flag
            ));
        }

        $agendaCollection = new Collection($array);

        $posts = new LengthAwarePaginator($agendaCollection, $total_registros, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return view('agenda.agenda',[
          'agendas' => $posts
        ])->withPosts($posts);
    }

    public function saveAgenda(Request $request)
    {

        $agenda = new Agenda();
        $agenda->fecha = $request->fecha;
        $agenda->admin_id = Auth::user()->id;

        $agenda->save();

        $anio = Carbon::now()->year;

        $agenda->codigo = "AGE-" . $agenda->id . "-" . $anio;

        $agenda->save();

        return redirect()->route('agenda');
    }

    public function viewUpload($id_agenda)
    {
        $agenda = Agenda::where('id', $id_agenda)->first();

        $fecha = explode(' ', $agenda->fecha)[0];

        return view('agenda.upload', ['agenda' => $agenda, 'fecha' =>$fecha]);
    }

    public function subirServicios(Request $request)
    {
        $archivo = $request->file;
        $agenda = Agenda::where('id', $request->agenda)->first();
        $results = Excel::load($archivo)->all()->toArray();
        foreach ($results as $row) {
            foreach ($row as $x => $x_value) {
                $base = [];
                $count = 0;
                foreach ($x_value as $y => $y_value) {
                    $base[$count] = $y_value;
                    $count++;
                }
                $servicio = new AuditoriaTemp();
                $servicio->barrio = $base[1];
                $servicio->localidad = $base[2];
                $servicio->cliente = $base[3];
                $servicio->direccion = $base[4];
                $servicio->nic = $base[5];
                $servicio->nis = $base[6];
                $servicio->nif = $base[7];
                $servicio->ruta = $base[8];
                $servicio->itin = $base[9];
                $servicio->unicom = $base[10];
                $servicio->medidor = $base[11];
                $servicio->paquete = $base[12];
                $servicio->fecha_emision = $base[13]->format('Y-m-d');
                $servicio->lector = $base[14];
                $servicio->pide_foto = $base[15];
                $servicio->pide_gps = $base[16];
                $servicio->admin_id = Auth::user()->id;
                $servicio->agenda_id = $agenda->id;
                $servicio->save();
            }
        }
        return \Redirect::route('agenda');
    }

    public function listar($agenda)
    {
        $lector_filtro = 0;
        $estados_filtro = 0;
        $nic_filtro = '';
        $medidor_filtro = '';

        $agendaModel = Agenda::find($agenda);
        $lectores = null;

        $lectores = AuditoriaTemp::select('lector')->where('agenda_id', $agenda)->groupBy('lector')->get();

        $usuarios = Usuarios::all();

        $perPage = 150;
        $page = Input::get('page');
        $pageName = 'page';
        $page = Paginator::resolveCurrentPage($pageName);
        $offSet = ($page * $perPage) - $perPage;

        $servicios = Auditoria::where('agenda_id', $agenda);
        $serviciosAux1 = Auditoria::where('agenda_id', $agenda);
        $serviciosAux2 = Auditoria::where('agenda_id', $agenda);

        if(Input::has('gestor_filtro')){
            $lector_filtro = Input::get('gestor_filtro');
            if($lector_filtro != 0){
                $servicios = $servicios->where('lector_id', $lector_filtro);
                $serviciosAux1 = $serviciosAux1->where('lector_id', $lector_filtro);
                $serviciosAux2 = $serviciosAux2->where('lector_id', $lector_filtro);
            }
        }
        if(Input::has('estados_filtro')){
            $estados_filtro = Input::get('estados_filtro');
            if($estados_filtro != 0){
                $servicios = $servicios->where('estado', $estados_filtro);
                $serviciosAux1 = $serviciosAux1->where('estado', $estados_filtro);
                $serviciosAux2 = $serviciosAux2->where('estado', $estados_filtro);
            }
        }
        if(Input::has('nic_filtro')){
            $nic_filtro = Input::get('nic_filtro');
            if($nic_filtro != 0){
                $servicios = $servicios->where('nic', $nic_filtro);
                $serviciosAux1 = $serviciosAux1->where('nic', $nic_filtro);
                $serviciosAux2 = $serviciosAux2->where('nic', $nic_filtro);
            }
        }
        if(Input::has('medidor_filtro')){
            $medidor_filtro = Input::get('medidor_filtro');
            if($medidor_filtro != 0){
                $servicios = $servicios->where('medidor', DB::raw("'$medidor_filtro'"));
                $serviciosAux1 = $serviciosAux1->where('medidor', DB::raw("'$medidor_filtro'"));
                $serviciosAux2 = $serviciosAux2->where('medidor', DB::raw("'$medidor_filtro'"));
            }
        }
        $serviciosAux = $servicios;
        $total_registros = $serviciosAux->count();
        $pendientes = $serviciosAux1->where('estado',  '=', DB::raw("1"))->count();
        $realizados = $serviciosAux2->where('estado', '>', DB::raw("1"))->count();
        $servicios = $servicios->offset($offSet)->limit($perPage)->orderBy('id')->get();

        $posts = new LengthAwarePaginator($servicios, $total_registros, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        $lectoresAsignados = Auditoria::select('lector_id')->where('agenda_id', $agenda)->groupBy('lector_id')->get();

        return view('agenda.detalle', [
            'lectores' => $lectores,
            'usuarios' => $usuarios,
            'agenda' => $agenda,
            'agendaModel' => $agendaModel,
            'servicios' => $posts,
            'lectoresAsignados' => $lectoresAsignados,
            'pendientes' => $pendientes,
            'realizados' => $realizados,
            'gestor_filtro' => $lector_filtro,
            'estados_filtro' => $estados_filtro,
            'nic_filtro' => $nic_filtro,
            'medidor_filtro' => $medidor_filtro
        ]);
    }

    public function asignarUnoAUno(Request $request)
    {
        $agenda = Agenda::find($request->agenda);
        $lector = $request->gestor;
        $user = $request->user;

        $servicios = AuditoriaTemp::where('lector', $lector)->where('agenda_id', $agenda->id)->get();

        foreach ($servicios as $servicio) {
          $serv = new Auditoria();
          $serv->barrio = $servicio->barrio;
          $serv->localidad = $servicio->localidad;
          $serv->cliente = $servicio->cliente;
          $serv->direccion = $servicio->direccion;
          $serv->nic = $servicio->nic;
          $serv->nis = $servicio->nis;
          $serv->nif = $servicio->nif;
          $serv->ruta = $servicio->ruta;
          $serv->itin = $servicio->itin;
          $serv->unicom = $servicio->unicom;
          $serv->medidor = $servicio->medidor;
          $serv->paquete = $servicio->paquete;
          $serv->fecha_emision = $servicio->fecha_emision;
          $serv->lector = $servicio->lector;
          $serv->pide_foto = $servicio->pide_foto;
          $serv->pide_gps = $servicio->pide_gps;
          $serv->orden_realizado = 0;
          $serv->estado = 1;
          $serv->lector_id = $user;
          $serv->admin_id = Auth::user()->id;
          $serv->agenda_id = $agenda->id;
          try {
              $serv->save();
              $servicio->delete();
          } catch (\Exception $e) {
          }
        }

        return redirect()->route('agenda.detalle', ['agenda' => $agenda->id]);
    }

    public function asignarAll(Request $request)
    {
        $agenda = Agenda::find($request->agenda);
        $lectoresTemp = AuditoriaTemp::select('lector')->where('agenda_id', $agenda->id)->groupBy('lector')->get();

        foreach ($lectoresTemp as $ges) {
            $gestor = explode(" ", $ges->lector);
            $cedula = trim($gestor[0]);

            $serviciosTemp = AuditoriaTemp::where('lector', $ges->lector)->where('agenda_id', $agenda->id)->get();

            $usuario = Usuarios::where('nickname', $cedula)->first();
            foreach ($serviciosTemp as $servicio) {
              $serv = new Auditoria();
              $serv->barrio = $servicio->barrio;
              $serv->localidad = $servicio->localidad;
              $serv->cliente = $servicio->cliente;
              $serv->direccion = $servicio->direccion;
              $serv->nic = $servicio->nic;
              $serv->nis = $servicio->nis;
              $serv->nif = $servicio->nif;
              $serv->ruta = $servicio->ruta;
              $serv->itin = $servicio->itin;
              $serv->unicom = $servicio->unicom;
              $serv->medidor = $servicio->medidor;
              $serv->paquete = $servicio->paquete;
              $serv->fecha_emision = $servicio->fecha_emision;
              $serv->lector = $servicio->lector;
              $serv->pide_foto = $servicio->pide_foto;
              $serv->pide_gps = $servicio->pide_gps;
              $serv->orden_realizado = 0;
              $serv->estado = 1;
              $serv->lector_id = $usuario->id;
              $serv->admin_id = Auth::user()->id;
              $serv->agenda_id = $agenda->id;

              try {
                $serv->save();
                $servicio->delete();
              } catch (\Exception $e) {
              }
            }
        }
        return redirect()->route('agenda.detalle', ['agenda' => $agenda->id]);
    }

    public function vaciarCarga(Request $request)
    {
        $id = Auth::user()->id;
        $agenda = Agenda::find($request->agenda);
        AuditoriaTemp::where('admin_id', $id)->where('agenda_id', $request->agenda)->delete();

        return redirect()->route('agenda.detalle', ['agenda' => $agenda->id]);
    }

    public function deleteAgenda($agenda)
    {
        $agenda = Agenda::where('id', $agenda)->first();

        $pendientes = Auditoria::where('estado', 1)->where('agenda_id', $agenda->id)->count();
        $realizados = Auditoria::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
        $cargasPendientes = AuditoriaTemp::where('agenda_id', $agenda->id)->count();

        $flag = true;

        if ($pendientes > 0 || $cargasPendientes > 0 || $realizados > 0){
            $flag = false;
        }
        if ($flag){
            $agenda->delete();
        }

        return \Redirect::route('agenda');
    }

    public function viewServicio($agenda, $servicio_id){
      $agenda = Agenda::where('id', $agenda)->first();
      $servicio = null;
      $path = '';
      $view = '';
      $critica = false;
      $mensajeCritica = '';
      $historiaLecturas = null;

      $servicio = Auditoria::where('id', $servicio_id)->first();
      $filename = $servicio->id . ".png";
      $path = config('myconfig.public_fotos_auditoria')  . $filename;
      $view = 'agenda.editar_auditoria';

      $anomalias = Anomalias::all();
      $observaciones = ObservacionesRapidas::all();

      return view($view, [
          'servicio' => $servicio,
          'agenda' => $agenda,
          'anomalias' => $anomalias,
          'observaciones' => $observaciones,
          'path' => $path,
          'critica' => $critica,
          'mensajeCritica' => $mensajeCritica,
          'ultimasLecturas' => $historiaLecturas
      ]);
    }

    public function saveAviso(Request $request){
      $agenda = Agenda::where('id', $request->agenda)->first();

      $servicio = Auditoria::where('id', $request->servicio)->first();
      $servicio->anomalia_id = $request->anomalia;
      $servicio->lectura = $request->lectura;
      $servicio->observacion_rapida = $request->observacion;
      $servicio->observacion_analisis = $request->observacion_analisis;
      $servicio->estado = 3;
      $servicio->save();

      return redirect()->route('agenda.detalle', ['agenda' => $request->agenda]);
    }


    public function deleteServicio($agenda, $servicio){
      $agenda = Agenda::where('id', $agenda)->first();

      Auditoria::where('id', $servicio)->where('estado', 1)->delete();

      return redirect()->route('agenda.detalle', ['id' => $agenda]);
    }

    public function deleteServicioPorSeleccion(Request $request){
        $arrayIdAvisos = null;
        if ($request->has('avisos')) {
            $arrayIdAvisos = $request->get('avisos');
        }
        $agenda_id = $request->agenda_id;

        if($arrayIdAvisos != null){
          $agenda = Agenda::where('id', $agenda_id)->first();
          Auditoria::whereIn('id', $arrayIdAvisos)->where('estado', 1)->delete();
        }
        return redirect()->route('agenda.detalle', ['id' => $agenda_id]);
    }

    public function visitaMapa() {
        $usuarios = Usuarios::orderBy('nombre')->get();
        return view('geo.mapas', [
            'usuarios' => $usuarios
        ]);
    }

    public function getPointMapVisita(Request $request){
        $agendas = Agenda::where('fecha', 'LIKE', DB::raw("'%$request->fecha%'"))->get();
        $arrayAgendas = [];
        $count = 0;
        $stringIn = '';
        foreach ($agendas as $agenda) {
            $arrayAgendas[] = $agenda->id;
            if($count == 0){
                $stringIn = $agenda->id;
                $count++;
            } else {
                $stringIn .= ',' . $agenda->id;
            }
        }

        $puntos = [];
        if(count($arrayAgendas) > 0){
          $puntos = Auditoria::whereIn('agenda_id', $arrayAgendas)
              ->where('lector_id', $request->gestor_id)
              ->where('estado', '>', 1)
              ->where('latitud', '!=', '0.0')
              ->orderBy('orden_realizado')->get();
        }

        return response()->json([
            'puntos' => $puntos
        ]);
    }

    public function subirMultifamiliares(Request $request)
    {
        if(isset($request->file)){
          $archivo = $request->file;
          $results = Excel::load($archivo)->all()->toArray();
          foreach ($results as $row) {
              foreach ($row as $x => $x_value) {
                  $base = [];
                  $count = 0;
                  foreach ($x_value as $y => $y_value) {
                      $base[$count] = $y_value;
                      $count++;
                  }
                  $servicio = new MultifamiliarSuministros();
                  $servicio->nif = $base[0];
                  $servicio->cantidad = $base[1];
                  $servicio->save();
              }
          }
          return view('mfl.upload', array(
                                        'success' => true,
                                        'mensaje' => 'Los Multifamiliares se cargaron'));
        }else{
          return view('mfl.upload');
        }
    }
    public function consultaServicios(Request $request)
    {
        if(isset($request->medidor_filtro) || isset($request->nic_filtro)){
          $servicios = [];
          if(isset($request->nic_filtro)){
            $auditorias = Auditoria::where('nic',$request->nic_filtro)->orWhere('medidor',$request->medidor_filtro)->get();
            foreach ($auditorias as $aud) {
              $fechaC = new Carbon($aud->fecha_recibido);
              $anomalia = '';
              if(isset($aud->anomalia->nombre)){
                $anomalia = $aud->anomalia->nombre;
              }
              $filename = $aud->id . ".png";
              $path = config('myconfig.public_fotos_auditoria')  . $filename;
              array_push($servicios, (object) array(
                                          'fecha' => $fechaC->format('d/m/Y'),
                                          'nicct' => $aud->nic,
                                          'medidor' => $aud->medidor,
                                          'anomalia' => $anomalia,
                                          'lectura' => $aud->lectura,
                                          'lector' => $aud->usuario->nombre,
                                          'path' => $path,
                                        ));
            }
          }
          $serviciosCollection = new Collection($servicios);
          $servicios = $serviciosCollection->sortBy('fecha');
          return view('agenda.consulta', array(
                                          'nic_filtro' => $request->nic_filtro,
                                          'medidor_filtro' => $request->medidor_filtro,
                                          'servicios' => $servicios,
          ));
        }else{
          return view('agenda.consulta', array(
                                          'nic_filtro' => '',
                                          'medidor_filtro' => '',
                                          'servicios' => [],
          ));
        }
    }
}
