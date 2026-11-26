<?php
/**
 * File: test-class-boldgrid-backup-admin-ftp.php
 *
 * @link https://www.boldgrid.com
 * @since 1.17.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Ftp
 *
 * @since 1.17.5
 */
class Test_Boldgrid_Backup_Admin_Ftp extends WP_UnitTestCase {

	/**
	 * An unresolvable host must not raise a PHP warning, and a second connect must not try again.
	 *
	 * PHPUnit converts warnings to exceptions, so an unsilenced ftp_ssl_connect() failure fails this test.
	 *
	 * @since 1.17.5
	 *
	 * @dataProvider provider_unreachable_types
	 *
	 * @param string $type ftp or ftpes.
	 */
	public function test_unreachable_host_does_not_warn( $type ) {
		if ( 'ftpes' === $type && ! function_exists( 'ftp_ssl_connect' ) ) {
			$this->markTestSkipped( 'FTP SSL extension is not available.' );
		}

		if ( 'ftp' === $type && ! function_exists( 'ftp_connect' ) ) {
			$this->markTestSkipped( 'FTP extension is not available.' );
		}

		$settings = $this->getMockBuilder( 'Boldgrid_Backup_Admin_Settings' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_settings' ) )
			->getMock();

		$settings->method( 'get_settings' )->willReturn(
			array(
				'remote' => array(
					'ftp' => array(
						'host' => 'ftp.invalid',
						'user' => 'user',
						'pass' => 'pass',
						'port' => 21,
						'type' => $type,
					),
				),
			)
		);

		$core           = new stdClass();
		$core->settings = $settings;

		$ftp          = new Boldgrid_Backup_Admin_Ftp( $core );
		$ftp->timeout = 1;

		$warnings = array();
		set_error_handler(
			static function( $errno, $errstr ) use ( &$warnings ) {
				$warnings[] = $errstr;
				return true;
			}
		);

		$contents = $ftp->get_contents( true, 'boldgrid-backup' );
		$ftp->connect();

		restore_error_handler();

		$this->assertSame( array(), $warnings );
		$this->assertSame( array(), $contents );
		$this->assertCount( 1, $ftp->errors );
		$this->assertNotFalse( strpos( $ftp->errors[0], 'ftp.invalid' ) );
	}

	/**
	 * Saving FTP settings with an unresolvable host must fail without a PHP warning.
	 *
	 * @since 1.17.5
	 *
	 * @dataProvider provider_unreachable_types
	 *
	 * @param string $type ftp or ftpes.
	 */
	public function test_is_valid_credentials_unreachable_host_does_not_warn( $type ) {
		$ftp          = new Boldgrid_Backup_Admin_Ftp( new stdClass() );
		$ftp->timeout = 1;

		$warnings = array();
		set_error_handler(
			static function( $errno, $errstr ) use ( &$warnings ) {
				$warnings[] = $errstr;
				return true;
			}
		);

		$valid = $ftp->is_valid_credentials( 'ftp.invalid', 'user', 'pass', 21, $type, 'auto' );

		restore_error_handler();

		$this->assertFalse( $valid );
		$this->assertSame( array(), $warnings );
		$this->assertCount( 1, $ftp->errors );
	}

	/**
	 * An IPv6-only system name is accepted when gethostbynamel() has no address.
	 *
	 * ip6-allrouters is the Ubuntu hosts-file name for ff02::2. gethostbynamel()
	 * is IPv4-only, so this fails closed unless the check uses getaddrinfo().
	 *
	 * @since 1.17.5
	 */
	public function test_ipv6_system_name_resolves() {
		if ( ! function_exists( 'socket_addrinfo_lookup' ) || ! defined( 'SOCK_STREAM' ) ) {
			$this->markTestSkipped( 'sockets extension is not available.' );
		}

		$records = socket_addrinfo_lookup(
			'ip6-allrouters',
			null,
			array(
				'ai_socktype' => SOCK_STREAM,
			)
		);
		if ( empty( $records ) || ! empty( gethostbynamel( 'ip6-allrouters' ) ) ) {
			$this->markTestSkipped( 'ip6-allrouters is not an IPv6-only system name here.' );
		}

		$ftp    = new Boldgrid_Backup_Admin_Ftp( new stdClass() );
		$method = new ReflectionMethod( Boldgrid_Backup_Admin_Ftp::class, 'host_resolves' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		$warnings = array();
		set_error_handler(
			static function( $errno, $errstr ) use ( &$warnings ) {
				$warnings[] = $errstr;
				return true;
			}
		);

		$resolves = $method->invoke( $ftp, 'ip6-allrouters' );

		restore_error_handler();

		$this->assertTrue( $resolves );
		$this->assertSame( array(), $warnings );
	}

	/**
	 * Connection types that open a socket in connect().
	 *
	 * @since 1.17.5
	 *
	 * @return array
	 */
	public function provider_unreachable_types() {
		return array(
			'ftp'   => array( 'ftp' ),
			'ftpes' => array( 'ftpes' ),
		);
	}
}
