<?php
include './komerci.php';
include "./user-komerci.php";

use komerci\UserKomerci;

// Exemplo de uso para ambiente de teste
// Colocar número da filiação válido
$komerci = new UserKomerci("047506289","testews","testews",true,true,false);

$paga_direto = $komerci->paga_direto("1234", "0.01", "00", "4539372053217316", "517", "10", "2017", "Bla");
var_dump($paga_direto);

echo "<br />";
echo "<br />";
echo "<br />";

$estorna = $komerci->estorna($paga_direto['NUMCV'], $paga_direto['NUMAUTOR'], '0.01');
var_dump($estorna);