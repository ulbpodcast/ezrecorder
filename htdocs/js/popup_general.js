/**
 * Takes the content of a div INCLUDED INSIDE THE PAGE (in a hidden div for instance), and displays it in a new popup on top of the page
 */
function show_popup_from_inner_div(destination) {
    $.colorbox({opacity: 0.50, inline: true, overlayClose: false, href: destination});
}

/**
 * Calls the destination page given as a parameter, and displays its result in a new popupon top of the page.
 */
function show_popup_from_outer_div(destination) {
    $.colorbox({opacity: 0.50, overlayClose: false, href: destination});
}

function close_popup() {
    $.colorbox.close();
}