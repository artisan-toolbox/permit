import { version } from '../../package.json';
import type { Plugin } from 'vue';
import { Gate } from './Gate';
import { createGate, gateKey } from './gateService';
import type { GateService } from './gateService';

export const PERMIT_VERSION = version;

export { Gate } from './Gate';
export { useGate } from './gateService';
export type { GateProps } from './Gate';
export type { AbilityQuery, GateService } from './gateService';

export function createPermit(): Plugin {
    return {
        install(app) {
            const gate = createGate(() => {
                const page: unknown = app.config.globalProperties.$page;
                return page;
            });

            app.provide(gateKey, gate);
            app.component('Gate', Gate);
            app.config.globalProperties.$gate = gate;
        },
    };
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $gate: GateService;
    }

    interface GlobalComponents {
        Gate: typeof Gate;
    }
}
