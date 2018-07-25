<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 *
 * Renders a single Sensei course based on the given ID. The rendered result is
 * meant to be displayed on the frontend, and may be used by shortcodes or
 * other rendering code.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Renderer_Single_Course {

	/**
	 * @var WP_Query $course_page_query The query for the Course post.
	 */
	protected $course_page_query;

	/**
	 * @var WP_Post $global_post_ref Backup of the global $post variable.
	 */
	protected $global_post_ref;

	/**
	 * @var WP_Query $global_wp_query_ref Backup of the global $wp_query variable.
	 */
	protected $global_wp_query_ref;

	/**
	 * @var array $global_pages_ref Backup of the global $pages variable.
	 */
	protected $global_pages_ref;

	/**
	 * Setup the renderer object
	 *
	 * @since 1.12.0
	 * @param array  $attributes {
	 *   @type int  id              The course ID.
	 *   @type bool show_pagination Whether to show pagination on the course page.
	 * }
	 */
	public function __construct( $attributes ) {
		$this->id = isset( $attributes['id'] ) ? $attributes['id'] : '';
		$this->show_pagination = isset( $attributes['show_pagination'] ) ? $attributes['show_pagination'] : false;
		$this->setup_course_query();
	}

	/**
	 * Render and return the content. This will use the 'single-course.php'
	 * template, and will use an overridden version if it exists.
	 *
	 * @return string The rendered output.
	 */
	public function render() {
		if( empty( $this->id ) ){
			throw new Sensei_Renderer_Missing_Fields_Exception( array( 'id' ) );
		}

		// Set the wp_query to the current courses query.
		global $wp_query, $post, $pages;

		$this->backup_global_vars();
		$this->set_global_vars();

		// Capture output.
		ob_start();
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_action( 'sensei_single_course_lessons_before', array( $this, 'set_global_vars' ), 1, 0 );
		Sensei_Templates::get_template( 'single-course.php' );
		if ( $this->show_pagination ) {
			do_action( 'sensei_pagination' );
		}
		$output = ob_get_clean();

		$this->reset_global_vars();

		return $output;
	}

	/**
	 * Create the courses query.
	 */
	private function setup_course_query(){
		if ( empty( $this->id ) ) {
			return;
		}

		$args = array(
			'post_type'      => 'course',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'post__in'       => array( $this->id ),
		);

		$this->course_page_query = new WP_Query( $args );
	}

	/**
	 * Backup the globals that we will be modifying. Set them back with
	 * `reset_global_vars`.
	 */
	private function backup_global_vars() {
		global $wp_query, $post, $pages;

		$this->global_post_ref     = clone $post;
		$this->global_wp_query_ref = clone $wp_query;
		$this->global_pages_ref    = $pages;

	}

	/**
	 * Set global variables to the currently requested course. This is used
	 * internally and should not be called from external code.
	 *
	 * @access private
	 */
	public function set_global_vars() {
		$post           = get_post( $this->id );
		$pages          = array( $post->post_content );
		$wp_query       = $this->course_page_query;
		$wp_query->post = get_post( $this->id );
	}

	/**
	 * Reset global variables to what they were before calling
	 * `backup_global_vars`.
	 */
	private function reset_global_vars() {
		global $wp_query, $post, $pages;

		$wp_query       = $this->global_wp_query_ref;
		$post           = $this->global_post_ref;
		$wp_query->post = $this->global_post_ref;
		$pages          = $this->global_pages_ref;
	}
}