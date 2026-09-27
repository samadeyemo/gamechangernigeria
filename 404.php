<?php
get_header(); ?>
<div style="padding:180px 5vw 120px; max-width:600px; margin:0 auto; text-align:center;">
  <div style="font-family:'Space Mono',monospace; font-size:13px; color:var(--gold); letter-spacing:.14em; text-transform:uppercase; margin-bottom:14px;">404</div>
  <h1 style="font-family:'Playfair Display',serif; font-size:clamp(28px,4vw,42px); color:var(--ink); margin-bottom:16px;"><?php esc_html_e( 'Page Not Found', 'game-changers' ); ?></h1>
  <p style="color:var(--muted); line-height:1.7; margin-bottom:32px;"><?php esc_html_e( "The page you're looking for doesn't exist or has moved.", 'game-changers' ); ?></p>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block; background:var(--green); color:#fff; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:600; font-size:13px; text-transform:uppercase; letter-spacing:.06em;"><?php esc_html_e( 'Back to Home', 'game-changers' ); ?></a>
</div>
<?php get_footer(); ?>
