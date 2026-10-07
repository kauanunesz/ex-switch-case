<?php
// Processamento Principal e Roteamento via switch...case
require_once 'db.php';

class MysqliCompatResult {
    private $result;

    public function __construct($result) {
        $this->result = $result;
    }

    public function fetchAll($mode = MYSQLI_ASSOC) {
        return $this->result->fetch_all($mode);
    }

    public function fetch($mode = MYSQLI_ASSOC) {
        return $this->result->fetch_array($mode);
    }

    public function fetch_assoc() {
        return $this->result->fetch_assoc();
    }

    public function fetchColumn() {
        $row = $this->result->fetch_row();
        return $row ? $row[0] : false;
    }
}

class MysqliCompatStatement {
    private $stmt;

    public function __construct($stmt) {
        $this->stmt = $stmt;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $types = '';
            $bindValues = [];

            foreach ($params as $key => $value) {
                $types .= (is_int($value) || is_float($value) || is_double($value)) ? 'd' : 's';
                $bindValues[] = &$params[$key];
            }

            call_user_func_array([$this->stmt, 'bind_param'], array_merge([$types], $bindValues));
        }

        $executed = $this->stmt->execute();
        if ($executed === false) {
            throw new RuntimeException('Erro ao executar statement: ' . $this->stmt->error);
        }

        return $executed;
    }

    public function __call($name, $arguments) {
        return $this->stmt->$name(...$arguments);
    }
}

class MysqliCompat {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function query($sql) {
        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Erro na consulta: ' . $this->conn->error);
        }

        return new MysqliCompatResult($result);
    }

    public function prepare($sql) {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Erro ao preparar statement: ' . $this->conn->error);
        }

        return new MysqliCompatStatement($stmt);
    }
}

$pdo = new MysqliCompat(getDBConnection());
$mensagemFeedback = '';
$tipoFeedback = '';

// Captura a opção via GET ou POST (Aceita Letras A-F, Números 1-6 ou Slugs)
$opcao = $_REQUEST['opcao'] ?? 'dashboard';

