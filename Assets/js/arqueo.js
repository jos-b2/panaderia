document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.arq-tab-btn');
    const tabContents = document.querySelectorAll('.arq-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });
});