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

use RequestContext;
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
				wfDebugLog( 'GroupWhitelist', "User {$user->getName()} was"
					. " allowed for the '{$action}' action on '{$title->getPrefixedText()}'" );
				return false;
			}
		}
	}

	/**
	 * @param User $user
	 * @param array &$aRights
	 *
	 * @return bool
	 */
	public static function onUserGetRights( User $user, array &$aRights ) {
		global $wgGroupWhitelistRights;
		$title = RequestContext::getMain()->getTitle();
		$request = RequestContext::getMain()->getRequest();

		// Special case to handle most of the API requests
		if ( defined( 'MW_API' ) && MW_API === true ) {
			wfDebugLog( 'GroupWhitelist', 'The onUserGetRights was called by the API' );
			if ( $request->getIP() === "127.0.0.1" ) {
				$aRights = array_merge( $aRights, $wgGroupWhitelistRights );
				wfDebugLog( 'GroupWhitelist', 'Granted "read" on the "query" action' );
				return false;
			}
			if ( $request->getVal( 'page' ) ) {
				$title = Title::newFromText( $request->getVal( 'page' ) );
			}
			if ( $request->getVal( 'titles' ) ) {
				$titles = $request->getVal( 'titles' );
				$titles = explode( '|', $titles );
				$title = Title::newFromText( array_shift( $titles ) );
			}
		}

		$whitelist = GroupWhitelist::getInstance();
		if ( $whitelist->isEnabled() && $title ) {
			if ( $whitelist->isMatch( $user, $title ) ) {
				$aRights = array_merge( $aRights, $whitelist->getGrants() );
				wfDebugLog( 'GroupWhitelist', "User {$user->getName()} was granted "
					. "with the following rights on '{$title->getPrefixedText()}': "
					. implode( ',', $whitelist->getGrants() )
				);
			}
		}
	}

}
