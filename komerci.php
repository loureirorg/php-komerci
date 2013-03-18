<?php
/*-------------------------------------------------------------------------
 *
 * komerci.php
 *		Biblioteca para acesso à plataforma Komerci da Redecard
 * 
 * Copyleft 2013 - Public Domain
 * Original Author: Daniel Loureiro
 *
 * version 1.2a @ 2013-03-18
 *
 * https://github.com/loureirorg/php-komerci
 *-------------------------------------------------------------------------
 *
 * ATENÇÃO: Antes de efetuar uma venda, é necessário configurar o tipo de 
 * parcelamento (emissor ou estabelecimento) através da função "config_set()",
 * opção "parcelado_emissor", com o valor "true" ou "false". Ex.:
 *
 * 		komerci\config_set("parcelado_emissor", true);
 *
 * ATENÇÃO II: Lembre-se de liberar o IP do server juntamente com a Redecard,
 * caso contrário algumas funções retornarão erro 56 - "dados inválidos".
 *-------------------------------------------------------------------------
 *
 * CONFIGURAÇÕES POSSÍVEIS DE komerci\config()
 *
 * 	* duas_etapas (bool): se "true", a função "paga_cartao()" realiza 2 
 * 		chamadas ao Komerci - preferencialmente deixe como "true";
 *  * filiacao (string): seu número de filiação, coloque 0 na frente se 
 * 		necessário;
 *  * usuario (string): um usuário cadastrado junto ao Komerci. Necessário para 
 *		cancelamento de venda e outras funções. O usuário master, utilizado para 
 * 		acesso à página e que normalmente é o próprio número de filiação sem 
 *		zeros na frente NÃO é um usuário válido. Veja os usuários válidos 
 *		acessando a página da Redecard > Komerci > Usuários do Komerci
 *  * senha (string): sua senha de internet junto ao Komerci. Necessário para 
 *		cancelamento de venda;
 *  * parcelado_emissor (bool): ao fazer parcelado, informa se é parcelado pelo 
 *		emissor ou pelo estabelecimento;
 *-------------------------------------------------------------------------
 */
namespace komerci;


// configurações padrão - altere com a função "komerci\config()"
$_komerci_config = array(
	"filiacao" => null,
	"usuario" => null,
	"senha" => null,
	"parcelado_emissor" => null,
	"duas_etapas"	=> true,
);

// ex. de chamadas válidas:
// config_set(array("filiacao" => "123", "senha" => "123"));
// config_set("parcelado_emissor", true);
function config()
{
	global $_komerci_config;
	$config = (func_num_args() == 1)? func_get_arg(0): array("'". func_get_arg(0) ."'" => func_get_arg(1));
	$_komerci_config = array_merge($_komerci_config, $config);
}

function server_call($method, $args)
{
	// monta xml
	$url = "https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx";
	$xml_data = implode("", array_map(create_function('$key, $value', 'return "<$key>$value</$key>";'), array_keys($args), array_values($args)));
	$xml = 	"<?xml version=\"1.0\" encoding=\"utf-8\"?>".
			"<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ".
				"xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" ".
				"xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">".
			"<soap:Body>".
			"<$method xmlns=\"http://ecommerce.redecard.com.br\">".
			$xml_data.
			"</$method>".
			"</soap:Body>".
			"</soap:Envelope>";

	// envia dados e retorna resposta do servidor da redecard
	$hdrs = array( 
		"Content-Type: text/xml; charset=utf-8", 
		"SOAPAction: \"http://ecommerce.redecard.com.br/$method\"",
		"Content-length: ". strlen($xml)
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $hdrs);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($curl);
	curl_close($curl);
	return	$result;
}


/***
 *** FUNÇÕES QUE ENVELOPAM O ACESSO AOS WEBSERVICES, SEM TRATAMENTO (RAW)
 ***/

function raw_confirm_txn(
	$data, $num_seq, $nsu, $num_autorizacao, 
	$parcelas, $transacao, $total, $filiacao, 
	$distribuidor, $id_pedido, $pax1, $pax2, 
	$pax3, $pax4, $numdoc1, $numdoc2, 
	$numdoc3, $numdoc4
)
{
	$args = array(
		"Data"		=> $data,
		"NumSqn"	=> $num_seq,
		"NumCV"		=> $nsu,
		"NumAutor"	=> $num_autorizacao,
		"Parcelas"	=> $parcelas,
		"TransOrig"	=> $transacao,
		"Total"		=> $total,
		"Filiacao"	=> $filiacao,
		"Distribuidor" => $distribuidor, 
		"NumPedido"	=> $id_pedido,
		"Pax1"		=> $pax1,
		"Pax2"		=> $pax2,
		"Pax3"		=> $pax3,
		"Pax4"		=> $pax4,
		"Numdoc1"	=> $numdoc1,
		"Numdoc2"	=> $numdoc2,
		"Numdoc3"	=> $numdoc3,
		"Numdoc4"	=> $numdoc4,
	);
	$result = extract_data(server_call("ConfirmTxn", $args));
	return	normalize_return($result["ConfirmTxnResponse"]["ConfirmTxnResult"]["CONFIRMATION"]);
}

