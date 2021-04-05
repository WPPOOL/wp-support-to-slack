<?php

function wpslack_get_addresses($args = [])
{
    global $wpdb;

    $defaults = [
        'number'  => 20,
        'offset'  => 0,
        'orderby' => 'id',
        'order'   => 'ASC'
    ];
    $id = get_current_user_id();

    $args = wp_parse_args($args, $defaults);

    $sql = $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}_pluginfeeds WHERE created_by = %s
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d, %d",
        $id,
        $args['offset'],
        $args['number']
    );

    $items = $wpdb->get_results($sql);

    return $items;
}

function wpslack_address_count()
{
    global $wpdb;

    return (int) $wpdb->get_var("SELECT count(id) FROM {$wpdb->prefix}_pluginfeeds");
}

function wpslack_get_plugin_info($id)
{
    global $wpdb;

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}_pluginfeeds WHERE id = %d", $id)
    );
}

function wpslack_delete_feed($id)
{
    global $wpdb;

    $plugins = wpslack_get_plugin_info($id);
    //write_log($plugins);
    if(is_object($plugins)){
        wp_clear_scheduled_hook($plugins->plugin_feed_url);
    }

    return $wpdb->delete(
        $wpdb->prefix . '_pluginfeeds',
        ['id' => $id],
        ['%d']
    );
}

function get_all_feed(){
    global $wpdb;
    $id = get_current_user_id();
    return $wpdb->get_results ( "SELECT * FROM {$wpdb->prefix}_pluginfeeds WHERE ");
}