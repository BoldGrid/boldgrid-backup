/* global ajaxurl,jQuery */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.SETTINGS = BOLDGRID.SETTINGS || {};

( function( $ ) {
	BOLDGRID.SETTINGS.AutoUpdate = {

		/**
		 * Constructor.
		 *
		 * @since 1.7.0
		 */
		init: function() {
			$( self._onLoad );
		},

		/**
		 * On DOM load.
		 *
		 * @since 1.7.0
		 */
		_onLoad: function() {
			var $bgBox = $( '.bg-box' );

			// Initialize jquery-toggles.
			$bgBox.find( '.toggle' ).toggles( {
				text: {
					on: '',
					off: ''
				},
				height: 15,
				width: 40
			} );

			self._setMasterToggles();

			$bgBox.find( '.toggle-group' ).on( 'click swipe contextmenu', self._toggleGroup );

			$bgBox
				.find( '.toggle' )
				.not( '.toggle-group' )
				.on( 'click swipe contextmenu', self._setMasterToggles );

			$bgBox.find( '.dashicons-editor-help' ).on( 'click', self._toggleHelp );

			$bgBox.find( '.bglib-collapsible-control' ).on( 'click', function() {
				$( this ).toggleClass( 'bglib-collapsible-open' );
			} );
		},

		/**
		 * Set inputs for toggles.
		 *
		 * @since 1.7.0
		 */
		_setInputs: function() {
			var $bgBox = $( '.bg-box' ),
				$wpcoreToggles = $bgBox.find( '.wpcore-toggle' ),
				$pluginToggles = $bgBox.find( '.plugin-toggle' ),
				$themeToggles = $bgBox.find( '.theme-toggle' ),
				$pluginsDefault = $bgBox.find( '#toggle-default-plugins' ),
				$themesDefault = $bgBox.find( '#toggle-default-themes' );

			// If the updates section is not in use, then just return.
			if ( ! $pluginsDefault.data( 'toggles' ) ) {
				return;
			}

			$wpcoreToggles.each( function() {
				var $this = $( this );

				$this
					.next( 'input' )
					.attr( 'name', 'autoupdate[wpcore][' + $this.data( 'wpcore' ) + ']' )
					.val( $this.data( 'toggles' ).active ? 1 : 0 );
			} );

			$pluginToggles.each( function() {
				var $this = $( this );

				$this
					.parent()
					.next( 'input' )
					.attr( 'name', 'autoupdate[plugins][' + $this.data( 'plugin' ) + ']' )
					.val( $this.data( 'toggles' ).active ? 1 : 0 );
			} );

			$themeToggles.each( function() {
				var $this = $( this );

				$this
					.parent()
					.next( 'input' )
					.attr( 'name', 'autoupdate[themes][' + $this.data( 'stylesheet' ) + ']' )
					.val( $this.data( 'toggles' ).active ? 1 : 0 );
			} );

			$pluginsDefault
				.parent()
				.next( 'input' )
				.val( $pluginsDefault.data( 'toggles' ).active ? 1 : 0 );

			$themesDefault
				.parent()
				.next( 'input' )
				.val( $themesDefault.data( 'toggles' ).active ? 1 : 0 );
		},

		/**
		 * Set master toggles.
		 *
		 * @since 1.7.0
		 */
		_setMasterToggles: function() {
			var $masters = $( '.bg-box' ).find( '.toggle-group' );

			$masters.each( function() {
				var $master = $( this ),
					state = true;

				$master
					.closest( '.div-table-body' )
					.find( '.toggle' )
					.not( '.toggle-group,#toggle-default-plugins,#toggle-default-themes' )
					.each( function() {
						if ( ! state || ! $( this ).data( 'toggles' ).active ) {
							state = false;
						}
					} );

				$master.toggles( state );
			} );

			self._setInputs();
		},

		/**
		 * Toggle an entire group on/off.
		 *
		 * @since 1.7.0
		 */
		_toggleGroup: function() {
			var $this = $( this ),
				$toggles = $this
					.parent()
					.parent()
					.parent()
					.find( '.toggle' )
					.not( '#toggle-default-plugins,#toggle-default-themes' );

			$toggles.toggles( $this.data( 'toggles' ).active );

			self._setInputs();
		},

		/**
		 * Replace the notice with a clone when removed by dismissal.
		 *
		 * @since 1.7.0
		 */
		_replaceNotice: function( $notice ) {
			var $noticeClone = $notice.clone(),
				$noticeNext = $notice.next();

			$notice.one( 'click.wp-dismiss-notice', '.notice-dismiss', function() {
				$noticeNext.before( $noticeClone );
				$notice = $noticeClone;
				$notice.hide();
			} );
		},

		/**
		 * Handle form submission.
		 *
		 * @since 1.7.0
		 */
		_toggleHelp: function( e ) {
			var id = $( this ).attr( 'data-id' );

			e.preventDefault();

			if ( id === undefined ) {
				return false;
			}

			$( '.help[data-id="' + id + '"]' ).slideToggle();

			return false;
		}
	};

	var self = BOLDGRID.SETTINGS.AutoUpdate;
	BOLDGRID.SETTINGS.AutoUpdate.init();
} )( jQuery );
