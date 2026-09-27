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
      <img class="gc-brand-logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/game-changers-mark.png' ); ?>" width="31" height="50" alt="Game Changers Nigeria logo">
      <span class="gc-brand-copy"><strong>GAME CHANGERS</strong><small>NIGERIA</small></span>
    </a>

    <ul class="gc-menu" id="gc-primary-menu">
      <li><a href="<?php echo esc_url( gc_page_url( 'about' ) ); ?>">About</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'academy' ) ); ?>">Academy</a></li>
      <li><a href="<?php echo esc_url( home_url( '/game-changer-leadership-program/' ) ); ?>">Leadership Program</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'community' ) ); ?>">Community</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'opportunities' ) ); ?>">Opportunities</a></li>
      <li><a href="<?php echo esc_url( gc_page_url( 'stories' ) ); ?>">Stories</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( gc_page_url( 'find-a-chapter' ) ); ?>">Find a Chapter</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( gc_page_url( 'start-a-chapter' ) ); ?>">Start a Chapter</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( gc_page_url( 'mentor-with-us' ) ); ?>">Become a Mentor</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( gc_page_url( 'partner-with-us' ) ); ?>">Partner With Us</a></li>
      <li class="gc-donate-link"><a href="<?php echo esc_url( gc_page_url( 'donate' ) ); ?>">Donate</a></li>
      <li class="gc-mobile-only"><a href="<?php echo esc_url( is_user_logged_in() ? gc_page_url( 'premium-dashboard' ) : gc_page_url( 'premium-login' ) ); ?>"><?php echo is_user_logged_in() ? 'Member Hub' : 'Login'; ?></a></li>
      <li class="gc-mobile-only"><a class="gc-button gc-button-gold" href="<?php echo esc_url( gc_page_url( 'inner-circle' ) ); ?>">Join Inner Circle</a></li>
    </ul>

    <div class="gc-nav-actions gc-desktop-only">
      <a class="gc-login-link" href="<?php echo esc_url( is_user_logged_in() ? gc_page_url( 'premium-dashboard' ) : gc_page_url( 'premium-login' ) ); ?>"><?php echo is_user_logged_in() ? 'Member Hub' : 'Login'; ?></a>
      <a class="gc-button gc-button-gold" href="<?php echo esc_url( gc_page_url( 'inner-circle' ) ); ?>">Join Inner Circle</a>
    </div>

    <button type="button" class="gc-menu-toggle" id="gc-nav-toggle" aria-label="<?php esc_attr_e( 'Menu', 'game-changers' ); ?>" aria-expanded="false" aria-controls="gc-primary-menu">
      <span></span>
    </button>
  </nav>
</header>
<script>
/* Same pattern as the working v1.5 theme: toggle class only; links navigate normally. */
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

  // Close when a real link is tapped — do NOT preventDefault (lets browser navigate).
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