function raw_council_report(
	$filiacao, $usr, $pwd, $distribuidor, 
	$data_inicial, $data_final, $tipo_trx, $status_trx, 
	$servico_avs
)
{
	$args = array(
		"Filiacao"		=> $filiacao,
		"Distribuidor"	=> $distribuidor,
		"Data_Inicial"	=> $data_inicial,
		"Data_Final"	=> $data_final,
		"Tipo_Trx"		=> $tipo_trx,
		"Status_Trx"	=> $status_trx,
		"Servico_AVS"	=> $servico_avs,
		"Programa"		=> "",
		"Usr" 			=> $usr,
		"Pwd"			=> $pwd,
	);
	$result = extract_data(server_call("CouncilReport", $args));
	return	
		empty($result["CouncilReportResponse"]["CouncilReportResult"]["COUNCIL"]["REGISTRO"])?
		$result:
		$result["CouncilReportResponse"]["CouncilReportResult"]["COUNCIL"]["REGISTRO"];
}

function raw_sales_summ($filiacao, $usr, $pwd)
{
	$args = array(
		"Filiacao"		=> $filiacao,
		"Usr" 			=> $usr,
		"Pwd"			=> $pwd,
	);
	$result = extract_data(server_call("SalesSumm", $args));
	return	
		empty($result["SalesSummResponse"]["SalesSummResult"]["REPORT"]["root"])?
		$result:
		$result["SalesSummResponse"]["SalesSummResult"]["REPORT"]["root"];
}

function raw_void_conf_pre_authorization(
	$filiacao, $total, $parcelas, 
	$data, $num_autorizacao, $nsu, $concentrador, 
	$usr, $pwd
)
{
	$args = array(
		"Filiacao"	=> $filiacao,
		"Total"		=> $total,
		"Parcelas"	=> $parcelas,
		"Data"		=> $data,
		"NumAutor"	=> $num_autorizacao,
		"NumCV"		=> $nsu,
		"Concentrador" => $concentrador,
		"Usr" 		=> $usr, 
		"Pwd"		=> $pwd,
	);
	$result = extract_data(server_call("VoidConfPreAuthorization", $args));
	return	normalize_return($result["VoidConfPreAuthorizationResponse"]["VoidConfPreAuthorizationResult"]["CONFIRMATION"]["root"]);
}

function raw_conf_pre_authorization(
	$filiacao, $distribuidor, $total, $parcelas, 
	$data, $num_autorizacao, $nsu, $concentrador, 
	$usr, $pwd
)
{
	$args = array(
		"Filiacao"	=> $filiacao,
		"Distribuidor" => $distribuidor, 
		"Total"		=> $total,
		"Parcelas"	=> $parcelas,
		"Data"		=> $data,
		"NumAutor"	=> $num_autorizacao,
		"NumCV"		=> $nsu,
		"Concentrador" => $concentrador,
		"Usr" 		=> $usr, 
		"Pwd"		=> $pwd,
	);
	$result = extract_data(server_call("ConfPreAuthorization", $args));
	return	normalize_return($result["ConfPreAuthorizationResponse"]["ConfPreAuthorizationResult"]["CONFIRMATION"]["root"]);
}

function raw_void_pre_authorization(
	$filiacao, $distribuidor, $total, 
	$data, $num_autorizacao, $nsu, $concentrador, 
	$usr, $pwd
)
{
	$args = array(
		"Filiacao"	=> $filiacao,
		"Distribuidor" => $distribuidor, 
		"Total"		=> $total,
		"Data"		=> $data,
		"NumAutor"	=> $num_autorizacao,
		"NumCV"		=> $nsu,
		"Concentrador" => $concentrador,
		"Usr" 		=> $usr, 
		"Pwd"		=> $pwd,
	);
	$result = extract_data(server_call("VoidPreAuthorization", $args));
	return	normalize_return($result["VoidPreAuthorizationResponse"]["VoidPreAuthorizationResult"]["CONFIRMATION"]["root"]);
}

