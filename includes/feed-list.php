<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e("Plugin Feed List", "support-to-slack") ?></h1>
    <a href="<?php echo admin_url("admin.php?page=wp_support_to_slack_page&action=new") ?>" class="page-title-action"><?php _e("Add New", "support-to-slack") ?></a>

    <?php if (isset($_GET['inserted'])) { ?>
        <div class="notice notice-success">
            <p><?php _e('Plugin Feed has been added successfully!', 'support-to-slack'); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    <?php } ?>

    <?php if (isset($_GET['cron-deleted']) && $_GET['cron-deleted'] == 'true') { ?>
        <div class="notice notice-success">
            <p><?php _e('Plugin Feed has been deleted successfully!', 'support-to-slack'); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    <?php } ?>

    <form action="" method="post">
        <?php
        $table = new FeedList();
        $table->prepare_items();
        $table->search_box('search', 'search_id');
        $table->display();
        ?>
    </form>
</div>