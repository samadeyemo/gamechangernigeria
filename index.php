<?php get_header(); ?>
<main style="padding: 120px 5vw; min-height: 70vh; max-width: 800px; margin: 0 auto;">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <article>
            <h1 style="font-family:'Playfair Display',serif; margin-bottom:20px;"><?php the_title(); ?></h1>
            <div style="line-height:1.8; color:var(--ink);"><?php the_content(); ?></div>
        </article>
    <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>