function raw_get_authorized_avs(
	$total, $transacao, $parcelas, $filiacao,
	$id_pedido, $cartao, $cvc, $mes, 
	$ano, $portador, $cpf, $endereco, 
	$num1, $complemento, $cep1, $cep2, 
	$iata, $distribuidor, $concentrador, $taxa_embarque, 
	$entrada, $numdoc1, $numdoc2, $numdoc3, 
	$numdoc4, $pax1, $pax2, $pax3, 
	$pax4
)
{
	$args = array(
		"Filiacao"	=> $filiacao,
		"Total"		=> $total,
		"Transacao"	=> $transacao,
		"Parcelas"	=> $parcelas,
		"NumPedido"	=> $id_pedido,
		"Nrcartao"	=> $cartao,
		"CVC2"		=> $cvc,
		"Mes"		=> $mes,
		"Ano"		=> $ano,
		"Portador"	=> $portador,
		"ConfTxn"	=> $conftxn,
		"IATA"		=> $iata, 
		"Distribuidor" => $distribuidor, 
		"Concentrador" => $concentrador,
		"TaxaEmbarque" => $taxa_embarque, 
		"Entrada"	=> $entrada,
		"Pax1"		=> $pax1,
		"Pax2"		=> $pax2,
		"Pax3"		=> $pax3,
		"Pax4"		=> $pax4,
		"Numdoc1"	=> $numdoc1,
		"Numdoc2"	=> $numdoc2,
		"Numdoc3"	=> $numdoc3,
		"Numdoc4"	=> $numdoc4,
		"Add_Data"	=> $add_data,
		"CPF"		=> $cpf,
		"Endereco"	=> $endereco,
		"Num1"		=> $num1,
		"Complemento"	=> $complemento,
		"Cep1"		=> $cep1,
		"Cep2"		=> $cep2,
	);
	$result = extract_data(server_call("GetAuthorizedAVS", $args));
	return	normalize_return($result["GetAuthorizedAVSResponse"]["GetAuthorizedAVSResult"]["AUTHORIZATION"]);
}

function raw_void_transaction(
	$nsu, $num_autorizacao, $total, $filiacao, 
	$usr, $pwd, $concentrador
)
{
	$args = array(
		"Total"		=> $total,
		"Filiacao"	=> $filiacao,
		"NumCV"		=> $nsu,
		"NumAutor"	=> $num_autorizacao,
		"Concentrador" => $concentrador,
		"Usr" 		=> $usr, 
		"Pwd"		=> $pwd,
	);
	$result = extract_data(server_call("VoidTransaction", $args));
	return	normalize_return($result["VoidTransactionResponse"]["VoidTransactionResult"]["CONFIRMATION"]["root"]);
}

function raw_get_authorized(
	$filiacao, $total, $transacao, $parcelas, 
	$id_pedido, $cartao, $cvc, $mes, 
	$ano, $portador, $conftxn, $iata,
	$distribuidor, $concentrador, $taxa_embarque, $entrada,
	$pax1, $pax2, $pax3, $pax4, 
	$numdoc1, $numdoc2, $numdoc3, $numdoc4, 
	$add_data
)
{
	$args = array(
		"Filiacao"	=> $filiacao,
		"Total"		=> $total,
		"Transacao"	=> $transacao,
		"Parcelas"	=> $parcelas,
		"NumPedido"	=> $id_pedido,
		"Nrcartao"	=> $cartao,
		"CVC2"		=> $cvc,
		"Mes"		=> $mes,
		"Ano"		=> $ano,
		"Portador"	=> $portador,
		"ConfTxn"	=> $conftxn,
		"IATA"		=> $iata, 
		"Distribuidor" => $distribuidor, 
		"Concentrador" => $concentrador,
		"TaxaEmbarque" => $taxa_embarque, 
		"Entrada"	=> $entrada,
		"Pax1"		=> $pax1,
		"Pax2"		=> $pax2,
		"Pax3"		=> $pax3,
		"Pax4"		=> $pax4,
		"Numdoc1"	=> $numdoc1,
		"Numdoc2"	=> $numdoc2,
		"Numdoc3"	=> $numdoc3,
		"Numdoc4"	=> $numdoc4,
		"Add_Data"	=> $add_data,
	);
	$result = extract_data(server_call("GetAuthorized", $args));
	return	normalize_return($result["GetAuthorizedResponse"]["GetAuthorizedResult"]["AUTHORIZATION"]);
}

