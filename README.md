#php-komerci
[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/d27098cf2e9f554fc7fb6a8beb98b719 "githalytics.com")](http://githalytics.com/loureirorg/php-komerci)
Biblioteca PHP para acesso à plataforma Komerci da Redecard

##Por enquanto apenas as seguintes funções estão disponíveis (vender, cancelar venda, visualizar cupom):
* GetAuthorized
* VoidTransaction
* Cupom

##Exemplo de impressão de cupom:
```php
<?php
include "komerci.php";
use komerci;

komerci\config("filiacao", "012345678");
komerci\config("senha", "minha senha secreta");

print_r(komerci\comprovante("123456789", "123456", "20121031"));
?>
```