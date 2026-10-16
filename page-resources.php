<?php
/**
 * Template Name: Resources
 * Free vs premium resource library gated by onboarding email.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$res_msg = sanitize_key( $_GET['gc_res'] ?? '' );
$free_q = new WP_Query( array(
	'post_type'      => 'gc_resource',
	'posts_per_page' => 50,
	'meta_key'       => 'gc_resource_access',
	'meta_value'     => 'free',
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
$prem_q = new WP_Query( array(
	'post_type'      => 'gc_resource',
	'posts_per_page' => 50,
	'meta_key'       => 'gc_resource_access',
	'meta_value'     => 'premium',
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
$email_pre = is_user_logged_in() ? wp_get_current_user()->user_email : '';
?>
<style>
.gc-res-page{padding:110px 0 70px}
.gc-res-hero{text-align:center;max-width:640px;margin:0 auto 36px;padding:0 5vw}
.gc-res-hero h1{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,44px);margin:8px 0}
.gc-res-grid{display:grid;gap:16px;max-width:800px;margin:0 auto;padding:0 5vw}
.gc-res-card{background:#fff;border:1px solid rgba(13,26,15,.1);border-radius:14px;padding:20px}
.gc-res-card h3{margin:0 0 6px;font-size:18px}
.gc-res-card p{margin:0 0 12px;color:#66756a;font-size:14px;line-height:1.55}
.gc-res-card form{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.gc-res-card input[type=email]{flex:1 1 180px;padding:10px 12px;border-radius:8px;border:1px solid #ccd0d4;font-size:15px}
.gc-res-sec{max-width:800px;margin:28px auto 8px;padding:0 5vw}
.gc-res-sec h2{font-family:'Playfair Display',serif;font-size:24px;margin:0 0 4px}
.gc-res-sec .tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#006b3f;background:rgba(0,107,63,.08);padding:3px 8px;border-radius:999px}
.gc-res-sec.prem .tag{color:#8a6a12;background:rgba(232,184,64,.15)}
.gc-res-alert{max-width:800px;margin:0 auto 18px;padding:12px 16px;border-radius:10px;font-size:14px}
.gc-res-alert.err{background:#fff3f0;border:1px solid #f0c0b0;color:#8b2e1a}
</style>
<main class="gc-res-page">
  <div class="gc-res-hero">
    <p class="gc-kicker" style="color:#006b3f;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;">Resource library</p>
    <h1>Tools for leaders who do the work</h1>
    <p style="color:#66756a;">Enter the email you used when you joined. Free members unlock free resources; premium members unlock everything.</p>
  </div>

  <?php if ( 'email' === $res_msg ) : ?>
    <div class="gc-res-alert err">No membership found for that email. Join free or premium first, then return here.</div>
  <?php elseif ( 'premium_only' === $res_msg ) : ?>
    <div class="gc-res-alert err">That resource is for premium members only. Upgrade from your dashboard, then try again with the same email.</div>
  <?php endif; ?>

  <div class="gc-res-sec">
    <span class="tag">Free</span>
    <h2>Free membership resources</h2>
  </div>
  <div class="gc-res-grid">
    <?php if ( $free_q->have_posts() ) : while ( $free_q->have_posts() ) : $free_q->the_post(); ?>
      <article class="gc-res-card">
        <h3><?php the_title(); ?></h3>
        <p><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: wp_strip_all_tags( get_the_content() ), 28 ) ); ?></p>
        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
          <input type="hidden" name="action" value="gc_resource_dl">
          <input type="hidden" name="resource_id" value="<?php the_ID(); ?>">
          <?php wp_nonce_field( 'gc_resource_dl', 'gc_res_nonce' ); ?>
          <input type="email" name="member_email" required placeholder="Your join email" value="<?php echo esc_attr( $email_pre ); ?>">
          <button type="submit" class="gc-button gc-button-green">Download</button>
        </form>
      </article>
    <?php endwhile; wp_reset_postdata(); else : ?>
      <p style="padding:0 5vw;color:#66756a;">No free resources published yet. Admin: add under <strong>Resources</strong> in WP Admin (access = Free).</p>
    <?php endif; ?>
  </div>

  <div class="gc-res-sec prem">
    <span class="tag">Premium</span>
<h2>Premium membership resources</h2>
</div>

<div class="gc-res-grid">
  <?php if ( $prem_q->have_posts() ) : while ( $prem_q->have_posts() ) : $prem_q->the_post(); ?>

    <article class="gc-res-card">
      <h3><?php the_title(); ?></h3>

      <p>
        <?php echo esc_html( wp_trim_words( get_the_excerpt() ?: wp_strip_all_tags( get_the_content() ), 28 ) ); ?>
      </p>

      <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
        <input type="hidden" name="action" value="gc_resource_dl">
        <input type="hidden" name="resource_id" value="<?php the_ID(); ?>">

        <?php wp_nonce_field( 'gc_resource_dl', 'gc_res_nonce' ); ?>

        <input
          type="email"
          name="member_email"
          required
          placeholder="Your premium join email"
          value="<?php echo esc_attr( $email_pre ); ?>"
        >

        <button type="submit" class="gc-button gc-button-gold">
          Download
        </button>
      </form>

    </article>

  <?php endwhile; wp_reset_postdata(); else : ?>

    <p style="padding:0 5vw;color:#66756a;">
      No premium resources published yet. Admin: add under
      <strong>Resources</strong> (access = Premium).
    </p>

  <?php endif; ?>
</div>

</main>

<?php get_footer(); ?>
