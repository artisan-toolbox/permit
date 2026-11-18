import { readFileSync } from 'node:fs';
import { expect, test } from 'vite-plus/test';
import { PERMIT_VERSION } from '../../resources/js';

test('reports the same preview version as the Composer package', () => {
    const source = readFileSync(new URL('../../src/Permit.php', import.meta.url), 'utf8');

    expect(source).toContain(`public const string VERSION = '${PERMIT_VERSION}';`);
});
