import { inject } from 'vue';
import type { InjectionKey } from 'vue';

export const gateConditions = [
    'allows',
    'allow',
    'check',
    'denies',
    'deny',
    'any',
    'none',
] as const;

export type AbilityQuery = string | readonly string[];
type GateCondition = (typeof gateConditions)[number];
export type GateService = Readonly<Record<GateCondition, (abilities: AbilityQuery) => boolean>>;

export const gateKey: InjectionKey<GateService> = Symbol('permit.gate');

function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function abilityKeys(abilities: AbilityQuery): readonly string[] {
    return typeof abilities === 'string' ? [abilities] : abilities;
}

export function isAbilityQuery(value: unknown): value is AbilityQuery {
    if (typeof value === 'string') return true;
    if (!Array.isArray(value)) return false;
    for (const ability of value) {
        if (typeof ability !== 'string') return false;
    }
    return true;
}

function isGranted(permissions: Record<string, unknown>, ability: unknown): boolean {
    return (
        typeof ability === 'string' &&
        Object.hasOwn(permissions, ability) &&
        permissions[ability] === true
    );
}

export function createGate(readPage: () => unknown): GateService {
    const decisions = (): Record<string, unknown> => {
        const page = readPage();
        const props = isObject(page) && Object.hasOwn(page, 'props') ? page.props : undefined;
        const permissions =
            isObject(props) && Object.hasOwn(props, 'permitAbilities')
                ? props.permitAbilities
                : undefined;

        return isObject(permissions) ? permissions : {};
    };

    const allows = (abilities: AbilityQuery): boolean => {
        const permissions = decisions();
        for (const ability of abilityKeys(abilities)) {
            if (!isGranted(permissions, ability)) return false;
        }
        return true;
    };
    const denies = (abilities: AbilityQuery): boolean => !allows(abilities);
    const any = (abilities: AbilityQuery): boolean => {
        const permissions = decisions();
        return abilityKeys(abilities).some((ability) => isGranted(permissions, ability));
    };

    return {
        allows,
        allow: allows,
        check: allows,
        denies,
        deny: denies,
        any,
        none: (abilities) => !any(abilities),
    };
}

export function useGate(): GateService {
    const gate = inject(gateKey);
    if (!gate) throw new Error('Install createPermit() before using the Permit Gate.');

    return gate;
}
