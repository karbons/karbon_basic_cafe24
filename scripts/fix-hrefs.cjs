const fs = require('fs');
const path = require('path');

function processDir(dir, appName) {
    const files = fs.readdirSync(dir, { withFileTypes: true });
    
    for (const file of files) {
        const fullPath = path.join(dir, file.name);
        
        if (file.isDirectory()) {
            processDir(fullPath, appName);
        } else if (file.name.endsWith('.svelte')) {
            processFile(fullPath, appName);
        }
    }
}

function processFile(filePath, appName) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;
    
    // Check if already imports base
    const hasBaseImport = content.includes("import { base }") || content.includes("import { base, ");
    
    // Add import if not present and file has href=" or {base}/
    if (!hasBaseImport && (content.includes('href="/') || content.includes('href="{base}/'))) {
        // Find the last import statement (indented or not)
        const importMatch = content.match(/^\s*import .+$/gm);
        if (importMatch) {
            const lastImport = importMatch[importMatch.length - 1];
            const insertPos = content.indexOf(lastImport) + lastImport.length;
            content = content.slice(0, insertPos) + '\nimport { base } from \'$app/paths\';' + content.slice(insertPos);
            modified = true;
        }
    }
    
    // Replace href="/..." with href="{base}/..." (but not if already has {base})
    content = content.replace(/href="\/([^"]+)"/g, (match, p1) => {
        if (p1.startsWith('{')) return match; // Already has variable
        if (p1.startsWith('#')) return match; // Fragment
        modified = true;
        return `href="{base}/${p1}"`;
    });
    
    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Modified: ${filePath.replace('/Users/imjongpil/works/Karbon/projects/karbon_basic_cafe24/', '')}`);
    }
}

// Process all apps
processDir('/Users/imjongpil/works/Karbon/projects/karbon_basic_cafe24/frontend/app/src', 'app');
processDir('/Users/imjongpil/works/Karbon/projects/karbon_basic_cafe24/frontend/main/src', 'main');
processDir('/Users/imjongpil/works/Karbon/projects/karbon_basic_cafe24/frontend/admin/src', 'admin');

console.log('Done!');
