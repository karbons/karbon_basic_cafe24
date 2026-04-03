const fs = require('fs');
let html = fs.readFileSync('build/index.html', 'utf8');
html = html.replace(/href="\/(_app\/)/g, 'href="/main/$1');
html = html.replace(/src="\/(_app\/)/g, 'src="/main/$1');
html = html.replace(/import\("\/(_app\/)/g, 'import("/main/$1');
html = html.replace(/base: ""/g, 'base: "/main"');
fs.writeFileSync('build/index.html', html);
console.log('Fixed paths in index.html');
