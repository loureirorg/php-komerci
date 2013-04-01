<?php
include "komerci.php";
use komerci;

komerci\config("filiacao", "012345678");
komerci\config("senha", "minha senha secreta");

print_r(komerci\comprovante("123456789", "123456", "20121031"));
?>