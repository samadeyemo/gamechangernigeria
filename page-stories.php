<?php
get_header();
/**
 * Category filtering: clicking a category (Family, Marriage, Leadership,
 * etc.) filters the grid to posts in that category, using standard
 * WordPress categories/tags on the "post" type. Nothing here needs the
 * content library taxonomy from the engagement engine — this is the
 * ordinary blog/Stories archive.
 */
$gc_active_cat = isset( $_GET['gc_cat'] ) ? sanitize_title( wp_unslash( $_GET['gc_cat'] ) ) : '';
$gc_categories = get_categories( array( 'hide_empty' => true ) );

$gc_story_query_args = array( 'post_type' => 'post', 'posts_per_page' => 9 );
$gc_active_cat_obj   = null;
if ( $gc_active_cat ) {
	$gc_active_cat_obj = get_category_by_slug( $gc_active_cat );
	if ( $gc_active_cat_obj ) {
		$gc_story_query_args['cat'] = $gc_active_cat_obj->term_id;
	}
}
?>
<main class="gc-page"><section class="gc-hero"><div class="gc-container">
<p class="gc-eyebrow">STORIES</p><h1>The people and ideas moving Nigeria forward.</h1>
<p>Read leadership stories, chapter updates, Academy news and impact stories from the movement.</p>
</div></section><section class="gc-container" style="padding:0 0 80px">
<?php if ( ! empty( $gc_categories ) ) : ?>
<div class="gc-story-filters" role="navigation" aria-label="Filter stories by category">
	<a href="<?php echo esc_url( remove_query_arg( 'gc_cat' ) ); ?>" class="gc-story-filter<?php echo $gc_active_cat ? '' : ' is-active'; ?>"><?php esc_html_e( 'All', 'game-changers' ); ?></a>
	<?php foreach ( $gc_categories as $gc_cat ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'gc_cat', $gc_cat->slug ) ); ?>" class="gc-story-filter<?php echo ( $gc_active_cat_obj && $gc_active_cat_obj->term_id === $gc_cat->term_id ) ? ' is-active' : ''; ?>"><?php echo esc_html( $gc_cat->name ); ?></a>
	<?php endforeach; ?>
</div>
<?php endif; ?>
<?php $q = new WP_Query( $gc_story_query_args ); if ( $q->have_posts() ) : ?>
	<div class="gc-hub-grid three"><?php while ( $q->have_posts() ) : $q->the_post(); $gc_post_cats = get_the_category(); ?>
		<article class="gc-hub-card">
			<?php if ( ! empty( $gc_post_cats ) ) : ?>
				<span class="gc-card-num"><a href="<?php echo esc_url( add_query_arg( 'gc_cat', $gc_post_cats[0]->slug ) ); ?>"><?php echo esc_html( strtoupper( $gc_post_cats[0]->name ) ); ?></a></span>
			<?php else : ?>
				<span class="gc-card-num">STORY</span>
			<?php endif; ?>
			<h3><?php the_title(); ?></h3>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
			<strong><a href="<?php the_permalink(); ?>">Read story →</a></strong>
		</article>
	<?php endwhile; ?></div>
<?php else : ?>
	<p><?php echo $gc_active_cat_obj ? esc_html( sprintf( __( 'No stories under "%s" yet — check back soon.', 'game-changers' ), $gc_active_cat_obj->name ) ) : esc_html__( 'No stories have been published yet.', 'game-changers' ); ?></p>
<?php endif; wp_reset_postdata(); ?>
</section></main>
<style>
.gc-story-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px}
.gc-story-filter{display:inline-block;padding:8px 16px;border-radius:999px;border:1px solid var(--gc-line);font-size:13px;font-weight:600;color:var(--gc-ink);text-decoration:none}
.gc-story-filter.is-active,.gc-story-filter:hover{background:var(--gc-green);border-color:var(--gc-green);color:#fff}
.gc-hub-card .gc-card-num a{color:inherit;text-decoration:none}
</style>
<?php get_footer(); ?>
