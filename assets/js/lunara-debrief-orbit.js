/**
 * The Debrief constellation (3.2.95).
 *
 * Each slide is a server-rendered Debrief: the reviewed film at the centre and
 * its three pairings around it. This script measures the posters, lays a gold
 * line from the centre to each pairing and around the three of them, then runs
 * the sequence: the film arrives, the lines draw out, the pairings land, the
 * triangle closes, and after a hold the next Debrief takes over.
 *
 * - Without this script the first Debrief shows complete, with no lines.
 * - Reduced motion: lines drawn instantly, no autoplay; the tabs still work.
 * - Autoplay pauses off-screen, in a hidden tab, on hover or focus, and on
 *   the Pause button (which stays paused until pressed again).
 */
( function () {
	'use strict';

	var reduceQuery = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;

	function reducedMotion() {
		return !! ( reduceQuery && reduceQuery.matches );
	}

	function centreOf( el, origin ) {
		var r = el.getBoundingClientRect();
		return { x: r.left - origin.left + r.width / 2, y: r.top - origin.top + r.height / 2 };
	}

	/** Position every line in a slide from its endpoints' poster centres. */
	function layoutSlide( slide ) {
		var origin = slide.getBoundingClientRect();
		var points = {};
		slide.querySelectorAll( '[data-orbit-node]' ).forEach( function ( node ) {
			var poster = node.querySelector( '.lunara-debrief-orbit-poster' ) || node;
			points[ node.getAttribute( 'data-orbit-node' ) ] = centreOf( poster, origin );
		} );
		slide.querySelectorAll( '.lunara-debrief-orbit-line' ).forEach( function ( line ) {
			var a = points[ line.getAttribute( 'data-from' ) ];
			var b = points[ line.getAttribute( 'data-to' ) ];
			if ( ! a || ! b ) {
				line.style.display = 'none';
				return;
			}
			var dx = b.x - a.x;
			var dy = b.y - a.y;
			line.style.display = '';
			line.style.left = a.x + 'px';
			line.style.top = a.y + 'px';
			line.style.width = Math.sqrt( dx * dx + dy * dy ) + 'px';
			line.style.transform = 'rotate(' + Math.atan2( dy, dx ) + 'rad)';
		} );
		slide.classList.add( 'is-laid-out' );
	}

	function Orbit( root ) {
		this.root = root;
		this.slides = Array.prototype.slice.call( root.querySelectorAll( '[data-orbit-slide]' ) );
		this.tabs = Array.prototype.slice.call( root.querySelectorAll( '[data-orbit-go]' ) );
		this.controls = root.querySelector( '[data-orbit-controls]' );
		this.toggle = root.querySelector( '[data-orbit-toggle]' );
		this.section = root.closest( 'section' );
		this.caption = this.section ? this.section.querySelector( '[data-orbit-caption]' ) : null;
		this.hold = parseInt( root.getAttribute( 'data-orbit-hold' ), 10 ) || 7200;
		this.index = 0;
		this.elapsed = 0;
		this.last = 0;
		this.frame = 0;
		this.userPaused = false;
		this.hovered = false;
		this.visible = false;
		this.started = false;
		this.init();
	}

	Orbit.prototype.init = function () {
		var self = this;
		if ( ! this.slides.length ) {
			return;
		}

		this.root.classList.add( 'is-ready' );
		if ( ! reducedMotion() ) {
			this.root.classList.add( 'is-animated' );
			// Hold the first Debrief undrawn until it scrolls into view.
			this.slides[ 0 ].classList.remove( 'is-drawn' );
		}
		this.slides.forEach( function ( slide, i ) {
			if ( i !== 0 ) {
				slide.setAttribute( 'aria-hidden', 'true' );
				slide.inert = true;
			}
		} );
		layoutSlide( this.slides[ 0 ] );

		if ( this.controls && this.slides.length > 1 ) {
			this.controls.hidden = false;
			if ( reducedMotion() && this.toggle ) {
				this.toggle.hidden = true;
			}
		}

		this.tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				self.go( parseInt( tab.getAttribute( 'data-orbit-go' ), 10 ), true );
			} );
			tab.addEventListener( 'keydown', function ( event ) {
				var step = 'ArrowRight' === event.key ? 1 : ( 'ArrowLeft' === event.key ? -1 : 0 );
				if ( step ) {
					event.preventDefault();
					var next = ( self.index + step + self.slides.length ) % self.slides.length;
					self.go( next, true );
					self.tabs[ next ].focus();
				}
			} );
		} );

		if ( this.toggle ) {
			this.toggle.addEventListener( 'click', function () {
				self.userPaused = ! self.userPaused;
				self.toggle.setAttribute( 'aria-pressed', self.userPaused ? 'true' : 'false' );
				self.sync();
			} );
		}

		var stage = this.root.querySelector( '.lunara-debrief-orbit-stage' );
		stage.addEventListener( 'mouseenter', function () { self.hovered = true; self.sync(); } );
		stage.addEventListener( 'mouseleave', function () { self.hovered = false; self.sync(); } );
		this.root.addEventListener( 'focusin', function () { self.hovered = true; self.sync(); } );
		this.root.addEventListener( 'focusout', function ( event ) {
			if ( ! self.root.contains( event.relatedTarget ) ) {
				self.hovered = false;
				self.sync();
			}
		} );
		document.addEventListener( 'visibilitychange', function () { self.sync(); } );

		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( function () { layoutSlide( self.slides[ self.index ] ); } ).observe( stage );
		} else {
			window.addEventListener( 'resize', function () { layoutSlide( self.slides[ self.index ] ); } );
		}
		// Posters arriving late move the centres.
		this.root.querySelectorAll( 'img' ).forEach( function ( img ) {
			if ( ! img.complete ) {
				img.addEventListener( 'load', function () { layoutSlide( self.slides[ self.index ] ); }, { once: true } );
			}
		} );

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) {
				self.visible = entries[ 0 ].isIntersecting;
				if ( self.visible && ! self.started ) {
					self.started = true;
					self.draw( self.slides[ self.index ] );
				}
				self.sync();
			}, { threshold: 0.35 } ).observe( this.root );
		} else {
			this.visible = true;
			this.started = true;
			this.draw( this.slides[ 0 ] );
			this.sync();
		}
	};

	Orbit.prototype.draw = function ( slide ) {
		layoutSlide( slide );
		// Two frames: commit the undrawn state, then let the transitions run.
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				slide.classList.add( 'is-drawn' );
			} );
		} );
	};

	Orbit.prototype.go = function ( next, fromUser ) {
		if ( next === this.index || ! this.slides[ next ] ) {
			return;
		}
		var current = this.slides[ this.index ];
		var incoming = this.slides[ next ];

		current.classList.remove( 'is-active', 'is-drawn' );
		current.setAttribute( 'aria-hidden', 'true' );
		current.inert = true;

		incoming.classList.add( 'is-active' );
		incoming.removeAttribute( 'aria-hidden' );
		incoming.inert = false;
		this.draw( incoming );

		if ( this.tabs[ this.index ] ) {
			this.tabs[ this.index ].removeAttribute( 'aria-current' );
			this.tabs[ this.index ].style.removeProperty( '--orbit-progress' );
		}
		if ( this.tabs[ next ] ) {
			this.tabs[ next ].setAttribute( 'aria-current', 'true' );
		}
		if ( this.caption ) {
			this.caption.textContent = incoming.getAttribute( 'data-review-title' ) || '';
			this.caption.setAttribute( 'href', incoming.getAttribute( 'data-review-url' ) || '#' );
		}

		this.index = next;
		this.elapsed = 0;
		// A chosen Debrief gets a full hold before the sequence moves on.
		if ( fromUser ) {
			this.last = 0;
		}
	};

	Orbit.prototype.playing = function () {
		return this.slides.length > 1 && ! reducedMotion() && this.started && this.visible &&
			! this.userPaused && ! this.hovered && ! document.hidden;
	};

	Orbit.prototype.sync = function () {
		var self = this;
		this.root.classList.toggle( 'is-paused', ! this.playing() );
		if ( ! this.playing() ) {
			window.cancelAnimationFrame( this.frame );
			this.frame = 0;
			this.last = 0;
			return;
		}
		if ( this.frame ) {
			return;
		}
		var tick = function ( now ) {
			if ( ! self.playing() ) {
				self.frame = 0;
				return;
			}
			if ( self.last ) {
				self.elapsed += now - self.last;
			}
			self.last = now;
			var tab = self.tabs[ self.index ];
			if ( tab ) {
				tab.style.setProperty( '--orbit-progress', Math.min( 1, self.elapsed / self.hold ).toFixed( 4 ) );
			}
			if ( self.elapsed >= self.hold ) {
				self.go( ( self.index + 1 ) % self.slides.length, false );
			}
			self.frame = window.requestAnimationFrame( tick );
		};
		this.frame = window.requestAnimationFrame( tick );
	};

	function boot() {
		document.querySelectorAll( '[data-debrief-orbit]' ).forEach( function ( root ) {
			if ( ! root.lunaraOrbit ) {
				root.lunaraOrbit = new Orbit( root );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
