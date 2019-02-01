<?php
/**
 * File: restore.php
 *
 * Emergency restoration script.  This script is used when there is a severe issue with the site
 * which requires immediate restoration from the latest backup archive.
 *
 * @link https://www.boldgrid.com
 * @since 1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/cron
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// Run the restoration.
require __DIR__ . '/class-boldgrid-backup-restore.php';
$restore = new BoldGrid_Backup_Restore();
$restore->run();
