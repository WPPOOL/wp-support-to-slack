<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List Table Class
 */
class FeedList extends \WP_List_Table
{

    function __construct()
    {
        parent::__construct([
            'singular' => 'contact',
            'plural'   => 'contacts',
            'ajax'     => false
        ]);
    }

    /**
     * Message to show if no designation found
     *
     * @return void
     */
    function no_items()
    {
        _e('No plugin found', 'support-to-slack');
    }

    /**
     * Get the column names
     *
     * @return array
     */
    public function get_columns()
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'name'       => __('Plugin Name', 'support-to-slack'),
            'plugin_theme'    => __('Plugin / Theme', 'support-to-slack'),
            'created_at' => __('Date', 'support-to-slack'),
        ];
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = [
            'plugin_name'       => ['plugin_name', true],
            'created_at' => ['created_at', true],
        ];

        return $sortable_columns;
    }

    /**
     * Set the bulk actions
     *
     * @return array
     */
    function get_bulk_actions()
    {
        $actions = array(
            'trash'  => __('Move to Trash', 'support-to-slack'),
        );

        return $actions;
    }

    /**
     * Default column values
     *
     * @param  object $item
     * @param  string $column_name
     *
     * @return string
     */
    protected function column_default($item, $column_name)
    {
        switch ($column_name) {

            case 'created_at':
                return wp_date(get_option('date_format'), strtotime($item->created_at));
            default:
                return isset($item->$column_name) ? $item->$column_name : '';
        }
    }

    /**
     * Render the "name" column
     *
     * @param  object $item
     *
     * @return string
     */
    public function column_name($item)
    {
        $actions = [];
        $actions['edit']   = sprintf('<a href="%s" title="%s">%s</a>', admin_url('admin.php?page=wp_support_to_slack_page&action=edit&id=' . $item->id), $item->id, __('Edit', 'support-to-slack'), __('Edit', 'support-to-slack'));
        $actions['delete'] = sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'Are you sure?\');" title="%s">%s</a>', wp_nonce_url(admin_url('admin-post.php?action=wp_support_to_slack_page-delete-cron&id=' . $item->id), 'wp_support_to_slack_page-delete-cron'), $item->id, __('Delete', 'support-to-slack'), __('Delete', 'support-to-slack'));

        return sprintf(
            '<a href="%1$s"><strong>%2$s</strong></a> %3$s',
            admin_url('admin.php?wp_support_to_slack_page&action=view&id' . $item->id),
            $item->plugin_feed_url,
            $this->row_actions($actions)
        );
    }

    /**
     * Render the "cb" column
     *
     * @param  object $item
     *
     * @return string
     */
    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%d" />',
            $item->id
        );
    }

    /**
     * Prepare the address items
     *
     * @return void
     */
    public function prepare_items()
    {
        $column   = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$column, $hidden, $sortable];

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;

        $args = [
            'number' => $per_page,
            'offset' => $offset,
        ];

        if (isset($_REQUEST['orderby']) && isset($_REQUEST['order'])) {
            $args['orderby'] = $_REQUEST['orderby'];
            $args['order']   = $_REQUEST['order'];
        }

        $this->items = wpslack_get_addresses($args);
        $this->set_pagination_args([
            'total_items' => wpslack_address_count(),
            'per_page'    => $per_page
        ]);
    }
}
