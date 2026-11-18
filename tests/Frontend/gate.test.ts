// @vitest-environment happy-dom
import { createInertiaApp, router } from '@inertiajs/vue3';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import { createApp, defineComponent, h, nextTick, resolveComponent, shallowRef } from 'vue';
import type { App, ComputedOptions, MethodOptions } from 'vue';
import { createPermit, Gate, useGate } from '../../resources/js';

const mountedApps: App[] = [];

afterEach(() => {
    for (const app of mountedApps.splice(0)) app.unmount();
    document.body.replaceChildren();
});

function rootElement(): HTMLDivElement {
    const root = document.createElement('div');
    document.body.append(root);
    return root;
}

function sparseQuery(): string[] {
    const abilities: string[] = [];
    abilities.length = 1;
    return abilities;
}

test('matches Laravel boolean semantics for single, mixed, unknown, and empty checks', () => {
    const app = createApp({}).use(createPermit());
    Object.defineProperty(app.config.globalProperties, '$page', {
        value: { props: { permitAbilities: { 'users::read': true, 'users::update': false } } },
    });
    const gate = app.runWithContext(useGate);
    const mixed = ['users::read', 'users::update'] as const;

    expect(gate).toBe(app.config.globalProperties.$gate);
    expect(gate.allows('users::read')).toBe(true);
    expect(gate.allow('users::read')).toBe(true);
    expect(gate.allows('users::update')).toBe(false);
    expect(gate.denies('users::update')).toBe(true);
    expect(gate.deny('users::read')).toBe(false);
    expect(gate.allows('users::missing')).toBe(false);
    expect(gate.check(mixed)).toBe(false);
    expect(gate.denies(mixed)).toBe(true);
    expect(gate.any(mixed)).toBe(true);
    expect(gate.none(mixed)).toBe(false);
    expect(gate.allows([])).toBe(true);
    expect(gate.denies([])).toBe(false);
    expect(gate.any([])).toBe(false);
    expect(gate.none([])).toBe(true);
    expect(gate.allows(sparseQuery())).toBe(false);
});

test('only grants strictly true own values from the current application page', () => {
    const app = createApp({}).use(createPermit());
    Object.defineProperty(app.config.globalProperties, '$page', {
        value: {
            props: {
                permitAbilities: Object.assign(Object.create({ 'users::remove': true }), {
                    'users::read': 'true',
                    'users::update': 1,
                    'role::admin': true,
                }),
            },
        },
    });
    const secondApp = createApp({}).use(createPermit());

    const gate = app.runWithContext(useGate);
    expect(gate.any(['users::read', 'users::update', 'users::remove'])).toBe(false);
    expect(gate.allows('role::admin')).toBe(true);
    expect(secondApp.runWithContext(useGate).allows('role::admin')).toBe(false);
});

test('the global component renders one valid condition and ignores invalid runtime combinations', async () => {
    const page = shallowRef<unknown>();
    const root = rootElement();
    const app = createApp({
        render() {
            const GlobalGate = resolveComponent('Gate');
            return [
                h(GlobalGate, { allows: 'users::read' }, () => 'read'),
                h(GlobalGate, { denies: 'users::read' }, () => 'denied'),
                h(GlobalGate, { allows: ['users::read', 1] }, () => 'malformed'),
                h(GlobalGate, { allows: sparseQuery() }, () => 'sparse'),
                h(GlobalGate, { allows: 'users::read', any: ['users::read'] }, () => 'conflicting'),
                h(GlobalGate, () => 'missing'),
            ];
        },
    }).use(createPermit());
    Object.defineProperty(app.config.globalProperties, '$page', { get: () => page.value });
    mountedApps.push(app);
    app.mount(root);

    expect(root.textContent).toBe('denied');
    page.value = { props: { permitAbilities: { 'users::read': true } } };
    await nextTick();
    expect(root.textContent).toBe('read');
    page.value = { props: { permitAbilities: ['users::read'] } };
    await nextTick();
    expect(root.textContent).toBe('denied');
});

test('the component and Options API observe a revoked ability on a real Inertia partial reload', async () => {
    const root = rootElement();
    root.id = 'permit-inertia-test';
    const computed: ComputedOptions = {};
    const methods: MethodOptions = {};
    const dashboard = defineComponent({
        computed,
        methods,
        render() {
            return [
                h(Gate, { allows: 'users::read' }, () => 'read'),
                h('span', this.$gate.denies('users::read') ? 'denied' : ''),
            ];
        },
    });
    const page = {
        component: 'Dashboard',
        url: '/',
        version: null,
        props: { errors: {}, permitAbilities: { 'users::read': true } },
        rescuedProps: [],
        flash: {},
        rememberedState: {},
    };
    const request = vi.fn();

    await createInertiaApp({
        id: root.id,
        page,
        resolve: () => dashboard,
        progress: false,
        http: {
            request({ headers }) {
                request(headers);
                return Promise.resolve({
                    status: 200,
                    headers: { 'x-inertia': 'true' },
                    data: JSON.stringify({
                        ...page,
                        props: { permitAbilities: { 'users::read': false } },
                    }),
                });
            },
        },
        setup({ el, App, props, plugin }) {
            const app = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(createPermit());
            mountedApps.push(app);
            app.mount(el);
        },
    });

    expect(root.textContent).toBe('read');
    router.reload({ only: ['permitAbilities'] });
    await expect.poll(() => root.textContent).toBe('denied');
    expect(request).toHaveBeenCalledWith(
        expect.objectContaining({ 'X-Inertia-Partial-Data': 'permitAbilities' }),
    );
});
