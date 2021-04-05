<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e("Plugin Feed List", "support-to-slack") ?></h1>
    <a href="<?php echo admin_url("admin.php?page=wp_support_to_slack_page&action=new") ?>" class="page-title-action"><?php _e("Add New", "support-to-slack") ?></a>

    <form action="" method="post">
        <?php
        $table = new FeedList();
        $table->prepare_items();
        $table->search_box('search', 'search_id');
        $table->display();
        ?>
    </form>
</div>