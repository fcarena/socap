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
	$iduser = $_SESSION["log_id"];
//=====================================================================================================

$xHP		= new cHPage("TR.RECIBO DE PAGO", HP_FORM);
$xCaja		= new cCaja();
$xEvt		= new cCreditosEventos();

if( $xCaja->getEstatus() == TESORERIA_CAJA_CERRADA ){	$xHP->goToPageError(200); }

$jxc = new TinyAjax();
/**
 * Verifica si Existe el Plan de Pagos
 *
 * @param integer $idcred
 * @return string Mensaje de Descripcion del Estatus del Credito
 */
function jsaGetVerifyPlan($idcred){
	$idcred		= setNoMenorQueCero($idcred);
	if($idcred > DEFAULT_CREDITO){
		$xCred		= new cCredito($idcred); $xCred->init();
		
		if($xCred->getPeriocidadDePago() != CREDITO_TIPO_PERIOCIDAD_FINAL_DE_PLAZO ){
			$plan		= $xCred->getNumeroDePlanDePagos();
			if($plan == false){
				return $xCred->getMessages(OUT_HTML);
			} else {
				return "";
			}
		}
	}
}
function jsaGetLetras($idcredito){
	$idcred		= setNoMenorQueCero($idcredito);
	$xF			= new cFecha();
	if($idcred > DEFAULT_CREDITO){
		$xCred		= new cCredito($idcred); 
		if($xCred->init() == true){
			if($xCred->getEsAfectable() == false OR $xCred->getSaldoActual() <= 0){
				$xCred->setRevisarSaldo();
				if(MODO_CORRECION == true){
					$xTxt		= new cHText();
					$xTxt->setDivClass("");
					return $xTxt->getNumero("idparcialidad", $xCred->getPeriodoActual()+1, "TR.Numero de Parcialidad");
				} else {
					return $xCred->getMessages();
				}			
			} else {
				if($xCred->getPeriocidadDePago() == CREDITO_TIPO_PERIOCIDAD_FINAL_DE_PLAZO ){
					//
					$xTxt		= new cHText();
					$xTxt->setDivClass("");
					return $xTxt->getNumero("idparcialidad", $xCred->getPeriodoActual()+1, "TR.Numero de Parcialidad");
				} else {
					$plan		= $xCred->getNumeroDePlanDePagos();
					if($plan != false){
						$xPlan		= new cPlanDePagos($plan); $xPlan->init();
						$parcs		= $xPlan->getParcsPendientes();
						//$txt		= "";
						$arrD		= array();
						foreach ($parcs as $p){
							//setLog( $p[SYS_NUMERO]. " " . $xF->getFechaDDMM($p[SYS_FECHA]) . " ". getFMoney($p[SYS_TOTAL]));
							if( setNoMenorQueCero($p[SYS_TOTAL]) > 0){ 
								$neto	= $p[SYS_TOTAL];
								if($p[SYS_VARIOS] >0){
									$neto	+= $p[SYS_VARIOS]; 
								}
								$arrD[$p[SYS_NUMERO]]	= $p[SYS_NUMERO]. " " . $xF->getFechaDDMM($p[SYS_FECHA]) . " ". getFMoney($neto);
							}
						}
						if($xCred->getPagosSinCapital() == true){
							if(!isset( $arrD[$xCred->getPeriodoActual()] )){
								$arrD[$xCred->getPeriodoActual()]	= $xCred->getPeriodoActual() . " - Pagos a Capital Letra Anterior"; 
							}
						}						
						$xSel		= new cHSelect();
						$xSel->addOptions($arrD);
						$xSel->setEnclose(false);
						return $xSel->get("idparcialidad", "TR.Numero de Parcialidad", $xCred->getPeriodoActual()+1);
					} else {
						if(MODO_CORRECION == true){
							$xTxt		= new cHText();
							$xTxt->setDivClass("");
							return $xTxt->getNumero("idparcialidad", $xCred->getPeriodoActual()+1, "TR.Numero de Parcialidad");
						}
					}
				}
			}
		}
	}
}

