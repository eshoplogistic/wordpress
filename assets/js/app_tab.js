document.addEventListener('DOMContentLoaded', function () {
    let root = document.getElementById('eShopLogisticWidgetBlock')
    if (root) {
        setTimeout(function () {
            document.getElementById('eShopLogisticWidgetBlock').dispatchEvent(new CustomEvent('eShopLogisticWidgetBlock:loadApp'))
        }, 1000)
    }else{
        let base = window.wcEslPluginUrl || '',
            css = [base + 'assets/css/widget-static.css'],
            js = [base + 'assets/js/widget-static-vendors.js', base + 'assets/js/widget-static-app.js'];

        for (const path of css) {
            let style = document.createElement('link');
            style.rel = "stylesheet"
            style.href = path
            document.body.appendChild(style)
        }
        for (const path of js) {
            let script = document.createElement('script');
            script.src = path
            document.body.appendChild(script)

        }

        let elementTabWidget = document.getElementById('wtpbtn');
        if (typeof (elementTabWidget) != 'undefined' && elementTabWidget != null) {
            setTimeout("document.getElementById('wtpbtn').click();", 1000);
        }
    }

})