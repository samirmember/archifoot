(function () {
  const isFixtureFullCreatePage = () => {
    const pathMatches = window.location.pathname.includes('/admin/custom/fixture/new-complete');
    const routeName = new URLSearchParams(window.location.search).get('routeName');

    return pathMatches || routeName === 'admin_fixture_full_new';
  };

  const setupTomSelect = (select) => {
    if (select.dataset.min3Ready === '1') {
      return;
    }

    if (typeof window.TomSelect === 'undefined') {
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
      loadThrottle: 250,
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
      onInitialize() {
        this.wrapper.classList.add('form-select');
      },
      onType(str) {
        const val = (str || '').trim();
        if (val.length > 0 && val.length < 3) {
          this.refreshOptions(false);
        }
      },
    });
  };

  const init = () => {
    if (!isFixtureFullCreatePage()) {
      return;
    }

    document
      .querySelectorAll('select[data-live-min3="1"]')
      .forEach(setupTomSelect);
  };

  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('ea.collection.item-added', init);
  init();
})();
