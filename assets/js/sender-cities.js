(function () {
    const config = window.wcEslSenderCitiesConfig || {};
    let selected = config.selected || '';
    let busy = false;
    window.wcEslSenderCities = {
        value: () => selected,
        isChanging: () => busy,
        params(params) {
            // Empty from resets the origin retained by the widget after a previous selection.
            return Object.assign({}, params, { from: selected });
        },
        init(root, onChange) {
            if (!root || Object.keys(config.options || {}).length < 2) return;
            let box = root.parentNode.querySelector('.esl-sender-city');
            if (box) return;
            box = document.createElement('label');
            box.className = 'esl-sender-city';
            box.style.cssText = 'display:block;padding:12px;';
            box.appendChild(document.createTextNode('Город отправки: '));
            const select = document.createElement('select');
            select.setAttribute('aria-label', 'Город отправки');
            Object.entries(config.options).forEach(([value, name]) => {
                select.add(new Option(name, value));
            });
            select.value = selected;
            box.appendChild(select);
            const status = document.createElement('span');
            status.setAttribute('role', 'status');
            box.appendChild(status);
            root.parentNode.insertBefore(box, root);
            select.addEventListener('change', async () => {
                if (busy) return;
                busy = true;
                select.disabled = true;
                status.textContent = ' Пересчёт доставки…';
                try {
                    const response = await fetch(config.ajaxUrl, {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'wc_esl_set_sender_city', nonce: config.nonce, fias: select.value })
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error();
                    selected = select.value;
                    document.querySelectorAll('#wc_esl_billing_terminal, #wc_esl_shipping_terminal, #terminalEsl').forEach(input => { input.value = ''; });
                    onChange();
                    if (window.jQuery) window.jQuery('body').trigger('update_checkout');
                    if (window.wp?.data?.dispatch) {
                        const cart = window.wp.data.dispatch('wc/store/cart');
                        if (cart?.invalidateResolutionForStoreSelector) cart.invalidateResolutionForStoreSelector('getCartData');
                    }
                    status.textContent = '';
                } catch (error) {
                    select.value = selected;
                    status.textContent = ' Не удалось изменить город. Повторите попытку.';
                } finally {
                    busy = false;
                    select.disabled = false;
                }
            });
        }
    };
})();
