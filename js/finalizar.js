document.addEventListener("DOMContentLoaded", () => {

  /* ----------------------------------------------------
     CARREGA ITENS DO CARRINHO
  -----------------------------------------------------*/
  const lista = document.getElementById("pedido-lista");
  const pedido = JSON.parse(localStorage.getItem("pedido")) || [];
  const total = parseFloat(localStorage.getItem("total")) || 0;

  if (pedido.length === 0) {
    lista.innerHTML = "<li id='pedido-feito'>Nenhum item encontrado no pedido.</li>";
  } else {
    pedido.forEach(item => {
      const li = document.createElement("li");
      li.textContent = `${item.nome} (${item.qntd}×) — R$ ${(item.preco * item.qntd).toFixed(2).replace('.', ',')}`;
      lista.appendChild(li);
    });

    const liTotal = document.createElement("li");
    liTotal.innerHTML = `<strong>Total: R$ ${total.toFixed(2).replace('.', ',')}</strong>`;
    lista.appendChild(liTotal);
  }

  /* ----------------------------------------------------
     BUSCA CEP
  -----------------------------------------------------*/
  const cepInput = document.getElementById("cep");
  const ruaInput = document.getElementById("rua");
  const bairroInput = document.getElementById("bairro");

  const cepErroDiv = document.createElement("div");
  cepErroDiv.style.color = "red";
  cepErroDiv.style.fontSize = "0.9em";
  cepErroDiv.style.marginTop = "4px";
  cepInput.insertAdjacentElement("afterend", cepErroDiv);

  cepInput.addEventListener("blur", () => {
    const cep = cepInput.value.replace(/\D/g, "");

    cepErroDiv.textContent = "";
    ruaInput.value = "";
    bairroInput.value = "";

    if (cep.length !== 8) {
      cepErroDiv.textContent = "CEP inválido. Informe 8 números.";
      return;
    }

    ruaInput.value = "...";
    bairroInput.value = "...";

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
      .then(response => response.json())
      .then(data => {
        if (!data.erro) {
          ruaInput.value = data.logradouro || "";
          bairroInput.value = data.bairro || "";
        } else {
          cepErroDiv.textContent = "CEP não encontrado.";
          ruaInput.value = "";
          bairroInput.value = "";
        }
      })
      .catch(() => {
        cepErroDiv.textContent = "Erro ao buscar o CEP.";
        ruaInput.value = "";
        bairroInput.value = "";
      });
  });

  /* ----------------------------------------------------
     ENVIO DO PEDIDO PARA O BANCO
  -----------------------------------------------------*/
  const modal = document.getElementById("modal-sucesso");
  const formEndereco = document.getElementById("form-endereco");

  formEndereco.addEventListener("submit", async (e) => {
    e.preventDefault();

    const pedido = JSON.parse(localStorage.getItem("pedido")) || [];
    const total = parseFloat(localStorage.getItem("total")) || 0;

    if (pedido.length === 0) {
      alert("Seu carrinho está vazio!");
      return;
    }

    try {
      const resposta = await fetch("salvar_pedido.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          itens: pedido,
          total: total
        })
      });

      const resultado = await resposta.json();

      if (resultado.status === "sucesso") {

        // Salva o tema antes de limpar
        const temaAtual = localStorage.getItem("tema");

        // Limpa tudo
        localStorage.clear();

        // Restaura o tema
        if (temaAtual !== null) {
          localStorage.setItem("tema", temaAtual);
        }

        // Mostra modal
        modal.style.display = "flex";

        // Redireciona após 3s
        setTimeout(() => {
          modal.style.display = "none";
          formEndereco.reset();
          window.location.href = "Home.php"; 
        }, 3000);

      } else {
        alert("Erro ao salvar pedido: " + resultado.mensagem);
      }

    } catch (error) {
      alert("Falha ao enviar o pedido ao servidor.");
      console.error(error);
    }
  });

});
