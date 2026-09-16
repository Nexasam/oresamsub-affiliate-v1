const fs = require('fs');

const rate = 48000;
const seconds = 62;
const frames = rate * seconds;
const pcm = Buffer.alloc(frames * 2);
const chords = [
  [261.63, 329.63, 392.00], // C
  [220.00, 261.63, 329.63], // Am
  [174.61, 220.00, 261.63], // F
  [196.00, 246.94, 293.66], // G
];
const melody = [523.25, 659.25, 783.99, 659.25, 440.00, 523.25, 659.25, 523.25,
                349.23, 440.00, 523.25, 440.00, 392.00, 493.88, 587.33, 493.88];
let seed = 1731;
const noise = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 2147483648) - 1;

for (let i = 0; i < frames; i++) {
  const t = i / rate;
  const beat = t * 2; // 120 BPM
  const beatPhase = beat % 1;
  const chord = chords[Math.floor(t / 2) % chords.length];
  let x = 0;

  // Warm, gently pulsing chord bed.
  const pulse = 0.58 + 0.42 * Math.max(0, Math.sin(Math.PI * beatPhase));
  for (const f of chord) x += 0.055 * pulse * Math.sin(2 * Math.PI * f * t);

  // Bright eighth-note arpeggio.
  const step = Math.floor(t * 4);
  const note = melody[step % melody.length];
  const stepPhase = (t * 4) % 1;
  const env = Math.exp(-4.8 * stepPhase);
  x += 0.075 * env * Math.sin(2 * Math.PI * note * t);
  x += 0.025 * env * Math.sin(2 * Math.PI * note * 2 * t);

  // Firm commercial-pop kick on every beat.
  if (beatPhase < 0.22) {
    const k = beatPhase / 0.22;
    x += 0.48 * Math.exp(-5.5 * k) * Math.sin(2 * Math.PI * (112 - 58 * k) * t);
    x += 0.10 * Math.exp(-18 * k) * noise();
  }

  // Snare/clap on beats two and four.
  const beatIndex = Math.floor(beat) % 4;
  if ((beatIndex === 1 || beatIndex === 3) && beatPhase < 0.14) {
    x += 0.17 * Math.exp(-12 * beatPhase) * noise();
    x += 0.07 * Math.exp(-18 * beatPhase) * Math.sin(2 * Math.PI * 190 * t);
  }

  // Light hi-hat on eighth notes.
  const eighthPhase = (t * 4) % 1;
  if (eighthPhase < 0.055) x += 0.075 * Math.exp(-35 * eighthPhase) * noise();

  // Bass pulse locks the harmony to the kick.
  const bassEnv = Math.exp(-3.2 * beatPhase);
  x += 0.14 * bassEnv * Math.sin(2 * Math.PI * (chord[0] / 2) * t);

  const fadeIn = Math.min(1, t / 0.8);
  const fadeOut = Math.min(1, (seconds - t) / 1.2);
  const sample = Math.max(-1, Math.min(1, x * fadeIn * fadeOut));
  pcm.writeInt16LE(Math.round(sample * 32767), i * 2);
}

const header = Buffer.alloc(44);
header.write('RIFF', 0); header.writeUInt32LE(36 + pcm.length, 4); header.write('WAVE', 8);
header.write('fmt ', 12); header.writeUInt32LE(16, 16); header.writeUInt16LE(1, 20);
header.writeUInt16LE(1, 22); header.writeUInt32LE(rate, 24); header.writeUInt32LE(rate * 2, 28);
header.writeUInt16LE(2, 32); header.writeUInt16LE(16, 34); header.write('data', 36);
header.writeUInt32LE(pcm.length, 40);
fs.writeFileSync(__dirname + '/upbeat-tech-bed.wav', Buffer.concat([header, pcm]));
