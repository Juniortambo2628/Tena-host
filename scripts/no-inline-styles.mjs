/**
 * Detects inline styles in JSX files
 * Run: node scripts/no-inline-styles.mjs
 */

import fs from 'fs';
import path from 'path';

const INLINE_STYLE_PATTERN = /style=\{\{/g;
const JSX_DIR = path.join(process.cwd(), 'resources/js');

function findInlineStyles() {
    const errors = [];
    const allowedPatterns = [
        /--\w+-/,
    ];

    function traverse(dir) {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            
            if (entry.isDirectory()) {
                traverse(fullPath);
            } else if (entry.isFile() && entry.name.endsWith('.jsx')) {
                const content = fs.readFileSync(fullPath, 'utf8');
                let match;
                
                while ((match = INLINE_STYLE_PATTERN.exec(content)) !== null) {
                    const line = content.substring(0, match.index).split('\n').length;
                    const lineContent = content.split('\n')[line - 1].trim();
                    
                    // Check if it's a CSS custom property (allowed)
                    const isCustomProp = allowedPatterns.some(p => p.test(lineContent));
                    
                    if (!isCustomProp) {
                        errors.push({
                            file: fullPath,
                            line: line,
                            content: lineContent
                        });
                    }
                }
            }
        }
    }

    traverse(JSX_DIR);
    return errors;
}

const errors = findInlineStyles();

if (errors.length > 0) {
    console.error('❌ Found inline styles (style={{...}}) in JSX files:');
    console.error('');
    for (const error of errors) {
        const relativePath = path.relative(process.cwd(), error.file);
        console.error(`  ${relativePath}:${error.line}`);
        console.error(`    ${error.content}`);
        console.error('');
    }
    console.error('⚠️  Inline styles should be moved to CSS classes or CSS custom properties.');
    process.exit(1);
} else {
    console.log('✅ No inline styles found in JSX files.');
    process.exit(0);
}
