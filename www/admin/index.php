<?php
// ============================================================
// ProDoc Admin Panel — Gerenciamento de Licenças
// Acesso: prodocorcamento.com.br/admin
// ============================================================

session_start();

// ── Senha do administrador ──────────────────────────────────
define('ADMIN_SENHA', password_hash('ProDoc@Admin2026', PASSWORD_DEFAULT));
define('ADMIN_SENHA_PLAIN', 'ProDoc@Admin2026'); // só para verificação
// ────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config.php';

// ── Autenticação ────────────────────────────────────────────
if (isset($_POST['acao']) && $_POST['acao'] === 'login_admin') {
    if ($_POST['senha'] === ADMIN_SENHA_PLAIN) {
        $_SESSION['admin_logado'] = true;
    } else {
        $erro_login = 'Senha incorreta.';
    }
}
if (isset($_GET['sair'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$logado = $_SESSION['admin_logado'] ?? false;

// ── Ações AJAX ──────────────────────────────────────────────
if ($logado && isset($_POST['acao'])) {
    header('Content-Type: application/json');

    $acao = $_POST['acao'];

    // Estender licença
    if ($acao === 'estender') {
        $id   = (int) $_POST['usuario_id'];
        $dias = (int) $_POST['dias'];
        $stmt = $conn->prepare("
            UPDATE usuarios
            SET data_expiracao = DATE_ADD(
                IF(data_expiracao > NOW(), data_expiracao, NOW()),
                INTERVAL :dias DAY
            )
            WHERE id = :id
        ");
        $stmt->execute([':dias' => $dias, ':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Definir data manual
    if ($acao === 'definir_data') {
        $id   = (int) $_POST['usuario_id'];
        $data = $_POST['data'];
        $stmt = $conn->prepare("UPDATE usuarios SET data_expiracao = :data WHERE id = :id");
        $stmt->execute([':data' => $data, ':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Bloquear / desbloquear
    if ($acao === 'toggle_status') {
        $id     = (int) $_POST['usuario_id'];
        $status = $_POST['status']; // 'ativo' ou 'suspenso'
        $stmt   = $conn->prepare("UPDATE usuarios SET status_assinatura = :s WHERE id = :id");
        $stmt->execute([':s' => $status, ':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Buscar usuários
    if ($acao === 'listar') {
        $busca = '%' . trim($_POST['busca'] ?? '') . '%';
        $stmt  = $conn->prepare("
            SELECT id, nome_completo, email, whatsapp,
                   data_expiracao, status_assinatura, papel,
                   DATE_FORMAT(data_expiracao, '%d/%m/%Y') AS validade_fmt,
                   DATEDIFF(data_expiracao, NOW()) AS dias_restantes
            FROM usuarios
            WHERE nome_completo LIKE :b OR email LIKE :b2
            ORDER BY data_expiracao ASC
        ");
        $stmt->execute([':b' => $busca, ':b2' => $busca]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}

// ── HTML ────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ProDoc Admin</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; background:#0f1923; color:#e0e0e0; min-height:100vh; }

  /* LOGIN */
  .login-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .login-box { background:#1a2535; border-radius:16px; padding:36px 32px; width:320px; box-shadow:0 8px 32px rgba(0,0,0,0.5); }
  .login-box h1 { color:#25d366; font-size:22px; margin-bottom:4px; }
  .login-box p { font-size:12px; color:#888; margin-bottom:24px; }
  .login-box input { width:100%; padding:12px; background:#0f1923; border:1px solid #2a3a4a; border-radius:8px; color:#fff; font-size:14px; margin-bottom:14px; }
  .login-box button { width:100%; padding:13px; background:#25d366; color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:700; cursor:pointer; }
  .erro { color:#e74c3c; font-size:12px; margin-top:8px; text-align:center; }

  /* PAINEL */
  .header { background:#1a2535; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #2a3a4a; }
  .header h1 { font-size:18px; color:#25d366; }
  .header a { font-size:12px; color:#888; text-decoration:none; }
  .header a:hover { color:#e74c3c; }

  .container { max-width:900px; margin:0 auto; padding:20px 16px; }

  /* BUSCA */
  .busca-wrap { display:flex; gap:10px; margin-bottom:20px; }
  .busca-wrap input { flex:1; padding:11px 14px; background:#1a2535; border:1px solid #2a3a4a; border-radius:8px; color:#fff; font-size:14px; }
  .busca-wrap button { padding:11px 18px; background:#075e54; color:#fff; border:none; border-radius:8px; font-size:14px; cursor:pointer; }

  /* CARDS */
  .card-usuario { background:#1a2535; border-radius:12px; padding:16px; margin-bottom:12px; border-left:4px solid #2a3a4a; }
  .card-usuario.expirado { border-left-color:#e74c3c; }
  .card-usuario.ok { border-left-color:#25d366; }
  .card-usuario.critico { border-left-color:#f39c12; }
  .card-usuario.suspenso { border-left-color:#888; opacity:0.7; }

  .user-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
  .user-nome { font-weight:700; font-size:15px; }
  .user-email { font-size:11px; color:#888; margin-top:2px; }
  .user-zap { font-size:11px; color:#25d366; margin-top:2px; }

  .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
  .badge-ok { background:#1a4a2a; color:#25d366; }
  .badge-critico { background:#4a3a0a; color:#f39c12; }
  .badge-expirado { background:#4a1a1a; color:#e74c3c; }
  .badge-suspenso { background:#2a2a2a; color:#888; }

  .user-validade { font-size:12px; color:#aaa; margin:6px 0; }
  .dias-restantes { font-weight:700; }

  .acoes { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
  .btn-acao { padding:8px 14px; border:none; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; }
  .btn-7   { background:#1a3a4a; color:#34b7f1; }
  .btn-30  { background:#1a4a2a; color:#25d366; }
  .btn-90  { background:#2a1a4a; color:#9b59b6; }
  .btn-data { background:#3a2a1a; color:#f39c12; }
  .btn-bloquear { background:#4a1a1a; color:#e74c3c; }
  .btn-desbloquear { background:#1a4a2a; color:#25d366; }

  /* MODAL DATA */
  .modal-overlay { display:none; position:fixed; top:0;left:0;right:0;bottom:0; background:rgba(0,0,0,0.8); z-index:100; align-items:center; justify-content:center; }
  .modal-overlay.ativo { display:flex; }
  .modal { background:#1a2535; border-radius:14px; padding:24px; width:300px; }
  .modal h3 { color:#25d366; margin-bottom:16px; font-size:16px; }
  .modal input { width:100%; padding:10px; background:#0f1923; border:1px solid #2a3a4a; border-radius:8px; color:#fff; font-size:14px; margin-bottom:14px; }
  .modal-btns { display:flex; gap:10px; }
  .modal-btns button { flex:1; padding:10px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
  .btn-confirmar { background:#25d366; color:#fff; }
  .btn-cancelar  { background:#2a3a4a; color:#aaa; }

  .resumo { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .resumo-card { flex:1; min-width:120px; background:#1a2535; border-radius:10px; padding:14px; text-align:center; }
  .resumo-num { font-size:28px; font-weight:800; }
  .resumo-label { font-size:11px; color:#888; margin-top:4px; }
  .num-verde { color:#25d366; }
  .num-amarelo { color:#f39c12; }
  .num-vermelho { color:#e74c3c; }
  .num-cinza { color:#888; }

  .loading { text-align:center; color:#888; padding:40px; }
</style>
</head>
<body>

<?php if (!$logado): ?>
<!-- TELA DE LOGIN -->
<div class="login-wrap">
  <div class="login-box">
    <h1>🔐 ProDoc Admin</h1>
    <p>Painel de gerenciamento de licenças</p>
    <form method="POST">
      <input type="hidden" name="acao" value="login_admin">
      <input type="password" name="senha" placeholder="Senha de administrador" autofocus>
      <button type="submit">ENTRAR</button>
      <?php if (isset($erro_login)): ?>
        <p class="erro"><?= $erro_login ?></p>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php else: ?>
<!-- PAINEL PRINCIPAL -->
<div class="header">
  <h1>🛡️ ProDoc Admin</h1>
  <a href="?sair=1">Sair</a>
</div>

<div class="container">

  <!-- Resumo -->
  <div class="resumo" id="resumo">
    <div class="resumo-card"><div class="resumo-num num-verde" id="r-ativos">—</div><div class="resumo-label">Ativos</div></div>
    <div class="resumo-card"><div class="resumo-num num-amarelo" id="r-criticos">—</div><div class="resumo-label">Vencem em 7 dias</div></div>
    <div class="resumo-card"><div class="resumo-num num-vermelho" id="r-expirados">—</div><div class="resumo-label">Expirados</div></div>
    <div class="resumo-card"><div class="resumo-num num-cinza" id="r-suspensos">—</div><div class="resumo-label">Suspensos</div></div>
  </div>

  <!-- Busca -->
  <div class="busca-wrap">
    <input type="text" id="campoBusca" placeholder="🔍 Buscar por nome ou e-mail..." oninput="buscarUsuarios()">
    <button onclick="buscarUsuarios()">Buscar</button>
  </div>

  <div id="listaUsuarios"><div class="loading">Carregando...</div></div>
</div>

<!-- MODAL: definir data manual -->
<div class="modal-overlay" id="modalData">
  <div class="modal">
    <h3>📅 Definir data de validade</h3>
    <input type="date" id="inputDataManual">
    <div class="modal-btns">
      <button class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
      <button class="btn-confirmar" onclick="confirmarData()">Confirmar</button>
    </div>
  </div>
</div>

<script>
let usuarioAtualId = null;
let todosUsuarios  = [];

async function buscarUsuarios() {
    const busca = document.getElementById('campoBusca').value;
    const fd = new FormData();
    fd.append('acao', 'listar');
    fd.append('busca', busca);
    const res  = await fetch('index.php', { method:'POST', body:fd });
    todosUsuarios = await res.json();
    renderizar(todosUsuarios);
    atualizarResumo(todosUsuarios);
}

function atualizarResumo(lista) {
    let ativos=0, criticos=0, expirados=0, suspensos=0;
    lista.forEach(u => {
        if (u.status_assinatura === 'suspenso') { suspensos++; return; }
        const dias = parseInt(u.dias_restantes);
        if (dias < 0)      expirados++;
        else if (dias <= 7) criticos++;
        else                ativos++;
    });
    document.getElementById('r-ativos').textContent    = ativos;
    document.getElementById('r-criticos').textContent  = criticos;
    document.getElementById('r-expirados').textContent = expirados;
    document.getElementById('r-suspensos').textContent = suspensos;
}

function renderizar(lista) {
    const el = document.getElementById('listaUsuarios');
    if (!lista.length) { el.innerHTML = '<div class="loading">Nenhum usuário encontrado.</div>'; return; }

    el.innerHTML = lista.map(u => {
        const dias = parseInt(u.dias_restantes);
        const suspenso = u.status_assinatura === 'suspenso';
        let classe = 'ok', badge = '', diasTxt = '';

        if (suspenso) {
            classe = 'suspenso';
            badge  = '<span class="badge badge-suspenso">SUSPENSO</span>';
            diasTxt = 'Conta suspensa';
        } else if (dias < 0) {
            classe = 'expirado';
            badge  = '<span class="badge badge-expirado">EXPIRADO</span>';
            diasTxt = `<span class="dias-restantes" style="color:#e74c3c;">Expirado há ${Math.abs(dias)} dia(s)</span>`;
        } else if (dias <= 7) {
            classe = 'critico';
            badge  = '<span class="badge badge-critico">CRÍTICO</span>';
            diasTxt = `<span class="dias-restantes" style="color:#f39c12;">Vence em ${dias} dia(s)</span>`;
        } else {
            badge  = '<span class="badge badge-ok">ATIVO</span>';
            diasTxt = `<span class="dias-restantes" style="color:#25d366;">${dias} dias restantes</span>`;
        }

        const btnStatus = suspenso
            ? `<button class="btn-acao btn-desbloquear" onclick="toggleStatus(${u.id},'ativo')">✅ Desbloquear</button>`
            : `<button class="btn-acao btn-bloquear"    onclick="toggleStatus(${u.id},'suspenso')">🚫 Suspender</button>`;

        return `
        <div class="card-usuario ${classe}" id="card-${u.id}">
          <div class="user-header">
            <div>
              <div class="user-nome">${u.nome_completo}</div>
              <div class="user-email">${u.email}</div>
              ${u.whatsapp ? `<div class="user-zap">📱 ${u.whatsapp}</div>` : ''}
            </div>
            ${badge}
          </div>
          <div class="user-validade">
            📅 Validade: <strong>${u.validade_fmt}</strong> — ${diasTxt}
          </div>
          <div class="acoes">
            <button class="btn-acao btn-7"   onclick="estender(${u.id}, 7)">+7 dias</button>
            <button class="btn-acao btn-30"  onclick="estender(${u.id}, 30)">+30 dias</button>
            <button class="btn-acao btn-90"  onclick="estender(${u.id}, 90)">+90 dias</button>
            <button class="btn-acao btn-data" onclick="abrirModalData(${u.id})">📅 Data manual</button>
            ${btnStatus}
          </div>
        </div>`;
    }).join('');
}

async function estender(id, dias) {
    const fd = new FormData();
    fd.append('acao', 'estender');
    fd.append('usuario_id', id);
    fd.append('dias', dias);
    await fetch('index.php', { method:'POST', body:fd });
    buscarUsuarios();
}

function abrirModalData(id) {
    usuarioAtualId = id;
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('inputDataManual').value = hoje;
    document.getElementById('modalData').classList.add('ativo');
}

function fecharModal() {
    document.getElementById('modalData').classList.remove('ativo');
    usuarioAtualId = null;
}

async function confirmarData() {
    const data = document.getElementById('inputDataManual').value;
    if (!data) { alert('Selecione uma data.'); return; }
    const fd = new FormData();
    fd.append('acao', 'definir_data');
    fd.append('usuario_id', usuarioAtualId);
    fd.append('data', data);
    await fetch('index.php', { method:'POST', body:fd });
    fecharModal();
    buscarUsuarios();
}

async function toggleStatus(id, novoStatus) {
    const acao = novoStatus === 'suspenso' ? 'Suspender' : 'Desbloquear';
    if (!confirm(`${acao} este usuário?`)) return;
    const fd = new FormData();
    fd.append('acao', 'toggle_status');
    fd.append('usuario_id', id);
    fd.append('status', novoStatus);
    await fetch('index.php', { method:'POST', body:fd });
    buscarUsuarios();
}

// Carrega ao abrir
buscarUsuarios();
</script>
<?php endif; ?>
</body>
</html>
