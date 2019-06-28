<?php

namespace App\Http\Controllers;

use App\Auditoria;
use App\Pci;
use App\Anomalias;
use App\ObservacionesRapidas;
use App\Usuarios;
use App\Log;
use App\Agenda;
use App\LecturasPci;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
  public function login(Request $request){
    $response = null;
    $usuarios = Usuarios::where('nickname', '=', $request->user)->where('contrasena', '=', $request->password)->first();
    if(isset($usuarios->id)){
      $response = array(
        'estado' => true,
        'nombre' => $usuarios->nombre,
        'nickname' => $usuarios->nickname,
        'tipo' => $usuarios->tipo_id,
        'fk_delegacion' => 1,
        'fk_id' => $usuarios->id
      );
    } else {
      $response = array(
        'estado' => false
      );
    }

    return $response;
  }
  public function getServicios(Request $request)
  {
    $arrayAuditorias = [];
    $arrayAnomalias = [];
    $arrayPci = [];
    $arrayObservacionesRapidas = [];
    $arrayFINAL = [];
    $collection = null;

    $fechaHoy = Carbon::now()->format('Y-m-d');
    $auditorias = Auditoria::select('agenda_id')
                    ->where('lector_id', '=', $request->user)
                    ->where('estado', '=', '1')
                    ->groupBy('agenda_id')->get();
    $agendas = Agenda::where('fecha', '<=', "'$fechaHoy'");
    foreach ($auditorias as $auditoria) {
      $agendas = $agendas->orWhere('id', $auditoria->agenda_id);
    }
    $agendas = $agendas->get();

    $arrayIn = array();
    foreach ($agendas as $agenda) {
      $arrayIn[] = $agenda->id;
    }

    $auditorias = Auditoria::where('lector_id', '=', $request->user)
                    ->where('estado', '=', '1')
                    ->whereIn('agenda_id', $arrayIn)->get();
    foreach ($auditorias as $auditoria) {
      array_push($arrayAuditorias, (object) array(
        'id' => $auditoria->id,
        'barrio' => $auditoria->barrio,
        'localidad' => $auditoria->localidad,
        'cliente' => $auditoria->cliente,
        'direccion' => $auditoria->direccion,
        'nic' => $auditoria->nic,
        'nis' => $auditoria->nis,
        'nif' => $auditoria->nif,
        'ruta' => $auditoria->ruta,
        'itin' => $auditoria->itin,
        'medidor' => $auditoria->medidor,
        'paquete' => $auditoria->paquete,
        'pide_foto' => $auditoria->pide_foto,
        'pide_gps' => $auditoria->pide_gps
      ));
    }

    $anomalias = Anomalias::all();
    foreach ($anomalias as $anomalia) {
      array_push($arrayAnomalias, (object) array(
        'id' => $anomalia->id,
        'nombre' => $anomalia->nombre,
        'codigo' => $anomalia->codigo,
        'lectura' => $anomalia->lectura,
        'foto' => $anomalia->foto,
        'orden' => $anomalia->orden
      ));
    }

    $observaciones = ObservacionesRapidas::all();
    foreach ($observaciones as $observacion) {
      array_push($arrayObservacionesRapidas, (object) array(
        'id' => $observacion->id,
        'nombre' => $observacion->nombre
      ));
    }

    array_push($arrayFINAL, (object) array(
      'estado' => true,
      'anomalias' => $arrayAnomalias,
      'observaciones_rapidas' => $arrayObservacionesRapidas,
      'auditorias' => $arrayAuditorias
    ));
    $collection = new Collection($arrayFINAL);
    return $collection;
  }

  public function actualizarAuditoria(Request $request)
  {
    $response = null;
    if($request->user){
      $auditoria = Auditoria::where('id', '=', $request->id)->where('estado', '=', '1')->first();
      if(isset($auditoria->id)){
        try {
          if($request->anomalia == 0){
            $request->anomalia = null;
          }
          if($request->observacion_rapida == 0){
            $request->observacion_rapida = null;
          }
          $auditoria->anomalia_id = $request->anomalia;
          $auditoria->observacion_rapida = $request->observacion_rapida;
          $auditoria->lectura = $request->lectura;
          $auditoria->observacion_analisis = $request->observacion_analisis;
          $auditoria->latitud = $request->latitud;
          $auditoria->longitud = $request->longitud;
          $auditoria->fecha_recibido = $request->fecha_realizado;
          $auditoria->fecha_recibido_servidor = Carbon::now();
          $auditoria->estado = 2;
          $auditoria->orden_realizado = $request->orden_realizado;
          $auditoria->nombre_recibe = $request->nombre_recibe;
          $auditoria->telefono_recibe = $request->telefono_recibe;
          $auditoria->cantidad_recibe = $request->cantidad_recibe;
          $auditoria->foto_mf = $auditoria->id . '.png';

          $auditoria->save();

          $multifamiliares = Auditoria::where('nif', $auditoria->nif)
                                        ->where('agenda_id', $auditoria->agenda_id)
                                        ->where('estado', '=', '1')->get();
          foreach ($multifamiliares as $mf) {
            $mf->anomalia_id = $request->anomalia;
            $mf->observacion_rapida = $request->observacion_rapida;
            $mf->lectura = $request->lectura;
            $mf->observacion_analisis = $request->observacion_analisis;
            $mf->latitud = $request->latitud;
            $mf->longitud = $request->longitud;
            $mf->fecha_recibido = $request->fecha_realizado;
            $mf->fecha_recibido_servidor = Carbon::now();
            $mf->estado = 2;
            $mf->orden_realizado = $request->orden_realizado;
            $mf->nombre_recibe = $request->nombre_recibe;
            $mf->telefono_recibe = $request->telefono_recibe;
            $mf->cantidad_recibe = $request->cantidad_recibe;
            $mf->foto_mf = $auditoria->foto_mf;
            $mf->save();
          }
          /*
          $logSeg = new Log();
          $logSeg->log = '' . $request;
          $logSeg->servicio_id = $auditoria->id;
          $logSeg->save();}*/

          if($request->foto != null || $request->foto != ""){
            //decode base64 string
            $image = base64_decode($request->foto);

            $archivo = $auditoria->id . '.png';
            \File::put(config('myconfig.ruta_fotos_auditoria') . $archivo, $image);
          }
          $response = array(
            'estado' => true
          );
        } catch (\Exception $e) {
          $logSeg = new Log();
          $logSeg->log = '' . $e;
          $logSeg->servicio_id = $auditoria->id;
          $logSeg->save();
          $response = array(
            'estado' => false
          );
        }
      } else {
        $response = array(
          'estado' => true
        );
      }
    } else {
      $response = array(
        'estado' => false
      );
    }

    return $response;
  }

  public function insertarRepartoAleatorio(Request $request){
    $response = null;
    if($request->user){
      if($request->anomalia == 0){
        $request->anomalia = null;
      }
      if($request->observacion_rapida == 0){
        $request->observacion_rapida = null;
      }
      $auditoria = new Auditoria();

      try {
        $agenda = Auditoria::where('lector_id', $request->user)->where('agenda_id', '>', 0)->orderByDesc('id')->first();
        $auditoria->barrio = "";
        $auditoria->localidad = "";
        $auditoria->cliente = "";
        $auditoria->direccion = "";
        $auditoria->nis = 0;
        $auditoria->nif = 0;
        $auditoria->ruta = 0;
        $auditoria->itin = 0;
        $auditoria->unicom = 0;
        $auditoria->medidor = "";
        $auditoria->paquete = "";
        $auditoria->fecha_emision = Carbon::now()->format('Y-m-d');
        $auditoria->lector = $request->user;
        $auditoria->lector_id = $request->user;
        $auditoria->agenda_id = $agenda->agenda_id;

        $auditoria->nic = $request->nic;
        $auditoria->anomalia_id = $request->anomalia;
        $auditoria->observacion_rapida = $request->observacion_rapida;
        $auditoria->lectura = $request->lectura;
        $auditoria->observacion_analisis = $request->observacion_analisis;
        $auditoria->latitud = $request->latitud;
        $auditoria->longitud = $request->longitud;
        $auditoria->fecha_recibido = $request->fecha_realizado;
        $auditoria->fecha_recibido_servidor = Carbon::now();
        $auditoria->estado = 2;
        $auditoria->orden_realizado = $request->orden_realizado;
        $auditoria->nombre_recibe = $request->nombre_recibe;
        $auditoria->telefono_recibe = $request->telefono_recibe;
        $auditoria->cantidad_recibe = $request->cantidad_recibe;

        $auditoria->save();

        /*
        $logSeg = new Log();
        $logSeg->log = '' . $request;
        $logSeg->servicio_id = $auditoria->id;
        $logSeg->save();}*/

        if($request->foto != null || $request->foto != ""){
          //decode base64 string
          $image = base64_decode($request->foto);

          $archivo = $auditoria->id . '.png';
          \File::put(config('myconfig.ruta_fotos_auditoria') . $archivo, $image);
        }
        $response = array(
          'estado' => true
        );
      } catch (\Exception $e) {
        $logSeg = new Log();
        $logSeg->log = '' . $e;
        $logSeg->servicio_id = 0;
        $logSeg->save();
        $response = array(
          'estado' => false
        );
      }
    } else {
      $response = array(
        'estado' => false
      );
    }

    return $response;
  }
}
