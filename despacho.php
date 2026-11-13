<?php
/*
 * FORMATO (separado por ";"), igual ao que o pedidos.php e o entregadores.php já gravam:
 *
 * pedidos.txt:      codigo;cliente;endereco;x;y;prioridade;entregador;seq
 *   - entregador vazio -> pedido na FILA (FIFO: ordem do arquivo = ordem de chegada)
 *   - entregador = ID  -> pedido na MOCHILA desse entregador (LIFO: maior "seq" = topo)
 *   - entregador = #ok -> pedido já entregue
 *
 * entregadores.txt: id;nome;(livre);veiculo;status
 */
require_once __DIR__ . '/funcoes.php';   // usa o listar() que você já tem

const ARQ_PEDIDOS      = __DIR__ . '/pedidos.txt';
const ARQ_ENTREGADORES = __DIR__ . '/entregadores.txt';
const CAPACIDADE       = 3;   // pacotes por mochila

// ===== FUNÇÕES =====
function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Regrava o arquivo inteiro (o salvar() do funcoes.php só acrescenta no fim)
function reescrever(string $arq, array $linhas): void
{
    file_put_contents($arq, $linhas ? implode("\n", $linhas) . "\n" : '', LOCK_EX);
}

function lerPedidos(): array
{
    $lista = [];
    foreach (listar(ARQ_PEDIDOS) as $l) {
        $c = explode(';', $l);
        if (count($c) < 6) {
            continue;
        }
        $lista[] = [
            'codigo' => $c[0], 'cliente' => $c[1], 'endereco' => $c[2],
            'x' => (int) $c[3], 'y' => (int) $c[4], 'prioridade' => $c[5],
            'entregador' => $c[6] ?? '', 'seq' => (int) ($c[7] ?? 0),
        ];
    }
    return $lista;
}

function salvarPedidos(array $pedidos): void
{
    reescrever(ARQ_PEDIDOS, array_map(fn($p) => implode(';', [
        $p['codigo'], $p['cliente'], $p['endereco'], $p['x'], $p['y'],
        $p['prioridade'], $p['entregador'], $p['seq'],
    ]), $pedidos));
}

function lerEntregadores(): array
{
    $lista = [];
    foreach (listar(ARQ_ENTREGADORES) as $l) {
        $c = explode(';', $l);
        if (count($c) < 5) {
            continue;
        }
        $lista[] = ['id' => $c[0], 'nome' => $c[1], 'extra' => $c[2], 'veiculo' => $c[3], 'status' => $c[4]];
    }
    return $lista;
}

function salvarEntregadores(array $entregadores): void
{
    reescrever(ARQ_ENTREGADORES, array_map(fn($e) => implode(';', [
        $e['id'], $e['nome'], $e['extra'], $e['veiculo'], $e['status'],
    ]), $entregadores));
}

// FIFO: pedidos sem entregador, na ordem do arquivo
function fila(array $pedidos): array
{
    return array_values(array_filter($pedidos, fn($p) => $p['entregador'] === ''));
}

// LIFO: pedidos do entregador, do fundo (mais antigo) ao topo (mais novo)
function mochila(array $pedidos, string $id): array
{
    $m = array_values(array_filter($pedidos, fn($p) => $p['entregador'] === $id));
    usort($m, fn($a, $b) => $a['seq'] <=> $b['seq']);
    return $m;
}

function podeReceber(array $e, array $pedidos): bool
{
    return $e['status'] !== 'Em Pausa' && count(mochila($pedidos, $e['id'])) < CAPACIDADE;
}

// Deixa o status coerente: Em Rota (com pacotes) ou Disponível (mochila vazia)
function atualizarStatus(string $id, array $pedidos): void
{
    $entregadores = lerEntregadores();
    foreach ($entregadores as $i => $e) {
        if ($e['id'] === $id && $e['status'] !== 'Em Pausa') {
            $entregadores[$i]['status'] = mochila($pedidos, $id) ? 'Em Rota' : 'Disponível';
        }
    }
    salvarEntregadores($entregadores);
}

