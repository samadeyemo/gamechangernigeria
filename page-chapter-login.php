<?php
if(!defined('DONOTCACHEPAGE')){define('DONOTCACHEPAGE',true);} nocache_headers();
if(is_user_logged_in()&&gc_chapter_leader_allowed()){wp_safe_redirect(gc_page_url('chapter-dashboard'));exit;}get_header();?>
<main class="gc-chapter-login-page"><div class="gc-container gc-chapter-login-grid">
<section class="gc-chapter-login-intro"><span class="gc-kicker">Chapter leaders</span><h1>Lead your chapter<br><em>with purpose.</em></h1><p>Approved chapter leaders use this private space to manage their chapter information, community link and learning resources.</p><div class="gc-chapter-login-links"><a href="<?php echo esc_url(gc_page_url('start-a-chapter')); ?>">Start a Chapter →</a><a href="<?php echo esc_url(gc_page_url('find-a-chapter')); ?>">Find a Chapter →</a></div><div class="gc-login-diamonds" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></section>
<section class="gc-chapter-login-card"><span class="gc-login-card-label">CHAPTER LEADER LOGIN</span><h2>Welcome back.</h2><p>Use the email or username connected to your approved chapter account.</p>
<?php wp_login_form(array('redirect'=>gc_page_url('chapter-dashboard'),'label_username'=>'Email or Username','label_password'=>'Password','label_log_in'=>'Log in to Chapter Dashboard','remember'=>true)); ?>
<div class="gc-chapter-login-foot"><span>Not approved yet?</span> <a href="<?php echo esc_url(gc_page_url('start-a-chapter')); ?>">Apply to start a chapter</a></div></section>
</div></main><?php get_footer();?>
