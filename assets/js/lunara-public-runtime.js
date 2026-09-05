/**
 * Generated from the verified inline runtime during Phase 1C.
 * Keep behavior route-gated and free of render-blocking dependencies.
 */

(function () {
            var observer = null;
            var imageSelector = [
                '.lunara-review-grid-poster',
                '.lunara-review-feature-image',
                '.lunara-poster-card-image',
                '.lunara-journal-home-card-image',
                '.lunara-dispatch-archive-thumb',
                '.lunara-dispatch-lead-image',
                '.lunara-oscar-pick-card-image',
                '.lunara-oscar-fact-card-poster-image',
                '.aat-entity-poster',
                '.aat-filmography-poster',
                '.aat-winner-circle-photo',
                '.aat-winner-circle-media img',
                '.aat-hub-spotlight-media img',
                '.aat-related-review-image'
            ].join(',');

            function sanitizeSrcset(srcset) {
                srcset = (srcset || '').trim();
                if (!srcset) return '';

                return srcset.split(/,\s*(?=(?:https?:)?\/\/|\/)/).map(function (candidate) {
                    candidate = (candidate || '').trim();
                    if (!candidate) return '';

                    var decoded = candidate.replace(/&amp;/g, '&');
                    if (/(?:[?&;](?:resize|fit)=0(?:%2c|,)nan)/i.test(decoded) || /(?:[?&;](?:w|h)=0(?:&|$))/i.test(decoded)) {
                        return '';
                    }

                    return (/\s+\d+w$/.test(candidate) || /\s+\d+(?:\.\d+)?x$/.test(candidate)) ? candidate : '';
                }).filter(Boolean).join(', ');
            }

            function sanitizeImageSrcset(node) {
                if (!node || !node.getAttribute || !node.setAttribute) return;

                var currentSrcset = node.getAttribute('srcset') || '';
                if (!currentSrcset) return;

                var sanitizedSrcset = sanitizeSrcset(currentSrcset);
                if (sanitizedSrcset && sanitizedSrcset !== currentSrcset) {
                    node.setAttribute('srcset', sanitizedSrcset);
                } else if (!sanitizedSrcset) {
                    node.removeAttribute('srcset');
                    node.removeAttribute('sizes');
                }
            }

            function hydrateImage(img) {
                if (!img) return;
                img.dataset.lunaraHydratorState = 'hydrated';
                sanitizeImageSrcset(img);

                var dataSrcset = img.getAttribute('data-srcset') || img.getAttribute('data-lazy-srcset') || '';

                if (dataSrcset && !img.getAttribute('srcset')) {
                    var sanitizedSrcset = sanitizeSrcset(dataSrcset);
                    if (sanitizedSrcset) {
                        img.setAttribute('srcset', sanitizedSrcset);
                    } else {
                        img.removeAttribute('data-srcset');
                        img.removeAttribute('data-lazy-srcset');
                    }
                }

                if (img.complete && img.naturalWidth > 1) {
                    img.classList.add('lunara-img-loaded');
                    img.dataset.lunaraHydratorState = 'loaded';
                } else {
                    var markLoaded = function () {
                        img.classList.add('lunara-img-loaded');
                        img.dataset.lunaraHydratorState = 'loaded';
                    };
                    img.addEventListener('load', markLoaded, { once: true });
                    img.addEventListener('error', markLoaded, { once: true });
                    window.setTimeout(markLoaded, 1800);
                }
            }

            function observeImage(img) {
                if (!img || img.dataset.lunaraHydratorState) return;

                var loading = (img.getAttribute('loading') || '').toLowerCase();
                if (loading === 'eager' || img.getAttribute('fetchpriority') === 'high') {
                    hydrateImage(img);
                    return;
                }

                if (!('IntersectionObserver' in window)) {
                    hydrateImage(img);
                    return;
                }

                if (!observer) {
                    observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            hydrateImage(entry.target);
                            observer.unobserve(entry.target);
                        });
                    }, { rootMargin: '640px 0px' });
                }

                img.dataset.lunaraHydratorState = 'observed';
                observer.observe(img);
            }

            function scanImages(root) {
                root = root || document;
                if (root.matches && root.matches(imageSelector)) {
                    observeImage(root);
                }
                if (root.querySelectorAll) {
                    root.querySelectorAll(imageSelector).forEach(observeImage);
                }
            }

            function bootImageRuntime() {
                scanImages(document);
                if (!window.MutationObserver) return;

                new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'attributes') {
                            sanitizeImageSrcset(mutation.target);
                            if (mutation.target.matches && mutation.target.matches(imageSelector)) {
                                observeImage(mutation.target);
                            }
                            return;
                        }

                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                scanImages(node);
                            }
                        });
                    });
                }).observe(document.body || document.documentElement, {
                    attributes: true,
                    attributeFilter: ['srcset', 'data-srcset', 'data-lazy-srcset'],
                    childList: true,
                    subtree: true
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootImageRuntime, { once: true });
            } else {
                bootImageRuntime();
            }
        }());

