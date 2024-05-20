let HttpClientEsl = {
    post: function(data, callback_func) {
        return this.sendRequest(data, callback_func, 'POST');
    },

    get: function(data, callback_func) {
        return this.sendRequest(data, callback_func, 'GET');
    },

    sendRequest: function(
        data = [],
        callback_func = null,
        method = 'POST'
    ) {
        const request = new XMLHttpRequest();
        request.open( method, wc_esl_shipping_global.ajaxUrl, true );
        request.responseType = 'json';
        request.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
        request.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );

        request.send( this.serialize( data ) );

        request.addEventListener( "readystatechange", () => {

            if( request.readyState === 4 ) {

                if(!callback_func) return;

                callback_func( request.response );

            }
        });
    },

    serialize: function( data ) {
        let query = "";
        for( let key in data ) {
            if( query != "" ) {
                query += "&";
            }
            query += key + "=" + encodeURIComponent( data[key] );
        }

        return query;
    }
};