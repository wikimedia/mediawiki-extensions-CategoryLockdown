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

		// Admins can view all
		if ( in_array( 'sysop', $groups ) ) {
			return;
		}

		// If the page is in a protected category and the user is not in the allowed group, hide the page
		$categories = array_keys( $title->getParentCategories() );
		if ( $title->getNamespace() === NS_CATEGORY ) {
			$categories[] = $title->getFullText(); // Protect the category itself
		}
		foreach ( $categories as $category ) {
			$category = substr( $category, strpos( $category, ':' ) + 1 );
			if ( array_key_exists( $category, $wgCategoryLockdown ) ) {
				$allowedGroups = $wgCategoryLockdown[ $category ];
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
}