// classe CSS e texto da prioridade (aceita "media" ou "Média")
function prioridade(string $p): array
{
    $chave = str_replace(['é', 'É'], 'e', strtolower(trim($p)));
    $rotulos = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
    return ['b-' . $chave, $rotulos[$chave] ?? $p];
}

function despachar(string $id): string
{
    $pedidos = lerPedidos();
    $fila    = fila($pedidos);
    if (!$fila) {
        return 'A fila está vazia.';
    }

    foreach (lerEntregadores() as $e) {
        if ($e['id'] !== $id) {
            continue;
        }
        if (!podeReceber($e, $pedidos)) {
            return $e['nome'] . ' não está disponível.';
        }

        $primeiro = array_shift($fila);                       // FIFO: sai da frente da fila
        $seq      = max(array_column($pedidos, 'seq') ?: [0]) + 1;

        foreach ($pedidos as $i => $p) {
            if ($p['codigo'] === $primeiro['codigo']) {
                $pedidos[$i]['entregador'] = $id;             // LIFO: entra no topo da mochila
                $pedidos[$i]['seq']        = $seq;
                break;
            }
        }
        salvarPedidos($pedidos);
        atualizarStatus($id, $pedidos);
        return 'Pedido ' . $primeiro['codigo'] . ' atribuído a ' . $e['nome'] . '.';
    }

    return 'Selecione um entregador.';
}

function entregar(string $id): string
{
    $pedidos = lerPedidos();
    $m       = mochila($pedidos, $id);
    if (!$m) {
        return 'A mochila está vazia.';
    }

    $topo = array_pop($m);                                    // LIFO: sai o último que entrou
    foreach ($pedidos as $i => $p) {
        if ($p['codigo'] === $topo['codigo']) {
            $pedidos[$i]['entregador'] = '#ok';
            break;
        }
    }
    salvarPedidos($pedidos);
    atualizarStatus($id, $pedidos);
    return 'Entrega do pedido ' . $topo['codigo'] . ' registrada.';
}

// ===== AÇÕES (POST -> redireciona para não repetir ao apertar F5) =====
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['entregador'] ?? '';
    $_SESSION['aviso'] = match ($_POST['acao'] ?? '') {
        'despachar' => despachar($id),
        'entregar'  => entregar($id),
        default     => '',
    };
    header('Location: despacho.php');
    exit;
}

$aviso = $_SESSION['aviso'] ?? '';
unset($_SESSION['aviso']);

