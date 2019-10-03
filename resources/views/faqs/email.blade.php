<style>
body{
	font-family: Arial, Helvetica, sans-serif;
}
</style>
<h4>{{ $asunto }}</h4>
DE: {{ $from }} <br>
NIC: {{ $nic }} <br>
DATOS DEL CLIENTE: {{ $usuario }} <br>
COMENTARIO: {{ $comentario }}
@isset($respuesta)
<br><br><br><b>RESPUESTA:</b> {{ $respuesta }}
@endisset