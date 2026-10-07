<?php
// Configuração de Conexão com o Banco de Dados (MySQL via MySQLi)

function getDBConnection() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $host = 'localhost';$dbName = 'oficina_motos';
    $user = 'root';$pass = 'admin';

    // Desativa o relatório de erros padrão e ativa exceções no MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Conecta ao servidor MySQL local (sem especificar o banco de dados inicialmente)
        $conn = new mysqli($host,$user, $pass);$conn->set_charset('utf8mb4');

        // Cria o banco de dados se não existir
        $sqlCreateDB = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->query($sqlCreateDB);

        // Seleciona o banco de dados para uso
        $conn->select_db($dbName);

    } catch (mysqli_sql_exception $e) {
        die("Erro ao conectar ao banco de dados MySQL: " . $e->getMessage());
    }

    // Inicializa as tabelas se ainda não existirem
    initDatabaseSchema($conn);

    return $conn;
}

function initDatabaseSchema($conn) {
    // Tabela de Clientes (Oficina de Motos)
    $sqlClientes = "CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        cpf VARCHAR(14) NOT NULL,
        telefone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        modelo_moto VARCHAR(80) NOT NULL,
        placa_moto VARCHAR(10) NOT NULL,
        ano_moto VARCHAR(4),
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Tabela de Produtos (Peças e Insumos de Motos)
    $sqlProdutos = "CREATE TABLE IF NOT EXISTS produtos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo_sku VARCHAR(30) UNIQUE NOT NULL,
        nome VARCHAR(100) NOT NULL,
        categoria VARCHAR(50) NOT NULL,
        preco_custo DECIMAL(10,2) NOT NULL,
        preco_venda DECIMAL(10,2) NOT NULL,
        quantidade_estoque INT NOT NULL DEFAULT 0,
        quantidade_minima INT NOT NULL DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Tabela de Vendas / Ordens de Serviço
    $sqlVendas = "CREATE TABLE IF NOT EXISTS vendas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_os VARCHAR(20) NOT NULL,
        cliente_id INT NOT NULL,
        produto_id INT NOT NULL,
        quantidade INT NOT NULL,
        valor_unitario DECIMAL(10,2) NOT NULL,
        valor_total DECIMAL(10,2) NOT NULL,
        data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(30) DEFAULT 'Concluída'
    )";

    $conn->query($sqlClientes);
    $conn->query($sqlProdutos);
    $conn->query($sqlVendas);

    // Se estiver vazio, popula com dados iniciais para testes
    seedInitialData($conn);
}

function seedInitialData($conn) {
    $result =$conn->query("SELECT COUNT(*) as total FROM clientes");
    $row =$result->fetch_assoc();

    if ((int)$row['total'] === 0) {         // Inserir Clientes de Exemplo
    $conn->query("INSERT INTO clientes (nome, cpf, telefone, email, modelo_moto, placa_moto, ano_moto, observacoes) VALUES
            ('Carlos Eduardo Silva', '123.456.789-00', '(11) 98765-4321', 'carlos.silva@email.com', 'Honda CG 160 Titan', 'ABC-1D23', '2022', 'Revisão periódica dos 10.000km'),
            ('Mariana Oliveira Rocha', '987.654.321-11', '(11) 91234-5678', 'mariana.rocha@email.com', 'Yamaha Fazer FZ25', 'XYZ-9K88', '2023', 'Troca de óleo e alinhamento'),
            ('Roberto Mendes Ferreira', '456.789.123-22', '(11) 99887-7665', 'roberto.mendes@email.com', 'Honda Biz 125', 'MNO-3P45', '2020', 'Pastilha de freio gasta'),
            ('Fernanda Souza Lima', '321.654.987-33', '(11) 97654-3210', 'fernanda.lima@email.com', 'Kawasaki Ninja 400', 'KWA-4N00', '2024', 'Troca de kit relação completo')
        ");

        // Inserir Produtos de Exemplo
        $conn->query("INSERT INTO produtos (codigo_sku, nome, categoria, preco_custo, preco_venda, quantidade_estoque, quantidade_minima) VALUES
            ('PEC-001', 'Óleo Mobil Super Moto 20W50 1L', 'Lubrificantes', 22.50, 42.00, 45, 10),
            ('PEC-002', 'Pastilha de Freio Dianteira Cobreq (Titan 160)', 'Freios', 18.00, 38.50, 3, 5),
            ('PEC-003', 'Kit Relação Transmissão DID com Retentor', 'Transmissão', 120.00, 240.00, 2, 4),
            ('PEC-004', 'Pneu Traseiro Pirelli City Cross 110/80-18', 'Pneus', 180.00, 310.00, 12, 3),
            ('PEC-005', 'Vela de Ignição Iridium NGK CPR8EAIX-9', 'Elétrica & Motor', 45.00, 85.00, 25, 8),
            ('PEC-006', 'Filtro de Ar Tecfil CG 160 Titan', 'Filtros', 12.00, 28.00, 1, 6)
        ");

        // Inserir Vendas de Exemplo
        $conn->query("INSERT INTO vendas (numero_os, cliente_id, produto_id, quantidade, valor_unitario, valor_total, status) VALUES
            ('OS-2026-001', 1, 1, 2, 42.00, 84.00, 'Concluída'),
            ('OS-2026-002', 2, 2, 1, 38.50, 38.50, 'Concluída'),
            ('OS-2026-003', 3, 3, 1, 240.00, 240.00, 'Concluída'),
            ('OS-2026-004', 4, 5, 2, 85.00, 170.00, 'Concluída')
        ");
    }
}