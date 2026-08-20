<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$SALT = 'associacao-do-amor-painel';
$hashFile = __DIR__ . '/senha.hash';
$jsonFile = dirname(__DIR__) . '/data/conteudo.json';
$docDir = dirname(__DIR__) . '/documentos';
$fotoDir = dirname(__DIR__) . '/fotos';

function fail($code, $msg) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'erro' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

function hash_senha($senha) {
  global $SALT;
  return hash('sha256', $senha . $SALT);
}

function senha_ok($senha) {
  global $hashFile;
  if (!is_string($senha) || $senha === '') return false;
  if (!is_file($hashFile)) return false;
  $esperado = trim(file_get_contents($hashFile));
  return hash_equals($esperado, hash_senha($senha));
}

function ler_json() {
  global $jsonFile;
  if (!is_file($jsonFile)) fail(500, 'Arquivo de conteúdo não encontrado.');
  $data = json_decode(file_get_contents($jsonFile), true);
  if (!is_array($data)) fail(500, 'Conteúdo inválido.');
  return $data;
}

$acao = $_POST['acao'] ?? '';
$raw = file_get_contents('php://input');
$input = [];
if ($raw && empty($_POST)) {
  $input = json_decode($raw, true) ?: [];
  $acao = $input['acao'] ?? $acao;
}

$senha = $_POST['senha'] ?? ($input['senha'] ?? '');
if (!senha_ok($senha)) fail(401, 'Senha incorreta.');

if ($acao === 'login') {
  echo json_encode(['ok' => true, 'conteudo' => ler_json()], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($acao === 'salvar') {
  $dados = $input['dados'] ?? null;
  if (!is_array($dados)) fail(400, 'Dados inválidos.');
  $dir = dirname($jsonFile);
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $ok = file_put_contents($jsonFile, json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  if ($ok === false) fail(500, 'Não foi possível salvar. Verifique permissão da pasta data/.');
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($acao === 'senha') {
  $nova = $input['nova'] ?? '';
  if (strlen($nova) < 8) fail(400, 'A nova senha precisa ter pelo menos 8 caracteres.');
  if (file_put_contents($hashFile, hash_senha($nova)) === false) fail(500, 'Não foi possível gravar a nova senha.');
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($acao === 'upload') {
  if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    fail(400, 'Envie um arquivo válido.');
  }
  $destino = $_POST['destino'] ?? '';
  $permitidos = [
    'estatuto' => [$docDir, 'estatuto-social.pdf', ['pdf']],
    'ata' => [$docDir, 'ata-eleicao-diretoria-2026.pdf', ['pdf']],
    'crf' => [$docDir, 'crf-fgts.pdf', ['pdf']],
    'cndt' => [$docDir, 'cndt-trabalhista.pdf', ['pdf']],
    'estadual' => [$docDir, 'cdt-estadual-mg.pdf', ['pdf']],
    'municipal' => [$docDir, 'certidao-municipal.jpg', ['jpg', 'jpeg', 'png', 'pdf']],
    'termo065' => [$docDir, 'relatorio-execucao-parceria-065-2022.pdf', ['pdf']],
    'termo064' => [$docDir, 'relatorio-execucao-parceria-064-2023.pdf', ['pdf']],
    'termo059' => [$docDir, 'relatorio-execucao-parceria-059-2024.pdf', ['pdf']],
    'pix' => [dirname(__DIR__), 'pix-qr-code.png', ['png', 'jpg', 'jpeg']],
    'logo' => [dirname(__DIR__), 'logo-limpa.png', ['png', 'jpg', 'jpeg']],
  ];
  if (!isset($permitidos[$destino])) fail(400, 'Destino de arquivo não permitido.');
  [$pasta, $nome, $exts] = $permitidos[$destino];
  $ext = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $exts, true)) fail(400, 'Tipo de arquivo não aceito.');
  if ($_FILES['arquivo']['size'] > 12 * 1024 * 1024) fail(400, 'Arquivo maior que 12 MB.');
  if (!is_dir($pasta)) mkdir($pasta, 0755, true);
  $alvo = $pasta . '/' . $nome;
  if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $alvo)) fail(500, 'Falha ao gravar o arquivo.');
  echo json_encode(['ok' => true, 'arquivo' => $nome], JSON_UNESCAPED_UNICODE);
  exit;
}

fail(400, 'Ação desconhecida.');
