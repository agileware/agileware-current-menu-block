<?php

/**
 * @file
 *
 * Implementation class for the Current Menu Block
 */

namespace Agileware;

class CurrentMenuBlock {
	/**
	 * @var string - Location of the plugin in the filesystem
	 */
	protected $plugin_dir;

	/**
	 * @var static CurrentMenuBlock - Singleton instance of plugin
	 */
	private static $instance;

	public function __construct( $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * Gets the singleton for the plugin, creating it if necessary
	 *
	 * @return CurrentMenuBlock
	 */
	public static function getInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static( realpath( __DIR__ ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the block server-side and adds prefix data
	 *
	 * @return void
	 */
	public function register() {
		// Load generated dependencies and version
		$asset_file = include( plugin_dir_path( $this->plugin_dir ) . '/build/current-menu-block.asset.php' );

		// Scripts for the block in the editor
		wp_register_script(
			'aw-current-menu-block',
			plugins_url( 'build/current-menu-block.js', $this->plugin_dir ),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		// Settings for the block script
		wp_localize_script(
			'aw-current-menu-block',
			'currentMenuBlockData',
			[ 'options' => $this->getMenus() ]
		);

		// Load the block via JSON metadata and set render callback
		register_block_type( plugin_dir_path( $this->plugin_dir ) . '/lib', [
			'render_callback' => [ $this, 'render' ],
			'script'          => 'aw-current-menu-block'
		] );
	}

	/**
	 * Renders the menu server-side
	 *
	 * @param $block_attributes array
	 * @param $content string
	 * @param $block WP_Block
	 *
	 * @return string
	 */
	public function render( $block_attributes, $content, $block ) {
		// Check for the current context - this is not always in the block object.
		$context = $block->context['agileware/current-menu'] ?? $_GET['context'] ?? null;

		// Exit early if no menu has been selected - advising the user in the edit context.
		if ( ! isset( $block_attributes['navigationMenuId'] ) ) {
			return ( $context === 'edit' ) ? "<p>No menu selected.</p>" : null;
		}

		// Flattened render the selected menu with our filter active
		add_action( 'wp_get_nav_menu_items', [ $this, 'filter_items' ], 10, 3 );

		$result = wp_nav_menu( [
			'menu'        => $block_attributes['navigationMenuId'],
			'container'   => null,
			'fallback_cb' => false,
			'echo'        => false,
			'menu_class'  => 'wp-block-current-menu',
			'menu_id'     => null,
			'depth'       => - 1,
		] );

		remove_action( 'wp_get_nav_menu_items', [ $this, 'filter_items' ] );

		// Advise user that the current path has no entry in the selected menu in edit context only
		if ( ! $result && $context === 'edit' ) {
			$menu = get_term( $block_attributes['navigationMenuId'], 'nav_menu' );

			return '<p>No "' . $menu->name . '" menu entry for this path.</p>';
		}

		return $result;
	}

	/* Reusable callback to find a current item using a specific query */
	private function item_search( $items, $queried, $ancestors, $current_url ) {
		$current_item = false;

		foreach ( $items as $item ) {
			// First check if the queried item is in the menu
			if (  // If we have a queried object, find a related menu item
				( ( $queried instanceof \WP_Post ) && ( $item->object_id == $queried->ID ) ) ||
				( ( $queried instanceof \WP_Term ) && ( $item->object_id == $queried->term_id ) ) ||
				// Otherwise, checked if we've exactly matched the URL.
				( $item->url == $current_url )
			) {
				$current_item = $item;
				break;
			} elseif ( ( ! $current_item ) && $ancestors ) {
				// Next, if there's a queried post, check its ancestors, nearest first.
				foreach ( $ancestors as $anc_id ) {
					if ( ( $item->object_id == $anc_id ) &&
					     ( $item->type == ( $queried instanceof \WP_Term ? 'taxonomy' : 'post_type' ) ) ) {
						$current_item = $item;
						// Don't break out of this one, as there may be an exact match later.
					}
				}
			}
		}

		return $current_item;
	}


	/**
	 * Filter the items in the selected menu according to whether they are:
	 * 1. for the currently selected DB Object,
	 * 2. a sibling of (1),
	 * 3. the parent of (1), or
	 * 4. a child of (1)
	 *
	 * @param $items
	 * @param $menu
	 * @param $args
	 *
	 * @return array
	 */
	public function filter_items( $items, $menu, $args ) {
		global $wp_query;
		global $wp;

		$queried = &$wp_query->get_queried_object();
		$url     = home_url( $wp->request );
		$terms   = null;

		// Get the post ancestors in case we need them
		if ( $queried instanceof \WP_Post ) {
			$ancestors  = get_post_ancestors( $queried );
			$taxonomies = get_object_taxonomies( $queried );
			$terms      = wp_get_object_terms( $queried->ID, $taxonomies );
		} elseif ( $queried instanceof \WP_Term ) {
			$ancestors = get_ancestors( $queried->term_id, $queried->taxonomy );
		}

		$current_item = $this->item_search( $items, $queried, $ancestors ?? null, $url );

		// Try terms of the queried post if available
		if ( ( $current_item === false ) && ! empty( $terms ) ) {
			$current_item = array_reduce(
				$terms, function ( $current_item, $term ) use ( $items, $url ) {
				return $current_item ?: $this->item_search( $items, $term, null, $url );
			},
				$current_item );
		}

		// Didn't find anything, exit.
		if ( $current_item === false ) {
			return [];
		}

		$current_parent_id = (int) get_post_meta( $current_item->db_id, '_menu_item_menu_item_parent', true );

		// Filter menu items based on relationship to current menu item
		$items = array_filter( $items, function ( $item ) use ( $current_item, $current_parent_id ) {
			$item_parent_id = (int) get_post_meta( $item->db_id, '_menu_item_menu_item_parent', true );

			return
				( $current_item->db_id == $item->db_id ) || // Current item
				( ( $item_parent_id !== 0 ) && ( $current_parent_id === $item_parent_id ) ) || // Sibling of current item, excluding top level
				( $current_item->db_id === $item_parent_id ) || // Child of current item
				( $item->db_id === $current_parent_id ); // Parent of current item
		} );


		return $items;
	}

	public function getMenus() {
		$menus = wp_get_nav_menus();
		$menus = apply_filters( 'current_menu_block_locations', $menus );

		return $menus;
	}

}