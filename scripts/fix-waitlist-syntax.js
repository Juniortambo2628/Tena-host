const fs = require('fs');
const p = 'resources/js/Components/Welcome/WaitlistModal.jsx';
let c = fs.readFileSync(p, 'utf8');

const broken = '<span>{opt</span>';
const fixed = '<span>{opt</span>';

console.log('Looking for:', JSON.stringify(broken));
console.log('Replacing with:', JSON.stringify(fixed));
console.log('Contains broken:', c.includes(broken));

const idx = c.indexOf(broken);
if (idx !== -1) {
  c = c.substring(0, idx) + fixed + c.substring(idx + broken.length);
  fs.writeFileSync(p, c);
  console.log('Fixed at index', idx);
} else {
  // Show what's around the area
  const lines = c.split('\n');
  for (let i = 263; i < 270; i++) {
    console.log(i + 1 + ': ' + JSON.stringify(lines[i]));
  }
}
