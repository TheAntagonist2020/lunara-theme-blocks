/**
 * The Pair It With thread (3.2.96).
 *
 * On a single review, a gold line runs from the "Pair It With" heading through
 * the three films. It draws with the reader's scroll; as it reaches each film
 * that film's node lights in its role colour and the card arrives. It never
 * retracts, so scrolling back up leaves the Debrief complete.
 *
 * - Without this script the cards render exactly as before.
 * - Reduced motion: the thread is drawn complete and every card is shown.
 * - Stacked cards get a vertical thread in the left gutter; cards side by side
 *   get a horizontal thread above them. No gutter, no thread.
 */
( function () {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var ROLES = [ 'theme', 'counter', 'career' ];

	function roleOf( card ) {
		for ( var i = 0; i < ROLES.length; i++ ) {
			if ( card.classList.contains( 'lunara-pair-card--' + ROLES[ i ] ) ) {
				return ROLES[ i ];
			}
		}
		return 'theme';
	}

	function el( tag, className ) {
		var node = document.createElement( tag );
		node.className = className;
		return node;
	}

	function Thread( section ) {
		this.section = section;
		this.head = section.querySelector( '.lunara-pair-cards-head' );
		this.grid = section.querySelector( '.lunara-pair-cards-grid' );
		this.cards = Array.prototype.slice.call( section.querySelectorAll( '.lunara-pair-card' ) );
		this.reach = 0; // Furthest progress reached, 0..1. Never decreases.
		this.nodes = [];
		this.frame = 0;
		this.geometry = null;
		if ( this.grid && this.cards.length ) {
			this.build();
		}
	}

	Thread.prototype.build = function () {
		var self = this;
		this.root = el( 'span', 'lunara-pair-thread' );
		this.root.setAttribute( 'aria-hidden', 'true' );
		this.line = el( 'span', 'lunara-pair-thread-line' );
		this.line.appendChild( document.createElement( 'i' ) );
		this.origin = el( 'span', 'lunara-pair-thread-origin' );
		this.root.appendChild( this.line );
		this.root.appendChild( this.origin );

		this.cards.forEach( function ( card ) {
			var role = roleOf( card );
			var node = el( 'span', 'lunara-pair-thread-node lunara-pair-thread-node--' + role );
			var tick = el( 'span', 'lunara-pair-thread-tick lunara-pair-thread-node--' + role );
			self.root.appendChild( tick );
			self.root.appendChild( node );
			self.nodes.push( { card: card, node: node, tick: tick, at: 0 } );
		} );

		this.section.insertBefore( this.root, this.section.firstChild );
		this.section.classList.add( 'has-pair-thread' );
		if ( ! reduced ) {
			this.section.classList.add( 'is-thread-animated' );
		}

		this.layout();

		var schedule = function () { self.schedule(); };
		window.addEventListener( 'scroll', schedule, { passive: true } );
		window.addEventListener( 'resize', function () { self.layout(); } );
		if ( 'ResizeObserver' in window ) {
			// Spoiler reveals, late posters and font swaps all move the cards.
			new ResizeObserver( function () { self.layout(); } ).observe( this.grid );
		}
	};

	/** Measure the cards and place the line, nodes and ticks. */
	Thread.prototype.layout = function () {
		var box = this.section.getBoundingClientRect();
		var gridBox = this.grid.getBoundingClientRect();
		if ( ! box.width || ! gridBox.height ) {
			this.root.hidden = true;
			return;
		}

		var rects = this.cards.map( function ( card ) {
			var poster = card.querySelector( '.lunara-pair-card-poster' ) || card;
			return { card: card.getBoundingClientRect(), poster: poster.getBoundingClientRect() };
		} );
		var sideBySide = rects.length > 1 && Math.abs( rects[ 0 ].card.top - rects[ rects.length - 1 ].card.top ) < 8;
		var gutter = gridBox.left - box.left;
		var headBottom = this.head ? this.head.getBoundingClientRect().bottom - box.top : gridBox.top - box.top;
		var geometry;

		if ( ! sideBySide ) {
			if ( gutter < 12 ) {
				this.root.hidden = true;
				return;
			}
			var x = gutter / 2;
			var start = Math.max( 8, headBottom - 18 );
			var points = rects.map( function ( r ) {
				return r.poster.top + r.poster.height / 2 - box.top;
			} );
			var end = points[ points.length - 1 ];
			geometry = { vertical: true, start: start, length: Math.max( 1, end - start ) };
			this.line.style.cssText = 'left:' + x + 'px;top:' + start + 'px;height:' + geometry.length + 'px;';
			this.origin.style.cssText = 'left:' + x + 'px;top:' + start + 'px;';
			this.nodes.forEach( function ( n, i ) {
				n.at = ( points[ i ] - start ) / geometry.length;
				n.node.style.cssText = 'left:' + x + 'px;top:' + points[ i ] + 'px;';
				n.tick.style.cssText = 'left:' + ( x + 6 ) + 'px;top:' + points[ i ] + 'px;width:' + Math.max( 0, gutter - x - 6 ) + 'px;';
			} );
		} else {
			var gap = gridBox.top - box.top - headBottom;
			var y = gridBox.top - box.top - Math.max( 8, gap / 2 );
			var xs = rects.map( function ( r ) {
				return r.card.left + r.card.width / 2 - box.left;
			} );
			var from = Math.max( 8, gridBox.left - box.left );
			geometry = { vertical: false, start: from, length: Math.max( 1, xs[ xs.length - 1 ] - from ) };
			this.root.classList.add( 'is-horizontal' );
			this.line.style.cssText = 'left:' + from + 'px;top:' + y + 'px;width:' + geometry.length + 'px;';
			this.origin.style.cssText = 'left:' + from + 'px;top:' + y + 'px;';
			this.nodes.forEach( function ( n, i ) {
				n.at = ( xs[ i ] - from ) / geometry.length;
				n.node.style.cssText = 'left:' + xs[ i ] + 'px;top:' + y + 'px;';
				n.tick.style.cssText = 'display:none;';
			} );
		}

		if ( geometry.vertical ) {
			this.root.classList.remove( 'is-horizontal' );
		}
		this.root.hidden = false;
		this.geometry = geometry;
		this.update();
	};

	Thread.prototype.schedule = function () {
		var self = this;
		if ( this.frame || this.reach >= 1 ) {
			return;
		}
		this.frame = window.requestAnimationFrame( function () {
			self.frame = 0;
			self.update();
		} );
	};

	/** Advance the thread to the reader's position. */
	Thread.prototype.update = function () {
		if ( ! this.geometry ) {
			return;
		}
		var progress = 1;
		if ( ! reduced ) {
			var box = this.section.getBoundingClientRect();
			var target = window.innerHeight * 0.72;
			if ( this.geometry.vertical ) {
				progress = ( target - ( box.top + this.geometry.start ) ) / this.geometry.length;
			} else {
				// A horizontal thread runs across as the row rises into view.
				progress = ( target - this.grid.getBoundingClientRect().top ) / ( window.innerHeight * 0.35 );
			}
		}
		this.reach = Math.max( this.reach, Math.min( 1, Math.max( 0, progress ) ) );
		this.root.style.setProperty( '--pair-thread-progress', this.reach.toFixed( 4 ) );

		var reach = this.reach;
		this.nodes.forEach( function ( n ) {
			var lit = reach >= n.at - 0.001;
			n.node.classList.toggle( 'is-lit', lit );
			n.tick.classList.toggle( 'is-lit', lit );
			n.card.classList.toggle( 'is-thread-reached', lit );
		} );
	};

	function boot() {
		document.querySelectorAll( '.lunara-review-single-debrief-section .lunara-pair-cards' ).forEach( function ( section ) {
			if ( ! section.lunaraPairThread ) {
				section.lunaraPairThread = new Thread( section );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
