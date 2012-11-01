<?php
/*-------------------------------------------------------------------------
 *
 * redecard.php
 *		Biblioteca PHP para acesso à plataforma Komerci da Redecard
 * 
 * Copyleft 2012 - Public Domain
 * Original Author: Daniel Loureiro
 *
 * version 1.0
 *
 * https://github.com/loureirorg/php-redecard
 *-------------------------------------------------------------------------
 */


function redecard_gen_ws($url, $nome_metodo, $campos, $dados)
{
	// dados
	$conteudo = array();
	foreach ($campos as $indice => $valor) {
		$conteudo[] = "<$INDICE>".$valor."</$INDICE>";
	}
	
	// corpo do xml
	$xml = 	'<?xml version="1.0" encoding="utf-8"?>'.
			'<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'.
			'<soap:Body>'.
			'<'. $nome_metodo.' xmlns="http://ecommerce.redecard.com.br">'.
			implode('', $conteudo).
			'</'. $nome_metodo.'>'.
			'</soap:Body>'.
			'</soap:Envelope>';
	
	// cabeçalho http
	$headers = array( 
		'Content-Type: text/xml; charset=utf-8', 
		'SOAPAction: "http://ecommerce.redecard.com.br/'. $nome_metodo.'"',
		'Content-length: '.strlen($xml)
	);

	// chamada à url
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($curl);
	curl_close($curl);
	
	// retorna
	return	$result;
}

function GetAuthorized($dados)
{
	$campos = array( 
		'Total', 'Transacao', 'Parcelas', 'Filiacao', 'NumPedido', 'Nrcartao', 
		'CVC2', 'Mes', 'Ano', 'Portador', 'IATA', 'Distribuidor', 'Concentrador',
		'TaxaEmbarque', 'Entrada', 'Pax1', 'Pax2', 'Pax3', 'Pax4', 'Numdoc1',
		'Numdoc2', 'Numdoc3', 'Numdoc4', 'ConfTxn', 'Add_Data' 
	);	
	return	komerci_gen_ws("https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx", "GetAuthorized", $campos, $dados);
}

function VoidTransaction($dados)
{
	$campos = array( 
		'Total', 'Filiacao', 'NumCV', 'NumAutor', 'Concentrador', 'Usr', 'Pwd'
	);
	return	komerci_gen_ws("https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx", "VoidTransaction", $campos, $dados);
}

function Cupom($dados)
{
	$CAMPOS = array(
		'Data', 'Transacao', 'NumCV', 'NumAutor', 'Filiacao'
	);

	//
	//return	komerci_gen_ws( "https://ecommerce.redecard.com.br/pos_virtual/cupom.asp", "Cupom", $CAMPOS, $DADOS );
	return	komerci_gen_ws("https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx", "Cupom", $campos, $dados);
}

?>