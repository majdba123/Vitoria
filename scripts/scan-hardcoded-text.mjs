#!/usr/bin/env node
/**
 * Heuristic scanner for likely hardcoded, user-facing English/Arabic text in
 * resources/js/**. Not an AST-based tool (no new build dependency), so it
 * works on raw source lines with a set of patterns tuned to catch the shapes
 * called out in docs/reviews/FINAL_PLATFORM_ACCEPTANCE.md's localization
 * audit: JSX text nodes, common a11y/label attributes, and `?? 'text'` /
 * `|| 'text'` fallback literals. False positives are expected and must be
 * triaged by a human/reviewer — see the "false positive" exclusions below,
 * which were tuned against this repository's actual codebase, not guessed.
 *
 * Usage: node scripts/scan-hardcoded-text.mjs [--json] [glob...]
 */

import { readFileSync } from 'node:fs';
import { globSync } from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();
const args = process.argv.slice(2);
const asJson = args.includes('--json');
const globs = args.filter((a) => !a.startsWith('--'));

const DEFAULT_GLOBS = ['resources/js/**/*.{jsx,js}'];

const EXCLUDE_DIRS = [
    'node_modules',
    'resources/js/lib/i18n',
];

// Attributes worth checking for literal user-facing text.
const TEXT_ATTRS = ['placeholder', 'aria-label', 'aria-description', 'title', 'alt'];

// A line is skipped entirely if it contains any of these — each is either an
// intentionally-non-translated technical value, or already going through the
// translation layer (t(...)/__(...) calls carry the raw fallback text as an
// argument, which is expected and not itself a bug).
const SKIP_LINE_IF_CONTAINS = [
    'className=', 'class=', 'data-testid=', 'data-test=', 'htmlFor=',
    'import ', 'from \'', 'from "', 'require(',
    // SVG technical attributes.
    ' d="', ' points="', ' viewBox="', ' transform="', ' xmlns=', ' fill="#', ' stroke="#',
    // Route/asset/identifier-shaped calls.
    'route(', 'href="/', 'href={', 'src="/', 'src={', "src='/",
];

// Function-call wrappers whose first-argument literal is a translation key
// (not hardcoded display text) — the literal itself is fine.
const TRANSLATION_CALL_RE = /\b(t|__|trans|useI18n)\s*\(/;

function isLikelyIdentifierOrTechnical(value) {
    // e.g. "flex-1", "btn-primary", "admin.users.index", "SAR", "USD", numeric, single word all-caps
    if (/^[a-z0-9-]+$/.test(value)) return true; // tailwind/class-like token
    if (/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/.test(value)) return true; // dot.path.key
    if (/^[A-Z0-9_]+$/.test(value)) return true; // CONSTANT_CASE
    if (/^\d+$/.test(value)) return true; // pure number
    if (value.length < 3) return true;
    return false;
}

function scanFile(filePath) {
    const text = readFileSync(filePath, 'utf8');
    const lines = text.split('\n');
    const findings = [];

    lines.forEach((line, idx) => {
        const lineNo = idx + 1;

        if (SKIP_LINE_IF_CONTAINS.some((needle) => line.includes(needle))) return;
        if (TRANSLATION_CALL_RE.test(line)) return;
        if (/^\s*(\/\/|\*|\/\*)/.test(line)) return; // comments

        // 1. Suspicious attributes with a literal string value.
        for (const attr of TEXT_ATTRS) {
            const re = new RegExp(`\\b${attr}\\s*=\\s*["']([^"'{}]{3,})["']`, 'g');
            let m;
            while ((m = re.exec(line))) {
                const value = m[1].trim();
                if (!/[A-Za-z؀-ۿ]/.test(value)) continue;
                if (isLikelyIdentifierOrTechnical(value)) continue;
                findings.push({ line: lineNo, kind: `attr:${attr}`, text: value });
            }
        }

        // 2. JSX text node: `>Some Text<` with no interpolation/tag chars inside.
        const jsxTextRe = />([A-Za-z؀-ۿ][^<>{}]{2,})</g;
        let jm;
        while ((jm = jsxTextRe.exec(line))) {
            const value = jm[1].trim();
            if (!value) continue;
            if (!/[A-Za-z؀-ۿ]/.test(value)) continue;
            if (isLikelyIdentifierOrTechnical(value)) continue;
            findings.push({ line: lineNo, kind: 'jsx-text', text: value });
        }

        // 3. `?? 'text'` / `|| 'text'` fallback literals.
        const fallbackRe = /(\?\?|\|\|)\s*["']([A-Za-z؀-ۿ][^"']{2,})["']/g;
        let fm;
        while ((fm = fallbackRe.exec(line))) {
            const value = fm[2].trim();
            if (isLikelyIdentifierOrTechnical(value)) continue;
            findings.push({ line: lineNo, kind: `fallback:${fm[1]}`, text: value });
        }

        // 4. toast/alert/confirm literal calls.
        const callRe = /\b(toast(?:\.(?:success|error|info|warn))?|alert|confirm)\s*\(\s*["']([A-Za-z؀-ۿ][^"']{2,})["']/g;
        let cm;
        while ((cm = callRe.exec(line))) {
            findings.push({ line: lineNo, kind: `call:${cm[1]}`, text: cm[2].trim() });
        }
    });

    return findings;
}

const patterns = globs.length ? globs : DEFAULT_GLOBS;
const files = patterns
    .flatMap((g) => globSync(g, { cwd: ROOT }))
    .filter((f) => !EXCLUDE_DIRS.some((d) => f.startsWith(d) || f.includes(`/${d}/`)))
    .map((f) => path.join(ROOT, f));

const results = [];
for (const file of files) {
    const findings = scanFile(file);
    if (findings.length) {
        results.push({ file: path.relative(ROOT, file).replace(/\\/g, '/'), findings });
    }
}

const totalFindings = results.reduce((sum, r) => sum + r.findings.length, 0);

if (asJson) {
    console.log(JSON.stringify({ filesScanned: files.length, filesWithFindings: results.length, totalFindings, results }, null, 2));
} else {
    console.log(`Scanned ${files.length} files, ${results.length} with findings, ${totalFindings} total findings.\n`);
    for (const { file, findings } of results) {
        console.log(file);
        for (const f of findings) {
            console.log(`  L${f.line} [${f.kind}] ${JSON.stringify(f.text)}`);
        }
    }
}
