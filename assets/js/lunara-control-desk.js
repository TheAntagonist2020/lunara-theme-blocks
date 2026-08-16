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

    function renderImageSource(control, attachment) {
        var input = qs('[data-lunara-image-source-input]', control);
        var preview = qs('[data-lunara-image-source-preview]', control);
        var thumb = qs('.lunara-control-desk-image-source-thumb', control);
        var title = qs('[data-lunara-image-source-title]', control);
        var meta = qs('[data-lunara-image-source-meta]', control);

        if (input) {
            input.value = attachment.id || '0';
        }

        if (thumb) {
            thumb.innerHTML = attachment.thumb ? '<img src="' + escapeAttr(attachment.thumb) + '" alt="" />' : '';
        }

        if (title) {
            title.textContent = attachment.title || 'No replacement selected';
        }

        if (meta) {
            meta.textContent = attachment.meta || 'Choose a Media Library image, then save this row.';
        }

        if (preview) {
            preview.classList.toggle('is-ready', !!attachment.id);
            preview.classList.toggle('is-empty', !attachment.id);
        }
    }

    function openImageSourcePicker(button) {
        var control = button.closest('[data-lunara-image-source-control]');
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

            renderImageSource(control, normalizeMediaAttachment(model));
        });

        frame.open();
    }

    function clearImageSource(button) {
        var control = button.closest('[data-lunara-image-source-control]');

        if (!control) {
            return;
        }

        renderImageSource(control, {
            id: 0,
            title: 'No replacement selected',
            thumb: '',
            meta: 'Choose a Media Library image, then save this row.'
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

    function archiveStudioVariant(element) {
        var scope = element && element.closest ? element.closest('[data-lunara-archive-studio]') : null;

        return scope && 'reviews' === scope.getAttribute('data-lunara-archive-studio') ? 'reviews' : 'journal';
    }

    function archiveStudioGalleryPrefix(element) {
        return 'reviews' === archiveStudioVariant(element) ? 'lunara_reviews_archive_gallery_' : 'lunara_journal_archive_gallery_';
    }

    function filterJournalPostOptions(input) {
        var selector = input.getAttribute('data-lunara-journal-post-filter');
        var select = selector ? qs(selector) : null;
        var needle = String(input.value || '').toLowerCase().trim();

        if (!select) {
            return;
        }

        qsa('option', select).forEach(function (option, index) {
            option.hidden = index > 0 && !!needle && option.textContent.toLowerCase().indexOf(needle) === -1;
        });
    }

    function setJournalPostSearchStatus(input, message) {
        var status = input.parentNode ? qs('[data-lunara-journal-post-search-status]', input.parentNode) : null;

        if (status) {
            status.textContent = message || '';
        }
    }

    function setJournalPostSearchBusy(input, isBusy) {
        input.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }

    function replaceJournalPostOptions(select, items) {
        var currentValue = String(select.value || '0');
        var currentOption = select.options[select.selectedIndex] || null;
        var currentText = currentOption ? currentOption.textContent : '';
        var placeholder = select.options.length ? select.options[0].textContent : 'Choose a published Journal file';
        var seen = {};

        while (select.firstChild) {
            select.removeChild(select.firstChild);
        }

        function appendOption(value, label) {
            var option = document.createElement('option');
            option.value = String(value);
            option.textContent = String(label || '');
            select.appendChild(option);
            seen[String(value)] = true;
        }

        appendOption('0', placeholder);
        if ('0' !== currentValue && currentText) {
            appendOption(currentValue, currentText);
        }

        (Array.isArray(items) ? items : []).slice(0, 20).forEach(function (item) {
            var postId = item && /^\d+$/.test(String(item.id || '')) ? String(item.id) : '';

            if (!postId || seen[postId]) {
                return;
            }
            appendOption(postId, item.text || ('#' + postId));
        });

        select.value = seen[currentValue] ? currentValue : '0';
    }

    function searchJournalPostOptions(input) {
        var config = window.LunaraControlDesk || {};
        var i18n = config.i18n || {};
        var selector = input.getAttribute('data-lunara-journal-post-filter');
        var select = selector ? qs(selector) : null;
        var needle = String(input.value || '').trim();
        var variant = archiveStudioVariant(input);
        var searchNonce = 'reviews' === variant ? config.reviewsSearchNonce : config.journalSearchNonce;
        var searchingText = 'reviews' === variant
            ? (i18n.reviewsSearching || 'Searching published Reviews…')
            : (i18n.journalSearching || 'Searching published Journal files…');
        var readyText = 'reviews' === variant
            ? (i18n.reviewsSearchReady || 'Published matches updated.')
            : (i18n.journalSearchReady || 'Published matches updated.');
        var failedText = 'reviews' === variant
            ? (i18n.reviewsSearchFailed || 'Search could not be completed. Your current selection is unchanged.')
            : (i18n.journalSearchFailed || 'Search could not be completed. Your current selection is unchanged.');
        var requestNumber;

        filterJournalPostOptions(input);
        window.clearTimeout(input._lunaraJournalSearchTimer);
        input._lunaraJournalSearchRequest = (input._lunaraJournalSearchRequest || 0) + 1;
        requestNumber = input._lunaraJournalSearchRequest;

        if (!select || !config.journalSearchUrl || !searchNonce || (!/^\d+$/.test(needle) && needle.length < 2)) {
            setJournalPostSearchBusy(input, false);
            setJournalPostSearchStatus(input, '');
            return;
        }

        input._lunaraJournalSearchTimer = window.setTimeout(function () {
            var searchAction = 'reviews' === variant ? 'lunara_reviews_archive_studio_search' : 'lunara_journal_archive_studio_search';
            var requestUrl = config.journalSearchUrl
                + (config.journalSearchUrl.indexOf('?') === -1 ? '?' : '&')
                + 'action=' + searchAction
                + '&nonce=' + encodeURIComponent(searchNonce)
                + '&q=' + encodeURIComponent(needle.slice(0, 100));

            setJournalPostSearchBusy(input, true);
            setJournalPostSearchStatus(input, searchingText);
            window.fetch(requestUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Archive search failed.');
                }
                return response.json();
            }).then(function (payload) {
                if (requestNumber !== input._lunaraJournalSearchRequest) {
                    return;
                }
                if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.items)) {
                    throw new Error('Archive search failed.');
                }
                replaceJournalPostOptions(select, payload.data.items);
                setJournalPostSearchBusy(input, false);
                setJournalPostSearchStatus(input, readyText);
            }).catch(function () {
                if (requestNumber !== input._lunaraJournalSearchRequest) {
                    return;
                }
                setJournalPostSearchBusy(input, false);
                setJournalPostSearchStatus(input, failedText);
            });
        }, 250);
    }

    function createJournalCuratedItem(postId, label, fieldName) {
        var item = document.createElement('li');
        var text = document.createElement('span');
        var input = document.createElement('input');
        var actions = document.createElement('span');

        item.setAttribute('data-lunara-journal-curated-item', '');
        item.setAttribute('data-post-id', postId);
        text.textContent = label;
        input.type = 'hidden';
        input.name = fieldName || 'lunara_journal_archive_curated_ids[]';
        input.value = postId;
        actions.className = 'lunara-control-desk-actions';

        [
            { label: 'Up', attr: 'data-lunara-journal-curated-move', value: 'up' },
            { label: 'Down', attr: 'data-lunara-journal-curated-move', value: 'down' },
            { label: 'Remove', attr: 'data-lunara-journal-curated-remove', value: '' }
        ].forEach(function (spec) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'button button-small';
            button.textContent = spec.label;
            button.setAttribute(spec.attr, spec.value);
            actions.appendChild(button);
        });

        item.appendChild(text);
        item.appendChild(input);
        item.appendChild(actions);
        return item;
    }

    function addJournalCuratedItem(button) {
        var shell = button.closest('[data-lunara-journal-curation]');
        var picker = shell ? qs('[data-lunara-journal-curated-picker]', shell) : null;
        var list = shell ? qs('[data-lunara-journal-curated-list]', shell) : null;
        var postId = picker ? String(picker.value || '') : '';

        if (!picker || !list || !postId || '0' === postId || qsa('[data-post-id="' + postId + '"]', list).length || qsa('[data-lunara-journal-curated-item]', list).length >= 24) {
            return;
        }

        list.appendChild(createJournalCuratedItem(
            postId,
            picker.options[picker.selectedIndex].textContent,
            'reviews' === archiveStudioVariant(shell) ? 'lunara_reviews_archive_curated_ids[]' : 'lunara_journal_archive_curated_ids[]'
        ));
    }

    function moveJournalCuratedItem(button) {
        var item = button.closest('[data-lunara-journal-curated-item]');
        var direction = button.getAttribute('data-lunara-journal-curated-move');

        if (!item) {
            return;
        }
        if ('up' === direction && item.previousElementSibling) {
            item.parentNode.insertBefore(item, item.previousElementSibling);
        }
        if ('down' === direction && item.nextElementSibling) {
            item.parentNode.insertBefore(item.nextElementSibling, item);
        }
    }

    function journalArchiveGalleryItems(shell) {
        return qsa('[data-lunara-journal-archive-gallery-item]', shell);
    }

    function syncJournalArchiveGallery(shell) {
        var field = qs('[data-lunara-journal-archive-gallery-ids]', shell);

        if (!field) {
            return;
        }

        field.value = journalArchiveGalleryItems(shell).map(function (item) {
            return item.getAttribute('data-attachment-id');
        }).filter(Boolean).join(',');
    }

    function toggleJournalArchiveGalleryEmpty(shell) {
        var list = qs('[data-lunara-journal-archive-gallery-list]', shell);
        var empty = qs('[data-lunara-journal-archive-gallery-empty]', shell);

        if (!list) {
            return;
        }
        if (journalArchiveGalleryItems(shell).length) {
            if (empty) {
                empty.remove();
            }
            return;
        }
        if (!empty) {
            list.innerHTML = '<div class="lunara-control-desk-empty" data-lunara-journal-archive-gallery-empty><p>' + (
                'reviews' === archiveStudioVariant(shell)
                    ? 'No archive gallery images selected. The public Reviews archive has no gallery wrapper or reserved space.'
                    : 'No archive gallery images selected. The public Journal has no gallery wrapper or reserved space.'
            ) + '</p></div>';
        }
    }

    function journalArchiveGalleryField(field, id, label, type, value, required, namePrefix) {
        var tag = 'caption' === field ? 'textarea' : 'input';
        var input = '<' + tag + ' data-lunara-journal-archive-gallery-field="' + escapeAttr(field) + '" name="' + escapeAttr(namePrefix || 'lunara_journal_archive_gallery_') + escapeAttr(field) + '[' + escapeAttr(id) + ']"';
        var limits = { alt: 180, caption: 360, link_url: 2048, credit: 180, source: 180, source_url: 2048 };

        if ('input' === tag) {
            input += ' type="' + escapeAttr(type || 'text') + '" value="' + escapeAttr(value || '') + '"';
        }
        if ('focal_x' === field || 'focal_y' === field) {
            input += ' min="0" max="100"';
        }
        if (limits[field]) {
            input += ' maxlength="' + limits[field] + '"';
        }
        if (required) {
            input += ' required';
        }
        input += '>';
        if ('textarea' === tag) {
            input += escapeHtml(value || '') + '</textarea>';
        }
        return '<label><span>' + escapeHtml(label) + '</span>' + input + '</label>';
    }

    function createJournalArchiveGalleryCard(model, namePrefix) {
        var raw = model && model.toJSON ? model.toJSON() : (model || {});
        var attachment = normalizeMediaAttachment(raw);
        var id = String(attachment.id || '');
        var alt = raw.alt || '';
        var caption = raw.caption || '';

        return (
            '<article class="lunara-control-desk-carousel-item lunara-journal-archive-gallery-editor-item is-new" data-lunara-journal-archive-gallery-item data-attachment-id="' + escapeAttr(id) + '">' +
            '<div class="lunara-control-desk-carousel-thumb">' + (attachment.thumb ? '<img src="' + escapeAttr(attachment.thumb) + '" alt="">' : '') + '</div>' +
            '<div class="lunara-control-desk-carousel-copy"><div class="lunara-control-desk-carousel-title-row"><div><strong data-lunara-journal-archive-gallery-item-title>' + escapeHtml(attachment.title) + '</strong><span>' + escapeHtml(attachment.meta) + '</span></div>' +
            '<div class="lunara-control-desk-carousel-controls"><button type="button" class="button button-small" data-lunara-journal-archive-gallery-move="up">Up</button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-move="down">Down</button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-replace>Replace</button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-remove>Remove</button></div></div>' +
            '<div class="lunara-control-desk-carousel-fields">' +
            journalArchiveGalleryField('alt', id, 'Alt text', 'text', alt, true, namePrefix) +
            journalArchiveGalleryField('caption', id, 'Caption', 'text', caption, false, namePrefix) +
            journalArchiveGalleryField('link_url', id, 'Optional image link', 'url', '', false, namePrefix) +
            journalArchiveGalleryField('credit', id, 'Credit', 'text', '', true, namePrefix) +
            journalArchiveGalleryField('source', id, 'Source name', 'text', '', true, namePrefix) +
            journalArchiveGalleryField('source_url', id, 'Source URL', 'url', '', true, namePrefix) +
            journalArchiveGalleryField('focal_x', id, 'Focal X', 'number', '50', false, namePrefix) +
            journalArchiveGalleryField('focal_y', id, 'Focal Y', 'number', '50', false, namePrefix) +
            '</div></div></article>'
        );
    }

    function openJournalArchiveGalleryPicker(button) {
        var shell = button.closest('[data-lunara-journal-archive-gallery-form]');
        var list = shell ? qs('[data-lunara-journal-archive-gallery-list]', shell) : null;
        var namePrefix = shell ? archiveStudioGalleryPrefix(shell) : '';
        var frame;

        if (!shell || !list || !window.wp || !window.wp.media) {
            return;
        }
        frame = window.wp.media({
            title: 'reviews' === archiveStudioVariant(shell) ? 'Add Reviews archive gallery images' : 'Add Journal archive gallery images',
            button: { text: 'Add images' },
            library: { type: 'image' },
            multiple: 'add'
        });
        frame.on('select', function () {
            var existing = {};

            journalArchiveGalleryItems(shell).forEach(function (item) {
                existing[item.getAttribute('data-attachment-id')] = true;
            });
            frame.state().get('selection').each(function (model) {
                var id = String(model.get('id') || '');

                if (!id || existing[id] || journalArchiveGalleryItems(shell).length >= 12) {
                    return;
                }
                existing[id] = true;
                list.insertAdjacentHTML('beforeend', createJournalArchiveGalleryCard(model, namePrefix));
            });
            toggleJournalArchiveGalleryEmpty(shell);
            syncJournalArchiveGallery(shell);
        });
        frame.open();
    }

    function replaceJournalArchiveGalleryItem(button) {
        var item = button.closest('[data-lunara-journal-archive-gallery-item]');
        var shell = button.closest('[data-lunara-journal-archive-gallery-form]');
        var frame;

        if (!item || !shell || !window.wp || !window.wp.media) {
            return;
        }
        frame = window.wp.media({ title: 'Replace archive gallery image', button: { text: 'Replace image' }, library: { type: 'image' }, multiple: false });
        frame.on('select', function () {
            var model = frame.state().get('selection').first();
            var id = model ? String(model.get('id') || '') : '';
            var currentId = item.getAttribute('data-attachment-id');
            var duplicate = id ? qs('[data-lunara-journal-archive-gallery-item][data-attachment-id="' + id + '"]', shell) : null;

            // Choosing the same attachment must preserve every unsaved field.
            if (!id || id === currentId || (duplicate && duplicate !== item)) {
                return;
            }
            item.insertAdjacentHTML('afterend', createJournalArchiveGalleryCard(model, archiveStudioGalleryPrefix(shell)));
            item.remove();
            syncJournalArchiveGallery(shell);
        });
        frame.open();
    }

    function moveJournalArchiveGalleryItem(button) {
        var item = button.closest('[data-lunara-journal-archive-gallery-item]');
        var shell = button.closest('[data-lunara-journal-archive-gallery-form]');
        var direction = button.getAttribute('data-lunara-journal-archive-gallery-move');

        if (!item || !shell) {
            return;
        }
        if ('up' === direction && item.previousElementSibling && item.previousElementSibling.hasAttribute('data-lunara-journal-archive-gallery-item')) {
            item.parentNode.insertBefore(item, item.previousElementSibling);
        }
        if ('down' === direction && item.nextElementSibling && item.nextElementSibling.hasAttribute('data-lunara-journal-archive-gallery-item')) {
            item.parentNode.insertBefore(item.nextElementSibling, item);
        }
        syncJournalArchiveGallery(shell);
    }

    function removeJournalArchiveGalleryItem(button) {
        var item = button.closest('[data-lunara-journal-archive-gallery-item]');
        var shell = button.closest('[data-lunara-journal-archive-gallery-form]');

        if (item && shell) {
            item.remove();
            syncJournalArchiveGallery(shell);
            toggleJournalArchiveGalleryEmpty(shell);
        }
    }

    function clearJournalArchiveGallery(button) {
        var shell = button.closest('[data-lunara-journal-archive-gallery-form]');
        var list = shell ? qs('[data-lunara-journal-archive-gallery-list]', shell) : null;

        if (!shell || !list) {
            return;
        }
        list.innerHTML = '';
        syncJournalArchiveGallery(shell);
        toggleJournalArchiveGalleryEmpty(shell);
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
            var imageSourcePicker = event.target.closest('[data-lunara-image-source-picker]');
            var imageSourceClear = event.target.closest('[data-lunara-image-source-clear]');
            var carouselPicker = event.target.closest('[data-lunara-carousel-picker]');
            var carouselMove = event.target.closest('[data-lunara-carousel-move]');
            var carouselRemove = event.target.closest('[data-lunara-carousel-remove]');
            var curatedAdd = event.target.closest('[data-lunara-journal-curated-add]');
            var curatedMove = event.target.closest('[data-lunara-journal-curated-move]');
            var curatedRemove = event.target.closest('[data-lunara-journal-curated-remove]');
            var archiveGalleryPicker = event.target.closest('[data-lunara-journal-archive-gallery-picker]');
            var archiveGalleryMove = event.target.closest('[data-lunara-journal-archive-gallery-move]');
            var archiveGalleryReplace = event.target.closest('[data-lunara-journal-archive-gallery-replace]');
            var archiveGalleryRemove = event.target.closest('[data-lunara-journal-archive-gallery-remove]');
            var archiveGalleryClear = event.target.closest('[data-lunara-journal-archive-gallery-clear]');

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

            if (imageSourcePicker) {
                openImageSourcePicker(imageSourcePicker);
                return;
            }

            if (imageSourceClear) {
                clearImageSource(imageSourceClear);
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
                return;
            }

            if (curatedAdd) {
                addJournalCuratedItem(curatedAdd);
                return;
            }

            if (curatedMove) {
                moveJournalCuratedItem(curatedMove);
                return;
            }

            if (curatedRemove) {
                var curatedItem = curatedRemove.closest('[data-lunara-journal-curated-item]');
                if (curatedItem) {
                    curatedItem.remove();
                }
                return;
            }

            if (archiveGalleryPicker) {
                openJournalArchiveGalleryPicker(archiveGalleryPicker);
                return;
            }

            if (archiveGalleryMove) {
                moveJournalArchiveGalleryItem(archiveGalleryMove);
                return;
            }

            if (archiveGalleryReplace) {
                replaceJournalArchiveGalleryItem(archiveGalleryReplace);
                return;
            }

            if (archiveGalleryRemove) {
                removeJournalArchiveGalleryItem(archiveGalleryRemove);
                return;
            }

            if (archiveGalleryClear) {
                clearJournalArchiveGallery(archiveGalleryClear);
            }
        });

        qsa('[data-lunara-journal-post-filter]').forEach(function (input) {
            input.addEventListener('input', function () {
                searchJournalPostOptions(input);
            });
        });

        qsa('[data-lunara-carousel-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                syncCarouselIds(form);
            });
        });

        qsa('[data-lunara-journal-archive-gallery-form]').forEach(function (shell) {
            var form = shell.closest('form');
            if (form) {
                form.addEventListener('submit', function () {
                    syncJournalArchiveGallery(shell);
                });
            }
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
