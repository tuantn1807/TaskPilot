<template>
    <span class="ai-suggest-wrap" @click.stop @mousedown.stop @pointerdown.stop>
        <button
            type="button"
            class="ai-suggest-btn"
            :class="{ 'ai-suggest-btn-compact': compact, 'ai-suggest-btn-icon': iconOnly }"
            :disabled="aiLoading || disabled"
            :title="title"
            :aria-label="title"
            :aria-busy="aiLoading"
            @click="fetchRecommendations"
        >
            <i v-if="aiLoading" class="fa fa-spinner fa-spin"></i>
            <i v-else class="fa fa-magic ai-magic-icon" aria-hidden="true"></i>
            <span v-if="!iconOnly">{{ compact ? 'AI' : label }}</span>
        </button>

        <Teleport to="body">
            <div v-if="showAiModal" class="ai-modal-overlay" @click.self="showAiModal = false">
                <div class="ai-modal-card">
                    <div class="ai-modal-header">
                        <div class="ai-modal-title">
                            <i class="fa fa-magic ai-title-icon" aria-hidden="true"></i>
                            <span>AI Đề Xuất Người Xử Lý Phù Hợp</span>
                        </div>
                        <button type="button" aria-label="Đóng gợi ý AI" class="ai-modal-close" @click="showAiModal = false">&times;</button>
                    </div>
                    <div class="ai-modal-body">
                        <p class="ai-modal-subtitle">
                            Mô hình Học Xếp Hạng (LightGBM / LambdaMART) kết hợp phân tích chuyên môn, lịch sử dự án và tải công việc hiện tại.
                        </p>

                        <div v-if="aiRecommendations.length === 0" class="ai-empty-state">
                            <i class="fa fa-info-circle"></i> Chưa tìm thấy ứng viên phù hợp.
                        </div>

                        <div v-else class="ai-candidate-list">
                            <div
                                v-for="candidate in aiRecommendations"
                                :key="candidate.user_id"
                                class="ai-candidate-card"
                                :class="{ selected: String(selectedUserId) === String(candidate.user_id) }"
                            >
                                <div class="ai-candidate-header">
                                    <div class="ai-candidate-info">
                                        <div class="ai-candidate-avatar">{{ candidateInitial(candidate) }}</div>
                                        <div>
                                            <div class="ai-candidate-name">{{ candidateDisplayName(candidate) }}</div>
                                            <div class="ai-candidate-meta">
                                                <span>{{ candidate.job_title || 'Thành viên dự án' }}</span>
                                                <span class="ai-dot">•</span>
                                                <span>{{ candidate.active_tasks_count ?? 0 }} task đang làm</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ai-candidate-score-badge">
                                        {{ candidateConfidence(candidate) }}% Phù hợp
                                    </div>
                                </div>

                                <p v-if="candidate.persistence_warning" role="status">{{ candidate.persistence_warning }}</p>

                                <div v-if="candidate.match_reasons && candidate.match_reasons.length" class="ai-candidate-reasons-block">
                                    <div class="ai-reasons-header" @click="toggleDetails(candidate.user_id)">
                                        <div class="ai-reasons-title">
                                            <i class="fa fa-info-circle" style="color: #7c3aed;" aria-hidden="true"></i>
                                            <span>Chi tiết thông tin đánh giá ({{ candidate.match_reasons.length }} tiêu chí)</span>
                                        </div>
                                        <button type="button" class="ai-reasons-toggle-btn" @click.stop="toggleDetails(candidate.user_id)">
                                            <span>{{ isDetailsOpen(candidate.user_id) ? 'Thu gọn' : 'Xem chi tiết' }}</span>
                                            <i :class="isDetailsOpen(candidate.user_id) ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                                        </button>
                                    </div>

                                    <div v-show="isDetailsOpen(candidate.user_id)" class="ai-candidate-reasons">
                                        <div v-for="(reason, idx) in candidate.match_reasons" :key="idx" class="ai-reason-item">
                                            <div class="ai-reason-main">
                                                <i :class="getReasonMeta(reason).icon" :style="{ color: getReasonMeta(reason).color }"></i>
                                                <span class="ai-reason-text">{{ reason }}</span>
                                            </div>
                                            <button
                                                v-if="getReasonCategory(reason)"
                                                type="button"
                                                class="ai-reason-detail-pill"
                                                :title="'Xem chi tiết ' + getReasonCategoryName(reason)"
                                                @click.stop="openDetailModal(candidate, getReasonCategory(reason))"
                                            >
                                                <span>Xem chi tiết</span>
                                                <i class="fa fa-chevron-right" style="font-size: 11px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-candidate-action">
                                    <button type="button" class="ai-select-btn" @click="applyCandidate(candidate)">
                                        <i class="fa fa-check" style="margin-right: 4px;"></i>
                                        Chọn người này
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal xem chi tiết thông tin Kỹ năng / Chuyên môn / Lịch sử -->
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

                    <!-- Navigation tabs -->
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

                    <!-- Tab content -->
                    <div class="ai-submodal-body">
                        <!-- Tab 1: Kỹ năng cụ thể -->
                        <div v-if="activeDetailTab === 'skills'" class="ai-tab-panel">
                            <div v-if="!detailCandidate.skills || !detailCandidate.skills.length" class="ai-tab-empty">
                                <i class="fa fa-info-circle"></i> Chưa có kỹ năng nào được đăng ký cho ứng viên này.
                            </div>
                            <div v-else>
                                <p class="ai-tab-desc">
                                    Hồ sơ toàn bộ kỹ năng chuyên môn của <strong>{{ candidateDisplayName(detailCandidate) }}</strong> và độ tương thích với task này:
                                </p>
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
                                                Sẵn sàng nhận việc ngay (0 task)
                                            </span>
                                            <span v-else-if="(detailCandidate.active_tasks_count ?? 0) <= 2" class="ai-pill-badge ai-pill-info">
                                                Khối lượng công việc nhẹ ({{ detailCandidate.active_tasks_count }} task)
                                            </span>
                                            <span v-else-if="(detailCandidate.active_tasks_count ?? 0) < 5" class="ai-pill-badge ai-pill-warning">
                                                Khối lượng công việc vừa phải ({{ detailCandidate.active_tasks_count }} task)
                                            </span>
                                            <span v-else class="ai-pill-badge ai-pill-danger">
                                                Đang quá tải ({{ detailCandidate.active_tasks_count }} task)
                                            </span>
                                        </div>

                                        <div v-if="detailCandidate.active_tasks && detailCandidate.active_tasks.length" class="ai-active-tasks-box">
                                            <div class="ai-box-title">Danh sách task đang phụ trách:</div>
                                            <ul class="ai-mini-task-list">
                                                <li v-for="at in detailCandidate.active_tasks" :key="at.id">
                                                    <a :href="'/tasks/' + at.id" target="_blank" class="ai-mini-task-link">
                                                        #{{ at.id }} - {{ at.name }}
                                                    </a>
                                                    <span class="ai-mini-badge">{{ at.status }}</span>
                                                </li>
                                            </ul>
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
                                    Các công việc <strong>{{ candidateDisplayName(detailCandidate) }}</strong> đã từng hoàn thành trong dự án này (dùng để đo độ tương đồng và kinh nghiệm thực tế):
                                </p>
                                <div v-if="detailCandidate.details && (detailCandidate.details.overlap_keywords || []).length" class="ai-overlap-bar">
                                    <span class="font-bold">Từ khóa tương đồng phát hiện trong lịch sử:</span>
                                    <div class="ai-tag-group">
                                        <span v-for="kw in detailCandidate.details.overlap_keywords" :key="kw" class="ai-tag ai-tag-accent">
                                            {{ kw }}
                                        </span>
                                    </div>
                                </div>

                                <div class="ai-table-wrap">
                                    <table class="ai-detail-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">Mã</th>
                                                <th>Tên công việc</th>
                                                <th style="width: 110px;">Loại task</th>
                                                <th style="width: 100px;">Mức ưu tiên</th>
                                                <th style="width: 110px;">Trạng thái</th>
                                                <th style="width: 110px;">Ngày tạo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="ht in detailCandidate.history_tasks" :key="ht.id">
                                                <td>
                                                    <a :href="'/tasks/' + ht.id" target="_blank" class="ai-task-link" title="Mở xem chi tiết task">
                                                        #{{ ht.id }} <i class="fa fa-external-link-alt" style="font-size: 10px;"></i>
                                                    </a>
                                                </td>
                                                <td class="font-medium text-slate-800">{{ ht.name }}</td>
                                                <td>
                                                    <span class="ai-pill-badge ai-pill-muted">{{ ht.issue_type }}</span>
                                                </td>
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

                    <!-- Footer action -->
                    <div class="ai-submodal-footer">
                        <button type="button" class="btn btn-secondary ai-submodal-btn" @click="showDetailModal = false">
                            Đóng
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary ai-submodal-btn"
                            @click="applyCandidate(detailCandidate); showDetailModal = false;"
                        >
                            <i class="fa fa-check" style="margin-right: 6px;"></i> Chọn người này
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
    candidateConfidence,
    candidateDisplayName,
    candidateInitial,
    fetchAiRecommendations,
    saveTaskAssignee,
    sendAiFeedback,
} from '../../composables/useAiAssignee';

