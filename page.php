<?php
get_header(); $gc_inquiry_slugs = array( 'apply-now', 'become-a-member', 'mentor-with-us', 'partner-with-us' ); $gc_slug = get_post_field( 'post_name' ); $gc_is_inquiry = in_array( $gc_slug, $gc_inquiry_slugs, true ); ?>
<style>
  .gc-page-wrap { padding: 140px 5vw 100px; max-width: 900px; margin: 0 auto; }
  .gc-page-head { text-align: center; margin-bottom: 44px; }
  .gc-page-head .section-label { color: var(--green); font-family:'Space Mono',monospace; font-size:12px; letter-spacing:.12em; text-transform:uppercase; }
  .gc-page-head h1 { font-family:'Playfair Display',serif; font-size: clamp(28px,4vw,44px); color: var(--ink); margin: 10px 0 14px; }
  .gc-page-content { line-height: 1.8; color: var(--ink); max-width: 720px; margin: 0 auto 36px; }
  .gc-page-content p { margin-bottom: 16px; }
  .gc-page-content img { max-width: 100%; height: auto; border-radius: 10px; }
</style>
<div class="gc-page-wrap" id="gc-form-anchor">
  <?php while ( have_posts() ) : the_post(); ?>
    <div class="gc-page-head">
      <?php if ( 'apply-now' === $gc_slug ) : ?>
        <div class="section-label"><?php esc_html_e( 'Premium Membership', 'game-changers' ); ?></div>
      <?php elseif ( $gc_is_inquiry ) : ?>
        <div class="section-label"><?php esc_html_e( 'Get Involved', 'game-changers' ); ?></div>
      <?php endif; ?>
      <h1><?php the_title(); ?></h1>
    </div>
    <?php if ( 'apply-now' === $gc_slug ) : $gc_selar = gc_opt( 'gc_selar_url', 'https://selar.com/9763ts1jk5' ); if ( $gc_selar ) : ?>
      <a href="<?php echo esc_url( $gc_selar ); ?>" target="_blank" rel="noopener" class="gc-selar-cta">
        <?php esc_html_e( 'Enrol & Pay Now', 'game-changers' ); ?>
        <small><?php esc_html_e( 'Opens secure checkout on Selar', 'game-changers' ); ?></small>
      </a>
    <?php endif; endif; ?>
    <?php if ( 'apply-now' === $gc_slug && function_exists('gc_tiers') ) : ?><div class="gc-tier-options"><h2>Membership tiers</h2><div><?php foreach(gc_tiers() as $tn=>$tv): ?><article><span>Tier <?php echo (int)$tn; ?></span><strong><?php echo esc_html($tv['name']); ?></strong><b>₦<?php echo esc_html(number_format_i18n($tv['price'])); ?></b></article><?php endforeach; ?></div></div><?php endif; ?>
    <?php if ( has_post_thumbnail() ) : ?>
      <div style="text-align:center;margin-bottom:32px;"><?php the_post_thumbnail( 'large', array( 'style' => 'border-radius:14px;max-width:100%;height:auto;' ) ); ?></div>
    <?php endif; ?>
    <div class="gc-page-content">
      <?php the_content(); ?>
    </div>
    <?php if ( $gc_is_inquiry ) : ?>
      <?php gc_render_inquiry_form( $gc_slug ); ?>
    <?php endif; ?>
    <?php if ( 'apply-now' === $gc_slug ) : $gc_bank = gc_opt( 'gc_account_bank_name', '' ); $gc_acct_n = gc_opt( 'gc_account_name', '' ); $gc_acct_num = gc_opt( 'gc_account_number', '' ); if ( $gc_bank || $gc_acct_n || $gc_acct_num ) : ?>
      <div class="gc-account-box">
        <h3><?php esc_html_e( 'Prefer Bank Transfer?', 'game-changers' ); ?></h3>
        <p><?php esc_html_e( 'Pay directly using the account below, then fill in the form above so we can confirm your membership.', 'game-changers' ); ?></p>
        <ul>
          <?php if ( $gc_bank ) : ?><li><span><?php esc_html_e( 'Bank', 'game-changers' ); ?></span><strong><?php echo esc_html( $gc_bank ); ?></strong></li><?php endif; ?>
          <?php if ( $gc_acct_n ) : ?><li><span><?php esc_html_e( 'Account Name', 'game-changers' ); ?></span><strong><?php echo esc_html( $gc_acct_n ); ?></strong></li><?php endif; ?>
          <?php if ( $gc_acct_num ) : ?><li><span><?php esc_html_e( 'Account Number', 'game-changers' ); ?></span><strong><?php echo esc_html( $gc_acct_num ); ?></strong></li><?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
      <div class="gc-telegram-box">
        <p><?php esc_html_e( 'Once you join, connect with the community here:', 'game-changers' ); ?></p>
        <a href="https://t.me/gamechangersnig" target="_blank" rel="noopener" class="gc-telegram-cta">✈ <?php esc_html_e( 'Join our Telegram Group', 'game-changers' ); ?></a>
      </div>
    <?php endif; ?>
  <?php endwhile; ?>
</div>
<style>
  .gc-account-box { max-width: 620px; margin: 0 auto 24px; background: var(--white); border: 1px dashed var(--gold); border-radius: 12px; padding: 24px 28px; }
  .gc-account-box h3 { font-family:'Playfair Display',serif; font-size: 18px; color: var(--ink); margin-bottom: 6px; }
  .gc-account-box p { font-size: 13px; color: var(--muted); margin-bottom: 14px; }
  .gc-account-box ul { list-style:none; }
  .gc-account-box li { display:flex; justify-content:space-between; padding: 8px 0; border-top: 1px solid var(--line); font-size: 13px; }
  .gc-account-box li span { color: var(--muted); }
  .gc-account-box li strong { color: var(--ink); }
  .gc-telegram-box { max-width: 620px; margin: 0 auto; text-align: center; }
  .gc-telegram-box p { font-size: 13px; color: var(--muted); margin-bottom: 12px; }
  .gc-telegram-cta { display:inline-flex; align-items:center; gap:8px; background:#229ED9; color:#fff; padding:12px 24px; border-radius:6px; font-size:13px; font-weight:700; text-decoration:none; }
  .gc-telegram-cta:hover { background:#1c8cc2; }
</style>
<?php get_footer(); ?>
