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

		$groups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );

		// Rules don't apply to admins
		if ( in_array( 'sysop', $groups ) ) {
			return;
		}

		$categories = array_keys( $title->getParentCategories() );

		// Apply rules to the category page itself
		if ( $title->getNamespace() === NS_CATEGORY ) {
			$categories[] = $title->getFullText();
		}

		foreach ( $categories as $category ) {
			// Normalize from "Category:Top_secret" to "Top secret" to compare
			$category = substr( $category, strpos( $category, ':' ) + 1 );
			$category = str_replace( '_', ' ', $category );
			$permissions = $wgCategoryLockdown[ $category ] ?? null;
			if ( !$permissions ) {
				$category_ = str_replace( ' ', '_', $category );
				$permissions = $wgCategoryLockdown[ $category_ ] ?? null;
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
				if ( in_array( $allowedGroup, $groups ) ) {
					return;
				}
			}
			$result = [ 'categorylockdown-error', implode( $allowedGroups, ', ' ) ];
			return false;
		}
	}
}