const props = defineProps({
    task: { type: Object, default: null },
    name: { type: String, default: '' },
    description: { type: String, default: '' },
    projectId: { type: [Number, String], default: null },
    issueType: { type: String, default: 'task' },
    priority: { type: String, default: 'medium' },
    taskId: { type: [Number, String], default: null },
    selectedUserId: { type: [Number, String], default: null },
    recommendUrl: { type: String, default: '/tasks/recommend-assignee' },
    feedbackUrl: { type: String, default: '/tasks/ai-feedback' },
    compact: { type: Boolean, default: false },
    iconOnly: { type: Boolean, default: false },
    autoSave: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '✨ AI Đề xuất' },
    title: { type: String, default: 'Dùng AI đề xuất người xử lý phù hợp' },
});

const emit = defineEmits(['select', 'applied']);

const aiLoading = ref(false);
const showAiModal = ref(false);
const aiRecommendations = ref([]);
const collapsedCandidates = ref({});
const showDetailModal = ref(false);
const detailCandidate = ref(null);
const activeDetailTab = ref('skills');

const toggleDetails = (userId) => {
    collapsedCandidates.value[userId] = !collapsedCandidates.value[userId];
};

const isDetailsOpen = (userId) => {
    return !collapsedCandidates.value[userId];
};

