document.addEventListener('DOMContentLoaded', () => {
    function alterarStatus(id, oculto) {
    let formData = new FormData();
    formData.append("id", id);
    formData.append("oculto", oculto);

    fetch("", {
        method: "POST",
        body: formData
    }).then(() => {
        // recarrega só o botão sem recarregar a página toda
        location.reload();
    });
}
    });