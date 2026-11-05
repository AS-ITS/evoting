(function ($) {
    'use strict';

    window.changeBlockUrlModal = function (selector, url) {
        var block = $(selector);
        block.text('Loading...').attr('aria-busy', 'true');
        block.load(url, function (response, status, xhr) {
            block.removeAttr('aria-busy');
            if (status === 'error') {
                block.text('Unable to load content (HTTP ' + xhr.status + ').');
            }
        });
    };
})(jQuery);
