document.addEventListener('DOMContentLoaded', function(){
    let base = window.wcEslPluginUrl || '',
        css = [base + 'assets/css/widget-modal.css'],
        js = [base + 'assets/js/widget-modal-vendors.js', base + 'assets/js/widget-modal-app.js'];

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