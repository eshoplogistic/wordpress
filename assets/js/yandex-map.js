window.addEventListener('load', function (event) {
    eslRunMap()
});

function eslRunMap() {

    esl = {
        setTerminal: function (terminal) {
            _self = this

            const request = new XMLHttpRequest()
            request.open('POST', wc_esl_shipping_global.ajaxUrl, true)
            request.responseType = 'json'
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
            request.send(`action=wc_esl_set_terminal_address&terminal=${terminal.address}&terminal_code=${terminal.code}`)

            request.addEventListener("readystatechange", () => {

                if (request.readyState === 4 && request.status === 200) {
                    jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val(terminal.address);
                    jQuery(".wc-esl-terminals__button").text("Выбрать другой пункт выдачи");
                    console.log(request.response);

                    modalDom.close()
                }
            })

        },
        setFilter: function () {
            _self = this

            let filter_inputs = document.querySelectorAll('#modal-filter-esl input');
            let filters = {};
            for (const input of filter_inputs){
                filters[input.name] = input.value
                if(input.type === 'checkbox')
                    filters[input.name] = input.checked
            }


            const request = new XMLHttpRequest()
            request.open('POST', wc_esl_shipping_global.ajaxUrl, true)
            request.responseType = 'json'
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
            request.send('action=wc_esl_set_terminal_filter&filters='+JSON.stringify(filters))

            request.addEventListener("readystatechange", () => {

                if (request.readyState === 4 && request.status === 200 && request.response.data) {
                    yandexMaps.createContainer()
                    yandexMaps.terminals = request.response.data
                    yandexMaps.destroyMap()
                    ymaps.ready(yandexMaps.initMap)
                    document.getElementById('wcEslTerminals').value = JSON.stringify(request.response.data)
                }
            })


        },
    };

    let ID_MODAL = 'wc_esl_yandex_map',
        YANDEX_MAP_CONTAINER_ID = 'wc_esl_yandex_map_container',
        YANDEX_MAP_CONTAINER_ID_FOR_MAP = 'wc_esl-modal-yandex-map-wrap',
        YANDEX_MAP_CONTAINER_ID_FOR_ADDRESS = 'wc_esl-modal-yandex-map-address'

    let modalDom = {
        initLayout: {
            createHeader: function () {
                return 'Пункты самовывоза';
            },
            createBodyContent: function (){
                let result

                result = this.createFilterMap()
                result += this.createColForMap()

                return result
            },
            createColForMap: function () {
                let col = `<div id="${YANDEX_MAP_CONTAINER_ID_FOR_MAP}""><h4 class="without-map">Пункты выдачи не найдены</h4></div>`

                return col
            },
            createFilterMap: function () {
                let filter = document.createElement('div')
                let labelTitle = document.createElement('label')
                let filterName = document.createElement('input')
                let labelName = document.createElement('label')
                let filterMetro = document.createElement('input')
                let labelMetro = document.createElement('label')
                let filterAutomat = document.createElement('input')
                let labelAutomat = document.createElement('label')
                let filterPvz = document.createElement('input')
                let labelPvz = document.createElement('label')
                let labelTitleEnd = document.createElement('label')

                labelTitle.innerHTML = 'Показывать:'

                filterName.setAttribute('name', 'search-filter-esl')
                filterName.classList.add('filter-map-esl')
                labelName.innerHTML = 'Поиск'
                labelName.appendChild(filterName)

                filterMetro.setAttribute('name', 'metro-filter-esl')
                filterMetro.classList.add('filter-map-esl')
                labelMetro.innerHTML = 'Метро'
                labelMetro.appendChild(filterMetro)

                filterAutomat.setAttribute('name', 'automat-filter-esl')
                filterAutomat.setAttribute('type', 'checkbox')
                filterAutomat.setAttribute('checked', 'checked')
                filterAutomat.classList.add('filter-map-esl')
                labelAutomat.appendChild(filterAutomat)
                let titleAutomat = document.createTextNode("Постаматы");
                labelAutomat.appendChild(titleAutomat)

                filterPvz.setAttribute('name', 'pvz-filter-esl')
                filterPvz.setAttribute('type', 'checkbox')
                filterPvz.setAttribute('checked', 'checked')
                filterPvz.classList.add('filter-map-esl')
                labelPvz.appendChild(filterPvz)
                let titlePvz = document.createTextNode("ПВЗ");
                labelPvz.appendChild(titlePvz)

                labelTitleEnd.classList.add('title-end-esl')
                labelTitleEnd.innerHTML = 'Для поиска по улице/метро воспользуйтесь полем поиска на карте'


                filter.setAttribute('id', 'modal-filter-esl')
                filter.classList.add('modal-filter')

                let apiKeyYa = ''
                if(document.getElementById("wcEslKeyYa"))
                    apiKeyYa = document.getElementById('wcEslKeyYa').value

                //filter.appendChild(labelName)
                //filter.appendChild(labelMetro)
                filter.appendChild(labelTitle)
                filter.appendChild(labelAutomat)
                filter.appendChild(labelPvz)

                if(apiKeyYa)
                    filter.appendChild(labelTitleEnd)

                return new XMLSerializer().serializeToString(filter);
            },

        },
        createModalBootstrap: function () {
            if (this.checkOnInit()) return;
            return {
                idModal: ID_MODAL,
                title: this.initLayout.createHeader(),
                content: this.initLayout.createBodyContent()
            }
        },
        checkOnInit: function () {
            if (document.getElementById(this.idModal)) {
                return true
            }
            return false
        },
        open: function () {
            modalEsl.show();
        },
        close: function () {
            modalEsl.hide();
        },
        destroy: function () {
            if (this.checkOnInit()) {
                document.getElementById(this.idModal).remove()
            }
        }
    }
    let modalEsl = $modal(modalDom.createModalBootstrap())




    let yandexMaps = {
        terminals: [],
        settings: {},
        initApi: function () {
            let apiKeyYa = ''
            if(document.getElementById("wcEslKeyYa"))
                apiKeyYa = document.getElementById('wcEslKeyYa').value

            let paramsMap = ''
            if(apiKeyYa){
                paramsMap = '&apikey='+apiKeyYa;
            }

            let script = document.createElement('script')
            script.setAttribute('src', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'+paramsMap)
            script.setAttribute('defer', '')
            document.head.appendChild(script)
        },
        createContainer: function () {
            let container = document.createElement('div'),
                modalBody = document.getElementById(ID_MODAL).querySelector('.modal__body  #' + YANDEX_MAP_CONTAINER_ID_FOR_MAP);
            container.setAttribute('id', YANDEX_MAP_CONTAINER_ID)
            container.setAttribute('style', 'width: 100%')
            container.setAttribute('style', 'height: 400px')
            modalBody.appendChild(container)
        },
        initMap: function (event) {
            var zoom = 10;
            if(typeof yandexMaps.terminals[1] === "undefined")
                zoom = 16;
            if(typeof yandexMaps.terminals[0] !== "undefined"){
                let defaultControls = ['zoomControl']
                let apiKeyYa = document.getElementById('wcEslKeyYa').value
                if(apiKeyYa)
                    defaultControls = ['zoomControl', 'searchControl']

                var map = new ymaps.Map(YANDEX_MAP_CONTAINER_ID, {
                    center: [yandexMaps.terminals[0]['lat'], yandexMaps.terminals[0]['lon']],
                    zoom: zoom,
                    controls: defaultControls
                }, {
                    suppressMapOpenBlock: true,
                })

                // map.behaviors.disable('scrollZoom')
                yandexMaps.createPlacemarks(yandexMaps.terminals, map)
            }

        },
        createPlacemarks: function (items, map) {
            let geoObjects = [],
                iteration = 0;
            for (const terminal of items) {
                if (terminal.lat === '' || terminal.lon === '') {
                    continue;
                }
                const BalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                    `<h3 style="font-size: 1.3em;font-weight: bold;margin-bottom: 0.5em;">{{ properties.address }}</h3><p>{{ properties.note }}</p><p><b>{% if properties.timeWork %} Время работы: {{ properties.timeWork }} {% endif %}</b></p><button type="button" data-accept-terminal class="btn btn-success">Забрать отсюда</button>`,
                    {
                        build: function () {
                            BalloonContentLayout.superclass.build.call(this)
                            const button = document.getElementById(YANDEX_MAP_CONTAINER_ID_FOR_MAP).querySelector('[data-accept-terminal]')
                            if (button) {
                                button.addEventListener('click', this.selectedTerminal)
                            }

                        },
                        clear: function () {
                            const button = document.getElementById(YANDEX_MAP_CONTAINER_ID_FOR_MAP).querySelector('[data-accept-terminal]')
                            if (button) {
                                button.removeEventListener('click', this.selectedTerminal)
                            }
                            BalloonContentLayout.superclass.clear.call(this);
                        },
                        selectedTerminal: function () {
                            // document.dispatchEvent(new CustomEvent('onSelectAddress', {detail: terminal}))
                            esl.setTerminal(terminal)
                            // modal.close()
                        }
                    }
                )
                geoObjects[iteration] = new ymaps.Placemark([terminal.lat, terminal.lon], {
                    note: terminal.note,
                    address: terminal.address,
                    timeWork: terminal.workTime ? terminal.workTime : false,
                }, {
                    balloonContentLayout: BalloonContentLayout,
                    balloonPanelMaxMapArea: 0
                });
                iteration++;
            }
            let myClusterer = new ymaps.Clusterer(
                {clusterDisableClickZoom: false}
            );
            myClusterer.add(geoObjects);
            map.geoObjects.add(myClusterer);

        },
        destroyMap: function () {
            if (document.getElementById(YANDEX_MAP_CONTAINER_ID)) {
                document.getElementById(YANDEX_MAP_CONTAINER_ID).remove()
            }
        }
    }
    yandexMaps.initApi()

    let bindEvents = {
        clickOnTerminals: function (event) {

            let terminals = document.getElementById('wcEslTerminals').value
            if (terminals) {
                yandexMaps.createContainer()
                yandexMaps.terminals = JSON.parse(terminals)
                ymaps.ready(yandexMaps.initMap)
                modalDom.open()
            }

        },
        onCloseModal: function () {
            yandexMaps.destroyMap()
        },
        filterOn: function (event) {
            let terminals = document.getElementById('wcEslTerminals').value
            if (terminals) {
                esl.setFilter()
            }

        },
    }

    let els_terminals_buttons = document.getElementsByClassName('wc-esl-terminals__button')

    if (els_terminals_buttons) {

        for (let i = 0; i < els_terminals_buttons.length; i++) {
            els_terminals_buttons[i].addEventListener('click', bindEvents.clickOnTerminals, false);
        }

    }

    let esl_filters = document.getElementsByClassName('filter-map-esl')

    if (esl_filters) {

        for (let i = 0; i < esl_filters.length; i++) {
            esl_filters[i].addEventListener('change', bindEvents.filterOn);
        }

    }

    //jQuery('#' + ID_MODAL).on('hide.bs.modal', bindEvents.onCloseModal);

    document.addEventListener('hide.modal', function (e) {
        bindEvents.onCloseModal()
    });

    // document.addEventListener('onSelectAddress', function (event){
    //     esl.setTerminal( event.detail.address )
    // });

};