function jsaGetLetrasAVencerTodas($fecha = false){
	$xD		= new cFecha();
	$xL		= new cSQLListas();
	
	$fecha	= $xD->getFechaISO($fecha);
	$filtro	= " AND (`creditos_solicitud`.`saldo_actual`> " . TOLERANCIA_SALDOS .  ") AND (`creditos_tipoconvenio`.`omitir_seguimiento` =0) ";
	$sql	= $xL->getListadoDeLetrasConCreditos_Simple($fecha, false, "", "", $filtro, true);
	$xT		= new cTabla($sql, 2);
	$xT->setWithMetaData(true);
	$xT->setEventKey("jsCargarCredito");
	return $xT->Show();
}

$jxc ->exportFunction('jsaGetVerifyPlan', array("idsolicitud"), "#aviso");
$jxc ->exportFunction('jsaGetLetrasAVencerTodas', array("idfecha-0"), "#lst");
$jxc ->exportFunction('jsaGetVerifyPlan', array("idsolicitud"), "#aviso");
$jxc ->exportFunction('jsaGetLetras', array("idsolicitud"), "#divparcialidad");

$jxc ->process();

echo $xHP->getHeader();
echo $xHP->setBodyinit("initComponents()");

//$jbf    				= new jsBasicForm("frmPreRecibo");
//$jbf->setIncludeJQuery();
//$jbf->mIncludeCreditos 	= true;

$jxc->drawJavaScript(false, true);


$xFRM		= new cHForm("frmPreRecibo", "frmcobrosdecreditos2.php", false, "POST");
$xBtn		= new cHButton();
$xTxt		= new cHText();
$xDate		= new cHDate();
$xSel		= new cHSelect();
//$msel		= $xSel->getListaDeProductosDeCredito();
///$msel->addEvent("onchange", "initComponents()");

$xDate->setDivClass("");
$xFRM->addDivSolo($xDate->get("TR.Fecha"), "<div id='mscom'></div>", "tx14", "tx34");
$xFRM->addCreditBasico();
$xFRM->addDataTag("role", $xEvt->PAGO);

$xTxt->addEvent("jsaGetLetras();jsaGetVerifyPlan();", "onfocus");

$xTxt->setDivClass("");
$props	= array( 1 => array("id" => "divparcialidad"), 2 => array("id" => "divavisos"));
$xFRM->addDivSolo($xTxt->get("idparcialidad", "", "TR.Numero de Parcialidad", "", false, CTRL_GOLETRAS),"<p class='aviso' id='aviso'></p>" , "tx14", "tx34", $props);

$xDate->addEvents("onblur=\"initComponents()\" onchange=\"initComponents()\" ");

$xFRM->addHTML("<div id='lst' class='inv'></div>");

$xFRM->addSubmit("", "setFrmSubmit()");

$xFRM->addToolbar($xBtn->getBasic("TR.Letras Pendientes", "jsObtenerLetras()", "ejecutar", "cmdGetLetras", false));
if(getEsModuloMostrado(USUARIO_TIPO_OFICIAL_CRED) == true){
	$xFRM->addToolbar($xBtn->getBasic("TR.panel_de_control_de", "jsGoPanel()", "panel", "idgetpanel", false));
}
$xFRM->OButton("TR.PAGOS POR FECHA", "jsaGetLetrasAVencerTodas()", $xFRM->ic()->REPORTE4);
$xFRM->OButton("TR.Estado de Cuenta", "getEdoCtaCredito()", $xFRM->ic()->ESTADO_CTA);
$xFRM->OButton("TR.PLAN_DE_PAGOS", "getPlanDePagos()", $xFRM->ic()->CALENDARIO);
$xFRM->OButton("TR.generar PLAN_DE_PAGOS", "getFormaPlanDePagos()", $xFRM->ic()->CALENDARIO1);

$xFRM->OButton("TR.NOTAS", "jsAddNota()", $xFRM->ic()->NOTA);

