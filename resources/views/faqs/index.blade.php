@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-10">
                            <h4>Lista de Reclamos</h4>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-9">
                            <form class="form-inline" action="{{route('faqs')}}"
                                  method="get">
                              <label class="sr-only">NIC</label>
                              <input type="text" class="form-control mb-2 mr-sm-2" name="nombre_filtro" placeholder="NIC"
                                     value="{{$nombre_filtro}}">

                                <button class="btn btn-success mb-2" type="submit">BUSCAR</button>
                            </form>
                        </div>
                        @if(\Illuminate\Support\Facades\Auth::user()->tipo_usuario == 3)
                          <div class="col-md-3">
                              <a href="{{route('faqs.new')}}" class="btn btn-success mb-2">NUEVO RECLAMO</a>
                          </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table style="width: 100%;text-align:center;font-size: 12px;" class="table-bordered">
                                    <thead>
                                    <tr>
                                        <th style="width: 10%;">#</th>
                                        <th style="width: 20%;">NIC</th>
                                        <th style="width: 15%;">Reclamo</th>
                                        <th style="width: 10%;">Tipo</th>
                                        <th style="width: 10%;">Usuario</th>
                                        <th style="width: 10%;">Estado</th>
                                        <th style="width: 10%;">Accion</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($usuarios as $usuario)
                                        <tr>
                                            <td>{{ $usuario->id }}</td>
                                            <td>{{ $usuario->nic }}</td>
                                            <td>{{ $usuario->reclamo }}</td>
                                            <td>{{ $usuario->tipo }}</td>
                                            <td>Email: {{ $usuario->email_usuario }} - CEL:{{ $usuario->celular_usuaio }}</td>
                                            <td>
                                              @if ($usuario->estado == 1)
                                                EN SOLICITUD
                                              @elseif ($usuario->estado == 2)
                                                RESUELTO
                                              @endif
                                            </td>
                                            <td>
                                                <form action="{{route('faqs.view', ['usuario' => $usuario->id])}}">
                                                    <button style="margin-bottom: 8px"
                                                            class="btn-sm btn btn-outline-primary">
                                                        Ver <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <br>
                                {{ $usuarios->appends([])->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
