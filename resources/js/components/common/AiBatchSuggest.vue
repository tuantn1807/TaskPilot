<template>
    <span class="ai-suggest-wrap">
        <button
            type="button"
            class="ai-suggest-btn"
            :disabled="aiLoading || applying || resetting || !taskIds.length"
            :title="buttonTitle"
            :aria-label="buttonTitle"
            :aria-busy="aiLoading || applying"
            @click="runBatch"
        >
            <i v-if="aiLoading || applying" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-magic ai-magic-icon" aria-hidden="true"></i>
            <span>{{ buttonLabel }}</span>
        </button>
        <button
            type="button"
            class="ai-suggest-btn ai-reset-btn"
            :disabled="aiLoading || applying || resetting"
            title="Gỡ phân công các việc đã đánh dấu test AI để gợi ý lại"
            aria-label="Gỡ phân công các việc test AI"
            :aria-busy="resetting"
            @click="resetPool"
        >
            <i v-if="resetting" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-undo"></i>
            <span>Reset test AI</span>
        </button>

        <Teleport to="body">
            <div v-if="showModal" class="ai-modal-overlay" @click.self="showModal = false">
                <div class="ai-modal-card ai-modal-wide">
                    <div class="ai-modal-header">
                        <div class="ai-modal-title">
                            <i class="fa fa-magic ai-title-icon" aria-hidden="true"></i>
                            <span>AI gợi ý tất cả công việc chưa phân công</span>
                        </div>
                        <button type="button" aria-label="Đóng gợi ý AI" class="ai-modal-close" @click="showModal = false">&times;</button>
                    </div>
                    <div class="ai-modal-body">
                        <p class="ai-modal-subtitle">
                            AI đã chọn người phù hợp nhất cho từng việc. Bỏ chọn dòng không muốn gán, rồi bấm Áp dụng tất cả.
                        </p>

                        <div v-if="!rows.length" class="ai-empty-state">
                            <i class="fa fa-info-circle"></i> Không có gợi ý nào để áp dụng.
                        </div>

                        <div v-else class="ai-batch-list">
                            <div v-for="row in rows" :key="row.task_id" class="ai-batch-row-card">
                                <div class="ai-batch-row">
                                    <label class="ai-batch-check">
                                        <input :aria-label="`Chọn công việc ${row.task_name}`" type="checkbox" v-model="row.include" :disabled="!row.suggested">
                                    </label>
                                    <div class="ai-batch-task">
                                        <div class="ai-home-item-title">{{ row.task_name }}</div>
                                        <div class="ai-home-item-meta">{{ row.issue_type || 'task' }} · {{ row.priority || 'medium' }}</div>
                                    </div>
                                    <div v-if="row.recommendations.length" class="ai-batch-pick">
                                        <select :aria-label="`Người xử lý cho ${row.task_name}`" v-model="row.selectedUserId" class="edit-select">
                                            <option
                                                v-for="candidate in row.recommendations"
                                                :key="candidate.user_id"
                                                :value="String(candidate.user_id)"
                                            >
                                                {{ candidateDisplayName(candidate) }} · {{ candidateConfidence(candidate) }}%
                                            </option>
                                        </select>
                                    </div>
                                    <div v-else class="ai-home-item-meta">Chưa có ứng viên</div>
                                </div>

                                <div
                                    v-if="selectedCandidateForRow(row) && (selectedCandidateForRow(row).match_reasons || []).length"
                                    class="ai-batch-xai-panel"
                                >
                                    <div class="ai-batch-xai-title">
                                        <i class="fa fa-info-circle" style="color: #7c3aed;"></i>
                                        <span>Chi tiết thông tin đánh giá:</span>
                                    </div>
                                    <div class="ai-batch-xai-reasons">
                                        <div
                                            v-for="(reason, rIdx) in selectedCandidateForRow(row).match_reasons"
                                            :key="rIdx"
                                            style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px;"
                                        >
                                            <span class="ai-batch-reason-tag">
                                                <i :class="getReasonMeta(reason).icon" :style="{ color: getReasonMeta(reason).color }"></i>
                                                <span>{{ reason }}</span>
                                            </span>
                                            <button
                                                v-if="getReasonCategory(reason)"
                                                type="button"
                                                class="ai-reason-detail-pill"
                                                style="padding: 2px 10px; font-size: 0.85rem;"
                                                :title="'Xem chi tiết ' + getReasonCategoryName(reason)"
                                                @click.stop="openDetailModal(selectedCandidateForRow(row), getReasonCategory(reason))"
                                            >
                                                <span>Chi tiết</span>
                                                <i class="fa fa-chevron-right" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ai-modal-footer">
                        <button type="button" class="tp-btn tp-btn-light" @click="showModal = false">Hủy</button>
                        <button
                            type="button"
                            class="ai-select-btn"
                            :disabled="applying || !selectedCount"
                            @click="applyAll"
                        >
                            <i v-if="applying" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-check"></i>
                            Áp dụng tất cả ({{ selectedCount }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submodal chi tiết cho Batch -->
            <div v-if="showDetailModal && detailCandidate" class="ai-submodal-overlay" @click.self="showDetailModal = false">
                <div class="ai-submodal-card">
                    <div class="ai-submodal-header">
                        <div class="ai-submodal-title">
                            <div class="ai-candidate-avatar ai-submodal-avatar">{{ candidateInitial(detailCandidate) }}</div>
                            <div>
                                <div class="ai-submodal-name">
                                    {{ candidateDisplayName(detailCandidate) }}
                                    <span class="ai-submodal-badge">{{ candidateConfidence(detailCandidate) }}% Phù hợp</span>
                                </div>
                                <div class="ai-submodal-subtitle">
                                    <span>{{ detailCandidate.job_title || 'Thành viên dự án' }}</span>
                                    <span v-if="detailCandidate.department" class="ai-dot">•</span>
                                    <span v-if="detailCandidate.department">Phòng ban: {{ detailCandidate.department }}</span>
                                    <span class="ai-dot">•</span>
                                    <span>{{ detailCandidate.active_tasks_count ?? 0 }} task đang làm</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" aria-label="Đóng chi tiết" class="ai-modal-close" @click="showDetailModal = false">&times;</button>
                    </div>

                    <div class="ai-submodal-tabs">
                        <button
                            type="button"
                            class="ai-submodal-tab"
                            :class="{ active: activeDetailTab === 'skills' }"
                            @click="activeDetailTab = 'skills'"
                        >
                            <i class="fa fa-bullseye"></i>
                            <span>Kỹ năng cụ thể ({{ (detailCandidate.skills || []).length }})</span>
                        </button>
                        <button
                            type="button"
                            class="ai-submodal-tab"
                            :class="{ active: activeDetailTab === 'expertise' }"
                            @click="activeDetailTab = 'expertise'"
                        >
                            <i class="fa fa-briefcase"></i>
                            <span>Chuyên môn & Khối lượng việc</span>
                        </button>
                        <button
                            type="button"
                            class="ai-submodal-tab"
                            :class="{ active: activeDetailTab === 'history' }"
                            @click="activeDetailTab = 'history'"
                        >
                            <i class="fa fa-history"></i>
                            <span>Lịch sử dự án ({{ (detailCandidate.history_tasks || []).length }})</span>
                        </button>
                    </div>

                    <div class="ai-submodal-body">
                        <!-- Tab 1: Kỹ năng cụ thể -->
                        <div v-if="activeDetailTab === 'skills'" class="ai-tab-panel">
                            <div v-if="!detailCandidate.skills || !detailCandidate.skills.length" class="ai-tab-empty">
                                <i class="fa fa-info-circle"></i> Chưa có kỹ năng nào được đăng ký cho ứng viên này.
                            </div>
                            <div v-else>
                                <p class="ai-tab-desc">
                                    Hồ sơ toàn bộ kỹ năng chuyên môn của <strong>{{ candidateDisplayName(detailCandidate) }}</strong>:
                                </p>
                                <div class="ai-table-wrap">
                                    <table class="ai-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Kỹ năng</th>
                                                <th>Mức độ thành thạo</th>
                                                <th>Kinh nghiệm</th>
                                                <th>Độ tương thích</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="sk in detailCandidate.skills"
                                                :key="sk.id"
                                                :class="{
                                                    'ai-tr-matched': sk.is_matched,
                                                    'ai-tr-hint': sk.is_hint && !sk.is_matched,
                                                }"
                                            >
                                                <td class="ai-td-skill">
                                                    <i
                                                        :class="sk.is_matched ? 'fa fa-check-circle text-purple' : (sk.is_hint ? 'fa fa-dot-circle text-blue' : 'fa fa-circle text-muted')"
                                                    ></i>
                                                    <span class="font-bold">{{ sk.name }}</span>
                                                </td>
                                                <td>
                                                    <div class="ai-proficiency-wrap">
                                                        <div class="ai-stars">
                                                            <i v-for="s in 5" :key="s" class="fa fa-star" :class="{ filled: s <= sk.level }"></i>
                                                        </div>
                                                        <span class="ai-prof-label">{{ sk.level_label }} ({{ sk.proficiency_percent }}%)</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span v-if="sk.years">{{ sk.years }} năm</span>
                                                    <span v-else class="text-muted">—</span>
                                                </td>
                                                <td>
                                                    <span v-if="sk.is_matched" class="ai-pill-badge ai-pill-direct">
                                                        <i class="fa fa-bullseye"></i> Khớp trực tiếp
                                                    </span>
                                                    <span v-else-if="sk.is_hint" class="ai-pill-badge ai-pill-hint">
                                                        <i class="fa fa-lightbulb"></i> Lĩnh vực liên quan
                                                    </span>
                                                    <span v-else class="ai-pill-badge ai-pill-secondary">
                                                        Kỹ năng phụ trợ
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Chuyên môn & Tải việc -->
                        <div v-if="activeDetailTab === 'expertise'" class="ai-tab-panel">
                            <div class="ai-expertise-grid">
                                <div class="ai-info-card">
                                    <div class="ai-info-card-header">
                                        <i class="fa fa-id-badge text-purple"></i>
                                        <span>Hồ sơ Chuyên môn</span>
                                    </div>
                                    <div class="ai-info-card-body">
                                        <div class="ai-info-row">
                                            <span class="ai-info-label">Chức danh / Vị trí:</span>
                                            <span class="ai-info-val font-bold">{{ detailCandidate.job_title || 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="ai-info-row">
                                            <span class="ai-info-label">Phòng ban:</span>
                                            <span class="ai-info-val">{{ detailCandidate.department || 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="ai-info-row">
                                            <span class="ai-info-label">Trạng thái:</span>
                                            <span class="ai-pill-badge ai-pill-success">Đang hoạt động</span>
                                        </div>
                                        <div v-if="detailCandidate.details && (detailCandidate.details.job_hits || []).length" class="ai-info-row">
                                            <span class="ai-info-label">Từ khóa chuyên môn phù hợp:</span>
                                            <div class="ai-tag-group">
                                                <span v-for="tag in detailCandidate.details.job_hits" :key="tag" class="ai-tag">{{ tag }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-info-card">
                                    <div class="ai-info-card-header">
                                        <i class="fa fa-tachometer text-emerald"></i>
                                        <span>Khối lượng Công Việc Hiện Tại</span>
                                    </div>
                                    <div class="ai-info-card-body">
                                        <div class="ai-info-row">
                                            <span class="ai-info-label">Số task đang xử lý:</span>
                                            <span
                                                class="ai-info-val font-bold"
                                                :class="(detailCandidate.active_tasks_count ?? 0) >= 5 ? 'text-amber' : 'text-emerald'"
                                            >
                                                {{ detailCandidate.active_tasks_count ?? 0 }} task
                                            </span>
                                        </div>
                                        <div class="ai-info-row">
                                            <span class="ai-info-label">Đánh giá khả dụng:</span>
                                            <span v-if="(detailCandidate.active_tasks_count ?? 0) === 0" class="ai-pill-badge ai-pill-success">
                                                Sẵn sàng nhận việc ngay
                                            </span>
                                            <span v-else-if="(detailCandidate.active_tasks_count ?? 0) <= 2" class="ai-pill-badge ai-pill-info">
                                                Khối lượng công việc nhẹ ({{ detailCandidate.active_tasks_count }} task)
                                            </span>
                                            <span v-else class="ai-pill-badge ai-pill-warning">
                                                {{ detailCandidate.active_tasks_count }} task đang làm
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Lịch sử dự án -->
                        <div v-if="activeDetailTab === 'history'" class="ai-tab-panel">
                            <div v-if="!detailCandidate.history_tasks || !detailCandidate.history_tasks.length" class="ai-tab-empty">
                                <i class="fa fa-info-circle"></i> Chưa có lịch sử công việc nào trong dự án này.
                            </div>
                            <div v-else>
                                <p class="ai-tab-desc">
                                    Các công việc <strong>{{ candidateDisplayName(detailCandidate) }}</strong> đã từng hoàn thành trong dự án này:
                                </p>
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
                                            <tr v-for="ht in detailCandidate.history_tasks" :key="ht.id">
                                                <td>
                                                    <a :href="'/tasks/' + ht.id" target="_blank" class="ai-task-link">
                                                        #{{ ht.id }} <i class="fa fa-external-link-alt" style="font-size: 10px;"></i>
                                                    </a>
                                                </td>
                                                <td class="font-medium text-slate-800">{{ ht.name }}</td>
                                                <td><span class="ai-pill-badge ai-pill-muted">{{ ht.issue_type }}</span></td>
                                                <td>
                                                    <span class="ai-pill-badge" :class="ht.priority === 'high' ? 'ai-pill-danger' : 'ai-pill-info'">
                                                        {{ ht.priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="ai-pill-badge" :class="ht.status === 'done' || ht.status === 'completed' ? 'ai-pill-success' : 'ai-pill-warning'">
                                                        {{ ht.status }}
                                                    </span>
                                                </td>
                                                <td class="text-slate-500 text-sm">{{ ht.created_at || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ai-submodal-footer">
                        <button type="button" class="btn btn-secondary ai-submodal-btn" @click="showDetailModal = false">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </span>
</template>

<script setup>
import { computed, ref } from 'vue';
import {
    applyAiBatchAssignments,
    assigneeFromCandidate,
    candidateConfidence,
    candidateDisplayName,
    candidateInitial,
    fetchAiBatchRecommendations,
    resetAiTestAssignments,
} from '../../composables/useAiAssignee';

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    projectId: { type: [Number, String], default: null },
});

const emit = defineEmits(['applied', 'reset']);

const aiLoading = ref(false);
const applying = ref(false);
const resetting = ref(false);
const showModal = ref(false);
const rows = ref([]);
const showDetailModal = ref(false);
const detailCandidate = ref(null);
const activeDetailTab = ref('skills');

const getReasonCategory = (reason) => {
    if (!reason) return null;
    if (reason.includes('Kỹ năng')) return 'skills';
    if (reason.includes('Chuyên môn') || reason.includes('Lĩnh vực')) return 'expertise';
    if (reason.includes('Lịch sử') || reason.includes('Đã xử lý')) return 'history';
    if (reason.includes('Khối lượng công việc') || reason.includes('trống việc') || reason.includes('nhận việc')) return 'expertise';
    return null;
};

const getReasonCategoryName = (reason) => {
    const cat = getReasonCategory(reason);
    if (cat === 'skills') return 'kỹ năng';
    if (cat === 'expertise') return 'chuyên môn';
    if (cat === 'history') return 'lịch sử công việc';
    return 'thông tin';
};

const openDetailModal = (candidate, tab = 'skills') => {
    if (!candidate) return;
    detailCandidate.value = candidate;
    activeDetailTab.value = tab;
    showDetailModal.value = true;
};

const unassignedTasks = computed(() => {
    return (props.tasks || []).filter((task) => {
        if (['done', 'completed', 'resolved', 'closed'].includes(task.status)) {
            return false;
        }
        return !task.assignee_id && !task.assignee;
    });
});

const taskIds = computed(() => unassignedTasks.value.map((task) => task.id));

const selectedCount = computed(() => rows.value.filter((row) => row.include && row.selectedUserId).length);

const getReasonMeta = (reason) => {
    if (reason.includes('Kỹ năng')) return { icon: 'fa fa-bullseye', color: '#8b5cf6' };
    if (reason.includes('Chuyên môn') || reason.includes('Lĩnh vực')) return { icon: 'fa fa-briefcase', color: '#2563eb' };
    if (reason.includes('Lịch sử')) return { icon: 'fa fa-history', color: '#f59e0b' };
    if (reason.includes('Khối lượng công việc') || reason.includes('Sẵn sàng') || reason.includes('trống việc')) return { icon: 'fa fa-tachometer', color: '#10b981' };
    if (reason.includes('ngữ nghĩa')) return { icon: 'fa fa-brain', color: '#ec4899' };
    return { icon: 'fa fa-check-circle', color: '#10b981' };
};

const selectedCandidateForRow = (row) => {
    if (!row || !Array.isArray(row.recommendations)) {
        return row?.suggested || null;
    }
    return row.recommendations.find((item) => String(item.user_id) === String(row.selectedUserId)) || row.suggested || null;
};

const buttonLabel = computed(() => {
    if (aiLoading.value) {
        return 'Đang gợi ý...';
    }
    if (applying.value) {
        return 'Đang gán...';
    }
    const count = taskIds.value.length;
    return count ? `AI gợi ý tất cả (${count})` : 'AI gợi ý tất cả';
});

const buttonTitle = computed(() => {
    if (!taskIds.value.length) {
        return 'Không còn công việc chưa phân công';
    }
    return 'Dùng AI đề xuất người xử lý cho toàn bộ công việc chưa phân công';
});

const runBatch = async () => {
    if (!taskIds.value.length) {
        alert('Không còn công việc chưa phân công để gợi ý.');
        return;
    }

    aiLoading.value = true;
    try {
        const items = await fetchAiBatchRecommendations(taskIds.value);
        rows.value = items.map((item) => ({
            ...item,
            include: Boolean(item.suggested),
            selectedUserId: item.suggested ? String(item.suggested.user_id) : '',
        }));
        showModal.value = true;
    } catch (err) {
        console.error('AI batch error:', err);
        alert(err.message || 'Không thể gợi ý hàng loạt lúc này.');
    } finally {
        aiLoading.value = false;
    }
};

const applyAll = async () => {
    const selected = rows.value.filter((row) => row.include && row.selectedUserId);
    if (!selected.length) {
        return;
    }

    applying.value = true;
    try {
        const assignments = selected.map((row) => {
            const candidate = (row.recommendations || []).find(
                (item) => String(item.user_id) === String(row.selectedUserId)
            ) || row.suggested;
            return {
                task_id: row.task_id,
                user_id: Number(row.selectedUserId),
                score: candidate?.score ?? 0,
                match_reasons: candidate?.match_reasons || [],
                candidate,
            };
        });

        await applyAiBatchAssignments(assignments.map(({ candidate, ...payload }) => payload));

        emit('applied', assignments.map((row) => ({
            taskId: row.task_id,
            candidate: row.candidate || assigneeFromCandidate({
                user_id: row.user_id,
                name: 'Thành viên',
            }),
        })));

        showModal.value = false;
    } catch (err) {
        console.error('AI batch assign error:', err);
        alert(err.message || 'Không thể lưu phân công hàng loạt.');
    } finally {
        applying.value = false;
    }
};

const resetPool = async () => {
    if (!confirm('Gỡ người xử lý trên các việc test AI để gợi ý lại từ đầu? Việc đã đóng/gán từ dataset sẽ giữ nguyên.')) {
        return;
    }

    resetting.value = true;
    try {
        const result = await resetAiTestAssignments({
            projectId: props.projectId || null,
        });
        emit('reset', result.tasks || []);
        alert(`Đã reset ${result.reset_count || 0} công việc test AI. Có thể bấm AI gợi ý tất cả lại.`);
    } catch (err) {
        console.error('AI test reset error:', err);
        alert(err.message || 'Không thể reset dữ liệu test AI.');
    } finally {
        resetting.value = false;
    }
};
</script>
