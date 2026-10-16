<?php
/**
 * Referral Wallet — 35% of paid tier only (direct referrers).
 * Cashout: member requests → admin approves → pays → balance decreases.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GC_WALLET_PCT', 0.35 );

/** Current wallet balance (₦). */
function gc_wallet_balance( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	return $user_id ? (int) get_user_meta( $user_id, 'gc_wallet_balance', true ) : 0;
}

/** Ledger entries for a user (newest first). */
function gc_wallet_ledger( $user_id, $limit = 20 ) {
	$user_id = absint( $user_id );
	$log     = get_user_meta( $user_id, 'gc_wallet_ledger', true );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$log = array_slice( array_reverse( $log ), 0, max( 1, absint( $limit ) ) );
	return $log;
}

/**
 * Credit direct referrer 35% when invitee PAYS for a tier.
 * No credit on free join or admin tier without payment_ref.
 */
function gc_wallet_credit_referral_on_payment( $payer_id, $tier, $payment_ref = '', $amount_paid = 0 ) {
	$payer_id    = absint( $payer_id );
	$tier        = absint( $tier );
	$payment_ref = sanitize_text_field( $payment_ref );
	if ( ! $payer_id || ! $payment_ref ) {
		return false; // Payment required
	}
	// One credit per payment reference
	$key = 'gc_wallet_paid_ref_' . md5( $payment_ref );
	if ( get_user_meta( $payer_id, $key, true ) ) {
		return false;
	}

	$referrer_id = (int) get_user_meta( $payer_id, 'gc_referred_by_user', true );
	if ( ! $referrer_id ) {
		$tag = get_user_meta( $payer_id, 'gc_referred_by_tag', true );
		if ( $tag && function_exists( 'gc_get_premium_user_by_tag' ) ) {
			$ru = gc_get_premium_user_by_tag( $tag );
			if ( $ru ) {
				$referrer_id = (int) $ru->ID;
				update_user_meta( $payer_id, 'gc_referred_by_user', $referrer_id );
			}
		}
	}
	if ( ! $referrer_id || $referrer_id === $payer_id ) {
		return false;
	}

	$amount_paid = absint( $amount_paid );
	if ( $amount_paid < 1 && function_exists( 'gc_tiers' ) ) {
		$tiers = gc_tiers();
		if ( isset( $tiers[ $tier ]['price'] ) ) {
			$amount_paid = absint( $tiers[ $tier ]['price'] );
		}
	}
	if ( $amount_paid < 1 ) {
		return false;
	}

	$credit = (int) floor( $amount_paid * GC_WALLET_PCT );
	if ( $credit < 1 ) {
		return false;
	}

	$bal = (int) get_user_meta( $referrer_id, 'gc_wallet_balance', true ) + $credit;
	update_user_meta( $referrer_id, 'gc_wallet_balance', $bal );

	$ledger   = get_user_meta( $referrer_id, 'gc_wallet_ledger', true );
	$ledger   = is_array( $ledger ) ? $ledger : array();
	$payer    = get_userdata( $payer_id );
	$ledger[] = array(
		'type'        => 'credit',
		'amount'      => $credit,
		'pct'         => 35,
		'base'        => $amount_paid,
		'tier'        => $tier,
		'payer_id'    => $payer_id,
		'payer_name'  => $payer ? $payer->display_name : '',
		'payment_ref' => $payment_ref,
		'time'        => current_time( 'mysql' ),
	);
	if ( count( $ledger ) > 100 ) {
		$ledger = array_slice( $ledger, -100 );
	}
	update_user_meta( $referrer_id, 'gc_wallet_ledger', $ledger );
	update_user_meta( $payer_id, $key, 1 );
	return true;
}

/**
 * Hook: after paid migrate to premium.
 */
function gc_wallet_on_migrate_premium( $user_id, $tier, $payment_ref ) {
	if ( empty( $payment_ref ) ) {
		return;
	}
	gc_wallet_credit_referral_on_payment( $user_id, $tier, $payment_ref, 0 );
}

/** Cashout requests store (site option). */
function gc_wallet_cashout_requests() {
	$r = get_option( 'gc_wallet_cashouts', array() );
	return is_array( $r ) ? $r : array();
}

function gc_wallet_save_cashouts( $list ) {
	update_option( 'gc_wallet_cashouts', array_values( $list ), false );
}

