<?php
/*-------------------------------------------------------------------------
 *
 * komerci.php
 *      Biblioteca para acesso à plataforma Komerci da Redecard
 * 
 * Copyleft 2013 - Public Domain
 * Original Author: Daniel Loureiro
 *
 * version 1.2.2a @ 2013-03-30
 *
 * https://github.com/loureirorg/php-komerci
 *-------------------------------------------------------------------------
 *
 * ATENÇÃO: Antes de efetuar uma venda, é necessário configurar o tipo de 
 * parcelamento (emissor ou estabelecimento) através da função "config_set()",
 * opção "parcelado_emissor", com o valor "true" ou "false". Ex.:
 *
 *      komerci\config("parcelado_emissor", true);
 *
 * ATENÇÃO II: Lembre-se de liberar o IP do server juntamente com a Redecard,
 * caso contrário algumas funções retornarão erro 56 - "dados inválidos".
 *-------------------------------------------------------------------------
 *
 * CONFIGURAÇÕES POSSÍVEIS DE komerci\config()
 *
 *  * duas_etapas (bool): se "true", a função "paga_cartao()" realiza 2 
 *      chamadas ao Komerci - preferencialmente deixe como "true";
 *  * filiacao (string): seu número de filiação, coloque 0 na frente se 
 *      necessário;
 *  * usuario (string): um usuário cadastrado junto ao Komerci. Necessário para 
 *      cancelamento de venda e outras funções. O usuário master, utilizado para 
 *      acesso à página e que normalmente é o próprio número de filiação sem 
 *      zeros na frente NÃO é um usuário válido. Veja os usuários válidos 
 *      acessando a página da Redecard > Komerci > Usuários do Komerci
 *  * senha (string): sua senha de internet junto ao Komerci. Necessário para 
 *      cancelamento de venda;
 *  * parcelado_emissor (bool): ao fazer parcelado, informa se é parcelado pelo 
 *      emissor ou pelo estabelecimento;
 *-------------------------------------------------------------------------
 */
namespace komerci;

class Komerci{
    /**
     * Número de filiação
     * 
     * @String 
     */
    private $filiacao;
    
    /**
     * Usuário komerci
     * 
     * @String 
     */
    private $usuario;
    
    /**
     * Senha Komerci
     * 
     * @String
     */
    private $senha;
    
    /**
     * Ao fazer parcelado, informa se é parcelado pelo 
     * emissor ou pelo estabelecimento;
     * 
     * @bool
     */
    private $parcelado_emissor;
    
    /**
     * se "true", a função "paga_cartao()" realiza 2  
     * chamadas ao Komerci - preferencialmente deixe como "true"
     * 
     * @bool
     */
    private $duas_etapas;
    
    /**
     *  Indica se o script está em prudução ou desenvolvimento
     * 
     * @bool
     */
    private $producao;
    
    /**
     * Sufixo da url para requisição:
     * Se $producao == true $sufix_url = 'teste';
     *
     * @String
     */
    private $sufix_url = '';
    
    /**
     * Sufixo das funções
     * Se $producao == true $sufix_func = 'Tst';
     *
     * @String
     */
    private $sufix_func = '';
    
    public function __construct($filiacao, $usuario, $senha, $parcelado_emissor, $duas_etapas = true, $producao = true) 
    {
        $this->filiacao = $filiacao;
        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->parcelado_emissor = $parcelado_emissor;
        $this->duas_etapas = $duas_etapas;
        $this->producao = $producao;
        
        if(!$producao) {
            $this->sufix_url = '_teste';
            $this->sufix_func = 'Tst';
        }
    
    }
    
    protected function get_parcelado_emissor() 
    {
        return $this->parcelado_emissor;
    }
    
    protected function get_duas_etapas() 
    {
        return $this->duas_etapas;
    }
        
    /**
    * Responsável por enviar requisição ao servidor redecard
    * 
    * @param String $method Nome do método
    * @param Array $args Array com os parametros do método
    * @return Array Array com a resposta da requisição
    */
    protected function server_call($method, $args)
    {
        $method = $method.$this->sufix_func;
        // monta xml
        $url = "https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap".$this->sufix_url.".asmx";
        $xml_data = implode("", array_map(create_function('$key, $value', 'return "<$key>$value</$key>";'), array_keys($args), array_values($args)));
        $xml =  "<?xml version=\"1.0\" encoding=\"utf-8\"?>".
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
       // file_get_contents($xml); die();
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
        return  $result;
    }
    
    /**
     * @param String $data
     * @param String $num_seq
     * @param String $nsu
     * @param String $num_autorizacao
     * @param String $parcelas
     * @param String $transacao
     * @param String $total
     * @param String $distribuidor
     * @param String $id_pedido
     * @param String $pax1
     * @param String $pax2
     * @param String $pax3
     * @param String $pax4
     * @param String $numdoc1
     * @param String $numdoc2
     * @param String $numdoc3
     * @param String $numdoc4
     * @return Array
     */
    protected function raw_confirm_txn(
        $data, $num_seq, $nsu, $num_autorizacao, 
        $parcelas, $transacao, $total, $distribuidor, 
        $id_pedido, $pax1, $pax2, $pax3, $pax4, 
        $numdoc1, $numdoc2, $numdoc3, $numdoc4
    )
    {
        $args = array(
            "Data"      => $data,
            "NumSqn"    => $num_seq,
            "NumCV"     => $nsu,
            "NumAutor"  => $num_autorizacao,
            "Parcelas"  => $parcelas,
            "TransOrig" => $transacao,
            "Total"     => $total,
            "Filiacao"  => $this->filiacao,
            "Distribuidor" => $distribuidor, 
            "NumPedido" => $id_pedido,
            "Pax1"      => $pax1,
            "Pax2"      => $pax2,
            "Pax3"      => $pax3,
            "Pax4"      => $pax4,
            "Numdoc1"   => $numdoc1,
            "Numdoc2"   => $numdoc2,
            "Numdoc3"   => $numdoc3,
            "Numdoc4"   => $numdoc4,
        );
        $result = self::extract_data($this->server_call("ConfirmTxn", $args));
        return  $this->normalize_return($result, "CONFIRMATION", "ConfirmTxn");
    }
    
