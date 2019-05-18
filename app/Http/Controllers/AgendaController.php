<?php

namespace App\Http\Controllers;

use App\AdminTable;
use App\Agenda;
use App\Anomalias;
use App\Auditoria;
use App\AuditoriaTemp;
use App\Pci;
use App\PciTemp;
use App\TipoLectura;
use App\ObservacionesRapidas;
use App\User;
use App\Usuarios;
use App\LecturasPci;
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
        $tiposLectura = TipoLectura::all();

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

            $pendientes = 0;
            $realizados = 0;
            $cargasPendientes = 0;
            if($agenda->tipo_lectura_id == 1){
              $pendientes = Auditoria::where('estado', 1)->where('agenda_id', $agenda->id)->count();
              $realizados = Auditoria::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
              $cargasPendientes = AuditoriaTemp::where('agenda_id', $agenda->id)->count();
            } elseif($agenda->tipo_lectura_id == 2){
              $pendientes = Pci::where('estado', 1)->where('agenda_id', $agenda->id)->count();
              $realizados = Pci::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
              $cargasPendientes = PciTemp::where('agenda_id', $agenda->id)->count();
            }

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
                'tipo_lectura_id' => $agenda->tipo_lectura_id,
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
          'tiposLectura' => $tiposLectura,
          'agendas' => $posts
        ])->withPosts($posts);
    }

    public function saveAgenda(Request $request)
    {

        $agenda = new Agenda();
        $agenda->fecha = $request->fecha;
        $agenda->tipo_lectura_id = $request->delegacion;
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
                if($agenda->tipo_lectura_id == 1){
                  $servicio = new AuditoriaTemp();
                  $servicio->barrio = $base[1];
                  $servicio->localidad = $base[2];
                  $servicio->cliente = $base[3];
                  $servicio->direccion = $base[4];
                  $servicio->nic = $base[5];
                  $servicio->ruta = $base[6];
                  $servicio->itin = $base[7];
                  $servicio->medidor = $base[8];
                  $servicio->motivo = $base[9];
                  $servicio->nis = $base[10];
                  $servicio->lector = $base[11];
                  $servicio->an_anterior = $base[12];
                  $servicio->lectura_anterior = $base[13];
                  $servicio->pide_foto = $base[14];
                  $servicio->pide_gps = $base[15];
                  $servicio->admin_id = Auth::user()->id;
                  $servicio->agenda_id = $agenda->id;
                  $servicio->save();
                }elseif($agenda->tipo_lectura_id == 2){
                  $servicio = new PciTemp();
                  $servicio->ct = $base[1];
                  $servicio->mt = $base[2];
                  $servicio->direccion = $base[3];
                  $servicio->medidor = $base[4];
                  $servicio->medidor_anterior = $base[5];
                  $servicio->medidor_posterior = $base[6];
                  $servicio->barrio = $base[7];
                  $servicio->municipio = $base[8];
                  $servicio->codigo = $base[9];
                  $servicio->unicom = $base[10];
                  $servicio->ruta = $base[11];
                  $servicio->itin = $base[12];
                  $servicio->lector = $base[13];
                  $servicio->an_anterior = $base[14];
                  $servicio->lectura_anterior = $base[15];
                  $servicio->fecha_entrega = $base[16]->format('Y-m-d');
                  $servicio->pide_foto = $base[17];
                  $servicio->pide_gps = $base[18];
                  $servicio->admin_id = Auth::user()->id;
                  $servicio->agenda_id = $agenda->id;
                  $servicio->save();
                }
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
        if($agendaModel->tipo_lectura_id == 1){
          $lectores = AuditoriaTemp::select('lector')->where('agenda_id', $agenda)->groupBy('lector')->get();
        }elseif($agendaModel->tipo_lectura_id == 2){
          $lectores = PciTemp::select('lector')->where('agenda_id', $agenda)->groupBy('lector')->get();
        }
        $usuarios = Usuarios::all();

        $perPage = 150;
        $page = Input::get('page');
        $pageName = 'page';
        $page = Paginator::resolveCurrentPage($pageName);
        $offSet = ($page * $perPage) - $perPage;

        $servicios = null;
        $serviciosAux1 = null;
        $serviciosAux2 = null;
        if($agendaModel->tipo_lectura_id == 1){
          $servicios = Auditoria::where('agenda_id', $agenda);
          $serviciosAux1 = Auditoria::where('agenda_id', $agenda);
          $serviciosAux2 = Auditoria::where('agenda_id', $agenda);
        }elseif($agendaModel->tipo_lectura_id == 2){
          $servicios = Pci::where('agenda_id', $agenda);
          $serviciosAux1 = Pci::where('agenda_id', $agenda);
          $serviciosAux2 = Pci::where('agenda_id', $agenda);
        }

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

        $lectoresAsignados = null;
        if($agendaModel->tipo_lectura_id == 1){
          $lectoresAsignados = Auditoria::select('lector_id')->where('agenda_id', $agenda)->groupBy('lector_id')->get();
        }elseif($agendaModel->tipo_lectura_id == 2){
          $lectoresAsignados = Pci::select('lector_id')->where('agenda_id', $agenda)->groupBy('lector_id')->get();
        }
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

        $servicios = null;
        if($agenda->tipo_lectura_id == 1){
          $servicios = AuditoriaTemp::where('lector', $lector)->where('agenda_id', $agenda->id)->get();
        }elseif($agenda->tipo_lectura_id == 2){
          $servicios = PciTemp::where('lector', $lector)->where('agenda_id', $agenda->id)->get();
        }

        foreach ($servicios as $servicio) {
          $serv = null;
          if($agenda->tipo_lectura_id == 1){
            $serv = new Auditoria();
            $serv->barrio = $servicio->barrio;
            $serv->localidad = $servicio->localidad;
            $serv->cliente = $servicio->cliente;
            $serv->direccion = $servicio->direccion;
            $serv->nic = $servicio->nic;
            $serv->ruta = $servicio->ruta;
            $serv->itin = $servicio->itin;
            $serv->medidor = $servicio->medidor;
            $serv->motivo = $servicio->motivo;
            $serv->nis = $servicio->nis;
            $serv->lector = $servicio->lector;
            $serv->an_anterior = $servicio->an_anterior;
            $serv->lectura_anterior = $servicio->lectura_anterior;
            $serv->pide_foto = $servicio->pide_foto;
            $serv->pide_gps = $servicio->pide_gps;
            $serv->orden_realizado = 0;
            $serv->estado = 1;
            $serv->lector_id = $user;
            $serv->admin_id = Auth::user()->id;
            $serv->agenda_id = $agenda->id;
          }elseif($agenda->tipo_lectura_id == 2){
            $serv = new Pci();
            $serv->ct = $servicio->ct;
            $serv->mt = $servicio->mt;
            $serv->direccion = $servicio->direccion;
            $serv->medidor = $servicio->medidor;
            $serv->medidor_anterior = $servicio->medidor_anterior;
            $serv->medidor_posterior = $servicio->medidor_posterior;
            $serv->barrio = $servicio->barrio;
            $serv->municipio = $servicio->municipio;
            $serv->codigo = $servicio->codigo;
            $serv->an_anterior = $servicio->an_anterior;
            $serv->lectura_anterior = $servicio->lectura_anterior;
            $serv->unicom = $servicio->unicom;
            $serv->ruta = $servicio->ruta;
            $serv->itin = $servicio->itin;
            $serv->fecha_entrega = $servicio->fecha_entrega;
            $serv->pide_foto = $servicio->pide_foto;
            $serv->pide_gps = $servicio->pide_gps;
            $serv->lector = $servicio->lector;
            $serv->orden_realizado = 0;
            $serv->estado = 1;
            $serv->lector_id = $user;
            $serv->admin_id = Auth::user()->id;
            $serv->agenda_id = $agenda->id;
          }
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
        $lectoresTemp = null;
        if($agenda->tipo_lectura_id == 1){
          $lectoresTemp = AuditoriaTemp::select('lector')->where('agenda_id', $agenda->id)->groupBy('lector')->get();
        }elseif($agenda->tipo_lectura_id == 2){
          $lectoresTemp = PciTemp::select('lector')->where('agenda_id', $agenda->id)->groupBy('lector')->get();
        }

        foreach ($lectoresTemp as $ges) {
            $gestor = explode(" ", $ges->lector);
            $cedula = trim($gestor[0]);

            $serviciosTemp = null;
            if($agenda->tipo_lectura_id == 1){
              $serviciosTemp = AuditoriaTemp::where('lector', $ges->lector)->where('agenda_id', $agenda->id)->get();
            }elseif($agenda->tipo_lectura_id == 2){
              $serviciosTemp = PciTemp::where('lector', $ges->lector)->where('agenda_id', $agenda->id)->get();
            }

            $usuario = Usuarios::where('nickname', $cedula)->first();
            foreach ($serviciosTemp as $servicio) {
              $serv = null;
              if($agenda->tipo_lectura_id == 1){
                $serv = new Auditoria();
                $serv->barrio = $servicio->barrio;
                $serv->localidad = $servicio->localidad;
                $serv->cliente = $servicio->cliente;
                $serv->direccion = $servicio->direccion;
                $serv->nic = $servicio->nic;
                $serv->ruta = $servicio->ruta;
                $serv->itin = $servicio->itin;
                $serv->medidor = $servicio->medidor;
                $serv->motivo = $servicio->motivo;
                $serv->nis = $servicio->nis;
                $serv->lector = $servicio->lector;
                $serv->an_anterior = $servicio->an_anterior;
                $serv->lectura_anterior = $servicio->lectura_anterior;
                $serv->pide_foto = $servicio->pide_foto;
                $serv->pide_gps = $servicio->pide_gps;
                $serv->orden_realizado = 0;
                $serv->estado = 1;
                $serv->lector_id = $usuario->id;
                $serv->admin_id = Auth::user()->id;
                $serv->agenda_id = $agenda->id;
              }elseif($agenda->tipo_lectura_id == 2){
                $serv = new Pci();
                $serv->ct = $servicio->ct;
                $serv->mt = $servicio->mt;
                $serv->direccion = $servicio->direccion;
                $serv->medidor = $servicio->medidor;
                $serv->medidor_anterior = $servicio->medidor_anterior;
                $serv->medidor_posterior = $servicio->medidor_posterior;
                $serv->barrio = $servicio->barrio;
                $serv->municipio = $servicio->municipio;
                $serv->codigo = $servicio->codigo;
                $serv->an_anterior = $servicio->an_anterior;
                $serv->lectura_anterior = $servicio->lectura_anterior;
                $serv->unicom = $servicio->unicom;
                $serv->ruta = $servicio->ruta;
                $serv->itin = $servicio->itin;
                $serv->fecha_entrega = $servicio->fecha_entrega;
                $serv->pide_foto = $servicio->pide_foto;
                $serv->pide_gps = $servicio->pide_gps;
                $serv->lector = $servicio->lector;
                $serv->orden_realizado = 0;
                $serv->estado = 1;
                $serv->lector_id = $usuario->id;
                $serv->admin_id = Auth::user()->id;
                $serv->agenda_id = $agenda->id;
              }

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
        if($agenda->tipo_lectura_id == 1){
          AuditoriaTemp::where('admin_id', $id)->where('agenda_id', $request->agenda)->delete();
        }elseif($agenda->tipo_lectura_id == 2){
          PciTemp::where('admin_id', $id)->where('agenda_id', $request->agenda)->delete();
        }

        return redirect()->route('agenda.detalle', ['agenda' => $agenda->id]);
    }

    public function deleteAgenda($agenda)
    {
        $agenda = Agenda::where('id', $agenda)->first();

        $pendientes = 0;
        $realizados = 0;
        $cargasPendientes = 0;
        if($agenda->tipo_lectura_id == 1){
          $pendientes = Auditoria::where('estado', 1)->where('agenda_id', $agenda->id)->count();
          $realizados = Auditoria::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
          $cargasPendientes = AuditoriaTemp::where('agenda_id', $agenda->id)->count();
        } elseif($agenda->tipo_lectura_id == 2){
          $pendientes = Pci::where('estado', 1)->where('agenda_id', $agenda->id)->count();
          $realizados = Pci::where('estado', '>', 1)->where('agenda_id', $agenda->id)->count();
          $cargasPendientes = PciTemp::where('agenda_id', $agenda->id)->count();
        }

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
      if($agenda->tipo_lectura_id == 1){
        $servicio = Auditoria::where('id', $servicio_id)->first();
        $filename = $servicio->id . ".png";
        $path = config('myconfig.public_fotos_auditoria')  . $filename;
        $view = 'agenda.editar_auditoria';
      } elseif($agenda->tipo_lectura_id == 2){
        $servicio = Pci::where('id', $servicio_id)->first();
        $filename = $servicio->id . ".png";
        $path = config('myconfig.public_fotos_pci')  . $filename;
        $view = 'agenda.editar_pci';
        $historiaLecturas = LecturasPci::where('pci', $servicio->medidor)
                                ->orderByDesc('fecha')->limit(6)->get();
        if($servicio->estado > 1){
          $ultimasLecturas = LecturasPci::where('pci', $servicio->medidor)
                                  ->where('fecha', '>=',DB::raw("'" . $servicio->fecha_recibido . "'-interval 3 month"))
                                  ->orderBy('fecha')->get();
          $critica = true;
          $lectura1 = -1;
          $lectura2 = -1;
          $consumoActual = -1;
          $consumoAnterior = -1;
          $desviacion = -1;
          if(count($ultimasLecturas) >= 2){
            $count = 1;
            foreach ($ultimasLecturas as $l) {
              if($count == 1){
                $lectura1 = $l->lectura;
                $count++;
              }else{
                $lectura2 = $l->lectura;break;
              }
            }
            $consumoActual = $servicio->lectura - $lectura1;
            $consumoAnterior = $lectura1 - $lectura2;
            $desviacion = 0;
            if($consumoAnterior > 0){
              $desviacion = ceil((($consumoAnterior - $consumoActual)/$consumoAnterior)*100) . '%';
            }
          }
          $ultimomes = LecturasPci::where('pci', $servicio->medidor)->orderByDesc('fecha')->first();
          $ultimaLectura = -1;
          $ultimaAnom = '';
          if(isset($ultimomes->id)){
            $ultimaLectura = $ultimomes->lectura;
            $ultimaAnom = $ultimomes->anomalia;
          }
          //critica
          if($servicio->lectura == 0){
            $mensajeCritica = 'LECTURA CERO';
          }else if($ultimaLectura == -1){
            $mensajeCritica = 'SIN LECTURA ANTERIOR';
          }else if($servicio->lectura > $ultimaLectura){
            $mensajeCritica = 'LECTURA OK ' . $ultimaLectura;
          }else if($servicio->lectura == $ultimaLectura){
            $mensajeCritica = 'LECTURA REPETIDA';
          }else{
            $mensajeCritica = 'LECTURA MENOR ' . $ultimaLectura;
          }
          if($servicio->anomalia_id == 45){//SIN ANOMALIA EL CODIGO EN LA TABLA
            $mensajeCritica .= '';
          }else if($servicio->anomalia->nombre == $ultimaAnom){
            $mensajeCritica .= ' - ANOMALIA IGUAL ' . $ultimaAnom;
          }else if($ultimaAnom == ''){
            $mensajeCritica .= ' - SIN ANOMALIA ANTERIOR';
          }else{
            $mensajeCritica .= ' - ANOMALIA DIFERENTE ' . $ultimaAnom;
          }
          if($desviacion == -1){
            $mensajeCritica .= ' - Desviacion SIN CALCULAR';
          }else{
            $mensajeCritica .= ' - Desviacion ' . $desviacion;
          }

        }
      }
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
      if($agenda->tipo_lectura_id == 1){
        $servicio = Auditoria::where('id', $request->servicio)->first();
        $servicio->anomalia_id = $request->anomalia;
        $servicio->lectura = $request->lectura;
        $servicio->habitado = $request->habitado;
        $servicio->visible = $request->visible;
        $servicio->observacion_rapida = $request->observacion;
        $servicio->observacion_analisis = $request->observacion_analisis;
        $servicio->estado = 3;
        $servicio->save();
      } elseif($agenda->tipo_lectura_id == 2){
        $servicio = Pci::where('id', $request->servicio)->first();
        $servicio->anomalia_id = $request->anomalia;
        $servicio->lectura = $request->lectura;
        $servicio->observacion_analisis = $request->observacion_analisis;
        $servicio->estado = 3;
        $servicio->save();
      }

      return redirect()->route('agenda.detalle', ['agenda' => $request->agenda]);
    }


    public function deleteServicio($agenda, $servicio){
      $agenda = Agenda::where('id', $agenda)->first();
      if($agenda->tipo_lectura_id == 1){
        Auditoria::where('id', $servicio)->where('estado', 1)->delete();
      } elseif($agenda->tipo_lectura_id == 2){
        Pci::where('id', $servicio)->where('estado', 1)->delete();
      }
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
          if($agenda->tipo_lectura_id == 1){
            Auditoria::whereIn('id', $arrayIdAvisos)->where('estado', 1)->delete();
          } elseif($agenda->tipo_lectura_id == 2){
            Pci::whereIn('id', $arrayIdAvisos)->where('estado', 1)->delete();
          }
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
          if($request->tipo_servicio_id == 'auditoria'){
            $puntos = Auditoria::whereIn('agenda_id', $arrayAgendas)
                ->where('lector_id', $request->gestor_id)
                ->where('estado', '>', 1)
                ->where('latitud', '!=', '0.0')
                ->orderBy('orden_realizado')->get();
          }else if($request->tipo_servicio_id == 'pci'){
            $puntos = Pci::whereIn('agenda_id', $arrayAgendas)
                ->where('lector_id', $request->gestor_id)
                ->where('estado', '>', 1)
                ->where('latitud', '!=', '0.0')
                ->orderBy('orden_realizado')->get();
          }

        }

        return response()->json([
            'puntos' => $puntos
        ]);
    }

    public function subirLecturasPci(Request $request)
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
                  $servicio = new LecturasPci();
                  $servicio->ct = $base[0];
                  $servicio->mt = $base[1];
                  $servicio->pci = $base[2];
                  $servicio->lectura = $base[3];
                  $servicio->anomalia = $base[4];
                  $servicio->fecha = $base[5]->format('Y-m-d');
                  $servicio->save();
              }
          }
          return \Redirect::route('agenda.pci.uploadlecturas', array('success' => 'Las lecturas se cargaron'));
        }else{
          return view('pci.upload_lecturas');
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
            $pci = Pci::where('medidor',$request->medidor_filtro)->get();
            foreach ($pci as $pc) {
              $fechaC = new Carbon($pc->fecha_recibido);
              $anomalia = '';
              if(isset($pc->anomalia->nombre)){
                $anomalia = $pc->anomalia->nombre;
              }
              $filename = $pc->id . ".png";
              $path = config('myconfig.public_fotos_pci')  . $filename;
              array_push($servicios, (object) array(
                                          'fecha' => $fechaC->format('d/m/Y'),
                                          'nicct' => 'CT: ' . $pc->ct . ' - MT: ' . $pc->mt,
                                          'medidor' => $pc->medidor,
                                          'anomalia' => $anomalia,
                                          'lectura' => $pc->lectura,
                                          'lector' => $pc->usuario->nombre,
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
