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

        function getHTML(parent, selector) {
            var el = parent.querySelector(selector);
            return el ? el.innerHTML : '';
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

            var posterHTML = getHTML(card, '.lunara-lore-card-poster') || getHTML(card, '.lunara-ledger-story-poster');
            var eyebrow    = getText(card, '.lunara-ledger-story-year');
            var title      = getText(card, '.lunara-ledger-story-title');
            var meta       = getText(card, '.lunara-ledger-story-categories');
            var body       = getText(card, '.lunara-ledger-story-summary');
            var link       = card.querySelector('.lunara-ledger-story-link');
            var url        = link ? link.getAttribute('href') : '#';
            var backdrop   = (card.getAttribute('data-lunara-lore-backdrop') || '').trim();

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

            var html = '';
            html += '<button type="button" class="lunara-lore-detail-close" aria-label="Close detail">\u2715</button>';
            html += '<div class="lunara-lore-detail-poster">' + posterHTML + '</div>';
            html += '<div class="lunara-lore-detail-copy">';
            if (eyebrow) html += '<p class="lunara-lore-detail-eyebrow">' + eyebrow + '</p>';
            if (title)   html += '<h3 class="lunara-lore-detail-title">' + title + '</h3>';
            if (meta)    html += '<p class="lunara-lore-detail-meta">' + meta + '</p>';
            if (body)    html += '<p class="lunara-lore-detail-body">' + body + '</p>';
            if (url && url !== '#') {
                html += '<a class="lunara-lore-detail-cta" href="' + url + '">Open in the Ledger \u2192</a>';
            }
            html += '</div>';
            detail.innerHTML = html;

            wrap.appendChild(detail);
            section.classList.add('has-expanded');
            currentDetail = detail;

            // Focus the close button for a11y
            var closeBtn = detail.querySelector('.lunara-lore-detail-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDetail();
                });
                setTimeout(function () { closeBtn.focus(); }, 60);
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