echo $xFRM->get();
?>
</body>
<?php
//$jbf->show();
?>
<script>
var Wo			= new Gen();
var xG			= new Gen();
var onAsClicked = false;
var mCURFrm 	= document.getElementById("id-frmPreRecibo");
var jrsFileE 	= "./jscaja.js.php";
var xPer		= new PersGen();
var xSeg		= new SegGen();
var xCred		= new CredGen();
function initComponents(){	 }
function setFrmSubmit(){
	var success	= true;
	if(entero($("#idsolicitud").val()) <= 0||entero($("#idsocio").val()) <= 0){
		alert( Wo.lang("Falta el el codigo de persona o de el credito") );
	}
	if( $('#idparcialidad').length > 0){
		var idx	= $("#idparcialidad");
		if ( entero(idx.val()) <= 0 ) {
			alert( Wo.lang("Falta el numero Parcialidad") );
			idx.focus();
			success	= false;
		}
	} else {
		alert( Wo.lang("Falta el numero Parcialidad") );
		success		= false;
	}
	

	if (success == true ) {
		xG.spinInit();
		//mCURFrm.action = "frmcobrosdecreditos2.php?m=" + sIs;
		mCURFrm.submit();
	}
}
function getEdoCtaCredito(){ 
	var idcredito = $("#idsolicitud").val();
	if(idcredito > DEFAULT_CREDITO){
		xCred.getEstadoDeCuenta( idcredito );
	}
}
function getPlanDePagos(){
	var idcredito = $("#idsolicitud").val();
	if(idcredito > DEFAULT_CREDITO){
		xCred.getImprimirPlanPagosPorCred( idcredito );
	}	
}
function getFormaPlanDePagos(){
	var idcredito = $("#idsolicitud").val();
	if(idcredito > DEFAULT_CREDITO){
		xCred.getFormaPlanPagos( idcredito );
	}
}
function jsGoPanel(){ Wo.w({url: "../frmcreditos/creditos.panel.frm.php?idsolicitud=" + $("#idsolicitud").val() + "&idsocio=" + $("#idsocio").val()}); }

function jsCargarCredito(credito){
	var id 		= "#tr-letras-" + credito;
	var mObj	= processMetaData(id);

	jsCancelarAccion();
	$("#idsocio").val(mObj.codigo);
	$("#idsolicitud").val(mObj.credito);
	jsaGetLetras();jsaGetVerifyPlan();
	//TODO: Modificar
	$("#idparcialidad").val(entero(mObj.periodo));
	//frmSubmit(false);
	//envsoc(); envsol();// envparc();
}
function getSetLetra(){}
function jsGetLetras(){	jsObtenerLetras(); }
function jsEvaluarSalida(evt){
	jsaGetLetras();jsaGetVerifyPlan();jsGetMemos();
}
function jsObtenerLetras(){
	jsaGetLetrasAVencerTodas();
	Wo.winTip({ element : "#idfecha-0",  content : $("#lst"), title: Wo.lang(["letras"]) });
	//getModalTip(window, $("#lst"), Wo.lang(["letras"]));
}
function jsCancelarAccion(){	$("#idfecha-0").qtip("hide");    }
function jsGetMemos(){
	var idp	= $("#idsocio").val();
	var idc	= $("#idsolicitud").val();
	if(idp > DEFAULT_SOCIO){
		xPer.getListaDeNotas({persona:idp,credito:idc, tipo:12, callback:jsLoadedMemos, estado:0});
	}
}
function jsLoadedMemos(data){
	$("#divavisos").empty();
	$.each( data, function( key, val ) {
		xG.alerta({msg:val.notas,info:val.oficial});
		$("#divavisos").append("<div class='error' style='margin-bottom:0.2em'>" + val.notas + "</div>");
	});
}
function jsAddNota(){
	var idcred	= entero($("#idsolicitud").val());
	if(idcred > 1){
		xCred.setNuevaNotaCaja(idcred);
	} else {
		Wo.alerta({msg: "Credito no valido"});
	}
}
</script>
</html>