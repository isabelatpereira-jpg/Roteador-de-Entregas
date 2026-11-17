<?php
require_once "funcoes.php";

function lerPedidos()  {
    $pedidos = array();

    foreach (listar("pedidos.txt") as $linhas) {
        $partes = explode(";", $linhas);

        $pedidos[] = array(
            "id"          => $partes[0],
            "x"           => (int)$partes[3],
            "y"           => (int)$partes[4],
            "prioridade"  => $partes[5],
            "status"      => $partes[6] ?? ""
         );
    }
         return $pedidos;
}
?>
