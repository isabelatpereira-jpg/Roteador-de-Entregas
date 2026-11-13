<?php
$fila = [
    ['codigo' => 'TRK-1042', 'destinatario' => 'Hotel Mariana', 'endereco' => 'Av. Paulista, 900'],
    // um array por pedido
];

function buscarPedido($fila, $codigo) {
  //comentario
    $comparacoes = 0;
    foreach ($fila as $i => $pedido) {
        $comparacoes++;
        if ($pedido['codigo'] == $codigo) {
            return ['pedido' => $pedido, 'posicao' => $i + 1, 'comparacoes' => $comparacoes];
        }
    }
    return null;
}

$cod = '';
$resultado = null;

if (isset($_GET['cod'])) {
    $cod = strtoupper(trim($_GET['cod']));
    $resultado = buscarPedido($fila, $cod);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Busca · Roteador de Entregas</title>
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
      <a href="mapa.php">🗺️ Mapa</a>
      <a href="busca.php" class="ativo">🔎 Busca</a>
      <a href="estatisticas.html">📈 Estatísticas</a>
      <a href="login.html" class="sair">⏻ Sair</a>
    </nav>
    <div class="side-foot"><span class="dot ok"></span> Central online · Turno tarde</div>
  </aside>

  <main>
    <header class="topbar">
      <div class="titulo">
        <h1>Busca e ordenação</h1>
        <p>Busca linear por rastreio · ordenação por prioridade e distância</p>
      </div>
      <div class="user">
        <span class="avatar">CS</span>
      </div>
    </header>

    <div class="grid g2">

      <!-- BUSCA -->
      <section class="card">
        <div class="card-head">
          <h2>🔎 Buscar entrega por rastreio</h2>
          <span class="tag">busca linear</span>
        </div>

        <form class="form" action="busca.php" method="get">
          <div class="busca-linha">
            <div class="fld">
              <label for="cod">Código de rastreio</label>
              <input type="text" id="cod" name="cod" class="mono">
            </div>
            <button type="submit" class="btn btn-pri">🔎 Buscar</button>
          </div>
        </form>

        <?php if ($cod != '') { ?>
        <div class="card card-resultado">
          <div class="card-head">
            <h2>✅ Resultado da busca</h2>
            <?php if ($resultado != null) { ?>
              <span class="tag">encontrado · <?php echo $resultado['comparacoes']; ?> comparação(ões)</span>
            <?php } else { ?>
              <span class="tag">não encontrado</span>
            <?php } ?>
          </div>
          <?php if ($resultado != null) { ?>
            <div class="res-linha"><span>Código</span><b class="mono"><?php echo htmlspecialchars($resultado['pedido']['codigo']); ?></b></div>
            <div class="res-linha"><span>Posição na fila</span><b><?php echo $resultado['posicao']; ?>º</b></div>
            <div class="res-linha"><span>Destinatário</span><b><?php echo htmlspecialchars($resultado['pedido']['destinatario']); ?></b></div>
            <div class="res-linha"><span>Endereço</span><b><?php echo htmlspecialchars($resultado['pedido']['endereco']); ?></b></div>
          <?php } ?>
        </div>
        <?php } ?>
      </section>

      <!-- ORDENAÇÃO -->
      <section class="card">
        <div class="card-head">
          <h2>🔤 Ordenar fila por prioridade</h2>
          <span class="tag">Alta → Baixa</span>
        </div>
        <ul class="fila"></ul>

        <div class="card-head card-head-sec">
          <h2>📐 Ordenar por distância</h2>
          <span class="tag">mais perto primeiro</span>
        </div>
        <ul class="fila"></ul>
      </section>

    </div>

    <footer class="rodape">
      Roteador de Entregas · Frota Urbana ·  Logística 
    </footer>
  </main>
</div>
</body>
</html>