function raw_comprovante($data, $nsu, $num_autorizacao, $filiacao)
{
	$args = array(
		"DATA"		=> $data,
		"TRANSACAO"	=> "201",
		"NUMCV"		=> $nsu,
		"NUMAUTOR"	=> $num_autorizacao,
		"FILIACAO"	=> $filiacao,
	);
	
	// chamada à url
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "https://ecommerce.redecard.com.br/pos_virtual/cupom.asp" );
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$html_cupom = curl_exec($curl);
	curl_close($curl);
	
	// corrige html
	$html_cupom = str_replace("Imagens/", "https://ecommerce.redecard.com.br/pos_virtual/Imagens/", $html_cupom);
	$html_cupom = str_replace("Css/", "https://ecommerce.redecard.com.br/pos_virtual/Css/", $html_cupom);
	$html_cupom = str_replace("Cupom.aspx", "https://ecommerce.redecard.com.br/pos_virtual/Cupom.aspx", $html_cupom);
	
	// retorna
	return	$html_cupom;
}


/***
 *** FUNÇÕES QUE EMULAM O ACESSO DIRETO AO SITE DA REDECARD (HACKS)
 ***/
 
function hack_login($filiacao, $usuario, $senha)
{
	$url = "https://portal.redecard.com.br/sites/fechado/_layouts/loginPageRedecard.aspx?estabelecimento=1&ncadastro=$filiacao&usuario=$usuario&senha=$senha";
	http_read($url, "");
	return	count($_SESSION["_komerci_cookie"]);
}

function hack_movimentacao_vendas($data_inicial, $data_final)
{
	$url  = "https://services.redecard.com.br";
	$url .= "/novoportal/portals/servicoSharepoint/extratoonline/IS_ExtratosRedecard_Extrato.aspx";
	$url .= "?BankLine=&txtnu_pdv=&box_sel=010&data_inicial=$data_inicial&data_final=$data_final&moeda=R&Selpvs=0&Box=010&flgTrava=0";
	$html = http_read($url, "");
	
	// extrai info do html
	$ok = preg_match('#table class="frm_INS"([^>]*)>(.*?)</table>#s', $html, $matches); // somente a tabela
	if (!$ok) {
		return	array();
	}
	$html = preg_replace("#(<b>|</b>|<a.*?>|</a>|<font.*?>|</font>)#s", "", $matches[2]); // retira formatação
	$dados = extract_data($html);
	$dados = array_map(create_function('$i', 'return $i["td"];'), $dados["tr"]);
	
	// header / resumo
	$header = array_shift($dados);
	$resumo = array();
	for ($i = 0; $i <= 2; $i++) 
	{
		$resumo_ = array_pop($dados);
		$nome = array_pop(explode(" ", $resumo_[0]));
		$resumo[$nome]["VALOR_LIQUIDO"] = str_replace(",", ".", str_replace(".", "", array_pop($resumo_))) + 0;
		$resumo[$nome]["DESCONTO_TAXAS"] = str_replace(",", ".", str_replace(".", "", array_pop($resumo_))) + 0;
		$resumo[$nome]["VALOR_CORRECOES"] = str_replace(",", ".", str_replace(".", "", array_pop($resumo_))) + 0;
		$resumo[$nome]["VALOR_VENDAS"] = str_replace(",", ".", str_replace(".", "", array_pop($resumo_))) + 0;
	}
	
	// normaliza data (p/ iso), números
	foreach ($dados as $k => $v) 
	{
		$dados[$k][0] = \DateTime::createFromFormat("d/m/Y", $v[0])->format("Ymd");
		$dados[$k][1] = \DateTime::createFromFormat("d/m/Y", $v[1])->format("Ymd");
		$dados[$k][7] = str_replace(",", ".", str_replace(".", "", $v[7])) + 0;
		$dados[$k][8] = str_replace(",", ".", str_replace(".", "", $v[8])) + 0;
		$dados[$k][9] = str_replace(",", ".", str_replace(".", "", $v[9])) + 0;
		$dados[$k][10] = str_replace(",", ".", str_replace(".", "", $v[10])) + 0;
	}
	
	// fim
	return array(
		"resumo"	=> $resumo,
		"cabecalho" => $header,
		"dados"		=> $dados,
	);
}


/***
 *** FUNÇÕES PARA O USUÁRIO
 ***/

