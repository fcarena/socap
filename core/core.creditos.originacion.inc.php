<?php
include_once ("core.config.inc.php");
include_once ("entidad.datos.php");
include_once ("core.error.inc.php");
include_once ("core.common.inc.php");
include_once ("core.db.inc.php");
include_once ("core.creditos.inc.php");
include_once ("core.creditos.utils.inc.php");
include_once ("core.creditos.pagos.inc.php");




class cCreditosDeNomina{
	private $mObjCred	= null;
	private $mCredito	= 0;
	private $mInit		= false;
	function __construct($credito = false){
		$this->mCredito	= setNoMenorQueCero($credito);
	}
	function getOCredito(){
		if($this->mObjCred == null){
			if($this->mCredito > DEFAULT_CREDITO){
				$this->mObjCred	= new cCredito($this->mCredito);
				if($this->mObjCred->init() == true){
					$this->mInit	= true;
				}
			}
		}
		return $this->mObjCred;
	}
	function init(){ $this->getOCredito(); return $this->mInit;
	}
	//TODO: Asociar mensajes
	function setVincularEmpresa($empresa, $notas = "", $fecha = false){
		$empresa	= setNoMenorQueCero($empresa);
		$xLog		= new cCoreLog();
		if($this->init() == true){
			$xF		= new cFecha(0);
			$fecha	= $xF->getFechaISO($fecha);
			$xCred	= $this->getOCredito();
			$socio	= $xCred->getClaveDePersona();
			$credito= $this->mCredito;
			if($xCred->getTipoEnSistema() != SYS_PRODUCTO_NOMINA ){
				$xCred->setCambioProducto( CREDITO_PRODUCTO_NOMINA );
			}
			$xCred->setResetPersonaAsociada($fecha, $notas, $empresa);
			if($xCred->isPagable() == true){
				//Agregar operacion de desvinculacion
				$xRe	= new cReciboDeOperacion(RECIBOS_TIPO_ESTADISTICO, false, DEFAULT_RECIBO);
				$xRe->init();
				$xRe->setNuevoMvto($fecha, $xCred->getSaldoActual(), OPERACION_CLAVE_VINCULACION, $xCred->getPeriodoActual(), "", 1, false,	$socio, $credito, $fecha);
				$xRe->setFinalizarRecibo();
				$xLog->add( $xRe->getMessages(OUT_TXT), $xLog->DEVELOPER);
				//Mensaje
				$oP			= $xCred->getOPersona();
				$xEmp		= new cEmpresas($empresa); $xEmp->init();
				$xRN		= new cReglaDeNegocio();
				$xRN->setVariables(array(
						"nombre_de_persona" => $oP->getNombreCompleto(),
						"mensaje" => $notas,
						"descripcion" => $xCred->getDescripcion(),
						"saldo_del_credito" => $xCred->getSaldoActual(),
						"nombre_de_la_empresa" => $xEmp->getNombreCorto()
				));
				$xRN->setExecuteActions( $xRN->reglas()->RN_NOMINA_AL_VINCULAR );
				$xLog->add( $xRN->getMessages(), $xLog->DEVELOPER);
			}
				
			$xLog->add( $xCred->getMessages(OUT_TXT));
				
		}
		return $xLog->getMessages();
	}
	function setDesvincularEmpresa($notas = "", $fecha = false){
		$msg		= "";
		$xLng		= new cLang();

		if($this->init() == true){
			$xCred		= $this->getOCredito();
			$xF			= new cFecha(0);
			$fecha		= $xF->getFechaISO($fecha);
			$empresa	= $xCred->getClaveDeEmpresa();
			$xCred->setResetPersonaAsociada($fecha, $notas);
			if($xCred->isPagable() == true){
				$oP			= $xCred->getOPersona();
				$xEmp		= new cEmpresas($empresa); $xEmp->init();
				$xRN		= new cReglaDeNegocio();
				$xRN->setVariables(array(
						"nombre_de_persona" => $oP->getNombreCompleto(),
						"mensaje" => $notas,
						"descripcion" => $xCred->getDescripcion(),
						"saldo_del_credito" => $xCred->getSaldoActual(),
						"nombre_de_la_empresa" => $xEmp->getNombreCorto()
				));
				$xRN->setExecuteActions( $xRN->reglas()->RN_NOMINA_AL_DESVINCULAR );
			}
		}
	}
	//TODO: Agregar Enviar a Despedidos. nominas.desvincular.frm.php

}
class cCreditosDeGrupo{
	private $mObjCred	= null;
	private $mCredito	= 0;
	private $mInit		= false;
	function __construct($credito = false){
		$this->mCredito	= setNoMenorQueCero($credito);
	}
	function getOCredito(){
		if($this->mObjCred == null){
			if($this->mCredito > DEFAULT_CREDITO){
				$this->mObjCred	= new cCredito($this->mCredito);
				if($this->mObjCred->init() == true){
					$this->mInit	= true;
				}
			}
		}
	}
	function init(){ $this->getOCredito(); return $this->mInit;
	}
	function setVincularGrupo(){

	}
	function setQuitarGrupo(){

	}

}

