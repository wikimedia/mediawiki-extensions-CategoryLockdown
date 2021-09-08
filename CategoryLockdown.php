<?php

use MediaWiki\MediaWikiServices;

class CategoryLockdown {

	/**
	 * Main hook
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
		foreach ( $categories as $category ) {
			$category = substr( $category, strpos( $category, ':' ) + 1 );
			if ( array_key_exists( $category, $wgCategoryLockdown ) && !in_array( $wgCategoryLockdown[ $category ], $groups ) ) {
				return false;
			}
		}

		// Protect the category itself
		if ( $title->getNamespace() === NS_CATEGORY && array_key_exists( $title->getText(), $wgCategoryLockdown ) && !in_array( $wgCategoryLockdown[ $title->getText() ], $groups ) ) {
			return false;
		}
	}
}
