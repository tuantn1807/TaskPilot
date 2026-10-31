/**
 * TaskPilot — @Mention Autocomplete for CKEditor 4 / Textareas
 * & Real-time WebSocket / Live Sync for Task Comments & Kanban
 */
(function () {
    'use strict';

    // Inject styles for @Mention dropdown, mention chips, and real-time toast/indicator
    var styleEl = document.createElement('style');
    styleEl.textContent = [
        '.tp-mention-popup {',
        '  position: fixed;',
        '  z-index: 99999;',
        '  background: #ffffff;',
        '  border: 1px solid #cbd5e1;',
        '  border-radius: 10px;',
        '  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);',
        '  width: 290px;',
        '  max-height: 260px;',
        '  overflow-y: auto;',
        '  padding: 6px;',
        '  display: none;',
        '  font-family: Inter, -apple-system, BlinkMacSystemFont, sans-serif;',
        '}',
        '.tp-mention-popup-header {',
        '  font-size: 11px;',
        '  font-weight: 700;',
        '  text-transform: uppercase;',
        '  letter-spacing: 0.5px;',
        '  color: #64748b;',
        '  padding: 4px 8px 6px;',
        '  border-bottom: 1px solid #f1f5f9;',
        '  margin-bottom: 4px;',
        '}',
        '.tp-mention-option {',
        '  display: flex;',
        '  align-items: center;',
        '  gap: 10px;',
        '  padding: 7px 10px;',
        '  border-radius: 8px;',
        '  cursor: pointer;',
        '  transition: background 0.12s ease;',
        '}',
        '.tp-mention-option:hover, .tp-mention-option.is-active {',
        '  background: #eff6ff;',
        '}',
        '.tp-mention-avatar {',
        '  width: 30px;',
        '  height: 30px;',
        '  border-radius: 50%;',
        '  background: linear-gradient(135deg, #2563eb, #4f46e5);',
        '  color: #ffffff;',
        '  font-size: 11px;',
        '  font-weight: 700;',
        '  display: flex;',
        '  align-items: center;',
        '  justify-content: center;',
        '  flex-shrink: 0;',
        '}',
        '.tp-mention-info {',
        '  min-width: 0;',
        '  flex: 1;',
        '}',
        '.tp-mention-name {',
        '  font-size: 13px;',
        '  font-weight: 700;',
        '  color: #0f172a;',
        '  white-space: nowrap;',
        '  overflow: hidden;',
        '  text-overflow: ellipsis;',
        '}',
        '.tp-mention-meta {',
        '  font-size: 11.5px;',
        '  color: #64748b;',
        '  white-space: nowrap;',
        '  overflow: hidden;',
        '  text-overflow: ellipsis;',
        '}',
        '.tp-mention {',
        '  background: #eff6ff;',
        '  color: #2563eb;',
        '  padding: 1px 6px;',
        '  border-radius: 5px;',
        '  font-weight: 700;',
        '  text-decoration: none;',
        '  display: inline-block;',
        '}',
        '.tp-live-badge {',
        '  display: inline-flex;',
        '  align-items: center;',
        '  gap: 6px;',
        '  font-size: 11.5px;',
        '  font-weight: 700;',
        '  color: #059669;',
        '  background: #ecfdf5;',
        '  border: 1px solid #a7f3d0;',
        '  padding: 3px 10px;',
        '  border-radius: 999px;',
        '}',
        '.tp-live-dot {',
        '  width: 7px;',
        '  height: 7px;',
        '  border-radius: 50%;',
        '  background: #10b981;',
        '  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.22);',
        '  animation: tpPulseDot 1.8s infinite;',
        '}',
        '@keyframes tpPulseDot {',
        '  0%, 100% { opacity: 1; transform: scale(1); }',
        '  50% { opacity: 0.45; transform: scale(0.88); }',
        '}',
        '.tp-realtime-toast {',
        '  position: fixed;',
        '  bottom: 22px;',
        '  right: 22px;',
        '  z-index: 99998;',
        '  background: #0f172a;',
        '  color: #ffffff;',
        '  padding: 12px 18px;',
        '  border-radius: 10px;',
        '  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);',
        '  font-size: 13px;',
        '  font-weight: 600;',
        '  display: flex;',
        '  align-items: center;',
        '  gap: 10px;',
        '  border-left: 4px solid #3b82f6;',
        '  transition: opacity 0.25s ease, transform 0.25s ease;',
        '}',
        '.tp-mention-trigger-btn {',
        '  display: inline-flex;',
        '  align-items: center;',
        '  gap: 5px;',
        '  background: #eff6ff;',
        '  color: #2563eb;',
        '  border: 1px solid #bfdbfe;',
        '  border-radius: 6px;',
        '  padding: 3px 9px;',
        '  font-size: 12px;',
        '  font-weight: 700;',
        '  cursor: pointer;',
        '}',
        '.tp-mention-trigger-btn:hover {',
        '  background: #dbeafe;',
        '}',
        'html.tp-theme-dark .tp-mention-popup {',
        '  background: #1e293b;',
        '  border-color: #334155;',
        '}',
        'html.tp-theme-dark .tp-mention-name {',
        '  color: #f8fafc;',
        '}',
        'html.tp-theme-dark .tp-mention-option:hover, html.tp-theme-dark .tp-mention-option.is-active {',
        '  background: #334155;',
        '}'
    ].join('\n');
    document.head.appendChild(styleEl);

    function showRealtimeToast(message) {
        var existing = document.getElementById('tp-realtime-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'tp-realtime-toast';
        toast.className = 'tp-realtime-toast';
        toast.innerHTML = '<i class="fa fa-bolt" style="color:#60a5fa;font-size:15px;"></i><span></span>';
        toast.querySelector('span').textContent = message;
        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(function () { toast.remove(); }, 260);
            }
        }, 3800);
    }

    window.showTaskPilotRealtimeToast = showRealtimeToast;

    // -------------------------------------------------------------------------
    // 1. @Mention Autocomplete Engine for CKEditor 4 & Textareas
    // -------------------------------------------------------------------------
    var popup = document.createElement('div');
    popup.className = 'tp-mention-popup';
    popup.id = 'tp-mention-popup';
    document.body.appendChild(popup);

    var candidatesCache = null;
    var activeCandidates = [];
    var selectedIdx = 0;
    var activeTarget = null; // { type: 'ckeditor' | 'textarea', instance: ..., query: '' }

    function resolveContextIds() {
        var projectId = null;
        var taskId = null;

        var pathMatchTask = window.location.pathname.match(/\/tasks\/(\d+)/);
        if (pathMatchTask) {
            taskId = pathMatchTask[1];
        }
        var pathMatchProj = window.location.pathname.match(/\/projects\/(\d+)/);
        if (pathMatchProj) {
            projectId = pathMatchProj[1];
        }
        var urlParams = new URLSearchParams(window.location.search);
        if (!projectId && urlParams.get('project_id')) {
            projectId = urlParams.get('project_id');
        }
        var commentIdInput = document.querySelector('input[name="commentable_id"]');
        if (!taskId && commentIdInput && commentIdInput.value) {
            taskId = commentIdInput.value;
        }
        return { projectId: projectId, taskId: taskId };
    }

    function fetchCandidates(callback) {
        if (candidatesCache !== null) {
            callback(candidatesCache);
            return;
        }
        var ctx = resolveContextIds();
        var qs = [];
        if (ctx.projectId) qs.push('project_id=' + encodeURIComponent(ctx.projectId));
        if (ctx.taskId) qs.push('task_id=' + encodeURIComponent(ctx.taskId));
        var url = '/mentions/candidates' + (qs.length ? '?' + qs.join('&') : '');

        fetch(url, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.ok ? r.json() : { candidates: [] }; })
            .then(function (data) {
                candidatesCache = data.candidates || [];
                callback(candidatesCache);
            })
            .catch(function () {
                candidatesCache = [];
                callback([]);
            });
    }

    function hideMentionPopup() {
        popup.style.display = 'none';
        activeTarget = null;
    }

    function renderMentionPopup(query, anchorRect) {
        fetchCandidates(function (all) {
            var q = (query || '').toLowerCase().trim();
            activeCandidates = all.filter(function (c) {
                if (!q) return true;
                return (c.name && c.name.toLowerCase().indexOf(q) !== -1) ||
                       (c.username && c.username.toLowerCase().indexOf(q) !== -1) ||
                       (c.job_title && c.job_title.toLowerCase().indexOf(q) !== -1);
            }).slice(0, 8);

            if (!activeCandidates.length) {
                hideMentionPopup();
                return;
            }

            selectedIdx = 0;
            var html = '<div class="tp-mention-popup-header"><i class="fa fa-at me-1"></i> Nhắc tên thành viên (@Mention ưu tiên)</div>';
            activeCandidates.forEach(function (c, idx) {
                html += '<div class="tp-mention-option ' + (idx === 0 ? 'is-active' : '') + '" data-idx="' + idx + '">' +
                    '<div class="tp-mention-avatar">' + (c.initials || 'U') + '</div>' +
                    '<div class="tp-mention-info">' +
                    '  <div class="tp-mention-name">' + c.name + '</div>' +
                    '  <div class="tp-mention-meta">@' + c.username + ' • ' + (c.job_title || 'Thành viên') + '</div>' +
                    '</div>' +
                    '</div>';
            });
            popup.innerHTML = html;
            popup.style.display = 'block';

            var top = (anchorRect ? anchorRect.bottom + 6 : 200);
            var left = (anchorRect ? anchorRect.left + 12 : 200);
            if (top + 260 > window.innerHeight) {
                top = Math.max(12, (anchorRect ? anchorRect.top : 260) - popup.offsetHeight - 8);
            }
            if (left + 300 > window.innerWidth) {
                left = Math.max(12, window.innerWidth - 305);
            }
            popup.style.top = top + 'px';
            popup.style.left = left + 'px';

            popup.querySelectorAll('.tp-mention-option').forEach(function (opt) {
                opt.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var idx = parseInt(this.getAttribute('data-idx'), 10) || 0;
                    selectCandidate(activeCandidates[idx]);
                });
            });
        });
    }

    function updatePopupHighlight() {
        var opts = popup.querySelectorAll('.tp-mention-option');
        opts.forEach(function (el, idx) {
            if (idx === selectedIdx) {
                el.classList.add('is-active');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('is-active');
            }
        });
    }

    function selectCandidate(candidate) {
        if (!candidate || !activeTarget) return;

        if (activeTarget.type === 'ckeditor') {
            var editor = activeTarget.instance;
            var data = editor.getData();
            // Strip trailing @query if user typed it before selecting
            if (activeTarget.query !== null) {
                var pattern = new RegExp('@' + activeTarget.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?=\\s*<\\/p>|\\s*$)', 'i');
                if (pattern.test(data)) {
                    editor.setData(data.replace(pattern, ''), function () {
                        insertCkeditorMentionChip(editor, candidate);
                    });
                    hideMentionPopup();
                    return;
                }
            }
            insertCkeditorMentionChip(editor, candidate);
        } else if (activeTarget.type === 'textarea') {
            var ta = activeTarget.instance;
            var val = ta.value;
            var pos = ta.selectionStart || val.length;
            var before = val.substring(0, pos).replace(/@[a-zA-Z0-9_.\-]*$/, '');
            var after = val.substring(pos);
            var insertText = '@' + candidate.username + ' ';
            ta.value = before + insertText + after;
            ta.focus();
            var newCursor = (before + insertText).length;
            ta.setSelectionRange(newCursor, newCursor);
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }

        hideMentionPopup();
    }

    function insertCkeditorMentionChip(editor, candidate) {
        editor.focus();
        var chipHtml = '<span class="tp-mention" data-user-id="' + candidate.id + '" data-username="' + candidate.username + '" style="background:#eff6ff;color:#2563eb;padding:1px 6px;border-radius:4px;font-weight:700;">@' + candidate.name + '</span>&nbsp;';
        editor.insertHtml(chipHtml);
    }

    function bindCkeditorInstance(editor) {
        if (!editor || editor._tpMentionBound) return;
        editor._tpMentionBound = true;

        function attachDomListeners() {
            var editable = editor.editable();
            if (!editable) return;

            editable.attachListener(editable, 'keyup', function (evt) {
                var keyCode = evt.data.getKey();
                if (keyCode === 38 || keyCode === 40 || keyCode === 13 || keyCode === 27) {
                    return;
                }
                var text = editable.getText() || '';
                var match = text.match(/(?:^|\s)@([a-zA-Z0-9_.\-]{0,30})$/);
                if (match) {
                    var containerEl = editor.container && editor.container.$ ? editor.container.$ : document.body;
                    var rect = containerEl.getBoundingClientRect();
                    activeTarget = { type: 'ckeditor', instance: editor, query: match[1] || '' };
                    renderMentionPopup(match[1] || '', rect);
                } else if (activeTarget && activeTarget.instance === editor) {
                    hideMentionPopup();
                }
            });

            editable.attachListener(editable, 'keydown', function (evt) {
                if (popup.style.display !== 'block' || !activeTarget || activeTarget.instance !== editor) {
                    return;
                }
                var keyCode = evt.data.getKey();
                if (keyCode === 40) { // Down
                    evt.data.preventDefault();
                    selectedIdx = (selectedIdx + 1) % activeCandidates.length;
                    updatePopupHighlight();
                } else if (keyCode === 38) { // Up
                    evt.data.preventDefault();
                    selectedIdx = (selectedIdx - 1 + activeCandidates.length) % activeCandidates.length;
                    updatePopupHighlight();
                } else if (keyCode === 13 || keyCode === 9) { // Enter or Tab
                    evt.data.preventDefault();
                    selectCandidate(activeCandidates[selectedIdx]);
                } else if (keyCode === 27) { // Escape
                    evt.data.preventDefault();
                    hideMentionPopup();
                }
            });
        }

        if (editor.status === 'ready') {
            attachDomListeners();
        } else {
            editor.on('contentDom', attachDomListeners);
        }
    }

    function initAllMentions() {
        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.on('instanceReady', function (ev) {
                bindCkeditorInstance(ev.editor);
            });
            Object.keys(CKEDITOR.instances || {}).forEach(function (name) {
                bindCkeditorInstance(CKEDITOR.instances[name]);
            });
        }

        // Add "@ Nhắc tên (@Mention)" helper button under comment & description editors
        var commentForm = document.getElementById('task-comment-form');
        if (commentForm && !document.getElementById('tp-comment-mention-btn')) {
            var helperSpan = commentForm.querySelector('.text-muted.small');
            if (helperSpan) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'tp-comment-mention-btn';
                btn.className = 'tp-mention-trigger-btn ms-2';
                btn.innerHTML = '<i class="fa fa-at"></i> Nhắc tên (@Mention)';
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var ed = typeof CKEDITOR !== 'undefined' ? CKEDITOR.instances['comment-editor'] : null;
                    var rect = btn.getBoundingClientRect();
                    if (ed) {
                        activeTarget = { type: 'ckeditor', instance: ed, query: null };
                        renderMentionPopup('', rect);
                    }
                });
                helperSpan.parentNode.insertBefore(btn, helperSpan.nextSibling);
            }
        }
    }

    document.addEventListener('click', function (e) {
        if (!popup.contains(e.target) && !e.target.closest('.tp-mention-trigger-btn')) {
            hideMentionPopup();
        }
    });

    // -------------------------------------------------------------------------
    // 2. Real-time Task Comments Synchronization (WebSocket + Live Polling)
    // -------------------------------------------------------------------------
    function initRealtimeTaskComments() {
        var match = window.location.pathname.match(/^\/tasks\/(\d+)$/);
        if (!match) return;

        var taskId = match[1];
        var commentForm = document.getElementById('task-comment-form');
        if (!commentForm) return;

        var card = commentForm.closest('.edit-card');
        var headerTitle = card ? card.querySelector('.edit-card-title') : null;
        if (headerTitle && !document.getElementById('tp-comments-live-badge')) {
            var badge = document.createElement('span');
            badge.id = 'tp-comments-live-badge';
            badge.className = 'tp-live-badge ms-2';
            badge.innerHTML = '<span class="tp-live-dot"></span> Đồng bộ thời gian thực';
            headerTitle.parentNode.appendChild(badge);
        }

        var lastFingerprint = null;

        function renderCommentList(comments, count) {
            if (!card) return;
            var body = card.querySelector('.edit-card-body');
            if (!body) return;

            if (headerTitle) {
                headerTitle.innerHTML = '<i class="fa fa-comments-o text-primary"></i> Bình luận (' + count + ')';
            }

            // Remove old comment items or empty placeholder after the form
            Array.from(body.children).forEach(function (child) {
                if (child.id !== 'task-comment-form') {
                    child.remove();
                }
            });

            if (!comments || comments.length === 0) {
                var emptyDiv = document.createElement('div');
                emptyDiv.className = 'text-center py-4 text-muted small';
                emptyDiv.innerHTML = '<i class="fa fa-comments-o mb-2 d-block" style="font-size: 26px; opacity: 0.35;"></i>Chưa có bình luận nào. Hãy mở đầu cuộc trao đổi!';
                body.appendChild(emptyDiv);
                return;
            }

            var feedWrap = document.createElement('div');
            feedWrap.className = 'tp-comments-feed';
            comments.forEach(function (c) {
                var item = document.createElement('div');
                item.className = 'tp-comment-item';
                item.id = 'comment-' + c.id;
                item.innerHTML =
                    '<div class="user-avatar-circle flex-shrink-0">' + (c.user_initials || 'U') + '</div>' +
                    '<div class="flex-grow-1 min-w-0 comment-content-wrap">' +
                    '  <div class="comment-header">' +
                    '    <span class="user-name">' + (c.user_name || 'Thành viên') + '</span>' +
                    '    <span class="comment-time">' + (c.created_at_human || 'Vừa xong') + '</span>' +
                    '  </div>' +
                    '  <div class="comment-body ck-content-body mt-1" style="font-size: 13.5px; color: #334155; line-height: 1.6;">' +
                    (c.body || '') +
                    '  </div>' +
                    '</div>';
                feedWrap.appendChild(item);
            });
            body.appendChild(feedWrap);
        }

        function syncTaskComments() {
            fetch('/realtime/tasks/' + taskId + '/comments-state', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.fingerprint) return;
                    if (lastFingerprint === null) {
                        lastFingerprint = data.fingerprint;
                        return;
                    }
                    if (data.fingerprint !== lastFingerprint) {
                        lastFingerprint = data.fingerprint;
                        renderCommentList(data.comments || [], data.comments_count || 0);
                        showRealtimeToast('Bình luận hoặc trạng thái công việc vừa được cập nhật trực tiếp!');
                    }
                })
                .catch(function () {});
        }

        // Subscribe via Laravel Echo (Reverb/Pusher) if active
        if (window.Echo && typeof window.Echo.channel === 'function') {
            try {
                window.Echo.channel('task.' + taskId + '.comments')
                    .listen('.task.comment.created', function () {
                        syncTaskComments();
                    })
                    .listen('.task.comment.updated', function () {
                        syncTaskComments();
                    });
            } catch (err) {}
        }

        syncTaskComments();
        setInterval(syncTaskComments, 4500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAllMentions();
            initRealtimeTaskComments();
            setTimeout(initAllMentions, 700);
        });
    } else {
        initAllMentions();
        initRealtimeTaskComments();
        setTimeout(initAllMentions, 700);
    }
})();