class cCreditosLeasing {
	public $TIPO_PURO		= 1;
	public $TIPO_FINANCIERO	= 2;

	private $mClave				= false;
	private $mObj				= null;
	private $mInit				= false;
	private $mNombre			= "";
	private $mMessages			= "";
	private $mIDCache			= "";
	private $mTable				= "";
	private $mIDPersona			= 0;
	private $mIDCredito			= 0;
	private $mAnticipo			= 0;
	private $mFinanciamiento	= 0;
	private $mValorResidual		= 0;
	private $mTasaInteres		= 0;
	private $mTasaTIIE			= 0;
	private $mCuotaSeguro		= 0;
	private $mCuotaMtto			= 0;
	private $mCuotaTenencia		= 0;
	private $mCUotaAccesorios	= 0;
	private $mTasaIVA			= 0;
	private $mMontoSeguroFin	= 0;
	private $mMontoSeguroInit	= 0;
	private $mTenenciaInicial	= 0;
	private $mTenenciaFinanc	= 0;
	private $mDomicilia			= false;
	private $mVehiculoDescripcion	= "";
	private $mVehiculoColor			= "";
	private $mVehiculoAnnio			= "";
	private $mVehiculoMarca			= "";
	private $mVehiculoValor			= 0;
	private $mVehiculoExtras		= "";
	private $mVehiculoSerie			= "";
	private $mVehiculoMotor			= "";
	private $mVehiculoPlaca			= "";
	private $mVehiculoProveedor		= 0;
	private $mClaveDeUsuario		= 0;
	private $mClaveDeOficial		= 0;
	private $mClaveDeOriginador		= 0;
	private $mClaveDeSubOriginador	= 0;
	private $mIDVehiculoVinculado	= 0;
	private $mArrResiduales			= array();
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "originacion_leasing-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache();
			$xCache->clean($this->mIDCache);
			$xCache->clean("originacion_leasing-credito-" . $this->mIDCredito);
		} 
	}
	function initByCredito($credito){
		$credito				= setNoMenorQueCero($credito);
		$data					= false;
		if($credito > DEFAULT_CREDITO){
			$this->mIDCredito	= $credito;
			$idc				= "originacion_leasing-credito-" . $this->mIDCredito;
			$xCache				= new cCache();
			$data				= $xCache->get($idc);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `originacion_leasing` WHERE `credito`=". $this->mIDCredito . " LIMIT 0,1");
				if(isset($data["idoriginacion_leasing"])){
					$this->mClave		= $data["idoriginacion_leasing"];
					$this->mIDPersona	= $data["persona"];
					$xCache->set($idc, $data);
				}
			}
			$this->init($data);
		}
		return $this->mInit;
	}
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cOriginacion_leasing();
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj				= $xT; //Cambiar
			$this->mClave			= $xT->idoriginacion_leasing()->v();
			$this->mIDCredito		= $xT->credito()->v();
			$this->mIDPersona		= $xT->persona()->v();
			$this->mFinanciamiento	= $xT->total_credito()->v();
			$this->mAnticipo		= $xT->monto_anticipo()->v();
			$this->mValorResidual	= $xT->monto_residual()->v();
			$this->mTasaInteres		= ($xT->tasa_credito()->v()/100);
			$this->mTasaTIIE		= ($xT->tasa_tiie()->v() /100);
			$this->mCUotaAccesorios	= $xT->cuota_accesorios()->v();
			$this->mCuotaMtto		= $xT->cuota_mtto()->v();
			$this->mCuotaSeguro		= $xT->cuota_seguro()->v();
			$this->mCuotaTenencia	= $xT->cuota_tenencia()->v();
			$this->mTasaIVA			= $xT->tasa_iva()->v();
			$this->mMontoSeguroFin	= ($xT->financia_seguro()->v() == 1) ? $xT->monto_seguro()->v() : 0;
			$this->mMontoSeguroInit	= ($xT->financia_seguro()->v() == 0) ? $xT->monto_seguro()->v() : 0;
			$this->mTenenciaFinanc	= ($xT->financia_tenencia()->v() == 1) ? $xT->monto_tenencia()->v() : 0;
			$this->mTenenciaInicial	= ($xT->financia_tenencia()->v() == 0) ? $xT->monto_tenencia()->v() : 0;
			$this->mDomicilia		= ($xT->domicilia()->v() == 1) ? true : false;
			$this->mVehiculoDescripcion = $xT->modelo()->v();
			$this->mVehiculoMarca		= $xT->marca()->v();
			$this->mVehiculoAnnio		= $xT->annio()->v();
			$this->mVehiculoValor		= $xT->precio_vehiculo()->v();
			$this->mVehiculoExtras		= $xT->describe_aliado()->v();
			$this->mClaveDeUsuario		= $xT->usuario()->v();
			$this->mClaveDeOficial		= $xT->oficial()->v();
			$this->mClaveDeOriginador	= $xT->originador()->v();
			$this->mClaveDeSubOriginador= $xT->suboriginador()->v();
			//$this->mArrResiduales		= 
			$arrRes						= explode(",", $xT->residuales()->v());
			foreach ($arrRes as $dres){
				$DTasa	= explode("-", $dres);
				$PRes	= setNoMenorQueCero($DTasa[0]);
				$TRes	= (isset($DTasa[1])) ? $DTasa[1] : 0;
				$TRes	= setNoMenorQueCero($TRes);
				$this->mArrResiduales[$PRes]	= $TRes;
			}
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}
	function getVehiculoDescripcion(){ return $this->mVehiculoDescripcion;	}
	function getVehiculoMarca(){ return $this->mVehiculoMarca; }
	function getVehiculoAnnio(){ return $this->mVehiculoAnnio; }
	function getVehiculoValor(){ return $this->mVehiculoValor; }
	function getVehiculoExtras(){ return $this->mVehiculoExtras; }
	function getClaveDeOriginador(){ return $this->mClaveDeOriginador; }
	function getClaveDeSubOriginador(){ return $this->mClaveDeSubOriginador; }
	
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){ return $this->mClave; }
	function getDomicilia(){ return $this->mDomicilia; }
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	function setPersona($persona){
		$persona	= setNoMenorQueCero($persona);
		$xQL		= new MQL();
		$xQL->setRawQuery("UPDATE `originacion_leasing` SET `persona`=$persona WHERE `idoriginacion_leasing`=" . $this->mClave);
		$xQL		= null;
		$this->setCuandoSeActualiza();
	}
	function setCredito($credito){
		$credito	= setNoMenorQueCero($credito);
		$xQL		= new MQL();
		$xQL->setRawQuery("UPDATE `originacion_leasing` SET `credito`=$credito WHERE `idoriginacion_leasing`=" . $this->mClave);
		$xQL		= null;
		$this->setCuandoSeActualiza();
	}
	function getValorResidual(){ return $this->mValorResidual; }
	function getFinanciamiento(){ return $this->mFinanciamiento; }
	function getAnticipo(){ return $this->mAnticipo; }
	function getTasaInteres(){ return $this->mTasaInteres; }
	function getTasaTiie(){ return $this->mTasaTIIE; }
	function getCuotaAccesorios(){ return $this->mCUotaAccesorios; }
	function getCuotasNoCapitalizadas(){ return $this->mCuotaMtto+$this->mCuotaSeguro+$this->mCuotaTenencia; }
	function getSeguroInicial(){ return $this->mMontoSeguroInit; }
	function getSeguroFinanciado(){ return $this->mMontoSeguroFin; }
	function getTenenciaInicial(){ return $this->mTenenciaInicial; }
	function getTenenciaFinanciado(){ return $this->mTenenciaFinanc; }
	function getClaveDeOficial(){  return $this->mClaveDeOficial; }
	function getClaveDeVehiculo(){
		$xQL	= new MQL();
		$xDO	= new cCreditosDatosDeOrigen();
		$xCache	= new cCache();
		$xIDc	= "";
		$DG		= $xQL->getDataRow("SELECT * FROM `creditos_garantias` WHERE `solicitud_garantia`=" . $this->mIDCredito . " AND `tipo_origen`=" . $xDO->ORIGEN_ARRENDAMIENTO . " AND `clave_origen`= " . $this->mClave . " LIMIT 0,1");
		
		if(isset($DG["idcreditos_garantias"])){
			$this->mIDVehiculoVinculado	= $DG["idcreditos_garantias"];
			$this->mIDCredito			= $DG["solicitud_garantia"];
			$this->mIDPersona			= $DG["socio_garantia"];
			$this->mVehiculoMotor		= $DG["caracteristica2"];
			$this->mVehiculoPlaca		= $DG["caracteristica5"];
			$this->mVehiculoSerie		= $DG["caracteristica1"];
			$this->mVehiculoProveedor	= $DG["idsocio_duenno"];
			/*$xFRM->OText_13("caracteristica3", $xTabla->caracteristica3()->v(), "TR.Color");$xFRM->OText_13("caracteristica4", $xTabla->caracteristica4()->v(), "TR.ANNIO");*/
		}
		return $this->mIDVehiculoVinculado;
	}
	function getTasaResidualPzo($plazo, $periodicidad = 30){
		$residual	= 0;
		if(isset($this->mArrResiduales[$plazo])){
			$residual	= $this->mArrResiduales[$plazo];
		}
		return $residual;
	}
}