// Processamento de Ações de Formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    switch ($acao) {
        // --- AÇÃO 1: SALVAR CLIENTE ---
        case 'salvar_cliente':
            $nome = trim($_POST['nome'] ?? '');
            $cpf = trim($_POST['cpf'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $modelo_moto = trim($_POST['modelo_moto'] ?? '');
            $placa_moto = trim($_POST['placa_moto'] ?? '');
            $ano_moto = trim($_POST['ano_moto'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');

            if (!empty($nome) && !empty($cpf) && !empty($modelo_moto) && !empty($placa_moto)) {
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, cpf, telefone, email, modelo_moto, placa_moto, ano_moto, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $cpf, $telefone, $email, $modelo_moto, strtoupper($placa_moto), $ano_moto, $observacoes]);
                $mensagemFeedback = "Cliente <strong>" . htmlspecialchars($nome) . "</strong> e veículo cadastrados com sucesso!";
                $tipoFeedback = "success";
            } else {
                $mensagemFeedback = "Preencha todos os campos obrigatórios (*)!";
                $tipoFeedback = "danger";
            }
            break;

        // --- AÇÃO 2: DELETAR CLIENTE ---
        case 'deletar_cliente':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt->execute([$id]);
                $mensagemFeedback = "Cliente removido com sucesso!";
                $tipoFeedback = "warning";
            }
            break;

        // --- AÇÃO 3: SALVAR PRODUTO / PEÇA ---
        case 'salvar_produto':
            $sku = trim($_POST['codigo_sku'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $preco_custo = floatval($_POST['preco_custo'] ?? 0);
            $preco_venda = floatval($_POST['preco_venda'] ?? 0);
            $qtd_estoque = intval($_POST['quantidade_estoque'] ?? 0);
            $qtd_minima = intval($_POST['quantidade_minima'] ?? 5);

            if (!empty($sku) && !empty($nome) && $preco_venda > 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO produtos (codigo_sku, nome, categoria, preco_custo, preco_venda, quantidade_estoque, quantidade_minima) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([strtoupper($sku), $nome, $categoria, $preco_custo, $preco_venda, $qtd_estoque, $qtd_minima]);
                    $mensagemFeedback = "Produto / Peça <strong>" . htmlspecialchars($nome) . "</strong> cadastrado com sucesso!";
                    $tipoFeedback = "success";
                } catch (PDOException $e) {
                    $mensagemFeedback = "Erro ao cadastrar produto: Código SKU já existe no sistema!";
                    $tipoFeedback = "danger";
                }
            } else {
                $mensagemFeedback = "Preencha os campos obrigatórios do produto!";
                $tipoFeedback = "danger";
            }
            break;

        // --- AÇÃO 4: DELETAR PRODUTO ---
        case 'deletar_produto':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                $stmt->execute([$id]);
                $mensagemFeedback = "Produto removido com sucesso!";
                $tipoFeedback = "warning";
            }
            break;

// --- AÇÃO 5: SALVAR VENDA / ORDEM DE SERVIÇO ---
case 'salvar_venda':
    $cliente_id     = intval($_POST['cliente_id'] ?? 0);
    $produto_id     = intval($_POST['produto_id'] ?? 0);
    $quantidade     = intval($_POST['quantidade'] ?? 1);
    $valor_unitario = floatval($_POST['valor_unitario'] ?? 0);

    if ($cliente_id > 0 && $produto_id > 0 && $quantidade > 0) {
        // Verificar Estoque Atual
        $stmtProd = $pdo->prepare("SELECT quantidade_estoque, nome FROM produtos WHERE id = ?");
        $stmtProd->execute([$produto_id]);

        /** @var array<string,mixed>|false $prod */
        $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (is_array($prod) && (int)$prod['quantidade_estoque'] >= $quantidade) {
            $numOS = 'OS-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $valor_total = $quantidade * $valor_unitario;

            // Inserir Venda
            $stmtVenda = $pdo->prepare("INSERT INTO vendas (numero_os, cliente_id, produto_id, quantidade, valor_unitario, valor_total, status) VALUES (?, ?, ?, ?, ?, ?, 'Concluída')");
            $stmtVenda->execute([$numOS, $cliente_id, $produto_id, $quantidade, $valor_unitario, $valor_total]);

            // Dar baixa no estoque do produto
            $stmtBaixa = $pdo->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?");
            $stmtBaixa->execute([$quantidade, $produto_id]);

            $mensagemFeedback = "Venda/OS <strong>$numOS</strong> lançada com sucesso! Baixa de $quantidade un. realizada.";
            $tipoFeedback = "success";
        } else {
            $nomePeca   = is_array($prod) ? (string)$prod['nome'] : 'esta peça';
            $qtdEstoque = is_array($prod) ? (int)$prod['quantidade_estoque'] : 0;

            $mensagemFeedback = "Estoque insuficiente para " . htmlspecialchars($nomePeca) . "! Qtd em estoque: " . $qtdEstoque;
            $tipoFeedback = "danger";
        }
    } else {
        $mensagemFeedback = "Selecione um cliente, um produto e informe uma quantidade válida!";
        $tipoFeedback = "danger";
    }
    break;

        // --- AÇÃO 6: ATUALIZAR REPOSIÇÃO DE ESTOQUE ---
        case 'atualizar_estoque':
            $produto_id = intval($_POST['produto_id'] ?? 0);
            $qtd_adicionar = intval($_POST['qtd_adicionar'] ?? 0);

            if ($produto_id > 0 && $qtd_adicionar != 0) {
                $stmt = $pdo->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id = ?");
                $stmt->execute([$qtd_adicionar, $produto_id]);
                $mensagemFeedback = "Estoque atualizado com sucesso!";
                $tipoFeedback = "success";
            }
            break;

        // --- AÇÃO 7: SALVAR CONFIGURAÇÕES / ACESSIBILIDADE ---
        case 'salvar_configuracoes':
            $novoTema = $_POST['tema'] ?? 'claro';
            $tamanhoFonte = $_POST['tamanho_fonte'] ?? 'normal';

            $_SESSION['theme'] = $novoTema;
            $_SESSION['font_size'] = $tamanhoFonte;

            // Persistir via Cookie por 30 dias
            setcookie('app_theme', $novoTema, time() + (86400 * 30), "/");
            setcookie('app_font', $tamanhoFonte, time() + (86400 * 30), "/");

            $mensagemFeedback = "Preferências visuais e de acessibilidade salvas com sucesso!";
            $tipoFeedback = "success";
            break;

        // --- AÇÃO 8: CONFIRMAR ENCERRAMENTO DE SESSÃO ---
        case 'confirmar_sair':
            session_unset();
            session_destroy();
            header("Location: index.php?opcao=sair_confirmado");
            exit;
    }
}

