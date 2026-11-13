<?php
require "funcoes.php";

function h($t) { return htmlspecialchars($t ?? ""); }

$erro = "";

// Excluir um entregador (botão 🗑️)
if (isset($_POST['excluir'])) {
    $id = $_POST['excluir'];

    // não deixa excluir quem ainda tem pacote na mochila
    $temPacote = false;
    foreach (listar("pedidos.txt") as $l) {
        $p = explode(";", $l);
        if (($p[6] ?? "") === $id) {
            $temPacote = true;
            break;
        }
    }

    if ($temPacote) {
        $erro = "Esse entregador ainda tem pedidos na mochila. Registre as entregas antes de excluir.";
    } else {
        $restantes = array_filter(listar("entregadores.txt"), fn($l) => explode(";", $l)[0] !== $id);
        file_put_contents("entregadores.txt", $restantes ? implode("\n", $restantes) . "\n" : "", LOCK_EX);
        header("Location: entregadores.php");
        exit;
    }
}

$frota = listar("entregadores.txt");

// próximo ID = maior número já usado + 1 (não repete ID depois de excluir)
$maior = 0;
foreach ($frota as $linha) {
    $maior = max($maior, (int) substr(explode(";", $linha)[0], 2));
}
$proximoId = "M-" . str_pad($maior + 1, 3, "0", STR_PAD_LEFT);

// Se chegou o formulário (GET), valida, salva e recarrega a página
if (isset($_GET['nome'])) {
    $tel     = trim($_GET['tel'] ?? "");
    $digitos = preg_replace('/\D/', '', $tel);   // só os números

    if (strlen($digitos) < 10 || strlen($digitos) > 11) {
        $erro = "Informe um telefone válido, com DDD.";
    } else {
        salvar("entregadores.txt", [
            $proximoId,
            $_GET['nome'],
            $tel,
            $_GET['veiculo'] ?? "Moto",
            $_GET['status'] ?? "Disponível"
        ]);
        header("Location: entregadores.php");
        exit;
    }
}

// Se clicou em "Limpar frota", apaga o arquivo e recarrega a página
if (isset($_GET['limpar'])) {
    if (file_exists("entregadores.txt")) {
        unlink("entregadores.txt");
    }
    header("Location: entregadores.php");
    exit;
}

$classeStatus = [
    "Disponível" => "b-ok",
    "Em Rota"    => "b-info",
    "Em Pausa"   => "b-warn"
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entregadores · Roteador de Entregas</title>
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
      <a href="entregadores.php" class="ativo">🛵 Entregadores</a>
      <a href="despacho.php">🚀 Despacho</a>
      <a href="mapa.php">🗺️ Mapa</a>
      <a href="busca.html">🔎 Busca</a>
      <a href="estatisticas.html">📈 Estatísticas</a>
      <a href="login.html" class="sair">⏻ Sair</a>
    </nav>
    <div class="side-foot"><span class="dot ok"></span> Central online · Turno tarde</div>
  </aside>

  <main>
    <header class="topbar">
      <div class="titulo">
        <h1>Entregadores</h1>
      </div>
      <div class="user">
        <span class="tag"><?= count($frota) ?> no vetor</span>
        <span class="avatar">CS</span>
      </div>
    </header>

    <div class="grid g-21">

      <!-- FORMULÁRIO -->
      <section class="card">
        <div class="card-head">
          <h2>🛵 Novo entregador</h2>
        </div>

        <?php if ($erro !== ""): ?>
          <div class="aviso"><?= h($erro) ?></div>
        <?php endif; ?>

        <form class="form" action="entregadores.php" method="get">
          <div class="fld">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" placeholder="Ex.: Marina Costa" required
                   value="<?= h($_GET['nome'] ?? '') ?>">
          </div>
          <div class="fld-2">
            <div class="fld">
              <label for="ident">Identificação</label>
              <input type="text" id="ident" value="<?= $proximoId ?>" readonly class="mono">
            </div>
            <div class="fld">
              <label for="tel">Telefone / WhatsApp</label>
              <input type="tel" id="tel" name="tel" required maxlength="15"
                     placeholder="(11) 90000-0000"
                     pattern="\(?\d{2}\)?\s?\d{4,5}-?\d{4}"
                     title="Digite o DDD e o número, ex: (71) 99999-9999"
                     value="<?= h($_GET['tel'] ?? '') ?>">
            </div>
          </div>
          <div class="fld-2">
            <div class="fld">
              <label for="veiculo">Veículo</label>
              <select id="veiculo" name="veiculo">
                <option>Moto</option>
                <option>Bicicleta</option>
                <option>Van</option>
              </select>
            </div>
            <div class="fld">
              <label for="status">Status inicial</label>
              <select id="status" name="status">
                <option selected>Disponível</option>
                <option>Em Rota</option>
                <option>Em Pausa</option>
              </select>
            </div>
          </div>
          <div class="acoes">
            <button type="submit" class="btn btn-pri">✅ Cadastrar no vetor</button>
            <button type="reset" class="btn btn-ghost">Limpar</button>
          </div>
        </form>
      </section>

      <!-- VETOR DA FROTA -->
      <section class="card">
        <div class="card-head">
          <h2>🗂️ Vetor da frota</h2>
          <span class="tag"><?= count($frota) ?> registros</span>
        </div>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr>
               <th>ID</th><th>Nome</th><th>Telefone</th><th>Veículo</th>
                <th>Mochila</th><th>Status</th><th></th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($frota) == 0): ?>
                <tr><td colspan="9">Nenhum entregador cadastrado ainda.</td></tr>
              <?php endif; ?>

              <?php foreach ($frota as $i => $linha):
                $c = array_pad(explode(";", $linha), 5, "");
                $classe = $classeStatus[$c[4]] ?? "b-ok";
              ?>
                <tr>
                  <td class="mono"><?= h($c[0]) ?></td>
                  <td><?= h($c[1]) ?></td>
                  <td><?= h($c[2]) ?></td>
                  <td><?= h($c[3]) ?></td>
                  <td class="num">0</td>
                  <td><span class="b <?= $classe ?>"><?= h($c[4]) ?></span></td>
                  <td>
                    <form method="post" action="entregadores.php" style="display:inline;margin:0">
                      <input type="hidden" name="excluir" value="<?= h($c[0]) ?>">
                      <button type="submit" class="btn btn-ghost" title="Excluir entregador">🗑️</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (count($frota) > 0): ?>
          <div class="acoes" style="margin-top:14px">
            <a class="btn btn-ghost" href="entregadores.php?limpar=1">🗑️ Limpar frota</a>
          </div>
        <?php endif; ?>
        <p class="hint" style="margin-top:14px">🧠 O status pode ser alterado pelo painel — o vetor é regravado no arquivo a cada mudança.</p>
      </section>
    </div>

    <footer class="rodape">
      Roteador de Entregas · Frota Urbana ·  Logística 
    </footer>
  </main>
</div>
</body>
</html>