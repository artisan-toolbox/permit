import { Plugin } from 'vue';
import { Gate } from './Gate';
import { GateService } from './gateService';
export declare const PERMIT_VERSION: string;
export { Gate } from './Gate';
export { useGate } from './gateService';
export type { GateProps } from './Gate';
export type { AbilityQuery, GateService } from './gateService';
export declare function createPermit(): Plugin;
declare module 'vue' {
    interface ComponentCustomProperties {
        $gate: GateService;
    }
    interface GlobalComponents {
        Gate: typeof Gate;
    }
}
