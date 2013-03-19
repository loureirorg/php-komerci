[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/d27098cf2e9f554fc7fb6a8beb98b719 "githalytics.com")](http://githalytics.com/loureirorg/php-komerci)

Biblioteca PHP para acesso à plataforma Komerci da Redecard

Por enquanto apenas as seguintes funções estão disponíveis (vender, cancelar venda, visualizar cupom):
* GetAuthorized
* VoidTransaction
* Cupom

Exemplo de impressão de cupom:
```php
<?php
include("redecard.php");


print_r(Cupom(array(
	'Data' => '20121031', 
	'NumCV' => '123456789', 
	'NumAutor' => '123456', 
	'Filiacao' => '123456789' 
)));
?>
```