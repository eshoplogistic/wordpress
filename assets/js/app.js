document.addEventListener('DOMContentLoaded', function(){
    let css = ['https://api.eshoplogistic.ru/widget/modal/v1/css/app.css'],
        js = ['https://api.eshoplogistic.ru/widget/modal/v1/js/chunk-vendors.js','https://api.eshoplogistic.ru/widget/modal/v1/js/app.js'];

    for(const path of css){
        let style = document.createElement('link');
        style.rel="stylesheet"
        style.href = path
        document.body.appendChild(style)
    }
    for(const path of js){
        let script = document.createElement('script');
        script.src = path
        document.body.appendChild(script)
    }
})