function paga_direto($id_pedido, $total, $parcelas, $cartao, $cvc, $mes, $ano, $portador)
{
	global $_komerci_config;
	
	// à vista ou parcelado?
	list($transacao, $parcelas) = 
		($parcelas <= 1)?
		array("04", "00"): // a vista
		array( // parcelado
			$_komerci_config["parcelado_emissor"]? "06": "08", 
			"0". substr(($parcelas + 0), -1)
		);

	// faz a chamada e retorna o resultado
	$result = raw_get_authorized(
		$_komerci_config["filiacao"], number_format($total, 2, ".", ""), 
		$transacao, $parcelas, 
		$id_pedido, preg_replace("/[^0-9]/", "", $cartao),
		preg_replace("/[^0-9]/", "", $cvc), ($mes + 0 < 10)? "0". $mes: $mes,
		($ano + 0), strtoupper(remove_accents($portador)), 
		($_komerci_config["duas_etapas"]? "N": "S"), "",
		"", "", "", "",
		"", "", "", "",
		"", "", "", "",
		""
	);
	if ((!$_komerci_config["duas_etapas"]) OR ($result["CODRET"] != 0)) {
		return	$result;
	}
	
	// chamada em 2 etapas
	$result_txn = raw_confirm_txn(
		$result["DATA"], $result["NUMSQN"], $result["NUMCV"], $result["NUMAUTOR"],
		$parcelas, $transacao, number_format($total, 2, ".", ""), $_komerci_config["filiacao"], 
		"", $id_pedido, "", "", 
		"", "", "", "",
		"", ""
	);
	return	array_merge($result, $result_txn);
}

function estorna($nsu, $num_autorizacao, $total)
{
	global $_komerci_config;
	return	raw_void_transaction(
		$nsu, $num_autorizacao, 
		number_format($total, 2, ".", ""), $_komerci_config["filiacao"], 
		$_komerci_config["usuario"], $_komerci_config["senha"], 
		""
	);
}

function comprovante($nsu, $num_autorizacao, $data)
{
	global $_komerci_config;
	return	raw_comprovante($data, $nsu, $num_autorizacao, $_komerci_config["filiacao"]);
}


/***
 *** FUNÇÕES AUXILIARES
 ***/
 
function array_combine_($keys, $values)
{
	$result = array();
	foreach ($keys as $i => $k) {
		$result[$k][] = $values[$i];
	}
	array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
	return	$result;
}
 
function extract_data($str)
{
	return
		(is_array($str))?
		array_map(__NAMESPACE__. '\extract_data', $str):
		((!preg_match_all('#<([A-Za-z0-9]*)(?:\s+[^>]+)?>(.*?)</\1>#s', $str, $matches))? 
		$str:
		array_map((__NAMESPACE__. '\extract_data'), array_combine_($matches[1], $matches[2])));
}

function remove_accents($str)
{ 
	$from = array(
		"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", 
		"ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", 
		"Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", 
		"Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç"
	); 
	$to = array(
		"a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", 
		"o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", 
		"A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", 
		"O", "O", "U", "U", "U", "U", "C"
	);
	return	str_replace($from, $to, $str); 
}

function http_read($url, $raw_post_data)
{
	// headers
	$headers = array(
		"Content-Type: text/xml; charset=utf-8",
		"Expect: ",
	);

	// cookie
	if (session_id() == "") {
		session_start();
	}
	if (!array_key_exists("_komerci_cookie_", $_SESSION)) {
		$_SESSION["_komerci_cookie_"] = array();
	}
	if (count($_SESSION["_komerci_cookie_"])) 
	{
		$array = array_map(
			create_function('$k, $v', 'return "$k=$v";'), 
			array_keys($_SESSION["_komerci_cookie_"]), 
			array_values($_SESSION["_komerci_cookie_"])
		);
		$headers[] = "Cookie: ". implode("; ", $array);
	}
	
	// server comunication
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);	
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_HEADER, true); 
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $raw_post_data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 120);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($curl);
	curl_close($curl);

	// head + body split
	list($head, $body) = explode("\r\n\r\n", $result, 2);

	// cookies
	if (preg_match_all('#Set-Cookie: ([^=]*)=([^;]*);.*#', $head, $matches)) {
		$_SESSION["_komerci_cookie_"] = array_merge($_SESSION["_komerci_cookie_"], array_combine($matches[1], $matches[2]));
	}
	
	// returns the body
	return	$body;
}

function normalize_return($array)
{
	$array = array_change_key_case($array, CASE_UPPER);
	if (!array_key_exists("CODRET", $array)) {
		return	array("CODRET" => -1, "MSGRET" => "Falha ao se comunicar com a operadora do cartão");
	}
	$array["MSGRET"] = iconv("CP1252", "UTF-8", urldecode($array["MSGRET"]));
	return	$array;
}
?>