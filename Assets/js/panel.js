document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.panel-tab-btn');
    const tabContents = document.querySelectorAll('.panel-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    const legendItems = document.querySelectorAll('.panel-legend-item');
    const barsH = document.querySelectorAll('.panel-bars-group .bar-h');
    const barsV = document.querySelectorAll('.panel-bars-group .bar-v');

    legendItems.forEach(item => {
        item.addEventListener('click', () => {
            const series = item.getAttribute('data-series');
            const isActive = item.classList.toggle('active');
            item.style.opacity = isActive ? '1' : '0.4';

            const targetBars = series === 'horneadas' ? barsH : barsV;
            targetBars.forEach(bar => {
                bar.style.display = isActive ? '' : 'none';
            });
        });
    });
});