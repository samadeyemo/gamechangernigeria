<?php
get_header(); $gc_status = isset( $_GET['gc_enlist'] ) ? sanitize_key( wp_unslash( $_GET['gc_enlist'] ) ) : ''; $gc_tag = isset( $_GET['gc_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['gc_tag'] ) ) : ''; $gc_telegram = 'https://t.me/gamechangersnig'; $gc_members = get_posts( array( 'post_type' => 'gc_member', 'post_status' => 'publish', 'posts_per_page' => 24, 'orderby' => 'date', 'order' => 'DESC', ) ); $gc_member_total = wp_count_posts( 'gc_member' )->publish; ?>
<style>
  .gc-enl-wrap { padding: 140px 5vw 100px; max-width: 1000px; margin: 0 auto; }
  .gc-enl-head { text-align: center; margin-bottom: 40px; }
  .gc-enl-head .section-label { color: var(--green); font-family:'Space Mono',monospace; font-size:12px; letter-spacing:.12em; text-transform:uppercase; }
  .gc-enl-head h1 { font-family:'Playfair Display',serif; font-size: clamp(28px,4vw,44px); color: var(--ink); margin: 10px 0 14px; }
  .gc-enl-head p, .gc-enl-intro { color: var(--muted); max-width: 620px; margin: 0 auto; line-height: 1.7; }
  .gc-enl-intro p { margin-bottom: 12px; }
  .gc-enl-steps { display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 48px; }
  .gc-enl-step { background: var(--card-bg); border:1px solid var(--line); border-radius: 12px; padding: 26px; }
  .gc-enl-step .step-num { font-family:'Space Mono',monospace; color: var(--gold); font-size: 12px; text-transform:uppercase; letter-spacing:.1em; margin-bottom: 8px; }
  .gc-enl-step h3 { font-family:'Playfair Display',serif; font-size: 18px; color: var(--ink); margin-bottom: 8px; }
  .gc-enl-step p { font-size: 13px; color: var(--muted); line-height: 1.7; margin-bottom: 14px; }
  .gc-telegram-btn { display:inline-flex; align-items:center; gap:8px; background:#229ED9; color:#fff; padding:11px 20px; border-radius:6px; font-size:13px; font-weight:700; text-decoration:none; }
  .gc-telegram-btn:hover { background:#1c8cc2; }
  .gc-form-card { max-width: 640px; margin: 0 auto 60px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 14px; padding: 36px clamp(20px,5vw,44px); }
  .gc-form-heading { font-family: 'Playfair Display', serif; font-size: clamp(22px,3vw,28px); color: var(--ink); margin-bottom: 10px; }
  .gc-form-intro { color: var(--muted); line-height: 1.7; margin-bottom: 26px; font-size: 14px; }
  .gc-form-row { margin-bottom: 18px; }
  .gc-form-row--split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .gc-form label { display: block; font-size: 12px; font-weight: 600; letter-spacing: .04em; color: var(--ink); margin-bottom: 6px; }
  .gc-form input, .gc-form select, .gc-form textarea {
    width: 100%; font-family: 'Inter', sans-serif; font-size: 14px; padding: 12px 14px;
    border: 1px solid var(--line); border-radius: 7px; background: #fff; color: var(--ink);
  }
  .gc-form input:focus, .gc-form select:focus, .gc-form textarea:focus { outline: none; border-color: var(--green); }
  .gc-form-submit {
    width: 100%; background: var(--green); color: #fff; border: none; padding: 14px 20px;
    border-radius: 7px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    cursor: pointer; transition: background .2s;
  }
  .gc-form-submit:hover { background: var(--green-mid); }
  .gc-form-notice { padding: 12px 16px; border-radius: 7px; font-size: 13px; margin-bottom: 22px; }
  .gc-form-notice--err { background: rgba(200,40,40,0.08); color: #a02222; border: 1px solid rgba(200,40,40,0.2); }
  .gc-referral-hint { font-size: 11px; color: var(--muted); margin-top: 6px; }
  .gc-dir-head { text-align:center; margin-bottom: 28px; }
  .gc-dir-head h2 { font-family:'Playfair Display',serif; font-size: clamp(22px,3vw,30px); color: var(--ink); }
  .gc-dir-head p { color: var(--muted); font-size: 13px; }
  .gc-dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 16px; }
  .gc-dir-card { border: 1px solid var(--line); border-radius: 10px; padding: 16px 18px; background: var(--card-bg); }
  .gc-dir-name { font-weight: 700; color: var(--ink); font-size: 14px; margin-bottom: 8px; }
  .gc-dir-meta { font-size: 12px; color: var(--muted); }
  .gc-dir-field { font-size: 12px; color: var(--ink); margin-bottom: 6px; line-height: 1.5; }
  .gc-dir-field span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 1px; }
  .gc-dir-empty { text-align:center; color: var(--muted); padding: 30px 0; }
  .gc-popup-overlay { position: fixed; inset: 0; background: rgba(13,26,15,0.75); z-index: 999; display:flex; align-items:center; justify-content:center; padding: 20px; }
  .gc-popup { background:#fff; border-radius: 14px; max-width: 420px; width:100%; padding: 36px 30px; text-align:center; }
  .gc-popup .gc-popup-icon { font-size: 40px; margin-bottom: 14px; }
  .gc-popup h3 { font-family:'Playfair Display',serif; font-size: 22px; color: var(--ink); margin-bottom: 10px; }
  .gc-popup p { color: var(--muted); font-size: 13px; line-height:1.7; margin-bottom: 18px; }
  .gc-popup .gc-tag-box { background: var(--white); border: 1px dashed var(--gold); border-radius: 8px; padding: 12px; font-family:'Space Mono',monospace; font-size: 18px; color: var(--ink); font-weight:700; margin-bottom: 18px; letter-spacing: .06em; }
  .gc-popup-close { display:block; margin-top: 14px; font-size: 12px; color: var(--muted); text-decoration:underline; cursor:pointer; background:none; border:none; }
  @media screen and (max-width: 700px) {
    .gc-enl-steps { grid-template-columns: 1fr; }
    .gc-form-row--split { grid-template-columns: 1fr; }
  }
</style>
<main class="gc-submit-page"><section class="gc-submit-hero"><div class="gc-container"><span class="gc-kicker">Join the movement</span><h1>Join the Movement.</h1><p>For Premium members only — verify your membership email to enlist for the movement and record payout details for referrals.</p></div></section><div class="gc-enl-wrap" id="gc-enlist-form">
  <div class="gc-enl-head">
    <div class="section-label"><?php esc_html_e( 'Free Enlistment', 'game-changers' ); ?></div>
    <h1><?php the_title(); ?></h1>
    <div class="gc-enl-intro">
      <p><?php echo esc_html( gc_opt( 'gc_enlist_intro', "This path is for members who already hold a Premium tier (Tier 1, 2, or higher). Verify the email on your Premium account. Free members should use Join the Movement instead." ) ); ?></p>
    </div>
  </div>
  <div class="gc-enl-steps">
    <div class="gc-enl-step">
      <div class="step-num"><?php esc_html_e( 'Step 1 · Required', 'game-changers' ); ?></div>
      <h3><?php esc_html_e( 'Join the Community Hub', 'game-changers' ); ?></h3>
      <p><?php esc_html_e( 'Updates, free resources and announcements are shared here first.', 'game-changers' ); ?></p>
      <a href="<?php echo esc_url( $gc_telegram ); ?>" target="_blank" rel="noopener" class="gc-telegram-btn">✈ <?php esc_html_e( 'Join our Telegram Group', 'game-changers' ); ?></a>
    </div>
    <div class="gc-enl-step">
      <div class="step-num"><?php esc_html_e( 'Step 2 · Free', 'game-changers' ); ?></div>
      <h3><?php esc_html_e( 'Get Listed', 'game-changers' ); ?></h3>
      <p><?php esc_html_e( 'Fill in the form below to officially join the movement and receive your personal membership tag — your own referral code to invite others.', 'game-changers' ); ?></p>
    </div>
  </div>
  <?php if ( 'error' === $gc_status ) : ?>
    <div class="gc-form-notice gc-form-notice--err" style="max-width:640px;margin:0 auto 20px;">
      <?php esc_html_e( 'Please fill in your name, a valid Premium email, and try again.', 'game-changers' ); ?>
    </div>
  <?php elseif ( 'notpremium' === $gc_status ) : ?>
    <div class="gc-form-notice gc-form-notice--err" style="max-width:640px;margin:0 auto 20px;">
      <?php esc_html_e( 'That email is not an active Premium tier member (Tier 1+). Join the Movement or complete a Premium tier first, then return here.', 'game-changers' ); ?>
      <p style="margin:10px 0 0;"><a href="<?php echo esc_url( function_exists( 'gc_page_url' ) ? gc_page_url( 'join-free' ) : home_url( '/inner-circle/' ) ); ?>">Join the Movement</a>
      · <a href="<?php echo esc_url( function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' ) ); ?>">Go Premium</a></p>
    </div>
  <?php elseif ( 'duplicate_bank' === $gc_status ) : ?>
    <div class="gc-form-notice" style="max-width:640px;margin:0 auto 20px;background:rgba(0,140,81,.08);color:#006b3f;border:1px solid rgba(0,140,81,.2);">
      <?php esc_html_e( 'You are enlisted. Payout account details were already on file for this email — we did not overwrite them.', 'game-changers' ); ?>
    </div>
  <?php endif; ?>
  <div class="gc-form-card">
    <div class="gc-premium-enlist-note" style="background:rgba(0,107,63,.06);border:1px solid rgba(0,107,63,.15);border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:13px;line-height:1.55;color:#0d1a0f;">
      <strong>Premium members only.</strong> Enter the same email you used for Tier 1, 2, or any higher plan. Free accounts cannot enlist here —
      <a href="<?php echo esc_url( function_exists( 'gc_page_url' ) ? gc_page_url( 'join-free' ) : home_url( '/inner-circle/' ) ); ?>">Join the Movement</a> instead.
    </div>

    <h2 class="gc-form-heading"><?php esc_html_e( 'Premium member enlistment', 'game-changers' ); ?></h2>
    <p class="gc-form-intro"><?php esc_html_e( "Confirm you are an active Premium member, then submit once. Account numbers are stored once per email and never duplicated.", 'game-changers' ); ?></p>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-form">
      <input type="hidden" name="action" value="gc_submit_enlistment">
      <input type="hidden" name="gc_redirect" value="<?php echo esc_url( get_permalink() ); ?>">
      <?php wp_nonce_field( 'gc_enlist', 'gc_enlist_nonce' ); ?>
      <div class="gc-form-row">
        <label for="gc_name"><?php esc_html_e( 'Full Name', 'game-changers' ); ?> *</label>
        <input type="text" id="gc_name" name="gc_name" required>
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_email"><?php esc_html_e( 'Email', 'game-changers' ); ?> *</label>
          <input type="email" id="gc_email" name="gc_email" required>
        </div>
        <div>
          <label for="gc_phone"><?php esc_html_e( 'Phone / WhatsApp', 'game-changers' ); ?></label>
          <input type="tel" id="gc_phone" name="gc_phone">
        </div>
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_town"><?php esc_html_e( 'Town / City', 'game-changers' ); ?></label>
          <input type="text" id="gc_town" name="gc_town" placeholder="<?php esc_attr_e( 'e.g. Ibadan', 'game-changers' ); ?>">
        </div>
        <div>
          <label for="gc_lga"><?php esc_html_e( 'Local Government Area (optional)', 'game-changers' ); ?></label>
          <input type="text" id="gc_lga" name="gc_lga">
        </div>
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_state"><?php esc_html_e( 'State', 'game-changers' ); ?></label>
          <select id="gc_state" name="gc_state">
            <option value=""><?php esc_html_e( 'Select your state', 'game-changers' ); ?></option>
            <?php foreach ( gc_ng_states() as $state ) : ?>
              <option value="<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $state ); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="gc_interest"><?php esc_html_e( 'Your Interest as a Game Changer Ambassador', 'game-changers' ); ?></label>
          <input type="text" id="gc_interest" name="gc_interest" placeholder="<?php esc_attr_e( 'e.g. Personal Growth, Advocacy', 'game-changers' ); ?>">
        </div>
      </div>
      <div class="gc-form-row">
        <label for="gc_referral_tag"><?php esc_html_e( 'Referral Name / Membership Tag (optional)', 'game-changers' ); ?></label>
        <input type="text" id="gc_referral_tag" name="gc_referral_tag" placeholder="<?php esc_attr_e( 'e.g. GC-7K2QN9', 'game-changers' ); ?>">
        <div class="gc-referral-hint"><?php esc_html_e( "If someone invited you, enter their membership tag so they get credit.", 'game-changers' ); ?></div>
      </div>
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_bank_name"><?php esc_html_e( 'Bank name (for referral payouts)', 'game-changers' ); ?></label>
          <input type="text" id="gc_bank_name" name="gc_bank_name" placeholder="<?php esc_attr_e( 'e.g. GTBank', 'game-changers' ); ?>">
        </div>
        <div>
          <label for="gc_account_name"><?php esc_html_e( 'Account name', 'game-changers' ); ?></label>
          <input type="text" id="gc_account_name" name="gc_account_name">
        </div>
      </div>
      <div class="gc-form-row">
        <label for="gc_account_number"><?php esc_html_e( 'Account number (NUBAN)', 'game-changers' ); ?></label>
        <input type="text" id="gc_account_number" name="gc_account_number" placeholder="<?php esc_attr_e( '10-digit NUBAN', 'game-changers' ); ?>">
        <div class="gc-referral-hint"><?php esc_html_e( 'Only needed if you may receive referral rewards or reimbursements later.', 'game-changers' ); ?></div>
      </div>
      
      <div class="gc-form-row gc-form-row--split">
        <div>
          <label for="gc_password"><?php esc_html_e( 'Create password', 'game-changers' ); ?> *</label>
          <input type="password" id="gc_password" name="gc_password" required minlength="8" autocomplete="new-password" placeholder="<?php esc_attr_e( 'At least 8 characters', 'game-changers' ); ?>">
        </div>
        <div>
          <label for="gc_password_confirm"><?php esc_html_e( 'Confirm password', 'game-changers' ); ?> *</label>
          <input type="password" id="gc_password_confirm" name="gc_password_confirm" required minlength="8" autocomplete="new-password">
        </div>
      </div>
      <p style="font-size:13px;color:var(--muted,#66756a);margin:0 0 16px;"><?php esc_html_e( 'This email and password become your Member Hub login. Later you can upgrade to Premium from your dashboard without creating a new account.', 'game-changers' ); ?></p>
      <button type="submit" class="gc-form-submit"><?php esc_html_e( 'Submit Enlistment', 'game-changers' ); ?></button>
    </form>
  </div>
  <div class="gc-dir-head">
    <h2><?php esc_html_e( 'Recently Enlisted', 'game-changers' ); ?></h2>
    <p><?php printf( esc_html__( '%d members and counting', 'game-changers' ), (int) $gc_member_total ); ?></p>
  </div>
  <?php if ( ! empty( $gc_members ) ) : ?>
    <div class="gc-dir-grid">
      <?php foreach ( $gc_members as $m ) : $state = get_post_meta( $m->ID, 'gc_member_state', true ); $interest = get_post_meta( $m->ID, 'gc_member_interest', true ); $town = get_post_meta( $m->ID, 'gc_member_town', true ); $lga = get_post_meta( $m->ID, 'gc_member_lga', true ); ?>
        <div class="gc-dir-card">
          <div class="gc-dir-name"><?php echo esc_html( $m->post_title ); ?></div>
          <?php if ( $interest ) : ?>
            <div class="gc-dir-field"><span><?php esc_html_e( 'Interest as a Game Changer Ambassador', 'game-changers' ); ?></span><?php echo esc_html( $interest ); ?></div>
          <?php endif; ?>
          <?php if ( $town ) : ?>
            <div class="gc-dir-field"><span><?php esc_html_e( 'Town/City', 'game-changers' ); ?></span><?php echo esc_html( $town ); ?></div>
          <?php endif; ?>
          <?php if ( $lga ) : ?>
            <div class="gc-dir-field"><span><?php esc_html_e( 'LGA', 'game-changers' ); ?></span><?php echo esc_html( $lga ); ?></div>
          <?php endif; ?>
          <?php if ( $state ) : ?>
            <div class="gc-dir-field"><span><?php esc_html_e( 'State', 'game-changers' ); ?></span><?php echo esc_html( $state ); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div class="gc-dir-empty"><?php esc_html_e( 'Be the first to enlist!', 'game-changers' ); ?></div>
  <?php endif; ?>
</div>
<?php if ( 'success' === $gc_status && $gc_tag ) : ?>
  <div class="gc-popup-overlay" id="gc-success-popup">
    <div class="gc-popup">
      <div class="gc-popup-icon">🎉</div>
      <h3><?php esc_html_e( "You're In!", 'game-changers' ); ?></h3>
      <p><?php esc_html_e( 'Welcome to Game Changers Nigeria. This is your personal membership tag — share it as your referral code when you invite others.', 'game-changers' ); ?></p>
      <div class="gc-tag-box"><?php echo esc_html( $gc_tag ); ?></div>
      <a href="<?php echo esc_url( $gc_telegram ); ?>" target="_blank" rel="noopener" class="gc-telegram-btn">✈ <?php esc_html_e( 'Join our Telegram Group', 'game-changers' ); ?></a>
      <button class="gc-popup-close" onclick="document.getElementById('gc-success-popup').remove();"><?php esc_html_e( 'Close', 'game-changers' ); ?></button>
    </div>
  </div>
<?php endif; ?>
</div></main>
<?php get_footer(); ?>
