const fs = require('fs');
const content = fs.readFileSync('C:\\XAMPP\\htdocs\\EODS\\Master\\qa-traceability (artifact)\\resources\\views\\user\\trans_wip\\index.blade.php', 'utf8');

// Parse HTML line-by-line to extract section comments, card titles, and header badges in sequence.
const lines = content.split('\n');
let currentComment = '';

for (let i = 0; i < lines.length; i++) {
  const line = lines[i].trim();
  
  // Track HTML comments
  if (line.startsWith('<!--') && line.endsWith('-->')) {
    const comment = line.replace('<!--', '').replace('-->', '').trim();
    if (comment.includes('SECTION') || comment.includes('RUNDOWN') || comment.includes('FEED')) {
      console.log(`Line ${i+1} [COMMENT]: ${comment}`);
    }
  }

  // Track card header titles
  const headerMatch = line.match(/id="card-1-header"\s*[^>]*>([\s\S]*?)<\/span>/) || line.match(/class="card-header-title"[^>]*>([\s\S]*?)<\/h/);
  if (headerMatch) {
    const text = headerMatch[1].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    console.log(`Line ${i+1} [HEADER]: ${text}`);
  }

  // Track select/input names that could give context on feeds/rundowns IDs
  if (line.includes('name="feed_id"') || line.includes('name="rundown_id"')) {
    console.log(`Line ${i+1} [INPUT ID]: ${line}`);
  }
}
