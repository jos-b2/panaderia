document.addEventListener('DOMContentLoaded', function () {
    const side = document.querySelector('.side') || document.getElementById('side');
    const scrim = document.getElementById('scrim');
    const burger = document.getElementById('burger');
    const brandToggle = document.getElementById('brandToggle');

    if (localStorage.getItem('sideCollapsed') === '1' && window.innerWidth > 820) {
        if (side) side.classList.add('collapsed');
        document.body.classList.add('side-collapsed');
    }

    if (brandToggle && side) {
        brandToggle.addEventListener('click', function () {
            if (window.innerWidth <= 820) return;
            const isCollapsed = side.classList.toggle('collapsed');
            document.body.classList.toggle('side-collapsed', isCollapsed);
            localStorage.setItem('sideCollapsed', isCollapsed ? '1' : '0');
        });
    }

    function toggleSidebar(open) {
        if (side) side.classList.toggle('open', open);
        if (scrim) scrim.classList.toggle('show', open);
    }

    if (burger) {
        burger.addEventListener('click', function () {
            const isOpen = side && side.classList.contains('open');
            toggleSidebar(!isOpen);
        });
    }

    if (scrim) {
        scrim.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }
});