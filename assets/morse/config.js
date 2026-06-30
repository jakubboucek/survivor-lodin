// Morse code generator – configuration and the single source of truth for defaults.
//
// The output format (sample rate / bit depth / channels) is fixed by the phone
// network (G.711) and is intentionally NOT user-adjustable.
export const SAMPLE_RATE = 8000; // Hz, mono, 16-bit PCM

// One debounce interval for all continuous inputs (sliders/number fields), used by
// autoplay so dragging a slider does not fire a generation on every pixel.
export const DEBOUNCE_MS = 180;

export const STORAGE_KEY = 'morse.settings';
export const DEFAULT_PRESET_NAME = 'Default';

// All adjustable parameters with their slider bounds. `key` is used everywhere
// (state, localStorage, preset blobs, audio rendering).
export const PARAMS = [
    { key: 'freq', label: 'Frekvence tónu', unit: 'Hz', min: 200, max: 2000, step: 10 },
    { key: 'dot', label: 'Délka tečky (základní jednotka)', unit: 'ms', min: 20, max: 1000, step: 5 },
    { key: 'dash', label: 'Délka čárky', unit: 'ms', min: 20, max: 2000, step: 5 },
    { key: 'intra', label: 'Mezera mezi prvky v písmenu', unit: 'ms', min: 0, max: 1000, step: 5 },
    { key: 'letter', label: 'Mezera mezi písmeny', unit: 'ms', min: 0, max: 3000, step: 10 },
    { key: 'word', label: 'Mezera mezi slovy', unit: 'ms', min: 0, max: 5000, step: 10 },
    { key: 'volume', label: 'Hlasitost', unit: '', min: 0, max: 1, step: 0.01 },
    { key: 'fadeIn', label: 'Náběh tónu (fade-in)', unit: 'ms', min: 0, max: 200, step: 1 },
    { key: 'fadeOut', label: 'Doběh tónu (fade-out)', unit: 'ms', min: 0, max: 200, step: 1 },
    { key: 'leadIn', label: 'Úvodní ticho', unit: 'ms', min: 0, max: 5000, step: 10 },
    { key: 'tail', label: 'Koncové ticho', unit: 'ms', min: 0, max: 5000, step: 10 },
];

// The "Default" preset – the only definition of factory values. The initial state
// (when localStorage is empty) and the merge base for loaded presets both derive
// from this, so a preset missing a future key never yields `undefined`.
// Wider letter/word gaps than the standard 3×/7× ratio = Farnsworth timing for kids.
export const DEFAULT_PRESET = {
    freq: 750,
    dot: 100,
    dash: 300,
    intra: 100,
    letter: 500,
    word: 1000,
    volume: 0.8,
    fadeIn: 0,
    fadeOut: 0,
    leadIn: 500,
    tail: 500,
};

/** Clamps a numeric value into a parameter's [min, max] range. */
export function clampParam(key, value) {
    const def = PARAMS.find((p) => p.key === key);
    const num = Number(value);
    if (!Number.isFinite(num)) {
        return DEFAULT_PRESET[key];
    }
    if (!def) {
        return num;
    }
    return Math.min(def.max, Math.max(def.min, num));
}
