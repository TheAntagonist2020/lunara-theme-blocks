/**
 * Generated from the verified inline runtime during Phase 1C.
 * Keep behavior route-gated and free of render-blocking dependencies.
 */

(function () {
            var observer = null;

            function shouldHydrateNow(img) {
                if (!img) return false;

                var loading = (img.getAttribute('loading') || '').toLowerCase();

                if (loading === 'eager' || img.getAttribute('fetchpriority') === 'high') {
                    return true;
                }

                if (!img.getBoundingClientRect) {
                    return false;
                }

                var rect = img.getBoundingClientRect();
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

                return rect.top < viewportHeight * 1.35 && rect.bottom > -120;
            }

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

            function installSrcsetGuard() {
                if (window.lunaraSrcsetGuardInstalled || !window.Element) return;
                window.lunaraSrcsetGuardInstalled = true;

                var nativeSetAttribute = Element.prototype.setAttribute;
                Element.prototype.setAttribute = function (name, value) {
                    if (typeof name === 'string' && name.toLowerCase() === 'srcset') {
                        value = sanitizeSrcset(String(value || ''));
                    }

                    return nativeSetAttribute.call(this, name, value);
                };

                [window.HTMLImageElement, window.HTMLSourceElement].forEach(function (Constructor) {
                    if (!Constructor || !Constructor.prototype) return;

                    var descriptor = Object.getOwnPropertyDescriptor(Constructor.prototype, 'srcset');
                    if (!descriptor || !descriptor.set || !descriptor.get) return;

                    Object.defineProperty(Constructor.prototype, 'srcset', {
                        configurable: true,
                        enumerable: descriptor.enumerable,
                        get: function () {
                            return descriptor.get.call(this);
                        },
                        set: function (value) {
                            descriptor.set.call(this, sanitizeSrcset(String(value || '')));
                        }
                    });
                });
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

            function sanitizeDocumentSrcsets(root) {
                root = root || document;

                if (root.matches && root.matches('img[srcset], source[srcset]')) {
                    sanitizeImageSrcset(root);
                }

                if (root.querySelectorAll) {
                    root.querySelectorAll('img[srcset], source[srcset]').forEach(sanitizeImageSrcset);
                }
            }

            installSrcsetGuard();

            function hydrateImage(img) {
                if (!img) return;
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
                } else {
                    var markLoaded = function () {
                        img.classList.add('lunara-img-loaded');
                    };
                    img.addEventListener('load', markLoaded, { once: true });
                    img.addEventListener('error', markLoaded, { once: true });
                    window.setTimeout(markLoaded, 1800);
                }
            }

            function observeImage(img) {
                if (!img || img.dataset.lunaraHydratorObserved === '1') return;

                if (shouldHydrateNow(img)) {
                    hydrateImage(img);
                    return;
                }

                if (!('IntersectionObserver' in window)) {
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

                img.dataset.lunaraHydratorObserved = '1';
                observer.observe(img);
            }

            function hydrateCards() {
                sanitizeDocumentSrcsets(document);

                document.querySelectorAll([
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
                ].join(',')).forEach(observeImage);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hydrateCards);
            } else {
                hydrateCards();
            }

            if (window.MutationObserver) {
                new MutationObserver(function (mutations) {
                    var needsHydration = false;

                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'attributes') {
                            sanitizeImageSrcset(mutation.target);
                            return;
                        }

                        needsHydration = true;
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                sanitizeDocumentSrcsets(node);
                            }
                        });
                    });

                    if (needsHydration) {
                        hydrateCards();
                    }
                }).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['srcset', 'data-srcset', 'data-lazy-srcset'],
                    childList: true,
                    subtree: true
                });
            }
        }());

(function(){
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        // Only run scroll reveals on the front page — skip portal, plugin, single review, and other pages
        var isFrontPage=document.body.classList.contains('home')||document.querySelector('.lunara-front-page');
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
        // Entity pages get targeted reveals for stats/timeline only
        if(isPluginPage){
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
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        },{threshold:0.08,rootMargin:'0px 0px -40px 0px'});
        document.querySelectorAll('.lunara-reveal').forEach(function(el){obs.observe(el);});
    })();

(function(){
        var stats=document.querySelectorAll('.aat-stat-number');
        if(!stats.length||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(!entry.isIntersecting)return;
                obs.unobserve(entry.target);
                var el=entry.target,text=el.textContent.trim();
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
