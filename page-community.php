<?php
get_header();
$counts = array(
	'members'  => (int) wp_count_posts( 'gc_member' )->publish,
	'chapters' => (int) wp_count_posts( 'gc_chapter' )->publish,
	'mentors'  => (int) wp_count_posts( 'gc_mentor' )->publish,
);
?>
<main class="gc-interior gc-community-page">
<section class="gc-page-hero gc-dark"><div class="gc-container">
<span class="gc-kicker">The community</span>
<h1>People who choose to <em>create change.</em></h1>
<p>Connect with Game Changers across chapters, cohorts, mentorship and impact projects.</p>
<div class="gc-actions">
<a class="gc-button gc-button-gold" href="<?php echo esc_url( gc_page_url( 'members' ) ); ?>">Meet the Members</a>
<a class="gc-button gc-button-outline" href="<?php echo esc_url( gc_page_url( 'find-a-chapter' ) ); ?>">Find a Chapter</a>
</div>
<div class="gc-proof-row">
<span><strong><?php echo esc_html( number_format_i18n( $counts['members'] ) ); ?></strong> Members</span>
<span><strong><?php echo esc_html( number_format_i18n( $counts['chapters'] ) ); ?></strong> Chapters</span>
<span><strong><?php echo esc_html( number_format_i18n( $counts['mentors'] ) ); ?></strong> Mentors</span>
</div>
</div></section>

<section class="gc-section"><div class="gc-container">
<div class="gc-section-head">
<div><span class="gc-kicker gc-kicker-green">Find your place</span><h2>Every path into the<br><em>movement.</em></h2></div>
<p>Whether you are looking for people, a local hub, guidance or a project to join, the community has a door open for you.</p>
</div>
<div class="gc-four-grid">
<article>
<span>01</span>
<h3>Members</h3>
<p>Discover the people building the Game Changers network across Nigeria.</p>
<a class="gc-text-link" href="<?php echo esc_url( gc_page_url( 'members' ) ); ?>">Meet the members →</a>
</article>
<article>
<span>02</span>
<h3>Chapters</h3>
<p>Find a local chapter near you, or start one where none exists yet.</p>
<a class="gc-text-link" href="<?php echo esc_url( gc_page_url( 'find-a-chapter' ) ); ?>">Find a chapter →</a>
</article>
<article>
<span>03</span>
<h3>Mentorship</h3>
<p>Learn directly from experienced leaders and practitioners in your field.</p>
<a class="gc-text-link" href="<?php echo esc_url( gc_page_url( 'mentors' ) ); ?>">Meet the mentors →</a>
</article>
<article>
<span>04</span>
<h3>Impact</h3>
<p>See how members are turning leadership into practical community action.</p>
<a class="gc-text-link" href="<?php echo esc_url( home_url( '/impact/projects/' ) ); ?>">View projects →</a>
</article>
</div>
</div></section>

<section class="gc-section gc-soft"><div class="gc-container">
<div class="gc-section-head">
<div><span class="gc-kicker gc-kicker-green">Stay connected</span><h2>More ways to<br><em>get involved.</em></h2></div>
</div>
<div class="gc-programme-grid">
<article>
<span>ALUMNI</span>
<h3>Alumni Network</h3>
<p>Stay connected with fellow graduates of the Leadership Academy.</p>
<a href="<?php echo esc_url( gc_page_url( 'alumni' ) ); ?>">Explore →</a>
</article>
<article>
<span>LEADERSHIP</span>
<h3>Start a Chapter</h3>
<p>Bring the movement to your state and lead a new local chapter.</p>
<a href="<?php echo esc_url( gc_page_url( 'start-a-chapter' ) ); ?>">Explore →</a>
</article>
<article>
<span>ADVISORY</span>
<h3>Become a Mentor</h3>
<p>Share your expertise and guide the next generation of leaders.</p>
<a href="<?php echo esc_url( gc_page_url( 'mentor-with-us' ) ); ?>">Explore →</a>
</article>
</div>
</div></section>

<section class="gc-section"><div class="gc-container gc-about-cta">
<div><span class="gc-kicker">Ready when you are</span><h2>Join the community<br>and <em>find your people.</em></h2></div>
<div class="gc-actions">
<a class="gc-button gc-button-green" href="<?php echo esc_url( gc_page_url( 'enlistments' ) ); ?>">Join the Movement</a>
<a class="gc-button gc-button-outline" href="<?php echo esc_url( gc_page_url( 'partner-with-us' ) ); ?>">Partner With Us</a>
</div>
</div></section>
</main>
<?php get_footer(); ?>