class cLeasingOriginadores {
	private $mClave				= false;
	private $mObj				= null;
	private $mInit				= false;
	private $mNombre			= "";
	private $mMessages			= "";
	private $mIDCache			= "";
	private $mTable				= "";
	private $mTasaComision		= 0;
	private $mMeta				= 0;
	private $mClaveDePersona	= 0;
	private $mDireccion			= "";
	private $mTelefono			= "";
	private $mMail				= "";
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "leasing_originadores" . "-" . $clave;
	}
	private function setCleanCache(){if($this->mIDCache !== ""){ $xCache = new cCache(); $xCache->clean($this->mIDCache); } }
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cLeasing_originadores();//Tabla
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj		= $xT; //Cambiar
			$this->mClave			= $data[$xT->getKey()];
			$this->mNombre			= $xT->nombre_originador()->v();
			$this->mMeta			= $xT->meta()->v();
			$this->mTasaComision	= $xT->comision()->v();
			$this->mClaveDePersona	= $xT->clave_de_persona()->v();
			$this->mDireccion		= $xT->direccion()->v();
			$this->mTelefono		= $xT->telefono()->v();
			$this->mMail			= $xT->email_de_contacto()->v();
			
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}

	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){ return $this->mNombre; }
	function getTasaComision(){ return $this->mTasaComision;	}
	function getClave(){ return $this->mClave; }
	function getCorreoElectronico(){ return $this->mMail; }
	function getTelefono(){ return $this->mTelefono; }
	function getDomicilio(){ return $this->mDireccion; }
	function getClaveDePersona(){ return $this->mClaveDePersona; }
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	
}

