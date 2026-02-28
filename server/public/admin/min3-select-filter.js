(function () {
  const setupTomSelect = (select) => {
    if (select.dataset.min3Ready === '1') {
      return;
    }

    if (typeof window.TomSelect === 'undefined') {
      console.error('[min3-select-filter] TomSelect is not available on window.');
      return;
    }

    select.dataset.min3Ready = '1';

    new window.TomSelect(select, {
      plugins: {
        clear_button: { title: '' },
        dropdown_input: {},
        virtual_scroll: {},
      },
      create: false,
      allowEmptyOption: true,
      searchField: ['text'],
      maxOptions: 200,
      closeAfterSelect: true,
      score(search) {
        const query = (search.query || '').trim().toLowerCase();

        return function (item) {
          if (!query) {
            return 1;
          }

          if (query.length < 3) {
            return 0;
          }

          return String(item.text || '').toLowerCase().includes(query) ? 1 : 0;
        };
      },
      render: {
        no_results() {
          return '<div class="no-results">Tapez au moins 3 lettres pour rechercher</div>';
        },
      },
    });
  };

  const init = () => {
    document
      .querySelectorAll('select[data-live-min3="1"]')
      .forEach(setupTomSelect);
  };

  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('ea.collection.item-added', init);
  init();
})();
