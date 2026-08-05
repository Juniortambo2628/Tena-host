const fs = require('fs');
const p = 'resources/js/Components/Welcome/WaitlistModal.jsx';
let content = fs.readFileSync(p, 'utf8');

const fields = ['units', 'primary_platform', 'biggest_challenge'];
let totalFixed = 0;

for (const field of fields) {
  const fieldPart = '{formData.' + field;
  const brokenNeedle = fieldPart +</span>';
  const correctNeedle = fieldPart +</span>';

  let pos = 0;
  while ((pos = content.indexOf(brokenNeedle, pos)) !== -1) {
    const before = content.substring(0, pos);
    const after = content.substring(pos + brokenNeedle.length);
    content = before + correctNeedle + after;
    totalFixed++;
    pos += correctNeedle.length;
  }
}

fs.writeFileSync(p, content, 'utf8');
console.log('Fixed ' + totalFixed + ' occurrences');
