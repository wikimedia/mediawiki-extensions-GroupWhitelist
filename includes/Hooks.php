<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\GroupWhitelist;

use Title;
use User;

class Hooks {

	/**
	 * @param Title &$title
	 * @param User &$user
	 * @param string $action
	 * @param bool &$result
	 *
	 * @return bool
	 */
	public static function onUserCan( &$title, &$user, $action, &$result ) {
		$whitelist = GroupWhitelist::getInstance();
		if ( $whitelist->isEnabled() ) {
			if ( $whitelist->isMatch( $user, $title, $action ) ) {
				$result = true;
				return false;
			}
		}
	}

	/**
	 * @param User $user
	 * @param array &$aRights
	 */
	public static function onUserGetRights( User $user, array &$aRights ) {
		$whitelist = GroupWhitelist::getInstance();
		if ( $whitelist->isEnabled() && \RequestContext::getMain()->getTitle() ) {
			if ( $whitelist->isMatch( $user, \RequestContext::getMain()->getTitle() ) ) {
				$aRights = array_merge( $aRights, $whitelist->getGrants() );
			}
		}
	}

}
