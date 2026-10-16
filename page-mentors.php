<?php
/**
 * Template Name: Mentors
 * Public mentor directory (approved applications + manual profiles).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$q = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
$args = array(
	'post_type'      => 'gc_mentor',
	'posts_per_page' => 48,
	'post_status'    => 'publish',
	'orderby'        => 'title',
	'order'          => 'ASC',
);
if ( $q ) {
	$args['s'] = $q;
}
$items = new WP_Query( $args );
?>
<main class="gc-interior">
  <section class="gc-page-hero gc-dark">
    <div class="gc-container">
      <span class="gc-kicker">Guidance &amp; experience</span>
      <h1>Meet our<br><em>mentors.</em></h1>
      <p>Connect emerging leaders with experienced people willing to share perspective, skills and hard-earned lessons.</p>
      <form method="get" action="" style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap;max-width:480px;">
        <input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search mentors by name…" style="flex:1;min-width:180px;padding:12px 14px;border-radius:8px;border:1px solid rgba(245,242,236,.25);background:rgba(255,255,255,.08);color:#fff;font-size:15px;">
        <button type="submit" class="gc-button gc-button-gold">Find mentor</button>
      </form>
    </div>
  </section>
  <section class="gc-section">
    <div class="gc-container">
      <div class="gc-card-grid">
        <?php if ( $items->have_posts() ) : while ( $items->have_posts() ) : $items->the_post(); ?>
          <article class="gc-content-card">
            <div class="gc-card-body">
              <small>MENTOR</small>
              <h3><?php the_title(); ?></h3>
              <p><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 28 ) ); ?></p>
              <a href="<?php the_permalink(); ?>">View mentor →</a>
            </div>
          </article>
        <?php endwhile; wp_reset_postdata(); else : ?>
          <div class="gc-empty">
            <?php if ( $q ) : ?>
              <p>No mentors matched “<?php echo esc_html( $q ); ?>”. Try another name.</p>
            <?php else : ?>
              <p>Mentor profiles will appear here after applications are approved in WP Admin.</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <p style="margin-top:28px;"><a class="gc-button gc-button-outline" href="<?php echo esc_url( function_exists( 'gc_page_url' ) ? gc_page_url( 'mentor-with-us' ) : home_url( '/mentor-with-us/' ) ); ?>">Apply to mentor with us →</a></p>
    </div>
  </section>
</main>
<?php get_footer(); ?>
