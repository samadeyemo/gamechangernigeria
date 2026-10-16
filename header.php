<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="gc-site-header">
  <nav class="gc-nav" aria-label="Primary navigation">
    <a class="gc-brand" href="<?php echo esc_url( home_url('/') ); ?>" aria-label="Game Changers Nigeria home">
      <?php
      $logo_url = '';
      if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
        $logo_id = (int) get_theme_mod( 'custom_logo' );
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
        if ( $logo_url ) {
          echo '<img class="gc-brand-logo" src="' . esc_url( $logo_url ) . '" width="40" height="40" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
        }
      }
      if ( empty( $logo_url ) ) {
        $mark = get_template_directory_uri() . '/assets/images/game-changers-mark.png';
        echo '<img class="gc-brand-logo" src="' . esc_url( $mark ) . '" width="36" height="48" alt="Game Changers Nigeria logo">';
      }
      ?>
      <span class="gc-brand-copy"><strong>GAME CHANGERS</strong><small>NIGERIA</small></span>
    </a>
    <style>
    .gc-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
    .gc-brand-logo{display:block;width:36px;height:auto;max-height:48px;object-fit:contain;flex-shrink:0}
    .gc-brand-copy{display:flex;flex-direction:column;line-height:1.1}
    .gc-brand-copy strong{color:#f5f2ec;font-size:13px;letter-spacing:.04em}
    .gc-brand-copy small{color:#e8b840;font-size:10px;letter-spacing:.12em}
    </style>

    <ul class="gc-menu" id="gc-primary-menu">
      <li><a href="<?php echo esc_url( gc_page_url( 'about' ) ); ?>">About</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'academy' ) ); ?>">Academy</a></li>
      <li><a href="<?php echo esc_url( home_url( '/game-changer-leadership-program/' ) ); ?>">Leadership Program</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'community' ) ); ?>">Community</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'opportunities' ) ); ?>">Opportunities</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'stories' ) ); ?>">Stories</a></li>
      <li class="gc-donate-link"><a href="<?php echo esc_url( gc_page_url( 'donate' ) ); ?>">Donate</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( is_user_logged_in() ? gc_page_url( 'premium-dashboard' ) : gc_page_url( 'premium-login' ) ); ?>">Member Hub</a></li>
      <li class="gc-mobile-only"><a class="gc-button gc-button-gold" href="<?php echo esc_url( gc_page_url( 'inner-circle' ) ); ?>">Join Inner Circle</a></li>
    </ul>

    <div class="gc-nav-actions gc-desktop-only">
      <a class="gc-login-link" href="<?php echo esc_url( is_user_logged_in() ? gc_page_url( 'premium-dashboard' ) : gc_page_url( 'premium-login' ) ); ?>">Member Hub</a>
      <a class="gc-button gc-button-gold" href="<?php echo esc_url( gc_page_url( 'inner-circle' ) ); ?>">Join Inner Circle</a>
    </div>

    <button type="button" class="gc-menu-toggle" id="gc-nav-toggle" aria-label="<?php esc_attr_e( 'Menu', 'game-changers' ); ?>" aria-expanded="false" aria-controls="gc-primary-menu">
      <span></span>
    </button>
  </nav>
</header>
<script>
(function () {
  var toggle = document.getElementById('gc-nav-toggle');
  var links  = document.getElementById('gc-primary-menu');
  if (!toggle || !links) return;
  function setOpen(open) {
    links.classList.toggle('is-open', open);
    toggle.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('gc-menu-open', open);
  }
  toggle.addEventListener('click', function () {
    setOpen(!links.classList.contains('is-open'));
  });
  links.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { setOpen(false); });
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) setOpen(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });
})();
</script>