class cLeasingUsuarios {
	private $mClave		= false;
	private $mObj		= null;
	private $mInit		= false;
	private $mNombre	= "";
	private $mMessages	= "";
	private $mIDCache	= "";
	private $mTable				= "";
	private $mClaveOriginador	= false;
	private $mClaveUsuario		= false;
	private $mEsActivo			= false;
	private $mEsAdmin			= false;
	private $mMail				= "";
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "leasing_usuarios" . "-" . $clave;
	}
	private function setCleanCache(){if($this->mIDCache !== ""){ $xCache = new cCache(); $xCache->clean($this->mIDCache); } }
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cLeasing_usuarios();//Tabla
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj				= $xT; //Cambiar
			$this->mClave			= $xT->idleasing_usuarios()->v();
			
			$this->mClaveOriginador	= $xT->originador()->v();
			$this->mClaveUsuario	= setNoMenorQueCero($xT->idusuario()->v());
			$this->mEsActivo		= ($xT->estatus()->v() == SYS_UNO) ? true: false;
			$this->mEsAdmin			= ($xT->administrador()->v() == SYS_UNO) ? true : false;
			$this->mMail			= $xT->correo_electronico()->v();
			$this->mNombre			= $xT->nombre()->v();
			
			//setLog($this->mClaveUsuario . "--nada");
			
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}

	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){return $this->mClave;}
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function initByIDUsuario($user = 0){
		$user	= setNoMenorQueCero($user);
		$res	= false;
		if($user >0){
			$xQL	= new MQL();
			$data	= $xQL->getDataRow("SELECT * FROM `leasing_usuarios` WHERE `idusuario`=". $user . " LIMIT 0,1");
			$res	= $this->init($data);
		}
		return $res;
	}
	function getOriginador(){ return $this->mClaveOriginador; }
	function getSubOriginador(){return $this->mClave;}
	function getUsuario(){ return $this->mClaveUsuario; }
	function getEsActivo(){ return $this->mEsActivo; }
	function getEsAdmin(){ return $this->mEsAdmin; }
	function getCorreoElectronico(){ return $this->mMail; }
	function setPin($pin = ""){
		$xUser	= new cSystemUser($this->getUsuario());
		$xQL	= new MQL();
		$iduser	= $this->getUsuario();
		
		if($xUser->init() === false OR $iduser <= 0 ){
			
			$xImport	= new cTiposLimpiadores();
			$NC			= $xImport->cleanNombreComp($this->mNombre);
			$xUser->add($this->getCorreoElectronico(), $pin, USUARIO_TIPO_ORIGINADOR, $NC[0], $NC[1], $NC[2] );
			$xUser		= new cSystemUser($this->getCorreoElectronico(), false);
			$xUser->init();
			$iduser		= $xUser->getID();
			
		} else {
			$xUser->setPassword($pin);
		}
		$pin	= $xUser->getHash($pin);
		$res	= $xQL->setRawQuery("UPDATE `leasing_usuarios` SET `pin`='$pin', `idusuario`=$iduser WHERE `idleasing_usuarios`=" . $this->mClave);
		$this->setCuandoSeActualiza();
		return ($res === false) ? false : true;
	}
	function add(){}

}