const getReasonCategory = (reason) => {
    if (!reason) return null;
    if (reason.includes('Kỹ năng')) return 'skills';
    if (reason.includes('Chuyên môn') || reason.includes('Lĩnh vực')) return 'expertise';
    if (reason.includes('Lịch sử') || reason.includes('Đã xử lý')) return 'history';
    if (reason.includes('Khối lượng công việc') || reason.includes('trống việc') || reason.includes('nhận việc') || reason.includes('Sẵn sàng')) return 'expertise';
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
    detailCandidate.value = candidate;
    activeDetailTab.value = tab;
    showDetailModal.value = true;
};

const getReasonMeta = (reason) => {
    if (reason.includes('Kỹ năng')) return { icon: 'fa fa-bullseye', color: '#8b5cf6' };
    if (reason.includes('Chuyên môn') || reason.includes('Lĩnh vực')) return { icon: 'fa fa-briefcase', color: '#2563eb' };
    if (reason.includes('Lịch sử')) return { icon: 'fa fa-history', color: '#f59e0b' };
    if (reason.includes('Khối lượng công việc') || reason.includes('Sẵn sàng') || reason.includes('trống việc')) return { icon: 'fa fa-tachometer', color: '#10b981' };
    if (reason.includes('ngữ nghĩa')) return { icon: 'fa fa-brain', color: '#ec4899' };
    return { icon: 'fa fa-check-circle', color: '#10b981' };
};

const context = computed(() => ({
    name: props.task?.name ?? props.name,
    description: props.task?.description ?? props.description,
    project_id: props.task?.project_id ?? props.projectId,
    issue_type: props.task?.issue_type ?? props.issueType,
    priority: props.task?.priority ?? props.priority,
    task_id: props.task?.id ?? props.taskId,
}));

const fetchRecommendations = async () => {
    if (!context.value.name && !context.value.description) {
        alert('Hãy nhập tên hoặc mô tả công việc trước khi dùng AI đề xuất.');
        return;
    }

    aiLoading.value = true;
    try {
        aiRecommendations.value = await fetchAiRecommendations(context.value, props.recommendUrl);
        showAiModal.value = true;
    } catch (err) {
        console.error('AI Recommend Error:', err);
        alert(err.message || 'Lỗi kết nối khi gọi dịch vụ AI.');
    } finally {
        aiLoading.value = false;
    }
};

const applyCandidate = async (candidate) => {
    emit('select', candidate);

    if (props.autoSave && context.value.task_id) {
        try {
            const data = await saveTaskAssignee(context.value.task_id, candidate.user_id);
            if (!data.success) {
                alert('Không thể lưu người thực hiện.');
                return;
            }
            await sendAiFeedback(candidate.recommendation_id, 'accepted', props.feedbackUrl);
            emit('applied', { task: data.task, candidate });
        } catch (err) {
            console.error('AI assign save error:', err);
            alert('Không thể lưu người thực hiện.');
            return;
        }
    }

    showAiModal.value = false;
};
</script>
