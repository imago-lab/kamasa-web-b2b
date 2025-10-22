<?php
get_header();
?>

<main id="main-content" class="site-main" role="main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();

            the_content();

            // Uncomment the section below to enable comments on the front page.
            // if ( comments_open() || get_comments_number() ) :
            //     comments_template();
            // endif;
        endwhile;
    endif;
    ?>
</main>

<?php
get_footer();
?>

