const fs = require('fs');
const path = require('path');

const mappings = {
  '@/stores/auth': '@/modules/auth/stores',
  '@/stores/adminUsers': '@/modules/admin/stores',
  '@/stores/dashboard.js': '@/modules/dashboard/stores',
  '@/stores/dashboard': '@/modules/dashboard/stores',
  '@/stores/plantSelection': '@/modules/plant/stores',
  '@/stores/setupPlant': '@/modules/plant/stores',
  '@/stores/setupManufacturer': '@/modules/manufacturer/stores',
  '@/stores/setupMaterial': '@/modules/material/stores',
  '@/stores/materialStore': '@/modules/material/stores/materialStore',
  '@/stores/setupStorage': '@/modules/storage/stores',
  '@/stores/setupSupplier': '@/modules/supplier/stores',
  '@/stores/setupTank': '@/modules/tank/stores',
  '@/stores/transactionRmEntry': '@/modules/transaction/stores',
  '@/stores/transactionTransfer': '@/modules/transaction/stores',
  '@/api/auth': '@/modules/auth/api',
  '@/api/dashboard': '@/modules/dashboard/api',
  '@/api/setupMaterial': '@/modules/material/api',
  '@/api/setupSupplier': '@/modules/supplier/api',
  '@/api/transactionRmEntry': '@/modules/transaction/api',
  '@/api/setupPlant': '@/modules/plant/api',
  '@/api/setupManufacturer': '@/modules/manufacturer/api',
  '@/api/setupStorage': '@/modules/storage/api',
  '@/api/setupTank': '@/modules/tank/api',
  '@/api/transactionTransfer': '@/modules/transaction/api',
  '@/api/adminUsers': '@/modules/admin/api',
  '@/api/inquiries': '@/modules/inquiry/api',
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
    // Regex matches oldPath enclosed in single or double quotes
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
    console.log(`Updated: ${file}`);
  }
});

console.log(`Finished. Updated ${changedCount} files.`);
