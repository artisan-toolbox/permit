import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { createPermit, Gate, PERMIT_VERSION, useGate } from '../dist/index.js';

const require = createRequire(import.meta.url);
const manifest = JSON.parse(readFileSync(new URL('../package.json', import.meta.url), 'utf8'));
const commonjs = require('../dist/index.cjs');
const declarations = readFileSync(new URL('../dist/index.d.ts', import.meta.url), 'utf8');

assert.equal(PERMIT_VERSION, manifest.version);
assert.equal(commonjs.PERMIT_VERSION, manifest.version);
assert.match(declarations, /export declare const PERMIT_VERSION: string;/);
for (const entry of [{ createPermit, Gate, useGate }, commonjs]) {
    assert.equal(typeof entry.createPermit, 'function');
    assert.equal(entry.Gate.name, 'Gate');
    assert.equal(typeof entry.useGate, 'function');
}
assert.match(declarations, /interface GlobalComponents/);
assert.match(declarations, /\$gate: GateService/);
console.log('ES modules, CommonJS, and declarations expose the Permit Vue API.');