    /**
     * @param Float $total
     * @param String $transacao
     * @param String $parcelas
     * @param String $id_pedido
     * @param String $cartao
     * @param String $cvc
     * @param String $mes
     * @param String $ano
     * @param String $portador
     * @param String $conftxn
     * @param String $iata
     * @param String $distribuidor
     * @param String $concentrador
     * @param String $taxa_embarque
     * @param String $entrada
     * @param String $pax1
     * @param String $pax2
     * @param String $pax3
     * @param String $pax4
     * @param String $numdoc1
     * @param String $numdoc2
     * @param String $numdoc3
     * @param String $numdoc4
     * @param String $add_data
     * @return Array
     */
    protected function raw_get_authorized(
        $total, $transacao, $parcelas, 
        $id_pedido, $cartao, $cvc, $mes, 
        $ano, $portador, $conftxn, $iata,
        $distribuidor, $concentrador, $taxa_embarque, $entrada,
        $pax1, $pax2, $pax3, $pax4, 
        $numdoc1, $numdoc2, $numdoc3, $numdoc4, 
        $add_data
    )
    {
        $args = array(
            "Filiacao"  => $this->filiacao,
            "Total"     => $total,
            "Transacao" => $transacao,
            "Parcelas"  => $parcelas,
            "NumPedido" => $id_pedido,
            "Nrcartao"  => $cartao,
            "CVC2"      => $cvc,
            "Mes"       => $mes,
            "Ano"       => $ano,
            "Portador"  => $portador,
            "ConfTxn"   => $conftxn,
            "IATA"      => $iata, 
            "Distribuidor" => $distribuidor, 
            "Concentrador" => $concentrador,
            "TaxaEmbarque" => $taxa_embarque, 
            "Entrada"   => $entrada,
            "Pax1"      => $pax1,
            "Pax2"      => $pax2,
            "Pax3"      => $pax3,
            "Pax4"      => $pax4,
            "Numdoc1"   => $numdoc1,
            "Numdoc2"   => $numdoc2,
            "Numdoc3"   => $numdoc3,
            "Numdoc4"   => $numdoc4,
            "Add_Data"  => $add_data,
        );
        $result = self::extract_data($this->server_call("GetAuthorized", $args));
        return  $this->normalize_return($result, "AUTHORIZATION", "GetAuthorized");
    }
    
    /**
     *  Realizar o estorno de uma transação 
     * 
     * @param String $nsu Número do Comprovante de Venda (NSU)
     * @param String $num_autorizacao Número de Autorização
     * @param Float $total Valor total da compra
     * @param String $concentrador N/A – Enviar parâmetro com valor vazio
     * @return Array
     */    
    protected function raw_void_transaction($nsu, $num_autorizacao, $total, $concentrador)
    {
        $args = array(
            "Total"     => $total,
            "Filiacao"  => $this->filiacao,
            "NumCV"     => $nsu,
            "NumAutor"  => $num_autorizacao,
            "Concentrador" => $concentrador,
            "Usr"       => $this->usuario, 
            "Pwd"       => $this->senha,
        );
        $result = self::extract_data($this->server_call("VoidTransaction", $args));
        return $this->normalize_return($result,"CONFIRMATION","VoidTransaction","root");
    }
    
    /**
     * Solicita o comprovante
     * 
     * @param String $data Data da transação
     * @param String $nsu Número do Comprovante de Vendas
     * @param String $num_autorizacao Número da autorização
     * @return type
     */
    protected function raw_comprovante($data, $nsu, $num_autorizacao)
    {
        $args = array(
            "DATA"      => $data,
            "TRANSACAO" => "201",
            "NUMCV"     => $nsu,
            "NUMAUTOR"  => $num_autorizacao,
            "FILIACAO"  => $this->filiacao,
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
        return  $html_cupom;
    }
        
    /***
     *** MÉTODOS AUXILIARES
     ***/
    
    /**
     * Formata a resposta
     * 
     * @param Array $array 
     * @param String $response Tipo da resposta
     * @param String $method Método da resposta
     * @return Array
     */
    private function normalize_return($array, $response, $method, $root = '')
    {
        $array = $array[$method.$this->sufix_func."Response"][$method.$this->sufix_func."Result"][$response];
        
        if(!empty($root))
            $array = $array[$root];
        
        $array = array_change_key_case($array, CASE_UPPER);
        if (!array_key_exists("CODRET", $array)) {
            return  array("CODRET" => -1, "MSGRET" => "Falha ao se comunicar com a operadora do cartão");
        }
        $array["MSGRET"] = iconv("CP1252", "UTF-8", urldecode($array["MSGRET"]));
        return  $array;
    }
    
    protected function remove_accents($str)
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
        return  str_replace($from, $to, $str); 
    }
    
    private static function extract_data($str)
    {
        return
            (is_array($str))?
            array_map('self::extract_data', $str):
            ((!preg_match_all('#<([A-Za-z0-9]*)(?:\s+[^>]+)?>(.*?)</\1>#s', $str, $matches))? 
            $str:
            array_map(('self::extract_data'), self::array_combine_($matches[1], $matches[2])));
    }
    
    private static function array_combine_($keys, $values)
    {
        $result = array();
        foreach ($keys as $i => $k) {
            $result[$k][] = $values[$i];
        }
        array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
        return  $result;
    }
    
}