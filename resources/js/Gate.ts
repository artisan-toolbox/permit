import { defineComponent } from 'vue';
import { gateConditions, isAbilityQuery, useGate } from './gateService';
import type { AbilityQuery } from './gateService';

type Condition = (typeof gateConditions)[number];

export type GateProps = {
    [Name in Condition]: Record<Name, AbilityQuery> &
        Partial<Record<Exclude<Condition, Name>, never>>;
}[Condition];

export const Gate = defineComponent(
    (props: GateProps, { slots }) => {
        const gate = useGate();

        return () => {
            const conditions = gateConditions.filter((condition) => props[condition] !== undefined);
            if (conditions.length !== 1) return null;

            const condition = conditions[0];
            const abilities = props[condition];

            return isAbilityQuery(abilities) && gate[condition](abilities)
                ? (slots.default?.() ?? null)
                : null;
        };
    },
    { name: 'Gate', props: [...gateConditions], inheritAttrs: false },
);
