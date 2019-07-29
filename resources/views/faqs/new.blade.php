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
                              <label>Reclamo de:</label>
                              <select class="form-control" name="reclamo" class="form-control">
                                  <option value="">Selecciona..</option>
                                  <option value="LECTURA">LECTURA</option>
                                  <option value="REPARTO">REPARTO</option>
                              </select>
                            </div>
                            <div class="col-md-3">
                              <label>Tipo de Reclamo</label>
                              <select class="form-control" name="tipo" class="form-control">
                                  <option value="">Selecciona..</option>
                                  <option value="ERROR DE LECTURA">ERROR DE LECTURA</option>
                                  <option value="FACTURACION NO DISTRIBUIDA">FACTURACION NO DISTRIBUIDA</option>
                                  <option value="EXCESO DE CONSUMO">EXCESO DE CONSUMO</option>
                                  <option value="INMUEBLE DESOCUPADO">INMUEBLE DESOCUPADO</option>
                                  <option value="CONSUMO PROMEDIO CON LECTURA">CONSUMO PROMEDIO CON LECTURA</option>
                                  <option value="NO LECTURA">NO LECTURA</option>
                              </select>
                            </div>
                            <div class="col-md-3">
                              <label>NIC</label>
                              <input type="text" name="nic" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                              <label>Email Usuario</label>
                              <input type="text" name="email_usuario" class="form-control">
                            </div>
                            <div class="col-md-3">
                              <label>Celular Usuario</label>
                              <input type="text" name="celular_usuaio" class="form-control">
                            </div>
                            <div class="col-md-6">
                              <label>Comentarios</label>
                              <textarea name="observacion" rows="8" cols="80" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <button style="margin-bottom: 8px"
                                        class="btn-block btn btn-outline-primary" type="submit">
                                    Guardar <i class="mdi mdi-content-save"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