/** Member submits cashout. */
function gc_wallet_handle_cashout() {
	if ( empty( $_POST['gc_wallet_cashout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gc_wallet_cashout_nonce'] ) ), 'gc_wallet_cashout' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ), 403 );
	}
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	$uid    = get_current_user_id();
	$amount = absint( $_POST['gc_cashout_amount'] ?? 0 );
	$bank   = sanitize_text_field( wp_unslash( $_POST['gc_cashout_bank'] ?? '' ) );
	$acct   = sanitize_text_field( wp_unslash( $_POST['gc_cashout_account'] ?? '' ) );
	$name   = sanitize_text_field( wp_unslash( $_POST['gc_cashout_name'] ?? '' ) );
	$bal    = gc_wallet_balance( $uid );
	$dash   = function_exists( 'gc_page_url' ) ? gc_page_url( 'premium-dashboard' ) : home_url( '/premium-dashboard/' );

	if ( $amount < 1 || $amount > $bal || $bank === '' || $acct === '' || $name === '' ) {
		wp_safe_redirect( add_query_arg( 'gc_wallet', 'invalid', $dash . '#gc-wallet' ) );
		exit;
	}

	$id   = 'W' . $uid . '-' . time();
	$list = gc_wallet_cashout_requests();
	$list[] = array(
		'id'         => $id,
		'user_id'    => $uid,
		'amount'     => $amount,
		'bank'       => $bank,
		'account'    => $acct,
		'name'       => $name,
		'status'     => 'pending',
		'requested'  => current_time( 'mysql' ),
		'processed'  => '',
	);
	gc_wallet_save_cashouts( $list );
	wp_safe_redirect( add_query_arg( 'gc_wallet', 'requested', $dash . '#gc-wallet' ) );
	exit;
}
add_action( 'admin_post_gc_wallet_cashout', 'gc_wallet_handle_cashout' );

/** Admin approve cashout — deduct balance. */
function gc_wallet_admin_process() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( empty( $_GET['gc_wallet_action'] ) || empty( $_GET['gc_wallet_id'] ) ) {
		return;
	}
	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gc_wallet_admin' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'game-changers' ) );
	}
	$action = sanitize_key( wp_unslash( $_GET['gc_wallet_action'] ) );
	$id     = sanitize_text_field( wp_unslash( $_GET['gc_wallet_id'] ) );
	$list   = gc_wallet_cashout_requests();
	$changed = false;
	foreach ( $list as &$row ) {
		if ( ( $row['id'] ?? '' ) !== $id || ( $row['status'] ?? '' ) !== 'pending' ) {
			continue;
		}
		$uid    = absint( $row['user_id'] ?? 0 );
		$amount = absint( $row['amount'] ?? 0 );
		if ( 'approve' === $action && $uid && $amount > 0 ) {
			$bal = gc_wallet_balance( $uid );
			if ( $amount > $bal ) {
				$amount = $bal;
			}
			if ( $amount > 0 ) {
				update_user_meta( $uid, 'gc_wallet_balance', max( 0, $bal - $amount ) );
				$paid = (int) get_user_meta( $uid, 'gc_wallet_paid_out', true ) + $amount;
				update_user_meta( $uid, 'gc_wallet_paid_out', $paid );
				$ledger   = get_user_meta( $uid, 'gc_wallet_ledger', true );
				$ledger   = is_array( $ledger ) ? $ledger : array();
				$ledger[] = array(
					'type'   => 'debit',
					'amount' => $amount,
					'note'   => 'Cashout approved',
					'time'   => current_time( 'mysql' ),
				);
				update_user_meta( $uid, 'gc_wallet_ledger', $ledger );
			}
			$row['status']    = 'approved';
			$row['processed'] = current_time( 'mysql' );
			$changed          = true;
		} elseif ( 'reject' === $action ) {
			$row['status']    = 'rejected';
			$row['processed'] = current_time( 'mysql' );
			$changed          = true;
		}
		break;
	}
	unset( $row );
	if ( $changed ) {
		gc_wallet_save_cashouts( $list );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gc-wallet-cashouts&done=1' ) );
	exit;
}
add_action( 'admin_init', 'gc_wallet_admin_process' );

/** Admin menu. */
function gc_wallet_admin_menu() {
	add_menu_page(
		__( 'Referral Wallet', 'game-changers' ),
		__( 'Wallet Cashouts', 'game-changers' ),
		'manage_options',
		'gc-wallet-cashouts',
		'gc_wallet_admin_page',
		'dashicons-money-alt',
		58
	);
}
add_action( 'admin_menu', 'gc_wallet_admin_menu' );

