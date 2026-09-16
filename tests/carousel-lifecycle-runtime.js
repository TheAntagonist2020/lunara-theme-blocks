'use strict';
// Deterministic event/timer regression against both production carousel runtimes.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
let checks = 0;
function check(value, message) { checks++; if (!value) throw new Error(message); }
function node(attrs = {}) {
    const handlers = {}, classes = new Set();
    return {
        attrs, children: [], handlers, disabled: false, textContent: '', scrollLeft: 0,
        offsetLeft: 0, offsetWidth: 260, clientWidth: 300, scrollWidth: 812,
        classList: { toggle(key, value) { value ? classes.add(key) : classes.delete(key); } },
        addEventListener(type, handler) { (handlers[type] ||= []).push(handler); },
        emit(type, event = {}) { for (const fn of handlers[type] || []) fn(event); },
        hasAttribute(key) { return key in attrs; },
        getAttribute(key) { return attrs[key] ?? null; },
        setAttribute(key, value) { attrs[key] = String(value); },
        removeAttribute(key) { delete attrs[key]; },
        getBoundingClientRect() { return { left: this.offsetLeft, width: this.offsetWidth }; },
        scrollTo(options) { this.lastBehavior = options.behavior; this.scrollLeft = Math.min(this.scrollWidth-this.clientWidth, Math.max(0,options.left)); },
        scrollBy(options) { this.scrollTo({...options,left:this.scrollLeft+options.left}); },
        scrollIntoView() {},
    };
}
function fixture(kind, options = {}) {
    const dynamic = kind === 'dynamic', prefix = dynamic ? 'data-lunara-dynamic-rail' : 'data-lunara-carousel';
    const section = node({[prefix+'-autoplay']: String(options.autoplay ?? 800)}), track = node(), toggle = node(), prev = node(), next = node();
    const items = Array.from({length:3},(_,i)=>Object.assign(node(),{offsetLeft:i*276}));
    const dots = items.map((_,i)=>node({'data-lunara-dynamic-rail-index':String(i)}));
    track.children = items;
    track.querySelectorAll = () => items;
    section.querySelector = selector => ({['['+prefix+'-track]']:track,['['+prefix+'-toggle]']:toggle,['['+prefix+'-prev]']:prev,['['+prefix+'-next]']:next})[selector] || null;
    section.querySelectorAll = () => dots;
    section.contains = el => [section,track,toggle,prev,next,...items,...dots].includes(el);
    const document = Object.assign(node(),{readyState:'complete',hidden:!!options.hidden,activeElement:null,querySelectorAll:()=>[section]});
    const motion = Object.assign(node(),{matches:!!options.reduced});
    const timers = new Map(), tasks = []; let sequence = 0;
    const window = {
        innerWidth:390, matchMedia:()=>motion, getComputedStyle:()=>({columnGap:'16px'}),
        setInterval(fn) { timers.set(++sequence,fn); return sequence; },
        clearInterval(id) { timers.delete(id); },
        setTimeout(fn) { tasks.push(fn); },
        requestAnimationFrame(fn) { fn(); return 1; }, cancelAnimationFrame() {},
    };
    const context = vm.createContext({window,document,console});
    vm.runInContext(fs.readFileSync(path.join(__dirname,'../assets/js',dynamic?'lunara-dynamic-rails.js':'lunara-scroll-carousel.js'),'utf8'),context);
    if (!dynamic) document.emit('DOMContentLoaded');
    return {section,track,toggle,prev,next,document,timers,motion,
        flush() { while(tasks.length) tasks.shift()(); },
        tick() { for(const callback of [...timers.values()]) callback(); },
        reduce(value) { motion.matches=value; motion.emit('change',{matches:value}); },
        focus(el) { document.activeElement=el; section.emit(el?'focusin':'focusout'); this.flush(); },
    };
}
for (const kind of ['dynamic','scroll']) {
    const f=fixture(kind);
    const active=(expected,label)=>check(f.timers.size===expected,`${kind}: ${label} (timers ${f.timers.size})`);
    active(1,'initial rotation');
    f.tick(); check(f.track.scrollLeft>0,`${kind}: autoplay advances the actual track`);
    f.section.emit('pointerenter',{pointerType:'mouse'}); active(0,'hover pauses');
    f.focus(f.next); f.section.emit('pointerleave',{pointerType:'mouse'}); active(0,'hover exit cannot override focus');
    f.focus(null); active(1,'focus exit resumes');
    f.focus(f.toggle); f.toggle.emit('click'); active(0,'user pause');
    f.focus(null); active(0,'user pause persists after focus exit');
    f.focus(f.toggle); f.toggle.emit('click'); active(1,'explicit Play works while the button keeps focus');
    f.focus(f.next); active(0,'new focus interaction pauses again');
    f.focus(null); active(1,'focus release resumes');
    f.track.emit('touchstart',{touches:[{clientX:200,clientY:20}]}); active(0,'touch pauses');
    f.section.emit('pointerleave',{pointerType:'mouse'}); active(0,'pointer exit cannot override active touch');
    f.document.emit('visibilitychange'); active(0,'visible event cannot override active touch');
    f.track.emit('touchcancel'); active(1,'touch cancellation resumes');
    f.document.hidden=true; f.document.emit('visibilitychange'); active(0,'hidden tab pauses');
    f.section.emit('pointerleave',{pointerType:'mouse'}); active(0,'hover exit cannot restart hidden tab');
    f.track.emit('touchend',{changedTouches:[]}); active(0,'touch end cannot restart hidden tab');
    f.document.hidden=false; f.document.emit('visibilitychange'); active(1,'return to visible resumes');
    f.reduce(true); active(0,'live reduced-motion preference stops rotation');
    check(f.toggle.disabled&&f.toggle.attrs['aria-disabled']==='true',`${kind}: reduced motion disables toggle`);
    f.next.emit('click'); check(f.track.lastBehavior==='auto',`${kind}: reduced motion also changes manual scrolling`);
    f.reduce(false); active(1,'normal motion resumes when available');
    check(!f.toggle.disabled&&!('aria-disabled' in f.toggle.attrs),`${kind}: normal motion restores toggle`);
    f.toggle.emit('click'); f.reduce(true); f.reduce(false); active(0,'motion changes preserve an explicit user pause');
    const hidden=fixture(kind,{hidden:true}); check(hidden.timers.size===0,`${kind}: initially hidden tab never starts a timer`);
    const reduced=fixture(kind,{reduced:true}); check(reduced.timers.size===0&&reduced.toggle.disabled,`${kind}: initially reduced motion stays idle`);
    const disabled=fixture(kind,{autoplay:0}); check(disabled.timers.size===0&&disabled.toggle.disabled,`${kind}: disabled autoplay has no misleading Play button`);
}
console.log(`Carousel lifecycle passed: ${checks} checks across both production runtimes.`);
