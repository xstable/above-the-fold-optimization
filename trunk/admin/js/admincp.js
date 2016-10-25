/**
 * Request animation frame
 */
if ( !window.requestAnimationFrame ) {

    window.requestAnimationFrame = ( function() {

        return window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.oRequestAnimationFrame ||
        window.msRequestAnimationFrame ||
        function( /* function FrameRequestCallback */ callback, /* DOMElement Element */ element ) {

            window.setTimeout( callback, 1000 / 60 );

        };

    } )();

}

jQuery(function($) {

    /**
     * Extract CSS menu
     */
    if (jQuery('#fullcsspages').length > 0 && typeof jQuery('#fullcsspages').selectize !== 'undefined') {
        jQuery('#fullcsspages').selectize({
            persist         : true,
            placeholder     : "Select a page...",
            plugins         : ['remove_button']
        });

        // download button
        jQuery('#fullcsspages_dl').on('click',function() {

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
            document.location.href=href + 'extract-css='+jQuery('#fullcsspages_dl').attr('rel')+'&output=download';
        });

        // print button
        jQuery('#fullcsspages_print').on('click',function() {

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
            window.open(href + 'extract-css='+jQuery('#fullcsspages_print').attr('rel')+'&output=print');
        });

    }

    /**
     * Compare Critical CSS menu
     */
    if (jQuery('#comparepages').length > 0 && typeof jQuery('#comparepages').selectize !== 'undefined') {
        jQuery('#comparepages').selectize({
            persist         : true,
            placeholder     : "Select a page...",
            plugins         : ['remove_button']
        });

        // download button
        jQuery('#comparepages_split').on('click',function() {

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
            window.open(href + 'compare-abtf='+jQuery('#comparepages_split').attr('rel'));
        });

        // print button
        jQuery('#comparepages_full').on('click',function() {

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
            window.open(href + 'abtf-critical-only='+jQuery('#comparepages_full').attr('rel'));
        });

    }

    /**
     * Review animation
     * / 
    if (jQuery('#reviewanim').data('count')) {

        window.inputChange = false;
        jQuery('input,select,textarea').on('change', function() {
            window.inputChange = true;
        });
        var prevPos = false;
        var isUp = true;
        jQuery(window).scroll(jQuery.debounce( 250, function() {
            var pos = jQuery(window).scrollTop();

            if (!prevPos) {
                prevPos = pos;
            }
            if (pos < prevPos) {

                // up scroll
                if (!isUp && pos < 50) {

                    isUp = true; // entered top of page

                    // anything changed?
                    if (window.inputChange) {

                        showReviewAnimation();
                        window.inputChange = false;
                        
                    } 
                }
            }
            if (pos > 50) {
                isUp = false;
            }

            prevPos = pos;

        } ));

        var lastAnim = false;
        var showReviewAnimation = function() {

            var time = Math.round(new Date().getTime()/1000);

            // shown in last 30 seconds, abort
            if (lastAnim && lastAnim > (time - 30)) {
                return;
            }

            lastAnim = time;

            if (showReviewAnimationTimeout) {
                clearTimeout(showReviewAnimationTimeout);
                showReviewAnimationTimeout = false;
            }

            window.requestAnimationFrame(function() {

                jQuery('#reviewanim').show();
                setTimeout(function() {

                    // hide svg
                    jQuery('#reviewanim').hide();

                    // start again in 1 minute
                    showReviewAnimationTimeout = setTimeout(function() {
                        showReviewAnimation();
                    },(1000 * 60));

                },900);

            });
        }

        // start in 1 minute
        var showReviewAnimationTimeout = setTimeout(function() {

            var pos = jQuery(window).scrollTop();
            if (pos > 50) {
                window.inputChange = true;
            } else {
                if (window.inputChange) {
                    showReviewAnimation();
                }
            }
        },(1000 * 60));

    }
    */

});