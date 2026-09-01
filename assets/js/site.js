// KSC Coaching — shared site behavior: nav, footer year, accordion, FAQ, help widget

document.addEventListener('DOMContentLoaded', () => {
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Mobile nav toggle
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });
    navLinks.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  // Mark active nav link
  const path = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a[data-nav]').forEach((a) => {
    if (a.getAttribute('data-nav') === path) a.classList.add('active');
  });

  // Accordion (malla)
  document.querySelectorAll('.accordion-trigger').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const item = trigger.closest('.accordion-item');
      const wasOpen = item.classList.contains('open');
      item.parentElement.querySelectorAll('.accordion-item').forEach((i) => i.classList.remove('open'));
      if (!wasOpen) item.classList.add('open');
    });
  });

  // Open first accordion item by default
  const firstAccordion = document.querySelector('.accordion-item');
  if (firstAccordion) firstAccordion.classList.add('open');

  // FAQ toggles (help widget + any page FAQ)
  document.querySelectorAll('.help-faq-q').forEach((q) => {
    q.addEventListener('click', () => {
      q.closest('.help-faq-item').classList.toggle('open');
    });
  });

  // Floating help widget
  const helpFab = document.querySelector('.help-fab');
  const helpPanel = document.querySelector('.help-panel');
  if (helpFab && helpPanel) {
    helpFab.addEventListener('click', () => {
      helpPanel.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!helpPanel.contains(e.target) && !helpFab.contains(e.target)) {
        helpPanel.classList.remove('open');
      }
    });
  }
});