// Exibe mensagem de retorno se houver
if (!empty($mensagemFeedback)) {
    echo "<div class='alert alert-{$tipoFeedback}'>{$mensagemFeedback}</div>";
}

// ESTRUTURA SWITCH...CASE CENTRAL DE ROTEAMENTO DOS 6 MÓDULOS
switch ($opcao) {

    // =========================================================================
    // 1. CADASTRO DE CLIENTES (VERDE)
    // =========================================================================
    case 'A':
    case '1':
    case 'cliente':
        $clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll();
        ?>
        <div class="module-container theme-green">
            <div class="module-header">
                <h2>👤 1. Cadastro de Clientes & Veículos</h2>
                <span class="module-badge">Tema Verde • Receptividade & Atendimento</span>
            </div>

            <form action="index.php?opcao=cliente" method="POST" class="form-grid">
                <input type="hidden" name="acao" value="salvar_cliente">

                <div class="form-group">
                    <label>Nome Completo do Cliente *</label>
                    <input type="text" name="nome" placeholder="Ex: Carlos Eduardo Silva" required>
                </div>

                <div class="form-group">
                    <label>CPF *</label>
                    <input type="text" name="cpf" placeholder="000.000.000-00" required>
                </div>

                <div class="form-group">
                    <label>Telefone / WhatsApp *</label>
                    <input type="text" name="telefone" placeholder="(11) 99999-9999" required>
                </div>

                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="cliente@email.com">
                </div>

                <div class="form-group">
                    <label>Modelo da Moto *</label>
                    <input type="text" name="modelo_moto" placeholder="Ex: Honda CG 160 Titan, Yamaha Fazer 250" required>
                </div>

                <div class="form-group">
                    <label>Placa da Moto *</label>
                    <input type="text" name="placa_moto" placeholder="Ex: ABC-1D23" required>
                </div>

                <div class="form-group">
                    <label>Ano da Moto</label>
                    <input type="text" name="ano_moto" placeholder="Ex: 2023">
                </div>

                <div class="form-group full-width">
                    <label>Observações / Histórico de Manutenção</label>
                    <textarea name="observacoes" placeholder="Ex: Troca de óleo a cada 3.000km. Verificado barulho na corrente."></textarea>
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="btn-action">💾 Salvar Cadastro de Cliente</button>
                </div>
            </form>

            <h3 style="margin-top: 25px; margin-bottom: 12px; color: var(--color-green-dark);">📋 Clientes e Motos Cadastradas</h3>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <input type="text" id="tableSearch" placeholder="🔍 Digite para buscar cliente, CPF ou placa da moto...">
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Modelo da Moto</th>
                            <th>Placa</th>
                            <th>Ano</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr><td colspan="8" style="text-align: center;">Nenhum cliente cadastrado ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['cpf']) ?></td>
                                    <td><?= htmlspecialchars($c['telefone']) ?></td>
                                    <td>🏍️ <?= htmlspecialchars($c['modelo_moto']) ?></td>
                                    <td><span style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-family:monospace; color:#000;"><?= htmlspecialchars($c['placa_moto']) ?></span></td>
                                    <td><?= htmlspecialchars($c['ano_moto'] ?? '-') ?></td>
                                    <td>
                                        <form action="index.php?opcao=cliente" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este cliente?');">
                                            <input type="hidden" name="acao" value="deletar_cliente">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn-action btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">🗑️ Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        break;

    // =========================================================================
    // 2. CADASTRO DE PRODUTOS PARA MOTOS (AZUL)
    // =========================================================================
    case 'B':
    case '2':
    case 'produto':
        $produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();
        ?>
        <div class="module-container theme-blue">
            <div class="module-header">
                <h2>📦 2. Cadastro de Produtos e Peças para Motos</h2>
                <span class="module-badge">Tema Azul • Organização & Precisão Técnica</span>
            </div>

            <form action="index.php?opcao=produto" method="POST" class="form-grid">
                <input type="hidden" name="acao" value="salvar_produto">

                <div class="form-group">
                    <label>Código SKU / Referência *</label>
                    <input type="text" name="codigo_sku" placeholder="Ex: PEC-007 ou OIL-20W50" required>
                </div>

                <div class="form-group">
                    <label>Nome do Produto / Peça *</label>
                    <input type="text" name="nome" placeholder="Ex: Pastilha de Freio Cobreq, Óleo Yamalube 20W50" required>
                </div>

                <div class="form-group">
                    <label>Categoria da Peça *</label>
                    <select name="categoria" required>
                        <option value="Lubrificantes">Lubrificantes & Fluídos</option>
                        <option value="Freios">Sistema de Freios</option>
                        <option value="Transmissão">Transmissão & Kit Relação</option>
                        <option value="Pneus">Pneus & Câmaras de Ar</option>
                        <option value="Elétrica & Motor">Elétrica, Velas & Motor</option>
                        <option value="Filtros">Filtros de Ar e Óleo</option>
                        <option value="Acessórios">Acessórios & Capacetes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Preço de Custo (R$)</label>
                    <input type="number" step="0.01" name="preco_custo" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Preço de Venda (R$) *</label>
                    <input type="number" step="0.01" name="preco_venda" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>Quantidade Inicial em Estoque</label>
                    <input type="number" name="quantidade_estoque" value="10" min="0">
                </div>

                <div class="form-group">
                    <label>Estoque Mínimo para Alerta</label>
                    <input type="number" name="quantidade_minima" value="5" min="1">
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="btn-action">💾 Salvar Produto / Peça</button>
                </div>
            </form>

            <h3 style="margin-top: 25px; margin-bottom: 12px; color: var(--color-blue-dark);">🔩 Catálogo de Peças de Reposição</h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nome do Produto</th>
                            <th>Categoria</th>
                            <th>Preço Custo</th>
                            <th>Preço Venda</th>
                            <th>Qtd Estoque</th>
                            <th>Alerta Mín.</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produtos)): ?>
                            <tr><td colspan="8" style="text-align: center;">Nenhuma peça cadastrada ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td><strong style="font-family:monospace;"><?= htmlspecialchars($p['codigo_sku']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                                    <td>R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                                    <td><strong style="color: var(--color-blue-dark);">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></strong></td>
                                    <td><?= $p['quantidade_estoque'] ?> un.</td>
                                    <td><?= $p['quantidade_minima'] ?> un.</td>
                                    <td>
                                        <form action="index.php?opcao=produto" method="POST" style="display:inline;" onsubmit="return confirm('Deseja excluir esta peça?');">
                                            <input type="hidden" name="acao" value="deletar_produto">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-action btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">🗑️ Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        break;

    // =========================================================================
    // 3. RELATÓRIO DE VENDAS & EMISSÃO DE PDF/IMPRESSÃO (LARANJA)
    // =========================================================================
    case 'C':
    case '3':
    case 'vendas':
        // Consultar Histórico de Vendas com JOIN
        $sqlVendasList = "SELECT v.*, c.nome as cliente_nome, c.modelo_moto, c.placa_moto, p.nome as produto_nome 
                          FROM vendas v
                          JOIN clientes c ON v.cliente_id = c.id
                          JOIN produtos p ON v.produto_id = p.id
                          ORDER BY v.id DESC";
        $vendas = $pdo->query($sqlVendasList)->fetchAll();

        // Métricas Financeiras KPI
        $totalFaturado = $pdo->query("SELECT SUM(valor_total) as total FROM vendas")->fetch()['total'] ?? 0;
        $totalVendasCount = count($vendas);
        $ticketMedio = $totalVendasCount > 0 ? ($totalFaturado / $totalVendasCount) : 0;

        // Lista para popular formulário de nova venda
        $listaClientes = $pdo->query("SELECT id, nome, modelo_moto, placa_moto FROM clientes ORDER BY nome ASC")->fetchAll();
        $listaProdutos = $pdo->query("SELECT id, nome, preco_venda, quantidade_estoque FROM produtos WHERE quantidade_estoque > 0 ORDER BY nome ASC")->fetchAll();
        ?>
        <div class="module-container theme-orange">
            <!-- Cabeçalho visível em Impressão PDF -->
            <div class="print-header" style="display:none;">
                <h2>OFICINA MECÂNICA DE MOTOS - RELATÓRIO DE FATORAMENTO E ORDENS DE SERVIÇO</h2>
                <p>Data de Emissão: <?= date('d/m/Y H:i') ?> | Sistema de Manutenção de Motos PHP</p>
            </div>

            <div class="module-header no-print">
                <h2>💰 3. Relatório de Vendas e Ordens de Serviço (OS)</h2>
                <button onclick="imprimirRelatorio()" class="btn-action" style="background: var(--color-orange-dark);">🖨️ Imprimir Relatório / Gerar PDF</button>
            </div>

            <!-- Indicadores Financeiros em Laranja -->
            <div class="kpi-grid">
                <div class="kpi-card orange">
                    <span>Faturamento Total</span>
                    <h3>R$ <?= number_format($totalFaturado, 2, ',', '.') ?></h3>
                </div>
                <div class="kpi-card orange">
                    <span>Total de Vendas / OS</span>
                    <h3><?= $totalVendasCount ?> atendimentos</h3>
                </div>
                <div class="kpi-card orange">
                    <span>Ticket Médio por Moto</span>
                    <h3>R$ <?= number_format($ticketMedio, 2, ',', '.') ?></h3>
                </div>
            </div>

            <!-- Formulário para Registrar Nova Venda / Lançar OS -->
            <div class="no-print" style="background: var(--bg-container); padding: 20px; border-radius: 14px; margin-bottom: 25px; border: 1px solid var(--border-color);">
                <h4 style="margin-bottom: 15px; color: var(--color-orange-dark);">🛒 Lançar Nova Venda / Ordem de Serviço</h4>
                <form action="index.php?opcao=vendas" method="POST" class="form-grid">
                    <input type="hidden" name="acao" value="salvar_venda">

                    <div class="form-group">
                        <label>Cliente e Moto *</label>
                        <select name="cliente_id" required>
                            <option value="">-- Selecione o Cliente --</option>
                            <?php foreach ($listaClientes as $lc): ?>
                                <option value="<?= $lc['id'] ?>"><?= htmlspecialchars($lc['nome']) ?> (<?= htmlspecialchars($lc['modelo_moto']) ?> - <?= htmlspecialchars($lc['placa_moto']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Peça / Produto *</label>
                        <select name="produto_id" id="venda_produto_id" required>
                            <option value="">-- Selecione a Peça em Estoque --</option>
                            <?php foreach ($listaProdutos as $lp): ?>
                                <option value="<?= $lp['id'] ?>" data-preco="<?= $lp['preco_venda'] ?>"><?= htmlspecialchars($lp['nome']) ?> (Estoque: <?= $lp['quantidade_estoque'] ?> un.) - R$ <?= number_format($lp['preco_venda'], 2, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quantidade *</label>
                        <input type="number" name="quantidade" id="venda_quantidade" value="1" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Valor Unitário (R$)</label>
                        <input type="number" step="0.01" name="valor_unitario" id="venda_valor_unitario" readonly>
                    </div>

                    <div class="form-group">
                        <label>Valor Total (R$)</label>
                        <input type="number" step="0.01" id="venda_valor_total" readonly style="font-weight:bold; color:var(--color-orange-dark);">
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" class="btn-action">🧾 Concluir Venda e Gerar Baixa</button>
                    </div>
                </form>
            </div>

            <h3 style="margin-bottom: 12px; color: var(--color-orange-dark);">📜 Histórico Detalhado de Vendas</h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Nº OS</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Moto & Placa</th>
                            <th>Produto / Peça</th>
                            <th>Qtd</th>
                            <th>Valor Unit.</th>
                            <th>Total Venda</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vendas)): ?>
                            <tr><td colspan="9" style="text-align: center;">Nenhuma venda realizada ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vendas as $v): ?>
                                <tr>
                                    <td><strong style="font-family:monospace;"><?= htmlspecialchars($v['numero_os']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                    <td><strong><?= htmlspecialchars($v['cliente_nome']) ?></strong></td>
                                    <td>🏍️ <?= htmlspecialchars($v['modelo_moto']) ?> (<?= htmlspecialchars($v['placa_moto']) ?>)</td>
                                    <td><?= htmlspecialchars($v['produto_nome']) ?></td>
                                    <td><?= $v['quantidade'] ?> un.</td>
                                    <td>R$ <?= number_format($v['valor_unitario'], 2, ',', '.') ?></td>
                                    <td><strong style="color: var(--color-orange-dark);">R$ <?= number_format($v['valor_total'], 2, ',', '.') ?></strong></td>
                                    <td><span class="status-badge status-normal"><?= htmlspecialchars($v['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        break;

    // =========================================================================
    // 4. CONTROLE DE ESTOQUE E ALERTAS CRÍTICOS (VERMELHO)
    // =========================================================================
    case 'D':
    case '4':
    case 'estoque':
        $produtosEstoque = $pdo->query("SELECT *, (quantidade_estoque * preco_custo) as valor_patrimonio FROM produtos ORDER BY quantidade_estoque ASC")->fetchAll();
        
        // Contagem de alertas críticos (onde quantidade_estoque <= quantidade_minima)
        $criticosCount = 0;
        $patrimonioTotal = 0;
        foreach ($produtosEstoque as $pe) {
            $patrimonioTotal += $pe['valor_patrimonio'];
            if ($pe['quantidade_estoque'] <= $pe['quantidade_minima']) {
                $criticosCount++;
            }
        }
        ?>
        <div class="module-container theme-red">
            <div class="module-header">
                <h2>🚨 4. Controle de Estoque & Peças Sobressalentes</h2>
                <span class="module-badge" style="background: var(--color-red-light); color: var(--color-red-dark);">Tema Vermelho • Atenção & Urgência</span>
            </div>

            <!-- Banners de Alerta Crítico -->
            <?php if ($criticosCount > 0): ?>
                <div class="alert alert-danger">
                    <span>⚠️ <strong>ALERTA DE SEGURANÇA NO ESTOQUE:</strong> Existem <?= $criticosCount ?> produto(s) com quantidade abaixo ou igual ao estoque mínimo estipulado! Reposição urgente necessária.</span>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <span>✅ Todo o estoque de peças está em níveis seguros operacionais.</span>
                </div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card red">
                    <span>Total de Peças Cadastradas</span>
                    <h3><?= count($produtosEstoque) ?> SKUs</h3>
                </div>
                <div class="kpi-card red">
                    <span>Itens em Nível Crítico</span>
                    <h3 style="color: var(--color-red-dark);"><?= $criticosCount ?> itens</h3>
                </div>
                <div class="kpi-card red">
                    <span>Patrimônio em Peças</span>
                    <h3>R$ <?= number_format($patrimonioTotal, 2, ',', '.') ?></h3>
                </div>
            </div>

            <h3 style="margin-bottom: 12px; color: var(--color-red-dark);">⚙️ Inventário Operacional de Estoque</h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Produto / Peça</th>
                            <th>Categoria</th>
                            <th>Qtd Atual</th>
                            <th>Mínimo</th>
                            <th>Preço Venda</th>
                            <th>Status Estoque</th>
                            <th>Reposição Rápida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtosEstoque as $pe): 
                            $isCritico = ($pe['quantidade_estoque'] <= $pe['quantidade_minima']);
                        ?>
                            <tr class="<?= $isCritico ? 'table-row-danger' : '' ?>">
                                <td><strong style="font-family:monospace;"><?= htmlspecialchars($pe['codigo_sku']) ?></strong></td>
                                <td><strong><?= htmlspecialchars($pe['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($pe['categoria']) ?></td>
                                <td><span style="font-size:1.1rem; font-weight:bold;"><?= $pe['quantidade_estoque'] ?></span> un.</td>
                                <td><?= $pe['quantidade_minima'] ?> un.</td>
                                <td>R$ <?= number_format($pe['preco_venda'], 2, ',', '.') ?></td>
                                <td>
                                    <?php if ($isCritico): ?>
                                        <span class="status-badge status-critico">🔴 CRÍTICO / REPOR</span>
                                    <?php else: ?>
                                        <span class="status-badge status-normal">🟢 OK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="index.php?opcao=estoque" method="POST" style="display:flex; gap:6px; align-items:center;">
                                        <input type="hidden" name="acao" value="atualizar_estoque">
                                        <input type="hidden" name="produto_id" value="<?= $pe['id'] ?>">
                                        <input type="number" name="qtd_adicionar" value="5" min="1" style="width: 60px; padding: 4px; text-align:center;">
                                        <button type="submit" class="btn-action" style="padding: 4px 8px; font-size: 0.8rem; background: var(--color-red-dark);">+ Repor</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        break;

    // =========================================================================
    // 5. CONFIGURAÇÕES & ACESSIBILIDADE (ROXO)
    // =========================================================================
    case 'E':
    case '5':
    case 'config':
        $currentTheme = $_SESSION['theme'] ?? 'claro';
        $currentFont = $_SESSION['font_size'] ?? 'normal';
        ?>
        <div class="module-container theme-purple">
            <div class="module-header">
                <h2>⚙️ 5. Configurações & Acessibilidade do Sistema</h2>
                <span class="module-badge">Tema Roxo • Customização & Preferências</span>
            </div>

            <form action="index.php?opcao=config" method="POST" class="settings-panel">
                <input type="hidden" name="acao" value="salvar_configuracoes">

                <div class="setting-item">
                    <div class="setting-info">
                        <h4>🌓 Modo Visual (Tema Claro / Modo Escuro)</h4>
                        <p>Altere as cores da interface para maior conforto visual. A preferência é gravada na sessão e nos cookies.</p>
                    </div>
                    <div>
                        <select name="tema" style="padding: 10px 16px; border-radius: 8px; font-weight:600;">
                            <option value="claro" <?= $currentTheme === 'claro' ? 'selected' : '' ?>>☀️ Modo Claro (Padrão)</option>
                            <option value="escuro" <?= $currentTheme === 'escuro' ? 'selected' : '' ?>>🌙 Modo Escuro (Dark Mode)</option>
                        </select>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h4>🔍 Tamanho da Fonte (Acessibilidade Visual)</h4>
                        <p>Aumente a dimensão do texto para facilitar a leitura por mecânicos e atendentes.</p>
                    </div>
                    <div>
                        <select name="tamanho_fonte" style="padding: 10px 16px; border-radius: 8px; font-weight:600;">
                            <option value="normal" <?= $currentFont === 'normal' ? 'selected' : '' ?>>Normal (16px)</option>
                            <option value="grande" <?= $currentFont === 'grande' ? 'selected' : '' ?>>Grande (18px)</option>
                            <option value="extra-grande" <?= $currentFont === 'extra-grande' ? 'selected' : '' ?>>Extra Grande (20px)</option>
                        </select>
                    </div>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <h4>💾 Persistência de Dados</h4>
                        <p>O banco de dados do sistema está ativo em PDO e pronto para persistir cadastros de motos e vendas.</p>
                    </div>
                    <div>
                        <span class="status-badge status-normal">🟢 Banco Conectado</span>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-action">💾 Salvar Preferências de Sistema</button>
                </div>
            </form>
        </div>
        <?php
        break;

    // =========================================================================
    // 6. SAIR DO SISTEMA (CIANO / TURQUESA)
    // =========================================================================
    case 'F':
    case '6':
    case 'sair':
        ?>
        <div class="module-container theme-teal">
            <div class="module-header">
                <h2>🚪 6. Encerramento de Sessão</h2>
                <span class="module-badge">Tema Ciano • Transição de Saída Segura</span>
            </div>

            <div class="modal-logout">
                <i>🛡️</i>
                <h3>Deseja realmente encerrar sua sessão?</h3>
                <p>Ao confirmar, seus dados de sessão serão destruídos com <code>session_destroy()</code> para manter a segurança do sistema da oficina.</p>

                <form action="index.php?opcao=sair" method="POST" class="logout-actions">
                    <input type="hidden" name="acao" value="confirmar_sair">
                    <a href="index.php" class="btn-action btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-action" style="background: var(--color-teal-dark);">🔒 Confirmar e Sair do Sistema</button>
                </form>
            </div>
        </div>
        <?php
        break;

    // =========================================================================
    // SESSÃO ENCERRADA (CIANO)
    // =========================================================================
    case 'sair_confirmado':
        ?>
        <div class="module-container theme-teal" style="text-align: center; padding: 40px;">
            <h2 style="color: var(--color-teal-dark); margin-bottom: 15px;">👋 Sessão Encerrada com Sucesso!</h2>
            <p style="margin-bottom: 25px; color: var(--text-muted);">A função <code>session_destroy()</code> foi executada. O seu acesso foi finalizado de forma segura.</p>
            <a href="index.php" class="btn-action" style="background: var(--color-teal-dark);">🔑 Fazer Novo Login / Entrar Novamente</a>
        </div>
        <?php
        break;

    // =========================================================================
    // DEFAULT: PAINEL INICIAL (DASHBOARD SUMMARY)
    // =========================================================================
    default:
        ?>
        <div class="module-container" style="text-align: center; padding: 35px 20px;">
            <h2 style="font-size: 1.6rem; margin-bottom: 10px; color: var(--text-main);">🏍️ Bem-vindo ao Sistema de Manutenção de Motos</h2>
            <p style="color: var(--text-muted); max-width: 650px; margin: 0 auto 25px auto;">
                Selecione qualquer uma das 6 opções nos botões acima para executar os módulos via estrutura de decisão <code>switch...case</code> em PHP.
            </p>

            <div class="kpi-grid">
                <div class="kpi-card green">
                    <span>Clientes Cadastrados</span>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn() ?> motos</h3>
                </div>
                <div class="kpi-card blue">
                    <span>Peças no Catálogo</span>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn() ?> SKUs</h3>
                </div>
                <div class="kpi-card orange">
                    <span>Total de Vendas / OS</span>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM vendas")->fetchColumn() ?> OS</h3>
                </div>
                <div class="kpi-card red">
                    <span>Estoque Crítico</span>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM produtos WHERE quantidade_estoque <= quantidade_minima")->fetchColumn() ?> itens</h3>
                </div>
            </div>
        </div>
        <?php
        break;
}
?>