(function () {
  var textarea = document.getElementById('content');
  if (!textarea || typeof tinymce === 'undefined') return;

  var html = document.documentElement;
  var theme = html.getAttribute('data-bs-theme');
  if (!theme) {
    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  var isDark = theme === 'dark';

  tinymce.init({
    selector: '#content',
    base_url: window.TINYMCE_ASSET_BASE,
    suffix: '.min',
    // Self-hosted (not Tiny Cloud) — 'gpl' declares free/open-source use
    // under TinyMCE's GPL license, which this deployment qualifies for.
    // Without it, TinyMCE 6+ shows a "license key not provided" banner
    // and disables editing entirely.
    license_key: 'gpl',
    height: 480,
    menubar: false,
    skin: isDark ? 'oxide-dark' : 'oxide',
    content_css: isDark ? 'dark' : 'default',
    plugins: 'lists link image table code autolink wordcount advlist charmap searchreplace visualblocks fullscreen preview help',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | ' +
             'bullist numlist | link image table | alignleft aligncenter alignright | ' +
             'code preview fullscreen',
    branding: false,
    promotion: false,
    convert_urls: false,
    relative_urls: false,
  });
})();
