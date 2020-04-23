<?php
/**
 * File: boldgrid-backup-admin-dashboard.php
 *
 * This file is used to markup dashboard page.
 *
 * @link https://www.boldgrid.com
 * @since 1.11.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

$nav = include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-nav.php';

$dashboard = new \Boldgrid\Library\Library\Ui\Dashboard();

$dashboard->cards = $this->get_cards();

echo $nav; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

require BOLDGRID_BACKUP_PATH . '/admin/partials/archives/add-new.php';

$dashboard->printCards();
