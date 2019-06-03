<?php

namespace App\Http\Controllers;

use App\Auditoria;
use App\Pci;
use App\Agenda;
use App\LecturasPci;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class DownloadController extends Controller
{
    public $servicios = null;


    public function download(Request $request)
    {
        $agenda = $request->agenda;
        $agendaM = Agenda::where('id', $agenda)->first();
        $servicios = null;
        $nombreArchivo = 'Reparto';
        $model = new Auditoria();
        $servicios = $model->hydrate(
            DB::select(
                "call download_auditorias($agenda)"
            )
        );

        $this->servicios = $servicios;

        Excel::create($nombreArchivo, function ($excel) {

            $servicios = $this->servicios;

            $excel->sheet('Reparto', function ($sheet) use ($servicios) {

                $sheet->fromArray($servicios);

            });

        })->export('xlsx');
    }
}
