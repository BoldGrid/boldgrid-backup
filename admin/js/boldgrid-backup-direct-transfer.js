( function( $ ) {
	var BOLDGRID = BOLDGRID || {};
	BOLDGRID.TRANSFERS = BOLDGRID.TRANSFERS || {};

	var self;

	BOLDGRID.TRANSFERS.TransfersV2 = {
		init: function() {
			$( self._onLoad );
		},
		_onLoad: function() {
			self._bindEvents();
			self._checkReceiveStatus();
		},
		_bindEvents: function() {
			var $authButton  = $( '#auth_transfer' ),
				$xferButtons = $( 'button.start-transfer' ),
				$restoreButton = $( 'button.restore-site' ),
				$resyncDbButton = $( 'button.resync-database' ),
				$cancelButton = $( 'button.cancel-transfer' ),
				$deleteButton = $( 'button.delete-transfer' ),
				$closeModalButton = $( '#test-results-modal-close' );

			self._bindCancelButton( $cancelButton );

			$closeModalButton.on( 'click', function( e ) {
				const modal         = document.getElementById( 'test-results-modal' );
				modal.style.display = "none";
			} );

			$xferButtons.on( 'click', function( e ) {
				var $this = $( e.currentTarget ),
					url   = $this.data( 'url' ),
					nonce = $( '#transfer_start_nonce' ).val();

				e.preventDefault();
				self._startTransfer( $this, url, nonce );
			} );

			$restoreButton.on( 'click', function( e ) {
				var $this      = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#restore_site_nonce' ).val();

				e.preventDefault();
				self._startRestore( $this, transferId, nonce );
			} );

			$resyncDbButton.on( 'click', function( e ) {
				var $this 	= $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#resync_database_nonce' ).val();

				e.preventDefault();
				self._startResyncDb( $this, transferId, nonce );
			} );

			$deleteButton.on( 'click', function( e ) {
				var $this 	 = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#delete_transfer_nonce' ).val();

				e.preventDefault();
				self._deleteTransfer( $this, transferId, nonce );
			} );

			$authButton.on( 'click', function( e ) {
				var $appUuidInput = $( '#app_uuid' ),
					$authAdminInput = $( '#auth_admin_url' ),
					appUuid = $appUuidInput.val();

				e.preventDefault();
				self._authTransfer( $authAdminInput.val(), appUuid );
			} );
		},
		_bindCancelButton: function( $button ) {
			$button.on( 'click', function( e ) {
				var $this 	 = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#cancel_transfer_nonce' ).val();

				e.preventDefault();
				self._cancelTransfer( $this, transferId, nonce );
			} );
		},
		_startRestore: function( $button, transferId, nonce ) {
			$button.prop( 'disabled', true );
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_start_restore',
					'transfer_id': transferId,
					'nonce': nonce
				},
			} ).done( function( response ) {
				console.log( response );
				if ( response.success ) {
					$button.text( 'Restoring' );
				}
			} );
		},
		_startResyncDb: function( $button, transferId, nonce ) {
			$button.prop( 'disabled', true );
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_resync_database',
					'transfer_id': transferId,
					'nonce': nonce
				},
			} ).done( function( response ) {
				console.log( response );
				if ( response.success ) {
					window.location.reload();
				}
			} );
		},
		_cancelTransfer: function( $button, transferId, nonce ) {
			$button.prop( 'disabled', true );
			$button.text( 'Cancelling...' );
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_cancel_transfer',
					'transfer_id': transferId,
					'nonce': nonce
				},
			} ).done( function( response ) {
				console.log( response );
				if ( response.success ) {
					$button.text( 'Cancelled' );
					// Wait 3 seconds then reload page.
					setTimeout( function() {
						location.reload();
					}, 3000 );
				}
			} );
		},
		_deleteTransfer: function( $button, transferId, nonce ) {
			$button.prop( 'disabled', true );
			$button.text( 'Deleting...' );
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_delete_transfer',
					'transfer_id': transferId,
					'nonce': nonce
				},
			} ).done( function( response ) {
				console.log( response );
				if ( response.success ) {
					$button.text( 'Deleted' );
					// Wait 3 seconds then reload page.
					setTimeout( function() {
						location.reload();
					}, 3000 );
				} else {
					$button.text( 'Delete Error. Refresh and try again.' );
				}
			} );
		},
		_startTransfer: function( $button, url, nonce ) {
			$button.prop( 'disabled', true );

			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_start_rx',
					'url': url,
					'nonce': nonce
				},
			} ).done( function( response ) {
				console.log( { response } );
				if ( response.success ) {
					var transferId = response.data.transfer_id;
					$button.text( 'Transfer Started' );
					$button.addClass( 'transfer-started' );
					$button.prop( 'style', 'color: #2271b1 !important' );
					self._processTransfer( transferId );
					setTimeout( function() {
						self._addTransferRow( transferId, url );
					}, 3000 );
				} else if ( false === response.success && response.data.tests ) {
					self._checkResultsAndOpenModal( response.data.tests );
					$button.text( 'Preflight Tests Failed' );
					$button.addClass( 'preflight-tests-failed' );
				}
			} );
		},
		_checkResultsAndOpenModal: function( results ) {
			// Collect messages of items with `result` as false
			const failedMessages = Object.values( results )
				.filter( item => ! item.result ) // Filter for false results
				.map( item => item.message ); // Map to get the message only
	
			if ( 0 !== failedMessages.length ) {
				// Populate the modal content with the failed messages
				const modalContent     = document.getElementById( 'test-results-modal-content' );
				modalContent.innerHTML = failedMessages.join( '' );
		
				// Show the modal
				const modal = document.getElementById( 'test-results-modal' );
				modal.style.display = 'block';
			}
		},
		_addTransferRow: function( transferId, url ) {
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
			$( '.bgbkup-transfers-tx-table tbody' ).append( markup );
			$( '.bgbkup-transfers-rx tbody tr.bgbkup-transfers-none-found' ).remove();

			$cancelButton = $( `.bgbkup-transfers-tx-table tbody tr.transfer-info[data-transfer-id="${transferId}"] button.cancel-transfer` );

			console.log( $cancelButton );

			self._checkReceiveStatus();
			self._bindCancelButton( $cancelButton );
			
		},
		_authTransfer: function( authAdminUrl, appUuid ) {
			var endpointUri = 'authorize-application.php',
				params      = $.param( {
					'app_name': 'BoldGrid Transfer',
					'app_id': appUuid,
					'success_url': window.location.href,
				} );

			console.log( {
				'authAdminUrl': authAdminUrl,
				'endpointUri': endpointUri,
				'params': params,
				'fullUrl': authAdminUrl + endpointUri + '?' + params,
			} );
			window.location.href = authAdminUrl + endpointUri + '?' + params;
		},
		_processTransfer: function( transferId ) {
			var nonce = $( '#verify_files_nonce' ).val();
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_verify_files',
					'nonce' : nonce,
					'type': 'rx',
					'transfer_id': transferId,
				},
			} );
		},
		_checkReceiveStatus: function() {
			var $statusRow = $( '.bgbkup-transfers-rx tr.progress-row:not( .hidden ):not( .canceled )' );

			$statusRow.each( function( index, row ) {
				console.log( $statusRow );
				$( row ).attr( 'data-intervalId', setInterval( self._checkRxStatus, 15000, row ) );
				self._checkRxStatus( row );
			} );
		},
		_checkRxStatus: function( row ) {
			var $row                = $( row ),
				transferId          = $row.data( 'transferId' ),
				nonce               = $( '#check_status_nonce' ).val(),
				$progressBar        = $row.find( '.progress-bar' ),
				$progressText       = $row.find( '.progress-bar-text' ),
				$progressBarFill    = $row.find( '.progress-bar-fill' ),
				$progressStatusText = $('.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']').find( '.status' ),
				$timeElapsedText    = $('.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']').find( '.time_elapsed' );

			console.log(  { row } );
			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					'action': 'boldgrid_transfer_check_status',
					'nonce' : nonce,
					'type': 'rx',
					'transfer_id': transferId,
				},
			} ).done( function( response ) {
				if ( response.success ) {
					var status             = response.data.status,
						progress           = response.data.progress,
						progressText       = response.data.progress_text,
						progressStatusText = response.data.progress_status_text,
						timeElapsed        = response.data.elapsed_time;

					
					if ( 'completed' === status ) {
						$row.addClass( 'completed' );
						$progressBar.addClass( 'completed' );
						clearInterval( $row.data( 'intervalId' ) );

						status = 'Completed';
						progress = 100;
						progressText = '100%';
						progressStatusText = 'Transfer Completed';
					}

					$progressBarFill.css( 'width', progress + '%' );
					$progressText.text( progressText );
					$progressStatusText.text( progressStatusText );
					$timeElapsedText.text( timeElapsed );

					console.log( { response: response.data } );

					if ( 'canceled' === status ) {
						console.log( 'canceled' );
						$row.addClass( 'canceled' );
						$progressStatusText.text( 'Canceled' );
					}
				} else {
					console.log( response );
					$row.addClass( 'error' );
				}
			} );
		},
		_formatTime: function( seconds ) {
			const minutes = Math.floor(seconds / 60);
			const remainingSeconds = Math.floor(seconds % 60);

			// Pad with leading zero if necessary
			const formattedMinutes = minutes.toString();
			const formattedSeconds = remainingSeconds.toString().padStart(2, '0');

			return `${formattedMinutes}:${formattedSeconds}`;
		}
	};

	self = BOLDGRID.TRANSFERS.TransfersV2;

	BOLDGRID.TRANSFERS.TransfersV2.init();
} )( jQuery );