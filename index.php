<?php
// Inicia a Sessão do Usuário para Persistência de Preferências e Dados
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Recupera a opção selecionada no Roteamento
$opcao = $_REQUEST['opcao'] ?? '';

// Recupera Preferências de Tema e Acessibilidade (Sessão ou Cookies)
$temaAtual = $_SESSION['theme'] ?? $_COOKIE['app_theme'] ?? 'claro';
$fonteAtual = $_SESSION['font_size'] ?? $_COOKIE['app_font'] ?? 'normal';

// Define Classes CSS para o Body
$bodyClasses = [];
if ($temaAtual === 'escuro') {
    $bodyClasses[] = 'dark-mode';
}
if ($fonteAtual === 'grande') {
    $bodyClasses[] = 'font-grande';
} elseif ($fonteAtual === 'extra-grande') {
    $bodyClasses[] = 'font-extra-grande';
}
$classString = implode(' ', $bodyClasses);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Manutenção de Motos - Exercício Switch Case</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $classString ?>">

    <div class="main-wrapper">
        <!-- Cabeçalho Principal -->
        <header class="header-title no-print">
            <h1>🏍️ Sistema de Manutenção de Motos</h1>
            <p class="subtitle">Exercício de Roteamento Dinâmico em PHP utilizando <code>switch...case</code> e Banco de Dados</p>
        </header>

        <!-- Grid dos 6 Botões de Navegação Temáticos (Switch Case) -->
        <nav class="button-grid no-print">
            <a href="index.php?opcao=A" class="btn-nav btn-green <?= ($opcao === 'A' || $opcao === 'cliente') ? 'active' : '' ?>">
                <i>👤</i>
                <span>1. Cadastro Clientes</span>
            </a>

            <a href="index.php?opcao=B" class="btn-nav btn-blue <?= ($opcao === 'B' || $opcao === 'produto') ? 'active' : '' ?>">
                <i>📦</i>
                <span>2. Cadastro Produtos</span>
            </a>

            <a href="index.php?opcao=C" class="btn-nav btn-orange <?= ($opcao === 'C' || $opcao === 'vendas') ? 'active' : '' ?>">
                <i>💰</i>
                <span>3. Relatório Vendas</span>
            </a>

            <a href="index.php?opcao=D" class="btn-nav btn-red <?= ($opcao === 'D' || $opcao === 'estoque') ? 'active' : '' ?>">
                <i>🚨</i>
                <span>4. Controle Estoque</span>
            </a>

            <a href="index.php?opcao=E" class="btn-nav btn-purple <?= ($opcao === 'E' || $opcao === 'config') ? 'active' : '' ?>">
                <i>⚙️</i>
                <span>5. Configurações</span>
            </a>

            <a href="index.php?opcao=F" class="btn-nav btn-teal <?= ($opcao === 'F' || $opcao === 'sair') ? 'active' : '' ?>">
                <i>🚪</i>
                <span>6. Sair do Sistema</span>
            </a>
        </nav>

        <!-- Caixa de Exibição dos Módulos Roteados via processa.php -->
        <main class="result-box-container">
            <?php include 'processa.php'; ?>
        </main>

        <!-- Rodapé do Sistema -->
        <footer class="no-print">
            <p>Atividade Prática: Oficina de Manutenção de Motos • Desenvolvido com HTML5, CSS3, Vanilla JS e PHP Nativo PDO</p>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>