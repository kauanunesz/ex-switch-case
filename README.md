# Sistema de Manutenção de Motos em PHP (Switch Case)

Este projeto foi desenvolvido como uma solução completa para a atividade prática de **Sistema de Manutenção de Motos em PHP**, aplicando estruturas de decisão (`switch...case`), banco de dados PDO (MySQL & SQLite), persistência de sessão e estilização temática individualizada por módulo.

---

## 🎨 Diretrizes de Estilo e Temas para os Formulários

Conforme o guia de estilo e identidade visual da atividade, cada módulo/formulário possui sua paleta temática dedicada:

1. **Cadastro de Clientes & Veículos (`Verde - #22c55e / #16a34a`)**:
   - Transmite receptividade e início de atendimento.
   - Campos: Nome Completo, CPF, Telefone, E-mail, Modelo da Moto (ex: Honda CG 160 Titan), Placa (ex: ABC-1D23), Ano e Observações de manutenção.
2. **Cadastro de Produtos para Motos (`Azul - #3b82f6 / #2563eb`)**:
   - Transmite organização de catálogo e precisão técnica.
   - Campos: SKU / Referência, Nome da Peça (ex: Pastilha de Freio Cobreq, Óleo 20W50), Categoria, Preço de Custo, Preço de Venda, Qtd Estoque e Estoque Mínimo.
3. **Relatório de Vendas (`Laranja - #f97316 / #ea580c`)**:
   - Transmite movimentação financeira e faturamento.
   - Inclui indicadores financeiros KPI (Total Faturado, Qtd de Vendas, Ticket Médio por Moto), lançamento de Ordens de Serviço (OS) e botão para **Impressão / PDF formatado (`@media print` + `window.print()`)**.
4. **Controle de Estoque (`Vermelho - #ef4444 / #dc2626`)**:
   - Transmite atenção e urgência no manejo de peças.
   - Apresenta destaques visuais em vermelho para **estoque crítico (`quantidade_estoque <= quantidade_minima`)** e formulário de reposição rápida.
5. **Configurações / Acessibilidade (`Roxo - #a855f7 / #9333ea`)**:
   - Transmite customização e preferências.
   - Alternância entre **Modo Claro** e **Modo Escuro (Dark Mode)**, ajuste de tamanho de fonte e persistência via `$_SESSION` e Cookies (`app_theme`).
6. **Sair do Sistema (`Ciano/Turquesa - #06b6d4 / #0891b2`)**:
   - Transmite neutralidade e saída segura.
   - Caixa de diálogo para confirmação e destruição de dados de sessão (`session_destroy()`).

---

## 🛠️ Tecnologias Utilizadas

- **PHP 8+** (Estruturas `switch...case`, `$_SESSION`, PDO Prepared Statements contra SQL Injection)
- **MySQL / SQLite** (Estruturas DDL/DML relacionais)
- **HTML5 & CSS3** (CSS Grid, Flexbox, Variáveis CSS `:root`, `@media print`)
- **JavaScript (Vanilla JS)** (Cálculo dinâmico de totais de venda, busca em tabelas, disparo de `window.print()`)

---

## 🗄️ Estrutura de Arquivos do Projeto

- `index.php`: Layout principal com cabeçalho, grid de navegadores e leitor de preferências de sessão/cookies.
- `processa.php`: Arquivo central de roteamento com o bloco `switch...case` e controladores CRUD das ações.
- `db.php`: Conexão PDO segura com suporte a MySQL e fallback automático para SQLite (com criação de tabelas e dados iniciais de oficina de motos).
- `database.sql`: Script SQL nativo DDL/DML para importação manual no phpMyAdmin.
- `style.css`: Estilização completa com temas coloridos, dark mode e layout responsivo.
- `script.js`: Interatividade front-end (cálculo de vendas, busca e impressão).

---

## 🚀 Como Executar o Projeto

1. Coloque esta pasta em seu servidor PHP local (ex: `c:\xampp\htdocs\ex-switch-case`).
2. Acesse via navegador em `http://localhost/ex-switch-case/`.
3. Ou execute via CLI PHP:
   ```bash
   php -S localhost:8000
   ```
   E acesse `http://localhost:8000`.