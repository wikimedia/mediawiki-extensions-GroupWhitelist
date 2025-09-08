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

use MediaWiki\Title\Title;
use RequestContext;
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
	public static function ongetUserPermissionsErrors( &$title, &$user, $action, &$result ) {
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
	 * @return bool|void
	 */
	public static function onUserGetRights( User $user, array &$aRights ) {
		global $wgGroupWhitelistRights, $wgGroupWhitelistAPIAllow;
		$titles = [ RequestContext::getMain()->getTitle() ];
		$request = RequestContext::getMain()->getRequest();

		// Special case to handle most of the API requests
		if ( defined( 'MW_API' ) && MW_API === true ) {
			wfDebugLog( 'GroupWhitelist', 'The onUserGetRights was called by the API' );
			$apiModule = $request->getVal( 'action' );
			if ( $request->getIP() === "127.0.0.1" || in_array( $apiModule, $wgGroupWhitelistAPIAllow ) ) {
				$aRights = array_merge( $aRights, $wgGroupWhitelistRights );
				wfDebugLog( 'GroupWhitelist', "Granted rights for api action=$apiModule" );
				return false;
			}
			// Add some specific API workarounds
			$page = $request->getVal( 'page' );
			if (
				$page !== null &&
				( $apiModule === 'visualeditor' || $apiModule === 'visualeditoredit' )
			) {
				$titles = [ Title::newFromText( $page ) ];
			}
			$titlesParam = $request->getVal( 'titles' );
			if (
				$titlesParam !== null &&
				$apiModule === 'query' &&
				strpos( $titlesParam, "\x1F" ) === false &&
				$request->getVal( 'generator' ) === null &&
				$request->getVal( 'pageids' ) === null &&
				$request->getVal( 'revids' ) === null &&
				$request->getVal( 'list' ) === null
			) {
				$titlesStrs = explode( '|', $titlesParam );
				$titles = [];
				foreach ( $titlesStrs as $titleStr ) {
					$potentialTitle = Title::newFromText( $titleStr );
					if ( $potentialTitle ) {
						$titles[] = $potentialTitle;
					}
				}
			}
		}

		$whitelist = GroupWhitelist::getInstance();
		if ( $whitelist->isEnabled() && $titles ) {
			foreach ( $titles as $specificTitle ) {
				// @phan-suppress-next-line PhanImpossibleConditionInLoop
				if ( !$specificTitle || !$whitelist->isMatch( $user, $specificTitle ) ) {
					return;
				}
			}
			$aRights = array_merge( $aRights, $whitelist->getGrants() );
			wfDebugLog( 'GroupWhitelist', "User {$user->getName()} was granted "
				. "with the following rights: "
				. implode( ',', $whitelist->getGrants() )
			);
		}
	}

}
