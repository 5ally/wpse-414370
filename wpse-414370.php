<?php
/**
Plugin Name: WPSE 414370
Description: See <a href="https://wordpress.stackexchange.com/q/414370/137402">https://wordpress.stackexchange.com/q/414370</a> for more details.
Version: 20230306.1
 */

namespace WPSE_414370;

add_action( 'admin_menu', __NAMESPACE__ . '\\add_admin_menus' );
/**
 * Add custom admin menus.
 */
function add_admin_menus() {
	add_menu_page(
		'WPSE 414370',
		'WPSE 414370',
		'manage_options',
		'wpse-414370',
		__NAMESPACE__ . '\\admin_menu_page'
	);
}

/**
 * Run sample posts query.
 *
 * @param int    $paged         Current page number. Default 1.
 * @param string $category_name Category slug. Default videos.
 * @param string $paged_query   URL parameter for the page number. Default paged.
 * @param string $add_fragment  @see paginate_links().
 * @param string $mode          no_cache to not cache the results, no_found_rows to use a
 *                              `SELECT COUNT(*)` query to get the *total number of* posts.
 */
function sample_test_case(
	$paged = 1,
	$category_name = 'videos',
	$paged_query = 'paged',
	$add_fragment = '',
	$mode = ''
) {
	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 2,
		'paged'          => $paged,
		'category_name'  => $category_name,
		'cache_results'  => ( 'no_cache' !== $mode ),
		'_found_posts'   => ( 'no_found_rows' === $mode ),
		'no_found_rows'  => ( 'no_found_rows' === $mode ),
	);

	$my_query = new \WP_Query( $args );

	// Manually calculate the total number of posts.
	maybe_set_found_posts( $my_query );

	echo '<ul>';
	echo '<li>SQL command: <code>' . esc_html( $my_query->request ) . '</code></li>';
	echo '<li>Found posts (total in the DB): <b>' . esc_html( $my_query->found_posts ) . '</b></li>';
	echo '<li>Max. num pages: <b>' . esc_html( $my_query->max_num_pages ) . '</b></li>';
	echo '</ul>';

	if ( $my_query->have_posts() ) {
		echo '<h3>List of Posts</h3>';

		echo '<ol>';
		foreach ( $my_query->posts as $post ) {
			echo '<li>' . esc_html( $post->post_title ) . '</li>';
		}
		echo '</ol>';

		$paged_query = $paged_query ? $paged_query : 'paged';
		$links_args  = array(
			'total'        => $my_query->max_num_pages,
			'current'      => $paged,

			// Let's use a different query var for each test case.
			'format'       => "&$paged_query=%#%",

			'base'         => menu_page_url( 'wpse-414370', false ) . '%_%',
			'add_fragment' => $add_fragment,
		);

		$links = paginate_links( $links_args );
		if ( $links ) {
			printf(
				'<p><i>Pagination (URL parameter used is <code>%s</code>): %s</i></p>',
				esc_html( $paged_query ),
				$links
			);
		}
	}
}

/**
 * Render admin page.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @global string $wp_version
 */
