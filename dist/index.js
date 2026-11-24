import { defineComponent as e, inject as t } from "vue";
// package.json
var n = "1.0.0-beta.5", r = [
	"allows",
	"allow",
	"check",
	"denies",
	"deny",
	"any",
	"none"
], i = Symbol("permit.gate");
function a(e) {
	return typeof e == "object" && !!e && !Array.isArray(e);
}
function o(e) {
	return typeof e == "string" ? [e] : e;
}
function s(e) {
	if (typeof e == "string") return !0;
	if (!Array.isArray(e)) return !1;
	for (let t of e) if (typeof t != "string") return !1;
	return !0;
}
function c(e, t) {
	return typeof t == "string" && Object.hasOwn(e, t) && e[t] === !0;
}
function l(e) {
	let t = () => {
		let t = e(), n = a(t) && Object.hasOwn(t, "props") ? t.props : void 0, r = a(n) && Object.hasOwn(n, "permitAbilities") ? n.permitAbilities : void 0;
		return a(r) ? r : {};
	}, n = (e) => {
		let n = t();
		for (let t of o(e)) if (!c(n, t)) return !1;
		return !0;
	}, r = (e) => !n(e), i = (e) => {
		let n = t();
		return o(e).some((e) => c(n, e));
	};
	return {
		allows: n,
		allow: n,
		check: n,
		denies: r,
		deny: r,
		any: i,
		none: (e) => !i(e)
	};
}
function u() {
	let e = t(i);
	if (!e) throw Error("Install createPermit() before using the Permit Gate.");
	return e;
}
// resources/js/Gate.ts
var d = e((e, { slots: t }) => {
	let n = u();
	return () => {
		let i = r.filter((t) => e[t] !== void 0);
		if (i.length !== 1) return null;
		let a = i[0], o = e[a];
		return s(o) && n[a](o) ? t.default?.() ?? null : null;
	};
}, {
	name: "Gate",
	props: [...r],
	inheritAttrs: !1
}), f = n;
function p() {
	return { install(e) {
		let t = l(() => e.config.globalProperties.$page);
		e.provide(i, t), e.component("Gate", d), e.config.globalProperties.$gate = t;
	} };
}
export { d as Gate, f as PERMIT_VERSION, p as createPermit, u as useGate };