class cLeasingTasas {
	private $mClave				= false;
	private $mObj				= null;
	private $mInit				= false;
	private $mNombre			= "";
	private $mMessages			= "";
	private $mIDCache			= "";
	private $mTable				= "";
	private $mPlazo				= 0;
	private $mTasa				= 0;
	private $mTipoRac			= 0;
	private $mComisionApertura	= 0;
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "leasing_tasas" . "-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache(); 
			$xCache->clean($this->mIDCache);
			$xCache->clean("leasing_tasas-plazo-rac-" . $this->mPlazo . "-" . $this->mTipoRac);
		}
	}
	function init($data = false){
		$xCache		= new cCache();
		$xT			= new cLeasing_tasas();
		
		
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj					= $xT; //Cambiar
			$this->mClave				= $data[$xT->getKey()];
			$this->mTasa				= $xT->tasa_ofrecida()->v();
			$this->mPlazo				= $xT->limite_superior()->v();
			$this->mTipoRac				= $xT->tipo_de_rac()->v();
			$this->mComisionApertura	= $xT->comision_apertura()->v();
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit				= true;
			$xT 						= null;
		}
		return $this->mInit;
	}
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){return $this->mClave;}
	function getTasa(){ return $this->mTasa; }
	function getComisionApertura(){ return $this->mComisionApertura; }
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	
	function initByPlazoRAC($plazo, $rac){
		$xCache	= new cCache();
		$datos	= $xCache->get("leasing_tasas-plazo-rac-$plazo-$rac");
		if(!is_array($datos)){
			$xQL	= new MQL();
			$datos	= $xQL->getDataRow("SELECT * FROM `leasing_tasas` WHERE ($plazo >= `limite_inferior` AND $plazo<=`limite_superior`) AND `tipo_de_rac`=$rac LIMIT 0,1");
			if(isset($datos["idleasing_tasas"])){
				$this->mClave	= $datos["idleasing_tasas"];
				$this->mPlazo	= $plazo;
				$this->mTipoRac	= $rac;
				$this->init($datos);
			}
		}
		return $this->mInit;
	}
}
class cLeasingComisiones {
	private $mClave					= false;
	private $mObj					= null;
	private $mInit					= false;
	private $mNombre				= "";
	private $mMessages				= "";
	private $mIDCache				= "";
	private $mTable					= "";
	private $mComisionOriginador	= 0;
	private $mComisionEjecutivo		= 0;
	private $mComisionRegional		= 0;
	private $mTasaBono				= 0;
	private $mMetaBono				= 0;
	private $mTipoOriginador		= 0;
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "leasing_comisiones-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache(); 
			$xCache->clean($this->mIDCache);
			$xCache->clean("leasing_comisiones-by-tipo-" . $this->mTipoOriginador);
		} 
	}
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cLeasing_comisiones();
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj					= $xT;
			$this->mClave				= $data[$xT->getKey()];
			$this->mTasaBono			= $xT->bono()->v();
			$this->mTipoOriginador		= $xT->tipo_de_originador()->v();
			$this->mComisionEjecutivo	= $xT->comision_ejecutivo()->v();
			$this->mComisionOriginador	= $xT->tasa_comision()->v();
			$this->mComisionRegional	= $xT->comision_regional()->v();
			
			
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){ return $this->mNombre; }
	function getClave(){ return $this->mClave; }
	function getComisionOriginador(){ return $this->mComisionOriginador; }
	function getComisionEjecutivo(){ return $this->mComisionEjecutivo; }
	function getComisionRegional(){ return $this->mComisionRegional; }
	
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	function initByTipoRAC($tipo){
		$xCache			= new cCache();
		$this->mTipoOriginador	= $tipo;
		$datos			= $xCache->get("leasing_comisiones-by-tipo-" . $this->mTipoOriginador);
		if(!is_array($datos)){
			$xQL		= new MQL();
			$datos		= $xQL->getDataRow("SELECT * FROM `leasing_comisiones` WHERE `tipo_de_originador`=$tipo LIMIT 0,1");
		}
		if(isset($datos["idleasing_comisiones"])){
			$this->mClave	= $datos["idleasing_comisiones"];
			$this->init($datos);
		}
		return $this->mInit;
	}
}

