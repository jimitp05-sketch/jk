import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.join(__dirname, '..');

const cssFiles = [
    'styles.css',
    'improvements.css',
    'premium-ux.css'
];

let bundledCSS = '';

cssFiles.forEach(file => {
    const filePath = path.join(rootDir, file);
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        // Basic minification: remove comments, newlines, and extra spaces
        content = content.replace(/\/\*[\s\S]*?\*\//g, '');
        content = content.replace(/\s+/g, ' ');
        content = content.replace(/\s*([\{\}\:\;\,])\s*/g, '$1');
        bundledCSS += content;
    }
});

const outputPath = path.join(rootDir, 'apollo-bundle.min.css');
fs.writeFileSync(outputPath, bundledCSS);
console.log(`Bundled and minified CSS created at ${outputPath}`);

// Update HTML files
const htmlFiles = [
    'index.html', 'booking.html', 'diya.html', 'knowledge.html',
    'memories.html', 'quiz.html', 'research.html', 'reviews.html'
];

htmlFiles.forEach(file => {
    const filePath = path.join(rootDir, file);
    if (fs.existsSync(filePath)) {
        let html = fs.readFileSync(filePath, 'utf8');
        // Replace the three link tags with the single bundle
        html = html.replace(
            /<link rel="stylesheet" href="styles\.css\?v=[^"]*" \/>\s*<link rel="stylesheet" href="improvements\.css\?v=[^"]*" \/>\s*<link rel="stylesheet" href="premium-ux\.css\?v=[^"]*" \/>/g,
            '<link rel="stylesheet" href="apollo-bundle.min.css?v=' + Date.now() + '" />'
        );
        fs.writeFileSync(filePath, html);
        console.log(`Updated ${file}`);
    }
});
