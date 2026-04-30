<?php
/**
 * Template Name: Invoice Full Width
 * Description: A full-width template for invoice dashboard and editor.
 */

get_header();
?>

<div id="wp-invoice-full-width-page" class="wp-invoice-premium-ui" style="margin: 0; padding: 0; max-width: none; width: 100%;">
    <?php
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
    ?>
</div>

<style>
    /* Force full width by breaking out of theme containers if necessary */
    #wp-invoice-full-width-page {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        background: #f8fafc;
    }
    
    /* If the theme doesn't need breaking out, or to ensure it looks good */
    .wp-invoice-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Hide theme specific elements if they interfere */
    .entry-header, .post-navigation, .comments-area {
        display: none !important;
    }
</style>

<?php
get_footer();
