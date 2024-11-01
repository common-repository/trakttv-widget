<?php
/**
 * Plugin Name: Trakt.tv Widget
 * Description: Displays information about television shows using the trakt.tv API.
 * Version: 1.0
 * Author: Carolyn Sonnek, Kathryn Presner, Brad Angelcyk, Justin Shreve
 * Author URI: http://automattic.com
 * License: GPL2
 */

add_action( 'widgets_init', 'television_widget' );
function television_widget() {
     register_widget( 'television_widget' );
}

/**
* Television Widget
* Wraps up our trakt.tv widget and allows users to display basic TV info and top eps.
*
*/
class Television_Widget extends WP_Widget {

	/**
	* @version 1.0
	*/
	public function __construct() {
		parent::__construct(
			'television_widget', // internal id
			__( 'Television Shows', 'television' ), // wp-admin title
			array(
				'description' => __( 'Display information about television shows.', 'television' ), // description
			)
		);
	}

	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );

		wp_enqueue_style( 'television', plugins_url( 'tv-widget/style.css' ) );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

		$api_url = "http://api.trakt.tv/show/summary.json/" . $instance['api_key'] . "/" . $instance['slug'];

		$data_from_cache = get_transient( 'tv-show-' . $instance['slug'], 'tv-shows' );
		if ( false === $data_from_cache ) {
			$response = file_get_contents( $api_url );
			set_transient( 'tv-show-' . $instance['slug'], $response, 'tv-shows', ( 10 * MINUTE_IN_SECONDS ) );
		} else {
			$response = $data_from_cache;
		}

		$summary = json_decode( $response );

		if ( empty( $summary->title ) ) {
			echo "We can not pull episode information at this time.";
			echo $args['after_widget'];
			return;
		}

		echo "<div class='television'>";
		echo "<img src='" . esc_url( $summary->poster ) . "' class='poster' />";

		echo "<div class='episodes'>";
		echo "<strong>" . __( 'Top Episodes', 'television' ) . "</strong>";
		echo "<ul>";
		foreach ( $summary->top_episodes as $episode ) {
			echo "<li><a href='" . esc_url( $episode->url ) . "'>". esc_html( $episode->title ) . "</a></li>";
		}
		echo "</ul>";
		echo "</div>";

		echo "<div class='description'>";
		echo wpautop( esc_html( $summary->overview ) );
		echo "</div>";
		echo "</div>";

		echo $args['after_widget'];
	}

	/*
	the form() function for Widgets tells us what to display when creating or managing a widget in
	wp-admin
	notice the use of HTML forms
	*/
 	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'Television', 'television' );
		}

		if ( isset( $instance[ 'slug' ] ) ) {
			$slug = $instance[ 'slug' ];
		} else {
			$slug = "";
		}

		if ( isset( $instance[ 'api_key'] ) ) {
			$api_key = $instance[ 'api_key'];
		} else {
			$api_key = "";
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'television' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'slug' ); ?>"><?php _e( 'TV Show Search:', 'television' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'slug' ); ?>" name="<?php echo $this->get_field_name( 'slug' ); ?>" type="text" value="<?php echo esc_attr( $slug ); ?>" />
			<p class="widget-small">
			<?php _e( "You can choose from any of the shows at <a href='http://trakt.tv/'>trakt.tv</a>.", 'television' ); ?>
			</p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'api_key' ); ?>"><?php _e( 'API Key:', 'television' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'api_key' ); ?>" name="<?php echo $this->get_field_name( 'api_key' ); ?>" type="text" value="<?php echo esc_attr( $api_key );?>" />
		</p>
		<p class="widget-small">
			<?php _e( "You will need a free <a href='http://trakt.tv/api-docs'>trakt.tv</a> API key.", 'television' ); ?>
		</p>
		<?php
	}

	/*
	the update() function gets called on save - while we are not using $_POST here, $new_instance in the case of WordPress widgets
	is the same. It gives us access to the forms values
	$instance is what will get saved in the database
	*/
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['slug'] = ( ! empty( $new_instance['slug'] ) ) ? strip_tags( $new_instance['slug'] ) : '';
		$instance['slug'] = str_replace( ' ', '-', $instance['slug'] );
		$instance['slug'] = strtolower( $instance['slug'] );
		$instance['api_key'] = ( !empty( $new_instance['api_key'] ) ) ? strip_tags( $new_instance['api_key'] ) : '';
		return $instance;
	}
}
?>