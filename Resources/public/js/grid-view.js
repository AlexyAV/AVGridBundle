function GridView() {

    /**
     * @type {{}} List of grids on current page. Grid id as keys and {jQuery}
     * object as value.
     */
    this.gridViewList = {};

    /**
     * Default grid events.
     *
     * @type {{}}
     */
    this.gridEvents = {

        beforeFilter: 'beforeFilter',

        afterFilter: 'afterFilter'
    };
}

/**
 * Initialize grid.
 */
GridView.prototype.init = function () {
    this.getGridViewList();

    if (!$.isEmptyObject(this.gridViewList)) {
        this.initFilterEvents();
    }
};

/**
 * Get list of grids on current page.
 *
 * @param cached
 * @returns {{}|*}
 */
GridView.prototype.getGridViewList = function (cached) {

    var self = this;

    cached = cached || false;

    if ($.isEmptyObject(self.gridViewList) || cached) {

        $('.grid-view').each(function (key, value) {
            self.gridViewList[$(value).attr('id')] = value;
        });
    }

    return self.gridViewList;
};

/**
 * Sets handler for "change" and "keydown" events of filter fields.
 */
GridView.prototype.initFilterEvents = function () {

    var self = this;

    var eventHandled = false;

    $.each(this.gridViewList, function (key, value) {

        $(value).find('#' + key + '_filters')
            .on('change keydown', function (event) {
                    if (event.type === 'keydown') {
                        if (event.keyCode !== 13) {
                            return;
                        }
                        eventHandled = true;
                    }

                    if (eventHandled) {
                        eventHandled = false;

                        return;
                    }

                    self.applyGridFilters(key);
                }
            );
    });
};

/**
 * Submits filter form data. Before and after data submit events will be
 * triggered.
 *
 * @param gridViewId
 */
GridView.prototype.applyGridFilters = function (gridViewId) {

    var grid = $('#'+gridViewId);

    var event = $.Event(this.gridEvents.beforeFilter);

    grid.trigger(event);

    if (event.result === false) {
        return;
    }

    grid.find('.filters form').submit();

    grid.trigger(this.gridEvents.afterFilter);
};

(function ($) {
    var gridView = new GridView();

    gridView.init();
})(window.jQuery);