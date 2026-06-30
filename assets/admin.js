import './css/admin.css';

// Nette Forms enhancement (admin will use forms).
import netteForms from 'nette-forms';

netteForms.initOnLoad();

// Morse code generator – only loaded on its own page (Admin\Morse), so the heavy
// Web Audio code stays out of the rest of the admin.
const morseRoot = document.getElementById('morse-app');
if (morseRoot) {
    import('./morse/index.js').then(({ initMorse }) => initMorse(morseRoot));
}
