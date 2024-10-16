<?php
/**
 * Plugin Name: YS Upvote Plugin
 * Description: Adds upvote and downvote functionality to posts.
 * Version: 1.3
 * Author: Yoga Sukma
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Enqueue JS and pass necessary data to the script
function ys_enqueue_vote_scripts() {
    if (is_single()) {
        global $post;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
        $voted_ips = get_post_meta($post->ID, '_ys_voted_ips', true) ?: [];
        $already_upvoted = isset($voted_ips[$user_ip]['upvote']);
        $already_downvoted = isset($voted_ips[$user_ip]['downvote']);

        wp_enqueue_script(
            'ys-vote-js',
            plugin_dir_url(__FILE__) . 'ys-upvote.js',
            ['jquery'],
            '1.2',
            true
        );

        wp_localize_script('ys-vote-js', 'ys_vote_ajax', [
            'ajax_url'        => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('ys-vote-nonce'),
            'post_id'         => $post->ID,
            'already_upvoted' => $already_upvoted,
            'already_downvoted' => $already_downvoted,
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ys_enqueue_vote_scripts');

// Handle AJAX requests for upvotes and downvotes
function ys_handle_vote() {
    check_ajax_referer('ys-vote-nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $vote_type = sanitize_text_field($_POST['vote_type']); // 'upvote' or 'downvote'
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Retrieve or initialize voted IPs
    $voted_ips = get_post_meta($post_id, '_ys_voted_ips', true) ?: [];

    // Check if the IP has already voted
    if (isset($voted_ips[$user_ip][$vote_type])) {
        wp_send_json_error(['message' => "You have already {$vote_type}d this post."]);
    }

    // Save the vote and increment the count
    $voted_ips[$user_ip][$vote_type] = true;
    update_post_meta($post_id, '_ys_voted_ips', $voted_ips);

    $meta_key = $vote_type === 'upvote' ? '_ys_upvotes' : '_ys_downvotes';
    $count = (int) get_post_meta($post_id, $meta_key, true);
    $count++;
    update_post_meta($post_id, $meta_key, $count);

    // Respond with success message only
    wp_send_json_success(['message' => "Thank you for your {$vote_type}!"]);
}
add_action('wp_ajax_ys_vote', 'ys_handle_vote');
add_action('wp_ajax_nopriv_ys_vote', 'ys_handle_vote');

// Add upvotes and downvotes columns to the admin posts list
function ys_add_votes_columns($columns) {
    $columns['ys_upvotes'] = 'Upvotes';
    $columns['ys_downvotes'] = 'Downvotes';
    return $columns;
}
add_filter('manage_posts_columns', 'ys_add_votes_columns');

// Display vote counts in the custom columns
function ys_display_votes_columns($column, $post_id) {
    if ($column === 'ys_upvotes') {
        echo (int) get_post_meta($post_id, '_ys_upvotes', true);
    } elseif ($column === 'ys_downvotes') {
        echo (int) get_post_meta($post_id, '_ys_downvotes', true);
    }
}
add_action('manage_posts_custom_column', 'ys_display_votes_columns', 10, 2);

// Make vote columns sortable
function ys_votes_columns_sortable($columns) {
    $columns['ys_upvotes'] = 'ys_upvotes';
    $columns['ys_downvotes'] = 'ys_downvotes';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'ys_votes_columns_sortable');

// Sort posts by upvotes or downvotes
function ys_sort_by_votes($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby === 'ys_upvotes' || $orderby === 'ys_downvotes') {
        $meta_key = $orderby === 'ys_upvotes' ? '_ys_upvotes' : '_ys_downvotes';
        $query->set('meta_key', $meta_key);
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'ys_sort_by_votes');

