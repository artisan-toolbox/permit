import { gateConditions, AbilityQuery } from './gateService';
type Condition = (typeof gateConditions)[number];
export type GateProps = {
    [Name in Condition]: Record<Name, AbilityQuery> & Partial<Record<Exclude<Condition, Name>, never>>;
}[Condition];
export declare const Gate: import('vue').DefineSetupFnComponent<GateProps, {}, {}, GateProps & {}, import('vue').PublicProps>;
export {};
