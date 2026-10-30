/* empty css           */function e(){return document.querySelector(`meta[name="csrf-token"]`)?.getAttribute(`content`)||``}function t(e){return String(e??``).replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`).replace(/"/g,`&quot;`).replace(/'/g,`&#39;`)}function n(e){return e?.name||e?.display_name||e?.assignee||`Thành viên`}function r(e){if(e?.confidence_percent!=null&&e.confidence_percent!==``)return e.confidence_percent;let t=Number(e?.score);return Number.isFinite(t)?Math.round(t*100):0}function i(){let e=document.getElementById(`tp-ai-detail-submodal`);return e||(e=document.createElement(`div`),e.id=`tp-ai-detail-submodal`,e.className=`ai-submodal-overlay`,e.style.display=`none`,document.body.appendChild(e),e.addEventListener(`click`,t=>{(t.target===e||t.target.closest(`[data-submodal-close]`))&&(e.style.display=`none`)}),e)}function a(e,a,o){let s=i();function c(i){let a=``;if(i===`skills`){let r=e.skills||[];a=r.length?`
                    <p class="ai-tab-desc">Hồ sơ toàn bộ kỹ năng chuyên môn của <strong>${t(n(e))}</strong> và độ tương thích với task này:</p>
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
                                ${r.map(e=>{let n=e.is_matched?`ai-tr-matched`:e.is_hint?`ai-tr-hint`:``,r=e.is_matched?`fa fa-check-circle text-purple`:e.is_hint?`fa fa-dot-circle text-blue`:`fa fa-circle text-muted`,i=`<span class="ai-pill-badge ai-pill-secondary">Kỹ năng phụ trợ</span>`;e.is_matched?i=`<span class="ai-pill-badge ai-pill-direct"><i class="fa fa-bullseye"></i> Khớp trực tiếp</span>`:e.is_hint&&(i=`<span class="ai-pill-badge ai-pill-hint"><i class="fa fa-lightbulb"></i> Lĩnh vực liên quan</span>`);let a=Array.from({length:5},(t,n)=>`<i class="fa fa-star${n<(e.level||3)?` filled`:``}"></i>`).join(``);return`
                                        <tr class="${n}">
                                            <td class="ai-td-skill">
                                                <i class="${r}"></i>
                                                <span class="font-bold">${t(e.name)}</span>
                                            </td>
                                            <td>
                                                <div class="ai-proficiency-wrap">
                                                    <div class="ai-stars">${a}</div>
                                                    <span class="ai-prof-label">${t(e.level_label||``)} (${e.proficiency_percent}%)</span>
                                                </div>
                                            </td>
                                            <td>${e.years?`${e.years} năm`:`<span class="text-muted">—</span>`}</td>
                                            <td>${i}</td>
                                        </tr>
                                    `}).join(``)}
                            </tbody>
                        </table>
                    </div>
                `:`<div class="ai-tab-empty"><i class="fa fa-info-circle"></i> Chưa có kỹ năng nào được đăng ký cho ứng viên này.</div>`}else if(i===`expertise`){let n=e.details?.job_hits||[],r=e.active_tasks||[];a=`
                <div class="ai-expertise-grid">
                    <div class="ai-info-card">
                        <div class="ai-info-card-header"><i class="fa fa-id-badge text-purple"></i><span>Hồ sơ Chuyên môn</span></div>
                        <div class="ai-info-card-body">
                            <div class="ai-info-row"><span class="ai-info-label">Chức danh / Vị trí:</span><span class="ai-info-val font-bold">${t(e.job_title||`Chưa cập nhật`)}</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Phòng ban:</span><span class="ai-info-val">${t(e.department||`Chưa cập nhật`)}</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Trạng thái:</span><span class="ai-pill-badge ai-pill-success">Đang hoạt động</span></div>
                            ${n.length?`
                            <div class="ai-info-row">
                                <span class="ai-info-label">Từ khóa chuyên môn phù hợp:</span>
                                <div class="ai-tag-group">${n.map(e=>`<span class="ai-tag">${t(e)}</span>`).join(``)}</div>
                            </div>
                            `:``}
                        </div>
                    </div>
                    <div class="ai-info-card">
                        <div class="ai-info-card-header"><i class="fa fa-tachometer text-emerald"></i><span>Tải Công Việc Hiện Tại</span></div>
                        <div class="ai-info-card-body">
                            <div class="ai-info-row"><span class="ai-info-label">Số task đang xử lý:</span><span class="ai-info-val font-bold">${e.active_tasks_count??0} task</span></div>
                            <div class="ai-info-row"><span class="ai-info-label">Đánh giá khả dụng:</span><span class="ai-pill-badge ai-pill-info">${e.active_tasks_count===0?`Sẵn sàng nhận việc ngay`:`${e.active_tasks_count} task đang xử lý`}</span></div>
                            ${r.length?`
                            <div class="ai-active-tasks-box">
                                <div class="ai-box-title">Danh sách task đang phụ trách:</div>
                                <ul class="ai-mini-task-list">
                                    ${r.map(e=>`
                                        <li>
                                            <a href="/tasks/${e.id}" target="_blank" class="ai-mini-task-link">#${e.id} - ${t(e.name)}</a>
                                            <span class="ai-mini-badge">${t(e.status)}</span>
                                        </li>
                                    `).join(``)}
                                </ul>
                            </div>
                            `:``}
                        </div>
                    </div>
                </div>
            `}else if(i===`history`){let r=e.history_tasks||[],i=e.details?.overlap_keywords||[];a=r.length?`
                    <p class="ai-tab-desc">Các công việc <strong>${t(n(e))}</strong> đã từng hoàn thành trong dự án này:</p>
                    ${i.length?`
                    <div class="ai-overlap-bar">
                        <span class="font-bold">Từ khóa tương đồng trong lịch sử:</span>
                        <div class="ai-tag-group">${i.map(e=>`<span class="ai-tag ai-tag-accent">${t(e)}</span>`).join(``)}</div>
                    </div>
                    `:``}
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
                                ${r.map(e=>`
                                    <tr>
                                        <td><a href="/tasks/${e.id}" target="_blank" class="ai-task-link">#${e.id} <i class="fa fa-external-link-alt" style="font-size: 10px;"></i></a></td>
                                        <td class="font-medium">${t(e.name)}</td>
                                        <td><span class="ai-pill-badge ai-pill-muted">${t(e.issue_type)}</span></td>
                                        <td><span class="ai-pill-badge ${e.priority===`high`?`ai-pill-danger`:`ai-pill-info`}">${t(e.priority)}</span></td>
                                        <td><span class="ai-pill-badge ${e.status===`done`||e.status===`completed`?`ai-pill-success`:`ai-pill-warning`}">${t(e.status)}</span></td>
                                        <td class="text-muted">${t(e.created_at||`—`)}</td>
                                    </tr>
                                `).join(``)}
                            </tbody>
                        </table>
                    </div>
                `:`<div class="ai-tab-empty"><i class="fa fa-info-circle"></i> Chưa có lịch sử công việc nào trong dự án này.</div>`}s.innerHTML=`
            <div class="ai-submodal-card">
                <div class="ai-submodal-header">
                    <div class="ai-submodal-title">
                        <div class="ai-candidate-avatar ai-submodal-avatar">${t(n(e).charAt(0).toUpperCase())}</div>
                        <div>
                            <div class="ai-submodal-name">
                                ${t(n(e))}
                                <span class="ai-submodal-badge">${r(e)}% Phù hợp</span>
                            </div>
                            <div class="ai-submodal-subtitle">
                                <span>${t(e.job_title||`Thành viên dự án`)}</span>
                                ${e.department?`<span class="ai-dot">•</span><span>Phòng ban: ${t(e.department)}</span>`:``}
                                <span class="ai-dot">•</span>
                                <span>${e.active_tasks_count??0} task đang làm</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="ai-modal-close" data-submodal-close>&times;</button>
                </div>
                <div class="ai-submodal-tabs">
                    <button type="button" class="ai-submodal-tab${i===`skills`?` active`:``}" data-tab-switch="skills">
                        <i class="fa fa-bullseye"></i> <span>Kỹ năng (${(e.skills||[]).length})</span>
                    </button>
                    <button type="button" class="ai-submodal-tab${i===`expertise`?` active`:``}" data-tab-switch="expertise">
                        <i class="fa fa-briefcase"></i> <span>Chuyên môn & Tải việc</span>
                    </button>
                    <button type="button" class="ai-submodal-tab${i===`history`?` active`:``}" data-tab-switch="history">
                        <i class="fa fa-history"></i> <span>Lịch sử dự án (${(e.history_tasks||[]).length})</span>
                    </button>
                </div>
                <div class="ai-submodal-body">
                    ${a}
                </div>
                <div class="ai-submodal-footer">
                    <button type="button" class="btn btn-secondary ai-submodal-btn" data-submodal-close>Đóng</button>
                    <button type="button" class="btn btn-primary ai-submodal-btn" data-submodal-pick="${e.user_id}">
                        <i class="fa fa-check" style="margin-right: 6px;"></i> Chọn người này
                    </button>
                </div>
            </div>
        `,s.querySelectorAll(`[data-tab-switch]`).forEach(e=>{e.addEventListener(`click`,()=>{c(e.getAttribute(`data-tab-switch`))})}),s.querySelectorAll(`[data-submodal-pick]`).forEach(t=>{t.addEventListener(`click`,()=>{s.style.display=`none`,o&&o(e)})})}c(a),s.style.display=`flex`}function o(){let e=document.getElementById(`tp-ai-assignee-modal`);return e||(e=document.createElement(`div`),e.id=`tp-ai-assignee-modal`,e.className=`ai-modal-overlay`,e.style.display=`none`,e.innerHTML=`
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
    `,document.body.appendChild(e),e.addEventListener(`click`,t=>{(t.target===e||t.target.closest(`[data-ai-close]`))&&(e.style.display=`none`)}),e)}function s(e,i){return e.length?`<div class="ai-candidate-list">${e.map(e=>{let a=String(i||``)===String(e.user_id)?` selected`:``,o=Array.isArray(e.match_reasons)?e.match_reasons.map(n=>{let r=`fa fa-check-circle`,i=`#10b981`,a=``;return n.includes(`Kỹ năng`)?(r=`fa fa-bullseye`,i=`#8b5cf6`,a=`skills`):n.includes(`Chuyên môn`)||n.includes(`Lĩnh vực`)?(r=`fa fa-briefcase`,i=`#2563eb`,a=`expertise`):n.includes(`Lịch sử`)||n.includes(`Đã xử lý`)?(r=`fa fa-history`,i=`#f59e0b`,a=`history`):n.includes(`Tải công việc`)||n.includes(`Sẵn sàng`)||n.includes(`trống việc`)?(r=`fa fa-tachometer`,i=`#10b981`,a=`expertise`):n.includes(`ngữ nghĩa`)&&(r=`fa fa-brain`,i=`#ec4899`),`
                <div class="ai-reason-item">
                    <div class="ai-reason-main">
                        <i class="${r}" style="color: ${i};"></i>
                        <span class="ai-reason-text">${t(n)}</span>
                    </div>
                    ${a?`
                    <button type="button" class="ai-reason-detail-pill" data-detail-user="${e.user_id}" data-detail-tab="${a}" title="Xem chi tiết ${t(a)}">
                        <span>Xem chi tiết</span>
                        <i class="fa fa-chevron-right" style="font-size: 11px;"></i>
                    </button>
                    `:``}
                </div>
            `}).join(``):``;return`
            <div class="ai-candidate-card${a}">
                <div class="ai-candidate-header">
                    <div class="ai-candidate-info">
                        <div class="ai-candidate-avatar">${t(n(e).charAt(0).toUpperCase())}</div>
                        <div>
                            <div class="ai-candidate-name">${t(n(e))}</div>
                            <div class="ai-candidate-meta">
                                <span>${t(e.job_title||`Thành viên dự án`)}</span>
                                <span class="ai-dot">•</span>
                                <span>${e.active_tasks_count??0} task đang làm</span>
                            </div>
                        </div>
                    </div>
                    <div class="ai-candidate-score-badge">${r(e)}% Phù hợp</div>
                </div>
                ${o?`
                    <div class="ai-candidate-reasons-block">
                        <div class="ai-reasons-header">
                            <div class="ai-reasons-title">
                                <i class="fa fa-info-circle" style="color: #7c3aed;"></i>
                                <span>Chi tiết thông tin đánh giá:</span>
                            </div>
                        </div>
                        <div class="ai-candidate-reasons">${o}</div>
                    </div>
                `:``}
                <div class="ai-candidate-action">
                    <button type="button" class="ai-select-btn" data-ai-pick="${e.user_id}">
                        <i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này
                    </button>
                </div>
            </div>
        `}).join(``)}</div>`:`<div class="ai-empty-state"><i class="fa fa-info-circle"></i> Chưa tìm thấy ứng viên phù hợp.</div>`}async function c(t){let n=await fetch(`/tasks/recommend-assignee`,{method:`POST`,headers:{"Content-Type":`application/json`,"X-CSRF-TOKEN":e(),Accept:`application/json`,"X-Requested-With":`XMLHttpRequest`},body:JSON.stringify({name:t.name||``,summary:t.name||``,description:t.description||``,project_id:t.project_id||null,issue_type:t.issue_type||`task`,priority:t.priority||`medium`,task_id:t.task_id||null})}),r=await n.json().catch(()=>({}));if(!n.ok||!r.success||!Array.isArray(r.recommendations))throw Error(r.message===`This action is unauthorized.`?`Bạn không có quyền dùng AI gợi ý cho công việc này.`:r.message||`Không thể tải gợi ý AI vào lúc này.`);return r.recommendations}async function l(t,n){return(await fetch(`/tasks/${t}`,{method:`POST`,headers:{"Content-Type":`application/json`,"X-CSRF-TOKEN":e(),Accept:`application/json`,"X-Requested-With":`XMLHttpRequest`},body:JSON.stringify({_method:`PUT`,assignee_id:n})})).json()}async function u(t){if(t)try{await fetch(`/tasks/ai-feedback`,{method:`POST`,headers:{"Content-Type":`application/json`,"X-CSRF-TOKEN":e(),Accept:`application/json`,"X-Requested-With":`XMLHttpRequest`},body:JSON.stringify({recommendation_id:t,status:`accepted`})})}catch(e){console.warn(`AI feedback error:`,e)}}function d(e){let t=e.closest(`form`),n=e=>document.getElementById(e)?.value||``;return{name:e.dataset.name||n(`name`)||n(`input-task-name`),description:e.dataset.description||n(`description`)||``,project_id:e.dataset.projectId||n(`project_id`)||null,issue_type:e.dataset.issueType||n(`issue_type`)||`task`,priority:e.dataset.priority||n(`priority`)||`medium`,task_id:e.dataset.taskId||null,mode:e.dataset.aiMode||`select`,select:e.dataset.aiSelect||`#assignee_id`,form:t}}async function f(e){let t=d(e);if(!t.name&&!t.description){alert(`Hãy nhập tên hoặc mô tả công việc trước khi dùng AI đề xuất.`);return}let r=e.innerHTML;e.disabled=!0,e.innerHTML=`<i class="fa fa-spinner fa-spin"></i> AI`;try{let e=await c(t),r=o(),i=r.querySelector(`[data-ai-body]`);i.innerHTML=s(e,t.form?.querySelector(t.select)?.value||``),r.style.display=`flex`,i.querySelectorAll(`[data-detail-user]`).forEach(t=>{t.addEventListener(`click`,n=>{n.stopPropagation();let r=t.getAttribute(`data-detail-user`),o=t.getAttribute(`data-detail-tab`)||`skills`,s=e.find(e=>String(e.user_id)===String(r));s&&a(s,o,e=>{let t=i.querySelector(`[data-ai-pick="${e.user_id}"]`);t&&t.click()})})}),i.querySelectorAll(`[data-ai-pick]`).forEach(i=>{i.addEventListener(`click`,async()=>{let a=i.getAttribute(`data-ai-pick`),o=e.find(e=>String(e.user_id)===String(a));if(t.mode===`save`&&t.task_id){i.disabled=!0,i.innerHTML=`<i class="fa fa-spinner fa-spin"></i> Đang lưu...`;try{if(!(await l(t.task_id,a)).success){alert(`Không thể lưu người thực hiện.`),i.disabled=!1,i.innerHTML=`<i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này`;return}await u(o?.recommendation_id),window.location.reload();return}catch(e){console.error(e),alert(`Không thể lưu người thực hiện.`),i.disabled=!1,i.innerHTML=`<i class="fa fa-check" style="margin-right: 4px;"></i> Chọn người này`;return}}let s=document.querySelector(t.select);if(s){if(![...s.options].some(e=>String(e.value)===String(a))){let e=document.createElement(`option`);e.value=a,e.textContent=`👤 ${n(o)}`,s.appendChild(e)}s.value=a,s.dispatchEvent(new Event(`change`,{bubbles:!0}))}r.style.display=`none`})})}catch(e){console.error(e),alert(e.message||`Lỗi kết nối khi gọi dịch vụ AI.`)}finally{e.disabled=!1,e.innerHTML=r}}document.addEventListener(`click`,e=>{let t=e.target.closest(`[data-ai-assignee]`);t&&(e.preventDefault(),e.stopPropagation(),f(t))});