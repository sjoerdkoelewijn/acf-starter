<?php get_header(); ?>

    <main class="content">

        <?php if ( have_posts() ) : ?>	
                        
            <?php while ( have_posts() ) : the_post(); ?>

                <?php the_content(); ?>												
                
            <?php endwhile; ?>

        <?php else : ?>

            <p>
                <?php esc_html_e( 'No content', 'ACFSTARTER' ); ?>
            </p>

        <?php endif; ?>
        
    </main>    

<?php get_footer(); ?>