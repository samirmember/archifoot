(() => {
    const BUTTON_CLASS = 'ea-location-toggle__button';

    const configurePair = (sourceRow, targetRow) => {
        const sourceLabel = sourceRow.querySelector('.form-control-label');
        if (!sourceLabel || sourceLabel.querySelector(`.${BUTTON_CLASS}`)) {
            return;
        }

        targetRow.classList.add('ea-location-toggle__target');

        const targetInput = targetRow.querySelector('input, textarea');
        const hasInitialValue = Boolean(targetInput && targetInput.value.trim() !== '');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = BUTTON_CLASS;
        button.textContent = '+';
        button.title = 'Ajouter une nouvelle valeur';
        button.setAttribute('aria-label', 'Afficher le champ pour ajouter une nouvelle valeur');
        sourceLabel.appendChild(button);

        const setExpanded = (expanded) => {
            targetRow.hidden = !expanded;
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            button.classList.toggle('is-open', expanded);
            if (expanded && targetInput) {
                targetInput.focus();
            }
        };

        setExpanded(hasInitialValue);

        button.addEventListener('click', () => {
            setExpanded(targetRow.hidden);
        });
    };

    const init = () => {
        const sourceRows = [...document.querySelectorAll('.js-location-source-row')];
        const targetRows = [...document.querySelectorAll('.js-location-target-row')];

        if (!sourceRows.length || sourceRows.length !== targetRows.length) {
            return;
        }

        sourceRows.forEach((sourceRow, index) => {
            configurePair(sourceRow, targetRows[index]);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
        return;
    }

    init();
})();
