// JavaScript Nativo (Vanilla JS) para a Oficina de Motos

document.addEventListener('DOMContentLoaded', () => {
    // Cálculo Automático de Valor Total na Venda de Peças
    const produtoSelect = document.getElementById('venda_produto_id');
    const quantidadeInput = document.getElementById('venda_quantidade');
    const valorUnitarioInput = document.getElementById('venda_valor_unitario');
    const valorTotalInput = document.getElementById('venda_valor_total');

    if (produtoSelect && valorUnitarioInput && valorTotalInput) {
        produtoSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const preco = selectedOption.getAttribute('data-preco');
            if (preco) {
                valorUnitarioInput.value = parseFloat(preco).toFixed(2);
                calcularTotalVenda();
            }
        });

        if (quantidadeInput) {
            quantidadeInput.addEventListener('input', calcularTotalVenda);
        }
    }

    function calcularTotalVenda() {
        if (!valorUnitarioInput || !quantidadeInput || !valorTotalInput) return;
        const qtd = parseInt(quantidadeInput.value) || 0;
        const unitario = parseFloat(valorUnitarioInput.value) || 0;
        const total = qtd * unitario;
        valorTotalInput.value = total.toFixed(2);
    }

    // Filtro de Busca Rápida em Tabelas
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});

// Função para acionar Impressão / PDF do Relatório de Vendas
function imprimirRelatorio() {
    window.print();
}
