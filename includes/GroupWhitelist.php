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

use Config;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use ObjectCache;
use User;

class GroupWhitelist {

	/** @var GroupWhitelist|null */
	private static $instance;
	/** @var Config */
	private $config;
	/** @var string[] */
	private $whitelistedIds;

	/**
	 * @return GroupWhitelist
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Resets the saved instance, mostly to be used in tests
	 */
	public static function resetInstance() {
		if ( self::$instance ) {
			self::$instance = null;
		}
	}

	/**
	 * GroupWhitelist constructor.
	 */
	public function __construct() {
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'GroupWhitelist' );
		$this->whitelistedIds = $this->getWhitelist();
	}

	/**
	 * @param Title $title
	 *
	 * @return int|string
	 */
	public function getArticleID( $title ) {
		if ( $title->getNamespace() === NS_SPECIAL ) {
			return $title->getFullText();
		}
		if ( !$title->exists() && $title->canExist() ) {
			return $title->getFullText();
		}
		return $title->getArticleID();
	}

	/**
	 * @return (string|int)[]
	 */
	private function parseWhitelist() {
		$whitelistedIds = [];
		if ( $this->isEnabled() ) {
			$targetTitle = Title::newFromText( $this->config->get( 'GroupWhitelistSourcePage' ) );
			if ( $targetTitle->exists() ) {
				$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $targetTitle );
				$text = $page->getContent()->getWikitextForTransclusion();
				$entries = $this->parseEntries( $text );
				foreach ( $entries as $entry ) {
					$t = Title::newFromText( $entry );
					if ( $t ) {
						$whitelistedIds[] = $this->getArticleID( $t );
					}
				}
			}
		}
		return $whitelistedIds;
	}

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	private function parseEntries( $text ) {
		$entries = [];
		$matches = [];
		if ( preg_match_all( '/\*\s?([^\n]+)/', $text, $matches ) ) {
			foreach ( $matches[1] as $match ) {
				$entries[] = trim( $match );
			}
		}
		return $entries;
	}

	/**
	 * @return string[]
	 */
	private function getWhitelist() {
		$cache = ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'groupwhitelist', 'whitelistids' );
		$key_touched = $cache->makeKey( 'groupwhitelist', 'page_touched' );
		$targetTitle = Title::newFromText( $this->config->get( 'GroupWhitelistSourcePage' ) );

		$result = $cache->get( $key );
		$touched = $cache->get( $key_touched );

		if ( !$result || !$touched || $touched !== $targetTitle->getTouched() ) {
			// If we have no touched stamp stored or empty page contents in cache - invalidate
			$result = implode( ',', $this->parseWhitelist() );
			$cache->set( $key, $result );
			$cache->set( $key_touched, $targetTitle->getTouched() );
		}
		return explode( ',', $result );
	}

	/**
	 * Is properly configured and allowed to run
	 *
	 * @return bool
	 */
	public function isEnabled() {
		if (
			!count( $this->config->get( 'GroupWhitelistRights' ) ) ||
			!$this->config->get( 'GroupWhitelistGroup' ) ||
			!$this->config->get( 'GroupWhitelistSourcePage' )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Check if user and title are subjects for the override
	 *
	 * @param User $user
	 * @param Title $title
	 * @param string|null $action
	 *
	 * @return bool
	 */
	public function isMatch( $user, $title, $action = null ) {
		// Check if user has the target group
		$effectiveGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserEffectiveGroups( $user );
		if ( !in_array( $this->config->get( 'GroupWhitelistGroup' ), $effectiveGroups ) ) {
			return false;
		}

		// Check if target page is whitelisted
		if ( !in_array( $this->getArticleID( $title ), $this->whitelistedIds ) ) {
			return false;
		}
		// Check if target action needs to be overridden
		if ( $action && !in_array( $action, $this->config->get( 'GroupWhitelistRights' ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @return mixed
	 */
	public function getGrants() {
		return $this->config->get( 'GroupWhitelistRights' );
	}

}
