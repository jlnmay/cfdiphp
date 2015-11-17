<?php

// Nota: La clase se ha probado en sistemas operativos windows y linux de echo está en producción sobre un servidor UNIX, 
//		pero hay algunos detalles que considerar dependiendo el sistema operativo:  
//	Si se utiliza sistemas operativos windows la ruta de los archivos tendría que ser .\\
//	Si se utiliza sistemas operativos Lunix o Unix la ruta de los archivos tendría que ser ./
// 	En este caso los slashes está para el sistema operativo windows por lo que si se utiliza linux, unix, etc. Se tendría que
//		que cambiar el .\\ por el ./
//	Para ejecutar los comandos openssl tipo cmd se hacen individuales para mantener compatibilidad con sistemas operativos
//		linux, unix, etc. 
//  La librería hasta este momento puede generar documentos cfdi de tipo FACTURA, HONORARIOS, ARRENDAMIENTO (Se pretende agregar
// 		los complementos restantes).  
 

// Programado por: Lic. Julián Wilfredo May Dzul.  Contacto: md020985@gmail.com
// Clase que genera un comprobante cfd para timbrar ante un PAC. 
// Nota: 
//		Esta clase se realiza con base a la librería CFDI.prg que se hizo por la comunidad de VFP Factura Electrónica México, 
//		de echo soy programador VFP, tambien se utiliza como base la librería del colega FOrtiz de fábrica la corona.
//   	La clase es open source puede utilizar en sus proyectos libremente. 
// Cualquier duda o aclaración pueden contactarme al correo. 
// Si alguien quiere unirse al proyecto para mejorarlo mi usuario en github es md85.   

	$CFDVersions; // Especifica el objeto instanciado de la clase CFDVersions en una variable publica almacenada en $GLOBALS[].
	$CFDSetting;  // Especifica el objeto instanciado de la clase CFDSetting es una varible publica almacenada en  $GLOBALS[]. 

	/// Inicializa la configuración necesaria.
	function CFDInit(){
		$GLOBALS['CFDVersions'] = new CFDVersions();
		$GLOBALS['CFDSetting'] = new CFDSetting();
	}

	/// Prueba que el OpenSSL.exe este todo OK.
	function CFDTestOpenSSL(){
		$CFDSetting = $GLOBALS['CFDSetting'];
		$CFDSetting->LastError = ""; 
		$openSSL = $CFDSetting->OpenSSL;
		$fileOpenSSL = $openSSL."openssl.exe";
		if(empty($openSSL) || !file_exists($fileOpenSSL))
		{
			$CFDSetting->LastError = "No se encontró el archivo OpenSSL.exe en la ruta indicada";
			return false;  
		} 

		$arrayFile = GetTempFile();
		$file = $arrayFile['Name'];
		$handler = $arrayFile["Handler"];
		$tempFile = basename($file, ".tmp").PHP_EOL; 
		$tempFile = trim($tempFile);
		$tempFile = trim($tempFile); 

		$cBuff = $fileOpenSSL." version > {tempFile}.out"; 
		$cBuff = str_replace("{tempFile}", $tempFile, $cBuff); 
		shell_exec($cBuff); 
		fclose($handler); 
		// Se obtiene el contenido del archivo te lo devuelve en arreglos.
		$cResult = file($tempFile.".out");
		
		if($cResult != null)
		{
		    $cResult = $cResult[0];
		    $CFDSetting->LastError = $cResult; 
			$totalOccurrences = substr_count($cResult, "OpenSSL");
			if($totalOccurrences <> 0)
				$lOk = true;
		}
		else
		{
			$CFDSetting->LastError = "No se pudo obtener la información del archivo .out del SSL.";
			$lOk = false; 
		} 

		unlink($tempFile.".out"); 
		unlink($tempFile.".tmp");
		return $lOk;
	}
	
	/// Clase CFDVersions. 3.2 por default ya que la librería se hizo a partir de la versión que se utiliza hoy en día.  
	class CFDVersions { 
		public $CFDi_32 = 32; // Especifica la versión CFDI.

		/// Obtiene la versión del xml en string.
		public function ToString($version){
			switch($version){
				case 32: 
					return "3.2";
					break; 
				default: 
					return "";   
			}
		}

		/// Obtiene la versión del xml en número.
		public function FromString($version)
		{
			if($version == "3.2")
				return $this->CFDi_32;

			return 0; 
		}

		/// Obtiene la versión larga de un cfdi.
		public function ToLongString($version)
		{
			if($version == $this->CFDi_32)
				return "CFDI 3.2";
			
			return "Valor incorrecto (". $version .")";			
		}	

		/// Valida que la versión del xml sea valido.
		public function Validate($newValue, $currentValue)
		{
			$CFDVersions = $GLOBALS['CFDVersions']; 
			$value = 0; 
			if(gettype($newValue) == "integer")
				$newValue = $CFDVersions->ToString($newValue);

			$value = $CFDVersions->FromString($newValue);

			if(empty($value)) 
			{
				$value = $currentValue;
			}

			return $value; 
		}
	}

	/// Configuraciones generales de uso en la libreria. 
	class CFDSetting {
		public $Version = "1.0"; // Specifies versíon.
		public $OpenSSL = ""; // Specifies Open ssl.
		public $SMTPServer = ""; // Specifies smtp server.
		public $SMTPPort = ""; // Specifies port.
		public $SMTPUseSSL = true; // Specifies if use ssl.
		public $SMTPAuthenticate = true; // Specifies smtp authenticate.
		public $SMTPUserName = ""; // Specifies user name.
		public $SMTPPassword = ""; // Specifies user's password.
		public $MailSender = ""; // Specifies mail sender.
		public $LastError = ""; // Specifies last error.
		public $TestMode = false; // Specifies if is test mode.
		public $DigestMethod = "md5"; // Specifies digest method.
		public $LocationRepository = ""; // Specifies location repository.
		public $LastCertificate = NULL; // Specifies last certificate.
		public $PrintFormat = ""; // Specifies print format. 
		public $XmlVersion = 0; // Specifies xml version.
		public $IncludeBOM = false; // Specifies if is include bom.

		function __construct(){
			$CFDVersions = $GLOBALS['CFDVersions']; 
			$this->OpenSSL = "SSL\\";
			$this->LocationRepository = "SSL\\"; 
			$this->XmlVersion = $CFDVersions->Validate($CFDVersions->CFDi_32, $this->XmlVersion); 
		}
	}

	/// Clase que almacena la información de un comprobante 3.2
	class CFDComprobante {
		

		// Atributos requeridos
		public $Version = "";
		public $Fecha = NULL; // Datetime. 
		public $Sello = ""; 
		public $FormaDePago = ""; 
		public $NoCertificado = "";
		public $SubTotal = 0.00; 
		public $Total = 0.00;
		public $TipoDeComprobante = ""; 
		public $Emisor = NULL;  
		public $Receptor = NULL;
		public $Conceptos = NULL; 

		// Atributos opcionales. 
		public $Serie = ""; 
		public $Folio = 0; 
		public $Certificado = ""; 
		public $CondicionesDePago = ""; 
		public $Descuento = 0.00; 
		public $MotivoDescuento = ""; 
		public $Impuestos = NULL; 
		public $Addenda = NULL;  

		// Atributos CFD y CFDI 3.2 opcionales.
		public $MontoFolioFiscalOrig = NULL;
		public $FechaFolioFiscalOrig = NULL;
		public $SerieFolioFiscalOrig = NULL;
		public $FolioFiscalOrig = NULL;
		public $NumCtaPago = "";
		public $Moneda = NULL;
		public $TipoCambio = NULL;
		 
		// Atributos CFD 2.2 y CFDI 3.2 requeridos 
		public $LugarExpedicion = "";
		public $MetodoDePago = "";
		 
		// Propiedades
		public $CadenaOriginal = ""; // Solo lectura.
		public $UbicacionOpenSSL = ""; 


		/// Inizializa los objetos necesarios.s
		function __construct(){
			$CFDVersions = $GLOBALS["CFDVersions"]; 
			$CFDSetting = $GLOBALS['CFDSetting']; 
			$this->Version = $this->VersionAssign($CFDSetting->XmlVersion, 0); 
			$this->Emisor = new CFDPersona();
			$this->Receptor = new CFDPersona();     
			$this->Conceptos = new CFDConceptos();  
			$this->Impuestos = new CFDImpuestos(); 
			$this->UbicacionOpenSSL = $CFDSetting->OpenSSL;  
		}

		/// Asigna la versión del xml en caracter.
		function VersionAssign($versionXML)
		{
			$CFDVersions = $GLOBALS['CFDVersions'];
			$version = $CFDVersions->Validate($versionXML, 0);

			if($version > 0)
				$xmlVersion = $CFDVersions->ToString($version);

			return $xmlVersion; 
		}

		/// Obtiene la cadena original.
		function GetCadenaOriginal(){
			return $this->GenerarCadenaOriginal(); 
		}
		
		// Lee el archivo de certificado indicado y actualiza los atributos apropiados.
	    function LeerCertificado($archivoCER){
			$CFDSetting = $GLOBALS['CFDSetting'];

			if(is_null($CFDSetting->LastCertificate) || $CFDSetting->LastCertificate->Archivo <> $archivoCER)
			{
				$oCert = NULL; 
				$oCert = CFDLeerCertificado($archivoCER);
				if(is_null($oCert))
					return NULL; 

				$CFDSetting->LastCertificate = $oCert; 
			}
			else 
				$oCert = $CFDSetting->LastCertificate; 

			if($oCert->Valido && ($oCert->Vigente || $CFDSetting->TestMode))
			{
				$this->Certificado = trim($oCert->Certificado); 
				$this->NoCertificado = trim($oCert->Serial);
			}

			return $oCert;
		}

		// Genera el sello digital del comprobante y actualiza los atributos apropiados. 
		function Sellar($archivoKey, $password){
			
			$CFDSetting = $GLOBALS['CFDSetting'];
			$CFDSetting->LasteError = ""; 

			// Si la fecha del comprobante es igual o posterior al 01/01/2011, se cambia el método MD5 por SHA-1 
			$metodo = ""; 
			$metodo = "md5";

			$date = $this->Fecha; 
			$year = substr($date, 0, 3); 
			if(strval($year) > 2010)
				$metodo = "sha1";
			
			$CFDSetting->DigestMethod = $metodo; 

			$cadenaOriginal = ""; 
			$cadenaOriginal = $this->GetCadenaOriginal(); 
			$this->CadenaOriginal = $cadenaOriginal; 
			$fileCer = $cadenaOriginal; 
			if(empty($cadenaOriginal))
			{
				$CFDSetting->LastError = "No se pudo obtener la cadena original. Utilize CFDProbarOpenSSL() para verificar el funcionamiento de la librería OpenSSL."; 
				return false; 
			}

			$this->Sello = CFDGenerarSello($cadenaOriginal, $archivoKey, $password, $metodo, $this->UbicacionOpenSSL); 

			$selloFin = $this->Sello; 
			$cadenaFin = $cadenaOriginal; 
			$fileKey = $selloFin; 

			return !empty($this->Sello); 
		}

		// Genera la cadena original que sirve de base para generar el sello digital. 
		// La cadena original se obtiene directamente del XML aplicando el archivo XSLT proporcionado por el SAT. 
		function GenerarCadenaOriginal(){
			$tempFile =""; 
			$file = GetTempFile("", "XML");
			$tempFile =  $file['Name']; 
			$handler = $file["Handler"];
			$this->CrearXML($tempFile); 
			$str = ""; 
			$str = CFDExtraerCadenaOriginal($tempFile);
			fclose($handler);
			unlink($tempFile);
			return $str;  
		}

		// Crea el archivo XML que representa el comprobante.
		function CrearXML($archivo){
			$CFDSetting = $GLOBALS['CFDSetting'];
			$CFDSetting->LastError = ""; 
			$root = "";
			$xml = new DOMdocument("1.0", "UTF-8");
			$root = $xml->createElement("cfdi:Comprobante");
		    $root = $xml->appendChild($root); 

		    $this->satxmlsv32_cargaAtt($root, array("xmlns:cfdi"=>"http://www.sat.gob.mx/cfd/3",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/3  http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd"
                         )
		    );
		    
		    // Datos generales. 
		    
		    $this->satxmlsv32_cargaAtt($root, array("version"=> $this->FixStr(strval($this->Version),"", ""),
                      "serie"=> $this->FixStr($this->Serie, "", ""),
                      "folio"=> $this->FixStr($this->Folio, "", ""),
                      "fecha"=> $this->FixStr($this->Fecha, "T", ""),
                      "Moneda"=> $this->FixStr($this->Moneda, "", ""),
                      "TipoCambio" => $this->FixStr($this->TipoCambio, "N", ""),
                      "sello"=> $this->Sello,
                      "formaDePago"=>$this->FixStr($this->FormaDePago, "", ""),
                      "noCertificado"=> $this->NoCertificado,
                      "certificado"=> $this->Certificado,
                      "subTotal"=> $this->FixStr($this->SubTotal, "N", ""),
                      "descuento"=> $this->FixStr($this->Descuento, "N", ""),
                      "motivoDescuento" => $this->FixStr($this->MotivoDescuento, "", ""),
                      "total"=> $this->FixStr($this->Total, "N", ""),
                      "tipoDeComprobante"=> $this->FixStr($this->TipoDeComprobante, "", ""),
                      "metodoDePago"=> $this->FixStr($this->MetodoDePago, "", ""),
                      "LugarExpedicion"=> $this->FixStr($this->LugarExpedicion, "", ""),
                      "NumCtaPago"=> $this->FixStr($this->NumCtaPago, "", ""),
                      "FolioFiscalOrig"=>"",
                      "SerieFolioFiscalOrig"=>"",
                      "FechaFolioFiscalOrig"=>"",
                      "MontoFolioFiscalOrig"=>""
                   )
         	); 
		    
		    // Emisor. 
         	$emisor = $xml->createElement("cfdi:Emisor");
			$emisor = $root->appendChild($emisor);
			$this->satxmlsv32_cargaAtt($emisor, array("rfc"=> $this->FixStr(str_replace(array(".", "-", " "), "" , $this->Emisor->Rfc), "", ""),
		                       "nombre"=> $this->FixStr($this->Emisor->Nombre, "", "")
		                   )
		                );
			$domfis = $xml->createElement("cfdi:DomicilioFiscal");
			$domfis = $emisor->appendChild($domfis);
			
			$this->satxmlsv32_cargaAtt($domfis, array("calle"=> $this->FixStr($this->Emisor->DomicilioFiscal->Calle, "", ""),
		                        "noExterior"=> $this->FixStr($this->Emisor->DomicilioFiscal->NoExterior, "", ""),
		                        "noInterior"=> $this->FixStr($this->Emisor->DomicilioFiscal->NoInterior, "", ""),
		                        "colonia"=> $this->FixStr($this->Emisor->DomicilioFiscal->Colonia, "", ""),
		                        "municipio"=> $this->FixStr($this->Emisor->DomicilioFiscal->Municipio, "", ""),
		                        "estado"=> $this->FixStr($this->Emisor->DomicilioFiscal->Estado, "", ""),
		                        "pais"=> $this->FixStr($this->Emisor->DomicilioFiscal->Pais, "", ""),
		                        "codigoPostal"=> $this->FixStr($this->Emisor->DomicilioFiscal->CodigoPostal, "", "")
		                   )
		                );

			
			for($i=0; $i<count($this->Emisor->RegimenFiscal->RegimenFiscal); $i++){
				$regimen = $xml->createElement("cfdi:RegimenFiscal");
				$expedido = $emisor->appendChild($regimen);
				$this->satxmlsv32_cargaAtt($regimen, array("Regimen"=> $this->Emisor->RegimenFiscal->RegimenFiscal[$i]
		           		)
		    		); 
			} 

			// Receptor.
			$receptor = $xml->createElement("cfdi:Receptor");
			$receptor = $root->appendChild($receptor);
			$this->satxmlsv32_cargaAtt($receptor, array("rfc" => $this->FixStr(str_replace(array(".", "-", " "),"", $this->Receptor->Rfc), "", ""),
		                          "nombre"=> $this->FixStr($this->Receptor->Nombre, "", "")
			                    )
		            		);

			$domicilio = $xml->createElement("cfdi:Domicilio");
			$domicilio = $receptor->appendChild($domicilio);

			$this->satxmlsv32_cargaAtt($domicilio, array("calle"=> $this->FixStr($this->Receptor->DomicilioFiscal->Calle, "", ""),
		                        "noExterior"=> $this->FixStr($this->Receptor->DomicilioFiscal->NoExterior, "", ""),
		                        "noInterior"=> $this->FixStr($this->Receptor->DomicilioFiscal->NoInterior, "", ""),
		                        "colonia"=> $this->FixStr($this->Receptor->DomicilioFiscal->Colonia, "", ""),
		                        "municipio"=> $this->FixStr($this->Receptor->DomicilioFiscal->Municipio, "", ""),
		                        "estado"=> $this->FixStr($this->Receptor->DomicilioFiscal->Estado, "", ""),
		                        "pais"=> $this->FixStr($this->Receptor->DomicilioFiscal->Pais, "", ""),
		                        "codigoPostal"=> $this->FixStr($this->Receptor->DomicilioFiscal->CodigoPostal, "", "")
                   				)
                			);

			$conceptos = $xml->createElement("cfdi:Conceptos");
			$conceptos = $root->appendChild($conceptos);
			
			for ($i=0; $i<count($this->Conceptos->Conceptos); $i++) {
			    $concepto = $xml->createElement("cfdi:Concepto");
			    $concepto = $conceptos->appendChild($concepto);
			    
			    $concept = $this->Conceptos->Conceptos[$i]; 

			    $this->satxmlsv32_cargaAtt($concepto, array(
			    						"noIdentificacion" => $this->FixStr($concept->NoIdentificacion, "", ""),
			    						"cantidad"=> $this->FixStr($concept->Cantidad, "N", ""),
			                            "unidad"=> $this->FixStr($concept->Unidad, "", ""),
			                            "descripcion"=> $this->FixStr($concept->Descripcion, "", ""),
			                            "valorUnitario"=> $this->FixStr($concept->ValorUnitario, "N", ""),
			                            "importe"=> $this->FixStr($concept->Importe, "N", ""),
			                   )
			            	);

			    // BEGIN 2015-09-11 Customs information.
			    if(!empty($concept->InformacionAduanera->Numero))
			    {
			    	$customsInformation = $xml->createElement("cfdi:InformacionAduanera");
			    	$customsInformation = $concepto->appendChild($customsInformation);
			    	$this->satxmlsv32_cargaAtt($customsInformation, array(
			    			"numero" => $this->FixStr($concept->InformacionAduanera->Numero, "", ""),
			    			"fecha" => $concept->InformacionAduanera->Fecha,
			    			"aduana" => $this->FixStr($concept->InformacionAduanera->Aduana, "", ""))
			    		); 
			    }
			    // END 2015-09-11 Customs information.

			    // BEGIN 2015-10-02 Predial account.
			    if(!empty($concept->CuentaPredial->Numero))
			    {
			    	$predialAccount = $xml->createElement("cfdi:CuentaPredial");
			    	$predialAccount = $concepto->appendChild($predialAccount);
			    	$this->satxmlsv32_cargaAtt($predialAccount, array(
			    		"numero" => $this->FixStr($concept->CuentaPredial->Numero, "", "")));
			    }
			    // END 2015-10-02 Predial account. 
			}

			$impuestos = $xml->createElement("cfdi:Impuestos");
		    $impuestos = $root->appendChild($impuestos);

		    if($this->Receptor->Rfc != "XAXX010101000")
		    {
			    // BEGIN 2015-10-03 Retention 
			 
			    if(count($this->Impuestos->Retenciones->Impuestos) > 0)
			    {
			    	$retenciones = $xml->createElement("cfdi:Retenciones");
			    	$retenciones = $impuestos->appendChild($retenciones);
			    	for($i=0; $i<count($this->Impuestos->Retenciones->Impuestos); $i++)
			    	{
			    		$retencion = $xml->createElement("cfdi:Retencion");
			    		$retencion = $retenciones->appendChild($retencion);
			    		$this->satxmlsv32_cargaAtt($retencion, array(
			    			"impuesto" => $this->FixStr($this->Impuestos->Retenciones->Impuestos[$i]["Impuesto"], "", ""),
	                        "importe"=> $this->FixStr($this->Impuestos->Retenciones->Impuestos[$i]["Importe"], "N", "")
	                   	));
			    	}
			    }
			    // END 2015-10-03 Retentions

				$traslados = $xml->createElement("cfdi:Traslados");
				$traslados = $impuestos->appendChild($traslados);
				$traslado = $xml->createElement("cfdi:Traslado");
				$traslado = $traslados->appendChild($traslado);
				$this->satxmlsv32_cargaAtt($traslado, array("impuesto"=> $this->FixStr($this->Impuestos->Traslados->Impuestos[0]["Impuesto"], "", ""),
				                              "tasa"=> $this->FixStr($this->Impuestos->Traslados->Impuestos[0]["Tasa"], "N", ""),
				                              "importe"=> $this->FixStr($this->Impuestos->Traslados->Impuestos[0]["Importe"], "N", "")
				                             )
				                         );

				
				if(count($this->Impuestos->Retenciones->Impuestos) > 0)
			    {
					$impuestos->SetAttribute("totalImpuestosRetenidos", 
						$this->FixStr($this->Impuestos->TotalImpuestosRetenidos(), "N", ""));
				}
			
				$impuestos->SetAttribute("totalImpuestosTrasladados", 
					$this->FixStr($this->Impuestos->TotalImpuestosTrasladados(), "N", ""));
			}
		    $xml->formatOutput = true; 
		    $xml->saveXML(); 
		    $xml->save($archivo); 
		}

		// Recibe una cadena y realiza: 
		//	1. Sustituye cualquier caracter invalido por el caracter "."
		//	2. Elimina los espacios en blanco al inicio y a final de la cadena.
		//	3. Elimina cualquier secuencia de espacios en blanco repetidos dentro de la cadena. 
		function FixStr($value, $varType){
			
			// Númerico.
			if($varType == "N")
			{
				if(empty($value))
					return ""; 
				return number_format($value, 2, ".", "");
			}

			// DateTime.
			if($varType == "T")
			{
				if(empty($value))
					return ""; 
				return $value;
			}

			if(empty($value))
				return ""; 

			$fixed = $value; 
			$fixed = str_replace("&", "&amp;", $fixed);
			$fixed = str_replace("<", "&lt;", $fixed);
			$fixed = str_replace(">", "&gt;", $fixed);
			$fixed = str_replace('"', "&quot;", $fixed);
			$fixed = str_replace("'", "&apos;", $fixed);

			while(strpos($fixed, "  ") <> 0):
				$fixed = str_replace("   ", " ", $fixed); 
			endwhile;
			return $fixed; 
		}

		/// Funcion que carga los atributos a la etiqueta XML
		function satxmlsv32_cargaAtt(&$nodo, $attr) {
		global $xml, $sello;
			foreach ($attr as $key => $val) {
			    $val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
			    $val = trim($val);                           // Regla 5b
			    if (strlen($val)>0) {   // Regla 6
			        //$val = utf8_encode(str_replace("|","/",$val)); // Regla 1
			        $val = str_replace("|","/",$val);
			        $nodo->setAttribute($key,$val);
			    }
			}
		}
	}

	// CFDPersona
	// Representa los datos de una persona juridica especifica dentro de un comprobante digital.
	class CFDPersona{

		// Atributos requeridos.
		// Requiered properties. 

		public $Rfc = ""; // Especifica el rfc del la persona. // Specifies person's rfc. 
		// Specifies person's fiscal regimen. // Required only for the emisor.
		public $RegimenFiscal = NULL; // Especifica el regimen fiscal de la persona. // Requerido solo para el emisor. 

		// Atributos opcionales. 
		public $ExpedidoEn = NULL; // Especifica donde se expide el comprobante digital. // Specifies where was issued the digital receipt.
		public $Nombre = ""; // Especifica el nombre de la persona. // Specifies person's name. 
		public $DomicilioFiscal = NULL; // Especifica el domicilio fiscal de la persona. // Specifies fiscal domicile's person. 

		// Constructor de la clase. 
		// Class constructor.
		function __construct(){

			// Se inicializan las clases necesarias.
			// It is initialized the necessary classes
			$this->DomicilioFiscal = new CFDDireccion; 
			$this->ExpedidoEn = new CFDDireccion; 
			$this->RegimenFiscal = new CFDRegimenFiscal; 
		}

	}

	// CFDDireccion 
	// Representa una direccion fiscal dentro de un comprobante digital.
	class CFDDireccion{

		// Requiered properties.
		// Propiedades requeridas. 
		public $Calle = ""; // Especifica la dirección del domicilio fiscal, // Specifies fiscal domicile's direction.
		public $Municipio =""; // Especifica el municipio del domicilio fiscal, // Specifies fiscal domicile's municipality.
		public $Pais = ""; // Especifica el país del domicilio fiscal, // Specifies fiscal domicile's country.
		public $CodigoPostal = ""; // Especifica el código postal del domicilio fiscal. // Specifies fiscal domicile's postcode. 

		// Optional properties.
		public $NoExterior = ""; // Especifica el no exterior del domicilio fiscal. // Specifies fiscal domicile's ext number. 
		public $NoInterior =""; // Especifica el no interior del domicilio fiscal. // Specifies fiscal domicile's int number.
		public $Colonia = ""; // Especifica la colonia del domicilio fiscal. // Specifies fiscal domicile's colony.
		public $Localidad = ""; // Especifica la localidad del domicilio fiscal. // Specifies fiscal domicile's locality.
		public $Referencia = "";  // Especifica la referencia del domicilio fiscal. // Specifies fiscal domicile's reference. 
		public $Estado = ""; 
	}

	// Especifica el regimen fiscal del contribuyente solo aplica al emisor.
	class CFDRegimenFiscal {
		public $RegimenFiscal = array(); 
		public $Count = 0; 

		function __construct(){
			$this->Count = 0; 
		}
		
		/// Agrega un regimen fiscal. 
		public function Add($regimen){
			$this->Count = $this->Count + 1; 
			$this->RegimenFiscal[$this->Count - 1] = $regimen; 
		}
	}
	
	/// Clase CFDConceptos.
	class CFDConceptos {

		public $Conceptos = array(); 
		public $Count = 0; 

		function __construct(){
			$this->Count = 0; 
		}
		
		/// Agrega un concepto al array.
		public function Add($cantidad, $descripcion, $valorUnitario, $importe){
			$concept = new CFDConcepto(); 
			$concept->Cantidad = $cantidad; 
			$concept->Descripcion = $descripcion; 
			$concept->ValorUnitario = $valorUnitario; 
			$concept->Importe = $importe; 
			$this->Count = $this->Count + 1; 
			$this->Conceptos[$this->Count-1]= $concept; 

			return $concept; 
		}

	}

	/// CFDConcepto.
	class CFDConcepto 
	{
		
		// Required attributes.
		public $Cantidad = 0.00; 
		public $Descripcion = "";
		public $ValorUnitario = 0.00; 
		public $Importe = 0.00; 
		public $Unidad = ""; 

		public $NoIdentificacion = "";  
		public $InformacionAduanera = NULL;
		public $CuentaPredial = NULL; 
		public $Complemento = NULL; 

		function __construct(){
			$this->InformacionAduanera = new CFDInformacionAduanera(); 
			$this->CuentaPredial = new CFDCuentaPredial(); 
		}
	}

	/// CFDInformaciónAduanera. 
	class CFDInformacionAduanera 
	{
		// Obligatory attributes.
		public $Numero = ""; 
		public $Fecha = "";
		// Opcional attributes.
		public $Aduana = "";
	}

	/// Representa una cuenta predial.
	class CFDCuentaPredial 
	{
		// Obligatory attributes. 
		public $Numero = "";  // Specifies predial account's number.
	}

	/// Representa los impuestos de un comprobante CFDI.
	class CFDImpuestos{

		// Atributos opcionales.
		public $TotalImpuestosRetenidos = 0.00; 
		public $TotalImpuestoTrasladados = 0.00; 

		public $Retenciones = NULL; 
		public $Traslados = NULL; 

		// Get / Set. 		
		function __construct(){
			$this->Retenciones = new CFDRetenciones(); 
			$this->Traslados = new CFDTraslados(); 
		}

		
		/// Calcula los impuestos retenidos.
		function TotalImpuestosRetenidos(){
			
			$total = 0.00; 
			for($i=0; $i<sizeof($this->Retenciones->Impuestos); $i++){
				$total = $total + $this->Retenciones->Impuestos[$i]["Importe"]; 
			}

			return $total; 

		}

		/// Calcula los impuestos trasladados.
		function TotalImpuestosTrasladados(){
			
			$total = 0.00; 
			for($i=0; $i<sizeof($this->Traslados); $i++){
				$total = $total + $this->Traslados->Impuestos[$i]["Importe"]; 
			}

			return $total;
		}
	}

	// Representa la lista de retenciones de impuestos del comprobante.
	class CFDRetenciones {
		public $Impuestos = array(); 
			public $Count = 0; 

			function __construct(){
				$this->Count = 0; 
			}
			
			public function Add($impuesto, $importe){
				$this->Count = $this->Count + 1; 
				$this->Impuestos[$this->Count-1]['Impuesto'] = $impuesto; 
				$this->Impuestos[$this->Count-1]['Importe'] = $importe; 
			}
	}

	// Representa la lista de retenciones de impuestos del comprobante.
	class CFDTraslados {
		public $Impuestos = array(); 
			public $Count = 0; 

			function __construct(){
				$this->Count = 0; 
			}
			
			public function Add($impuesto, $tasa, $importe){
				$this->Count = $this->Count + 1; 
				$this->Impuestos[$this->Count-1]['Impuesto'] = $impuesto; 
				$this->Impuestos[$this->Count-1]['Tasa'] = $tasa; 
				$this->Impuestos[$this->Count-1]['Importe'] = $importe; 
			}
	}

	// Representa los datos de un certificado.
	class CFDCertificado{
		public $Archivo = ""; // Nombre y ubicación del archivo cer.
		public $Valido = false; // Indica si el certificado es valido o no.
		public $Vigente = false; // Indica si el certificado es vigente.
		public $Certificado = ""; // Contenido del certificado.
		public $Serial = ""; // Serial del certificado.
		public $VigenteDesde = ""; // Inicio de la vigencia.
		public $VigenteHasta = ""; // Fin de la vigencia.
	}

	/// Generate un archivo con una extensión especifica. 
	function GetTempFile($name="", $extension="tmp", $onlyName=false){
		
		$tempFile = ""; 
		if($extension == "")
			$extension = "tmp";
		$expression = true; 
		$root = ".\\"; 
		$result = array(); 

		while($expression):
			if(empty($name))
				$tempFile = uniqid().".".$extension;  
			else 
				$tempFile = $name.".".$extension; 

			$pathTempFile = $root.$tempFile;
			
			if(!file_exists($pathTempFile))
			{
				if(!$onlyName)
				{
					$result['Name'] = $pathTempFile; 
					$result['Handler'] = fopen($pathTempFile, 'w+');
				}
				else 
				{
					$result['Name'] = $tempFile; 
					$result['Handler'] = 0; 
				}
				$expression = false; 	 
			}

		endwhile;
		
		return $result; 
	}

	/// Valida el cer y key.
	function CFDValidarKeyCer($archivoKey, $archivoCertificado, $passwordKey, $openSSL){

		$CFDSetting = $GLOBALS['CFDSetting'];
		$CFDSetting->LastError = "";  
		
		// Se verifica que la carpeta indicada contenga el archivo OpenSSL.exe 
		if(empty($openSSL))
			$openSSL = $CFDSetting->OpenSSL; 

		$pathOpenSSL = $openSSL."openssl.exe";

		if(empty($openSSL) || !file_exists($pathOpenSSL))
		{
			$CFDSetting->LastError = "No se encontró el archivo OpenSSL.exe en la ruta indicada."; 
			return false; 
		}

		// Se emite por el momento utilizar la ddl de windows 32 para obtener el path corto ya que se pretenden utilizar en cualquier plataforma que 
		//	que soporte php. 
		
		$batFile = ""; 
		$tempFile = ""; 

		$file = GetTempFile();
		$fileName = $file['Name'];
		$handler = $file['Handler'];

		$tempFile = basename($fileName, ".tmp").PHP_EOL; 
		$tempFile = trim($tempFile); 
		
		$buff = $pathOpenSSL." x509 -inform DER -in {cerFile} -noout -modulus > {tempFile}.m1";
		$buff = str_replace("{cerFile}", $archivoCertificado, $buff); 
		$buff = str_replace("{tempFile}", $tempFile, $buff); 
		shell_exec($buff);		 
		
		$buff = $pathOpenSSL." pkcs8 -inform DER -in {keyFile} -passin pass:{password} -out {tempFile}.pem";
		$buff = str_replace("{keyFile}", $archivoKey, $buff); 
		$buff = str_replace("{password}", $passwordKey, $buff); 
		$buff = str_replace("{tempFile}", $tempFile, $buff); 
		shell_exec($buff);	

		$buff = $pathOpenSSL." rsa -in {tempFile}.pem -noout -modulus > {tempFile}.m2"; 
		$buff = str_replace("{tempFile}", $tempFile, $buff); 
		shell_exec($buff);

		fclose($handler); 

		// Se obtiene el contenido del archivo te lo devuelve en arreglos.
		
		$temp = $tempFile.".tmp";
		$tempm1 = $tempFile.".m1";
		$tempm2 = $tempFile.".m2";
		$temppem = $tempFile.".pem";
		
		$valid = false; 

		if(!file_exists($tempm1) || !file_exists($tempm2))
		{
  			$CFDSetting->LastError = "No se crearon los archivos .m1, .pem y .m2";
  			return false; 
		}
		else 
		{
			$cerMod = ""; 
			$keyMod = ""; 
	 		$cerMod = file($tempm1);
	 		$keyMod = file($tempm2);

	 		if(count($cerMod) == 0)
	  		{	
	  			$CFDSetting->LastError = "No se pudo obtener el modulus del archivo CER";
	  	        unlink($tempm1); 
				unlink($tempm2);
	  			return false; 
	 		}
	 
			if(count($keyMod) == 0)
			{
			 	$CFDSetting->LastError = "No se pudo obtener el modulus del archivo KEY (verifique la contraseña)";
			 	unlink($tempm1); 
				unlink($tempm2);
	  			return false;
			} 
	 
			
			if(empty($CFDSetting->LastError))
			{
				$cer = $cerMod[0];
				$key = $keyMod[0];

				if(trim($cer) === trim($key))
					$valid = true;  

				if(!$valid)
			  		$CFDSetting->LastError = "El archivo KEY no corresponde con el archivo CER indicado";
			}
	 		
	 		// Se eliminan los temporales creados
	 		
			unlink($tempm1); 
			unlink($tempm2);
			unlink($temppem);
			unlink($temp);
		}
	
 		return $valid;
	}

	/// Lee un certitificado.
	function CFDLeerCertificado($archivoCER, $openSSL=""){

		$CFDSetting = $GLOBALS['CFDSetting'];
		$CFDSetting->LastError = "";  

		if(empty($openSSL))
			$openSSL = $CFDSetting->OpenSSL; 

		$pathOpenSSL = $openSSL."openssl.exe";

		if(empty($openSSL) || !file_exists($pathOpenSSL))
		{
			$CFDSetting->LastError = "No se encontró el archivo OpenSSL.exe en la ruta indicada."; 
			return false; 
		}

		// Se emite por el momento utilizar la ddl de windows 32 para obtener el path corto ya que se pretenden utilizar en cualquier plataforma que 
		//	que soporte php. 
		$fileName = ""; 
		$tempFile = ""; 

		
		$file = GetTempFile();
		$fileName = $file['Name'];
		$handler = $file['Handler'];
	
		$tempFile = basename($fileName, ".tmp").PHP_EOL;  
		$tempFile = trim($tempFile); 

		$buff = ""; 
		$buff = $pathOpenSSL." x509 -inform DER -outform PEM -in {cerFile} -pubkey > {tempFile}.pem"; 
		$buff = str_replace("{cerFile}", $archivoCER, $buff); 
		$buff = str_replace("{tempFile}", $tempFile, $buff);
        shell_exec($buff);

        $buff = $pathOpenSSL." x509 -in {tempFile}.pem -serial -noout > {tempFile}.ser"; 
        $buff = str_replace("{tempFile}", $tempFile, $buff);
        shell_exec($buff);

        $buff = $pathOpenSSL." x509 -inform DER -in {cerFile} -noout -startdate > {tempFile}.sta"; 
        $buff = str_replace("{cerFile}", $archivoCER, $buff); 
		$buff = str_replace("{tempFile}", $tempFile, $buff);
		shell_exec($buff);

 		$buff = $pathOpenSSL." x509 -inform DER -in {cerFile} -noout -enddate > {tempFile}.end"; 
 		$buff = str_replace("{cerFile}", $archivoCER, $buff); 
		$buff = str_replace("{tempFile}", $tempFile, $buff);
		shell_exec($buff);

		fclose($handler); 

		// Se obtiene el contenido del archivo te lo devuelve en arreglos.
		$temp = $tempFile.".tmp";
		$temppem = $tempFile.".pem";
		$tempser = $tempFile.".ser";
		$tempsta = $tempFile.".sta";
		$tempend = $tempFile.".end"; 
		
		if(!file_exists($temppem) || !file_exists($tempser) || !file_exists($tempsta) || !file_exists($tempend))
		{
  			return NULL;
		}

		// Se crea el objeto a devolver. 
	 	$oData = NULL; 
	 	$oData = new CFDCertificado; 
	 	$oData->Archivo = $archivoCER;
	 	$oData->Valido = false; 
	 	$oData->Vigente = false; 
	 	$oData->Certificado = ""; 
	 	$oData->Serial = ""; 
	 	$oData->VigenteDesde = ""; 
	 	$oData->VigenteHasta = ""; 

	 	// Se extrae el certificado. 
		$handler = fopen($temppem, "rb");
	 
		$content = ""; 
		while( feof($handler) == false )
     	{
       		$textLine = fgets($handler);
       		$content = $content.$textLine;
    	}
    	
		fclose($handler);
		
		$oCert = ""; 
	 	$oCert = substr($content, strpos($content, "-----BEGIN CERTIFICATE-----")); 
	 	$oCert = str_replace("-----BEGIN CERTIFICATE-----", "", $oCert);
	 	$oCert = str_replace("-----END CERTIFICATE-----", "", $oCert);

	 	// Se quitan saltos de línea y retornos de carro.
	 	$oData->Certificado = trim($oCert); 
	 	$oData->Certificado = str_replace(chr(10), "", $oData->Certificado);
	 	$oData->Certificado = str_replace(chr(13), "", $oData->Certificado);  	 	
	 	$serie = ""; 
	 	$serie = file($tempser);
	 	$serie = $serie[0]; 

	 	$serie = str_replace("serial=", "", $serie);
	 	$serie = str_replace(chr(10), "", $serie);
	 	$serie = hex2bin($serie);

	 	$oData->Serial = $serie; 

	 	// Se extraen las fechas de vigencia.
	 	$oData->VigenteDesde = FCTOT($tempsta);
	 	$oData->VigenteHasta = FCTOT($tempend);

	 	$date = date('d/m/Y h:i:s A');
	 	$oData->Valido = (!empty($oData->Certificado));
	 	
	 	if($date >= $oData->VigenteDesde || $date <= $oData->VigenteHasta)
			$oData->Vigente = true; 
		else 
			$oData->Vigente = false; 
		
		unlink($temp);
	 	unlink($temppem); 
		unlink($tempser);
		unlink($tempsta); 
		unlink($tempend);
	 	
	 	return $oData; 
	}

	/// Permite extraer la cadena original de un comprobante en formato XML.
	function CFDExtraerCadenaOriginal($xmlFile, $openSSL=""){

		$CFDSetting = $GLOBALS['CFDSetting'];
		$CFDSetting->LastError = "";  
		// Load XML file.
		$xml = new DOMDocument("1.0","UTF-8"); 
		$xml->load($xmlFile);
		$xmlString = $xml->saveXML(); 
		// Load XSLT file.
		$xsl = new DOMDocument();
		$xslFile = "SSL\cadenaoriginal_3_2_local.xslt"; 
		$xsl->load($xslFile);

		// Configure the transformer.
		$proc = new XSLTProcessor;

		// Attach the xsl rules. 
		$proc->importStyleSheet($xsl);
		$cadenaOriginal = $proc->transformToXML($xml);
		return $cadenaOriginal; 
	}

	/// Genera el sello digital con base a la cadena original dada. 
	function CFDGenerarSello($cadenaOriginal, $archivoKey, $password, $metodo, $openSSL){
		
		$CFDSetting = $GLOBALS["CFDSetting"];
		$CFDSetting->LastError = "";  
		// Se verifica que la carpeta indicada contenga el archivo OpenSSL.exe 
		if(empty($openSSL))
			$openSSL = $CFDSetting->OpenSSL; 

		$pathOpenSSL = $openSSL."openssl.exe";

		if(empty($openSSL) || !file_exists($pathOpenSSL))
		{
			$CFDSetting->LastError = "No se encontró el archivo OpenSSL.exe en la ruta indicada."; 
			return false; 
		}

		$fileName = "";
		$file = GetTempFile();
		$fileName =  $file['Name'];
		$handler = $file["Handler"]; 
		$tempFile = basename($fileName, ".tmp").PHP_EOL; 
		$tempFileKeyPem = $tempFile; 
		$tempFileKeyPem = trim($tempFileKeyPem);
		$buff = $pathOpenSSL."  pkcs8 -inform DER -in {keyFile} -passin pass:{password} -out {tempFile}.key.pem";
		$buff = str_replace("{keyFile}", $archivoKey, $buff); 
		$buff = str_replace("{password}", $password, $buff);
 		$buff = str_replace("{tempFile}", trim($tempFileKeyPem), $buff); 
		shell_exec($buff);
		$keyPemFile = $tempFileKeyPem.".key.pem"; 
		$pkeyid = openssl_get_privatekey(file_get_contents($keyPemFile));
		openssl_sign($cadenaOriginal, $crypttext, $pkeyid, OPENSSL_ALGO_SHA1);
		openssl_free_key($pkeyid);
		$sello = base64_encode($crypttext); // lo codifica en formato base64
		$tempFile = trim($tempFile);
		fclose($handler);
		unlink($tempFile.".tmp");
		unlink($tempFileKeyPem.".key.pem"); 
		return $sello; 
	}

	/// Valida un XML.
	function CFDValidarXML($xmlFile, $cfdv32){
		// Load XML file.
		$xml = new DOMDocument("1.0","UTF-8"); 
		$xml->formatOutput=true;
		$pathXML = $xmlFile; 
		$xml->load($pathXML);
		$xmlString = $xml->saveXML(); 

		$xmlValidate = new DOMDocument("1.0","UTF-8");
		$xmlValidate->loadXML($xmlString);
		libxml_use_internal_errors(true);
		libxml_clear_errors();
		$fileCFD32 = $cfdv32; 
		$result = $xmlValidate->schemaValidate($fileCFD32);
			
		return $result;
	}
	
	/// Devuelve una fecha ISO a un datetime con el formato d/m/Y h:i:s A.
	function FCTOT($tempFileDate){

		$data = ""; 
		$year = ""; 
		$month = ""; 
		$day = ""; 
		$time = ""; 

		$data = file($tempFileDate);  
		$data = $data[0]; 
		$data = trim($data);
		$data = substr($data, strpos($data, "=") + 1);
		$data = trim(str_replace("GMT", "", $data)); 

		$year = substr($data, -4); 
		$month = substr($data, 0, 3); 
		$month = GetStrNumberMonth(strtoupper($month));
		$day = trim(substr($data, 4, 2));
		$time = substr($data, strpos($data, ":") -2, 8); 

		$dateTime = $year."-".$month."-".$day. " ".$time;  
		$dateTime = strtotime($dateTime); 
		$dateTime = date('d/m/Y h:i:s A', $dateTime);

		return $dateTime; 

	}

	/// Devuelve el número del mes apartir del formato en ingles. JAN -> 01
	function GetStrNumberMonth($month){
		if($month == "JAN")
			return "01";
		if($month == "FEB")
			return "02";
		if($month == "MAR")
			return "03";
		if($month == "APR")
			return "04";
		if($month == "MAY")
			return "05";
		if($month == "JUN")
			return "06";
		if($month == "JUL")
			return "07";
		if($month == "AUG")
			return "08";
		if($month == "SEP")
			return "09";
		if($month == "OCT")
			return "10";
		if($month == "NOV")
			return "11";
		if($month == "DEC")
			return "12";
	}
	
	/// Obtiene el nombre corto de una ruta dada.
	function GetShortPathName($longPath)
	{
		if(!file_exists($longPath))
			return $longPath; 

		$objectFSO  = new COM("Scripting.FileSystemObject");
		$objectFile = $objectFSO->GetFile($longPath);
		$shortPath = $objectFile->ShortPath();
		return $shortPath; 
	}

?> 