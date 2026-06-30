// Text → Morse conversion.
//
// Pipeline: strip diacritics (á→a, ř→r, …) via Unicode NFD decomposition, uppercase,
// collapse all whitespace runs to a single word separator, then map each character.
// Letters A–Z and digits 0–9 have codes; basic-ASCII punctuation is ignored; anything
// else (e.g. emoji) is an error – the caller surfaces it and skips generation.

const MORSE = {
    A: '.-', B: '-...', C: '-.-.', D: '-..', E: '.', F: '..-.', G: '--.',
    H: '....', I: '..', J: '.---', K: '-.-', L: '.-..', M: '--', N: '-.',
    O: '---', P: '.--.', Q: '--.-', R: '.-.', S: '...', T: '-', U: '..-',
    V: '...-', W: '.--', X: '-..-', Y: '-.--', Z: '--..',
    0: '-----', 1: '.----', 2: '..---', 3: '...--', 4: '....-',
    5: '.....', 6: '-....', 7: '--...', 8: '---..', 9: '----.',
};

/**
 * Converts input text to a list of words, each a list of Morse codes (one per
 * character). Throws an Error (with a Czech message) on empty input or an
 * unconvertible character.
 *
 * @param {string} input
 * @returns {string[][]} words → letters → Morse code
 */
export function textToMorse(input) {
    const normalized = input
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // drop combining diacritical marks
        .toUpperCase()
        .replace(/\s+/g, ' ')
        .trim();

    if (normalized === '') {
        throw new Error('Zadej text k převedení.');
    }

    const words = [];
    let current = [];

    for (const ch of normalized) {
        if (ch === ' ') {
            if (current.length) {
                words.push(current);
                current = [];
            }
            continue;
        }

        const code = MORSE[ch];
        if (code !== undefined) {
            current.push(code);
            continue;
        }

        // Basic-ASCII punctuation (printable, code ≤ 0x7E) is silently ignored;
        // anything beyond that (emoji, leftover symbols) is a hard error.
        if (ch.codePointAt(0) <= 0x7e) {
            continue;
        }

        throw new Error(`Znak „${ch}“ nelze převést do Morseovy abecedy.`);
    }

    if (current.length) {
        words.push(current);
    }

    if (words.length === 0) {
        throw new Error('Text neobsahuje žádné znaky převoditelné do Morseovy abecedy.');
    }

    return words;
}
