const fs = require('fs');
const content = fs.readFileSync('C:\\XAMPP\\htdocs\\EODS\\Master\\qa-traceability (artifact)\\resources\\views\\user\\trans_wip\\index.blade.php', 'utf8');

const regex = /<span[^>]*id="card-1-header"[^>]*>([\s\S]*?)<\/span>/g;
let match;
let count = 1;
while ((match = regex.exec(content)) !== null) {
  const text = match[1].replace(/\s+/g, ' ').trim();
  console.log(`${count++}.: ${text}`);
}
