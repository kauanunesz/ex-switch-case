<?php
if (isset($_POST['opcao'])) {
    $opcao = $_POST['opcao'];

    switch ($opcao) {
        case 'A':
            echo "Módulo de Cadastro de Clientes aberto com sucesso!";
            break;
        case 'B':
            echo "Módulo de Cadastro de Produtos aberto com sucesso!";
            break;
        case 'C':
            echo "Módulo de Relatório de Vendas aberto com sucesso!";
            break;
        case 'D':
            echo "Módulo de Controle de Estoque aberto com sucesso!";
            break;
        case 'E':
            echo "Módulo de Configurações aberto com sucesso!";
            break;
        case 'F':
            echo "Saindo do Sistema... Até logo!";
            break;
        default:
            echo "Opção inválida.";
            break;
    }
} else {
    echo "Escolha uma opção acima";
}
?>