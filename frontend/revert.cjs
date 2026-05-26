const fs = require('fs');
const path = require('path');

const mappings = {
  '@/modules/auth/stores': '@/stores/auth',
  '@/modules/plant/stores': '@/stores/plant'
};

function walkDir(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat && stat.isDirectory()) {
      if (!['node_modules', '.git', 'dist'].includes(file)) {
        results = results.concat(walkDir(filePath));
      }
    } else if (file.endsWith('.js') || file.endsWith('.vue') || file.endsWith('.ts')) {
      results.push(filePath);
    }
  });
  return results;
}

const files = walkDir(path.join(__dirname, 'src'));
let changedCount = 0;

files.forEach(file => {
  let content = fs.readFileSync(file, 'utf8');
  let changed = false;
  
  for (const [oldPath, newPath] of Object.entries(mappings)) {
    const escapedOldPath = oldPath.split('/').join('\\/');
    const regex = new RegExp(`(['"\`])${escapedOldPath}(['"\`])`, 'g');
    if (regex.test(content)) {
      content = content.replace(regex, `$1${newPath}$2`);
      changed = true;
    }
  }

  if (changed) {
    fs.writeFileSync(file, content, 'utf8');
    changedCount++;
    console.log(`Reverted: ${file}`);
  }
});

console.log(`Finished. Reverted ${changedCount} files.`);
