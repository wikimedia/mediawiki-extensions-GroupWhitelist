<?php

namespace MediaWiki\Extension\GroupWhitelist;

use MediaWikiIntegrationTestCase;
use Title;

/**
 * Class GroupWhitelistTest
 * @coverdefaultclass GroupWhitelist
 * @group Database
 */
class GroupWhitelistTest extends MediaWikiIntegrationTestCase {

	/** @var GroupWhitelist */
	private $gw;

	public function setUp(): void {
		$this->getExistingTestPage( Title::newFromText( 'TestWhitelist', NS_MEDIAWIKI ) );
		$this->editPage( 'Mediawiki:TestWhitelist', "* TestWhitelistPage" );
		$this->editPage( 'TestWhitelistPage', "GroupWhitelist!" );
		$this->overrideConfigValues( [
			'GroupWhitelistRights' => [ 'edit' ],
			'GroupWhitelistGroup' => 'user',
			'GroupWhitelistSourcePage' => 'Mediawiki:TestWhitelist',
		] );
		$this->overrideMwServices();
		GroupWhitelist::resetInstance();
		$this->gw = GroupWhitelist::getInstance();
	}

	/**
	 * @covers \MediaWiki\Extension\GroupWhitelist\GroupWhitelist::getGrants
	 */
	public function testGetGrants() {
		$grants = $this->gw->getGrants();
		$this->assertArrayEquals(
			[ 'edit' ],
			$grants
		);
	}

	/**
	 * @covers \MediaWiki\Extension\GroupWhitelist\GroupWhitelist::isMatch
	 *
	 * This actually also tests parseWhitelist internally
	 */
	public function testIsMatch() {
		$user = $this->getMutableTestUser()->getUser();
		$title = $this->getExistingTestPage( 'TestWhitelistPage' )->getTitle();
		$result = $this->gw->isMatch( $user, $title, 'edit' );
		$this->assertTrue(

			$result
		);
	}

	/**
	 * @covers \MediaWiki\Extension\GroupWhitelist\GroupWhitelist::isEnabled
	 */
	public function testIsEnabled() {
		$this->overrideConfigValues( [
			'GroupWhitelistRights' => [ 'edit' ],
			'GroupWhitelistGroup' => 'user',
			'GroupWhitelistSourcePage' => 'Mediawiki:TestWhitelist',
		] );
		$this->overrideMwServices();
		GroupWhitelist::resetInstance();
		$this->assertTrue( $this->gw->isEnabled() );

		$this->overrideConfigValues( [
			'GroupWhitelistRights' => [],
			'GroupWhitelistGroup' => 'user',
			'GroupWhitelistSourcePage' => 'Mediawiki:TestWhitelist',
		] );
		$this->overrideMwServices();
		GroupWhitelist::resetInstance();
		$this->assertFalse( $this->gw->isEnabled() );

		$this->overrideConfigValues( [
			'GroupWhitelistRights' => [],
			'GroupWhitelistGroup' => '',
			'GroupWhitelistSourcePage' => 'Mediawiki:TestWhitelist',
		] );
		$this->overrideMwServices();
		GroupWhitelist::resetInstance();
		$this->assertFalse( $this->gw->isEnabled() );

		$this->overrideConfigValues( [
			'GroupWhitelistRights' => [],
			'GroupWhitelistGroup' => 'user',
			'GroupWhitelistSourcePage' => '',
		] );
		$this->overrideMwServices();
		GroupWhitelist::resetInstance();
		$this->assertFalse( $this->gw->isEnabled() );
	}

}