(function(){
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        // Scroll reveals: front page, the Oscars portal (3.2.59), and plugin entity pages. Other routes stay static.
        var isFrontPage=document.body.classList.contains('home')||document.querySelector('.lunara-front-page');
        var isPortal=document.body.classList.contains('lunara-oscars-portal-page');
        var isPluginPage=document.querySelector('.aat-hub-page,.aat-entity-page');
        var revealSels=[];
        var staggerSels=[];
        if(isFrontPage){
            revealSels=[
                '.lunara-front-page>.lunara-home-section','.lunara-review-grid-card','.lunara-review-feature-card',
                '.lunara-poster-card','.lunara-ledger-card','.lunara-dispatch-archive-card'
            ];
            staggerSels=[
                '.lunara-review-grid','.lunara-review-related-grid'
            ];
        }
        /* Oscars portal: every section below the hero, and the cards inside its grids. Checked before the plugin-page branch because the portal embeds the plugin hub. */
        if(isPortal){
            revealSels=[
                '#primary.lunara-oscars-portal>.lunara-home-section'+':not(.lunara-oscars-portal-slot-hero)',
                '.lunara-oscars-portal-link-card','.lunara-oscars-portal-spotlight-card','.lunara-oscars-portal-title-card',
                '.lunara-oscars-research-card','.lunara-oscars-portal-fact-card','.lunara-oscars-board-row',
                '.lunara-ceremony-winners-grid>.lunara-ceremony-winner-card'
            ];
            staggerSels=[
                '.lunara-oscars-portal-link-grid','.lunara-oscars-portal-spotlight-grid','.lunara-oscars-portal-title-grid',
                '.lunara-oscars-research-card-grid','.lunara-oscars-portal-facts-grid','.lunara-oscars-board-list','.lunara-ceremony-winners-grid'
            ];
        }else if(isPluginPage){
            // Entity pages get targeted reveals for stats/timeline only
            revealSels=['.aat-entity-status-banner','.aat-stat','.aat-timeline-card'];
            staggerSels=['.aat-stats-bar','.aat-timeline-list'];
        }
        if(!revealSels.length)return;
        revealSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal');});
        });
        staggerSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal-stagger');});
        });
		if(isFrontPage){
			document.querySelectorAll('.lunara-reveal').forEach(function(el){el.classList.add('is-visible');});
			return;
		}
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
		},{threshold:0.01,rootMargin:'240px 0px'});
        /* Anything already on screen shows at once; a safety timer makes sure no observer can strand a section. */
        var vh=window.innerHeight||0;
        document.querySelectorAll('.lunara-reveal').forEach(function(el){
            if(el.getBoundingClientRect().top<vh*0.9){el.classList.add('is-visible');}else{obs.observe(el);}
        });
        window.setTimeout(function(){document.querySelectorAll('.lunara-reveal:not(.is-visible)').forEach(function(el){el.classList.add('is-visible');});},6000);
    })();

(function(){
        var stats=document.querySelectorAll('.aat-stat-number,.lunara-oscars-portal-stat-value,.lunara-oscars-portal-fact-value,.lunara-oscars-season-days');
        if(!stats.length||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(!entry.isIntersecting)return;
                obs.unobserve(entry.target);
                var el=entry.target,text=el.textContent.trim();
                if(/^\d{4}$/.test(text))return;
                var match=text.match(/^([\d,]+)(.*)/);
                if(!match)return;
                var target=parseInt(match[1].replace(/,/g,''),10);
                var suffix=match[2];
                if(isNaN(target)||target===0)return;
                var duration=Math.min(1600,Math.max(600,target*8));
                var start=performance.now();
                function tick(now){
                    var t=Math.min(1,(now-start)/duration);
                    var ease=1-Math.pow(1-t,3);
                    var current=Math.round(target*ease);
                    el.textContent=current.toLocaleString()+suffix;
                    if(t<1)requestAnimationFrame(tick);
                }
                el.textContent='0'+suffix;
                requestAnimationFrame(tick);
            });
        },{threshold:0.3});
        stats.forEach(function(el){obs.observe(el);});
    })();

