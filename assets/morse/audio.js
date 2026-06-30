// Morse → WAV rendering. Tones are generated with the Web Audio API via an
// OfflineAudioContext fixed at 8 kHz, then encoded to 16-bit mono PCM WAV by hand
// (no library). One oscillator per tone segment guarantees every tone starts at
// the same sine phase, so the "click" at each onset is consistent.

import { SAMPLE_RATE } from './config.js';

/**
 * Builds the tone timeline (in ms) from words → letters → Morse codes.
 * @returns {{events: {start: number, dur: number}[], totalMs: number}}
 */
function buildTimeline(words, p) {
    const events = [];
    let t = p.leadIn;

    words.forEach((letters, wi) => {
        letters.forEach((code, li) => {
            for (let si = 0; si < code.length; si++) {
                const dur = code[si] === '.' ? p.dot : p.dash;
                events.push({ start: t, dur });
                t += dur;
                if (si < code.length - 1) {
                    t += p.intra; // gap between elements inside a letter
                }
            }
            if (li < letters.length - 1) {
                t += p.letter; // gap between letters
            }
        });
        if (wi < words.length - 1) {
            t += p.word; // gap between words
        }
    });

    t += p.tail;
    return { events, totalMs: t };
}

/** Schedules a gain envelope for one tone: fade-in → hold at amp → fade-out. */
function scheduleEnvelope(gain, start, end, amp, fadeIn, fadeOut) {
    const dur = end - start;
    // Never let the fades overlap; scale them down proportionally if they would.
    if (fadeIn + fadeOut > dur && fadeIn + fadeOut > 0) {
        const scale = dur / (fadeIn + fadeOut);
        fadeIn *= scale;
        fadeOut *= scale;
    }

    const peakAt = start + fadeIn;
    gain.setValueAtTime(0, start);
    if (fadeIn > 0) {
        gain.linearRampToValueAtTime(amp, peakAt);
    } else {
        gain.setValueAtTime(amp, start); // sharp onset = rhythmic click
    }

    if (fadeOut > 0) {
        const foAt = end - fadeOut;
        if (foAt > peakAt) {
            gain.setValueAtTime(amp, foAt);
        }
        gain.linearRampToValueAtTime(0, end);
    } else {
        gain.setValueAtTime(amp, end); // hard cut
    }
}

/**
 * Renders the given words to a WAV Blob.
 * @returns {Promise<{blob: Blob, durationMs: number}>}
 */
export async function renderMorseWav(words, p) {
    const { events, totalMs } = buildTimeline(words, p);
    const length = Math.max(1, Math.ceil((totalMs / 1000) * SAMPLE_RATE));
    const ctx = new OfflineAudioContext(1, length, SAMPLE_RATE);
    const amp = Math.min(1, Math.max(0, p.volume));

    for (const ev of events) {
        const osc = ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = p.freq;

        const gain = ctx.createGain();
        osc.connect(gain).connect(ctx.destination);

        const start = ev.start / 1000;
        const end = (ev.start + ev.dur) / 1000;
        scheduleEnvelope(gain.gain, start, end, amp, p.fadeIn / 1000, p.fadeOut / 1000);

        osc.start(start);
        osc.stop(end);
    }

    const buffer = await ctx.startRendering();
    const blob = encodeWav(buffer.getChannelData(0), SAMPLE_RATE);
    return { blob, durationMs: totalMs };
}

/** Encodes a mono Float32 sample buffer to a 16-bit PCM WAV Blob. */
function encodeWav(samples, sampleRate) {
    const numSamples = samples.length;
    const bytesPerSample = 2;
    const buffer = new ArrayBuffer(44 + numSamples * bytesPerSample);
    const view = new DataView(buffer);

    const writeString = (offset, str) => {
        for (let i = 0; i < str.length; i++) {
            view.setUint8(offset + i, str.charCodeAt(i));
        }
    };

    writeString(0, 'RIFF');
    view.setUint32(4, 36 + numSamples * bytesPerSample, true);
    writeString(8, 'WAVE');
    writeString(12, 'fmt ');
    view.setUint32(16, 16, true); // PCM fmt chunk size
    view.setUint16(20, 1, true); // audio format = PCM
    view.setUint16(22, 1, true); // mono
    view.setUint32(24, sampleRate, true);
    view.setUint32(28, sampleRate * bytesPerSample, true); // byte rate (mono)
    view.setUint16(32, bytesPerSample, true); // block align (mono)
    view.setUint16(34, 16, true); // bits per sample
    writeString(36, 'data');
    view.setUint32(40, numSamples * bytesPerSample, true);

    let offset = 44;
    for (let i = 0; i < numSamples; i++) {
        const s = Math.min(1, Math.max(-1, samples[i]));
        view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7fff, true);
        offset += bytesPerSample;
    }

    return new Blob([buffer], { type: 'audio/wav' });
}
