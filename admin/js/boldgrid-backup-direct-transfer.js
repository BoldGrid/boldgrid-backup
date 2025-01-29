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
BoldGrid.DirectTransfers = function( $ ) {
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
			callback(response, callbackArgs);
		});
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
			$sectionLinks = $('.bg-left-nav li[data-section-id]');

		$cancelButton.on('click', function(e) {
			var $this = $(e.currentTarget),
				transferId = $this.data('transferId');

			e.preventDefault();
			$this.prop('disabled', true);
			$this.text('Cancelling...');
			self._restRequest(
				'cancel-transfer',
				'POST',
				{ transfer_id: transferId },
				self._cancelCallback,
				{ $cancelButton: $this }
			);
		});

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
			var $this = $(e.currentTarget),
				transferId = $this.data('transferId');

			e.preventDefault();
			$this.prop('disabled', true);
			$this.text('Restoring...');

			self._restRequest(
				'start-restore',
				'POST',
				{ transfer_id: transferId },
				self._startRestoreCallback,
				{ $restoreButton: $this }
			);
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
			$this.text('Deleting...');

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
			var $appUuidInput = $('#app_uuid'),
				$authAdminInput = $('#auth_admin_url'),
				appUuid = $appUuidInput.val();

			e.preventDefault();
			self._authTransfer($authAdminInput.val(), appUuid);
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
		var $button = args.$restoreButton;
		console.log({ responseType: typeof response });
		if (response.success) {
			$button.text('Restoring');
			window.location.reload();
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
			$button.text('Cancelled');
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
			$button.text('Deleted');
			// Wait 2 seconds then reload page.
			setTimeout(function() {
				location.reload();
			}, 2000);
		} else {
			$button.text('Delete Error. Refresh and try again.');
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
				timeElapsed = response.data.elapsed_time;

			if ('completed' === status) {
				$row.addClass('completed');
				$progressBar.addClass('completed');
				clearInterval($row.data('intervalId'));

				status = 'Completed';
				progress = 100;
				progressText = '100%';
				progressStatusText = progressStatusText;

				window.location.reload();
			}

			$progressBarFill.css('width', progress + '%');
			$progressText.text(progressText);
			$progressStatusText.text(progressStatusText);
			$timeElapsedText.text(timeElapsed);

			if ('canceled' === status) {
				$row.addClass('canceled');
				$progressStatusText.text('Canceled');
			}
		} else {
			console.log(response);
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
		var $button = args.$startButton,
			url = args.url;
		$button.prop('disabled', true);

		if (response.success) {
			var transferId = response.data.transfer_id;
			$button.text('Transfer Started');
			$button.addClass('transfer-started');
			$button.prop('style', 'color: #2271b1 !important');
			setTimeout(function() {
				self._addTransferRow(transferId, url);
			}, 3000);
		}
	};

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
				<td class="status">Pending</td>
				<td class="time_elapsed">0:00</td>
				<td class="actions">
					<button class="cancel-transfer button-secondary" data-transfer-id="${transferId}">Cancel</button>
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
	self._authTransfer = function(authAdminUrl, appUuid) {
		var endpointUri = 'authorize-application.php',
			params = $.param({
				app_name: 'BoldGrid Transfer',
				app_id: appUuid,
				success_url: window.location.href
			});

		window.location.href = authAdminUrl + endpointUri + '?' + params;
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
				'.bgbkup-transfers-rx tr.progress-row:not( .hidden ):not( .canceled ):not( .completed )'
			),
			interval = 15000;

		$statusRow.each(function(index, row) {
			$(row).attr('data-intervalId', setInterval(self._checkRxStatus, interval, row));
			transferId = $(row).data('transferId');
			self._checkRxStatus(row);
		});
	},

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
			).find('.time_elapsed');

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
				$timeElapsedText: $timeElapsedText
			}
		);
	};

	$( function() {
		self.init();
	} );
};

BoldGrid.DirectTransfers( jQuery );

