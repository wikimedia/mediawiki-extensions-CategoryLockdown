<?php

use MediaWiki\MediaWikiServices;

class CategoryLockdown {

	/**
	 * Main hook
	 *
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param string &$result
	 * @return false|void
	 */
	public static function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		global $wgCategoryLockdown;

		$explicitGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		$implicitGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserImplicitGroups( $user );
		$userGroups = $explicitGroups + $implicitGroups;

		// Rules don't apply to admins
		if ( in_array( 'sysop', $userGroups ) ) {
			return;
		}

		$categories = array_keys( $title->getParentCategories() );

		// Apply rules to the category page itself
		if ( $title->getNamespace() === NS_CATEGORY ) {
			$categories[] = $title->getFullText();
		}

		$combinedGroups = [];
		foreach ( $categories as $category ) {
			// Support "Category:Top_secret", "Category:Top secret", "Top_secret" and "Top secret"
			$category = substr( $category, strpos( $category, ':' ) + 1 );
			$category = str_replace( '_', ' ', $category );
			$permissions = $wgCategoryLockdown[ $category ] ?? null;
			if ( !$permissions ) {
				$category = str_replace( ' ', '_', $category );
				$permissions = $wgCategoryLockdown[ $category ] ?? null;
			}
			if ( !$permissions ) {
				continue;
			}
			$allowedGroups = $permissions[ $action ] ?? null;
			if ( !$allowedGroups ) {
				continue;
			}
			if ( is_string( $allowedGroups ) ) {
				$allowedGroups = [ $allowedGroups ];
			}
			foreach ( $allowedGroups as $allowedGroup ) {
				$combinedGroups[] = $allowedGroup;
			}
		}
		if ( $combinedGroups ) {
			foreach ( $userGroups as $userGroup ) {
				if ( in_array( $userGroup, $combinedGroups ) ) {
					return true;
				}
			}
			$result = [ 'categorylockdown-error', implode( ', ', $combinedGroups ) ];
			return false;
		}
	}
}
