<?php
/*
Plugin Name: RZ Ajax Filter With Custom Post Type 
Description: Ajax Filtering With Custom Post Type 
Version: 1.0
Author: Razel Ahmed
*/

if ( ! defined('ABSPATH') ) {
  exit;
}

class Rz_Ajax_Filter_And_Custom_Post_Type {
  public function __construct() {
    add_action( 'init', [ $this, 'init' ] );
    add_action( 'init', array( $this,'register_taxonomy') );
    add_action( 'init', [ $this, 'register_shortcode' ] );
  }

  public function init() {

    // enqueue scripts
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));

    register_post_type( 'Film', [
      'label' => 'film',
      'labels' => array(
        'name'=> 'Films',
        'singular_name'=> 'Film',
        'add_new' => 'Add New Film',
      ),
      'public' => true,
      'has_archive' => true,
      'menu_icon' => 'dashicons-editor-video',
      'supports' => array( 'title', 'thumbnail', 'editor', 'author' ),
      'taxonomies' => array( 'genre', ),
    ]);

  }

  // enqueue frontend css
  public function enqueue_frontend_styles() { 
    // Define the base URL for the plugin
    $plugin_url = plugin_dir_url(__FILE__);
    // Custom css
    wp_enqueue_style('ra-latestpost-custom-css', $plugin_url . 'css/styles.css', array(), '1.0.0', 'all');
    // Add jQuery script to show/hide film details
    wp_enqueue_script('film-ajax-script', $plugin_url . 'assets/js/scripts.js', array('jquery'), '1.0.0', true);

    wp_localize_script( 'film-ajax-script', 'variables', [
      'ajax_url' => admin_url( 'admin-ajax.php' ),
    ] );

  }

  public function register_taxonomy() {
      // Genre
      register_taxonomy('movie_type', ['film'], [
        'labels' => [
            'name' => __('Categories'),
            'singular_name' => 'Category',
            'menu_name' => __('Categories'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'rewrite' => [ 'slug' => __('type') ],
      ]);
  }

  // shortcode
  public function register_shortcode() {
    add_shortcode( 'display_films', [ $this, 'display_films' ] );
  }

  // Function to display custom post type posts
  public function display_films() {

    $query = new WP_Query( [
      'post_type' => 'film',
      'posts_per_page' => -1,
    ] );

    if ( $query->have_posts() ) {
      $output = '<div class="films-list">';
      ?>
      <div class="js-filter">
        <h2>Filters</h2>
        <?php 
          $terms = get_terms(['taxonomy' => 'movie_type']); 
          dump($terms);
          if ( $terms ) : ?> 
          <select name="cat" id="cat">
            <option value="">Select</option>
            <?php foreach ( $terms as $term ) : ?>
              <option value="<?php echo $term->slug ?>"> <?php echo $term->name; ?> </option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        
          <select name="popularity" id="popularity">
            <option value="">Select Popularity</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
          </select>
      </div>
      <?php
 
      while ( $query->have_posts() ) {
          $query->the_post();
          // Get the ACF field for Ratings
          $rating = get_field('movie_option');
          // dump($rating);
          // Get the ACF field for Ratings
          $rating = get_field('movie_option'); // Adjust 'ratings' or 'movie_option' based on your field structure
          // Categories
          $categories = get_the_terms( get_the_ID(), 'movie_type' );
          

          $output .= '<div class="film-item">';
            $output .= '<h2><a href="' . get_the_permalink() . '">' . get_the_title() . get_field('') .'</a></h2>';
            if ( has_post_thumbnail() ) {
              $output .= '<a href="' . get_the_permalink() . '">' . get_the_post_thumbnail() . '</a>';
            }
            $output .= '<div class="film-excerpt">' . get_the_excerpt() . '</div>';

            // Display Categories
            if ( $categories && ! is_wp_error( $categories ) ) {
              $output .= '<div class="film-categories"><p>Categories: ';
              $cat_list = [];
              foreach ( $categories as $category ) {
                  $cat_list[] = $category->name;
              }
              $output .= implode( ', ', $cat_list );
              $output .= '</p></div>';
            }

            // Display Ratings field
            if ( $rating ) {
              $output .= '<div class="rat-div"><p>Rating: ' . esc_html($rating) . '</p> </div>';
           } else {
              $output .= '<p>Rating: No Rating Available For This Movie!! </p>';
           }
            
          $output .= '</div>';
          wp_reset_postdata();
      }
      $output .= '</div>';
    } else {
      $output = '<p>No films found</p>';
    }

    return $output;
  }



}

new Rz_Ajax_Filter_And_Custom_Post_Type();


// Helper function for print_r
function dump ( $var ) {
  echo '<pre>';
  print_r($var);
  echo '</pre>';
}