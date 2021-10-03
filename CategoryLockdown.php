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
	public static function onUserCan( $title, $user, $action, &$result ) {
		global $wgCategoryLockdown;

		$groups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );

		// Rules don't apply to admins
		if ( in_array( 'sysop', $groups ) ) {
			return;
		}

		$categories = array_keys( $title->getParentCategories() );
		if ( $title->getNamespace() === NS_CATEGORY ) {
			$categories[] = $title->getFullText(); // Rules apply to the category itself
		}
		foreach ( $categories as $category ) {
			// Normalize for comparison, from "Category:Top_secret" to "Top secret"
			$category = substr( $category, strpos( $category, ':' ) + 1 );
			$category = str_replace( '_', ' ', $category );
			if ( !array_key_exists( $category, $wgCategoryLockdown ) ) {
				continue;
			}
			if ( !array_key_exists( $action, $wgCategoryLockdown[ $category ] ) ) {
				continue;
			}
			$allowedGroups = $wgCategoryLockdown[ $category ][ $action ];
			if ( is_string( $allowedGroups ) ) {
				$allowedGroups = [ $allowedGroups ];
			}
			foreach ( $allowedGroups as $allowedGroup ) {
				if ( in_array( $allowedGroup, $groups ) ) {
					return;
				}
			}
			return false;
		}
	}
}
