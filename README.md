#php-komerci
[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/d27098cf2e9f554fc7fb6a8beb98b719 "githalytics.com")](http://githalytics.com/loureirorg/php-komerci)
Biblioteca PHP para acesso à plataforma Komerci da Redecard

##3D Secure
dúvidas sobre o 3D Secure, use a [issue #1](https://github.com/loureirorg/php-komerci/issues/1)

##Colabore
se você tem alguma dúvida, crítica, pedido ou sugestão de melhoria, use a seção "Issues" do GitHub: https://github.com/loureirorg/php-komerci/issues

##Funções:
Existem 3 grupos de funções:
 * **funções raw**: é uma implementação "crua" da API, sem nenhum tratamento;
 * **funções hack**: são funções que acessam diretamente o site (simulando um usuário normal) para conseguirmos algumas funções não disponíveis na API (ex. movimentação de vendas)
 * **funções de usuário**: são funções de alto nível que utilizam internamente as funções raw. Preferencialmente utilize apenas estas;

##Exemplo de uso da biblioteca para impressão de cupom:
```php
<?php
include "komerci.php";
use komerci;

komerci\config("filiacao", "012345678");
komerci\config("senha", "minha senha secreta");

print_r(komerci\comprovante("123456789", "123456", "20121031"));
?>
```

##Lista de funções "raw":
  * **raw_confirm_txn**($data, $num_seq, $nsu, $num_autorizacao, 
      $parcelas, $transacao, $total, $filiacao, 
      $distribuidor, $id_pedido, $pax1, $pax2, 
      $pax3, $pax4, $numdoc1, $numdoc2, 
      $numdoc3, $numdoc4)
  * **raw_council_report**($filiacao, $usr, $pwd, $distribuidor, 
      $data_inicial, $data_final, $tipo_trx, $status_trx, 
      $servico_avs)
  * **raw_sales_summ**($filiacao, $usr, $pwd)
  * **raw_void_conf_pre_authorization**($filiacao, $total, $parcelas, 
      $data, $num_autorizacao, $nsu, $concentrador, 
      $usr, $pwd)
  * **raw_conf_pre_authorization**($filiacao, $distribuidor, $total, $parcelas, 
      $data, $num_autorizacao, $nsu, $concentrador, 
      $usr, $pwd)
  * **raw_void_pre_authorization**($filiacao, $distribuidor, $total, 
      $data, $num_autorizacao, $nsu, $concentrador, 
      $usr, $pwd)
  * **raw_get_authorized_avs**($total, $transacao, $parcelas, $filiacao,
      $id_pedido, $cartao, $cvc, $mes, 
      $ano, $portador, $cpf, $endereco, 
      $num1, $complemento, $cep1, $cep2, 
      $iata, $distribuidor, $concentrador, $taxa_embarque, 
      $entrada, $numdoc1, $numdoc2, $numdoc3, 
      $numdoc4, $pax1, $pax2, $pax3, 
      $pax4)
  * **raw_void_transaction**($nsu, $num_autorizacao, $total, $filiacao, $usr, $pwd, $concentrador)
  * **raw_get_authorized**($filiacao, $total, $transacao, $parcelas, $id_pedido, $cartao, $cvc, $mes, $ano, $portador, $conftxn, $iata, $distribuidor, $concentrador, $taxa_embarque, $entrada, $pax1, $pax2, $pax3, $pax4, $numdoc1, $numdoc2, $numdoc3, $numdoc4, $add_data)
  * **raw_comprovante**($data, $nsu, $num_autorizacao, $filiacao)

##Lista de funções "hack":
  * **hack_login**($filiacao, $usuario, $senha)
  * **hack_movimentacao_vendas**($data_inicial, $data_final)
  
##Lista de funções do usuário:
  * **config**($chave, $valor)
  * **paga_direto**($id_pedido, $total, $parcelas, $cartao, $cvc, $mes, $ano, $portador)
  * **estorna**($nsu, $num_autorizacao, $total)
  * **comprovante**($nsu, $num_autorizacao, $data)
  
##Opções que podem ser utilizadas na função config:
  * **filiacao**(string): seu número de filiacao com a redecard. Se tiver "0" na frente, deve ser informado;
  * **usuario**(string): seu usuário webservice do pagseguro. Normalmente é a filiação sem o 0 na frente;
  * **senha**(string): sua senha de webservice;
  * **parcelamento_emissor**(bool): quando o pagamento é parcelado, indica se será parcelado pelo estabelecimento (sem juros, recebendo de acordo com as parcelas) ou pelo emissor (juros pago pelo cliente, com recebimento em 30 dias independente do número de parcelas);
  * **duas_etapas**(bool): sempre deixe em true, a menos que esteja enfrentando problemas;
 
*PS: as opções são utilizadas somente pelas funções de usuário, não afetando o comportamento das demais funções.*
