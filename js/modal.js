// Script para pasar el número al modal
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-bs-target="#replyModal"]');

    if (button) {
        const num = button.getAttribute('data-num');
        document.getElementById('displayNum').textContent = num;
        document.getElementById('inputNum').value = num;

        // Limpiamos el historial viejo y cargamos el nuevo
        const historyContainer = document.getElementById('chat-history');
        historyContainer.innerHTML = '<div class="text-center">Cargando historial...</div>';

        fetch('../response/getHistory.php?remitente=' + encodeURIComponent(num))
            .then(response => response.text())
            .then(html => {
                historyContainer.innerHTML = html;
                // Auto-scroll al final del chat
                historyContainer.scrollTop = historyContainer.scrollHeight;
            });

        const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('replyModal'));
        modalInstance.show();
    }
});
const myModal = document.getElementById('replyModal');
myModal.addEventListener('shown.bs.modal', () => {
    // Busca el textarea dentro del modal y le da el foco
    myModal.querySelector('textarea').focus();
});