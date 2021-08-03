window.onload = function() {

    esl = {
      setTerminal: function ( terminal ) {
        _self = this
        
        const request = new XMLHttpRequest()
        request.open('POST', wc_esl_shipping_global.ajaxUrl, true)
        request.responseType = 'json'
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
        request.send( `action=wc_esl_set_terminal_address&terminal=${terminal}` )
        
        request.addEventListener("readystatechange", () => {
      
            if (request.readyState === 4 && request.status === 200) {
              jQuery( '#wc_esl_billing_terminal, #wc_esl_shipping_terminal' ).val( terminal );
              jQuery(".wc-esl-terminals__button").text("Выбрать другой пункт выдачи");
              console.log( request.response );
  
              modal.close()
            }
        })
  
    },
    };
  
      let ID_MODAL = 'wc_esl_yandex_map',
          YANDEX_MAP_CONTAINER_ID = 'wc_esl_yandex_map_container',
          YANDEX_MAP_CONTAINER_ID_FOR_MAP = 'wc_esl-modal-yandex-map-wrap',
          YANDEX_MAP_CONTAINER_ID_FOR_ADDRESS = 'wc_esl-modal-yandex-map-address'
  
      let modal = {
          initLayout: {
            createRoot: function (){
                let root = document.createElement('div')
                root.classList.add('modal','fade')
                root.setAttribute('tabindex','-1')
                root.setAttribute('role','dialog')
                root.setAttribute('id', ID_MODAL)
                document.body.appendChild(root)
                return root;
            },
            createDialog: function (dom){
                let dialog = document.createElement('div')
                dialog.classList.add('modal-dialog','modal-lg')
                if(document.documentElement.clientWidth < 768){
                    dialog.setAttribute('style','width: 100%')
                }
                dom.appendChild(dialog)
                return dialog;
            },
            createContent: function (dom){
                let content = document.createElement('div')
                content.classList.add('modal-content')
                dom.appendChild(content)
                return content;
            },
            createHeader: function (dom){
                let header = document.createElement('div')
                header.classList.add('modal-header')
                header.innerHTML = '<h4>Пункты самовывоза</h4>'
                dom.appendChild(header)
                return header;
            },
            createButtonClose: function (dom){
                let buttonClose = document.createElement('button')
                buttonClose.setAttribute('type','button')
                buttonClose.setAttribute('data-dismiss','modal')
                buttonClose.setAttribute('aria-label','Close')
                buttonClose.classList.add('close')
                dom.appendChild(buttonClose)
                return buttonClose;
            },
            createIconClose: function (dom){
                let iconClose = document.createElement('span')
                iconClose.setAttribute('aria-hidden','true')
                iconClose.innerHTML= '&times;'
                dom.appendChild(iconClose)
                return iconClose;
            },
            createBody: function (dom){
                let body = document.createElement('div')
                body.classList.add('modal-body')
                dom.appendChild(body)
                return body;
            },
            createRow: function (dom){
                let row = document.createElement('div')
                row.classList.add('class','row')
                dom.appendChild(row)
                return row;
            },
            createColForMap: function (dom){
                let col = document.createElement('div')
                col.classList.add('col-lg-12','col-md-12')
                col.setAttribute('id',YANDEX_MAP_CONTAINER_ID_FOR_MAP)
                dom.appendChild(col)
                return
            },
  
          },
          createModalBootstrap: function (){
              if(this.checkOnInit()) return;
              let root = this.initLayout.createRoot(),
                  dialog = this.initLayout.createDialog(root),
                  content = this.initLayout.createContent(dialog),
                  header = this.initLayout.createHeader(content),
                  buttonClose = this.initLayout.createButtonClose(header),
                  iconClose = this.initLayout.createIconClose(buttonClose),
                  body = this.initLayout.createBody(content),
                  row = this.initLayout.createRow(body),
                  colMap = this.initLayout.createColForMap(row);
          },
          checkOnInit: function (){
              if(document.getElementById(this.idModal)){
                  return true
              }
              return false
          },
          open: function (){
              jQuery('#'+ID_MODAL).modal('show')
          },
          close: function (){
              jQuery('#'+ID_MODAL).modal('hide')
          },
          destroy: function (){
              if(this.checkOnInit()){
                  document.getElementById(this.idModal).remove()
              }
          }
      }
      modal.createModalBootstrap()
  
      let yandexMaps = {
          terminals: [],
          settings: {},
          initApi: function (){
              let script = document.createElement('script')
              script.setAttribute('src', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU')
              script.setAttribute('defer','')
              document.head.appendChild(script)
          },
          createContainer: function (){
              let container = document.createElement('div'),
                  modalBody = document.getElementById(ID_MODAL).querySelector('.modal-body  #'+YANDEX_MAP_CONTAINER_ID_FOR_MAP);
              container.setAttribute('id',YANDEX_MAP_CONTAINER_ID)
              container.setAttribute('style','width: 100%')
              container.setAttribute('style','height: 400px')
              modalBody.appendChild(container)
          },
          initMap: function (event){
              var map = new ymaps.Map(YANDEX_MAP_CONTAINER_ID, {
                  center: [yandexMaps.terminals[0]['lat'],yandexMaps.terminals[0]['lon']],
                  zoom: 10,
                  controls: ['zoomControl']
              }, {
                  suppressMapOpenBlock: true
              })
              // map.behaviors.disable('scrollZoom')
              yandexMaps.createPlacemarks(yandexMaps.terminals, map)
          },
          createPlacemarks: function (items, map){
              let geoObjects = [],
                  iteration = 0;
              for(const terminal of items){
                  if(terminal.lat === '' || terminal.lon === ''){
                      continue;
                  }
                  const BalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                      `<h3 style="font-size: 1.3em;font-weight: bold;margin-bottom: 0.5em;">{{ properties.address }}</h3><p>{{ properties.note }}</p><p><b>Время работы: {{ properties.timeWork }}</b></p><button type="button" data-accept-terminal class="btn btn-success">Забрать отсюда</button>`,
                      {
                          build: function (){
                              BalloonContentLayout.superclass.build.call(this)
                              const button =  document.getElementById(YANDEX_MAP_CONTAINER_ID_FOR_MAP).querySelector('[data-accept-terminal]')
                              if(button){
                                  button.addEventListener('click', this.selectedTerminal)
                              }
  
                          },
                          clear: function(){
                              const button =  document.getElementById(YANDEX_MAP_CONTAINER_ID_FOR_MAP).querySelector('[data-accept-terminal]')
                              if(button){
                                  button.removeEventListener('click', this.selectedTerminal)
                              }
                              BalloonContentLayout.superclass.clear.call(this);
                          },
                          selectedTerminal: function (){
                              // document.dispatchEvent(new CustomEvent('onSelectAddress', {detail: terminal}))
                              esl.setTerminal( terminal.address )
                              // modal.close()
                          }
                      }
                  )
                  geoObjects[iteration] = new ymaps.Placemark([terminal.lat, terminal.lon],{
                      note: terminal.note,
                      address: terminal.address,
                      timeWork: terminal.workTime ? terminal.workTime : 'РќРµ СѓРєР°Р·Р°РЅРѕ',
                  },{
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
          destroyMap: function (){
              document.getElementById(YANDEX_MAP_CONTAINER_ID).remove()
          }
      }
      yandexMaps.initApi()
  
      let bindEvents = {
          clickOnTerminals: function (event) {
  
              let terminals = document.getElementById( 'wcEslTerminals' ).value
              if( terminals ) {
                  yandexMaps.createContainer()
                  yandexMaps.terminals = JSON.parse(terminals)
                  ymaps.ready(yandexMaps.initMap)
                  modal.open()
              }
  
          },
          onCloseModal: function (){
              yandexMaps.destroyMap()
          }
      }
  
      let els_terminals_buttons = document.getElementsByClassName( 'wc-esl-terminals__button' )
  
      if( els_terminals_buttons ) {
  
        for( let i = 0; i < els_terminals_buttons.length; i++ ) {
          els_terminals_buttons[i].addEventListener( 'click', bindEvents.clickOnTerminals, false );
        }
  
      }
      
      jQuery('#'+ID_MODAL).on('hide.bs.modal', bindEvents.onCloseModal);
  
      // document.addEventListener('onSelectAddress', function (event){
      //     esl.setTerminal( event.detail.address )
      // });
  
  };