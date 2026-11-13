<?php
function salvar($arquivo, $campos) {
    // troca ; por , para não quebrar a separação das colunas
    $campos = array_map(function ($c) {
        return str_replace([";", "\n", "\r"], [",", " ", " "], $c);
    }, $campos);
    file_put_contents($arquivo, implode(";", $campos) . "\n", FILE_APPEND);
}

function listar($arquivo) {
    if (!file_exists($arquivo)) {
        return [];
    }
    return file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

function lerLinhas(string $arq): array {
    return file_exists($arq)
        ? file($arq, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        : [];
}


