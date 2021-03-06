<?php
/**
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * MWLogger service provider that creates MWLoggerLegacyLogger instances.
 *
 * Usage:
 * @code
 * $wgMWLoggerDefaultSpi = array(
 *   'class' => 'MWLoggerLegacySpi',
 * );
 * @endcode
 *
 * @see MWLogger
 * @since 1.25
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2014 Bryan Davis and Wikimedia Foundation.
 */
class MWLoggerLegacySpi implements MWLoggerSpi {

	/**
	 * @var array $singletons
	 */
	protected $singletons = array();


	/**
	 * Get a logger instance.
	 *
	 * @param string $channel Logging channel
	 * @return MWLogger Logger instance
	 */
	public function getLogger( $channel ) {
		if ( !isset( $this->singletons[$channel] ) ) {
			$this->singletons[$channel] = new MWLoggerLegacyLogger( $channel );
		}
		return $this->singletons[$channel];
	}

}
