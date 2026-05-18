const fs = require('fs');
const path = require('path');

const colorsToReplace = ['emerald', 'teal', 'indigo', 'purple', 'orange', 'amber'];

function walk(dir, callback) {
  fs.readdirSync(dir).forEach( f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walk(dirPath, callback) : callback(path.join(dir, f));
  });
};

walk('c:/XAMPP/htdocs/EODS/Master/frontend/src', (filePath) => {
  if (filePath.endsWith('.vue')) {
    let content = fs.readFileSync(filePath, 'utf8');
    let newContent = content;
    
    colorsToReplace.forEach(color => {
      const regex = new RegExp(color, 'g');
      newContent = newContent.replace(regex, 'green');
    });

    if (content !== newContent) {
      fs.writeFileSync(filePath, newContent, 'utf8');
      console.log('Updated:', filePath);
    }
  }
});
