(function () {
  const isFixtureFullCreatePage = () => {
    const pathMatches = window.location.pathname.includes('/admin/custom/fixture/new-complete');
    const routeName = new URLSearchParams(window.location.search).get('routeName');

    return pathMatches || routeName === 'admin_fixture_full_new';
  };

  const ensureTomSelectAssets = () => {
    if (window.TomSelect) {
      return Promise.resolve();
    }

    if (window.__min3TomSelectLoadingPromise) {
      return window.__min3TomSelectLoadingPromise;
    }

    const cssHref = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css';
    const jsSrc = 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js';

    window.__min3TomSelectLoadingPromise = new Promise((resolve, reject) => {
      if (!document.querySelector(`link[href="${cssHref}"]`)) {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = cssHref;
        document.head.appendChild(css);
      }

      const existingScript = document.querySelector(`script[src="${jsSrc}"]`);
      if (existingScript) {
        existingScript.addEventListener('load', () => resolve(), { once: true });
        existingScript.addEventListener('error', () => reject(new Error('TomSelect script failed to load')), { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = jsSrc;
      script.defer = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('TomSelect script failed to load'));
      document.head.appendChild(script);
    });

    return window.__min3TomSelectLoadingPromise;
  };

  const setupTomSelect = (select) => {
    if (select.dataset.min3Ready === '1' || !window.TomSelect) {
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

    ensureTomSelectAssets()
      .then(() => {
        document
          .querySelectorAll('select[data-live-min3="1"]')
          .forEach(setupTomSelect);
      })
      .catch(() => {
        // Silent fallback: if remote assets can't load, keep native selects.
      });
  };

  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('ea.collection.item-added', init);
  init();
})();
