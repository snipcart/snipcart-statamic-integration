var $ = require('jquery');
var Mousetrap = require('mousetrap');

Vue.config.debug = false;
Vue.config.silent = true;

require('./plugins');
require('./filters');
require('./mixins');
require('./components');
require('./fieldtypes');
require('./directives');

Vue.http.headers.common['X-CSRF-TOKEN'] = document.querySelector('#csrf-token').getAttribute('value');

Vue.http.interceptors.push({
    response: function (response) {
        if (response.status === 401) {
            window.location = response.data.redirect;
        }

        return response;
    }
});

var vm = new Vue({
    el: '#statamic',

    data: {
        isPublishPage: false,
        isPreviewing: false,
        showShortcuts: false,
        navVisible: false,
        version: Statamic.version,
        flashSuccess: false,
        flashError: false,
        draggingNonFile: false
    },

    computed: {
        showPage: function() {
            return !this.hasSearchResults;
        },

        hasSearchResults: function() {
            return this.$refs.search.hasItems;
        }
    },

    methods: {
        preview: function() {
            var self = this;
            self.$broadcast('previewing');
            self.isPreviewing = true;

            $('.sneak-peek-viewport').addClass('on');

            setTimeout(function() {
                $(self.$el).addClass('sneak-peeking');
                $('#sneak-peek').find('iframe').show();
                setTimeout(function() {
                    $(self.$el).addClass('sneak-peek-editing');
                }, 200);
            }, 200);
        },

        stopPreviewing: function() {
            var self = this;
            var $viewport = $('.sneak-peek-viewport');
            var $icon = $viewport.find('.icon');

            $(self.$el).removeClass('sneak-peek-editing');
            $('#sneak-peek').find('iframe').fadeOut().remove();
            $icon.hide();
            setTimeout(function() {
                $(self.$el).removeClass('sneak-peeking');
                $viewport.removeClass('on');
                setTimeout(function(){
                    $icon.show();
                    self.isPreviewing = false;
                    self.$broadcast('previewing.stopped');
                }, 200);
            }, 500);
        },

        toggleNav: function () {
            this.navVisible = !this.navVisible;
        },

        /**
         * When the dragstart event is triggered.
         *
         * This event doesn't get triggered when dragging something from outside the browser,
         * so we can determine that something other than a file is being dragged.
         */
        dragStart() {
            this.draggingNonFile = true;
        },

        /**
         * When the dragend event is triggered.
         *
         * This event doesn't get triggered when dragging something from outside the browser,
         * so we can determine that something other than a file is being dragged.
         */
        dragEnd() {
            this.draggingNonFile = false;
        }
    },

    ready: function() {
        Mousetrap.bind(['/', 'ctrl+f'], function(e) {
            $('#global-search').focus();
        }, 'keyup');

        Mousetrap.bind('?', function(e) {
            this.showShortcuts = true;
        }.bind(this), 'keyup');

        Mousetrap.bind('escape', function(e) {
            this.$broadcast('close-modal');
            this.$broadcast('close-editor');
            this.$broadcast('close-selector');
            this.$broadcast('close-dropdown', null);
        }.bind(this), 'keyup');

        // Keep track of whether something other than a file is being dragged
        // so that components can tell when a file is being dragged.
        window.addEventListener('dragstart', this.dragStart);
        window.addEventListener('dragend', this.dragEnd);
    },

    events: {
        'setFlashSuccess': function (msg) {
            this.flashSuccess = msg
        },
        'setFlashError': function (msg) {
            this.flashError = msg
        }
    }
});
