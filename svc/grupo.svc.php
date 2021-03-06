<?php
//=====================================================================================================
//=====>	INICIO_H
	include_once("../core/go.login.inc.php");
	include_once("../core/core.error.inc.php");
	include_once("../core/core.html.inc.php");
	include_once("../core/core.init.inc.php");
	$theFile					= __FILE__;
	$permiso					= getSIPAKALPermissions($theFile);
	if($permiso === false){		header ("location:../404.php?i=999");	}
	$_SESSION["current_file"]	= addslashes( $theFile );
//<=====	FIN_H
//=====================================================================================================
$xInit      = new cHPage("", HP_SERVICE );
$xLi		= new cSQLListas();

$txt		= "";
$persona	= parametro("persona", 0, MQL_INT);// $nombre	= parametro("n", $nombre);
$grupo		= parametro("grupo", 0, MQL_INT);// $nombre	= parametro("n", $nombre);
$nombre		= parametro("nombre"); $nombre	= parametro("n", $nombre);


$action		= "LIST";
//$ByPersona	= ($persona == DEFAULT_SOCIO) ? "`idsocios_grupossolidarios` != " . DEFAULT_GRUPO . "" : "codigo = $persona ";
$ByPersona	= (setNoMenorQueCero($persona) > 0) ? " AND (`socios_grupossolidarios`.`clave_de_persona`=$persona) " : "";
$ByNombre	= "";//($nombre != "") ? " AND (`nombre_gruposolidario` LIKE '%$nombre%' OR `representante_nombrecompleto` LIKE '%$nombre%' OR `vocalvigilancia_nombrecompleto` LIKE '%$nombre%') " : "";

$rs			= array("error" => true);

if(setNoMenorQueCero($grupo) > 0 ){
	$xGpo		= new cGrupo($grupo);

	if($xGpo->init() == true){
		$rs["descripcion"]		= $xGpo->getDescripcion();
		$rs["error"] 			= false;
	}
}
header('Content-type: application/json');
echo json_encode($rs);
?>