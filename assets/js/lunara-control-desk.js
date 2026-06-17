(function () {
    var FIELD_DEFS = [
        { key: 'titles', label: 'Titles' },
        { key: 'deks', label: 'Deks / Standfirsts' },
        { key: 'h2s', label: 'H2s' },
        { key: 'pullQuotes', label: 'Pull Quotes' },
        { key: 'socialHooks', label: 'Social Hooks' },
        { key: 'homepagePitch', label: 'Homepage Pitch' },
        { key: 'readinessNotes', label: 'Readiness Notes' },
        { key: 'ledgerOpportunities', label: 'Ledger Opportunities' }
    ];

    var INTENT_LABELS = {
        package: 'Packaging',
        rewrite: 'Rewrite',
        readiness: 'Readiness',
        homepage_pitch: 'Homepage Pitch',
        ledger_links: 'Ledger'
    };

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function findResultTarget(postId, source) {
        var row = source.closest('[data-lunara-row-post]');
        var scoped = row ? qs('[data-lunara-result="' + postId + '"]', row) : null;

        return scoped || qs('[data-lunara-result="' + postId + '"]');
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = String(text || '');
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return escapeHtml(text).replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function intentLabel(intent) {
        return INTENT_LABELS[intent] || String(intent || '').replace(/_/g, ' ');
    }

    function splitLines(text) {
        return String(text || '')
            .split(/\r\n|\r|\n/)
            .map(function (line) {
                return line.trim();
            })
            .filter(Boolean);
    }

    function unique(values) {
        var seen = {};

        return values.filter(function (value) {
            if (seen[value]) {
                return false;
            }

            seen[value] = true;
            return true;
        });
    }

    function itemToLines(item) {
        var keys;
        var parts;

        if (item === null || typeof item === 'undefined') {
            return [];
        }

        if (Array.isArray(item)) {
            return item.reduce(function (lines, child) {
                return lines.concat(itemToLines(child));
            }, []);
        }

        if (typeof item === 'object') {
            keys = ['text', 'title', 'label', 'value', 'quote', 'note', 'person', 'film', 'category', 'ceremony', 'reason', 'url'];
            parts = [];

            keys.forEach(function (key) {
                if (Object.prototype.hasOwnProperty.call(item, key) && typeof item[key] !== 'object') {
                    splitLines(item[key]).forEach(function (line) {
                        parts.push(line);
                    });
                }
            });

            return parts.length ? [unique(parts).join(' - ')] : [];
        }

        return splitLines(item);
    }

    function normalizeLines(value) {
        if (Array.isArray(value)) {
            return value.reduce(function (lines, item) {
                return lines.concat(itemToLines(item));
            }, []).filter(Boolean);
        }

        return itemToLines(value).filter(Boolean);
    }

    function getFieldGroups(data) {
        var fields = data && data.fields && typeof data.fields === 'object' ? data.fields : {};
        var groups = [];

        FIELD_DEFS.forEach(function (field) {
            var lines;

            if (!Object.prototype.hasOwnProperty.call(fields, field.key)) {
                return;
            }

            lines = normalizeLines(fields[field.key]);

            if (!lines.length) {
                return;
            }

            groups.push({
                label: field.label,
                lines: lines
            });
        });

        return groups;
    }

    function formatCreated(value) {
        var date;

        if (!value) {
            return '';
        }

        date = new Date(value);

        if (isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString();
    }

    function renderFieldGroups(groups) {
        if (!groups.length) {
            return (
                '<div class="lunara-control-desk-empty lunara-control-desk-field-empty">' +
                '<p>No structured fields in this snapshot yet.</p>' +
                '<p class="lunara-control-desk-subtle">Use the raw output below if this provider returned plain text.</p>' +
                '</div>'
            );
        }

        return (
            '<div class="lunara-control-desk-field-groups">' +
            groups.map(function (group) {
                var copyAll = group.lines.join('\n');

                return (
                    '<section class="lunara-control-desk-field-group">' +
                    '<div class="lunara-control-desk-field-group-head">' +
                    '<h4>' + escapeHtml(group.label) + '</h4>' +
                    '<div><span>' + escapeHtml(group.lines.length) + '</span>' +
                    '<button type="button" class="button button-small" data-lunara-copy data-lunara-copy-text="' + escapeAttr(copyAll) + '">Copy all</button></div>' +
                    '</div>' +
                    '<ul class="lunara-control-desk-field-list">' +
                    group.lines.map(function (line) {
                        return (
                            '<li class="lunara-control-desk-field-line">' +
                            '<span data-lunara-copy-source>' + escapeHtml(line) + '</span>' +
                            '<button type="button" class="button button-small" data-lunara-copy>Copy</button>' +
                            '</li>'
                        );
                    }).join('') +
                    '</ul>' +
                    '</section>'
                );
            }).join('') +
            '</div>'
        );
    }

    function renderRaw(raw) {
        if (!raw) {
            return '';
        }

        return (
            '<details class="lunara-control-desk-raw">' +
            '<summary>Raw output</summary>' +
            '<pre data-lunara-copy-source>' + escapeHtml(raw) + '</pre>' +
            '<button type="button" class="button button-small" data-lunara-copy>Copy raw</button>' +
            '</details>'
        );
    }

    function renderSuggestion(target, data) {
        var raw = data.rawText || '';
        var summary = data.summary || '';
        var provider = data.provider || '';
        var intent = data.intent || '';
        var created = formatCreated(data.createdAt);
        var groups = getFieldGroups(data);

        target.hidden = false;
        target.innerHTML =
            '<div class="lunara-control-desk-suggestion is-live">' +
            '<div class="lunara-control-desk-suggestion-head">' +
            '<div class="lunara-control-desk-suggestion-meta">' +
            (provider ? '<span class="lunara-control-desk-chip">' + escapeHtml(provider.toUpperCase()) + '</span>' : '') +
            (intent ? '<span class="lunara-control-desk-chip">' + escapeHtml(intentLabel(intent)) + '</span>' : '') +
            (created ? '<span>' + escapeHtml(created) + '</span>' : '') +
            '</div>' +
            (summary ? '<p>' + escapeHtml(summary) + '</p>' : '') +
            '</div>' +
            renderFieldGroups(groups) +
            renderRaw(raw) +
            '</div>';
    }

    function setButtonState(button, text, disabled) {
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent;
        }

        button.textContent = text || button.dataset.originalText;
        button.disabled = !!disabled;
    }

    function requestSuggestion(button) {
        var postId = button.getAttribute('data-post-id');
        var intent = button.getAttribute('data-intent');
        var target = findResultTarget(postId, button);

        if (!postId || !intent || !target || !window.LunaraControlDesk) {
            return;
        }

        target.hidden = false;
        target.textContent = window.LunaraControlDesk.i18n.working;
        setButtonState(button, window.LunaraControlDesk.i18n.working, true);

        window.fetch(window.LunaraControlDesk.suggestUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.LunaraControlDesk.nonce
            },
            body: JSON.stringify({
                postId: parseInt(postId, 10),
                intent: intent
            })
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.message || window.LunaraControlDesk.i18n.failed);
                    }
                    return data;
                });
            })
            .then(function (data) {
                renderSuggestion(target, data);
                setButtonState(button, window.LunaraControlDesk.i18n.ready, false);
                window.setTimeout(function () {
                    setButtonState(button, '', false);
                }, 1800);
            })
            .catch(function (error) {
                target.hidden = false;
                target.innerHTML = '<p class="lunara-control-desk-error">' + escapeHtml(error.message || window.LunaraControlDesk.i18n.failed) + '</p>';
                setButtonState(button, '', false);
            });
    }

    function copyFromButton(button) {
        var text = button.getAttribute('data-lunara-copy-text');
        var container;
        var source;

        if (text === null) {
            container = button.closest('.lunara-control-desk-field-line') ||
                button.closest('.lunara-control-desk-raw') ||
                button.closest('.lunara-control-desk-field-group') ||
                button.closest('.lunara-control-desk-suggestion') ||
                document;
            source = qs('[data-lunara-copy-source]', container);
            text = source ? (source.textContent || '') : '';
        }

        if (!text) {
            return;
        }

        writeClipboard(text).then(function () {
            var original = button.textContent;
            button.textContent = (window.LunaraControlDesk && window.LunaraControlDesk.i18n.copied) || 'Copied.';
            window.setTimeout(function () {
                button.textContent = original;
            }, 1200);
        });
    }

    function writeClipboard(text) {
        var textarea;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        return Promise.resolve();
    }

    function selectSnapshot(button) {
        var shell = button.closest('[data-lunara-suggestion-shell]');
        var selector = button.getAttribute('data-lunara-snapshot-select');
        var target = selector && shell ? qs(selector, shell) : null;

        if (!shell || !target) {
            return;
        }

        qsa('[data-lunara-snapshot-panel]', shell).forEach(function (panel) {
            panel.hidden = true;
        });

        qsa('[data-lunara-snapshot-select]', shell).forEach(function (historyButton) {
            historyButton.classList.remove('is-active');
        });

        target.hidden = false;
        button.classList.add('is-active');
    }

    function carouselItems(form) {
        return qsa('[data-lunara-carousel-item]', form);
    }

    function syncCarouselIds(form) {
        var field = qs('[data-lunara-carousel-ids]', form);
        var items = carouselItems(form);

        if (!field || !items.length) {
            return;
        }

        field.value = items.map(function (item) {
            return item.getAttribute('data-lunara-carousel-id');
        }).filter(Boolean).join(',');
    }

    function toggleCarouselEmpty(form) {
        var list = qs('[data-lunara-carousel-list]', form);
        var empty = qs('[data-lunara-carousel-empty]', form);

        if (!list) {
            return;
        }

        if (carouselItems(form).length) {
            if (empty) {
                empty.remove();
            }
            return;
        }

        if (!empty) {
            list.innerHTML = '<div class="lunara-control-desk-empty" data-lunara-carousel-empty><p>No carousel images selected yet.</p><p>Choose images from the Media Library, then save the visual file.</p></div>';
        }
    }

    function createCarouselCard(attachment) {
        var id = attachment.id || attachment.get && attachment.get('id');
        var title = attachment.title || attachment.get && attachment.get('title') || attachment.filename || attachment.get && attachment.get('filename') || ('Attachment #' + id);
        var url = attachment.url || attachment.get && attachment.get('url') || '';
        var sizes = attachment.sizes || attachment.get && attachment.get('sizes') || {};
        var thumb = sizes.thumbnail && sizes.thumbnail.url ? sizes.thumbnail.url : (sizes.medium && sizes.medium.url ? sizes.medium.url : url);
        var width = attachment.width || attachment.get && attachment.get('width') || '';
        var height = attachment.height || attachment.get && attachment.get('height') || '';
        var dims = width && height ? width + 'x' + height : '';

        return (
            '<article class="lunara-control-desk-carousel-item is-new" data-lunara-carousel-item data-lunara-carousel-id="' + escapeAttr(id) + '">' +
            '<div class="lunara-control-desk-carousel-thumb">' +
            (thumb ? '<img class="lunara-control-desk-carousel-image" src="' + escapeAttr(thumb) + '" alt="" />' : '') +
            '</div>' +
            '<div class="lunara-control-desk-carousel-copy">' +
            '<div class="lunara-control-desk-carousel-title-row">' +
            '<div><strong>' + escapeHtml(title) + '</strong><span>Attachment #' + escapeHtml(id) + (dims ? ' ' + escapeHtml(dims) : '') + '</span></div>' +
            '<div class="lunara-control-desk-carousel-controls">' +
            '<button type="button" class="button button-small" data-lunara-carousel-move="up">Up</button>' +
            '<button type="button" class="button button-small" data-lunara-carousel-move="down">Down</button>' +
            '<button type="button" class="button button-small" data-lunara-carousel-remove>Remove</button>' +
            '</div>' +
            '</div>' +
            '<div class="lunara-control-desk-carousel-fields">' +
            '<label><span>Credit</span><input type="text" name="lunara_journal_carousel_credit[' + escapeAttr(id) + ']" value="" placeholder="Warner Bros. Pictures" /></label>' +
            '<label><span>Source</span><input type="text" name="lunara_journal_carousel_source_name[' + escapeAttr(id) + ']" value="" placeholder="Entertainment Weekly" /></label>' +
            '<label class="lunara-control-desk-carousel-url-field"><span>Source URL</span><input type="url" name="lunara_journal_carousel_source_url[' + escapeAttr(id) + ']" value="" placeholder="https://" /></label>' +
            '</div>' +
            '</div>' +
            '</article>'
        );
    }

    function openCarouselPicker(button) {
        var form = button.closest('[data-lunara-carousel-form]');
        var list = form ? qs('[data-lunara-carousel-list]', form) : null;
        var frame;

        if (!form || !list || !window.wp || !window.wp.media) {
            return;
        }

        frame = window.wp.media({
            title: 'Choose Journal carousel images',
            button: { text: 'Use images' },
            library: { type: 'image' },
            multiple: 'add'
        });

        frame.on('select', function () {
            var existing = {};

            carouselItems(form).forEach(function (item) {
                existing[item.getAttribute('data-lunara-carousel-id')] = true;
            });

            frame.state().get('selection').each(function (model) {
                var data = model.toJSON();
                var id = String(data.id || '');

                if (!id || existing[id]) {
                    return;
                }

                existing[id] = true;
                list.insertAdjacentHTML('beforeend', createCarouselCard(data));
            });

            toggleCarouselEmpty(form);
            syncCarouselIds(form);
        });

        frame.open();
    }

    function normalizeMediaAttachment(model) {
        var attachment = model && model.toJSON ? model.toJSON() : (model || {});
        var sizes = attachment.sizes || {};
        var thumb = sizes.medium && sizes.medium.url ? sizes.medium.url : (sizes.thumbnail && sizes.thumbnail.url ? sizes.thumbnail.url : attachment.url);
        var width = attachment.width || '';
        var height = attachment.height || '';

        if ((!width || !height) && sizes.full) {
            width = width || sizes.full.width || '';
            height = height || sizes.full.height || '';
        }

        return {
            id: attachment.id || '',
            title: attachment.title || attachment.filename || ('Attachment #' + (attachment.id || '')),
            thumb: thumb || '',
            meta: 'Attachment #' + (attachment.id || '') + (width && height ? ' / ' + width + 'x' + height : '')
        };
    }

    function renderBrandMedia(control, attachment) {
        var input = qs('[data-lunara-brand-media-input]', control);
        var preview = qs('[data-lunara-brand-media-preview]', control);
        var thumb = qs('.lunara-control-desk-brand-thumb', control);
        var title = qs('[data-lunara-brand-media-title]', control);
        var meta = qs('[data-lunara-brand-media-meta]', control);

        if (input) {
            input.value = attachment.id || '0';
        }

        if (thumb) {
            thumb.innerHTML = attachment.thumb ? '<img src="' + escapeAttr(attachment.thumb) + '" alt="" />' : '';
        }

        if (title) {
            title.textContent = attachment.title || 'Using fallback/default';
        }

        if (meta) {
            meta.textContent = attachment.meta || 'No custom image selected here.';
        }

        if (preview) {
            preview.classList.toggle('is-ready', !!attachment.id);
            preview.classList.toggle('is-empty', !attachment.id);
        }
    }

    function openBrandMediaPicker(button) {
        var control = button.closest('[data-lunara-brand-media-control]');
        var frame;

        if (!control || !window.wp || !window.wp.media) {
            return;
        }

        frame = window.wp.media({
            title: button.getAttribute('data-title') || 'Choose image',
            button: { text: button.getAttribute('data-button') || 'Use image' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            var model = frame.state().get('selection').first();

            if (!model) {
                return;
            }

            renderBrandMedia(control, normalizeMediaAttachment(model));
        });

        frame.open();
    }

    function clearBrandMedia(button) {
        var control = button.closest('[data-lunara-brand-media-control]');

        if (!control) {
            return;
        }

        renderBrandMedia(control, {
            id: 0,
            title: 'Using fallback/default',
            thumb: '',
            meta: 'No custom image selected here.'
        });
    }

    function clampNumber(value, min, max) {
        value = parseInt(value, 10);
        min = parseInt(min, 10);
        max = parseInt(max, 10);

        if (isNaN(value)) {
            value = min;
        }

        return Math.min(Math.max(value, min), max);
    }

    function syncBrandNumber(control, source) {
        var range = qs('[data-lunara-brand-range]', control);
        var number = qs('[data-lunara-brand-number]', control);
        var sourceInput = source || range || number;
        var min = sourceInput ? sourceInput.getAttribute('min') : 0;
        var max = sourceInput ? sourceInput.getAttribute('max') : 9999;
        var value = clampNumber(sourceInput ? sourceInput.value : 0, min, max);

        if (range) {
            range.value = value;
        }

        if (number) {
            number.value = value;
        }
    }

    function moveCarouselItem(button) {
        var item = button.closest('[data-lunara-carousel-item]');
        var form = button.closest('[data-lunara-carousel-form]');
        var direction = button.getAttribute('data-lunara-carousel-move');

        if (!item || !form) {
            return;
        }

        if ('up' === direction && item.previousElementSibling && item.previousElementSibling.hasAttribute('data-lunara-carousel-item')) {
            item.parentNode.insertBefore(item, item.previousElementSibling);
        }

        if ('down' === direction && item.nextElementSibling) {
            item.parentNode.insertBefore(item.nextElementSibling, item);
        }

        syncCarouselIds(form);
    }

    function removeCarouselItem(button) {
        var form = button.closest('[data-lunara-carousel-form]');
        var item = button.closest('[data-lunara-carousel-item]');
        var field = form ? qs('[data-lunara-carousel-ids]', form) : null;

        if (!form || !item) {
            return;
        }

        item.remove();
        if (!carouselItems(form).length && field) {
            field.value = '';
        } else {
            syncCarouselIds(form);
        }
        toggleCarouselEmpty(form);
    }

    document.addEventListener('DOMContentLoaded', function () {
        qsa('.lunara-control-desk-suggest').forEach(function (button) {
            button.addEventListener('click', function () {
                requestSuggestion(button);
            });
        });

        document.addEventListener('click', function (event) {
            var historyButton = event.target.closest('[data-lunara-snapshot-select]');
            var copyButton = event.target.closest('[data-lunara-copy]');
            var printButton = event.target.closest('[data-lunara-print]');
            var brandPicker = event.target.closest('[data-lunara-brand-media-picker]');
            var brandClear = event.target.closest('[data-lunara-brand-media-clear]');
            var carouselPicker = event.target.closest('[data-lunara-carousel-picker]');
            var carouselMove = event.target.closest('[data-lunara-carousel-move]');
            var carouselRemove = event.target.closest('[data-lunara-carousel-remove]');

            if (historyButton) {
                selectSnapshot(historyButton);
                return;
            }

            if (copyButton) {
                copyFromButton(copyButton);
                return;
            }

            if (printButton) {
                window.print();
                return;
            }

            if (brandPicker) {
                openBrandMediaPicker(brandPicker);
                return;
            }

            if (brandClear) {
                clearBrandMedia(brandClear);
                return;
            }

            if (carouselPicker) {
                openCarouselPicker(carouselPicker);
                return;
            }

            if (carouselMove) {
                moveCarouselItem(carouselMove);
                return;
            }

            if (carouselRemove) {
                removeCarouselItem(carouselRemove);
            }
        });

        qsa('[data-lunara-carousel-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                syncCarouselIds(form);
            });
        });

        qsa('[data-lunara-brand-number-control]').forEach(function (control) {
            var range = qs('[data-lunara-brand-range]', control);
            var number = qs('[data-lunara-brand-number]', control);

            if (range) {
                range.addEventListener('input', function () {
                    syncBrandNumber(control, range);
                });
            }

            if (number) {
                number.addEventListener('input', function () {
                    syncBrandNumber(control, number);
                });
                number.addEventListener('change', function () {
                    syncBrandNumber(control, number);
                });
            }
        });
    });
})();
