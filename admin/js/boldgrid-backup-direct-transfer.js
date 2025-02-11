/**
 * File: boldgrid-backup-direct-transfer.js
 *
 * Direct Transfer UI
 *
 * @since 1.17.0
 *
 * @package BoldGrid\Backup
 */

var BoldGrid = BoldGrid || {};

/**
 * DirectTransfers
 *
 * This object contains all the functions and properties
 * for the Direct Transfers UI. Specifically, this uses
 * Rest API endpoints to start, cancel, delete, and check
 * the status of a transfer.
 *
 * @since 1.17.0
 */
BoldGrid.DirectTransfers = function($) {
	var self = this;

	self.init = function() {
		$(self._onLoad);
	};

	/**
	 * On Load
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._onLoad = function() {
		self.lang = BoldGridBackupAdmin.lang;
		self._bindEvents();
		self._checkReceiveStatus();
	};

	/**
	 * Rest Request
	 *
	 * @param {string} endpoint           The endpoint to be called ie. 'start-migration'
	 * @param {string} method             The method to be used ie. 'POST'
	 * @param {object} data               The data to be sent
	 * @param {CallableFunction} callback Function to call on completion
	 * @param {object} callbackArgs       Arguments to pass to the callback function
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._restRequest = function(endpoint, method, data, callback, callbackArgs = {}) {
		wp.apiRequest({
			path: '/boldgrid-backup/v1/direct-transfer/' + endpoint,
			method: method,
			data: data
		}).then(function(response) {
			console.log( 'API Response', { response, callback, callbackArgs } );
			callback(response, callbackArgs);
		}).fail(function(response, responseText) {
			/*
			 * If we are checking the status, and the status is 'restoring-db' and the response is 403
			 * then this likely indicates that the site has been restored, and user has been logged out.
			 * Therefore, we want to reload the page.
			 */
			if ( 'check-status' === endpoint 
				&& ( 'restoring-db' === callbackArgs.status || 'restoring-files' === callbackArgs.status )
				&& 403 === response.status ) {
				window.location.reload();
			}
			console.log( 'API Error', { endpoint, response, responseText, callback, callback } );
		} );
	};

	/**
	 * Bind Events
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._bindEvents = function() {
		var $authButton = $('#auth_transfer'),
			$xferButtons = $('button.start-transfer'),
			$restoreButton = $('button.restore-site'),
			$resyncDbButton = $('button.resync-database'),
			$cancelButton = $('button.cancel-transfer'),
			$deleteButton = $('button.delete-transfer'),
			$sectionLinks = $('.bg-left-nav li[data-section-id]'),
			$authError    = $('.bgbkup-transfers-rx .authentication-error'),
			$closeModal   = $( '.direct-transfer-modal-close' );

		self_bindCancelButton = self._bindCancelButton( $cancelButton );

		// Hide the authentication error message on load.
		$authError.hide();

		$closeModal.on( 'click', self._closeModal );

		// Hide the authentication error message on input.
		$('#auth_admin_url').on('input', function() {
			$authError.hide();
		} );

		// Bind the Start Transfer button
		$xferButtons.on('click', function(e) {
			var $this = $(e.currentTarget),
				url = $this.data('url');

			e.preventDefault();
			$this.prop('disabled', true);

			self._restRequest('start-migration', 'POST', { url: url }, self._startTransferCallback, {
				$startButton: $this,
				url: url
			});
		});

		// Bind the Restore button
		$restoreButton.on('click', function(e) {
			var $restoreButton = $(e.currentTarget),
				transferId     = $restoreButton.data('transferId');

			e.preventDefault();

			self._openModal( 'restore-site', transferId );

			$( '#restore-site-yes' ).on( 'click', function( e ) {
				e.preventDefault();

				self._closeModal( e );

				$restoreButton.prop('disabled', true);
				$restoreButton.text(self.lang.restoring + '...');

				self._restRequest(
					'start-restore',
					'POST',
					{ transfer_id: transferId },
					self._startRestoreCallback,
					{ $restoreButton: $restoreButton }
				);
			} );
		});

		// Bind the Resync Database button
		$resyncDbButton.on('click', function(e) {
			var $this = $(e.currentTarget),
				transferId = $this.data('transferId');

			e.preventDefault();
			$this.prop('disabled', true);

			self._restRequest(
				'resync-database',
				'POST',
				{ transfer_id: transferId },
				self._resyncCallback
			);
		});

		// Bind the Delete button
		$deleteButton.on('click', function(e) {
			var $this = $(e.currentTarget),
				transferId = $this.data('transferId');

			e.preventDefault();
			$this.prop('disabled', true);
			$this.text(self.lang.deleting + '...');

			self._restRequest(
				'delete-transfer',
				'POST',
				{ transfer_id: transferId },
				self._deleteCallback,
				{ $deleteButton: $this }
			);
		});

		// Bind the Authenticate Button
		$authButton.on('click', function(e) {
			var $appUuidInput   = $('#app_uuid'),
				$authAdminInput = $('#auth_admin_url'),
				appUuid         = $appUuidInput.val();

			e.preventDefault();

			self._validateUrl( $authAdminInput.val(), appUuid );

		});

		// Bind the section links to add query arg.
		$sectionLinks.on('click', function(e) {
			var $link = $(this),
				sectionId = $link.attr('data-section-id'),
				url = new URL(window.location);

			// Older browsers ( IE < 10 ) do not support history.pushState
			if (window.history.pushState) {
				url.searchParams.set('section', sectionId);
				window.history.pushState({}, '', url);
			}
		});
	};

	self._openModal = function( modalId, transferId ) {
		var $modal           = $( '.direct-transfer-modal[data-modal-id="' + modalId + '"]' ),
			$transferIdInput = $modal.find( 'input[name="transfer_id"]' );

		$transferIdInput.val( transferId );

		$modal.show();
	}

	self._closeModal = function( e ) {
		var $this  = $( e.currentTarget ),
			$modal = $this.parents( '.direct-transfer-modal' );
			$modal.hide();
	}

	self._bindCancelButton = function($cancelButton) {
		$cancelButton.on('click', function(e) {
			var $this = $(e.currentTarget),
				transferId = $this.data('transferId');

			e.preventDefault();
			$this.prop('disabled', true);
			$this.text(self.lang.cancelling + '...');
			self._restRequest(
				'cancel-transfer',
				'POST',
				{ transfer_id: transferId },
				self._cancelCallback,
				{ $cancelButton: $this }
			);
		});
	};

	/**
	 * Validate URL
	 *
	 * Validate that the URL is in fact an actual URL,
	 * validate that the URL is not for the same site
	 * as the current site. If the URL is invalid, display
	 * an error message, and return false. If it is valid,
	 * return the validated URL string.
	 * 
	 * @param {string} url The URL to validate
	 *
	 * @return {string|boolean} The validated URL or false
	 */
	self._validateUrl = function(url, appUuid ) {
		var $error = $('.bgbkup-transfers-rx .authentication-error' ),
			urlObj;

		// Try to create a URL object from the string.
		try {
			urlObj = new URL(url);
		} catch (e) {
			$error.text( self.lang.invalid_url );
			$error.show();
			return false;
		}

		// Get Current Site URL Base by getting everything that comes before the wp-admin
		var currentSiteUrl = window.location.href.split( 'wp-admin' )[0];

		// Check if url contains the currentSiteUrl
		if ( urlObj.href.includes( currentSiteUrl ) ) {
			$error.text( self.lang.same_site_error );
			$error.show();
			return false;
		}

		self._restRequest(
			'validate-url',
			'GET',
			{ url: url },
			function( response ) {
				if ( response.success && response.auth_endpoint ) {
					self._authTransfer( response.auth_endpoint, appUuid );
				} else {
					$error.text( response.message );
					$error.show();
				}
			}
		);

		return false;
	}

	/**
	 * Start Restore Callback
	 *
	 * Callback for endpoint: /start-restore
	 *
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._startRestoreCallback = function(response, args) {
		var $button      = args.$restoreButton,
			transferId   = $button.data( 'transferId' ),
			$progressDiv = $button.parents( 'tbody' ).find( '.progress-row[data-transfer-id="' + transferId + '"] div.progress' );
		if ( response.success ) {
			$button.text(self.lang.restoring);
			window.location.reload();
		} else {
			$button.text( self.lang.restore );
			$progressDiv.empty();
			$progressDiv.append( '<p class="notice notice-error">' + response.data.error + '</p>' );
			$progressDiv.parents( 'tr').removeClass(  'hidden' );
		}
	};

	/**
	 * Resync DB Callback
	 *
	 * Callback for endpoint: /resync-database
	 *
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._resyncCallback = function(response, args) {
		if (response.success) {
			window.location.reload();
		}
	};

	/**
	 * Cancel Callback
	 *
	 * Callback for endpoint: /cancel-transfer
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._cancelCallback = function(response, args) {
		var $button = args.$cancelButton;
		if (response.success) {
			$button.text(self.lang.cancelled);
			// Wait 3 seconds then reload page.
			setTimeout(function() {
				location.reload();
			}, 3000);
		}
	};

	/**
	 * Delete Callback
	 *
	 * Callback for endpoint: /delete-transfer
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._deleteCallback = function(response, args) {
		var $button = args.$deleteButton;
		if (response.success) {
			$button.text(self.lang.deleted);
			// Wait 2 seconds then reload page.
			setTimeout(function() {
				location.reload();
			}, 2000);
		} else {
			$button.text(self.lang.delete_error);
		}
	};

	/**
	 * Update Progress Bar.
	 *
	 * This is a callback for the 'check-status' Rest API endpoint.
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 */
	self._updateProgressCallback = function(response, args) {
		var $progressBar = args.$progressBar,
			$progressBarFill = args.$progressBarFill,
			$progressStatusText = args.$progressStatusText,
			$timeElapsedText = args.$timeElapsedText,
			$progressText = args.$progressText,
			$row = args.$row;

		if (response.success) {
			var status = response.data.status,
				progress = response.data.progress,
				progressText = response.data.progress_text,
				progressStatusText = response.data.progress_status_text,
				timeElapsed = response.data.elapsed_time,
				borderRadius = '10';

			if ('completed' === status) {
				$row.addClass('completed');
				$progressBar.addClass('completed');
				clearInterval($row.data('intervalid'));

				status = self.lang.completed;
				progress = 100;
				progressText = '100%';
				progressStatusText = progressStatusText;
				
				window.location.reload();
			}

			if ( 99 < progress ) {
				borderRadius = 10 * ( progress - 99 );
				$progressBarFill.css('border-radius', '10px ' +  borderRadius + 'px' + ' ' + borderRadius + 'px 10px');
			} else {
				$progressBarFill.css('border-radius', '10px 0 0 10px');
			}

			$progressBarFill.css('width', progress + '%');
			$progressText.text(progressText);
			$progressStatusText.text(progressStatusText);
			$progressStatusText.data( 'status', status );
			$timeElapsedText.text(timeElapsed);

			if ('canceled' === status) {
				$row.addClass('canceled');
				$progressStatusText.text(self.lang.cancelled);
			}
			if ( 'failed' === status ) {
				clearInterval($row.data('intervalid'));
				$row.addClass('error');
				$row.find( 'td' ).empty();
				$row.find( 'td' ).append( '<p class="notice notice-error">' + progressText + '</p>' );
			}
		} else {
			console.log( 'Update Progress Error: ', { response } );
			$row.addClass('error');
		}
	};

	/**
	 * Start Migration Callback
	 *
	 * Callback for endpoint: /start-migration
	 * @param {object} response The response from the REST API
	 * @param {object} args     The arguments passed to the callback
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._startTransferCallback = function(response, args) {
		var $button   = args.$startButton,
			url       = args.url,
			$errorDiv = $button.parents( 'tbody' ).find( '.errors-row[data-url="' + url + '"] .errors' );
		$button.prop('disabled', true);

		if (response.success) {
			var transferId = response.data.transfer_id;
			$button.text(self.lang.transfer_started);
			$button.addClass('transfer-started');
			$button.prop('style', 'color: #2271b1 !important');
			setTimeout(function() {
				self._addTransferRow(transferId, url);
			}, 3000);
		} else {
			$errorDiv.empty();
			$errorDiv.append( response.data.error );
			self._bindInstallTotalUpkeepButton( $errorDiv.find( 'button.install-total-upkeep' ) );
			self._bindUpdateTotalUpkeepButton( $errorDiv.find( 'button.update-total-upkeep' ) );
		}
	};

	self._bindUpdateTotalUpkeepButton = function( $button ) {
		var url                  = $button.data( 'url' ),
			$errorDiv            = $button.parents( '.errors' ),
			$startTransferButton = $button.parents( 'tbody' ).find( 'button.start-transfer[data-url="' + url + '"]' );

		$button.on( 'click', function( e ) {
			self._restRequest(
				'update-total-upkeep',
				'POST',
				{ url: url },
				self._updateTotalUpkeepCallback,
				{
					$startTransferButton: $startTransferButton,
					$errorDiv: $errorDiv
				}
			)
		} );
	};

	self._updateTotalUpkeepCallback = function( response, args ) {
		if ( response.success ) {
			args.$startTransferButton.prop( 'disabled', false );
			args.$errorDiv.empty();
			args.$errorDiv.append( response.data.message );
		}
	}

	self._bindInstallTotalUpkeepButton = function( $button ) {
		var url                  = $button.data( 'url' ),
			$errorDiv            = $button.parents( '.errors' ),
			$startTransferButton = $button.parents( 'tbody' ).find( 'button.start-transfer[data-url="' + url + '"]' );

		$button.on( 'click', function( e ) {
			self._restRequest(
				'install-total-upkeep',
				'POST',
				{ url: url },
				self._installTotalUpkeepCallback,
				{
					$startTransferButton: $startTransferButton,
					$errorDiv: $errorDiv
				}
			)
		} );
	};

	self._installTotalUpkeepCallback = function( response, args ) {
		if ( response.success ) {
			args.$startTransferButton.prop( 'disabled', false );
			args.$errorDiv.empty();
			args.$errorDiv.append( response.data.message );
		}
	}

	/**
	 * Add Transfer Row
	 *
	 * Add a row to the transfers table once it has been started.
	 *
	 * @param {string} transferId Transfer ID
	 * @param {string} url        Source URL
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._addTransferRow = function(transferId, url) {
		var $cancelButton,
			markup = `<tr class="transfer-info transferring" data-transfer-id="${transferId}">
				<td class="transfer_id">${transferId}</td>
				<td class="dest_url">${url}</td>
				<td class="status">${self.lang.pending}</td>
				<td class="time_elapsed">0:00</td>
				<td class="actions">
					<button class="cancel-transfer button-secondary" data-transfer-id="${transferId}">${self.lang.cancel}</button>
				</td>
			</tr>
			<tr class="progress-row transferring" data-transfer-id="${transferId}">
				<td colspan="5">
					<div class="progress">
						<div class="progress-bar" role="progressbar">
							<div class="progress-bar-text">0%</div>
							<div class="progress-bar-fill" style="width: 0%"></div>
						</div>
					</div>
				</td>
			</tr>`;
		$('.bgbkup-transfers-tx-table tbody').append(markup);
		$('.bgbkup-transfers-rx tbody tr.bgbkup-transfers-none-found').remove();

		$cancelButton = $(
			`.bgbkup-transfers-tx-table tbody tr.transfer-info[data-transfer-id="${transferId}"] button.cancel-transfer`
		);

		self._checkReceiveStatus();
		self._bindCancelButton($cancelButton);
	};

	/**
	 * Authenticate Transfer
	 *
	 * Redirect to the source site to obtain application password.
	 *
	 * @param {string} authAdminUrl WP-Admin URL of source site
	 * @param {string} appUuid      App UUID to be passed to the source site
	 */
	self._authTransfer = function( authEndpoint, appUuid ) {
		var authNonce = $('#auth_nonce').val(),
			params    = $.param({
				app_name: 'Total Upkeep',
				app_id: appUuid,
				success_url: window.location.href + '&_wpnonce=' + authNonce,
			});

		window.location.href = authEndpoint + '?' + params;
	};

	/**
	 * Check Receive Status
	 *
	 * This binds to all the applicable rows
	 * and creates the interval for the _checkRxStatus
	 * function to be called.
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._checkReceiveStatus = function() {
		var $statusRow = $(
				'.bgbkup-transfers-rx tr.progress-row:not( .hidden ):not( .canceled ):not( .completed ):not( .error )'
			),
			interval = 15000;

		$statusRow.each(function(index, row) {
			var intervalId = setInterval(self._checkRxStatus, interval, row );
			$(row).attr('data-intervalId', intervalId );
			transferId = $(row).data('transferId');
			self._checkRxStatus(row);
		});
	};

	/**
	 * Check Rx Status.
	 *
	 * This function is called on an interval
	 * set for each applicable row in the transfers table.
	 *
	 * @param {HTMLTableRowElement} row The row to check the status of
	 *
	 * @return {void}
	 * @since 1.17.0
	 */
	self._checkRxStatus = function(row) {
		var $row = $(row),
			transferId = $row.data('transferId'),
			$progressBar = $row.find('.progress-bar'),
			$progressText = $row.find('.progress-bar-text'),
			$progressBarFill = $row.find('.progress-bar-fill'),
			$progressStatusText = $(
				'.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']'
			).find('.status'),
			$timeElapsedText = $(
				'.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']'
			).find('.time_elapsed'),
			status = $progressStatusText.data('status');

		self._restRequest(
			'check-status',
			'GET',
			{ transfer_id: transferId },
			self._updateProgressCallback,
			{
				$row: $row,
				$progressBar: $progressBar,
				$progressText: $progressText,
				$progressBarFill: $progressBarFill,
				$progressStatusText: $progressStatusText,
				$timeElapsedText: $timeElapsedText,
				status: status
			}
		);
	};

	$(function() {
		self.init();
	});
};

BoldGrid.DirectTransfers(jQuery);
