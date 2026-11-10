/**
 * Complete Controller Modern — progressive enhancements.
 *
 * Two behaviours, no dependencies:
 *   1. Scroll reveal  — fades sections in as they enter the viewport.
 *   2. Stat counters  — counts numeric figures up on first view.
 *
 * Both are strictly additive. The final state is what the markup already says, so
 * blocking this file costs an animation and nothing else.
 */
( function () {
	'use strict';

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/* ------------------------------------------------------------ reveal -- */

	function initReveal() {
		var targets = document.querySelectorAll( '.cc-reveal, .cc-reveal--stagger' );

		if ( ! targets.length ) {
			return;
		}

		// Without IntersectionObserver, show everything immediately.
		if ( ! ( 'IntersectionObserver' in window ) || reduced.matches ) {
			Array.prototype.forEach.call( targets, function ( el ) {
				el.classList.add( 'is-visible' );
			} );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				} );
			},
			{
				// Fire slightly before the element reaches the fold so the motion
				// finishes about when it becomes properly readable.
				rootMargin: '0px 0px -12% 0px',
				threshold: 0.08,
			}
		);

		const pending = new Set();

		Array.prototype.forEach.call( targets, function ( el ) {
			pending.add( el );
			observer.observe( el );
		} );

		/**
		 * Reveal anything the viewport has already passed.
		 *
		 * An element that goes from below the fold to above it within a single
		 * frame — a scrollbar drag, an anchor jump, browser scroll restoration —
		 * may never register as intersecting, and would otherwise sit at opacity 0
		 * forever. This sweep guarantees content is never lost to the animation.
		 */
		const sweep = () => {
			pending.forEach( ( el ) => {
				if ( el.classList.contains( 'is-visible' ) ) {
					pending.delete( el );
					return;
				}
				if ( el.getBoundingClientRect().bottom < 0 ) {
					el.classList.add( 'is-visible' );
					observer.unobserve( el );
					pending.delete( el );
				}
			} );

			if ( ! pending.size ) {
				window.removeEventListener( 'scroll', onScroll );
			}
		};

		let ticking = false;
		const onScroll = () => {
			if ( ticking ) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame( () => {
				ticking = false;
				sweep();
			} );
		};

		sweep(); // Catch a restored scroll position on first paint.
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/* ---------------------------------------------------------- counters -- */

	/**
	 * Split a display figure into prefix, number and suffix.
	 *
	 * Handles the shapes used on the site: "600+", "$2.4M", "24hr", "100%", "2.3X".
	 * Returns null when there is no number to animate.
	 */
	function parseFigure( text ) {
		var match = text.match( /^(\D*?)([\d,]+(?:\.\d+)?)(.*)$/ );

		if ( ! match ) {
			return null;
		}

		var raw = match[ 2 ].replace( /,/g, '' );

		return {
			prefix: match[ 1 ],
			value: parseFloat( raw ),
			suffix: match[ 3 ],
			// Preserve the author's precision: "2.4" animates to one decimal place.
			decimals: ( raw.split( '.' )[ 1 ] || '' ).length,
			grouped: match[ 2 ].indexOf( ',' ) !== -1,
		};
	}

	function format( figure, value ) {
		var out = value.toFixed( figure.decimals );

		if ( figure.grouped ) {
			var parts = out.split( '.' );
			parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
			out = parts.join( '.' );
		}

		return figure.prefix + out + figure.suffix;
	}

	function countUp( el, figure ) {
		var duration = 1400;
		var start = null;

		function step( now ) {
			if ( start === null ) {
				start = now;
			}

			var progress = Math.min( ( now - start ) / duration, 1 );
			// easeOutExpo — fast out of the gate, settles gently on the number.
			var eased = progress === 1 ? 1 : 1 - Math.pow( 2, -10 * progress );

			el.textContent = format( figure, figure.value * eased );

			if ( progress < 1 ) {
				window.requestAnimationFrame( step );
			} else {
				el.textContent = figure.original;
			}
		}

		window.requestAnimationFrame( step );
	}

	function initCounters() {
		var targets = document.querySelectorAll( '[data-cc-count]' );

		if ( ! targets.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) || reduced.matches ) {
			return; // Markup already holds the final value.
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					var el = entry.target;
					observer.unobserve( el );

					var figure = parseFigure( el.textContent.trim() );

					if ( ! figure ) {
						return;
					}

					figure.original = el.textContent.trim();

					// Lock the box so counting up does not reflow the layout.
					el.style.display = 'inline-block';
					el.style.minWidth = el.getBoundingClientRect().width + 'px';

					countUp( el, figure );
				} );
			},
			{ threshold: 0.5 }
		);

		Array.prototype.forEach.call( targets, function ( el ) {
			observer.observe( el );
		} );
	}

	/* ------------------------------------------------ consultation form -- */

	function initConsultationForm() {
		var form = document.querySelector( '[data-cc-consultation]' );

		if ( ! form ) {
			return;
		}

		var response = form.querySelector( '.cc-form-response' );
		var button = form.querySelector( 'button[type="submit"]' );

		function showResponse( message, isError ) {
			response.textContent = message;
			response.hidden = false;
			response.style.borderColor = isError ? '#bb4c28' : '#6a9082';
			response.style.backgroundColor = isError ? 'rgba(187,76,40,.08)' : 'rgba(106,144,130,.08)';
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var data = new FormData( form );
			var fullName = String( data.get( 'name' ) || '' ).trim();
			var email = String( data.get( 'email' ) || '' ).trim();
			var phone = String( data.get( 'phone' ) || '' ).trim();

			if ( ! fullName || ! email || ! phone ) {
				showResponse( 'Please fill in all required fields.', true );
				return;
			}

			var splitAt = fullName.indexOf( ' ' );
			var firstName = splitAt === -1 ? fullName : fullName.slice( 0, splitAt );
			var lastName = splitAt === -1 ? '' : fullName.slice( splitAt + 1 );

			button.disabled = true;
			button.textContent = 'Submitting…';
			response.hidden = true;

			fetch( 'https://api.hsforms.com/submissions/v3/integration/submit/2446402/9b400fd2-8b1f-478c-b4ce-5c99028a0686', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( {
					fields: [
						{ name: 'firstname', value: firstName },
						{ name: 'lastname', value: lastName },
						{ name: 'email', value: email },
						{ name: 'phone', value: phone },
						{ name: 'company', value: String( data.get( 'company' ) || '' ) },
						{ name: 'all_interests_that_apply', value: String( data.get( 'serviceType' ) || '' ) },
						{ name: 'message', value: String( data.get( 'message' ) || '' ) },
					],
					context: { pageUri: window.location.href, pageName: document.title },
				} ),
			} )
				.then( function ( result ) {
					if ( ! result.ok ) {
						throw new Error( 'HubSpot submission failed' );
					}
					form.reset();
					showResponse( 'Thank you — our team will be in touch within 24 hours. A confirmation has been sent to your email.', false );
				} )
				.catch( function () {
					showResponse( 'Something went wrong. Please try again or call us directly.', true );
				} )
				.finally( function () {
					button.disabled = false;
					button.textContent = 'Request Consultation';
				} );
		} );
	}

	/* Keep CAS-i in its compact layout on touch screens in either orientation. */
	function initVoiceAgentDock() {
		var touchDock = window.matchMedia( '(max-width: 1024px) and (pointer: coarse), (max-height: 600px) and (pointer: coarse)' );
		var observer;

		function connect() {
			var frame = document.getElementById( 'casi-iframe' );
			var panel = document.getElementById( 'casi-panel' );
			if ( ! frame || ! panel ) {
				return false;
			}

			function sync() {
				// Avoid reloading an open conversation or scheduler on rotation.
				if ( ! touchDock.matches || panel.classList.contains( 'casi-expanded' ) || panel.classList.contains( 'casi-booking' ) ) {
					return;
				}
				var url = new URL( frame.src, window.location.href );
				if ( url.pathname === '/widget-true' && url.searchParams.get( 'mobileDock' ) !== 'true' ) {
					url.searchParams.set( 'mobileDock', 'true' );
					frame.src = url.href;
				}
			}

			window.addEventListener( 'resize', function () { window.requestAnimationFrame( sync ); } );
			new MutationObserver( sync ).observe( panel, { attributes: true, attributeFilter: [ 'class' ] } );
			frame.addEventListener( 'load', sync );
			sync();
			return true;
		}

		if ( ! connect() ) {
			observer = new MutationObserver( function () {
				if ( connect() ) {
					observer.disconnect();
				}
			} );
			observer.observe( document.body, { childList: true, subtree: true } );
			// Pages without the optional widget need no long-lived observer.
			window.setTimeout( function () { observer.disconnect(); }, 15000 );
		}
	}

	/* -------------------------------------------------------------- boot -- */

	function init() {
		initReveal();
		initCounters();
		initConsultationForm();
		initVoiceAgentDock();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
