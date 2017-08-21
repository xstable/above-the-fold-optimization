/**
 * Request animation frame
 */
if (!window.requestAnimationFrame) {
    window.requestAnimationFrame = (function() {
        return window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.oRequestAnimationFrame ||
            window.msRequestAnimationFrame ||
            function( /* function FrameRequestCallback */ callback, /* DOMElement Element */ element) {
                window.setTimeout(callback, 1000 / 60);
            };
    })();
}

jQuery(function($) {

    /**
     * Page selection menu
     */
    if (jQuery('select.wp-pageselect').length > 0 && typeof jQuery('select.wp-pageselect').selectize !== 'undefined') {
        jQuery('select.wp-pageselect').selectize({
            placeholder: "Search a post/page/category ID or name...",
            optgroupField: 'class',
            labelField: 'name',
            searchField: ['name'],
            optgroups: window.abtf_pagesearch_optgroups,
            load: function(query, callback) {
                if (!query.length) return callback();
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'abtf_page_search',
                        query: query,
                        maxresults: 10
                    },
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        callback(res);
                    }
                });
            }
        });
    }

    /**
     * Extract CSS menu
     */
    if (jQuery('#fullcsspages').length > 0 && typeof jQuery('#fullcsspages').selectize !== 'undefined') {

        // download button
        jQuery('#fullcsspages_dl').on('click', function() {

            var href = jQuery('#fullcsspages').val();

            if (href === '') {
                alert('Select a page...');
                return;
            }

            if (/\?/.test(href)) {
                href += '&';
            } else {
                href += '?';
            }
            document.location.href = href + 'extract-css=' + jQuery('#fullcsspages_dl').attr('rel') + '&output=download';
        });

        // print button
        jQuery('#fullcsspages_print').on('click', function() {

            var href = jQuery('#fullcsspages').val();

            if (href === '') {
                alert('Select a page...');
                return;
            }

            if (/\?/.test(href)) {
                href += '&';
            } else {
                href += '?';
            }
            window.open(href + 'extract-css=' + jQuery('#fullcsspages_print').attr('rel') + '&output=print');
        });

    }

    /**
     * Compare Critical CSS menu
     */
    if (jQuery('#comparepages').length > 0 && typeof jQuery('#comparepages').selectize !== 'undefined') {

        // download button
        jQuery('#comparepages_split').on('click', function() {

            var href = jQuery('#comparepages').val();

            if (href === '') {
                alert('Select a page...');
                return;
            }

            if (/\?/.test(href)) {
                href += '&';
            } else {
                href += '?';
            }
            window.open(href + 'compare-abtf=' + jQuery('#comparepages_split').attr('rel'));
        });

        // print button
        jQuery('#comparepages_full').on('click', function() {

            var href = jQuery('#comparepages').val();

            if (href === '') {
                alert('Select a page...');
                return;
            }

            if (/\?/.test(href)) {
                href += '&';
            } else {
                href += '?';
            }
            window.open(href + 'abtf-critical-only=' + jQuery('#comparepages_full').attr('rel'));
        });

    }


    // text selection
    $('.clickselect').on('click', function() {
        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText($(this)[0]);
            range.select();
        } else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode($(this)[0]);
            window.getSelection().addRange(range);
        }
    });

    /**
     * Search/replace example
     */

    // example links
    $('[data-example]').each(function(index, el) {
        $(el).on('click', function(e) {
            var example = $('#' + $(this).data('example'));
            if (example.length > 0) {

                var original_text = example.data('example-text');
                var original_html = example.html();

                var example_html = $(this).data('example-html');
                if (typeof example_html !== 'string') {
                    example_html = JSON.stringify(example_html);
                }

                example.html(example_html);
                example.data('example-text', $(this).html());

                $(this).html(original_text);
                $(this).data('example-html', original_html);
            }
        });
    });

    /**
     * Real Time Text example
     * /
    if (jQuery('#livehtml').length > 0) {

        var scrollHandler = function() {
            if (jQuery(window).scrollTop() > 100) {
                jQuery('#livehtml').html('');
                jQuery('#livehtml').hide();
                jQuery( window ).off('scroll', scrollHandler)
            }
        }

        jQuery('.ws-info').on('mouseover touchstart', function() {

            jQuery( window ).scroll(scrollHandler);

            jQuery('#livehtml').show();
            jQuery('#livehtml').html('<span class="live wp-exclude-emoji"><span class="tag">&lt;div <span class="ws">⚡</span>&gt</span><span class="text">real-time writing for 10.000 viewers, as simple as a HTML attribute</span><span class="tag">&lt;/div&gt</span></span>');
            if ( !jQuery('#livehtml').data('realtimetext') ) {
                jQuery('#livehtml').data('realtimetext',1);
                var color = jQuery('#livehtml .live span.text').css("color").replace(')', ', 0.7)').replace('rgb', 'rgba');
                var lcolor = jQuery('.ws-info').css("color");
                var width = jQuery('#livehtml .live span.text').css("width");
                jQuery('body').append('<style>.live span.tag .ws {color: '+lcolor+';}.live span.text { border-right-color:'+color+'; } @keyframes typing { from { width: 0px } to { width: '+width+'; } }@keyframes blink-caret { from, to { border-color: transparent } 50% { border-color: '+color+'; } }</style>');
            }
        });



    }*/
});