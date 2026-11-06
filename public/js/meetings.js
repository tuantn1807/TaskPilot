(() => {
    const roomRoot = document.getElementById('meeting-room');
    if (!roomRoot) return;

    const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const json = (url, options = {}) => fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            ...(options.headers || {})
        }
    }).then(async response => {
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.message || 'Yêu cầu không thành công.');
        }
        return response.json();
    });

    const videoGrid = document.getElementById('video-grid');
    const chatMessages = document.getElementById('chat-messages');
    const participants = JSON.parse(meta('meeting-members') || '[]');
    const platform = meta('meeting-platform') || 'taskpilot';

    let room;
    let lastMessage = 0;
    let activeRecipient = null;
    let projectTasks = [];

    const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));

    const renderMessage = (message) => {
        if (!chatMessages) return;
        const item = document.createElement('article');
        item.className = 'tp-chat-message';
        item.dataset.messageId = message.id;
        const isMine = Number(message.user_id) === Number(meta('current-user-id'));
        const sourceTask = message.source_task
            ? `<small class="text-success" style="display:block;margin-top:4px;">Task: ${escapeHtml(message.source_task.name)}</small>`
            : `<button class="tp-message-action create-task" style="border:none;background:none;color:#2563eb;font-size:12px;cursor:pointer;padding:0;margin-top:4px;">+ Tạo Task</button>`;
        const controls = !message.deleted_at && isMine
            ? `<button class="tp-message-action edit-message" style="border:none;background:none;color:#64748b;font-size:12px;cursor:pointer;margin-left:8px;">Sửa</button><button class="tp-message-action revoke-message" style="border:none;background:none;color:#ef4444;font-size:12px;cursor:pointer;margin-left:8px;">Thu hồi</button>`
            : '';
        const body = message.deleted_at
            ? '<em class="tp-message-revoked" style="color:#94a3b8;font-style:italic;">Tin nhắn đã được thu hồi</em>'
            : escapeHtml(message.body || '');
        const edited = message.edited_at && !message.deleted_at ? ' <small style="color:#94a3b8;">(đã sửa)</small>' : '';
        const attachments = (message.attachments || []).map(file => `
            <a class="tp-message-file" href="${meta('meeting-file-prefix')}/${encodeURIComponent(file.path)}" target="_blank" style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:4px;background:#f1f5f9;margin-top:4px;font-size:12px;text-decoration:none;color:#334155;">
                <i class="fa fa-file-o"></i><span>${escapeHtml(file.name)}</span>
            </a>
        `).join('');

        item.innerHTML = `
            <div class="tp-message-head" style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:2px;">
                <strong style="color:#0f172a;">${escapeHtml(message.user?.name || 'Ẩn danh')}</strong>
                <time style="color:#94a3b8;">${new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}</time>${edited}
            </div>
            <p style="margin:2px 0 4px 0;font-size:13.5px;color:#334155;word-break:break-word;">${body}</p>
            <div class="tp-message-files">${attachments}</div>
            <div style="margin-top:2px;">${sourceTask}${controls}</div>
        `;
        item.querySelector('.create-task')?.addEventListener('click', () => createTask(message));
        item.querySelector('.edit-message')?.addEventListener('click', () => editMessage(message));
        item.querySelector('.revoke-message')?.addEventListener('click', () => revokeMessage(message));
        chatMessages.appendChild(item);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    const loadMessages = () => {
        const url = meta('meeting-messages-url');
        if (!url) return;
        json(`${url}?after=0${activeRecipient ? `&recipient_id=${activeRecipient}` : ''}`)
            .then(data => {
                if (!chatMessages) return;
                chatMessages.replaceChildren();
                (data.messages || []).forEach(message => {
                    lastMessage = Math.max(lastMessage, message.id);
                    renderMessage(message);
                });
            })
            .catch(() => {});
    };

    const editMessage = (message) => {
        const body = window.prompt('Chỉnh sửa tin nhắn:', message.body || '');
        if (body === null || !body.trim()) return;
        json(`${meta('meeting-message-url')}/${message.id}`, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({body: body.trim()})
        }).then(loadMessages).catch(error => window.alert(error.message));
    };

    const revokeMessage = (message) => {
        if (!window.confirm('Bạn có chắc muốn thu hồi tin nhắn này?')) return;
        json(`${meta('meeting-message-url')}/${message.id}`, {method: 'DELETE'})
            .then(loadMessages)
            .catch(error => window.alert(error.message));
    };

    const createTask = (message) => {
        const name = window.prompt('Tên Task cần tạo từ tin nhắn này:', message.body?.slice(0, 120) || 'Action item');
        if (!name) return;
        json(`${meta('meeting-message-url')}/${message.id}/task`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({name})
        }).then(loadMessages).catch(error => window.alert(error.message));
    };

    const publishSignal = (type, value) => {
        if (!room?.localParticipant) return;
        const payload = new TextEncoder().encode(JSON.stringify({
            type,
            value,
            name: room.localParticipant.name || room.localParticipant.identity
        }));
        room.localParticipant.publishData(payload, {reliable: true, topic: 'meeting-ui'}).catch(() => {});
    };

    const showReaction = (reaction, name) => {
        const reactionsWrap = document.getElementById('floating-reactions');
        if (!reactionsWrap) return;
        const bubble = document.createElement('span');
        bubble.className = 'tp-floating-reaction';
        bubble.textContent = `${reaction} ${name || ''}`;
        reactionsWrap.appendChild(bubble);
        window.setTimeout(() => bubble.remove(), 2200);
    };

    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const bodyInput = document.getElementById('chat-body');
            const fileInput = document.getElementById('chat-file');
            const body = bodyInput ? bodyInput.value.trim() : '';
            const form = new FormData(chatForm);
            if (!body && (!fileInput || !fileInput.files.length)) return;

            const mentionIds = participants.filter(member =>
                new RegExp(`@${member.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'i').test(body)
            ).map(member => member.id);

            mentionIds.forEach(id => form.append('mention_ids[]', id));
            if (activeRecipient) form.append('recipient_id', activeRecipient);

            json(meta('meeting-message-url'), {method: 'POST', body: form})
                .then(() => {
                    chatForm.reset();
                    const preview = document.getElementById('file-preview');
                    if (preview) preview.innerHTML = '';
                    loadMessages();
                })
                .catch(error => window.alert(error.message));
        });
    }

    const attachBtn = document.getElementById('attach-file');
    const fileInp = document.getElementById('chat-file');
    if (attachBtn && fileInp) {
        attachBtn.addEventListener('click', () => fileInp.click());
        fileInp.addEventListener('change', event => {
            const preview = document.getElementById('file-preview');
            if (!preview) return;
            preview.innerHTML = Array.from(event.target.files)
                .map(file => `<span style="display:inline-block;padding:2px 6px;margin:2px;background:#e2e8f0;border-radius:4px;font-size:12px;"><i class="fa fa-file-o"></i> ${escapeHtml(file.name)}</span>`)
                .join('');
        });
    }

    const emojiToggle = document.getElementById('emoji-toggle');
    const emojiPicker = document.getElementById('emoji-picker');
    if (emojiToggle && emojiPicker) {
        emojiToggle.addEventListener('click', () => {
            emojiPicker.hidden = !emojiPicker.hidden;
        });
        emojiPicker.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            const input = document.getElementById('chat-body');
            if (!input) return;
            const start = input.selectionStart || 0;
            input.value = `${input.value.slice(0, start)}${button.textContent}${input.value.slice(input.selectionEnd || 0)}`;
            input.focus();
            input.selectionStart = input.selectionEnd = start + button.textContent.length;
        }));
    }

    // Task picker & mention
    const taskPicker = document.getElementById('task-picker');
    const showTasks = () => {
        if (!taskPicker) return;
        if (!projectTasks.length) {
            json(meta('meeting-tasks-url')).then(data => {
                projectTasks = data.tasks || [];
                renderTasks('');
            });
        } else {
            renderTasks('');
        }
        taskPicker.hidden = false;
    };

    const renderTasks = (query) => {
        const results = document.getElementById('task-results');
        if (!results) return;
        const filtered = projectTasks.filter(task => (task.name || '').toLowerCase().includes(query.toLowerCase()));
        results.innerHTML = filtered.map(task => `
            <button type="button" data-task-id="${task.id}" class="btn btn-sm btn-link text-left" style="display:block;width:100%;text-align:left;padding:6px;border-bottom:1px solid #f1f5f9;">
                <strong>#${task.id} ${escapeHtml(task.name)}</strong>
                <small class="text-muted" style="display:block;">${escapeHtml(task.status || '')}</small>
            </button>
        `).join('') || '<p class="p-2 text-muted small">Không tìm thấy task</p>';

        results.querySelectorAll('[data-task-id]').forEach(button => button.addEventListener('click', () => {
            const input = document.getElementById('chat-body');
            if (input) {
                input.value = `${input.value.replace(/@task\S*$/, '')}@task #${button.dataset.taskId} ${button.querySelector('strong').textContent} `;
                input.focus();
            }
            if (taskPicker) taskPicker.hidden = true;
        }));
    };

    const chatBodyInput = document.getElementById('chat-body');
    if (chatBodyInput) {
        chatBodyInput.addEventListener('input', event => {
            if (/@task\S*$/i.test(event.target.value)) showTasks();
            else if (taskPicker && !taskPicker.hidden) taskPicker.hidden = true;
        });
    }

    const taskSearch = document.getElementById('task-search');
    if (taskSearch) {
        taskSearch.addEventListener('input', event => renderTasks(event.target.value));
    }

    // Private Note
    const saveNoteBtn = document.getElementById('save-note');
    if (saveNoteBtn) {
        saveNoteBtn.addEventListener('click', () => {
            const noteText = document.getElementById('private-note')?.value || '';
            json(meta('meeting-note-url'), {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({body: noteText})
            }).then(() => window.alert('Đã lưu ghi chú riêng.')).catch(error => window.alert(error.message));
        });
    }

    // Sidebar tabs
    document.querySelectorAll('.tp-sidebar-tabs button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tp-sidebar-tabs button').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.tp-side-panel').forEach(panel => panel.classList.add('d-none'));
            button.classList.add('active');
            const targetPanel = document.getElementById(button.dataset.panel);
            if (targetPanel) targetPanel.classList.remove('d-none');
            const reactionPicker = document.getElementById('reaction-picker');
            if (reactionPicker) reactionPicker.hidden = true;
            if (button.dataset.panel === 'chat-panel') {
                activeRecipient = null;
                const input = document.getElementById('chat-body');
                if (input) input.placeholder = 'Viết tin nhắn, @mention...';
                loadMessages();
            }
        });
    });

    document.addEventListener('click', event => {
        const reactionToggle = document.getElementById('reaction-toggle');
        const reactionPicker = document.getElementById('reaction-picker');
        if (reactionPicker && !event.target.closest('#reaction-toggle, #reaction-picker')) {
            reactionPicker.hidden = true;
        }
        if (taskPicker && !event.target.closest('#task-picker, #chat-body')) {
            taskPicker.hidden = true;
        }
    });

    // Participants list
    const updateParticipants = () => {
        const list = document.getElementById('participants-list');
        if (!list) return;
        const connected = room ? [room.localParticipant, ...room.remoteParticipants.values()] : [];
        const connectedIds = new Set(connected.map(p => String(p.identity)));
        const offline = participants.filter(member => !connectedIds.has(String(member.id))).map(member => ({
            identity: String(member.id),
            name: member.name,
            isOffline: true
        }));
        const all = [...connected, ...offline];
        const countBadge = document.getElementById('participant-count');
        if (countBadge) countBadge.textContent = all.length;

        list.innerHTML = all.map(p => `
            <button type="button" class="tp-action-item ${p.isOffline ? 'is-offline' : ''}" data-participant="${escapeHtml(p.identity)}" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 12px;border:none;background:transparent;text-align:left;cursor:pointer;">
                <span class="tp-person-avatar" style="width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e0e7ff;color:#3730a3;font-weight:700;font-size:12px;">${escapeHtml((p.name || '?').slice(0, 1).toUpperCase())}</span>
                <strong style="font-size:13px;flex:1;color:#1e293b;">${escapeHtml(p.name || p.identity)}</strong>
                <small style="color:#64748b;font-size:11px;">
                    <span class="participant-hand"></span> ${p.isOffline ? 'Chưa vào phòng' : (p.isSpeaking ? 'Đang nói' : 'Chat riêng')}
                </small>
            </button>
        `).join('');

        list.querySelectorAll('[data-participant]').forEach(item => item.addEventListener('click', () => {
            activeRecipient = Number(item.dataset.participant) || null;
            const chatPanel = document.getElementById('chat-panel');
            const peoplePanel = document.getElementById('people-panel');
            if (chatPanel) chatPanel.classList.remove('d-none');
            if (peoplePanel) peoplePanel.classList.add('d-none');
            document.querySelectorAll('.tp-sidebar-tabs button').forEach(tab =>
                tab.classList.toggle('active', tab.dataset.panel === 'chat-panel')
            );
            const chatBody = document.getElementById('chat-body');
            if (chatBody) chatBody.placeholder = `Chat riêng với ${item.querySelector('strong')?.textContent || 'thành viên'}`;
            loadMessages();
        }));
    };

    // LiveKit WebRTC handling (only for TaskPilot built-in meetings)
    const attachTrack = (track, participant) => {
        if (!videoGrid) return;
        const element = track.attach();
        element.dataset.identity = participant.identity;
        videoGrid.querySelector('.tp-video-placeholder')?.remove();
        videoGrid.appendChild(element);
    };

    const connectLiveKit = () => {
        const tokenUrl = meta('meeting-token-url');
        const stateElem = document.getElementById('connection-state');
        if (!tokenUrl) return;

        json(tokenUrl).then(async data => {
            if (typeof LivekitClient === 'undefined') {
                if (stateElem) stateElem.textContent = 'Thiếu thư viện LiveKit';
                return;
            }
            room = new LivekitClient.Room({adaptiveStream: true, dynacast: true});
            room.on(LivekitClient.RoomEvent.TrackSubscribed, (track, publication, participant) => attachTrack(track, participant));
            room.on(LivekitClient.RoomEvent.TrackUnsubscribed, track => track.detach().forEach(element => element.remove()));
            room.on(LivekitClient.RoomEvent.ParticipantConnected, updateParticipants);
            room.on(LivekitClient.RoomEvent.ParticipantDisconnected, updateParticipants);
            room.on(LivekitClient.RoomEvent.DataReceived, (payload, participant) => {
                try {
                    const signal = JSON.parse(new TextDecoder().decode(payload));
                    if (signal.type === 'reaction') showReaction(signal.value, participant?.name || participant?.identity);
                    if (signal.type === 'hand') updateHand(participant?.identity, signal.value);
                } catch (error) {}
            });
            await room.connect(data.url, data.token);
            await room.localParticipant.enableCameraAndMicrophone();
            room.localParticipant.trackPublications.forEach(pub => pub.track && attachTrack(pub.track, room.localParticipant));
            if (stateElem) stateElem.textContent = 'Đã kết nối';
            updateParticipants();
        }).catch(error => {
            if (stateElem) stateElem.textContent = `Không thể kết nối LiveKit: ${error.message}`;
        });
    };

    const updateHand = (identity, raised) => {
        const item = document.querySelector(`[data-participant="${CSS.escape(identity || '')}"]`);
        if (item) {
            const handSpan = item.querySelector('.participant-hand');
            if (handSpan) handSpan.textContent = raised ? '✋' : '';
        }
    };

    // Audio/Video control buttons
    const toggleMic = document.getElementById('toggle-mic');
    if (toggleMic) {
        toggleMic.addEventListener('click', event => {
            if (!room?.localParticipant) return;
            const enabled = room.localParticipant.isMicrophoneEnabled;
            room.localParticipant.setMicrophoneEnabled(!enabled);
            event.currentTarget.classList.toggle('is-off', enabled);
        });
    }

    const toggleCam = document.getElementById('toggle-camera');
    if (toggleCam) {
        toggleCam.addEventListener('click', event => {
            if (!room?.localParticipant) return;
            const enabled = room.localParticipant.isCameraEnabled;
            room.localParticipant.setCameraEnabled(!enabled);
            event.currentTarget.classList.toggle('is-off', enabled);
        });
    }

    const shareScreen = document.getElementById('share-screen');
    if (shareScreen) {
        shareScreen.addEventListener('click', () => room?.localParticipant?.setScreenShareEnabled(true));
    }

    const raiseHand = document.getElementById('raise-hand');
    if (raiseHand) {
        raiseHand.addEventListener('click', event => {
            const raised = event.currentTarget.classList.toggle('is-active');
            const span = event.currentTarget.querySelector('span');
            if (span) span.textContent = raised ? 'Hạ tay' : 'Giơ tay';
            publishSignal('hand', raised);
        });
    }

    const reactionToggle = document.getElementById('reaction-toggle');
    const reactionPicker = document.getElementById('reaction-picker');
    if (reactionToggle && reactionPicker) {
        reactionToggle.addEventListener('click', () => {
            reactionPicker.hidden = !reactionPicker.hidden;
        });
        reactionPicker.querySelectorAll('[data-reaction]').forEach(button => button.addEventListener('click', () => {
            showReaction(button.dataset.reaction, 'Bạn');
            publishSignal('reaction', button.dataset.reaction);
            reactionPicker.hidden = true;
        }));
    }

    const leaveBtn = document.getElementById('leave-room');
    if (leaveBtn) {
        leaveBtn.addEventListener('click', () => {
            room?.disconnect();
            const archiveUrl = meta('meeting-archive-url');
            if (archiveUrl) window.location.href = archiveUrl;
        });
    }

    // Copy to clipboard helper
    document.querySelectorAll('[data-copy-target]').forEach(button => {
        button.addEventListener('click', () => {
            const textToCopy = button.getAttribute('data-copy-target');
            if (!textToCopy) return;
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fa fa-check"></i> Đã sao chép!';
                setTimeout(() => { button.innerHTML = originalText; }, 2000);
            }).catch(() => {
                window.prompt('Sao chép liên kết:', textToCopy);
            });
        });
    });

    // Initialize meeting room
    updateParticipants();
    loadMessages();

    const stateElem = document.getElementById('connection-state');
    if (platform === 'taskpilot') {
        connectLiveKit();
    } else {
        if (stateElem) {
            const platformLabels = {
                google_meet: 'Google Meet',
                zoom: 'Zoom Meeting',
                teams: 'Microsoft Teams',
                offline: 'Họp trực tiếp (Offline)',
                other: 'Cuộc họp ngoài'
            };
            stateElem.textContent = platformLabels[platform] || 'Cuộc họp đa nền tảng';
        }
    }

    window.setInterval(loadMessages, 3000);
})();

