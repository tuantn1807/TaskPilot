import '../css/ai-assignee.css';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function displayName(candidate) {
    return candidate?.name || candidate?.display_name || candidate?.assignee || 'Thành viên';
}

function confidence(candidate) {
    if (candidate?.confidence_percent != null && candidate.confidence_percent !== '') {
        return candidate.confidence_percent;
    }
    const score = Number(candidate?.score);
    return Number.isFinite(score) ? Math.round(score * 100) : 0;
}

function ensureSubModal() {
    let sub = document.getElementById('tp-ai-detail-submodal');
    if (sub) {
        return sub;
    }

    sub = document.createElement('div');
    sub.id = 'tp-ai-detail-submodal';
    sub.className = 'ai-submodal-overlay';
    sub.style.display = 'none';
    document.body.appendChild(sub);

    sub.addEventListener('click', (event) => {
        if (event.target === sub || event.target.closest('[data-submodal-close]')) {
            sub.style.display = 'none';
        }
    });

    return sub;
}

function openSubModal(candidate, activeTab, onSelect) {
    const sub = ensureSubModal();

    function renderContent(tab) {
        let tabContent = '';
        if (tab === 'skills') {
            const skills = candidate.skills || [];
            if (!skills.length) {
                tabContent = '<div class="ai-tab-empty"><i class="fa fa-info-circle"></i> Chưa có kỹ năng nào được đăng ký cho ứng viên này.</div>';
            } else {
                tabContent = `
                    <p class="ai-tab-desc">Hồ sơ toàn bộ kỹ năng chuyên môn của <strong>${escapeHtml(displayName(candidate))}</strong> và độ tương thích với task này:</p>
                    <div class="ai-table-wrap">
                        <table class="ai-detail-table">
                            <thead>
                                <tr>
                                    <th>Kỹ năng</th>
                                    <th>Mức độ thành thạo</th>
                                    <th>Kinh nghiệm</th>
                                    <th>Độ tương thích với Task</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${skills.map((sk) => {
                                    const trCls = sk.is_matched ? 'ai-tr-matched' : (sk.is_hint ? 'ai-tr-hint' : '');
                                    const icon = sk.is_matched ? 'fa fa-check-circle text-purple' : (sk.is_hint ? 'fa fa-dot-circle text-blue' : 'fa fa-circle text-muted');
                                    let badge = '<span class="ai-pill-badge ai-pill-secondary">Kỹ năng phụ trợ</span>';
                                    if (sk.is_matched) {
                                        badge = '<span class="ai-pill-badge ai-pill-direct"><i class="fa fa-bullseye"></i> Khớp trực tiếp</span>';
                                    } else if (sk.is_hint) {
                                        badge = '<span class="ai-pill-badge ai-pill-hint"><i class="fa fa-lightbulb"></i> Lĩnh vực liên quan</span>';
                                    }
                                    const stars = Array.from({ length: 5 }, (_, i) => `<i class="fa fa-star${i < (sk.level || 3) ? ' filled' : ''}"></i>`).join('');
                                    return `
                                        <tr class="${trCls}">
                                            <td class="ai-td-skill">
                                                <i class="${icon}"></i>
                                                <span class="font-bold">${escapeHtml(sk.name)}</span>
                                            </td>
                                            <td>
                                                <div class="ai-proficiency-wrap">
                                                    <div class="ai-stars">${stars}</div>
                                                    <span class="ai-prof-label">${escapeHtml(sk.level_label || '')} (${sk.proficiency_percent}%)</span>
                                                </div>
                                            </td>
                                            <td>${sk.years ? `${sk.years} năm` : '<span class="text-muted">—</span>'}</td>
                                            <td>${badge}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
        } else if (tab === 'expertise') {
            const hits = candidate.details?.job_hits || [];
            const activeTasks = candidate.active_tasks || [];
            tabContent = `
                <div class="ai-expertise-grid">
                    <div class="ai-info-card">
                        <div class="ai-info-card-header"><i class="fa fa-id-badge text-purple"></i><span>Hồ sơ Chuyên môn</span></div>
                        <div class="ai-info-card-body">
                            <div class="ai-info-row"><span class="ai-info-label">Chức danh / Vị trí:</span><span class="ai-info-val font-bold">${escapeHtml(candidate.job_title || 'Chưa cập nhật')}</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Phòng ban:</span><span class="ai-info-val">${escapeHtml(candidate.department || 'Chưa cập nhật')}</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Trạng thái:</span><span class="ai-pill-badge ai-pill-success">Đang hoạt động</span></div>
                            ${hits.length ? `
                            <div class="ai-info-row">
                                <span class="ai-info-label">Từ khóa chuyên môn phù hợp:</span>
                                <div class="ai-tag-group">${hits.map((h) => `<span class="ai-tag">${escapeHtml(h)}</span>`).join('')}</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="ai-info-card">
                        <div class="ai-info-card-header"><i class="fa fa-tachometer text-emerald"></i><span>Tải Công Việc Hiện Tại</span></div>
                        <div class="ai-info-card-body">
                            <div class="ai-info-row"><span class="ai-info-label">Số task đang xử lý:</span><span class="ai-info-val font-bold">${candidate.active_tasks_count ?? 0} task</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Đánh giá khả dụng:</span><span class="ai-pill-badge ai-pill-info">${candidate.active_tasks_count === 0 ? 'Sẵn sàng nhận việc ngay' : `${candidate.active_tasks_count} task đang xử lý`}</span></div>
                            ${activeTasks.length ? `
                            <div class="ai-active-tasks-box">
                                <div class="ai-box-title">Danh sách task đang phụ trách:</div>
                                <ul class="ai-mini-task-list">
                                    ${activeTasks.map((at) => `
                                        <li>
                                            <a href="/tasks/${at.id}" target="_blank" class="ai-mini-task-link">#${at.id} - ${escapeHtml(at.name)}</a>
                                            <span class="ai-mini-badge">${escapeHtml(at.status)}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        } else if (tab === 'history') {
            const hist = candidate.history_tasks || [];
            const overlaps = candidate.details?.overlap_keywords || [];
            if (!hist.length) {
                tabContent = '<div class="ai-tab-empty"><i class="fa fa-info-circle"></i> Chưa có lịch sử công việc nào trong dự án này.</div>';
            } else {
                tabContent = `
                    <p class="ai-tab-desc">Các công việc <strong>${escapeHtml(displayName(candidate))}</strong> đã từng hoàn thành trong dự án này:</p>
                    ${overlaps.length ? `
                    <div class="ai-overlap-bar">
                        <span class="font-bold">Từ khóa tương đồng trong lịch sử:</span>
                        <div class="ai-tag-group">${overlaps.map((o) => `<span class="ai-tag ai-tag-accent">${escapeHtml(o)}</span>`).join('')}</div>
                    </div>
                    ` : ''}
                    <div class="ai-table-wrap">
                        <table class="ai-detail-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Mã</th>
                                    <th>Tên công việc</th>
                                    <th style="width: 110px;">Loại</th>
                                    <th style="width: 100px;">Ưu tiên</th>
                                    <th style="width: 110px;">Trạng thái</th>
                                    <th style="width: 110px;">Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${hist.map((ht) => `
                                    <tr>
                                        <td><a href="/tasks/${ht.id}" target="_blank" class="ai-task-link">#${ht.id} <i class="fa fa-external-link-alt" style="font-size: 10px;"></i></a></td>
                                        <td class="font-medium">${escapeHtml(ht.name)}</td>
                                        <td><span class="ai-pill-badge ai-pill-muted">${escapeHtml(ht.issue_type)}</span></td>
                                        <td><span class="ai-pill-badge ${ht.priority === 'high' ? 'ai-pill-danger' : 'ai-pill-info'}">${escapeHtml(ht.priority)}</span></td>
                                        <td><span class="ai-pill-badge ${ht.status === 'done' || ht.status === 'completed' ? 'ai-pill-success' : 'ai-pill-warning'}">${escapeHtml(ht.status)}</span></td>
                                        <td class="text-muted">${escapeHtml(ht.created_at || '—')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
        }

        sub.innerHTML = `
            <div class="ai-submodal-card">
                <div class="ai-submodal-header">
                    <div class="ai-submodal-title">
                        <div class="ai-candidate-avatar ai-submodal-avatar">${escapeHtml(displayName(candidate).charAt(0).toUpperCase())}</div>
                        <div>
                            <div class="ai-submodal-name">
                                ${escapeHtml(displayName(candidate))}
                                <span class="ai-submodal-badge">${confidence(candidate)}% Phù hợp</span>
                            </div>
                            <div class="ai-submodal-subtitle">
                                <span>${escapeHtml(candidate.job_title || 'Thành viên dự án')}</span>
                                ${candidate.department ? `<span class="ai-dot">•</span><span>Phòng ban: ${escapeHtml(candidate.department)}</span>` : ''}
                                <span class="ai-dot">•</span>
                                <span>${candidate.active_tasks_count ?? 0} task đang làm</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="ai-modal-close" data-submodal-close>&times;</button>
                </div>
                <div class="ai-submodal-tabs">
                    <button type="button" class="ai-submodal-tab${tab === 'skills' ? ' active' : ''}" data-tab-switch="skills">
                        <i class="fa fa-bullseye"></i> <span>Kỹ năng (${(candidate.skills || []).length})</span>
                    </button>
                    <button type="button" class="ai-submodal-tab${tab === 'expertise' ? ' active' : ''}" data-tab-switch="expertise">
                        <i class="fa fa-briefcase"></i> <span>Chuyên môn & Tải việc</span>
                    </button>
                    <button type="button" class="ai-submodal-tab${tab === 'history' ? ' active' : ''}" data-tab-switch="history">
                        <i class="fa fa-history"></i> <span>Lịch sử dự án (${(candidate.history_tasks || []).length})</span>
                    </button>
                </div>
                <div class="ai-submodal-body">
                    ${tabContent}
                </div>
                <div class="ai-submodal-footer">
                    <button type="button" class="btn btn-secondary ai-submodal-btn" data-submodal-close>Đóng</button>
                    <button type="button" class="btn btn-primary ai-submodal-btn" data-submodal-pick="${candidate.user_id}">
                        <i class="fa fa-check" style="margin-right: 6px;"></i> Chọn người này
                    </button>
                </div>
            </div>
        `;

        sub.querySelectorAll('[data-tab-switch]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const newTab = btn.getAttribute('data-tab-switch');
                renderContent(newTab);
            });
        });

        sub.querySelectorAll('[data-submodal-pick]').forEach((btn) => {
            btn.addEventListener('click', () => {
                sub.style.display = 'none';
                if (onSelect) onSelect(candidate);
            });
        });
    }

    renderContent(activeTab);
    sub.style.display = 'flex';
}

function ensureModal() {
    let overlay = document.getElementById('tp-ai-assignee-modal');
    if (overlay) {
        return overlay;
    }

    overlay = document.createElement('div');
    overlay.id = 'tp-ai-assignee-modal';
    overlay.className = 'ai-modal-overlay';
    overlay.style.display = 'none';
    overlay.innerHTML = `
        <div class="ai-modal-card">
            <div class="ai-modal-header">
                <div class="ai-modal-title">
                    <i class="fa fa-magic" style="color: #8b5cf6; margin-right: 6px;"></i>
                    <span>AI Đề Xuất Người Xử Lý Phù Hợp</span>
                </div>
                <button type="button" class="ai-modal-close" data-ai-close>&times;</button>
            </div>
            <div class="ai-modal-body">
                <p class="ai-modal-subtitle">
                    Mô hình Học Xếp Hạng (LightGBM / LambdaMART) kết hợp phân tích chuyên môn, lịch sử dự án và tải công việc hiện tại.
                </p>
                <div data-ai-body></div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('[data-ai-close]')) {
            overlay.style.display = 'none';
        }
    });

    return overlay;
}

function renderCandidates(list, selectedUserId) {
    if (!list.length) {
        return '<div class="ai-empty-state"><i class="fa fa-info-circle"></i> Chưa tìm thấy ứng viên phù hợp.</div>';
    }

    return `<div class="ai-candidate-list">${list.map((candidate) => {
        const selected = String(selectedUserId || '') === String(candidate.user_id) ? ' selected' : '';
        const reasons = Array.isArray(candidate.match_reasons)
            ? candidate.match_reasons.map((reason) => {
                let icon = 'fa fa-check-circle';
                let color = '#10b981';
                let category = '';
                if (reason.includes('Kỹ năng')) {
                    icon = 'fa fa-bullseye';
                    color = '#8b5cf6';
                    category = 'skills';
                } else if (reason.includes('Chuyên môn') || reason.includes('Lĩnh vực')) {
                    icon = 'fa fa-briefcase';
                    color = '#2563eb';
                    category = 'expertise';
                } else if (reason.includes('Lịch sử') || reason.includes('Đã xử lý')) {
                    icon = 'fa fa-history';
                    color = '#f59e0b';
                    category = 'history';
                } else if (reason.includes('Tải công việc') || reason.includes('Sẵn sàng') || reason.includes('trống việc')) {
                    icon = 'fa fa-tachometer';
                    color = '#10b981';
                    category = 'expertise';
                } else if (reason.includes('ngữ nghĩa')) {
                    icon = 'fa fa-brain';
                    color = '#ec4899';
                }

                return `
                <div class="ai-reason-item">
                    <div class="ai-reason-main">
                        <i class="${icon}" style="color: ${color};"></i>
                        <span class="ai-reason-text">${escapeHtml(reason)}</span>
                    </div>
                    ${category ? `
                    <button type="button" class="ai-reason-detail-pill" data-detail-user="${candidate.user_id}" data-detail-tab="${category}" title="Xem chi tiết ${escapeHtml(category)}">
                        <span>Xem chi tiết</span>
                        <i class="fa fa-chevron-right" style="font-size: 11px;"></i>
                    </button>
                    ` : ''}
                </div>
            `;}).join('')
            : '';

        return `
            <div class="ai-candidate-card${selected}">
                <div class="ai-candidate-header">
                    <div class="ai-candidate-info">
                        <div class="ai-candidate-avatar">${escapeHtml(displayName(candidate).charAt(0).toUpperCase())}</div>
                        <div>
                            <div class="ai-candidate-name">${escapeHtml(displayName(candidate))}</div>
                            <div class="ai-candidate-meta">
                                <span>${escapeHtml(candidate.job_title || 'Thành viên dự án')}</span>
                                <span class="ai-dot">•</span>
                                <span>${candidate.active_tasks_count ?? 0} task đang làm</span>
                            </div>
                        </div>
                    </div>
                    <div class="ai-candidate-score-badge">${confidence(candidate)}% Phù hợp</div>
                </div>
                ${reasons ? `
                    <div class="ai-candidate-reasons-block">
                        <div class="ai-reasons-header">
                            <div class="ai-reasons-title">
                                <i class="fa fa-info-circle" style="color: #7c3aed;"></i>
                                <span>Chi tiết thông tin đánh giá:</span>
                            </div>
                        </div>
                        <div class="ai-candidate-reasons">${reasons}</div>
                    </div>
                ` : ''}
                <div class="ai-candidate-action">
                    <button type="button" class="ai-select-btn" data-ai-pick="${candidate.user_id}">
                        <i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này
                    </button>
                </div>
            </div>
        `;
    }).join('')}</div>`;
}

async function requestRecommendations(context) {
    const response = await fetch('/tasks/recommend-assignee', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            name: context.name || '',
            summary: context.name || '',
            description: context.description || '',
            project_id: context.project_id || null,
            issue_type: context.issue_type || 'task',
            priority: context.priority || 'medium',
            task_id: context.task_id || null,
        }),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success || !Array.isArray(data.recommendations)) {
        throw new Error(
            data.message === 'This action is unauthorized.'
                ? 'Bạn không có quyền dùng AI gợi ý cho công việc này.'
                : (data.message || 'Không thể tải gợi ý AI vào lúc này.')
        );
    }

    return data.recommendations;
}

async function saveAssignee(taskId, userId) {
    const response = await fetch(`/tasks/${taskId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ _method: 'PUT', assignee_id: userId }),
    });

    return response.json();
}

