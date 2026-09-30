<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício de Switch Case</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Exercício de Switch Case</h1>
        <p class="subtitle">Clique em qualquer botão para executar uma ação utilizando HTML e CSS e PHP.</p>

        <form action="index.php" method="POST">
            <div class="button-grid">
                <button type="submit" name="opcao" value="A" class="btn btn-green">Cadastro de Clientes</button>
                <button type="submit" name="opcao" value="B" class="btn btn-blue">Cadastro Produtos</button>
                <button type="submit" name="opcao" value="C" class="btn btn-orange">Relatório Vendas</button>
                <button type="submit" name="opcao" value="D" class="btn btn-red">Controle Estoque</button>
                <button type="submit" name="opcao" value="E" class="btn btn-purple">Configurações</button>
                <button type="submit" name="opcao" value="F" class="btn btn-teal">Sair do Sistema</button>
            </div>
        </form>

        <div class="result-box">
            <?php include 'processa.php'; ?>
        </div>

        <footer>
            <p>Exemplo Desenvolva com HTML e CSS Bem estilizado e PHP</p>
        </footer>
    </div>
</body>
</html>