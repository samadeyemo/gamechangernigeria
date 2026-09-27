<?php
get_header(); ?>
<main class="gc-page gc-onboarding">
    <section class="gc-hero">
        <div class="gc-container">
            <p class="gc-eyebrow">START YOUR JOURNEY</p>
            <h1>Become a Game Changer.</h1>
            <p>Join the community, discover your chapter, develop your leadership and turn your ideas into impact.</p>
            <div class="gc-actions">
                <a class="gc-btn gc-btn-primary" href="<?php echo esc_url(home_url('/enlistments/')); ?>">Join the Movement</a>
                <a class="gc-btn gc-btn-secondary" href="<?php echo esc_url(home_url('/academy/')); ?>">Explore the Academy</a>
            </div>
        </div>
    </section>
    <section class="gc-container gc-journey-grid">
        <article><span>01</span><h2>Join</h2><p>Create your community profile.</p></article>
        <article><span>02</span><h2>Discover</h2><p>Find your chapter and opportunities.</p></article>
        <article><span>03</span><h2>Develop</h2><p>Learn through the Leadership Academy.</p></article>
        <article><span>04</span><h2>Lead</h2><p>Build projects and create measurable impact.</p></article>
    </section>
</main>
<?php get_footer(); ?>