class cLeasingGPSCosteo {
	private $mClave		= false;
	private $mObj		= null;
	private $mInit		= false;
	private $mNombre	= "";
	private $mMessages	= "";
	private $mIDCache	= "";
	private $mTable		= "";
	private $mPlazo		= 0;
	private $mTipo		= 0;
	private $mMonto		= 0;
	
	function __construct($clave = false){ $this->mClave	= setNoMenorQueCero($clave); $this->setIDCache($this->mClave); }
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "vehiculos_gps_costeo-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache();
			$xCache->clean($this->mIDCache); 
			$idcx			= "vehiculos_gps_costeo-plazo-tipo-" . $this->mPlazo ."-" . $this->mTipo;
			$xCache->clean($idcx);
		} 
	}
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cVehiculos_gps_costeo();
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj		= $xT; //Cambiar
			$this->mClave	= $data[$xT->getKey()];
			$this->mMonto	= $xT->monto_gps()->v();
			$this->mTipo	= $xT->tipo_de_gps()->v();
			$this->mPlazo	= $xT->limite_superior()->v();
			
			
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){return $this->mClave;}
	function getPlazo(){ return $this->mPlazo; }
	function getMonto(){ return $this->mMonto; }
	function getTipo(){ return $this->mTipo; }
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	function initByPlazoTipo($plazo, $tipo){
		$this->mPlazo	= $plazo;
		$this->mTipo	= $tipo;
		$xCache			= new cCache();
		$idcx			= "vehiculos_gps_costeo-plazo-tipo-" . $this->mPlazo ."-" . $this->mTipo;
		$datos			= $xCache->get($idcx);
		if(!is_array($datos)){
			$xQL		= new MQL();
			$datos		= $xQL->getDataRow("SELECT * FROM `vehiculos_gps_costeo` WHERE ($plazo>=`limite_inferior` AND $plazo<=`limite_superior`) AND `tipo_de_gps`=$tipo LIMIT 0,1");
		}
		if(isset($datos["idvehiculos_gps_costeo"])){
			$this->mClave	= $datos["idvehiculos_gps_costeo"];
			$this->init($datos);
			$xCache->set($idcx, $datos);
		}
		//
		return $this->mInit;
	}
}

class cLeasingValorResidual {
	private $mClave				= false;
	private $mObj				= null;
	private $mInit				= false;
	private $mNombre			= "";
	private $mMessages			= "";
	private $mIDCache			= "";
	private $mTable				= "";
	private $mPorcientoResidual	= 0;
	private $mPlazo				= 0;
	private $mTipo				= 0;
	
	function __construct($clave = false){
		$this->mClave	= setNoMenorQueCero($clave);
		$this->setIDCache($this->mClave);
	}
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "leasing_residual-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache();
			$xCache->clean($this->mIDCache);
			$idcx			= "leasing_residual-" . $this->mPlazo ."-" . $this->mTipo;
			$xCache->clean($idcx);
		} 
	}
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cLeasing_residual();
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj					= $xT;
			$this->mClave				= $data[$xT->getKey()];
			$this->mPorcientoResidual	= $xT->porciento_residual()->v();
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit	= true;
			$xT 			= null;
		}
		return $this->mInit;
	}
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){return $this->mClave;}
	function getPorcientoResidual(){ return $this->mPorcientoResidual; }
	function setCuandoSeActualiza(){
		$this->setCleanCache();
	}
	function add(){}
	function initByPlazoTipo($plazo, $tipo=0){
		$this->mPlazo	= $plazo;
		$this->mTipo	= $tipo;
		$xCache			= new cCache();
		$idcx			= "leasing_residual-plazo-tipo-" . $this->mPlazo ."-" . $this->mTipo;
		$datos			= $xCache->get($idcx);
		if(!is_array($datos)){
			$xQL		= new MQL();
			$datos		= $xQL->getDataRow("SELECT * FROM `leasing_residual` WHERE ($plazo>=`limite_inferior` AND $plazo<=`limite_superior`) LIMIT 0,1");
		}
		if(isset($datos["idleasing_residual"])){
			$this->mClave	= $datos["idleasing_residual"];
			$xCache->set($idcx, $datos);
		}
		return $this->init($datos);
	}
}

class cCreditosDatosDeOrigen {
	private $mClave		= false;
	private $mObj		= null;
	private $mInit		= false;
	private $mNombre	= "";
	private $mMessages	= "";
	private $mIDCache	= "";
	private $mTable		= "";
	private $mCredito		= 0;
	private $mTipoOrigen	= 0;
	private $mMontoOrigen	= 0;
	private $mClaveOrigen	= 0;
	
