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

        let spinner = document.createElement('div');
        spinner.classList.add('spinner-grow');
        spinner.setAttribute('role', 'status');
        spinner.innerHTML = `<span class="sr-only">Загрузка...</span>`;

        loader.appendChild(spinner);

        return loader;
    }
};