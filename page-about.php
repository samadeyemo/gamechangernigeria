<?php
get_header(); ?>
<main class="gc-interior gc-about-page">
<section class="gc-page-hero gc-dark"><div class="gc-container">
<span class="gc-kicker">Who we are</span>
<h1>About <em>Game Changers.</em></h1>
<p>A leadership movement helping people learn, connect, lead and turn purpose into practical impact.</p>
</div></section>
<section class="gc-section"><div class="gc-container gc-about-intro">
<div><span class="gc-kicker gc-kicker-green">Our mission</span><h2>We develop people who are ready to <em>lead.</em></h2></div>
<div class="gc-prose">
<?php while(have_posts()):the_post(); if(trim(get_the_content())):the_content(); else: ?>
<p>Game Changers Nigeria exists to build a community of capable, values-driven leaders. We create practical pathways for learning, relationships, service and responsible leadership.</p>
<p>Our work connects leadership development with real communities, real opportunities and real-world projects — so growth does not stop at knowledge.</p>
<?php endif; endwhile; ?>
</div></div></section>
<section class="gc-section gc-soft"><div class="gc-container"><div class="gc-section-head"><div><span class="gc-kicker gc-kicker-green">What guides us</span><h2>How we <em>work.</em></h2></div></div>
<div class="gc-four-grid">
<article><span>01</span><h3>Learn</h3><p>Build the knowledge, judgment and skills required to lead well.</p></article>
<article><span>02</span><h3>Connect</h3><p>Grow through relationships, chapters, mentors and shared experience.</p></article>
<article><span>03</span><h3>Lead</h3><p>Move from ideas to responsible action in your community and organisation.</p></article>
<article><span>04</span><h3>Impact</h3><p>Turn leadership into measurable service, projects and lasting change.</p></article>
</div></div></section>
<section class="gc-section"><div class="gc-container gc-about-cta"><div><span class="gc-kicker">Take the next step</span><h2>Find your place in the <em>movement.</em></h2></div><div class="gc-actions"><a class="gc-button gc-button-green" href="<?php echo esc_url(gc_page_url('enlistments')); ?>">Join the Movement</a><a class="gc-button gc-button-outline" href="<?php echo esc_url(gc_page_url('academy')); ?>">Explore Academy</a></div></div></section>
</main>
<?php get_footer(); ?>
