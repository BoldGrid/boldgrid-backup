/**
 * Update Selectors
 *
 * JavaScript for handling WordPress' native "update" buttons and links. Primary
 * function is to disable "update" buttons when we're in the middle of making a
 * backup.
 *
 * @since 1.6.0
 *
 * @param $ The jQuery object.
 */

/* global boldgrid_backup_admin_update_selectors,jQuery */

var BOLDGRID = BOLDGRID || {};
BOLDGRID.BACKUP = BOLDGRID.BACKUP || {};

( function( $ ) {
	BOLDGRID.BACKUP.UpdateSelectors = {
		lang: boldgrid_backup_admin_update_selectors,

		$selectors: null,

		selectors: [

			// Plugins > Installed Plugins > "Apply" button for "Bulk Actions".
			'#doaction',
			'#doaction2',

			// Plugins > Installed Plugins > Inline "update now" link on Plugins > Installed Plugins.
			'.update-link',

			// Dashboard > Updates > "Update Plugins" button.
			'#upgrade-plugins',
			'#upgrade-plugins-2',

			// Dashboard > Updates > "Update" and "Re-install" WordPress button.
			'#upgrade',

			// Dashboard > Updates > "Update Themes" button.
			'#upgrade-themes',
			'#upgrade-themes-2',

			// Dashboard > Customize > Change Themes > Inline "Update now" link.
			// Customizer > Installed themes > "Update now" link.
			'.themes .update-message .button-link',

			// Dashboard > Customize > Changes Themes > Click a theme > "update now" link.
			// Customizer > Installed themes > click a theme > "update now" link.
			'#update-theme',

			// Update Protection > "Backup Site Now" button.
			'.notice #backup-site-now',

			// Backup Archive > "Backup Site Now" button.
			'.page-title-actions .page-title-action'
		],

		/**
		 * @summary Enable update selectors.
		 *
		 * @since 1.6.0
		 */
		enable: function() {
			var self = BOLDGRID.BACKUP.UpdateSelectors;

			self.setSelectors();

			self.$selectors.each( function() {
				var $el = $( this ),
					$target;

				$el.attr( 'disabled', false );

				// See comment in self::disable().
				$target = $el.is( 'a' ) ? $el.parent() : $el;
				$target.attr( 'title', '' ).removeClass( self.lang.waitClass );
			} );
		},

		/**
		 * @summary Disable update selectors.
		 *
		 * @since 1.6.0
		 */
		disable: function() {

			/*
			 * Timeout is required because some "Update" links are added via the
			 * wp.template system and do not call a trigger to alert when they're
			 * done.
			 */
			setTimeout( function() {
				var self = BOLDGRID.BACKUP.UpdateSelectors;

				self.setSelectors();

				self.$selectors.each( function() {
					var $el = $( this ),
						$target;

					$el.attr( 'disabled', true );

					/*
					 * Anchors behave differently. When you set an anchor to disabled,
					 * you cannot hover and see a title. For anchors, we'll temporarily
					 * adjust the parent instead.
					 */
					$target = $el.is( 'a' ) ? $el.parent() : $el;
					$target.attr( 'title', self.lang.backupInProgress ).addClass( self.lang.waitClass );
				} );
			}, 250 );
		},

		/**
		 * @summary Init.
		 *
		 * @since 1.6.0
		 */
		init: function() {
			$( 'body' ).on( 'boldgrid_backup_complete', $.proxy( this.enable, this ) );
			$( 'body' ).on( 'boldgrid_backup_progress_notice_added', $.proxy( this.disable, this ) );

			this.onInProgress();
		},

		/**
		 * @summary Actions to take when there is a backup in progress.
		 *
		 * @since 1.6.0
		 */
		onInProgress: function() {
			if ( 1 === $( '.boldgrid-backup-in-progress' ).length ) {
				this.disable();
			}
		},

		/**
		 * @summary Set our $selectors, which are used to find "update" buttons.
		 *
		 * @since 1.6.0
		 */
		setSelectors: function() {
			this.$selectors = $( this.selectors.join( ', ' ) );
		}
	};

	$( function() {
		BOLDGRID.BACKUP.UpdateSelectors.init();
	} );
} )( jQuery );
