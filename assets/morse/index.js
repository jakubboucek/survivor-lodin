// Morse code generator – UI controller. Wires the parameter panel, text input,
// preset picker and native <audio> player together; handles autoplay, debouncing,
// localStorage persistence and the preset JSON API.

import {
    PARAMS,
    DEFAULT_PRESET,
    DEFAULT_PRESET_NAME,
    DEBOUNCE_MS,
    STORAGE_KEY,
    clampParam,
} from './config.js';
import { textToMorse } from './text.js';
import { renderMorseWav } from './audio.js';
import { createPresetApi } from './presets-api.js';

const DEFAULT_VALUE = 'default'; // <select> value of the read-only Default preset

export function initMorse(root) {
    const api = createPresetApi({
        list: root.dataset.urlList,
        load: root.dataset.urlLoad,
        save: root.dataset.urlSave,
        create: root.dataset.urlCreate,
    });

    const els = {
        params: root.querySelector('#morse-params'),
        text: root.querySelector('#morse-text'),
        generate: root.querySelector('#morse-generate'),
        error: root.querySelector('#morse-error'),
        status: root.querySelector('#morse-status'),
        audio: root.querySelector('#morse-audio'),
        download: root.querySelector('#morse-download'),
        autoplay: root.querySelector('#morse-autoplay'),
        select: root.querySelector('#morse-preset-select'),
        load: root.querySelector('#morse-preset-load'),
        save: root.querySelector('#morse-preset-save'),
        saveAs: root.querySelector('#morse-preset-saveas'),
    };

    const state = loadState();
    const controls = {}; // key → { range, number }
    let currentUrl = null;
    let genToken = 0;
    let debounceTimer = null;
    let statusTimer = null;

    buildParamControls();
    syncControlsFromState();
    els.autoplay.checked = state.autoplay;
    els.text.value = state.text;

    bindEvents();
    refreshPresets(state.presetValue);

    // ---- state / persistence -------------------------------------------------

    function loadState() {
        let stored = {};
        try {
            stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch {
            stored = {};
        }
        return {
            params: mergeParams(stored.params),
            autoplay: stored.autoplay ?? false,
            presetValue: stored.presetValue ?? DEFAULT_VALUE,
            text: stored.text ?? '',
        };
    }

    // Always merge over Default so missing keys (e.g. a future parameter) never
    // become undefined; values are clamped to their slider range.
    function mergeParams(partial) {
        const merged = {};
        for (const { key } of PARAMS) {
            const raw = partial && partial[key] !== undefined ? partial[key] : DEFAULT_PRESET[key];
            merged[key] = clampParam(key, raw);
        }
        return merged;
    }

    function saveState() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    // ---- parameter controls --------------------------------------------------

    function buildParamControls() {
        for (const def of PARAMS) {
            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center gap-3';

            const label = document.createElement('label');
            label.className = 'w-full sm:w-64 shrink-0 text-sm';
            label.textContent = def.unit ? `${def.label} (${def.unit})` : def.label;

            const range = document.createElement('input');
            range.type = 'range';
            range.className = 'range range-sm grow';
            applyBounds(range, def);

            const number = document.createElement('input');
            number.type = 'number';
            number.className = 'input input-bordered input-sm w-24 font-mono';
            applyBounds(number, def);

            range.addEventListener('input', () => {
                number.value = range.value;
                updateParam(def.key, range.value);
            });
            number.addEventListener('input', () => {
                range.value = number.value;
                updateParam(def.key, number.value);
            });
            // On commit, snap a typed value back into range.
            number.addEventListener('change', () => {
                const clamped = clampParam(def.key, number.value);
                number.value = clamped;
                range.value = clamped;
                state.params[def.key] = clamped;
                saveState();
            });

            row.append(label, range, number);
            els.params.appendChild(row);
            controls[def.key] = { range, number };
        }
    }

    function applyBounds(input, def) {
        input.min = String(def.min);
        input.max = String(def.max);
        input.step = String(def.step);
    }

    function updateParam(key, rawValue) {
        state.params[key] = clampParam(key, rawValue);
        saveState();
        scheduleAutoplay();
    }

    function syncControlsFromState() {
        for (const { key } of PARAMS) {
            const value = state.params[key];
            controls[key].range.value = value;
            controls[key].number.value = value;
        }
    }

    // ---- events --------------------------------------------------------------

    function bindEvents() {
        els.generate.addEventListener('click', () => {
            // Manual generation is an explicit user gesture → also play.
            generate({ play: true });
        });

        // Text changes never trigger autoplay – only the Generate button does.
        els.text.addEventListener('input', () => {
            state.text = els.text.value;
            saveState();
        });

        els.autoplay.addEventListener('change', () => {
            state.autoplay = els.autoplay.checked;
            saveState();
        });

        els.select.addEventListener('change', () => {
            state.presetValue = els.select.value;
            saveState();
            updateSaveButton();
        });

        els.load.addEventListener('click', onLoadPreset);
        els.save.addEventListener('click', onSavePreset);
        els.saveAs.addEventListener('click', onSaveAsPreset);
    }

    // ---- generation ----------------------------------------------------------

    function scheduleAutoplay() {
        if (!state.autoplay) {
            return;
        }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => generate({ play: true }), DEBOUNCE_MS);
    }

    async function generate({ play }) {
        clearError();

        let words;
        try {
            words = textToMorse(state.text);
        } catch (e) {
            showError(e.message);
            return;
        }

        const token = ++genToken;
        els.audio.pause(); // a new generation interrupts current playback

        let result;
        try {
            result = await renderMorseWav(words, state.params);
        } catch (e) {
            showError(`Generování zvuku selhalo: ${e.message}`);
            return;
        }
        if (token !== genToken) {
            return; // superseded by a newer generation
        }

        if (currentUrl) {
            URL.revokeObjectURL(currentUrl); // free the previous Blob URL
        }
        currentUrl = URL.createObjectURL(result.blob);
        els.audio.src = currentUrl;
        els.download.href = currentUrl;
        els.download.classList.remove('hidden');

        if (play) {
            els.audio.play().catch(() => {
                /* autoplay may be blocked before the first interaction – ignore */
            });
        }
    }

    function maybeAutoplay() {
        if (state.autoplay) {
            generate({ play: true });
        }
    }

    // ---- presets -------------------------------------------------------------

    async function refreshPresets(selectValue) {
        let list = [];
        try {
            list = await api.list();
        } catch {
            // Server may be unreachable; the Default preset still works offline.
        }
        buildPresetOptions(list, selectValue);
    }

    function buildPresetOptions(list, selectValue) {
        els.select.replaceChildren();

        const optDefault = document.createElement('option');
        optDefault.value = DEFAULT_VALUE;
        optDefault.textContent = DEFAULT_PRESET_NAME;
        els.select.appendChild(optDefault);

        for (const preset of list) {
            const option = document.createElement('option');
            option.value = String(preset.id);
            option.textContent = preset.name; // textContent escapes the raw name
            els.select.appendChild(option);
        }

        const wanted = selectValue ?? state.presetValue;
        const exists = [...els.select.options].some((o) => o.value === wanted);
        els.select.value = exists ? wanted : DEFAULT_VALUE;
        state.presetValue = els.select.value;
        saveState();
        updateSaveButton();
    }

    function updateSaveButton() {
        // Default is read-only: "Uložit" falls back to "Uložit jako…".
        els.save.disabled = els.select.value === DEFAULT_VALUE;
    }

    function applyParams(params) {
        state.params = mergeParams(params);
        syncControlsFromState();
        saveState();
    }

    async function onLoadPreset() {
        clearError();
        const value = els.select.value;

        if (value === DEFAULT_VALUE) {
            applyParams(DEFAULT_PRESET);
            state.presetValue = DEFAULT_VALUE;
            saveState();
            showStatus('Načten preset „Default“.');
            maybeAutoplay();
            return;
        }

        try {
            const preset = await api.load(value);
            applyParams(preset.data);
            state.presetValue = String(preset.id);
            saveState();
            showStatus(`Načten preset „${preset.name}“.`);
            maybeAutoplay();
        } catch (e) {
            showError(e.message);
        }
    }

    async function onSavePreset() {
        clearError();
        const value = els.select.value;
        if (value === DEFAULT_VALUE) {
            return onSaveAsPreset();
        }

        try {
            await api.save(value, state.params);
            showStatus('Preset uložen.');
        } catch (e) {
            showError(e.message);
        }
    }

    async function onSaveAsPreset() {
        clearError();
        const name = prompt('Název nového presetu:');
        if (name === null) {
            return;
        }
        const trimmed = name.trim();
        if (trimmed === '') {
            showError('Zadej název presetu.');
            return;
        }

        try {
            const created = await api.create(trimmed, state.params);
            state.presetValue = String(created.id);
            saveState();
            await refreshPresets(String(created.id));
            showStatus(`Preset uložen jako „${created.name}“.`);
        } catch (e) {
            showError(e.message);
        }
    }

    // ---- messages ------------------------------------------------------------

    function showError(message) {
        els.error.textContent = message;
        els.error.classList.remove('hidden');
    }

    function clearError() {
        els.error.classList.add('hidden');
    }

    function showStatus(message) {
        els.status.textContent = message;
        els.status.classList.remove('hidden');
        clearTimeout(statusTimer);
        statusTimer = setTimeout(() => els.status.classList.add('hidden'), 4000);
    }
}
