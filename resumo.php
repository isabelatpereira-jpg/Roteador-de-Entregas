<?php
function lerPedidos() {
    $pedidos = array();

    $linhas = array("pedidos.txt");

    foreach($linhas as $linha) {
        $linha = trim($linha);

        if ($linha !="")
            $partes = explode(",", $linha);

        $pedido = array(
            "id"     => $partes[01],
            "status" => $partes[02],
            "linha"  => $partes[03],
            "coluna" => $partes[04],
        );

        $pedidos[] = $pedido;

    }
}

$entregadores = array(
array("nome" =>"", ""=> true),
array("nome"=> "", ""=> false),
array("nome"=> "","" => true),  


);
?>