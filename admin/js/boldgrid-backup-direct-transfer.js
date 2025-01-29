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
		_restRequest: function( endpoint, method, data, callback, callbackArgs = {} ) {
			wp.apiRequest( {
				path: '/boldgrid-backup/v1/direct-transfer/' + endpoint,
				method: method,
				data: data,
			} ).then( function( response ) {
				callback( response, callbackArgs );
			} );
		},
		_bindEvents: function() {
			var $authButton  = $( '#auth_transfer' ),
				$xferButtons = $( 'button.start-transfer' ),
				$restoreButton = $( 'button.restore-site' ),
				$resyncDbButton = $( 'button.resync-database' ),
				$cancelButton = $( 'button.cancel-transfer' ),
				$deleteButton = $( 'button.delete-transfer' ),
				$closeModalButton = $( '#test-results-modal-close' ),
				$sectionLinks = $( '.bg-left-nav li[data-section-id]' );

			self._bindCancelButton( $cancelButton );

			$closeModalButton.on( 'click', function( e ) {
				const modal         = document.getElementById( 'test-results-modal' );
				modal.style.display = "none";
			} );

			$xferButtons.on( 'click', function( e ) {
				var $this = $( e.currentTarget ),
					url   = $this.data( 'url' );
				e.preventDefault();
				$this.prop( 'disabled', true );
				self._restRequest(
					'start-migration',
					'POST',
					{ url: url },
					self._startTransfer,
					{ $startButton: $this, url: url }
				);
			} );

			$restoreButton.on( 'click', function( e ) {
				var $this      = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#restore_site_nonce' ).val();

				e.preventDefault();
				$this.prop( 'disabled', true );
				$this.text( 'Restoring...' );
				self._restRequest(
					'start-restore',
					'POST',
					{ transfer_id: transferId },
					self._startRestoreCallback,
					{ $restoreButton: $this }
				);
			} );

			$resyncDbButton.on( 'click', function( e ) {
				var $this 	= $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#resync_database_nonce' ).val();

				e.preventDefault();

				$this.prop( 'disabled', true );
				self._restRequest(
					'resync-database',
					'POST',
					{ transfer_id: transferId },
					self._resyncCallback
				);
			} );

			$deleteButton.on( 'click', function( e ) {
				var $this 	 = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#delete_transfer_nonce' ).val();

				e.preventDefault();
				$this.prop( 'disabled', true );
				$this.text( 'Deleting...' );

				self._restRequest(
					'delete-transfer',
					'POST',
					{ transfer_id: transferId },
					self._deleteCallback,
					{ $deleteButton: $this }
				);
			} );

			$authButton.on( 'click', function( e ) {
				var $appUuidInput = $( '#app_uuid' ),
					$authAdminInput = $( '#auth_admin_url' ),
					appUuid = $appUuidInput.val();

				e.preventDefault();
				self._authTransfer( $authAdminInput.val(), appUuid );
			} );

			$sectionLinks.on( 'click', function( e ) {
				var $link = $( this ),
					sectionId = $link.attr( 'data-section-id' ),
					url = new URL( window.location );

				if ( window.history.pushState ) {
					url.searchParams.set( 'section', sectionId );
					window.history.pushState( {}, '', url );
				}
			} );
		},
		_bindCancelButton: function( $button ) {
			$button.on( 'click', function( e ) {
				var $this 	 = $( e.currentTarget ),
					transferId = $this.data( 'transferId' ),
					nonce      = $( '#cancel_transfer_nonce' ).val();

				e.preventDefault();
				$this.prop( 'disabled', true );
				$this.text( 'Cancelling...' );
				self._restRequest(
					'cancel-transfer',
					'POST',
					{ transfer_id: transferId },
					self._cancelCallback,
					{ $cancelButton: $this }
				);
			} );
		},
		_startRestoreCallback: function( response, args ) {
			var $button = args.$restoreButton;
			if ( response.success ) {
				$button.text( 'Restoring' );
				window.location.reload();
			}
		},
		_resyncCallback: function( response, args ) {
			if ( response.success ) {
				window.location.reload();
			}
		},
		_cancelCallback: function( response, args ) {
			var $button = args.$cancelButton;
			if ( response.success ) {
				$button.text( 'Cancelled' );
				// Wait 3 seconds then reload page.
				setTimeout( function() {
					location.reload();
				}, 3000 );
			}
		},
		_deleteCallback: function( response, args ) {
			var $button = args.$deleteButton;
			if ( response.success ) {
				$button.text( 'Deleted' );
				// Wait 2 seconds then reload page.
				setTimeout( function() {
					location.reload();
				}, 2000 );
			} else {
				$button.text( 'Delete Error. Refresh and try again.' );
			}
		},
		_startTransfer: function( response, args ) {
			var $button = args.$startButton,
				url     = args.url;
			$button.prop( 'disabled', true );

			if ( response.success ) {
				var transferId = response.data.transfer_id;
				$button.text( 'Transfer Started' );
				$button.addClass( 'transfer-started' );
				$button.prop( 'style', 'color: #2271b1 !important' );
				setTimeout( function() {
					self._addTransferRow( transferId, url );
				}, 3000 );
			}
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

			window.location.href = authAdminUrl + endpointUri + '?' + params;
		},
		_checkReceiveStatus: function() {
			var $statusRow = $( '.bgbkup-transfers-rx tr.progress-row:not( .hidden ):not( .canceled ):not( .completed )' );

			$statusRow.each( function( index, row ) {
				$( row ).attr( 'data-intervalId', setInterval( self._checkRxStatus, 15000, row ) );
				transferId = $( row ).data( 'transferId' );
				self._checkRxStatus( row );
			} );
		},
		_updateProgress: function( response, args ) {
			var $progressBar        = args.$progressBar,
				$progressBarFill    = args.$progressBarFill,
				$progressStatusText = args.$progressStatusText,
				$timeElapsedText	= args.$timeElapsedText,
				$progressText       = args.$progressText,
				$row				= args.$row;

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
					progressStatusText = progressStatusText;

					window.location.reload();
				}

				$progressBarFill.css( 'width', progress + '%' );
				$progressText.text( progressText );
				$progressStatusText.text( progressStatusText );
				$timeElapsedText.text( timeElapsed );

				if ( 'canceled' === status ) {
					$row.addClass( 'canceled' );
					$progressStatusText.text( 'Canceled' );
				}
			} else {
				console.log( response );
				$row.addClass( 'error' );
			}
		},
		_checkRxStatus: function( row ) {
			var $row                = $( row ),
				transferId          = $row.data( 'transferId' ),
				$progressBar        = $row.find( '.progress-bar' ),
				$progressText       = $row.find( '.progress-bar-text' ),
				$progressBarFill    = $row.find( '.progress-bar-fill' ),
				$progressStatusText = $('.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']').find( '.status' ),
				$timeElapsedText    = $('.bgbkup-transfers-rx tr.transfer-info[data-transfer-id=' + transferId + ']').find( '.time_elapsed' );

			self._restRequest(
				'check-status',
				'GET',
				{ transfer_id: transferId },
				self._updateProgress,
				{
					$row: $row,
					$progressBar: $progressBar,
					$progressText: $progressText,
					$progressBarFill: $progressBarFill,
					$progressStatusText: $progressStatusText,
					$timeElapsedText: $timeElapsedText,
				}
			);
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