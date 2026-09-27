<?php
/**
 * Template Name: Leadership Program (Leaders Cadres)
 * Description: Structured 5-tier leadership pathway — fully designed layout.
 * Editable: Appearance → Customize → Leadership Program Page
 *           Settings → Membership Tiers (names & prices)
 *           Pages → this page (optional mission body text)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$join_url    = function_exists( 'gc_page_url' ) ? gc_page_url( 'become-a-member' ) : home_url( '/become-a-member/' );
$enlist_url  = function_exists( 'gc_page_url' ) ? gc_page_url( 'enlistments' ) : home_url( '/enlistments/' );
$academy_url = function_exists( 'gc_page_url' ) ? gc_page_url( 'academy' ) : home_url( '/academy/' );

$default_tiers = array(
	1 => array(
		'name'       => 'Foundations Circle',
		'subtitle'   => 'Discover Your Leadership Identity',
		'price'      => 5000,
		'points'     => '',
		'alt'        => '',
		'highlights' => array(
			'Clarity on personal leadership',
			'Understanding of effective citizenship',
			'Structured leadership manual',
			'Certificate of participation',
			'Community access',
		),
		'note'       => 'Your first step into structured nation-building.',
	),
	2 => array(
		'name'       => 'Influence Builder',
		'subtitle'   => 'Become Visible. Become Impactful.',
		'price'      => 25000,
		'points'     => '250 GC Points',
		'alt'        => 'Or invite 5 people to Level 1',
		'highlights' => array(
			'Build influence ethically',
			'Lead peer groups',
			'Apply leadership practically',
			'Earn referral rewards',
			'Certification upgrade & recognition badge',
		),
		'note'       => 'This is where leadership becomes active.',
	),
	3 => array(
		'name'       => 'Impact Architect',
		'subtitle'   => 'Lead Projects. Lead Communities.',
		'price'      => 75000,
		'points'     => '750 GC Points',
		'alt'        => 'Or invite 3 people to Level 2',
		'highlights' => array(
			'Supervised leadership projects',
			'Structured impact reporting',
			'Facilitator eligibility',
			'Revenue-sharing opportunities',
		),
		'note'       => 'Installment option available. Leadership becomes measurable.',
	),
	4 => array(
		'name'       => 'Strategic Partner',
		'subtitle'   => 'Shape Institutions. Shape Policy.',
		'price'      => 250000,
		'points'     => '2,500 GC Points',
		'alt'        => 'Or invite 3 people to Level 3',
		'highlights' => array(
			'Strategic immersion sessions',
			'Retreat access',
			'Institutional reform frameworks',
			'Academy hosting rights',
		),
		'note'       => 'For executives, entrepreneurs, senior civil servants & regional coordinators.',
	),
	5 => array(
		'name'       => 'Legacy Council',
		'subtitle'   => 'Build Beyond Your Lifetime.',
		'price'      => 750000,
		'points'     => '',
		'alt'        => 'Or invite 3 people to Level 4',
		'highlights' => array(
			'Executive roundtables',
			'Generational leadership architecture',
			'National advisory participation',
			'Direct strategy access',
		),
		'note'       => 'Invitation-based tier for distinguished leaders and public figures.',
	),
);

if ( function_exists( 'gc_tiers' ) ) {
	$saved = gc_tiers();
	foreach ( $saved as $n => $v ) {
		if ( isset( $default_tiers[ $n ] ) ) {
			if ( ! empty( $v['name'] ) ) {
				$default_tiers[ $n ]['name'] = $v['name'];
			}
			if ( isset( $v['price'] ) ) {
				$default_tiers[ $n ]['price'] = (int) $v['price'];
			}
		}
	}
}

$lp_hero_kicker = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_hero_kicker', 'Leaders Cadres' ) : 'Leaders Cadres';
$lp_hero_title  = function_exists( 'gc_opt_html' ) ? gc_opt_html( 'gc_lp_hero_title', 'Game Changers<br><em>Leadership Program.</em>' ) : 'Game Changers<br><em>Leadership Program.</em>';
$lp_hero_lead   = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_hero_lead', 'Nigeria will rise — when her citizens rise. We engineer structured, value-driven leaders from the ground up.' ) : 'Nigeria will rise — when her citizens rise. We engineer structured, value-driven leaders from the ground up.';
$lp_hero_cta    = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_hero_cta', 'Join Foundations Circle' ) : 'Join Foundations Circle';
$lp_chal_kicker = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_challenge_kicker', 'The challenge' ) : 'The challenge';
$lp_chal_title  = function_exists( 'gc_opt_html' ) ? gc_opt_html( 'gc_lp_challenge_title', 'Nations do not fail for lack of resources.<br><em>They fail for lack of structured leadership.</em>' ) : 'Nations do not fail for lack of resources.<br><em>They fail for lack of structured leadership.</em>';
$lp_path_kicker = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_pathway_kicker', 'The pathway' ) : 'The pathway';
$lp_path_title  = function_exists( 'gc_opt_html' ) ? gc_opt_html( 'gc_lp_pathway_title', '5 Levels. One Mission.<br><em>National Transformation.</em>' ) : '5 Levels. One Mission.<br><em>National Transformation.</em>';
$lp_path_intro  = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_pathway_intro', "You don't join randomly. You grow intentionally. Promotion is earned through participation, module completion, ethical conduct, demonstrated impact, and referral of serious members." ) : "You don't join randomly. You grow intentionally. Promotion is earned through participation, module completion, ethical conduct, demonstrated impact, and referral of serious members.";
$lp_cta_kicker  = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_cta_kicker', 'The question is not “Will Nigeria change?”' ) : 'The question is not “Will Nigeria change?”';
$lp_cta_title   = function_exists( 'gc_opt_html' ) ? gc_opt_html( 'gc_lp_cta_title', 'Will you be among those who <em>shaped the change?</em>' ) : 'Will you be among those who <em>shaped the change?</em>';
$lp_cta_body    = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_cta_body', 'Join the Foundations Circle. Begin your structured leadership journey. Become a Game Changer.' ) : 'Join the Foundations Circle. Begin your structured leadership journey. Become a Game Changer.';
$lp_cta_button  = function_exists( 'gc_opt' ) ? gc_opt( 'gc_lp_cta_button', 'Join Foundations Circle — ₦5,000' ) : 'Join Foundations Circle — ₦5,000';
?>
<main class="gc-lp-page">

	<!-- HERO -->
	<section class="gc-lp-hero">
		<div class="gc-container">
			<span class="gc-lp-kicker"><?php echo esc_html( $lp_hero_kicker ); ?></span>
			<h1 class="gc-lp-hero-title"><?php echo wp_kses_post( $lp_hero_title ); ?></h1>
			<p class="gc-lp-hero-lead"><?php echo esc_html( $lp_hero_lead ); ?></p>
			<div class="gc-lp-actions">
				<a class="gc-button gc-button-gold" href="<?php echo esc_url( add_query_arg( 'tier', 1, $join_url ) ); ?>"><?php echo esc_html( $lp_hero_cta ); ?></a>
				<a class="gc-button gc-button-outline-light" href="#pathway">View the Pathway</a>
			</div>
		</div>
	</section>

	<!-- CHALLENGE -->
	<section class="gc-lp-section">
		<div class="gc-container gc-lp-split">
			<div class="gc-lp-split-left">
				<span class="gc-lp-kicker gc-lp-kicker--green"><?php echo esc_html( $lp_chal_kicker ); ?></span>
				<h2 class="gc-lp-h2"><?php echo wp_kses_post( $lp_chal_title ); ?></h2>
			</div>
			<div class="gc-lp-split-right gc-lp-prose">
				<?php
				// Structured short copy only — ignore long WP editor paste that breaks the design.
				?>
					<p>You already know something is wrong: broken systems, weak institutions, unaccountable leadership, and brilliant people with no structure.</p>
					<p><strong>Game Changers Nigeria</strong> is a Leadership Engineering Ecosystem designed to raise reform-minded citizens, ethical public servants, value-driven entrepreneurs, competent civic leaders, and nation builders — using a proven Bottom-Up Transformation Model.</p>
				<?php
				?>
			</div>
		</div>
	</section>

	<!-- PHILOSOPHY -->
	<section class="gc-lp-section gc-lp-section--soft">
		<div class="gc-container">
			<div class="gc-lp-section-head">
				<span class="gc-lp-kicker gc-lp-kicker--green">Our philosophy</span>
				<h2 class="gc-lp-h2">Why <em>Bottom-Up?</em></h2>
			</div>
			<div class="gc-lp-philo">
				<article class="gc-lp-philo-card">
					<span class="gc-lp-philo-icon gc-lp-philo-icon--no">✕</span>
					<h3>Top-down control</h3>
					<p>Concentrates power. Leaves citizens waiting for messiahs. Repeats the same cycles of disappointment.</p>
				</article>
				<article class="gc-lp-philo-card gc-lp-philo-card--yes">
					<span class="gc-lp-philo-icon gc-lp-philo-icon--yes">✓</span>
					<h3>Distributed leadership</h3>
					<p>Real change starts in citizens. We multiply leaders instead of concentrating power — so transformation scales.</p>
				</article>
			</div>
		</div>
	</section>

	<!-- PATHWAY / TIERS -->
	<section class="gc-lp-section" id="pathway">
		<div class="gc-container">
			<div class="gc-lp-section-head gc-lp-section-head--center">
				<span class="gc-lp-kicker gc-lp-kicker--green"><?php echo esc_html( $lp_path_kicker ); ?></span>
				<h2 class="gc-lp-h2"><?php echo wp_kses_post( $lp_path_title ); ?></h2>
				<p class="gc-lp-intro"><?php echo esc_html( $lp_path_intro ); ?></p>
			</div>

			<div class="gc-lp-tiers">
				<?php foreach ( $default_tiers as $num => $tier ) : ?>
					<article class="gc-lp-tier <?php echo 1 === (int) $num ? 'gc-lp-tier--featured' : ''; ?>" id="tier-<?php echo (int) $num; ?>">
						<div class="gc-lp-tier-top">
							<span class="gc-lp-tier-num">Tier <?php echo (int) $num; ?></span>
							<?php if ( 1 === (int) $num ) : ?>
								<span class="gc-lp-tier-tag">Start here</span>
							<?php endif; ?>
						</div>
						<div class="gc-lp-tier-grid">
							<div class="gc-lp-tier-main">
								<h3><?php echo esc_html( $tier['name'] ); ?></h3>
								<p class="gc-lp-tier-sub"><?php echo esc_html( $tier['subtitle'] ); ?></p>
								<ul class="gc-lp-tier-list">
									<?php foreach ( $tier['highlights'] as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
								<?php if ( ! empty( $tier['note'] ) ) : ?>
									<p class="gc-lp-tier-note"><?php echo esc_html( $tier['note'] ); ?></p>
								<?php endif; ?>
							</div>
							<div class="gc-lp-tier-side">
								<div class="gc-lp-price-box">
									<span class="gc-lp-price-label">Investment</span>
									<strong class="gc-lp-price">₦<?php echo esc_html( number_format_i18n( (int) $tier['price'] ) ); ?></strong>
									<?php if ( ! empty( $tier['points'] ) ) : ?>
										<span class="gc-lp-meta"><?php echo esc_html( $tier['points'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $tier['alt'] ) ) : ?>
										<span class="gc-lp-meta gc-lp-meta--alt"><?php echo esc_html( $tier['alt'] ); ?></span>
									<?php endif; ?>
								</div>
								<a class="gc-button <?php echo 1 === (int) $num ? 'gc-button-gold' : 'gc-button-green'; ?>" href="<?php echo esc_url( add_query_arg( 'tier', (int) $num, $join_url ) ); ?>">
									<?php echo 1 === (int) $num ? esc_html( $lp_hero_cta ) : 'Select & pay'; ?>
								</a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- GROWTH + EARN -->
	<section class="gc-lp-section gc-lp-section--soft">
		<div class="gc-container gc-lp-split">
			<div>
				<span class="gc-lp-kicker gc-lp-kicker--green">Integrity first</span>
				<h2 class="gc-lp-h2">How you <em>grow</em> here</h2>
				<ul class="gc-lp-checks">
					<li>Active participation</li>
					<li>Completion of modules</li>
					<li>Ethical leadership conduct</li>
					<li>Demonstrated impact</li>
					<li>Referral of serious members</li>
				</ul>
				<p class="gc-lp-muted">We protect the integrity of the movement. Promotion is not bought — it is earned.</p>
			</div>
			<div>
				<span class="gc-lp-kicker gc-lp-kicker--green">Sustainability</span>
				<h2 class="gc-lp-h2">Earn while building <em>impact</em></h2>
				<ul class="gc-lp-checks">
					<li>Direct referral cash rewards (structured, transparent)</li>
					<li>GC Points staked for dividends</li>
					<li>Certified facilitator programmes</li>
					<li>Regional leadership academies</li>
					<li>Social enterprise partnerships</li>
				</ul>
				<p class="gc-lp-muted">Eligible from Tier 2+. Transparent. Structured. Non-pyramidal.</p>
			</div>
		</div>
	</section>

	<!-- DIFFERENTIATION -->
	<section class="gc-lp-section">
		<div class="gc-container">
			<div class="gc-lp-section-head">
				<span class="gc-lp-kicker gc-lp-kicker--green">What makes us different</span>
				<h2 class="gc-lp-h2">Engineered <em>transformation.</em></h2>
			</div>
			<div class="gc-lp-diff">
				<article><span>01</span><h3>Structured pathway</h3><p>Clear progression from Foundations to Legacy — not random courses or motivation.</p></article>
				<article><span>02</span><h3>Bottom-up model</h3><p>We multiply leaders in communities instead of waiting for top-down reform.</p></article>
				<article><span>03</span><h3>Revenue-backed</h3><p>A sustainable model that rewards real contribution without pyramid schemes.</p></article>
				<article><span>04</span><h3>National reform focus</h3><p>Value-centred ideology aimed at institutions, citizenship, and generational impact.</p></article>
			</div>
		</div>
	</section>

	<!-- AUDIENCE -->
	<section class="gc-lp-section gc-lp-section--soft">
		<div class="gc-container">
			<div class="gc-lp-section-head gc-lp-section-head--center">
				<span class="gc-lp-kicker gc-lp-kicker--green">Who belongs here</span>
				<h2 class="gc-lp-h2">If you believe Nigeria can rise — <em>you belong here.</em></h2>
			</div>
			<div class="gc-lp-audience">
				<span>Young professionals tired of dysfunction</span>
				<span>Entrepreneurs who want to build ethically</span>
				<span>Civil servants who want reform</span>
				<span>Students who want structure</span>
				<span>Community leaders seeking tools</span>
				<span>Nigerians in the diaspora who care</span>
			</div>
		</div>
	</section>

	<!-- FINAL CTA -->
	<section class="gc-lp-cta">
		<div class="gc-container">
			<div class="gc-lp-cta-inner">
				<span class="gc-lp-kicker"><?php echo esc_html( $lp_cta_kicker ); ?></span>
				<h2 class="gc-lp-h2 gc-lp-h2--light"><?php echo wp_kses_post( $lp_cta_title ); ?></h2>
				<p class="gc-lp-cta-body"><?php echo esc_html( $lp_cta_body ); ?></p>
				<div class="gc-lp-actions">
					<a class="gc-button gc-button-gold" href="<?php echo esc_url( add_query_arg( 'tier', 1, $join_url ) ); ?>"><?php echo esc_html( $lp_cta_button ); ?></a>
					<a class="gc-button gc-button-outline-light" href="<?php echo esc_url( $academy_url ); ?>">Explore the Academy</a>
					<a class="gc-button gc-button-outline-light" href="<?php echo esc_url( $enlist_url ); ?>">Free Enlistment</a>
				</div>
				<p class="gc-lp-disclaimer">Make findings for any of these before payment based on appropriate pricing. The concept is adapted from the Game Changer Model.</p>
			</div>
		</div>
	</section>

</main>

<style>
/* ========== Leadership Program — self-contained balanced layout ========== */
.gc-lp-page {
	padding-top: 76px;
	background: var(--gc-paper, #f5f2ec);
	color: var(--gc-ink, #0d1a0f);
}
.gc-lp-page .gc-container {
	width: min(1100px, 92vw);
	margin-inline: auto;
}

/* Hero */
.gc-lp-hero {
	background: linear-gradient(165deg, #0a1a10 0%, #0d1a0f 40%, #12261a 100%);
	color: #f5f2ec;
	padding: 72px 0 80px;
	border-bottom: 1px solid rgba(232, 184, 64, 0.2);
}
.gc-lp-hero-title {
	font-family: 'Playfair Display', Georgia, serif;
	font-weight: 900;
	font-size: clamp(36px, 6vw, 58px);
	line-height: 1.08;
	margin: 12px 0 18px;
	color: #fff;
}
.gc-lp-hero-title em {
	font-style: italic;
	color: var(--gc-gold-2, #e8b840);
}
.gc-lp-hero-lead {
	max-width: 560px;
	font-size: 1.05rem;
	line-height: 1.7;
	color: rgba(245, 242, 236, 0.8);
	margin: 0 0 28px;
}
.gc-lp-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: center;
}
.gc-button-outline-light {
	background: transparent;
	border: 1px solid rgba(245, 242, 236, 0.35);
	color: #f5f2ec;
}
.gc-button-outline-light:hover {
	border-color: var(--gc-gold-2, #e8b840);
	color: var(--gc-gold-2, #e8b840);
}

/* Kickers & headings */
.gc-lp-kicker {
	display: inline-block;
	font-family: 'Space Mono', monospace;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--gc-gold-2, #e8b840);
}
.gc-lp-kicker--green {
	color: var(--gc-green, #006b3f);
}
.gc-lp-h2 {
	font-family: 'Playfair Display', Georgia, serif;
	font-weight: 700;
	font-size: clamp(26px, 3.5vw, 40px);
	line-height: 1.2;
	margin: 10px 0 0;
	color: var(--gc-ink, #0d1a0f);
}
.gc-lp-h2 em {
	font-style: italic;
	color: var(--gc-green, #006b3f);
}
.gc-lp-h2--light {
	color: #fff;
}
.gc-lp-h2--light em {
	color: var(--gc-gold-2, #e8b840);
}

/* Sections */
.gc-lp-section {
	padding: 64px 0;
}
.gc-lp-section--soft {
	background: rgba(13, 26, 15, 0.03);
}
.gc-lp-section-head {
	margin-bottom: 32px;
	max-width: 720px;
}
.gc-lp-section-head--center {
	text-align: center;
	margin-left: auto;
	margin-right: auto;
}
.gc-lp-intro {
	max-width: 580px;
	margin: 14px auto 0;
	color: var(--gc-muted, #66756a);
	font-size: 15px;
	line-height: 1.7;
}

/* Split layout */
.gc-lp-split {
	display: grid;
	grid-template-columns: 1fr 1.15fr;
	gap: 40px 56px;
	align-items: start;
}
.gc-lp-prose p {
	margin: 0 0 14px;
	font-size: 15.5px;
	line-height: 1.7;
	color: var(--gc-ink, #0d1a0f);
}
.gc-lp-prose p:last-child {
	margin-bottom: 0;
}
.gc-lp-muted {
	margin: 16px 0 0;
	font-size: 13px;
	color: var(--gc-muted, #66756a);
	line-height: 1.55;
}

/* Philosophy cards */
.gc-lp-philo {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}
.gc-lp-philo-card {
	background: #fff;
	border: 1px solid var(--gc-line, rgba(13,26,15,.12));
	border-radius: 16px;
	padding: 28px 24px;
	box-shadow: 0 12px 36px rgba(13, 26, 15, 0.05);
}
.gc-lp-philo-card--yes {
	border-color: rgba(0, 107, 63, 0.3);
	background: linear-gradient(180deg, #fff 0%, rgba(0, 107, 63, 0.04) 100%);
}
.gc-lp-philo-icon {
	display: inline-grid;
	place-items: center;
	width: 36px;
	height: 36px;
	border-radius: 50%;
	font-weight: 800;
	font-size: 14px;
	margin-bottom: 14px;
}
.gc-lp-philo-icon--no {
	background: rgba(13, 26, 15, 0.07);
	color: var(--gc-muted, #66756a);
}
.gc-lp-philo-icon--yes {
	background: rgba(0, 107, 63, 0.12);
	color: var(--gc-green, #006b3f);
}
.gc-lp-philo-card h3 {
	font-family: 'Playfair Display', Georgia, serif;
	font-size: 22px;
	margin: 0 0 8px;
}
.gc-lp-philo-card p {
	margin: 0;
	font-size: 14.5px;
	line-height: 1.65;
	color: var(--gc-muted, #66756a);
}

/* Tiers */
.gc-lp-tiers {
	display: flex;
	flex-direction: column;
	gap: 18px;
	margin-top: 8px;
}
.gc-lp-tier {
	background: #fff;
	border: 1px solid var(--gc-line, rgba(13,26,15,.12));
	border-radius: 16px;
	overflow: hidden;
	box-shadow: 0 10px 32px rgba(13, 26, 15, 0.05);
}
.gc-lp-tier--featured {
	border-color: rgba(200, 153, 42, 0.5);
	box-shadow: 0 14px 40px rgba(200, 153, 42, 0.14);
}
.gc-lp-tier-top {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px 24px;
	background: rgba(13, 26, 15, 0.03);
	border-bottom: 1px solid var(--gc-line, rgba(13,26,15,.12));
}
.gc-lp-tier--featured .gc-lp-tier-top {
	background: linear-gradient(90deg, rgba(200, 153, 42, 0.14), transparent);
}
.gc-lp-tier-num {
	font-family: 'Space Mono', monospace;
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: var(--gc-green, #006b3f);
}
.gc-lp-tier-tag {
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	background: var(--gc-gold, #c8992a);
	color: #0d1a0f;
	padding: 4px 10px;
	border-radius: 999px;
}
.gc-lp-tier-grid {
	display: grid;
	grid-template-columns: 1.45fr 0.85fr;
	gap: 24px;
	padding: 26px 24px 28px;
	align-items: start;
}
.gc-lp-tier-main h3 {
	font-family: 'Playfair Display', Georgia, serif;
	font-size: clamp(20px, 2.2vw, 26px);
	margin: 0 0 4px;
}
.gc-lp-tier-sub {
	margin: 0 0 14px;
	font-size: 13.5px;
	font-weight: 600;
	color: var(--gc-muted, #66756a);
}
.gc-lp-tier-list {
	list-style: none;
	margin: 0 0 12px;
	padding: 0;
	display: grid;
	gap: 7px;
}
.gc-lp-tier-list li {
	position: relative;
	padding-left: 20px;
	font-size: 14px;
	line-height: 1.45;
}
.gc-lp-tier-list li::before {
	content: "✓";
	position: absolute;
	left: 0;
	top: 0;
	color: var(--gc-green, #006b3f);
	font-weight: 800;
	font-size: 12px;
}
.gc-lp-tier-note {
	margin: 0;
	font-size: 13px;
	font-style: italic;
	color: var(--gc-muted, #66756a);
}
.gc-lp-tier-side {
	display: flex;
	flex-direction: column;
	gap: 14px;
	align-items: stretch;
}
.gc-lp-price-box {
	background: rgba(13, 26, 15, 0.03);
	border: 1px solid var(--gc-line, rgba(13,26,15,.12));
	border-radius: 12px;
	padding: 18px 16px;
	text-align: center;
}
.gc-lp-price-label {
	display: block;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: var(--gc-muted, #66756a);
	margin-bottom: 6px;
}
.gc-lp-price {
	display: block;
	font-family: 'Playfair Display', Georgia, serif;
	font-size: 28px;
	font-weight: 700;
	color: var(--gc-ink, #0d1a0f);
	line-height: 1.15;
}
.gc-lp-meta {
	display: block;
	margin-top: 6px;
	font-size: 12px;
	font-weight: 600;
	color: var(--gc-green, #006b3f);
}
.gc-lp-meta--alt {
	color: var(--gc-muted, #66756a);
	font-weight: 500;
}
.gc-lp-tier-side .gc-button {
	width: 100%;
	justify-content: center;
}

/* Checks */
.gc-lp-checks {
	list-style: none;
	margin: 18px 0 0;
	padding: 0;
	display: grid;
	gap: 10px;
}
.gc-lp-checks li {
	position: relative;
	padding-left: 26px;
	font-size: 14.5px;
	line-height: 1.5;
}
.gc-lp-checks li::before {
	content: "";
	position: absolute;
	left: 0;
	top: 5px;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	background: rgba(0, 107, 63, 0.12);
	border: 2px solid var(--gc-green, #006b3f);
	box-sizing: border-box;
}
.gc-lp-checks li::after {
	content: "";
	position: absolute;
	left: 5px;
	top: 9px;
	width: 6px;
	height: 6px;
	border-radius: 50%;
	background: var(--gc-green, #006b3f);
}

/* Diff grid */
.gc-lp-diff {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 16px;
}
.gc-lp-diff article {
	background: #fff;
	border: 1px solid var(--gc-line, rgba(13,26,15,.12));
	border-radius: 14px;
	padding: 22px 18px;
}
.gc-lp-diff article span {
	font-family: 'Space Mono', monospace;
	font-size: 11px;
	font-weight: 700;
	color: var(--gc-gold, #c8992a);
	letter-spacing: 0.08em;
}
.gc-lp-diff article h3 {
	font-family: 'Playfair Display', Georgia, serif;
	font-size: 18px;
	margin: 10px 0 8px;
}
.gc-lp-diff article p {
	margin: 0;
	font-size: 13.5px;
	line-height: 1.55;
	color: var(--gc-muted, #66756a);
}

/* Audience */
.gc-lp-audience {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 10px;
	max-width: 820px;
	margin: 0 auto;
}
.gc-lp-audience span {
	display: inline-block;
	padding: 10px 16px;
	background: #fff;
	border: 1px solid var(--gc-line, rgba(13,26,15,.12));
	border-radius: 999px;
	font-size: 13.5px;
	font-weight: 600;
	color: var(--gc-ink, #0d1a0f);
}

/* Final CTA */
.gc-lp-cta {
	background: linear-gradient(165deg, #0a1a10 0%, #0d1a0f 50%, #12261a 100%);
	color: #f5f2ec;
	padding: 72px 0 80px;
}
.gc-lp-cta-inner {
	max-width: 720px;
	text-align: center;
	margin: 0 auto;
}
.gc-lp-cta-body {
	margin: 14px 0 28px;
	font-size: 15.5px;
	line-height: 1.65;
	color: rgba(245, 242, 236, 0.8);
}
.gc-lp-cta .gc-lp-actions {
	justify-content: center;
}
.gc-lp-disclaimer {
	margin: 28px 0 0;
	font-size: 11px;
	line-height: 1.5;
	color: rgba(245, 242, 236, 0.45);
	max-width: 480px;
	margin-left: auto;
	margin-right: auto;
}

/* Responsive */
@media (max-width: 900px) {
	.gc-lp-page { padding-top: 70px; }
	.gc-lp-hero { padding: 52px 0 56px; }
	.gc-lp-section { padding: 48px 0; }
	.gc-lp-split { grid-template-columns: 1fr; gap: 24px; }
	.gc-lp-philo { grid-template-columns: 1fr; }
	.gc-lp-tier-grid { grid-template-columns: 1fr; gap: 20px; }
	.gc-lp-diff { grid-template-columns: 1fr 1fr; }
	.gc-lp-cta { padding: 56px 0 64px; }
}
@media (max-width: 600px) {
	.gc-lp-page { padding-top: 68px; }
	.gc-lp-hero { padding: 40px 0 48px; }
	.gc-lp-section { padding: 40px 0; }
	.gc-lp-diff { grid-template-columns: 1fr; }
	.gc-lp-tier-top { padding: 12px 18px; }
	.gc-lp-tier-grid { padding: 20px 18px 24px; }
	.gc-lp-actions { flex-direction: column; align-items: stretch; }
	.gc-lp-actions .gc-button { width: 100%; justify-content: center; }
	.gc-lp-audience span { font-size: 12.5px; padding: 8px 12px; }
}
</style>
<?php
get_footer();