$pedidos      = lerPedidos();
$entregadores = lerEntregadores();
$fila         = fila($pedidos);
$livres       = array_values(array_filter($entregadores, fn($e) => podeReceber($e, $pedidos)));
$proximo      = $fila[0] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Despacho · Roteador de Entregas</title>
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
      <a href="despacho.php" class="ativo">🚀 Despacho</a>
      <a href="mapa.php">🗺️ Mapa</a>
      <a href="busca.php">🔎 Busca</a>
      <a href="estatisticas.html">📈 Estatísticas</a>
      <a href="login.html" class="sair">⏻ Sair</a>
    </nav>
  </aside>

  <main>
    <header class="topbar">
      <div class="titulo">
        <h1>Despacho</h1>
      </div>
      <div class="user">
        <span class="tag tag-acc">Fila: <?= count($fila) ?> · Livres: <?= count($livres) ?></span>
        <span class="avatar">CS</span>
      </div>
    </header>

    <?php if ($aviso !== ''): ?>
      <div class="aviso acc">🚀 <span><?= esc($aviso) ?></span></div>
    <?php elseif ($proximo): ?>
      <div class="aviso acc">🚀 <span><?= esc($proximo['codigo']) ?> pronto para atribuição — selecione um entregador disponível.</span></div>
    <?php endif; ?>

    <div class="grid g-21">

      <!-- FILA (FIFO) -->
      <section class="card">
        <div class="card-head">
          <h2>📥 Fila de despacho</h2>
          <span class="tag">sai sempre da frente</span>
        </div>
        <ul class="fila">
          <?php foreach ($fila as $n => $p): ?>
            <?php [$cls, $rot] = prioridade($p['prioridade']); ?>
            <li class="fila-item <?= $n === 0 ? 'prox' : '' ?>">
              <span class="pos"><?= str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <div class="fi-info">
                <strong class="mono"><?= esc($p['codigo']) ?></strong>
                <small><?= esc($p['cliente']) ?> · <?= esc($p['endereco']) ?> · Q(<?= $p['x'] ?>,<?= $p['y'] ?>)</small>
              </div>
              <div class="fi-badges">
                <span class="b <?= esc($cls) ?>"><?= esc($rot) ?></span>
                <?php if ($n === 0): ?><span class="b b-acc">Próximo</span><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if (!$fila): ?>
            <li class="vazio">Nenhum pedido na fila.</li>
          <?php endif; ?>
        </ul>
      </section>

      <div class="col">

        <!-- ENTREGADORES DISPONÍVEIS -->
        <section class="card">
          <div class="card-head">
            <h2>🛵 Entregadores disponíveis</h2>
            <span class="tag"><?= count($livres) ?> livres</span>
          </div>
          <form action="despacho.php" method="post">
            <input type="hidden" name="acao" value="despachar">
            <?php foreach ($livres as $n => $e): ?>
              <label class="radio-card">
                <input type="radio" name="entregador" value="<?= esc($e['id']) ?>" <?= $n === 0 ? 'checked' : '' ?>>
                <span class="rc-body">
                  <span class="avatar sm"><?= esc(strtoupper(substr($e['nome'], 0, 2))) ?></span>
                  <span class="rc-info">
                    <strong><?= esc($e['nome']) ?></strong>
                    <small><?= esc($e['id']) ?> · <?= esc($e['veiculo']) ?> · mochila <?= count(mochila($pedidos, $e['id'])) ?>/<?= CAPACIDADE ?></small>
                  </span>
                  <span class="b b-ok">Disponível</span>
                </span>
              </label>
            <?php endforeach; ?>
            <?php if (!$livres): ?>
              <p class="vazio">Nenhum entregador disponível.</p>
            <?php endif; ?>
            <div class="acoes mt">
              <button type="submit" class="btn btn-pri" <?= (!$livres || !$fila) ? 'disabled' : '' ?>>
                🚀 Despachar <?= $proximo ? esc($proximo['codigo']) : '' ?> (push)
              </button>
            </div>
          </form>
        </section>

        <!-- MOCHILAS (LIFO) -->
        <?php foreach ($entregadores as $e): ?>
          <?php $m = mochila($pedidos, $e['id']); ?>
          <?php if (!$m) { continue; } ?>
          <section class="card">
            <div class="card-head">
              <h2>🎒 Mochila (pilha) — <?= esc($e['nome']) ?></h2>
              <span class="tag">em rota · <?= count($m) ?> pacotes</span>
            </div>
            <ol class="pilha">
              <?php foreach (array_reverse($m) as $n => $p): ?>
                <?php [$cls, $rot] = prioridade($p['prioridade']); ?>
                <li class="caixa <?= $n === 0 ? 'topo' : '' ?>">
                  <?php if ($n === 0): ?><span class="tag-topo">TOPO · SAI PRIMEIRO</span><?php endif; ?>
                  <div class="fi-info">
                    <strong class="mono"><?= esc($p['codigo']) ?></strong>
                    <small><?= esc($p['endereco']) ?> · Q(<?= $p['x'] ?>,<?= $p['y'] ?>)</small>
                  </div>
                  <span class="b <?= esc($cls) ?>"><?= esc($rot) ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
            <form action="despacho.php" method="post" class="acoes mt">
              <input type="hidden" name="acao" value="entregar">
              <input type="hidden" name="entregador" value="<?= esc($e['id']) ?>">
              <button type="submit" class="btn btn-pri">📤 Registrar entrega (pop)</button>
            </form>
          </section>
        <?php endforeach; ?>

      </div>
    </div>
  </main>
</div>
</body>
</html>