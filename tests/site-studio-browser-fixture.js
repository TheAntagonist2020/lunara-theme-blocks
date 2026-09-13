'use strict';
const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const themeRoot = path.resolve(__dirname, '..');
const cssPath = path.join(themeRoot, 'assets/css/lunara-site-studio.css');
const jsPath = path.join(themeRoot, 'assets/js/lunara-site-studio.js');
const css = fs.existsSync(cssPath) ? fs.readFileSync(cssPath, 'utf8') : '';
const editorCss = fs.readFileSync(path.join(themeRoot, 'assets/css/lunara-editor-controls.css'), 'utf8');
const controller = fs.existsSync(jsPath) ? fs.readFileSync(jsPath, 'utf8') : '';
const editorControls = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-editor-controls.js'), 'utf8');
const previewBridge = fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-preview.js'), 'utf8');

/** Real PHP workspace markup with the shared admin chrome and controller assets. */
function fixture(surface, controllerSource, controlsSource) {
 const result = spawnSync('php', [path.join(__dirname, 'site-studio-runtime.php'), `--fixture=${surface}`], { encoding: 'utf8' });
 if (result.error || result.status !== 0) throw result.error || new Error(result.stderr);
 const adminCss = '<style>#wpcontent{margin-left:160px}#wpbody-content{min-width:0;padding-bottom:40px}@media(max-width:782px){#wpcontent{margin-left:0}}</style>';
 const usesOrderedList = ['homepage-structure', 'reviews-archive', 'journal-archive', 'lunara-method', 'oscars-portal'].includes(surface);
 const sharedSource = typeof controlsSource === 'undefined' ? editorControls : controlsSource;
 return result.stdout
  .replace('</head>', `<style>${css}</style>${usesOrderedList ? `<style>${editorCss}</style>` : ''}${adminCss}</head>`)
  .replace('<body class="wp-admin">', '<body class="wp-admin"><div id="wpwrap"><div id="wpcontent"><div id="wpbody"><div id="wpbody-content">')
  .replace('</body>', '</div></div></div></div>'
   + (usesOrderedList ? '<script>' + sharedSource + '</script>' : '')
   + (surface === 'lunara-method' ? '<script>' + fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-method.js'), 'utf8') + '</script>' : '')
   + (surface === 'reviews-archive' || surface === 'journal-archive' ? '<script>' + fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-archive-media.js'), 'utf8') + '</script><script>' + fs.readFileSync(path.join(themeRoot, 'assets/js/lunara-site-studio-archive-selection.js'), 'utf8') + '</script>' : '')
   + '<script>' + (controllerSource || controller) + '</script></body>');
}

module.exports = { fixture, controller, previewBridge };
