document.addEventListener("DOMContentLoaded", () => {
    const itens = document.querySelectorAll(".item");

    // Preços base das pizzas
    const precopizzas = {
        "P": 20.00,
        "M": 40.00,
        "G": 50.00
    };

    // Para cada pizza do cardápio
    itens.forEach(item => {
        const selectTamanho = item.querySelector(".tamanho");
        const quantidade = item.querySelector(".qntd");
        const precoElemento = item.querySelector(".preco");

        function atualizarPreco() {
            const tamanhoSelecionado = selectTamanho.value;
            const qtd = parseInt(quantidade.value);

            if (precopizzas[tamanhoSelecionado]) {
                const total = precopizzas[tamanhoSelecionado] * qtd;
                precoElemento.textContent = `Preço: R$ ${total.toFixed(2)}`;
            } else {
                precoElemento.textContent = "Preço: ";
            }
        }

        selectTamanho.addEventListener("change", atualizarPreco);
        quantidade.addEventListener("input", atualizarPreco);
        atualizarPreco();
    });
});

// Atualiza preço bebida e sobremesas
document.addEventListener("DOMContentLoaded", () => {
    const itens = document.querySelectorAll(".item");

    itens.forEach((item) => {
        const precoSpan = item.querySelector(".preco-valor");
        const inputQtd = item.querySelector("input.qntd-dinamica");

        if (precoSpan && inputQtd) {
            const precoBase = parseFloat(precoSpan.textContent.replace("R$", "").replace(",", "."));

            const atualizarPreco = () => {
                const qtd = parseInt(inputQtd.value) || 0;
                const total = precoBase * qtd;
                precoSpan.textContent = `R$ ${total.toFixed(2).replace(".", ",")}`;
            };

            inputQtd.addEventListener("input", atualizarPreco);
            atualizarPreco();
        }
    });
});

// Atualiza foto da bebida lata
document.addEventListener("DOMContentLoaded", () => {
  const cocaLata = document.getElementById("coca");
  const spriteLata = document.getElementById("sprite");
  const pepsiLata = document.getElementById("pepsi");
  const refriPequeno = document.getElementById("refri-lata");

  function atualizatarRefriLata(valor) {
    cocaLata.style.display = (valor === "cocaLata") ? "flex" : "none";
    spriteLata.style.display = (valor === "spriteLata") ? "flex" : "none";
    pepsiLata.style.display = (valor === "pepsiLata") ? "flex" : "none";
  }

  atualizatarRefriLata(refriPequeno.value);
  refriPequeno.addEventListener("change", () => atualizatarRefriLata(refriPequeno.value));
});

// Atualiza foto da bebida PET
document.addEventListener("DOMContentLoaded", () => {
  const cocaPet = document.getElementById("cocaLitro");
  const spritePet = document.getElementById("spriteLitro");
  const pepsiPet = document.getElementById("pepsiLitro");
  const refriGrande = document.getElementById("refri-pet");

  function atualizarRefriPet(valor) {
    cocaPet.style.display = (valor === "coca-litro") ? "flex" : "none";
    spritePet.style.display = (valor === "sprite-litro") ? "flex" : "none";
    pepsiPet.style.display = (valor === "pepsi-litro") ? "flex" : "none";
  }

  atualizarRefriPet(refriGrande.value);
  refriGrande.addEventListener("change", () => atualizarRefriPet(refriGrande.value));
});

// Seletor do pedido e total
const PedidoDiv = document.querySelector(".pedido");
const totalSpan = document.getElementById("total");

// Estado
let pedido = JSON.parse(localStorage.getItem("pedido")) || [];
let total = parseFloat(localStorage.getItem("total")) || 0;

atualizarPedido();

// ADICIONAR PEDIDO
function adicionarPedido(botao) {
  const itemDiv = botao.closest(".item");
  const nome = itemDiv.querySelector("h3").textContent.trim();

  // PEGA O TAMANHO CORRETAMENTE
  const tamanhoSelect = itemDiv.querySelector(".tamanho");
  const tamanho = tamanhoSelect ? tamanhoSelect.value : null;

  const precoSpanPizza = itemDiv.querySelector(".preco");
  const precoSpanOutros = itemDiv.querySelector(".preco-valor");

  const precoStr = precoSpanPizza?.textContent || precoSpanOutros?.textContent || "";
  const preco = parseFloat(precoStr.replace("Preço:", "").replace("R$", "").replace(",", ".").trim());

  const qntdInput = itemDiv.querySelector('input[type="number"]');
  const qntd = parseInt(qntdInput?.value);

  if (isNaN(preco) || isNaN(qntd) || qntd <= 0) {
    alert("Selecione um tamanho e informe uma quantidade válida maior que 0.");
    return;
  }

  // SALVA O TAMANHO NO OBJETO
  pedido.push({ nome, preco, qntd, tamanho });

  total += preco * qntd;

  qntdInput.value = "";

  salvarEstado();
  atualizarPedido();
}

// Atualiza visualização
function atualizarPedido() {
  if (pedido.length === 0) {
    PedidoDiv.innerHTML = "<p class='pedido-vazio'>Nenhum item selecionado ainda.</p>";
  } else {
    PedidoDiv.innerHTML = pedido.map((item, i) => {
      const subtotal = (item.preco * item.qntd).toFixed(2).replace('.', ',');

      // AGORA MOSTRA O TAMANHO NA LISTA
      const tamanhoTexto = item.tamanho ? ` (${item.tamanho})` : "";

      return `
        <div class="item-pedido">
          <span>${item.nome}${tamanhoTexto} (${item.qntd}×) - R$ ${subtotal}</span>
          <button class="btn-remover" onclick="removerPizza(${i})">❌</button>
        </div>`;
    }).join("");
  }
  totalSpan.textContent = total.toFixed(2).replace('.', ',');
}

// Remover item
function removerPizza(index) {
  const item = pedido[index];
  total -= item.preco * item.qntd;
  pedido.splice(index, 1);
  salvarEstado();
  atualizarPedido();
}

// Finalizar pedido
function finalizarPedido() {
  if (pedido.length === 0) {
    alert("Adicione pelo menos um item!");
    return;
  }
  window.location.href = "finalizar.php";
}

// Limpar pedido
function limparPedido() {
  pedido = [];
  total = 0;
  salvarEstado();
  atualizarPedido();
}

// Salvar no localStorage
function salvarEstado() {
  localStorage.setItem("pedido", JSON.stringify(pedido));
  localStorage.setItem("total", total.toString());
}
