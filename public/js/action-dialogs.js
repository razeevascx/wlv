if (!('commandForElement' in HTMLButtonElement.prototype)) {
    document.querySelectorAll('button[command="show-modal"][commandfor]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.getAttribute('commandfor'))?.showModal();
        });
    });

    document.querySelectorAll('button[command="close"][commandfor]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.getAttribute('commandfor'))?.close();
        });
    });
}
