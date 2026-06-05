( function( $, api ) {
	'use strict';

	if ( ! api || ! api.control ) {
		return;
	}

	const strings = window.lunaraCustomizerA11y || {};

	function getControlUnit( control ) {
		const title = control.find( '.customize-control-title' ).first().text().trim();
		const match = title.match( /\(([^)]+)\)\s*$/ );

		return match ? match[1].trim() : '';
	}

	function formatNumber( value, step ) {
		const numericValue = Number( value );
		const numericStep  = Number( step );

		if ( Number.isNaN( numericValue ) ) {
			return '';
		}

		if ( ! Number.isNaN( numericStep ) && numericStep > 0 && numericStep < 1 ) {
			const decimals = ( String( numericStep ).split( '.' )[1] || '' ).length;
			return numericValue.toFixed( decimals ).replace( /0+$/, '' ).replace( /\.$/, '' );
		}

		return String( numericValue );
	}

	function buildMetaText( currentValue, min, max, step, unit ) {
		const unitSuffix = unit ? ' ' + unit : '';

		return [
			( strings.currentValue || 'Current value' ) + ': ' + currentValue + unitSuffix,
			( strings.allowedRange || 'Allowed range' ) + ': ' + min + unitSuffix + ' to ' + max + unitSuffix,
			( strings.step || 'Step' ) + ': ' + step + unitSuffix,
		].join( ' | ' );
	}

	function syncRangeControl( rangeInput, numberInput, metaNode, unit ) {
		const min  = rangeInput.attr( 'min' ) || '0';
		const max  = rangeInput.attr( 'max' ) || '100';
		const step = rangeInput.attr( 'step' ) || '1';

		const update = function( rawValue ) {
			const formattedValue = formatNumber( rawValue, step );
			numberInput.val( formattedValue );
			metaNode.text( buildMetaText( formattedValue, min, max, step, unit ) );
			rangeInput.attr( 'aria-valuetext', unit ? formattedValue + ' ' + unit : formattedValue );
			numberInput.attr( 'aria-valuetext', unit ? formattedValue + ' ' + unit : formattedValue );
		};

		rangeInput.on( 'input change', function() {
			update( $( this ).val() );
		} );

		numberInput.on( 'input change', function() {
			const newValue = $( this ).val();
			rangeInput.val( newValue ).trigger( 'input' ).trigger( 'change' );
			update( newValue );
		} );

		update( rangeInput.val() );
	}

	function enhanceRangeControl( control ) {
		if ( control.hasClass( 'lunara-range-enhanced' ) ) {
			return;
		}

		const rangeInput = control.find( 'input[type="range"]' ).first();

		if ( ! rangeInput.length ) {
			return;
		}

		const title = control.find( '.customize-control-title' ).first().text().trim() || 'Customizer control';
		const unit  = getControlUnit( control );

		rangeInput.addClass( 'lunara-range-slider' );

		const wrapper = $( '<div class="lunara-range-accessory"></div>' );
		const row     = $( '<div class="lunara-range-accessory-row"></div>' );
		const label   = $( '<label class="lunara-range-number-label"></label>' );
		const labelText = $( '<span class="lunara-range-number-title"></span>' ).text( strings.exactValueLabel || 'Exact value' );
		const numberInput = $( '<input type="number" class="lunara-range-number" />' );
		const metaNode    = $( '<p class="lunara-range-meta" aria-live="polite"></p>' );
		const hintNode    = $( '<p class="lunara-range-hint"></p>' ).text( strings.typingHint || 'You can type the exact number here instead of dragging the slider.' );

		numberInput.attr( {
			min: rangeInput.attr( 'min' ) || '',
			max: rangeInput.attr( 'max' ) || '',
			step: rangeInput.attr( 'step' ) || '1',
			inputmode: 'decimal',
			'aria-label': ( strings.exactValueLabel || 'Exact value' ) + ' for ' + title,
		} );

		label.append( labelText );
		label.append( numberInput );

		if ( unit ) {
			label.append( $( '<span class="lunara-range-unit"></span>' ).text( unit ) );
		}

		row.append( label );
		wrapper.append( row );
		wrapper.append( metaNode );
		wrapper.append( hintNode );

		rangeInput.after( wrapper );
		control.addClass( 'lunara-range-enhanced' );

		syncRangeControl( rangeInput, numberInput, metaNode, unit );
	}

	function bootEnhancements() {
		$( '.customize-control' ).each( function() {
			enhanceRangeControl( $( this ) );
		} );
	}

	$( bootEnhancements );

	api.bind( 'ready', bootEnhancements );
} )( jQuery, wp.customize );
