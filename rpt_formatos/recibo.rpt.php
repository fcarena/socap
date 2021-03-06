<?php
/**
 * @since 31/03/2008
 * @author Balam Gonzalez Luis Humberto
 * @version 1.0.1
 *  01/Abril/2008
 * 		- cambios en la fecha
 * 		- Agregar Documento de Destino
 */
//====================================================================================================
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
$iduser = $_SESSION["log_id"];
//=====================================================================================================
$xHP		= new cHPage("", HP_RECIBO);
$recibo		= parametro("idrecibo", 0, MQL_INT); $recibo		= parametro("r", $recibo, MQL_INT); $recibo		= parametro("recibo", $recibo, MQL_INT);
$formato	= parametro("forma", 400, MQL_INT);
$sinTes		= parametro("notesoreria", false, MQL_BOOL);

//Agregado para hacer Backups

$senders		= getEmails($_REQUEST);
//end add




$xFMT		= new cHFormatoRecibo($recibo, $formato);
$xFMT->setIgnorarTesoreria($sinTes);
$items		= setNoMenorQueCero(RECIBOS_POR_HOJA);

$txt		= $xFMT->render();
if($xFMT->isInit() === false){
	$xHP->goToPageError(2011);	
} else {
	if(count($senders) >= 1){
		$xOH	= new cHObject();
		
		$html	= $xHP->getHeader() . $txt . "</body></html>";
		$title	= $xOH->getTitulize("RECIBO-$recibo");
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_paper("letter", "portrait" );
		$dompdf->render();
		
		$archivo	= PATH_TMP . "" . $title . ".pdf";
		$output 		= $dompdf->output();
		file_put_contents($archivo, $output);
		$output			= null;
		$body			= "RESPALDO DEL RECIBO $recibo";
		$xMail		= new cNotificaciones();
		foreach ($senders as $idmail => $email){
			$xMail->sendMail($title, $body, $email, array( "path" => $archivo ));
		}
		
		
		$rs		= array("message"  => $xMail->getMessages());
		$cnt	= json_encode($rs);
		
		//HEADERS JSON
		header('Content-type: application/json');
		echo $cnt;
	} else {

		$xHP->init();
		if($items>1){
			$xFMT	= new cHFormatoRecibo($recibo, $formato);
			$xFMT->setIgnorarTesoreria($sinTes);
			$txtNP	= $xFMT->render(false);
			for($i = 1; $i <= $items; $i++){
				if($i == $items){
					echo $txt;
				} else {
					echo $txtNP;
				}		
			}
		} else {
			echo $txt;	
		}
		$xHP->fin();
	}
}

?>