<?php
/**
 * Plugin Name: vote-product
 * Plugin URI: https://github.com/sumon665
 * Description: vote-product
 * Version: 1.0
 * Author: Md. Sumon Mia
 * Author URI: https://github.com/sumon665
 */


/* Vote button */
add_action('woocommerce_before_add_to_cart_form','cmk_additional_button');
function cmk_additional_button() {
    echo do_shortcode('[display_vote]');
}

/* Shortcode */
function display_vote_func() {
	ob_start();

    if ( is_user_logged_in() ) {

        global $product;
        $pid = $product->get_id();
        $uid = get_current_user_id();
        $args = array(
                'post_type' => 'provote',
                'posts_per_page' => 1,
                'meta_query' => array(
                    array(
                        'key'     => 'vote_pro_id',
                        'value'   => $pid,
                        'compare' => '='
                    )
                )               
             );

        $the_query = new WP_Query( $args );
        // The Loop
        if ( $the_query->have_posts() ) :
        while ( $the_query->have_posts() ) : $the_query->the_post();
            $users = get_post_meta( get_the_ID(), 'vote_user_id', true );
            $userlist = explode (",", $users); 
            if (in_array($uid, $userlist)) {
                $vote_given = true;
            }
        endwhile;
        endif;     
        wp_reset_query();     

        if (!$vote_given) {
        ?>
           <div id="vote-content" class="vote-content">
               <h3>Vote to unlock</h3>
               <p>This product isn't currently available for member participation. If you'd like to see this item in one of our upcoming releases go ahead and cast your vote!</p>
               <div id="vote-btn">
                   <button id="vote_btn" class="btn-primary">CAST YOUR VOTE</button>
                   <input type="hidden" id="pid" name="pid" value="<?php echo $pid; ?>">                  
               </div>
           </div>
     <?php  
        }
       
    } else {
        ?>
           <div id="vote-content" class="vote-content">
               <h3>Vote to unlock</h3>
               <p>This product isn't currently avilable for member paricipation. If you'd like to see this item in one of our upcoming release go ahead and cast your vote!</p>
               <div id="vote-btn">
                   <a href="https://dev.thekallective.com/my-account/" class="btn-primary loginbtn">Login to Vote<a>
               </div>
           </div>
    <?php
    }

	$output_string = ob_get_contents();
	ob_end_clean();
	return $output_string;
}
add_shortcode( 'display_vote', 'display_vote_func' );


/* Add custom post vote count */
add_action('init', 'vote_post_type');

function vote_post_type() {
    $args = array(
        'label' => __('Product Vote'),
        'singular_label' => __('Product Vote'),
        'public' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => true,
        'supports' => array('title',),
        'has_archive' => true
    );

    register_post_type('provote', $args);
}

add_filter("manage_edit-provote_columns", "provote_edit_columns");

function provote_edit_columns($columns) {
    $columns = array(
        "cb" => "<input type=\"checkbox\" />",
        "title" => "Product Name",
        "vote" => "Vote Count",
        "date" => "Date",
    );

    return $columns;
}

add_action("manage_posts_custom_column", "provote_custom_columns");

function provote_custom_columns($column) {
    global $post;
    switch ($column) {
        case "vote":
            echo get_post_meta( $post->ID, 'vote', true );
            break;
    }
}


/* Add custom metafield */
require_once ('metabox.php');



/* JS/CSS add */
function vote_ajax_enqueue() {
    wp_enqueue_style( 'vote-stye', plugins_url() . '/vote-product/css/main.css', array(),  time() );
    wp_enqueue_script('vote-ajax-script', plugins_url() . '/vote-product/js/main.js', array('jquery'), time(), true);
    wp_localize_script( 'vote-ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ));
}

add_action( 'wp_enqueue_scripts', 'vote_ajax_enqueue' );


/* Ajax request */
function submit_vote_request() {
    $pid = $_REQUEST['pid'];
    $uid = get_current_user_id();
    $args = array(
            'post_type' => 'provote',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key'     => 'vote_pro_id',
                    'value'   => $pid,
                    'compare' => '='
                )
            )               
         );

    $the_query = new WP_Query( $args );
    // The Loop
    if ( $the_query->have_posts() ) :
    while ( $the_query->have_posts() ) : $the_query->the_post();
        $post_id = get_the_ID();
        $users = get_post_meta( $post_id, 'vote_user_id', true ); 
        $vote = get_post_meta( $post_id, 'vote', true ); 
    endwhile;
    endif;     
    wp_reset_query();

    if ($post_id) {
     $userlist = $users.",".$uid;
     $total_vote = $vote + 1;   
     update_post_meta($post_id, 'vote_user_id', $userlist);
     update_post_meta($post_id, 'vote', $total_vote);  

    } else {
        $new_post = array(
        'post_title'    => get_the_title($pid),
        'post_status'   => 'publish',          
        'post_type'     => 'provote' 
        );

        $poid = wp_insert_post($new_post);

        /* general info */
        add_post_meta($poid, 'vote_pro_id', $pid, true);
        add_post_meta($poid, 'vote_user_id', $uid, true);
        add_post_meta($poid, 'vote', 1, true);
    }

    die();
}

add_action( 'wp_ajax_submit_vote_request', 'submit_vote_request' );
add_action( 'wp_ajax_nopriv_submit_vote_request', 'submit_vote_request' );