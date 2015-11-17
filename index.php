<html>
<head>
    <title>CFDI 3.2</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
</head>
<body>
    <?php
        
        // El test está probado con un servidor apache atravéz de XAMPP. 
        // No hay necesidad de instalar nada adicional al servidor. 
        // Para ejecutar solo escribir en el navegador localhost/cfdiphp

        include("InvoiceCFDI.php");
        CFDInit();
        echo "<pre>";
    	echo "CFD v".$CFDSetting->Version;  
    	echo "<br/>";
    	echo "Demo";
    	echo "<br/>";
    	echo "<br/>";
        echo "-Inicializando...";
        echo "<br/>";
        
    	if(CFDTestOpenSSL())
    	{
    		echo "-Probando OpenSLL...OK ". $CFDSetting->LastError;
    	}
    	else 
    	{
    	     echo "Ocurrio el siguiente error: ". $CFDSetting->LastError;
    	     return; 
    	}
        
    	$cfd = NULL; 

    	$cfd = new CFDComprobante(); 
    	$cfd->Serie = "A";
    	$cfd->Folio = 12345;

    	$objectDateTime = new DateTime('NOW', new DateTimeZone('America/Mexico_City'));
        $dateTime = $objectDateTime->format('Y-m-d\TH:i:s'); 

        // Se le utiliza para especificar una fecha con el formato que requiere en información aduanera. 
        $date = $objectDateTime->format('Y-m-d');  

        $cfd->Fecha = $dateTime; 
        $cfd->FormaDePago = "Pago en una sola exhibición";
        $cfd->CondicionesDePago = "Contado";
        $cfd->SubTotal = 8566.00;
        $cfd->Total = 8776.56;
        $cfd->Descuento = 1000.00;
        $cfd->MotivoDescuento = "Pronto pago";
        $cfd->TipoDeComprobante = "ingreso";
        
        // BEGIN PAGO EN PARCIALIDADES.
        // END PAGO EN PARCIALIDADES  

        $cfd->NumCtaPago = "1234";
        $cfd->Moneda = "MNX";
        $cfd->TipoCambio = 1.00; 
        $cfd->LugarExpedicion = "Sucurcal 1";
        $cfd->MetodoDePago = "Transferencia";

        $cfd->Emisor->Rfc = "AAD990814BP7";
        $cfd->Emisor->Nombre = "DEMO";
        $cfd->Emisor->DomicilioFiscal->Calle = "Villa de las Flores";
        $cfd->Emisor->DomicilioFiscal->NoExterior = "110";
        $cfd->Emisor->DomicilioFiscal->NoInterior = "1";
        $cfd->Emisor->DomicilioFiscal->Colonia = "Centro";
        $cfd->Emisor->DomicilioFiscal->Municipio = "Leon";
        $cfd->Emisor->DomicilioFiscal->Localidad = "México";
        $cfd->Emisor->DomicilioFiscal->Estado = "Leon";
        $cfd->Emisor->DomicilioFiscal->Pais = "México";
        $cfd->Emisor->DomicilioFiscal->CodigoPostal = "67589";

        $cfd->Emisor->RegimenFiscal->Add("Actividad Empresarial");
        $cfd->Emisor->RegimenFiscal->Add("Otro Regimen");

        $cfd->Receptor->Rfc = "EAM001231D51";
        $cfd->Receptor->Nombre = "Envasadoras de aguas en México, S. de R.L de C.V";
        $cfd->Receptor->DomicilioFiscal->Calle = "Av. La Silla";
        $cfd->Receptor->DomicilioFiscal->NoExterior = "7707";
        $cfd->Receptor->DomicilioFiscal->NoInterior = "1";
        $cfd->Receptor->DomicilioFiscal->Colonia = "Parque industial La Silla";
        $cfd->Receptor->DomicilioFiscal->Municipio = "Guadalupe";
        $cfd->Receptor->DomicilioFiscal->Localidad = "Guadalupe";
        $cfd->Receptor->DomicilioFiscal->Estado = "Nuevo León";
        $cfd->Receptor->DomicilioFiscal->Pais = "México";
        $cfd->Receptor->DomicilioFiscal->CodigoPostal = "67190";
        

        // DESCOMENTAR LA SI SE QUIERE REALIZAR UN DOCUMENTO PARA GENERAR RETENCIONES. 
        // INICIA IMPUESTOS PARA RETENCIONES  
        /*
        $cfd->Impuestos->Retenciones->Add("IVA", 106.67);
        $cfd->Impuestos->Retenciones->Add("ISR", 100.00);
        */
        //   FINALZIA IMPUESTOS PARA RETENCIONES  

        $cfd->Impuestos->Traslados->Add("IVA", 16.00, 1210.56);
        
        $concept = $cfd->Conceptos->Add(1.00, "SERVICIO DE ASESORIA", 1000.00, 1000.00);
        $concept->NoIdentificacion = "ABC";
        $concept->Unidad = "N/A";
        $concept->InformacionAduanera->Numero = "12345678901";
        $concept->InformacionAduanera->Fecha = $date;

        $concept->InformacionAduanera->Aduana = "240";
        $concept = $cfd->Conceptos->Add(1.00, "Cortadora circular 1/2", 1696.00, 1696.00);
        $concept->NoIdentificacion = "CCIRC-98";
        $concept->Unidad = "Pza";
        
        $concept = $cfd->Conceptos->Add(1.00, "Mesa de trabajo uso rudo madera", 2499.00, 2499.00);
        $concept->NoIdentificacion = "MES0002";
        $concept->Unidad = "Pza";
        $concept->InformacionAduanera->Numero = "12345698701";
        $concept->InformacionAduanera->Fecha =  $date;
        $concept->InformacionAduanera->Aduana = "350";

        $concept = $cfd->Conceptos->Add(1.00, "Mesa de trabajo uso rudo madera", 2499.00, 2499.00);
        $concept->NoIdentificacion = "MES0002";
        $concept->Unidad = "Pza";
        $concept->InformacionAduanera->Numero = "12345698701";
        $concept->InformacionAduanera->Fecha =  $date;
        $concept->InformacionAduanera->Aduana = "350";
        

        $concept = $cfd->Conceptos->Add(1.00, "Cortadora circular 1/2", 1696.00, 1696.00);
        $concept->NoIdentificacion = "CCIRC-98";
        $concept->Unidad = "Pza";
        
        // INICIA CONCEPTO DE PRUEBA PARA GENERAR UN ARRENDAMIENTO 
        /*
        $concept = $cfd->Conceptos->Add(1.00, "RENTA MENSUAL CORRESPONDIENTE AL MES DE SEPTIEMBRE DE 2015", 1000.00, 1000.00);
        $concept->NoIdentificacion = "CCIRC-98";
        $concept->Unidad = "N/A";
        $concept->CuentaPredial->Numero = "123456";
        */
        //   FINALIZA CONCEPTO DE PRUEBA PARA GENERAR UN ARRENDAMIENTO 

        echo "-Generando CFD...Versión ".$GLOBALS["CFDVersions"]->ToLongString($GLOBALS["CFDVersions"]->FromString($cfd->Version)); 
        echo "<br/>";
        
        $cer = "aad990814bp7_1210261233s.cer";
        $key = "aad990814bp7_1210261233s.key";
        $password = "12345678a";

        echo "-Validando archivos .cer y .key...";
        
        if(!CFDValidarKeyCer($key, $cer, $password, "SSL\\"))
        {
            echo "Ocurrio el siguiente error: ".$CFDSetting->LastError;
            return;
        }
        
        echo "<br/>";
        echo "-Leyendo certificado";

        $certificate = $cfd->LeerCertificado($cer);

        if(!$certificate)
        {
            echo "Ocurrio el siguiente error: ".$CFDSetting->LastError;
            return; 
        }

        if(!$certificate->Valido)
        {
            echo "Ocurrio el siguiente error: El certificado no es valido.";
            return; 
        }

        if(!$certificate->Vigente && !$CFDSetting->TestMode)
        {
            echo "Ocurrio el siguiente error: El certificado no está vigente.";
            return;
        }

        echo "<br/>";
        echo "-Generando sello digital";
        
        if(!$cfd->Sellar($key, $password))
        {
            echo "Ocurrio el siguiente error: ".$CFDSetting->LastError;
            return;
        }
        
        echo "<br/>";
        echo "-Creando CFD";
        echo "<br/>";
       

        $cfd->CrearXML("Test32.xml");
        
        $cfdv32 = "SSL\cfdv32.xsd";

        if(!CFDValidarXML("Test32.xml", $cfdv32))
        {
            echo "Ocurrio el siguiente error :".$CFDSetting->LastError;
        }
        else 
            echo "-Validando CFD OK....";
        
?>
