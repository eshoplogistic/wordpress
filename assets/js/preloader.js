let PreloaderEsl = {
    show: function(selector) {
        let element = document.querySelector(selector);

        if(element) {
            element.appendChild(this.createLoader());
        }
    },

    hide: function (selector) {
        let element = document.querySelectorAll(`${selector} > .esl-preloader`);

        if(element) {
            element.forEach(function(item, index) {
                item.remove();
            });
        }
    },

    createLoader: function () {
        let loader = document.createElement('div');
        loader.classList.add('esl-preloader');

        // Самодостаточный спиннер (не зависит от Bootstrap: .spinner-grow/.sr-only не
        // стилизованы на странице заказа, где bootstrap.min.css не подключается —
        // без него это был просто обычный текст "Загрузка..." без анимации).
        let spinner = document.createElement('div');
        spinner.classList.add('esl-spinner');
        spinner.setAttribute('role', 'status');
        spinner.innerHTML = `<span class="esl-spinner__label">Загрузка...</span>`;

        loader.appendChild(spinner);

        return loader;
    }
};