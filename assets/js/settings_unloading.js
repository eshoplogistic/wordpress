window.addEventListener('load', function(event) {
    eslRun()
});

function eslRun() {

    //modal
    let modalEsl = document.getElementById("modal-esl")
    let modalEslInfo = document.getElementById("modal-esl-info")
    if(modalEsl) { 
        let span = modalEsl.getElementsByClassName("close_modal_window")[0]
        span.onclick = function () {
            modalEsl.style.display = "none"
        }

    }
    if(modalEslInfo) {
        let spanInfo = modalEslInfo.getElementsByClassName("close_modal_window")[0]
        spanInfo.onclick = function () {
            modalEslInfo.style.display = "none"
        }
    }
    //let modalDoorButton = document.getElementById("buttonModalUnload")

    //modalDoorButton.onclick = function () {
        //modalEsl.style.display = "none"
    //}

    window.onclick = function (event) {
        if (event.target === modalEsl) {
            modalEsl.style.display = "none"
        }
        if (event.target === modalEslInfo) {
            modalEslInfo.style.display = "none"
        }
    }

    let bindEvents = {
        clickOnTerminals: function (event) {
            modalEsl.style.display = "block"
        },
        clickOnInfo: function (event) {
            modalEslInfo.style.display = "block"

            let order_id = document.getElementById("order_info_id").value
            let order_type = document.getElementById("order_info_type").value
            const xhr = new XMLHttpRequest()
            xhr.open("POST", wc_esl_shipping_global.ajaxUrl);
            let params = 'action=wc_esl_shipping_unloading_info&order_id='+order_id+'&order_type='+order_type+'&esl_nonce='+wc_esl_shipping_global.eslNonce;
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.send(params)
            xhr.onload = () => {
                let obj = JSON.parse(xhr.responseText);
                modalEslInfo.querySelector('main').innerHTML = obj.data;
            }
        },
        clickOnDelete: function (event) {
            if (!window.confirm('Внимание! Все данные по выгрузке доставки будут безвозвратно удалены из заказа. Продолжить?')) {
                return;
            }

            let order_id = document.getElementById("order_info_id").value
            let order_type = document.getElementById("order_info_type").value

            PreloaderEsl.show('#woocommerce-order-esl-unloading');
            const xhr = new XMLHttpRequest()
            xhr.open("POST", wc_esl_shipping_global.ajaxUrl);
            let params = 'action=wc_esl_shipping_unloading_delete&order_id='+order_id+'&order_type='+order_type+'&esl_nonce='+wc_esl_shipping_global.eslNonce;
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.send(params)
            xhr.onload = () => {
                let obj = JSON.parse(xhr.responseText);
                PreloaderEsl.hide('#woocommerce-order-esl-unloading');
                PushEsl.addItem(obj.success ? 'success' : 'error', obj.msg);
                // Локальное состояние заявки на сервере сброшено — перезагружаем,
                // чтобы кнопки "Выгрузить"/"Удалить" сразу отразили новое состояние.
                if (obj.success) {
                    window.location.reload();
                }
            }
        },
        clickOnStatusUpdate: function (event) {
            let order_id = document.getElementById("order_info_id").value
            let order_type = document.getElementById("order_info_type").value

            PreloaderEsl.show('#woocommerce-order-esl-unloading');
            const xhr = new XMLHttpRequest()
            xhr.open("POST", wc_esl_shipping_global.ajaxUrl);
            let params = 'action=wc_esl_shipping_unloading_status_update&order_id='+order_id+'&order_type='+order_type+'&esl_nonce='+wc_esl_shipping_global.eslNonce;
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.send(params)
            xhr.onload = () => {
                let obj = JSON.parse(xhr.responseText);
                PreloaderEsl.hide('#woocommerce-order-esl-unloading');
                PushEsl.addItem(obj.success ? 'success' : 'error', obj.data);
            }
        },
        onCloseModal: function () {
        },
    }


    let els_terminals_buttons = document.getElementById('esl_unloading_form')
    if(els_terminals_buttons)
        els_terminals_buttons.addEventListener('click', bindEvents.clickOnTerminals, false)

    let els_terminals_info = document.getElementById('esl_unloading_status')
    if(els_terminals_info)
        els_terminals_info.addEventListener('click', bindEvents.clickOnInfo, false)

    let els_terminals_delete = document.getElementById('esl_unloading_delete')
    if(els_terminals_delete)
        els_terminals_delete.addEventListener('click', bindEvents.clickOnDelete, false)

    let els_terminals_status_update = document.getElementById('esl_unloading_status_update')
    if(els_terminals_status_update)
        els_terminals_status_update.addEventListener('click', bindEvents.clickOnStatusUpdate, false)

}

