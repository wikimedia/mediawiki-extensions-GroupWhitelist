GroupWhitelist
--------------

The extension allows to grant users from selected group with a special per-page rights
specifying affected pages list on a regular wiki page.


* `wgGroupWhitelistRights` - A list of actions to be allowed
* `wgGroupWhitelistGroup` - A group affected by the extension
* `wgGroupWhitelistSourcePage` - A page to look for list of whitelisted pages

Example config:

```
$wgGroupWhitelistRights = ['edit'];
$wgGroupWhitelistGroup = 'user';
$wgGroupWhitelistSourcePage = 'Mediawiki:Whitelist';
```

And the `Project:PageList` contents:

```
* SomePage1
// Comments are allowed
* SomePage2
* SomaPage3
```

The settings above allow users from a `user` group to `edit` pages
specified in the `Mediawiki:Whitelist` page contents (`SomePage1`, `SomePage2`, `SomePage3`).

Note: when Visual Editor is enabled on the wiki it's necessary to enable cookies forwarding:

```
$wgVirtualRestConfig['modules']['parsoid']['forwardCookies'] = true;
```
