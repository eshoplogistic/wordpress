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
            let order_id = document.getElementById("order_info_id").value
            let order_type = document.getElementById("order_info_type").value
            const xhr = new XMLHttpRequest()
            xhr.open("POST", wc_esl_shipping_global.ajaxUrl);
            let params = 'action=wc_esl_shipping_unloading_delete&order_id='+order_id+'&order_type='+order_type+'&esl_nonce='+wc_esl_shipping_global.eslNonce;
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.send(params)
            xhr.onload = () => {
                let obj = JSON.parse(xhr.responseText);
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


(function( $ ) {

    $( document ).ready( function( e ) {
        $('#buttonModalUnload').click(function(e) {
            e.preventDefault();

            let data = JSON.stringify($('#unloading_form').serializeControls(), null, 2);
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
                }
            });
        });

        $('#buttonModalUnloadAdd').click(function(e) {
            e.preventDefault();
            let table = document.querySelector('.esl_list_links');
            let tbodyTr = table.querySelector('tbody tr');
            let tbodyTrAll = table.querySelectorAll('tbody tr');
            let tbodyTd = tbodyTr.querySelectorAll('td');
            let tbodyTdArray = [...tbodyTd];
            let tbodyTrArray = [...tbodyTrAll];
            let tbodyTrArrayCount = Number(tbodyTrArray.length)+1;
            let tr = document.createElement('tr');
            tbodyTdArray.forEach(element => {
                let td = document.createElement('td');
                let input = document.createElement('input');
                input.name = 'products['+tbodyTrArrayCount+']['+element.getAttribute('name')+']';
                td.appendChild(input);
                tr.appendChild(td);
            });


            table.querySelector('tbody').appendChild(tr);
        });

        $('.esl-delete_table_elem').click(function(e) {
            e.preventDefault();
            $(this).parents('tr').remove();
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

// Форматирует ввод времени в поле ЧЧ:ММ по мере набора текста (esl-time-mask).
document.addEventListener('input', function (e) {
    if (!e.target.matches('.esl-time-mask')) {
        return;
    }

    let digits = e.target.value.replace(/\D/g, '').slice(0, 4);
    let hours = digits.slice(0, 2);
    let minutes = digits.slice(2, 4);

    if (hours.length === 2) {
        hours = String(Math.min(parseInt(hours, 10), 23)).padStart(2, '0');
    }
    if (minutes.length === 2) {
        minutes = String(Math.min(parseInt(minutes, 10), 59)).padStart(2, '0');
    }

    e.target.value = digits.length > 2 ? (hours + ':' + minutes) : digits;
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