	public $ORIGEN_PRESUPUESTO		= 280;
	public $ORIGEN_PRECLIENTE		= 270;
	public $ORIGEN_NOMINA			= 290;
	public $ORIGEN_ARRENDAMIENTO	= 281;
	public $ORIGEN_RENOVACION		= 3;
	public $ORIGEN_REESTRUCTURA		= 4;
	
	function __construct($clave = false, $credito = false){
		$this->mClave	= setNoMenorQueCero($clave);
		$this->mCredito	= setNoMenorQueCero($credito);
		$this->setIDCache($this->mClave);
	}
	function getIDCache(){ return $this->mIDCache; }
	function setIDCache($clave = 0){
		$clave = ($clave <= 0) ? $this->mClave : $clave;
		$clave = ($clave <= 0) ? microtime() : $clave;
		$this->mIDCache	= "creditos_datos_originacion-" . $clave;
	}
	private function setCleanCache(){
		if($this->mIDCache !== ""){
			$xCache = new cCache();
			$xCache->clean($this->mIDCache);
			$xCache->clean("creditos_datos_originacion-credito-" . $this->mCredito);
		}
	}
	function initByCredito($credito = false){
		$credito		= setNoMenorQueCero($credito);
		$credito		= ($credito <= DEFAULT_CREDITO) ? $this->mCredito : $credito;
		$this->mCredito	= $credito;
		if($credito > DEFAULT_CREDITO){
			$xCache		= new cCache();
			$data		= $xCache->get("creditos_datos_originacion-credito-" . $this->mCredito);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `creditos_datos_originacion` WHERE `credito`=". $this->mCredito . " LIMIT 0,1");
			}
			if(isset($data["credito"])){
				$this->mClave	= $data["idcreditos_datos_originacion"]; 
				$xCache->set("creditos_datos_originacion-credito-" . $this->mCredito, $data);
			}
		}
		return $this->init($data);
	}
	function init($data = false){
		$xCache	= new cCache();
		$xT		= new cCreditos_datos_originacion();//Tabla
		if(!is_array($data)){
			$data	= $xCache->get($this->mIDCache);
			if(!is_array($data)){
				$xQL	= new MQL();
				$data	= $xQL->getDataRow("SELECT * FROM `" . $xT->get() . "` WHERE `" . $xT->getKey() . "`=". $this->mClave . " LIMIT 0,1");
			}
		}
		if(isset($data[$xT->getKey()])){
			$xT->setData($data);
			$this->mObj			= $xT; //Cambiar
			$this->mClave		= $data[$xT->getKey()];
			$this->mTipoOrigen	= $xT->tipo_originacion()->v();
			$this->mClaveOrigen	= $xT->clave_vinculada()->v();
			$this->mMontoOrigen = $xT->monto_vinculado()->v();
			
			$this->setIDCache($this->mClave);
			$xCache->set($this->mIDCache, $data, $xCache->EXPIRA_UNDIA);
			$this->mInit		= true;
			$xT 				= null;
		}
		return $this->mInit;
	}
	function getObj(){ if($this->mObj == null){ $this->init(); }; return $this->mObj; }
	function getMessages($put = OUT_TXT){ $xH = new cHObject(); return $xH->Out($this->mMessages, $put); }
	function __destruct(){ $this->mObj = null; $this->mMessages	= "";	}
	function getNombre(){return $this->mNombre;}
	function getClave(){return $this->mClave;}
	function setCuandoSeActualiza(){ $this->setCleanCache(); }
	function getClaveDeOrigen(){ return $this->mClaveOrigen; }

	function getTipoDeOrigen(){ return $this->mTipoOrigen; }
	function getMontoDeOrigen(){ return $this->mMontoOrigen; }
	
	function add($tipo_de_origen = false, $id_de_origen = false, $Monto = 0){
		$id_de_origen	= setNoMenorQueCero($id_de_origen);
		$tipo_de_origen	= setNoMenorQueCero($tipo_de_origen);
		$Monto			= setNoMenorQueCero($Monto);
		
		$xOrg	= new cCreditos_datos_originacion();
		$xOrg->idcreditos_datos_originacion("NULL");
		$xOrg->credito($this->mCredito);
		$xOrg->idusuario(getUsuarioActual());
		$xOrg->tiempo(time());
		$xOrg->tipo_originacion($tipo_de_origen);
		$xOrg->clave_vinculada($id_de_origen);
		$xOrg->monto_vinculado($Monto);
		
		$rs	= $xOrg->query()->insert()->save();
		
		if($rs === false){
			return false;
		} else {
			$this->mTipoOrigen 	= $tipo_de_origen;
			$this->mClaveOrigen	= $id_de_origen;
			return true;
		}
		
	}
	function getDescripcion(){
		$describe	= "";
		switch ($this->mTipoOrigen){
			case $this->ORIGEN_RENOVACION:
				$describe	= "Credito No. " . $this->mClaveOrigen . " Renovado por " . getFMoney($this->mMontoOrigen);
				break;
			case $this->ORIGEN_REESTRUCTURA:
				$describe	= "Credito No. " . $this->mClaveOrigen . " Reestructurado por " . getFMoney($this->mMontoOrigen);
				break;
		}
		return $describe;
	}
}