// Сравнение суммы заказа с суммой заявленных мест перед отправкой формы выгрузки.
// Пороговое значение (1 руб.) и формулы взяты из МС (script.js: getOrderSumMismatch).
function eslParseFloatSafe(value) {
    if (value === null || value === undefined) {
        return 0;
    }
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }
    let normalized = String(value).replace(',', '.').replace(/[^0-9.\-]/g, '');
    let parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
}

function eslRoundMoney(value) {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

function eslCalculatePlacesSum(products) {
    if (!products) {
        return 0;
    }
    let total = 0;
    let items = Array.isArray(products) ? products : Object.values(products);
    items.forEach(function (item) {
        if (!item) {
            return;
        }
        let price = eslParseFloatSafe(item.price);
        let quantity = eslParseFloatSafe(item.quantity);
        if (price > 0 && quantity > 0) {
            total += price * quantity;
        }
    });
    return total;
}

function eslGetOrderSumMismatch(formData) {
    if (!Object.prototype.hasOwnProperty.call(formData, 'order_sum')) {
        return null;
    }
    let orderSum = eslRoundMoney(eslParseFloatSafe(formData.order_sum));
    let placesSum = eslRoundMoney(eslCalculatePlacesSum(formData.products));
    if (Math.abs(orderSum - placesSum) > 1) {
        return {
            orderSum: orderSum,
            placesSum: placesSum,
            deviation: eslRoundMoney(placesSum - orderSum),
        };
    }
    return null;
}

(function( $ ) {

    $( document ).ready( function( e ) {
        $('#buttonModalUnload').click(function(e) {
            e.preventDefault();

            let formData = $('#unloading_form').serializeControls();
            let mismatch = eslGetOrderSumMismatch(formData);
            if (mismatch) {
                let deviationSign = mismatch.deviation >= 0 ? '+' : '';
                let confirmed = window.confirm(
                    'Сумма стоимости мест не совпадает с суммой заказа.\n' +
                    'Сумма мест: ' + mismatch.placesSum.toFixed(2) + ' руб.\n' +
                    'Сумма заказа: ' + mismatch.orderSum.toFixed(2) + ' руб.\n' +
                    'Отклонение: ' + deviationSign + mismatch.deviation.toFixed(2) + ' руб.\n' +
                    'Продолжить выгрузку?'
                );
                if (!confirmed) {
                    return;
                }
            }

            let data = JSON.stringify(formData, null, 2);
            PreloaderEsl.show('#unloading_form');

            $.ajax({
                method: 'POST',
                url: wc_esl_shipping_global.ajaxUrl,
                async: true,
                data: {
                    action : 'wc_esl_shipping_unloading_enable',
                    nonce : wc_esl_shipping_global.nonce,
                    data : data
                },
                dataType: 'json',
                success: function( response ) {
                    PreloaderEsl.hide('#unloading_form');

                    if(response.success === true)
                        document.getElementById("modal-esl").style.display = "none";

                    PreloaderEsl.hide('#woocommerce-order-esl-unloading');
                    PushEsl.addItem(response.success === true ? 'success' : 'error', response.msg);

                    // Заявка создана — перезагружаем, чтобы кнопки "Выгрузить"/"Удалить"
                    // и статус заявки сразу отразили новое состояние (как при удалении).
                    if (response.success === true) {
                        window.location.reload();
                    }
                }
            });
        });

        $.fn.serializeControls = function() {
            let data = {};
            function buildInputObject(arr, val) {
                if(val === 'on')
                    val = 1

                if (arr.length < 1)
                    return val;

                let objkey = arr[0];
                if (objkey.slice(-1) == "]") {
                    objkey = objkey.slice(0,-1);
                }
                let result = {};
                if (arr.length == 1){
                    result[objkey] = val;
                } else {
                    arr.shift();
                    result[objkey] = buildInputObject(arr, val);
                }
                return result;
            }
            $.each(this.serializeArray(), function() {
                let val = this.value;
                let c = this.name.split("[");
                let a = buildInputObject(c, val);
                $.extend(true, data, a);
            });

            return data;
        }

        for (const a of document.querySelectorAll("th")) {
            if (a.textContent.includes("esl_shipping_methods:")) {
                a.parentElement.style.display = 'none'
            }
        }

    });

})( jQuery );

// Показывает/скрывает и включает/отключает поле "Сумма к взятию с получателя" в зависимости
// от состояния соседнего чекбокса "Взять оплату с получателя за доставку" (esl-take-payment-toggle).
function eslSyncCostToggle(checkbox) {
    let wrapper = checkbox.closest('.esl-take-payment-toggle');
    if (!wrapper) {
        return;
    }

    let nameArr = checkbox.name.split('[')[0];
    let target = document.getElementById('esl-cost-toggle-' + nameArr);
    if (!target) {
        return;
    }

    let costInput = target.querySelector('input');
    if (checkbox.checked) {
        target.style.display = '';
        if (costInput) {
            costInput.disabled = false;
        }
    } else {
        target.style.display = 'none';
        if (costInput) {
            costInput.disabled = true;
            costInput.value = '';
        }
    }
}

document.addEventListener('change', function (e) {
    if (!e.target.matches('.esl-take-payment-toggle input[type="checkbox"]')) {
        return;
    }
    eslSyncCostToggle(e.target);
});

window.addEventListener('load', function () {
    document.querySelectorAll('.esl-take-payment-toggle input[type="checkbox"]').forEach(eslSyncCostToggle);
});

// Поле "Способ отгрузки в ТК" (pick_up) определяет, какие поля отправителя реально
// используются на бэкенде (Modules/Unloading.php: pick_up=0 -> sender-terminal, pick_up=1 -> адрес).
// Здесь просто отключаем неиспользуемые поля, чтобы они не вводили оператора в заблуждение
// и не отправлялись вместе с формой (disabled-поля не попадают в serializeArray()).
function eslSyncPickUpToggle(select) {
    let form = select.closest('#unloading_form');
    if (!form) {
        return;
    }

    let value = select.value;

    form.querySelectorAll('.esl-pickup-terminal').forEach(function (field) {
        let input = field.querySelector('.form-value');
        if (input) {
            input.disabled = (value === '1');
        }
    });

    form.querySelectorAll('.esl-pickup-address').forEach(function (field) {
        let input = field.querySelector('.form-value');
        if (input) {
            input.disabled = (value === '0');
        }
    });
}

document.addEventListener('change', function (e) {
    if (!e.target.matches('#unloading_form select[name="pick_up"]')) {
        return;
    }
    eslSyncPickUpToggle(e.target);
});

window.addEventListener('load', function () {
    document.querySelectorAll('#unloading_form select[name="pick_up"]').forEach(eslSyncPickUpToggle);
});

// ПЭК: тип отправителя (юрлицо/ИП/физлицо) определяет, какие поля реально нужны —
// документ+ФИО представителя (юрлицо/ИП) или реквизиты организации (физлицо).
// Как и pick_up выше — отключаем неиспользуемые поля, чтобы не отправлялись с формой.
function eslSyncPecomSenderType(select) {
    let form = select.closest('#unloading_form');
    if (!form) {
        return;
    }

    let value = select.value;
    let showIdentity = (value === '1' || value === '2');

    form.querySelectorAll('.esl-pecom-sender-identity .form-value').forEach(function (input) {
        input.disabled = !showIdentity;
    });
    form.querySelectorAll('.esl-pecom-sender-requisites .form-value').forEach(function (input) {
        input.disabled = showIdentity;
    });
}

document.addEventListener('change', function (e) {
    if (!e.target.matches('#unloading_form select[name="sender-entity-type-pecom[value]"]')) {
        return;
    }
    eslSyncPecomSenderType(e.target);
});

window.addEventListener('load', function () {
    document.querySelectorAll('#unloading_form select[name="sender-entity-type-pecom[value]"]').forEach(eslSyncPecomSenderType);
});

// ПЭК: тип получателя определяет паспортные данные (физлицо) или реквизиты
// организации/ИП (ИНН/КПП).
function eslSyncPecomReceiverType(select) {
    let form = select.closest('#unloading_form');
    if (!form) {
        return;
    }

    let value = select.value;
    let showIdentity = (value === '1');

    form.querySelectorAll('.esl-pecom-receiver-identity .form-value').forEach(function (input) {
        input.disabled = !showIdentity;
    });
    form.querySelectorAll('.esl-pecom-receiver-requisites .form-value').forEach(function (input) {
        input.disabled = showIdentity;
    });
}

document.addEventListener('change', function (e) {
    if (!e.target.matches('#unloading_form select[name="receiver[identity][type]"]')) {
        return;
    }
    eslSyncPecomReceiverType(e.target);
});

window.addEventListener('load', function () {
    document.querySelectorAll('#unloading_form select[name="receiver[identity][type]"]').forEach(eslSyncPecomReceiverType);
});

// "Места": обычная HTML-таблица с <template> для клонирования новой строки —
// перенесено из МС (assets/js/table_offers.js: elemCreateInFrameTableOffers/
// deleteFrameTableElem). WP_List_Table для этой задачи не подходил: он всегда
// рисовал второй ряд заголовков снизу (thead+tfoot) и не переиндексировал имена
// полей при удалении строки, из-за чего после удаления не первой строки и
// добавления новой могли получиться два input с одинаковым name="products[N][...]"
// (последний перетирал первый при сборке данных на отправку).
function eslPlacesRenumber(table) {
    if (!table) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach(function (tr, index) {
        tr.setAttribute('data-number', index);
        tr.querySelectorAll('td input[data-field]').forEach(function (input) {
            input.name = 'products[' + index + '][' + input.getAttribute('data-field') + ']';
        });
    });
}

function eslAddPlaceRow(button) {
    let wrapper = button.closest('.esl-places__main');
    if (!wrapper) {
        return;
    }

    let table = wrapper.querySelector('.esl-places-table');
    let template = wrapper.querySelector('template.esl-row-template');
    if (!table || !template) {
        return;
    }

    let row = template.content.firstElementChild.cloneNode(true);
    table.querySelector('tbody').appendChild(row);
    eslPlacesRenumber(table);
}

function eslDeletePlaceRow(button) {
    let table = button.closest('.esl-places-table');
    let row = button.closest('tr');
    if (!table || !row) {
        return;
    }

    row.remove();
    eslPlacesRenumber(table);
}

document.addEventListener('click', function (e) {
    if (e.target.id === 'buttonModalUnloadAdd') {
        e.preventDefault();
        eslAddPlaceRow(e.target);
        return;
    }

    let deleteBtn = e.target.closest('.esl-delete_table_elem');
    if (deleteBtn) {
        e.preventDefault();
        eslDeletePlaceRow(deleteBtn);
    }
});

function copyToClipboard(containerid, e) {
    let elemText = containerid
    let elemBut = e.id
    new ClipboardJS('#'+elemBut, {
        text: function(trigger) {
            return elemText.value
        }
    }).on('success', function(e) {
        let button = document.getElementById(elemBut);
        button.textContent = 'Скопировано'
    });
}