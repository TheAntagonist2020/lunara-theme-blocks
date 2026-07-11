/**
 * Generated from the verified inline runtime during Phase 1C.
 * Keep behavior route-gated and free of render-blocking dependencies.
 */

(function(){
        var stage=document.querySelector('.lunara-hero-cinema-stage');
        if(!stage)return;
        var slides=stage.querySelectorAll('.lunara-hero-cinema-slide');
        var pips=stage.querySelectorAll('.lunara-hero-cinema-pip');
        if(slides.length<2)return;
        var current=0;
        var interval=5500;
        var timer=null;
        var paused=false;
        function goTo(idx){
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden','true');
            if(pips[current])pips[current].classList.remove('is-active');
            current=idx%slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden','false');
            if(pips[current])pips[current].classList.add('is-active');
        }
        function next(){goTo(current+1);}
        function startAuto(){
            if(timer||paused||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
            timer=setInterval(next,interval);
        }
        function stopAuto(){if(timer){clearInterval(timer);timer=null;}}
        stage.addEventListener('mouseenter',function(){paused=true;stopAuto();});
        stage.addEventListener('mouseleave',function(){paused=false;startAuto();});
        stage.addEventListener('focusin',function(){paused=true;stopAuto();});
        stage.addEventListener('focusout',function(){paused=false;startAuto();});
        pips.forEach(function(pip){
            pip.addEventListener('click',function(){
                stopAuto();
                goTo(parseInt(pip.getAttribute('data-slide'),10));
                if(!paused)startAuto();
            });
        });
        startAuto();
    })();

(function () {
        'use strict';

        var section = document.querySelector('.lunara-home-oscar-story-section');
        if (!section) return;
        var wrap  = section.querySelector('.lunara-ledger-carousel-wrap');
        var track = section.querySelector('[data-lunara-carousel-track]');
        if (!wrap || !track) return;

        var currentDetail = null;

        function getText(parent, selector) {
            var el = parent.querySelector(selector);
            return el ? (el.textContent || '').trim() : '';
        }

        function safeHttpUrl(value) {
            try {
                var parsed = new URL(value || '', document.baseURI);
                return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? parsed.href : '';
            } catch (error) {
                return '';
            }
        }

        function appendTextElement(parent, tagName, className, text) {
            if (!text) return null;
            var element = document.createElement(tagName);
            element.className = className;
            element.textContent = text;
            parent.appendChild(element);
            return element;
        }

        function closeDetail() {
            if (!currentDetail) return;
            var detail = currentDetail;
            currentDetail = null;
            detail.classList.add('is-closing');
            section.classList.remove('has-expanded');
            setTimeout(function () {
                if (detail && detail.parentNode) {
                    detail.parentNode.removeChild(detail);
                }
            }, 280);
        }

        function openDetail(card) {
            if (currentDetail) closeDetail();

            var posterSource = card.querySelector('.lunara-lore-card-poster, .lunara-ledger-story-poster');
            var eyebrow    = getText(card, '.lunara-ledger-story-year');
            var title      = getText(card, '.lunara-ledger-story-title');
            var meta       = getText(card, '.lunara-ledger-story-categories');
            var body       = getText(card, '.lunara-ledger-story-summary');
            var link       = card.querySelector('.lunara-ledger-story-link');
            var url        = safeHttpUrl(link ? link.href : '');
            var backdrop   = safeHttpUrl(card.getAttribute('data-lunara-lore-backdrop'));

            var detail = document.createElement('div');
            detail.className = 'lunara-lore-detail';
            detail.setAttribute('role', 'dialog');
            detail.setAttribute('aria-modal', 'true');
            detail.setAttribute('aria-label', title || 'Oscar lore detail');

            // Apply TMDB backdrop as cinematic background with strong overlay for legibility
            if (backdrop) {
                detail.classList.add('has-backdrop');
                detail.style.backgroundImage =
                    'linear-gradient(130deg, rgba(7,15,26,.86) 0%, rgba(7,15,26,.68) 45%, rgba(7,15,26,.94) 100%), ' +
                    'url("' + backdrop + '")';
                detail.style.backgroundSize = 'cover';
                detail.style.backgroundPosition = 'center';
            }

            var closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'lunara-lore-detail-close';
            closeButton.setAttribute('aria-label', 'Close detail');
            closeButton.textContent = '\u2715';
            detail.appendChild(closeButton);

            var poster = document.createElement('div');
            poster.className = 'lunara-lore-detail-poster';
            if (posterSource) {
                poster.appendChild(posterSource.cloneNode(true));
            }
            detail.appendChild(poster);

            var copy = document.createElement('div');
            copy.className = 'lunara-lore-detail-copy';
            appendTextElement(copy, 'p', 'lunara-lore-detail-eyebrow', eyebrow);
            appendTextElement(copy, 'h3', 'lunara-lore-detail-title', title);
            appendTextElement(copy, 'p', 'lunara-lore-detail-meta', meta);
            appendTextElement(copy, 'p', 'lunara-lore-detail-body', body);
            if (url) {
                var cta = appendTextElement(copy, 'a', 'lunara-lore-detail-cta', 'Open in the Ledger \u2192');
                cta.href = url;
            }
            detail.appendChild(copy);

            wrap.appendChild(detail);
            section.classList.add('has-expanded');
            currentDetail = detail;

            // Focus the close button for a11y
            if (closeButton) {
                closeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDetail();
                });
                setTimeout(function () { closeButton.focus(); }, 60);
            }
        }

        // Intercept lore card clicks
        track.addEventListener('click', function (e) {
            // Let modifier-key clicks open in new tab normally
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            var link = e.target.closest('.lunara-lore-card .lunara-ledger-story-link');
            if (!link) return;

            e.preventDefault();
            var card = link.closest('.lunara-lore-card');
            if (card) openDetail(card);
        });

        // Escape to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && currentDetail) {
                closeDetail();
            }
        });

        // Click outside the detail to close
        document.addEventListener('click', function (e) {
            if (!currentDetail) return;
            if (currentDetail.contains(e.target)) return;
            // Ignore clicks that are already handled by track (new card click)
            if (track.contains(e.target)) return;
            closeDetail();
        });
    })();
