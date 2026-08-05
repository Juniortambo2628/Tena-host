const fs = require('fs');
const p = 'resources/js/Components/Welcome/WaitlistModal.jsx';
const buf = fs.readFileSync(p);
let content = buf.toString('utf8');

const rb = String.fromCharCode(125); // }
const lt = String.fromCharCode(60);  // <
const sl = String.fromCharCode(47);  // /
const sp = String.fromCharCode(32);  // space

const targets = ['units', 'primary_platform', 'biggest_challenge'];

let count = 0;
for (const field of targets) {
  // broken: {formData.UNITS</span>
  // correct: {formData.UNITS}</span>
  const brokenNeedle = '{formData.' + field + lt + sl + 'span>';
  const fixInsert = '{formData.' + field + rb + lt + sl + 'span>';

  const idx = content.indexOf(brokenNeedle);
  if (idx !== -1) {
    content = content.substring(0, idx) + fixInsert + content.substring(idx + brokenNeedle.length);
    count++;
  }
}

fs.writeFileSync(p, content, 'utf8');
console.log('Fixed ' + count + ' occurrences');
