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
    add_action( 'wp_ajax_filter_posts', array($this, 'filter_posts') );
    add_action( 'wp_ajax_nopriv_filter_posts', array( $this, 'filter_posts' ) );

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

  public function filter_posts() {
    $args = [
      'post_type' => 'film',
      'posts_per_page' => -1,
    ];

    // Category filtering
    $type = $_REQUEST['cat'];
    if ( !empty($type) ) {
      $args['tax_query'] = [
        [
          'taxonomy' => 'movie_type',
          'field'    => 'slug',
          'terms'    => $type,
        ]
      ];
    }


    // Ratings filtering (using ACF or meta field for storing ratings)
    $rating = $_REQUEST['rating'];
    if ( !empty($rating) ) {
      $args['meta_query'] = [
        [
          'key'     => 'movie_option',
          'value'   => $rating,
          'compare' => '=',
        ]
      ];
    }

    $movies = new WP_Query($args);
    
    if ($movies->have_posts()) {
      ob_start();
      while ($movies->have_posts()) {
        $movies->the_post();
        ?>
        <div class="film-item">
          <h2><a href="<?php echo get_the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <?php if ( has_post_thumbnail() ) : ?>
            <a href="<?php echo get_the_permalink(); ?>"><?php the_post_thumbnail(); ?></a>
          <?php endif; ?>
          <div class="film-excerpt"><?php the_excerpt(); ?></div>
          <!-- Display Categories -->
          <?php 
          $categories = get_the_terms( get_the_ID(), 'movie_type' );
          if ( $categories && ! is_wp_error( $categories ) ) :
          ?>
            <div class="film-categories">
              <p>Categories: 
              <?php 
                $cat_list = [];
                foreach ( $categories as $category ) {
                  $cat_list[] = $category->name;
                }
                echo implode( ', ', $cat_list );
              ?>
              </p>
            </div>
          <?php endif; ?>
          <div class="film-rating rat-div">
            <p>
              <?php 
              $rating = get_field('movie_option'); // Assuming ACF is used for the rating
              echo $rating ? 'Rating: ' . esc_html($rating) : 'Rating: Not available';
              ?>
            </p>
            
          </div>
        </div>
        <?php
      }
      wp_reset_postdata();
      echo ob_get_clean();
    } else {
      echo '<p>No films found</p>';
    }

    wp_die();
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

    $output = '<div class="films-container">'; // Added container for filter and films list

    // Filters section
    $output .= '<div class="js-filter">';
    $output .= '<h2>Filters</h2>';
    
    $terms = get_terms(['taxonomy' => 'movie_type']); 
    if ( $terms ) :
        $output .= '<select name="cat" id="cat">';
        $output .= '<option value="">Select ALL</option>';
        foreach ( $terms as $term ) :
            $output .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
        endforeach;
        $output .= '</select>';
    endif;

    $output .= '<select name="popularity" id="popularity">';
    $output .= '<option value="">Select Popularity</option>';
    $output .= '<option value="1">1</option>';
    $output .= '<option value="2">2</option>';
    $output .= '<option value="3">3</option>';
    $output .= '<option value="4">4</option>';
    $output .= '<option value="5">5</option>';
    $output .= '</select>';
    
    $output .= '</div>'; // Close filter section

    // Films list section
    $output .= '<div class="films-list">';

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            // Get the ACF field for Ratings
            $rating = get_field('movie_option'); // Adjust 'movie_option' based on your field structure
            
            // Categories
            $categories = get_the_terms( get_the_ID(), 'movie_type' );
            
            $output .= '<div class="film-item">';
            $output .= '<h2><a href="' . esc_url(get_the_permalink()) . '">' . get_the_title() . '</a></h2>';
            if ( has_post_thumbnail() ) {
                $output .= '<a href="' . esc_url(get_the_permalink()) . '">' . get_the_post_thumbnail() . '</a>';
            }
            $output .= '<div class="film-excerpt">' . get_the_excerpt() . '</div>';

            // Display Categories
            if ( $categories && ! is_wp_error( $categories ) ) {
                $output .= '<div class="film-categories"><p>Categories: ';
                $cat_list = [];
                foreach ( $categories as $category ) {
                    $cat_list[] = esc_html($category->name);
                }
                $output .= implode( ', ', $cat_list );
                $output .= '</p></div>';
            }

            // Display Ratings field
            if ( $rating ) {
                $output .= '<div class="rat-div"><p>Rating: ' . esc_html($rating) . '</p></div>';
            } else {
                $output .= '<p>Rating: No Rating Available For This Movie!!</p>';
            }
            
            $output .= '</div>'; // Close film-item
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No films found</p>';
    }

    $output .= '</div>'; // Close films-list
    $output .= '</div>'; // Close films-container

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