async function sendFeedback(recommendationId) {
    if (!recommendationId) {
        return;
    }

    try {
        await fetch('/tasks/ai-feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                recommendation_id: recommendationId,
                status: 'accepted',
            }),
        });
    } catch (err) {
        console.warn('AI feedback error:', err);
    }
}

function contextFromButton(button) {
    const formRoot = button.closest('form');
    const read = (id) => document.getElementById(id)?.value || '';

    return {
        name: button.dataset.name || read('name') || read('input-task-name'),
        description: button.dataset.description || read('description') || '',
        project_id: button.dataset.projectId || read('project_id') || null,
        issue_type: button.dataset.issueType || read('issue_type') || 'task',
        priority: button.dataset.priority || read('priority') || 'medium',
        task_id: button.dataset.taskId || null,
        mode: button.dataset.aiMode || 'select',
        select: button.dataset.aiSelect || '#assignee_id',
        form: formRoot,
    };
}

async function openFromButton(button) {
    const context = contextFromButton(button);
    if (!context.name && !context.description) {
        alert('Hãy nhập tên hoặc mô tả công việc trước khi dùng AI đề xuất.');
        return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> AI';

    try {
        const recommendations = await requestRecommendations(context);
        const overlay = ensureModal();
        const body = overlay.querySelector('[data-ai-body]');
        const selectedId = context.form?.querySelector(context.select)?.value || '';
        body.innerHTML = renderCandidates(recommendations, selectedId);
        overlay.style.display = 'flex';

        body.querySelectorAll('[data-detail-user]').forEach((detailBtn) => {
            detailBtn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                const userId = detailBtn.getAttribute('data-detail-user');
                const tab = detailBtn.getAttribute('data-detail-tab') || 'skills';
                const candidate = recommendations.find((item) => String(item.user_id) === String(userId));
                if (candidate) {
                    openSubModal(candidate, tab, (pickedCandidate) => {
                        const pickBtn = body.querySelector(`[data-ai-pick="${pickedCandidate.user_id}"]`);
                        if (pickBtn) {
                            pickBtn.click();
                        }
                    });
                }
            });
        });

        body.querySelectorAll('[data-ai-pick]').forEach((pickBtn) => {
            pickBtn.addEventListener('click', async () => {
                const userId = pickBtn.getAttribute('data-ai-pick');
                const candidate = recommendations.find((item) => String(item.user_id) === String(userId));
                if (context.mode === 'save' && context.task_id) {
                    pickBtn.disabled = true;
                    pickBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
                    try {
                        const result = await saveAssignee(context.task_id, userId);
                        if (!result.success) {
                            alert('Không thể lưu người thực hiện.');
                            pickBtn.disabled = false;
                            pickBtn.innerHTML = '<i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này';
                            return;
                        }
                        await sendFeedback(candidate?.recommendation_id);
                        window.location.reload();
                        return;
                    } catch (err) {
                        console.error(err);
                        alert('Không thể lưu người thực hiện.');
                        pickBtn.disabled = false;
                        pickBtn.innerHTML = '<i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này';
                        return;
                    }
                }

                const select = document.querySelector(context.select);
                if (select) {
                    if (![...select.options].some((opt) => String(opt.value) === String(userId))) {
                        const option = document.createElement('option');
                        option.value = userId;
                        option.textContent = `👤 ${displayName(candidate)}`;
                        select.appendChild(option);
                    }
                    select.value = userId;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                overlay.style.display = 'none';
            });
        });
    } catch (err) {
        console.error(err);
        alert(err.message || 'Lỗi kết nối khi gọi dịch vụ AI.');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ai-assignee]');
    if (!button) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    openFromButton(button);
});