class cLeasingEmulaciones {
	private $mPlazo		= 0;
	private $mAnticipo	= 0;
	private $mTasaIVA	= 0;
	private $mFrecuencia= 0;
	private $mTasaAnual	= 0;
	private $mCuotaPrincipal	= 0;
	private $mCuotaGPS			= 0;
	private $mCuotaSeguro		= 0;
	private $mCuotaTenencia		= 0;
	private $mCuotaMtto			= 0;
	private $mCuotaAccesorios	= 0;
	private $mCuotaAliado		= 0;
	
	//private $mCuota
	function __construct($plazo, $TasaInteres, $Frecuencia, $TasaIVA = 0){
		$this->mPlazo 		= setNoMenorQueCero($plazo,0);
		$this->mTasaIVA		= setNoMenorQueCero($TasaIVA, 4);
		$this->mTasaAnual	= setNoMenorQueCero($TasaInteres, 4);
		$this->mFrecuencia	= setNoMenorQueCero($Frecuencia,0);
	}
	function getCuotaRenta($precio, $anticipo, $residual, $aliado = 0, $costeGPS = 0){
		$precio		= $this->getMontoSinIva($precio);
		$aliado		= $this->getMontoSinIva($aliado);
		$mCoste		= ($precio+$aliado) - $anticipo;
		$this->mCuotaPrincipal	= $this->getCuota($mCoste, $residual);
		$this->mCuotaGPS		= $this->getCuota($costeGPS);
		
		return round(($this->mCuotaGPS+$this->mCuotaPrincipal),2);
	}
	function getCuotaSeguro($monto, $financia = false){
		$financia			= ($financia == 1 OR $financia == true) ? true : false;
		$this->mCuotaSeguro	= 0;
		if($financia == true){
			$monto				= $this->getMontoSinIva($monto);
			$this->mCuotaSeguro	= $this->getCuota($monto);
			
		}
		return $this->mCuotaSeguro;
	}
	function getCuotaTenencia($monto, $financia = false){
		$financia				= ($financia == 1 OR $financia == true) ? true : false;
		$this->mCuotaTenencia	= 0;
		if($financia == true){
			$monto				= $this->getMontoSinIva($monto);
			$this->mCuotaTenencia= $this->getCuota($monto);
				
		}
		return $this->mCuotaTenencia;
	}
	function getCuotaMtto($monto){
		$this->mCuotaMtto	= 0;
		$monto				= $this->getMontoSinIva($monto);
		$this->mCuotaMtto	= $this->getCuota($monto);
		return $this->mCuotaMtto;
	}
	function getCuotaAccesorios($monto){
		$this->mCuotaAccesorios	= 0;
		$monto					= $this->getMontoSinIva($monto);
		$this->mCuotaAccesorios	= $this->getCuota($monto);
		return $this->mCuotaAccesorios;
	}

	private function getMontoSinIva($monto){
		if($monto > 0){
			$monto		= (1/(1+$this->mTasaIVA)) * $monto;
			return round($monto,2);
		} else {
			return 0;
		}
	}
	private function getCuota($monto, $residual = 0){
		if($monto > 0){
			$xMath		= new cMath();
			return $xMath->getPagoLease($this->mTasaAnual, $this->mPlazo, $monto, $this->mFrecuencia, $residual);
		} else {
			return 0;
		}
	}
	function getValorResidual($precio, $aliado, $plazo = 0, $TasaResidual = 0){
		$xRes		= new cLeasingValorResidual();
		$plazo		= setNoMenorQueCero($plazo);
		$plazo		= ($plazo <= 0) ? $this->mPlazo : $plazo;
		$TasaResidual	= setNoMenorQueCero($TasaResidual);
		$TasaResidual	= ($TasaResidual <= 0) ? $xRes->getPorcientoResidual() : $TasaResidual;
		
		$residual	= 0;
		if($xRes->initByPlazoTipo($plazo) == true){
			$residual	= (($precio+$aliado) * ($TasaResidual / 100)* (1/(1+TASA_IVA)));
		}
		$residual		= round($residual,2);
		return $residual;
	}
	function getCuotaAliado($monto){
		$this->mCuotaAliado	= 0;
		$monto				= $this->getMontoSinIva($monto);
		$this->mCuotaAliado	= $this->getCuota($monto);
		return $this->mCuotaAliado;
	}
}
?>