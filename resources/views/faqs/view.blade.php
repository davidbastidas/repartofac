@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 grid-margin">
            <div class="card">
                <form action="{{route('faqs.save')}}" method="POST">
                    {{csrf_field()}}
                    <input type="hidden" name="usuario" value="{{$usuario->id}}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <center><h4>NUEVO RECLAMO</h4></center>
                            </div>
                        </div>
                        <br>

                        <div class="row">
                            <div class="col-md-3">
                              <label style="font-weight: bold;">Reclamo de:</label><br>
                              {{$usuario->reclamo}}
                            </div>
                            <div class="col-md-3">
                              <label style="font-weight: bold;">Tipo de Reclamo</label><br>
                              {{$usuario->tipo}}
                            </div>
                            <div class="col-md-3">
                              <label style="font-weight: bold;">NIC</label><br>
                              {{$usuario->nic}}
                            </div>
                            <div class="col-md-3">
                              <label style="font-weight: bold;">USUARIO GESTOR</label><br>
                              {{$usuario->user->name}}
                            </div>
                        </div>

                        <br>
                        <div class="row">
                            <div class="col-md-3">
                              <label style="font-weight: bold;">Email Usuario</label><br>
                              {{$usuario->email_usuario}}
                            </div>
                            <div class="col-md-3">
                              <label style="font-weight: bold;">Celular Usuario</label><br>
                              {{$usuario->celular_usuaio}}
                            </div>
                            <div class="col-md-6">
                              <label style="font-weight: bold;">Comentarios</label><br>
                              {{$usuario->observacion}}
                            </div>
                        </div>

                          <br>
                          <div class="row">
                              <div class="col-md-6">
                                <label style="font-weight: bold;">Respuesta</label>
                                @if(\Illuminate\Support\Facades\Auth::user()->puesto == 'admin')
                                  @if ($usuario->estado == 1)
                                    <textarea name="respuesta" rows="8" cols="80" class="form-control"></textarea>
                                  @elseif ($usuario->estado == 2)
                                    <br>{{$usuario->respuesta}}
                                  @endif
                                @else
                                  <br>{{$usuario->respuesta}}
                                @endif
                              </div>
                          </div>
                        @if(\Illuminate\Support\Facades\Auth::user()->puesto == 'admin')
                          <br>
                          <div class="row">
                              <div class="col-md-2">
                                @if ($usuario->estado == 1)
                                  <button style="margin-bottom: 8px" class="btn btn-outline-primary" type="submit">
                                      Guardar Respuesta <i class="mdi mdi-content-save"></i>
                                  </button>
                                @endif

                              </div>
                          </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