function admin_menu_page() {
	global $wpdb, $wp_version;

	$db_version = $wpdb->get_var( 'SELECT version()' );
	$mu_plugins = array_map( 'wp_basename', wp_get_mu_plugins() );

	$cat    = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : 'videos';
	$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;
	$paged2 = isset( $_GET['paged2'] ) ? (int) $_GET['paged2'] : 1;
	$paged3 = isset( $_GET['paged3'] ) ? (int) $_GET['paged3'] : 1;
	$paged4 = isset( $_GET['paged4'] ) ? (int) $_GET['paged4'] : 1;

	$req_uri = isset( $_SERVER['REQUEST_URI'] ) ?
		sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	?>
		<div class="wrap">
			<p id="go-to">
				Go to...
				<a href="#versions">Versions</a>
				| <a href="#select-cat">Select a Category</a>
				| <a href="#test-case-1">Test Case 1</a>
				| <a href="#test-case-2">Test Case 2</a>
				| <a href="#test-case-3">Test Case 3</a>
				| <a href="#test-case-4">Test Case 4</a>
				| <a href="#instructions">Instructions</a>
			</p>

			<h2 id="versions">Versions</h2>
			<ul>
				<li>WordPress: <?php echo esc_html( $wp_version ); ?></li>
				<li>Database: <?php echo esc_html( $db_version ); ?></li>
				<li>PHP: <?php echo esc_html( PHP_VERSION ); ?></li>
			</ul>
			<hr />

			<h2 id="select-cat">Select a Category</h2>
			<p>(For use with test cases 1, 3 and 4)</p>
			<?php
			$list = array();

			foreach ( get_categories() as $term ) {
				if ( $cat === $term->slug ) {
					$list[] = sprintf( '<b>%s</b>', esc_html( $term->name ) );
				} else {
					$list[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( add_query_arg( 'cat', $term->slug ) ),
						esc_html( $term->name )
					);
				}
			}

			echo implode( ', ', $list );
			?>
			<hr />

			<h2 id="test-case-1">Test Case 1</h2>
			<p>This test queries for posts in the <b><?php echo esc_html( $cat ); ?>
				category</b>, and displays 2 posts per page.</p>
			<?php sample_test_case( $paged, $cat, 'paged', '#test-case-1' ); ?>
			<hr />

			<h2 id="test-case-2">Test Case 2</h2>
			<p>This test queries for posts in the <b>ANY categories</b>, and displays 2
				posts per page.</p>
			<?php sample_test_case( $paged2, '', 'paged2', '#test-case-2' ); ?>
			<hr />

			<h2 id="test-case-3">Test Case 3</h2>
			<p>Same as the first test case, but this one is <b>without caching</b>.</p>
			<?php sample_test_case( $paged3, $cat, 'paged3', '#test-case-3', 'no_cache' ); ?>
			<hr />

			<h2 id="test-case-4">Test Case 4</h2>
			<p>Same as the first test case, but this one is <b>using a <code>SELECT
				COUNT(*)</code> query</b> to get the total number of posts. I.e. Not
				using <code>SQL_CALC_FOUND_ROWS</code> and <code>FOUND_ROWS()</code>.</p>
			<?php sample_test_case( $paged4, $cat, 'paged4', '#test-case-4', 'no_found_rows' ); ?>
			<hr />

			<h2 id="instructions">End of tests</h2>
			<p>Now, please capture a screenshot of this page (the entire page, including
				scrolling area) and upload it somewhere, then share it on <a href="https://wordpress.stackexchange.com/q/414370/137402">WPSE</a>
				(post a link to the screenshot).</p>

			<h2>Additional Debugging Information</h2>
			<ul>
				<li>Current theme: <?php echo esc_html( wp_get_theme()->Name ); ?></li>
				<li>
					<a href="https://wordpress.org/documentation/article/must-use-plugins/" target="_blank">Must-use plugins</a>:
					<?php echo count( $mu_plugins ) ? '<code>' . implode( '</code>, <code>', $mu_plugins ) . '</code>' : 'None'; ?>
				</li>
				<li>Current page path: <code><?php echo esc_html( $req_uri ); ?></code></li>
			</ul>
		</div>
	<?php
}

add_filter( 'posts_clauses', __NAMESPACE__ . '\\posts_clauses', 20, 2 );
/**
 * Filter the posts query clauses and set a custom argument named _found_posts_query,
 * to be used when manually calculating the max_num_pages value.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array    $clauses Associative array of clauses for the query.
 * @param WP_Query $query   The WP_Query instance.
 * @return array $clauses
 */
function posts_clauses( $clauses, $query ) {
	if ( $query->get( '_found_posts' ) ) {
		global $wpdb;

		$orderby = $clauses['orderby'] ? "ORDER BY {$clauses['orderby']}" : '';

		$found_posts_query = "
			SELECT COUNT( $wpdb->posts.ID )
			FROM $wpdb->posts {$clauses['join']}
			WHERE 1=1 {$clauses['where']}
			$orderby
		";

		$query->set( '_found_posts_query', $found_posts_query );
	}

	return $clauses;
}

/**
 * Manually calculate found_posts and max_num_pages.
 *
 * Hint: If all else fails, you can use this function in your own code. However,
 * it would need to be used together with the above custom filter.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param WP_Query $query The WP_Query instance.
 */
function maybe_set_found_posts( \WP_Query $query ) {
	$found_posts_query = $query->get( '_found_posts_query' );

	if ( $query->get( 'no_found_rows' ) && $found_posts_query ) {
		global $wpdb;

		$per_page = max( 1, $query->get( 'posts_per_page' ) );

		$query->found_posts   = (int) $wpdb->get_var( $found_posts_query );
		$query->max_num_pages = ceil( $query->found_posts / $per_page );
	}
}
