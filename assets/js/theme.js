/**
 * ACF Starter - front end script.
 *
 * The file has no dependencies and it loads with defer. Keep it small.
 */

( function () {
	'use strict';

	/**
	 * Open and close the menu on a small screen.
	 */
	function initNavigation() {
		var toggle = document.querySelector( '[data-nav-toggle]' );

		if ( ! toggle ) {
			return;
		}

		var nav = document.getElementById( toggle.getAttribute( 'aria-controls' ) );

		if ( ! nav ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';

			toggle.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );

			if ( isOpen ) {
				nav.removeAttribute( 'data-open' );
			} else {
				nav.setAttribute( 'data-open', '' );
			}
		} );

		// Close the menu with the escape key.
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key !== 'Escape' || toggle.getAttribute( 'aria-expanded' ) !== 'true' ) {
				return;
			}

			toggle.setAttribute( 'aria-expanded', 'false' );
			nav.removeAttribute( 'data-open' );
			toggle.focus();
		} );
	}

	/**
	 * Write the width of the scroll bar to a custom property.
	 *
	 * A full width block uses 100vw. On a desktop browser, 100vw includes the
	 * scroll bar, and that gives a horizontal scroll bar. The CSS takes this
	 * value off the width again.
	 */
	function setScrollbarWidth() {
		var width = window.innerWidth - document.documentElement.clientWidth;

		document.documentElement.style.setProperty( '--scrollbar-width', Math.max( 0, width ) + 'px' );
	}

	/**
	 * Run a function no more than once each animation frame.
	 *
	 * @param {Function} callback The function to run.
	 * @return {Function} The wrapped function.
	 */
	function onFrame( callback ) {
		var scheduled = false;

		return function () {
			if ( scheduled ) {
				return;
			}

			scheduled = true;

			window.requestAnimationFrame( function () {
				scheduled = false;
				callback();
			} );
		};
	}

	function init() {
		initNavigation();
		setScrollbarWidth();

		window.addEventListener( 'resize', onFrame( setScrollbarWidth ), { passive: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
