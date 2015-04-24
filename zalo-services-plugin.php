<?php
/**
 * Plugin Name: Zalo Services
 * Plugin URI:
 * Description: Plugin for adding services
 * Version: 0.1
 * Author: Tor Raswill
 * Author URI: http://tor.raswill.se
 * License:
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

//* Enqueue Scripts
add_action( 'wp_enqueue_scripts', 'add_services_styling' );
function add_services_styling() {

  $pluginversion = '0.2';
  if ( is_home() ) {
    wp_enqueue_script( 'zalo-services-js', plugins_url( 'zalo-services.js', __FILE__ ), array( 'jquery' ), $pluginversion, true );
    wp_enqueue_style( 'zalo-services', plugins_url( 'zalo-services.css', __FILE__ ), array(), $pluginversion, false );
  }

}

add_action( 'init', 'zalo_services_add_new_image_size' );
function zalo_services_add_new_image_size() {
    add_image_size( 'zalo_services_front', 600, 400, true ); //mobile
}

add_action( 'init', 'create_post_type_services' );
function create_post_type_services() {
  register_post_type( 'zalo_services',
    array(
      'supports' => array( 'title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail' ),
      'labels' => array(
        'name' => __( 'Services' ),
        'singular_name' => __( 'Service' )
      ),
      'public' => true,
      'has_archive' => true,
      'rewrite' => array('slug' => 'services'),
      'menu_icon' => plugins_url( 'images/icon.png', __FILE__ ),
    )
  );
}

// ONLY MOVIE CUSTOM TYPE POSTS
add_filter('manage_zalo_services_posts_columns', 'zalo_services_columns_head_only_services', 10);
add_action('manage_zalo_services_posts_custom_column', 'zalo_services_columns_content_only_services', 10, 2);

// CREATE TWO FUNCTIONS TO HANDLE THE COLUMN
function zalo_services_columns_head_only_services($defaults) {
  $defaults['sort_order'] = 'Sort order';
  return $defaults;
}
function zalo_services_columns_content_only_services($column_name, $post_ID) {
  if ($column_name == 'sort_order') {
    echo genesis_get_custom_field('zalo-service-sortorder');
  }
}
add_action( 'add_meta_boxes_zalo_services', 'zalo_services_add_meta_box' );
function zalo_services_add_meta_box() {
  add_meta_box(
  'zalo_service',
  'Icon',
  'create_zalo_serviceicon_nonce_output_box',
  'zalo_services',
  'side'
  );
}

function create_zalo_serviceicon_nonce_output_box( $post ) {
  wp_nonce_field( basename( __FILE__ ), 'serviceicon_nonce' );
  $prfx_stored_meta = get_post_meta( $post->ID );
  ?>
  <p>
    <label for="meta-text" class="prfx-row-icon"><?php _e( 'Sort order', 'prfx-textdomain' )?></label>
    <input type="text" name="zalo-service-sortorder" id="zalo-service-sortorder" value="<?php if ( isset ( $prfx_stored_meta['zalo-service-sortorder'] ) ) echo $prfx_stored_meta['zalo-service-sortorder'][0]; ?>" />
  </p>
  <p>
    <label for="meta-text" class="prfx-row-icon"><?php _e( 'Icon', 'prfx-textdomain' )?></label>
    <input type="text" name="zalo-icon-text" id="zalo-icon-text" value="<?php if ( isset ( $prfx_stored_meta['zalo-icon-text'] ) ) echo $prfx_stored_meta['zalo-icon-text'][0]; ?>" />
  </p>
  <p>
    Uses <a href="http://fortawesome.github.io/Font-Awesome">Font Awesome</a>. Visit <a href="http://fortawesome.github.io/Font-Awesome/icons/">http://fortawesome.github.io/Font-Awesome/icons/</a> for icons.<br>
    Copy the CSS-class for the desired icon and paste above, without the dot (.).
  </p>
  <?php
}

add_action( 'save_post', 'services_save_meta_box_content', 10, 2);
function services_save_meta_box_content( $post_id ) {
  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }
  if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
    if ( ! current_user_can( 'edit_page', $post_id ) ) {
      return;
    }
  } else {

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }
  }

  // Checks for input and sanitizes/saves if needed
  if( isset( $_POST[ 'zalo-icon-text' ] ) ) {
    update_post_meta( $post_id, 'zalo-icon-text', sanitize_text_field( $_POST[ 'zalo-icon-text' ] ) );
  }

  // Checks for input and sanitizes/saves if needed
  if( isset( $_POST[ 'zalo-service-sortorder' ] ) ) {
    update_post_meta( $post_id, 'zalo-service-sortorder', sanitize_text_field( $_POST[ 'zalo-service-sortorder' ] ) );
  }

}

class ZaloServicesWidget extends WP_Widget
{
  function ZaloServicesWidget()
  {
    $widget_ops = array('classname' => 'zaloserviceswidget featuredpage', 'description' => 'Displays the Zalo services' );
    $this->WP_Widget('ZaloServicesWidget', 'Zalo services widget', $widget_ops);
  }

  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
    echo '<p><label for="' . $this->get_field_id('title') . '">Title: <input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . attribute_escape($title) . '" /></label></p>';
  }

  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }

  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);

    echo $before_widget;
    //$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
    $title = empty($instance['title']) ? ' ' : $instance['title'];

    echo '<div id="zalo-services" class="zalo-section-target"></div>';

    if (!empty($title))
      //echo $before_title . $title . $after_title;
      echo '<header class="entry-header"><h2 class="entry-title">' . $title . '</h2></header>';


    $output = '';
    //$employee_query = new WP_Query('post_type=zalo_employees&showposts=10&orderby=\'ID\'&order=\'ASC\'');
    //$employee_query = new WP_Query( array ( 'post_type' => 'zalo_services', 'orderby' => 'title', 'order' => 'ASC' ) );
    $employee_query = new WP_Query( array ( 'post_type' => 'zalo_services', 'meta_key' => 'zalo-service-sortorder', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) );
    if ($employee_query->have_posts()) :
      $output .= '<div class="zalo-services">';

      while ($employee_query->have_posts()) : $employee_query->the_post(); $count++;

        if( $employee_query->current_post%3 == 0 ) {
          $output .= '<div class="three-services wrap">';
        }
        $zaloserviceclass = genesis_get_custom_field('zalo-icon-text');
        if( $employee_query->current_post%3 == 0 ) {
          $output .= '<div class="service one-third first">';
        } else {
          $output .= '<div class="service one-third">';
        }

        $placeholder = plugins_url( 'images/placeholder.png', __FILE__ );
        $content = apply_filters( 'the_content', get_the_content() );
        $content = str_replace( ']]>', ']]&gt;', $content );

        $output .= '<div class="service-image fa ' . $zaloserviceclass . '">';
        /*if(has_post_thumbnail()) {

          $output .= get_the_post_thumbnail($post->ID,'zalo_service_front');
        } else {
          $output .= '<img src="' . $placeholder . '" class="attachment-featured_image wp-post-image" alt="" />';
        }*/
        $output .= '</div>';
        $output .= '<div class="service-info">';
        $output .= '<header class="entry-header"><h4>' . get_the_title() . '</h4></header>';
        $output .= '<div>' . $content . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        if( $employee_query->current_post%3 == 2 || $employee_query->current_post == $employee_query->post_count-1 ) {
          $output .= '</div>';
        }
      endwhile;
      $output .= '</div>';
    endif;
    wp_reset_postdata();

    echo $output;

    echo $after_widget;
  }

}
add_action( 'widgets_init', create_function('', 'return register_widget("ZaloServicesWidget");') );

/* Stop Adding Functions Below this Line */
?>
