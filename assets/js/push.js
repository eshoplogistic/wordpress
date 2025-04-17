let PushEsl = {
    elementId: 'eslNotifications',
    element: '',
    delay: 5000,
    items: [],

    init: function () {
        let element = null;
        if( document.getElementById(this.elementId) ) {
            element = document.getElementById(this.elementId);
        } else {
            element = document.createElement('div');
            element.setAttribute('id', this.elementId);
            element.classList.add('esl-notifications');
        }

        if(element !== null) {
            this.element = element;
            document.body.appendChild(this.element);
        }
    },

    error: function(msg) {
        this.addItem('error', msg);
    },

    warning: function(msg) {
        this.addItem('warning', msg);
    },

    success: function(msg) {
        this.addItem('success', msg);
    },

    render: function(classPush) {
        let $this = this;
        this.items.forEach(function (value, index) {
            if( value.rendered === 0 ) {
                $this.element.appendChild($this.renderItem(value.id, value.type, value.msg));
                $this.updateRenderedItem(value.id, 1);
            }

            setTimeout(function (id) {
                PushEsl.deleteItem(id);
                PushEsl.deleteElement(id);
            }, $this.delay, value.id);
        });
    },

    renderItem: function(id, type, msg) {
        let item = document.createElement('div');
        item.setAttribute('id', `esl_notification${id}`);
        item.classList.add('esl-notifications__item', `esl-notifications__item-${type}`);

        let msg_el = document.createElement('p');
        msg_el.classList.add('esl-notifications__msg');
        msg_el.innerHTML = msg;

        let icon_class = '';

        switch (type) {
            case 'success':
                icon_class = 'fa-check';
                break;
            case 'error':
                icon_class = 'fa-times';
                break;
            case 'warning':
                icon_class = 'fa-exclamation';
                break;
            default:
                break;
        }

        let icon_el = document.createElement('i');
        icon_el.classList.add('esl-notifications__icon', 'fa', icon_class);

        item.appendChild(icon_el);
        item.appendChild(msg_el);
        return item;
    },

    deleteItem: function (id) {
        let newItems = [];

        newItems = this.items.filter(item => item.id !== id);
        this.items = newItems;

        this.render();
    },

    addItem: function (type, msg) {
        let newItem = {id: this.ID(), type, msg, rendered: 0};

        this.items.push(newItem);

        this.render();
    },

    updateRenderedItem: function (id, rendered) {
        this.items = this.items.map(item => {
            if( item.id === id ) {
                return {id: item.id, type: item.type, msg: item.msg, rendered};
            }
            return item;
        });
    },

    deleteElement: function (id) {
        if( document.getElementById(`esl_notification${id}`) ) {
            document.getElementById(`esl_notification${id}`).remove();
        }

        this.render();
    },

    ID: function() {
        return '_' + Math.random().toString(36).substr(2, 9);
    }
};

PushEsl.init();