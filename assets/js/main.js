/**
 * Game Changers Nigeria — front-end scripts
 * Mobile menu is handled inline in header.php (more reliable with WP caching).
 * This file is reserved for non-critical enhancements.
 */
(function () {
  'use strict';

  // Smooth scroll for same-page anchors
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    var id = a.getAttribute('href');
    if (!id || id === '#') return;
    var target = document.querySelector(id);
    if (!target) return;
    a.addEventListener('click', function (e) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
})();
