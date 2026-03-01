(function () {
  const minLength = 3;

  const setupTomSelect = (select) => {
    if (select.dataset.min3Ready === '1') {
      return;
    }

    if (typeof window.TomSelect === 'undefined') {
      console.error('[min3-select-filter] TomSelect is not available on window.');
      return;
    }

    const remoteType = select.dataset.remoteType;
    if (!remoteType) {
      return;
    }

    const initialOptions = Array.from(select.options)
      .filter((option) => option.value !== '')
      .map((option) => ({
        value: option.value,
        text: option.text,
      }));

    select.dataset.min3Ready = '1';

    new window.TomSelect(select, {
      plugins: {
        clear_button: { title: '' },
        dropdown_input: {},
      },
      create: false,
      allowEmptyOption: true,
      searchField: ['text'],
      maxOptions: 30,
      closeAfterSelect: true,
      options: initialOptions,
      shouldLoad(query) {
        return query.trim().length >= minLength;
      },
      load(query, callback) {
        const trimmed = query.trim();
        if (trimmed.length < minLength) {
          callback();
          return;
        }

        fetch(`/api/admin-search/${encodeURIComponent(remoteType)}?q=${encodeURIComponent(trimmed)}`)
          .then((response) => response.ok ? response.json() : [])
          .then((items) => callback(Array.isArray(items) ? items : []))
          .catch(() => callback());
      },
      render: {
        no_results() {
          return `<div class="no-results">Tapez au moins ${minLength} lettres pour rechercher</div>`;
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