function gc_wallet_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$list = array_reverse( gc_wallet_cashout_requests() );
	echo '<div class="wrap"><h1>Referral wallet cashouts</h1>';
	echo '<p>Approve only after you have paid the member’s bank account. Approval deducts the amount from their wallet.</p>';
	echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Member</th><th>Amount</th><th>Bank</th><th>Account</th><th>Name</th><th>Status</th><th>Requested</th><th></th></tr></thead><tbody>';
	if ( ! $list ) {
		echo '<tr><td colspan="9">No requests yet.</td></tr>';
	}
	foreach ( $list as $row ) {
		$u    = get_userdata( absint( $row['user_id'] ?? 0 ) );
		$st   = esc_html( $row['status'] ?? '' );
		$amt  = number_format_i18n( absint( $row['amount'] ?? 0 ) );
		echo '<tr>';
		echo '<td>' . esc_html( $row['id'] ?? '' ) . '</td>';
		echo '<td>' . esc_html( $u ? $u->user_email : (string) ( $row['user_id'] ?? '' ) ) . '</td>';
		echo '<td>₦' . esc_html( $amt ) . '</td>';
		echo '<td>' . esc_html( $row['bank'] ?? '' ) . '</td>';
		echo '<td>' . esc_html( $row['account'] ?? '' ) . '</td>';
		echo '<td>' . esc_html( $row['name'] ?? '' ) . '</td>';
		echo '<td>' . $st . '</td>';
		echo '<td>' . esc_html( $row['requested'] ?? '' ) . '</td>';
		echo '<td>';
		if ( ( $row['status'] ?? '' ) === 'pending' ) {
			$aid = esc_attr( $row['id'] );
			$n   = wp_create_nonce( 'gc_wallet_admin' );
			echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=gc-wallet-cashouts&gc_wallet_action=approve&gc_wallet_id=' . rawurlencode( $aid ) . '&_wpnonce=' . $n ) ) . '">Approve (paid)</a> ';
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=gc-wallet-cashouts&gc_wallet_action=reject&gc_wallet_id=' . rawurlencode( $aid ) . '&_wpnonce=' . $n ) ) . '">Reject</a>';
		} else {
			echo '—';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/** Hub UI. */
function gc_render_wallet_block() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$uid    = get_current_user_id();
	$bal    = function_exists( 'gc_wallet_balance' ) ? gc_wallet_balance( $uid ) : (int) get_user_meta( $uid, 'gc_wallet_balance', true );
	$paid   = (int) get_user_meta( $uid, 'gc_wallet_paid_out', true );
	$ledger = function_exists( 'gc_wallet_ledger' ) ? gc_wallet_ledger( $uid, 6 ) : array();
	$notice = isset( $_GET['gc_wallet'] ) ? sanitize_key( wp_unslash( $_GET['gc_wallet'] ) ) : '';
	ob_start();
	?>
	<section id="gc-wallet" class="gc-wallet-panel">
		<div class="gc-wallet-head">
			<div>
				<span class="gc-kicker">Referral wallet</span>
				<h2 class="gc-wallet-bal">₦<?php echo esc_html( number_format_i18n( $bal ) ); ?></h2>
				<p class="gc-wallet-lead">Direct invites only · <strong>35%</strong> when they <strong>pay</strong> a tier · not on free join</p>
			</div>
			<div class="gc-wallet-paid">Paid out<br><strong>₦<?php echo esc_html( number_format_i18n( $paid ) ); ?></strong></div>
		</div>
		<?php if ( 'requested' === $notice ) : ?>
			<p class="gc-wallet-msg is-ok">Cashout submitted — admin will review and pay your bank.</p>
		<?php elseif ( 'invalid' === $notice ) : ?>
			<p class="gc-wallet-msg is-err">Check amount and bank details (max ₦<?php echo esc_html( number_format_i18n( $bal ) ); ?>).</p>
		<?php endif; ?>
		<?php if ( $ledger ) : ?>
		<div class="gc-wallet-list">
			<div class="gc-wallet-list-h">Recent activity</div>
			<?php foreach ( $ledger as $row ) :
				$is_in = ( $row['type'] ?? '' ) === 'credit';
				$amt   = absint( $row['amount'] ?? 0 );
				$label = $is_in
					? ( ( $row['payer_name'] ?? 'Member' ) . ' · Tier ' . absint( $row['tier'] ?? 0 ) )
					: ( $row['note'] ?? 'Cashout' );
				?>
				<div class="gc-wallet-row">
					<span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $row['time'] ?? '' ); ?></small></span>
					<strong class="<?php echo $is_in ? 'in' : 'out'; ?>"><?php echo $is_in ? '+' : '−'; ?>₦<?php echo esc_html( number_format_i18n( $amt ) ); ?></strong>
				</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<p class="gc-wallet-empty">No earnings yet. Share your referral link — when they pay a tier, 35% lands here.</p>
		<?php endif; ?>
		<details class="gc-wallet-cashout">
			<summary>Request cashout</summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gc-wallet-form">
				<input type="hidden" name="action" value="gc_wallet_cashout">
				<?php wp_nonce_field( 'gc_wallet_cashout', 'gc_wallet_cashout_nonce' ); ?>
				<label>Amount (₦) <input type="number" name="gc_cashout_amount" min="1" max="<?php echo esc_attr( max( 1, $bal ) ); ?>" value="<?php echo $bal > 0 ? esc_attr( $bal ) : ''; ?>" required <?php disabled( $bal < 1 ); ?>></label>
				<label>Bank name <input type="text" name="gc_cashout_bank" required <?php disabled( $bal < 1 ); ?>></label>
				<label>Account number <input type="text" name="gc_cashout_account" required <?php disabled( $bal < 1 ); ?>></label>
				<label>Account name <input type="text" name="gc_cashout_name" required <?php disabled( $bal < 1 ); ?>></label>
				<button type="submit" <?php disabled( $bal < 1 ); ?>>Submit for admin review</button>
			</form>
		</details>
	</section>
	<style>
	.gc-wallet-panel{margin:24px 0;padding:20px;border-radius:16px;background:linear-gradient(165deg,#0a1a10,#143024);color:#f5f2ec}
	.gc-wallet-head{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start}
	.gc-wallet-panel .gc-kicker{font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:#e8b840;font-weight:700}
	.gc-wallet-bal{margin:6px 0 4px;font-size:clamp(26px,5vw,34px);color:#e8b840;font-weight:800;line-height:1.1}
	.gc-wallet-lead{margin:0;font-size:13px;opacity:.88;max-width:48ch;line-height:1.45}
	.gc-wallet-paid{font-size:12px;opacity:.9;text-align:right}
	.gc-wallet-paid strong{display:block;font-size:16px;color:#fff;margin-top:2px}
	.gc-wallet-msg{margin:14px 0 0;padding:10px 12px;border-radius:8px;font-size:13px}
	.gc-wallet-msg.is-ok{background:rgba(232,184,64,.15);color:#e8b840}
	.gc-wallet-msg.is-err{background:rgba(200,40,40,.12);color:#f0a0a0}
	.gc-wallet-list{margin-top:16px}
	.gc-wallet-list-h{font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.65;margin-bottom:6px}
	.gc-wallet-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid rgba(245,242,236,.08);font-size:13px}
	.gc-wallet-row small{display:block;opacity:.5;font-size:11px;margin-top:2px}
	.gc-wallet-row .in{color:#e8b840}.gc-wallet-row .out{color:#c8d4cc}
	.gc-wallet-empty{margin:14px 0 0;font-size:13px;opacity:.75}
	.gc-wallet-cashout{margin-top:16px}
	.gc-wallet-cashout summary{cursor:pointer;font-weight:700;color:#e8b840;list-style:none}
	.gc-wallet-form{margin-top:12px;display:grid;gap:10px;max-width:440px}
	.gc-wallet-form label{font-size:12px;display:grid;gap:4px}
	.gc-wallet-form input{min-height:44px;border-radius:8px;border:0;padding:0 12px;width:100%;box-sizing:border-box}
	.gc-wallet-form button{min-height:46px;border:0;border-radius:8px;background:#e8b840;color:#0d1a0f;font-weight:800;cursor:pointer}
	.gc-wallet-form button:disabled{opacity:.45;cursor:not-allowed}
	@media(max-width:700px){
		.gc-wallet-panel{padding:16px;margin:16px 0;border-radius:14px}
		.gc-wallet-paid{text-align:left;width:100%}
		.gc-wallet-form{max-width:none}
	}
	</style>
	<?php
	return ob_get_clean();
}
