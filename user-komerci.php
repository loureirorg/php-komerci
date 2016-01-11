<?php
/**
 * Classe com métodos de usuário
 */
namespace komerci;

use komerci\Komerci;

class UserKomerci extends Komerci{
    
    public function __construct($filiacao, $usuario, $senha, $parcelado_emissor, $duas_etapas = true, $producao = true)
    {
        parent::__construct($filiacao,$usuario,$senha,$parcelado_emissor,$duas_etapas,$producao);
    }
    
    public function paga_direto($id_pedido, $total, $parcelas, $cartao, $cvc, $mes, $ano, $portador)
    {
        // à vista ou parcelado?
        list($transacao, $parcelas) = 
            ($parcelas <= 1)?
            array("04", "00"): // a vista
            array( // parcelado
                $this->get_parcelado_emissor() ? "06": "08", 
                "0". substr(($parcelas + 0), -1)
            );

        // faz a chamada e retorna o resultado
        $result = $this->raw_get_authorized(
            number_format($total, 2, ".", ""), 
            $transacao, $parcelas, 
            $id_pedido, preg_replace("/[^0-9]/", "", $cartao),
            preg_replace("/[^0-9]/", "", $cvc), ($mes + 0 < 10)? "0". $mes: $mes,
            ($ano + 0), strtoupper($this->remove_accents($portador)), 
            ($this->get_duas_etapas() ? "N": "S"), "",
            "", "", "", "",
            "", "", "", "",
            "", "", "", "",
            ""
        );
        
        if ((!$this->get_duas_etapas()) OR ($result["CODRET"] != 0)) {
            return  $result;
        }

        // chamada em 2 etapas
        $result_txn = $this->raw_confirm_txn(
            $result["DATA"], $result["NUMSQN"], $result["NUMCV"], $result["NUMAUTOR"],
            $parcelas, $transacao, number_format($total, 2, ".", ""),
            "", $id_pedido, "", "", 
            "", "", "", "",
            "", ""
        );
        return  array_merge($result, $result_txn);
    }
    
    public function comprovante($nsu, $num_autorizacao, $data)
    {
        return $this->raw_comprovante($data, $nsu, $num_autorizacao);
    }
    
    public function estorna($nsu, $num_autorizacao, $total)
    {
        return  $this->raw_void_transaction($nsu,$num_autorizacao,$total,"");
    }

}