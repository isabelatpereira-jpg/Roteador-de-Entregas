<?php
require "resumo.php";
$pedidos = lerPedidos();
$entregadores = lerEntregadores();

$naFila = 0;
$concluidas = 0;
foreach ($pedidos as $p) {
  if ($p["status"] === "") {
    $naFila++;
  } elseif ($p["status"] === "#ok") {
    $concluidas++;
  }
}
$total = count($pedidos);
$pct = $total > 0 ? round($concluidas * 100 / $total) : 0;

$emRota = 0;
$disponiveis = 0;
foreach ($entregadores as $e) {
  if ($e["status"] === "Em Rota") {
    $emRota++;
  } elseif ($e["status"] === "Disponível") {
    $disponiveis++;
  }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel · Roteador de Entregas</title>
  <link rel="icon"
    href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛵</text></svg>">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div class="app">

    <aside class="sidebar">
      <a class="logo" href="painel.php">
        <span class="logo-ic">🛵</span>
        <span class="logo-txt">Roteador<b>Entregas</b></span>
      </a>
      <nav class="nav" aria-label="Navegação principal">
        <a href="painel.php" class="ativo">📊 Painel</a>
        <a href="pedidos.php">📥 Pedidos</a>
        <a href="entregadores.php">🛵 Entregadores</a>
        <a href="despacho.php">🚀 Despacho</a>
        <a href="mapa.php">🗺️ Mapa</a>
        <a href="busca.php">🔎 Busca</a>
        <a href="login.html" class="sair">⏻ Sair</a>
      </nav>
      <div class="side-foot"><span class="dot ok"></span> Central online · Turno tarde</div>
    </aside>

    <main>
      <header class="topbar">
        <div class="titulo">
          <h1>Painel de controle</h1>
          <p>Visão geral da central de despacho</p>
        </div>
        <div class="user">
          <span class="avatar">CS</span>
        </div>
      </header>

      <section class="kpis">
        <article class="card kpi">
          <span class="kpi-top">📦 Pedidos na fila</span>
          <b><?= $naFila?></b>
          <small><?= $total?></small>
        </article>
        <article class="card kpi">
          <span class="kpi-top">🚀 Em rota agora</span>
          <b><?= $emRota?></b>
          <small>Entregador(es)</small>
        </article>
        <article class="card kpi">
          <span class="kpi-top">✅ Concluídas hoje</span>
          <b><?= $concluidas?></b>
          <small><?= $pct?>% dos pedidos</small>
        </article>
        <article class="card kpi">
          <span class="kpi-top">🛵 Disponíveis</span>
          <b><?= $disponiveis?><small class="kpi-total">/<?= count ($entregadores) ?></small></b>
          <small> Entregadores cadastrados</small>
        </article>
      </section>

      <div class="grid g-21">
        <section class="card">
          <div class="card-head">
            <h2>📥 Fila de despacho</h2>
            <a class="card-link" href="pedidos.php">Fila completa →</a>
          </div>
          <ul class="fila">
            <?php foreach ($pedidos as $p) { ?>
              <?php if ($p["status"] === "") {  ?>
                <li><?= $p["id"] ?> · <?= $p["endereco"] ?> · <?= $p["prioridade"] ?></li> 
              <?php } ?>
            <?php } ?>
          </ul>
        </section>

        <section class="card">
          <div class="card-head">
            <h2>🛵 Frota agora</h2>
            <a class="card-link" href="entregadores.php">Gerenciar →</a>
          </div>
          <ul class="frota"> 
            <?php foreach ($entregadores as $e) { ?>
                <li><?= $e["nome"] ?>  -  <label style="color: green;"><b><?= $e["status"] ?></b></label></li>
              <?php } ?>
          </ul>
        </section>
      </div>

      <div class="grid g-21">
        <section class="card">
          <div class="card-head">
            <h2>🗺️ Mapa · matriz 10×10</h2>
            <a class="card-link" href="mapa.php">Ver mapa →</a>
          </div>
          <div class="mapa-box">
            <div class="eixo-y" aria-hidden="true">
              <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span>
            </div>
            <div class="mapa"></div>
            <div class="eixo-x" aria-hidden="true">
              <span>A</span><span>B</span><span>C</span><span>D</span><span>E</span><span>F</span><span>G</span><span>H</span><span>I</span><span>J</span>
            </div>
          </div>
          <div class="legenda">
            <span><i class="chip chip-acc"></i> Central</span>
            <span><i class="chip chip-info"></i> Entregador</span>
            <span><i class="chip chip-ok"></i> Ponto de entrega</span>
          </div>
        </section>

      </div>

      <footer class="rodape">
        Roteador de Entregas · Frota Urbana · Logística
      </footer>
    </main>
  </div>
</body>

</html>