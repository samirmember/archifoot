(function () {
  const isFixtureFullCreatePage = () => {
    const pathMatches = window.location.pathname.includes('/admin/custom/fixture/new-complete');
    const routeName = new URLSearchParams(window.location.search).get('routeName');

    return pathMatches || routeName === 'admin_fixture_full_new';
  };

  const setupSelect = (select) => {
    if (select.dataset.min3Ready === '1') {
      return;
    }
    select.dataset.min3Ready = '1';

    const options = Array.from(select.options).map((option) => ({
      value: option.value,
      text: option.text,
      selected: option.selected,
      disabled: option.disabled,
    }));

    const wrapper = document.createElement('div');
    wrapper.className = 'ea-min3-filter';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control mb-2';
    input.placeholder = 'Tapez au moins 3 lettres pour filtrer...';

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(input);
    wrapper.appendChild(select);

    const renderOptions = (term) => {
      const selectedValue = select.value;
      const normalized = term.trim().toLowerCase();
      const shouldFilter = normalized.length >= 3;

      select.innerHTML = '';
      const filtered = shouldFilter
        ? options.filter((option) => option.text.toLowerCase().includes(normalized) || option.selected)
        : options;

      filtered.forEach((optionData) => {
        const option = document.createElement('option');
        option.value = optionData.value;
        option.text = optionData.text;
        option.disabled = optionData.disabled;
        option.selected = optionData.value === selectedValue || optionData.selected;
        select.appendChild(option);
      });
    };

    input.addEventListener('input', () => renderOptions(input.value));
  };

  const init = () => {
    if (!isFixtureFullCreatePage()) {
      return;
    }

    document
      .querySelectorAll('select[data-live-min3="1"]')
      .forEach(setupSelect);
  };

  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('ea.collection.item-added', init);
  init();
})();
