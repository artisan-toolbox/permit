import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { PERMIT_VERSION } from '../dist/index.js';

const require = createRequire(import.meta.url);
const manifest = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf8'));
const commonjs = require('../dist/index.cjs');
const declarations = readFileSync(new URL('../dist/index.d.ts', import.meta.url), 'utf8');

assert.equal(PERMIT_VERSION, manifest.version);
assert.equal(commonjs.PERMIT_VERSION, manifest.version);
assert.match(declarations, /export declare const PERMIT_VERSION: string;/);
console.log('ES modules, CommonJS, and TypeScript declarations expose the preview version.');