(function(){
        var body=document.body;if(!body||!body.classList.contains('lunara-oscars-portal-page'))return;
        /* Season clock (3.2.59): recompute at view time so anonymous cached HTML never shows yesterday's count. */
        var labels={settled:'Ceremony settled',tonight:'Ceremony night',final:'Final stretch',season:'Awards season',countdown:'Ceremony countdown'};
        document.querySelectorAll('[data-lunara-season-clock]').forEach(function(el){
            var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(el.getAttribute('data-lunara-season-clock')||'');if(!m)return;
            var now=new Date();
            var days=Math.round((Date.UTC(+m[1],+m[2]-1,+m[3])-Date.UTC(now.getFullYear(),now.getMonth(),now.getDate()))/86400000);
            if(days<-14){el.hidden=true;return;}
            var num=el.querySelector('[data-lunara-season-days]'),unit=el.querySelector('[data-lunara-season-unit]'),ph=el.querySelector('.lunara-oscars-season-phase');
            if(!num||!unit)return;
            var phase=days<0?'settled':days===0?'tonight':days<=30?'final':days<=120?'season':'countdown';
            if(ph)ph.textContent=labels[phase];
            el.className=el.className.replace(/\bis-phase-\S+/,'is-phase-'+phase);
            if(days>0){num.textContent=String(days);unit.textContent=days===1?'day to the ceremony':'days to the ceremony';}
            else if(days===0){num.textContent='Tonight';unit.textContent='the envelopes open';}
            else{num.textContent=String(-days);unit.textContent=days===-1?'day since the ceremony':'days since the ceremony';}
        });
        /* Navigator scroll-spy: the pill whose section owns the middle of the viewport is current. */
        var nav=document.querySelector('.lunara-oscars-navigator');
        if(!nav||!('IntersectionObserver' in window))return;
        var links=Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]')),map={},targets=[];
        links.forEach(function(a){var id=a.getAttribute('href').slice(1);var t=id?document.getElementById(id):null;if(t){map[id]=a;targets.push(t);}});
        if(!targets.length)return;
        var current='';
        var spy=new IntersectionObserver(function(entries){
            entries.forEach(function(en){
                if(!en.isIntersecting||current===en.target.id)return;
                current=en.target.id;
                links.forEach(function(a){a.removeAttribute('aria-current');});
                map[current].setAttribute('aria-current','location');
            });
        },{rootMargin:'-35% 0px -55% 0px',threshold:0});
        targets.forEach(function(t){spy.observe(t);});
    })();

(function(){
        if(!('startViewTransition' in document))return;
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var CARDS='.lunara-review-grid-card,.lunara-poster-card,.lunara-oscar-pick-card,.lunara-journal-home-card,.lunara-cinematic-hero-link';
        document.addEventListener('click',function(e){
            if(e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey)return;
            var card=e.target.closest?e.target.closest(CARDS):null;if(!card)return;
            var a=card.matches('a')?card:card.querySelector('a[href]');
            if(!a||!a.href)return;
            if(a.target&&a.target!=='_self')return;
            if(a.origin!==location.origin)return;
            var img=card.querySelector('img');if(!img)return;
            var r=img.getBoundingClientRect();
            if(r.width<1||r.bottom<0||r.top>window.innerHeight)return;
            /* Demote the current page's own hero name first — two elements
               sharing lunara-screen would abort the whole transition. */
            var hero=document.querySelector('.lunara-review-single-cinematic-hero .lunara-review-visual--hero');
            if(hero)hero.style.viewTransitionName='none';
            img.style.viewTransitionName='lunara-screen';
            img.setAttribute('data-lunara-vt','1');
        },true);
        window.addEventListener('pageshow',function(e){
            if(!e.persisted)return;
            document.querySelectorAll('[data-lunara-vt]').forEach(function(el){el.style.viewTransitionName='';el.removeAttribute('data-lunara-vt');});
            var hero=document.querySelector('.lunara-review-single-cinematic-hero .lunara-review-visual--hero');
            if(hero)hero.style.viewTransitionName='';
        });
    })();
