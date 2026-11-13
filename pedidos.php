<?php
require "funcoes.php";

function h($t) { return htmlspecialchars($t ?? ""); }

$pedidos = listar("pedidos.txt");
$proximo = "TRK-" . (1001 + count($pedidos));

// Se chegou o formulário (GET), salva e recarrega a página
if (isset($_GET['destinatario'])) {
    salvar("pedidos.txt", [
        $proximo,
        $_GET['destinatario'],
        $_GET['endereco'] ?? "",
        $_GET['qx'] ?? 1,
        $_GET['qy'] ?? 1,
        $_GET['prio'] ?? "media",
        $_GET['obs'] ?? ""
    ]);
    header("Location: pedidos.php");
    exit;
}

// Se clicou em "Limpar", apaga o arquivo e recarrega a página
if (isset($_GET['limpar'])) {
    if (file_exists("pedidos.txt")) {
        unlink("pedidos.txt");
    }
    header("Location: pedidos.php");
    exit;
}

$nomesPrio = ["alta" => "Alta", "media" => "Média", "baixa" => "Baixa"];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos · Roteador de Entregas</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛵</text></svg>">
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
      <a href="painel.php">📊 Painel</a>
      <a href="pedidos.php" class="ativo">📥 Pedidos</a>
      <a href="entregadores.php">🛵 Entregadores</a>
      <a href="despacho.php">🚀 Despacho</a>
      <a href="mapa.php">🗺️ Mapa</a>
      <a href="busca.php">🔎 Busca</a>
      <a href="estatisticas.html">📈 Estatísticas</a>
      <a href="login.html" class="sair">⏻ Sair</a>
    </nav>
    <div class="side-foot"><span class="dot ok"></span> Central online · Turno tarde</div>
  </aside>

  <main>
    <header class="topbar">
      <div class="titulo">
        <h1>Cadastro de entregas</h1>
        <p>Cada pedido entra no final da fila de despacho</p>
      </div>
      <div class="user">
        <span class="tag tag-acc">Fila: <?= count($pedidos) ?> aguardando</span>
        <span class="avatar">CS</span>
      </div>
    </header>

    <div class="grid g-21">

      <!-- FORMULÁRIO DO PEDIDO -->
      <section class="card">
        <div class="card-head">
          <h2>📋 Novo pedido</h2>
        </div>
        <form class="form" action="pedidos.php" method="get">
          <div class="fld-2">
            <div class="fld">
              <label for="rastreio">Código de rastreio</label>
              <input type="text" id="rastreio" value="<?= $proximo ?>" readonly class="mono">
            </div>
            <div class="fld">
              <label for="destinatario">Destinatário</label>
              <input type="text" id="destinatario" name="destinatario" placeholder="Nome de quem recebe" required>
            </div>
          </div>
          <div class="fld">
            <label for="endereco">Endereço de entrega</label>
            <input type="text" id="endereco" name="endereco" placeholder="Rua / Avenida, número, bairro" required>
          </div>
          <div class="fld-2">
            <div class="fld">
              <label for="qx">Quadrante X (1–10)</label>
              <input type="number" id="qx" name="qx" min="1" max="10" value="4" required>
            </div>
            <div class="fld">
              <label for="qy">Quadrante Y (1–10)</label>
              <input type="number" id="qy" name="qy" min="1" max="10" value="7" required>
            </div>
          </div>
          <div class="fld">
            <label>Prioridade</label>
            <div class="pills">
              <label class="pill alta"><input type="radio" name="prio" value="alta"><span>🔴 Alta</span></label>
              <label class="pill"><input type="radio" name="prio" value="media" checked><span>🟡 Média</span></label>
              <label class="pill baixa"><input type="radio" name="prio" value="baixa"><span>🟢 Baixa</span></label>
            </div>
          </div>
          <div class="fld">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" placeholder="Ex.: frágil, entregar na portaria…"></textarea>
          </div>
          <div class="acoes">
            <button type="submit" class="btn btn-pri">📥 Enfileirar pedido</button>
            <button type="reset" class="btn btn-ghost">Limpar</button>
          </div>
          <p class="hint">📌 O pedido é gravado em arquivo e entra no <b>final da fila</b> — o primeiro a entrar é o primeiro a ser despachado.</p>
        </form>
      </section>

      <!-- FILA FIFO -->
      <section class="card">
        <div class="card-head">
          <h2>📥 Fila de despacho (FIFO)</h2>
          <span class="tag"><?= count($pedidos) ?> aguardando</span>
        </div>
        <ul class="fila">
          <?php if (count($pedidos) == 0): ?>
            <li class="fila-item">
              <div class="fi-info"><small>Nenhum pedido cadastrado ainda.</small></div>
            </li>
          <?php endif; ?>

          <?php foreach ($pedidos as $i => $linha):
            $c = array_pad(explode(";", $linha), 7, "");
            $dist = abs((int)$c[3] - 1) + abs((int)$c[4] - 1);
            $prio = isset($nomesPrio[$c[5]]) ? $c[5] : "media";
          ?>
            <li class="fila-item<?= $i == 0 ? ' prox' : '' ?>">
              <span class="pos"><?= str_pad($i + 1, 2, "0", STR_PAD_LEFT) ?></span>
              <div class="fi-info">
                <strong class="mono"><?= h($c[0]) ?></strong>
                <small><?= h($c[2]) ?> · Q(<?= h($c[3]) ?>,<?= h($c[4]) ?>) · <?= $dist ?> quadras</small>
              </div>
              <div class="fi-badges">
                <span class="b b-<?= $prio ?>"><?= $nomesPrio[$prio] ?></span>
                <?php if ($i == 0): ?><span class="b b-acc">Próximo</span><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($pedidos) > 0): ?>
          <div class="acoes" style="margin-top:14px">
            <a class="btn btn-ghost" href="pedidos.php?limpar=1">🗑️ Limpar fila</a>
          </div>
        <?php endif; ?>
    </div>

    <footer class="rodape">
      Roteador de Entregas · Frota Urbana ·  Logística 
    </footer>
  </main>
</div>
</body>
</html>