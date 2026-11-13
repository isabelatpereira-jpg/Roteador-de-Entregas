<?php
// ---------- DADOS ----------
$fila = [
    ['codigo' => 'TRK-1042', 'x' => 4, 'y' => 2],
    // um array por pedido
];

$entregadores = [
    ['nome' => 'Ana', 'codigo' => 'M-002', 'x' => 7, 'y' => 8],
    // um array por entregador
];

$centralX = 1;
$centralY = 1;
$letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

// ---------- FUNÇÃO ----------
function distancia($x1, $y1, $x2, $y2) {
    return abs($x1 - $x2) + abs($y1 - $y2);
}

$totalMarcadores = 1 + count($fila) + count($entregadores);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mapa · Roteador de Entregas</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛵</text></svg>">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app">

  <aside class="sidebar">
    <a class="logo" href="painel.html">
      <span class="logo-ic">🛵</span>
      <span class="logo-txt">Roteador<b>Entregas</b></span>
    </a>
    <nav class="nav" aria-label="Navegação principal">
      <a href="painel.html">📊 Painel</a>
      <a href="pedidos.php">📥 Pedidos</a>
      <a href="entregadores.php">🛵 Entregadores</a>
      <a href="despacho.php">🚀 Despacho</a>
      <a href="mapa.php" class="ativo">🗺️ Mapa</a>
      <a href="busca.php">🔎 Busca</a>
      <a href="estatisticas.html">📈 Estatísticas</a>
      <a href="login.html" class="sair">⏻ Sair</a>
    </nav>
    <div class="side-foot"><span class="dot ok"></span> Central online · Turno tarde</div>
  </aside>

  <main>
    <header class="topbar">
      <div class="titulo">
        <h1>Mapa da cidade</h1>
        <p>Matriz 10×10 · linha = Y · coluna = X · central em Q(1,1)</p>
      </div>
      <div class="user">
        <span class="tag"><?php echo $totalMarcadores; ?> marcadores</span>
        <span class="avatar">CS</span>
      </div>
    </header>

    <div class="grid g-21">

      <!-- MAPA -->
      <section class="card">
        <div class="card-head">
          <h2>🗺️ Quadrantes monitorados</h2>
          <span class="tag">matriz[$y][$x]</span>
        </div>
        <div class="mapa-box">
          <div class="eixo-y" aria-hidden="true"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span></div>
          <div class="mapa">
            <?php
            // central
            $left = ($centralX - 1) * 10 + 5;
            $top = ($centralY - 1) * 10 + 5;
            echo '<span class="mk mk-central" style="left:' . $left . '%;top:' . $top . '%">🏢<i>Central · Q(' . $centralX . ',' . $centralY . ')</i></span>';

            // pedidos
            foreach ($fila as $pedido) {
                $left = ($pedido['x'] - 1) * 10 + 5;
                $top = ($pedido['y'] - 1) * 10 + 5;
                echo '<span class="mk mk-entrega" style="left:' . $left . '%;top:' . $top . '%">📦<i>' . htmlspecialchars($pedido['codigo']) . '</i></span>';
            }

            // entregadores
            foreach ($entregadores as $entregador) {
                $left = ($entregador['x'] - 1) * 10 + 5;
                $top = ($entregador['y'] - 1) * 10 + 5;
                echo '<span class="mk mk-moto" style="left:' . $left . '%;top:' . $top . '%">🛵<i>' . htmlspecialchars($entregador['nome']) . ' · ' . htmlspecialchars($entregador['codigo']) . '</i></span>';
            }
            ?>
          </div>
          <div class="eixo-x" aria-hidden="true"><span>A</span><span>B</span><span>C</span><span>D</span><span>E</span><span>F</span><span>G</span><span>H</span><span>I</span><span>J</span></div>
        </div>
        <div class="legenda">
          <span><i class="chip chip-acc"></i> Central de despacho</span>
          <span><i class="chip chip-info"></i> Entregador em rota</span>
          <span><i class="chip chip-ok"></i> Ponto de entrega</span>
        </div>
      </section>

      <!-- PAINEL LATERAL -->
      <div class="col">
        <section class="card">     
            <?php
            echo '<div class="res-linha"><span>Q(' . $centralX . ',' . $centralY . ') · ' . $letras[$centralX - 1] . $centralY . '</span><b>🏢 Central</b></div>';

            foreach ($entregadores as $entregador) {
                $x = $entregador['x'];
                $y = $entregador['y'];
                echo '<div class="res-linha"><span>Q(' . $x . ',' . $y . ') · ' . $letras[$x - 1] . $y . '</span><b>🛵 ' . htmlspecialchars($entregador['nome']) . '</b></div>';
            }

            foreach ($fila as $pedido) {
                $x = $pedido['x'];
                $y = $pedido['y'];
                echo '<div class="res-linha"><span>Q(' . $x . ',' . $y . ') · ' . $letras[$x - 1] . $y . '</span><b>📦 ' . htmlspecialchars($pedido['codigo']) . '</b></div>';
            }
            ?>
          </div>
        </section>

        <section class="card">
          <div class="card-head">
            <div class="distancias">
            <?php
            foreach ($fila as $pedido) {
                $d = distancia($centralX, $centralY, $pedido['x'], $pedido['y']);
                echo '<div class="res-linha"><span>Central (' . $centralX . ',' . $centralY . ') → ' . htmlspecialchars($pedido['codigo']) . ' (' . $pedido['x'] . ',' . $pedido['y'] . ')</span><b class="mono">' . $d . ' quadras</b></div>';
            }
            ?>
          </div>
        </section>
      </div>
    </div>

    <footer class="rodape">
      Roteador de Entregas · Frota Urbana ·  Logística 
    </footer>
  </main>
</div>
</body>
</html>