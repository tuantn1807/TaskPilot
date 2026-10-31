@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<link href="{{ asset('css/project-reports.css') }}?v={{ file_exists(public_path('css/project-reports.css')) ? filemtime(public_path('css/project-reports.css')) : time() }}" rel="stylesheet">
@endpush

@section('content')
<div class="col-xs-12 col-md-12 p-0">
    <div class="project-report-page">
        <!-- Project Header -->
        <div class="pd-header mb-3" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div class="pd-header-left">
                <div class="pd-breadcrumb" style="margin-bottom: 6px; font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('projects.index') }}" style="color: #64748b; text-decoration: none;">Dự án</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}" style="color: #64748b; text-decoration: none;">{{ $project->name }}</a>
                    <span>/</span>
                    <span style="color: #0f172a; font-weight: 700;">Báo cáo</span>
                </div>
                <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $project->name }}</h1>
                    <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
                    <span class="pd-project-status">{{ strtoupper($project->status ?? 'active') }}</span>
                    <a href="{{ route('projects.edit', $project) }}" class="pd-settings-btn" title="Cài đặt dự án">
                        <i class="fa fa-cog"></i>
                    </a>
                </div>
                <p class="project-report-subtitle" style="margin: 6px 0 0 0; color: #64748b; font-size: 13px;">
                    Báo cáo tiến độ, khối lượng công việc, Sprint và thời gian làm việc của dự án.
                </p>
            </div>
            <div>
                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}" class="tp-btn tp-btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; text-decoration: none;">
                    <i class="fa fa-plus-circle" style="color: #ffffff !important;"></i>
                    <span>Tạo công việc mới</span>
                </a>
            </div>
        </div>

        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'reports'])

        <!-- Hero Progress Banner -->
        <section class="project-report-hero">
            <div>
                <span class="project-report-eyebrow">
                    <i class="fa fa-bar-chart"></i> Báo cáo tổng thể dự án
                </span>
                <h2>Tổng quan tiến độ thực hiện</h2>
                <p>Theo dõi khối lượng công việc, tỷ lệ hoàn thành và kết quả của các Sprint trong dự án.</p>
            </div>
            <div class="project-report-progress">
                <strong>{{ $summary['progress'] }}%</strong>
                <span>Hoàn thành toàn dự án</span>
                <div class="project-report-progress-track">
                    <span style="width: {{ $summary['progress'] }}%;"></span>
                </div>
            </div>
        </section>

        <!-- KPI Stats Grid -->
        <section class="project-report-stats" aria-label="Chỉ số dự án">
            <article class="project-report-stat stat-blue">
                <span class="project-report-stat-icon">
                    <i class="fa fa-list-alt"></i>
                </span>
                <div>
                    <small>Tổng công việc</small>
                    <strong>{{ $summary['total'] }}</strong>
                    <span>Trong toàn dự án</span>
                </div>
            </article>

            <article class="project-report-stat stat-green">
                <span class="project-report-stat-icon">
                    <i class="fa fa-check-circle"></i>
                </span>
                <div>
                    <small>Đã hoàn thành</small>
                    <strong>{{ $summary['done'] }}</strong>
                    <span>{{ $summary['progress'] }}% khối lượng</span>
                </div>
            </article>

            <article class="project-report-stat stat-purple">
                <span class="project-report-stat-icon">
                    <i class="fa fa-line-chart"></i>
                </span>
                <div>
                    <small>Tiến độ</small>
                    <strong>{{ $summary['progress'] }}%</strong>
                    <span>Theo trạng thái task</span>
                </div>
            </article>

            <article class="project-report-stat stat-amber">
                <span class="project-report-stat-icon">
                    <i class="fa fa-clock-o"></i>
                </span>
                <div>
                    <small>Worklog & Điểm</small>
                    <strong>{{ number_format($summary['hours'], 1) }}h</strong>
                    <span>{{ $summary['done_points'] ?? 0 }} / {{ $summary['total_points'] ?? 0 }} Story Points</span>
                </div>
            </article>
        </section>

        <!-- Agile Analytics Charts: Burndown, Burnup & Velocity -->
        <section class="tp-analytics-grid" aria-label="Biểu đồ phân tích Agile">
            <!-- 1. Burndown Chart -->
            <div class="tp-analytics-card">
                <div>
                    <div class="tp-analytics-head">
                        <div>
                            <h3 class="tp-analytics-title">
                                <i class="fa fa-area-chart text-primary"></i>
                                Biểu đồ Burndown (Khối lượng còn lại)
                            </h3>
                            <p class="tp-analytics-sub">Phạm vi: {{ $analytics['scope_label'] ?? 'Toàn dự án' }}</p>
                        </div>
                        @if(($analytics['health_status'] ?? 'on_track') === 'on_track')
                            <span class="tp-analytics-badge badge-on-track">
                                <i class="fa fa-check-circle"></i> Đúng tiến độ
                            </span>
                        @else
                            <span class="tp-analytics-badge badge-at-risk">
                                <i class="fa fa-exclamation-triangle"></i> Cần đẩy nhanh tiến độ
                            </span>
                        @endif
                    </div>

                    <div class="tp-chart-legend">
                        <span class="tp-legend-item">
                            <span class="tp-legend-line dashed"></span> Đường lý tưởng (Ideal)
                        </span>
                        <span class="tp-legend-item">
                            <span class="tp-legend-line" style="background: #2563eb;"></span> Thực tế còn lại (Actual)
                        </span>
                    </div>

                    <div class="tp-chart-svg-wrap" id="burndown-chart-container"></div>
                </div>

                <div class="tp-analytics-footer">
                    <span>Tổng cam kết: <strong class="tp-analytics-metric-pill">{{ $analytics['total_points'] ?? 0 }} pts</strong></span>
                    <span>Còn lại: <strong class="tp-analytics-metric-pill">{{ $analytics['remaining_points'] ?? 0 }} pts</strong></span>
                </div>
            </div>

            <!-- 2. Burnup Chart -->
            <div class="tp-analytics-card">
                <div>
                    <div class="tp-analytics-head">
                        <div>
                            <h3 class="tp-analytics-title">
                                <i class="fa fa-line-chart" style="color: #10b981;"></i>
                                Biểu đồ Burnup (Tích lũy hoàn thành & Phạm vi)
                            </h3>
                            <p class="tp-analytics-sub">Theo dõi khối lượng tích lũy so với tổng phạm vi (Scope Creep)</p>
                        </div>
                        <span class="tp-analytics-badge badge-velocity">
                            <i class="fa fa-bullseye"></i> Đạt {{ $summary['progress'] }}%
                        </span>
                    </div>

                    <div class="tp-chart-legend">
                        <span class="tp-legend-item">
                            <span class="tp-legend-line" style="background: #f59e0b;"></span> Tổng phạm vi (Total Scope)
                        </span>
                        <span class="tp-legend-item">
                            <span class="tp-legend-line" style="background: #10b981;"></span> Khối lượng hoàn thành (Completed)
                        </span>
                    </div>

                    <div class="tp-chart-svg-wrap" id="burnup-chart-container"></div>
                </div>

                <div class="tp-analytics-footer">
                    <span>Đã hoàn thành: <strong class="tp-analytics-metric-pill">{{ $analytics['completed_points'] ?? 0 }} pts</strong></span>
                    <span>Phạm vi hiện tại: <strong class="tp-analytics-metric-pill">{{ $analytics['total_points'] ?? 0 }} pts</strong></span>
                </div>
            </div>

            <!-- 3. Sprint Velocity Chart -->
            <div class="tp-analytics-card full-width">
                <div>
                    <div class="tp-analytics-head">
                        <div>
                            <h3 class="tp-analytics-title">
                                <i class="fa fa-bar-chart" style="color: #6366f1;"></i>
                                Biểu đồ Velocity qua các Sprint (Tốc độ thực thi của Đội ngũ)
                            </h3>
                            <p class="tp-analytics-sub">So sánh Story Points cam kết (Committed) và thực tế hoàn thành (Completed) giữa các Sprint</p>
                        </div>
                        <span class="tp-analytics-badge badge-velocity">
                            <i class="fa fa-tachometer"></i> Velocity TB: {{ $analytics['velocity']['avg_points'] ?? 0 }} pts / Sprint
                        </span>
                    </div>

                    <div class="tp-chart-legend">
                        <span class="tp-legend-item">
                            <span class="tp-legend-swatch" style="background: #cbd5e1;"></span> Cam kết (Committed Points)
                        </span>
                        <span class="tp-legend-item">
                            <span class="tp-legend-swatch" style="background: #4f46e5;"></span> Hoàn thành (Completed Points)
                        </span>
                        <span class="tp-legend-item">
                            <span class="tp-legend-line dashed" style="background: repeating-linear-gradient(90deg, #ef4444 0, #ef4444 4px, transparent 4px, transparent 8px);"></span> Trung bình ({{ $analytics['velocity']['avg_points'] ?? 0 }} pts)
                        </span>
                    </div>

                    <div class="tp-chart-svg-wrap" id="velocity-chart-container"></div>
                </div>

                <div class="tp-analytics-footer">
                    <span>Số lượng Sprint: <strong class="tp-analytics-metric-pill">{{ count($analytics['velocity']['sprints'] ?? []) }} Sprint</strong></span>
                    <span>Trung bình hoàn thành: <strong class="tp-analytics-metric-pill">{{ $analytics['velocity']['avg_tasks'] ?? 0 }} tasks ({{ $analytics['velocity']['avg_points'] ?? 0 }} pts) / Sprint</strong></span>
                </div>
            </div>
        </section>

        <!-- Sprint Report Panel -->
        <section class="project-report-panel">
            <div class="project-report-panel-head">
                <div>
                    <h2><i class="fa fa-filter text-primary"></i> Báo cáo chi tiết theo Sprint</h2>
                    <p>Chọn Sprint để lọc dữ liệu biểu đồ Burndown/Burnup hoặc xuất báo cáo chi tiết.</p>
                </div>
                <div class="project-report-exports">
                    <a class="project-report-export-excel" href="{{ route('projects.reports.excel', $project) }}?sprint_id={{ $selectedSprint }}">
                        <i class="fa fa-file-excel-o"></i> Xuất Excel
                    </a>
                    <a class="project-report-export-pdf" target="_blank" rel="noopener" href="{{ route('projects.reports.pdf', $project) }}?sprint_id={{ $selectedSprint }}">
                        <i class="fa fa-file-pdf-o"></i> Xuất PDF
                    </a>
                </div>
            </div>

            <!-- Sprint Filter Toolbar -->
            <form method="GET" class="project-report-filter">
                <label for="report-sprint"><i class="fa fa-cubes me-1"></i> Lọc Sprint:</label>
                <select id="report-sprint" name="sprint_id" onchange="this.form.submit()">
                    <option value="">Tất cả Sprint</option>
                    @foreach($project->sprints as $sprint)
                        <option value="{{ $sprint->id }}" @selected($selectedSprint === $sprint->id)>
                            {{ $sprint->name }} ({{ ucfirst($sprint->status) }})
                        </option>
                    @endforeach
                </select>
                @if($selectedSprint)
                    <a href="{{ route('projects.reports.show', $project) }}" class="project-report-reset">
                        <i class="fa fa-times"></i> Xóa bộ lọc
                    </a>
                @endif
            </form>

            <!-- Table of Sprints -->
            <div class="project-report-table-wrap">
                <table class="project-report-table">
                    <thead>
                        <tr>
                            <th>Sprint</th>
                            <th>Trạng thái</th>
                            <th>Công việc</th>
                            <th>Story Points (Velocity)</th>
                            <th>Tiến độ</th>
                            <th>Worklog</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sprintRows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong>
                                </td>
                                <td>
                                    <span class="project-report-status" style="{{ $row['status'] === 'closed' ? 'background: #ecfdf5; color: #059669;' : ($row['status'] === 'active' ? 'background: #eff6ff; color: #2563eb;' : 'background: #f1f5f9; color: #64748b;') }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                                <td>{{ $row['done'] }} / {{ $row['total'] }} hoàn thành</td>
                                <td>
                                    <strong>{{ $row['completed_points'] ?? 0 }}</strong> / {{ $row['committed_points'] ?? 0 }} pts
                                </td>
                                <td>
                                    <div class="project-report-table-progress">
                                        <span style="width: {{ $row['progress'] }}%;"></span>
                                    </div>
                                    <strong style="color: #0f172a;">{{ $row['progress'] }}%</strong>
                                </td>
                                <td>
                                    <i class="fa fa-clock-o text-muted me-1"></i>
                                    <strong>{{ number_format($row['hours'], 1) }}h</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="project-report-empty">
                                    <i class="fa fa-folder-open-o" style="font-size: 28px; margin-bottom: 8px; display: block;"></i>
                                    <span>Chưa có Sprint nào phù hợp trong dự án này.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script>
(function() {
    var analytics = @json($analytics ?? []);

    function renderLineChart(containerId, labels, seriesA, seriesB, opts) {
        var el = document.getElementById(containerId);
        if (!el || !labels || !labels.length) return;

        var width = 620;
        var height = 230;
        var padLeft = 44;
        var padRight = 20;
        var padTop = 20;
        var padBottom = 34;
        var plotW = width - padLeft - padRight;
        var plotH = height - padTop - padBottom;

        var allVals = [].concat(seriesA || [], seriesB || [], [1]);
        var maxVal = Math.max.apply(null, allVals);
        maxVal = Math.ceil(maxVal * 1.15) || 10;

        function getX(idx) {
            if (labels.length <= 1) return padLeft + plotW / 2;
            return padLeft + (idx / (labels.length - 1)) * plotW;
        }

        function getY(val) {
            return padTop + plotH - (Math.max(0, val) / maxVal) * plotH;
        }

        var svg = '<svg viewBox="0 0 ' + width + ' ' + height + '" class="tp-chart-svg" role="img">';

        // Horizontal grid lines & Y-axis labels
        var steps = 4;
        for (var s = 0; s <= steps; s++) {
            var yVal = Math.round((maxVal / steps) * s);
            var yPos = getY(yVal);
            svg += '<line x1="' + padLeft + '" y1="' + yPos + '" x2="' + (width - padRight) + '" y2="' + yPos + '" stroke="#e2e8f0" stroke-dasharray="3,3" stroke-width="1" />';
            svg += '<text x="' + (padLeft - 8) + '" y="' + (yPos + 4) + '" text-anchor="end" font-size="11" fill="#64748b">' + yVal + '</text>';
        }

        // X-axis labels
        for (var i = 0; i < labels.length; i++) {
            var xPos = getX(i);
            svg += '<text x="' + xPos + '" y="' + (height - 10) + '" text-anchor="middle" font-size="11" fill="#64748b">' + labels[i] + '</text>';
        }

        // Area under Series B (Primary actual/completed line)
        var ptsB = [];
        for (var j = 0; j < seriesB.length; j++) {
            ptsB.push(getX(j) + ',' + getY(seriesB[j]));
        }
        if (ptsB.length > 1) {
            var areaPath = 'M ' + getX(0) + ',' + (padTop + plotH) + ' L ' + ptsB.join(' L ') + ' L ' + getX(seriesB.length - 1) + ',' + (padTop + plotH) + ' Z';
            svg += '<path d="' + areaPath + '" fill="' + (opts.areaFill || 'rgba(37, 99, 235, 0.10)') + '" />';
        }

        // Series A (Ideal / Scope line)
        var ptsA = [];
        for (var k = 0; k < seriesA.length; k++) {
            ptsA.push(getX(k) + ',' + getY(seriesA[k]));
        }
        if (ptsA.length > 1) {
            svg += '<polyline fill="none" stroke="' + (opts.colorA || '#94a3b8') + '" stroke-width="2.2" ' + (opts.dashedA ? 'stroke-dasharray="6,4"' : '') + ' points="' + ptsA.join(' ') + '" />';
        }

        // Series B (Actual / Completed line)
        if (ptsB.length > 1) {
            svg += '<polyline fill="none" stroke="' + (opts.colorB || '#2563eb') + '" stroke-width="3" points="' + ptsB.join(' ') + '" />';
        }

        // Interactive data points
        for (var m = 0; m < seriesB.length; m++) {
            var cx = getX(m);
            var cy = getY(seriesB[m]);
            var tip = labels[m] + ': ' + seriesB[m] + ' pts (' + (opts.labelB || 'Thực tế') + ') / ' + (seriesA[m] !== undefined ? seriesA[m] : 0) + ' pts (' + (opts.labelA || 'Mục tiêu') + ')';
            svg += '<circle cx="' + cx + '" cy="' + cy + '" r="4.5" fill="#ffffff" stroke="' + (opts.colorB || '#2563eb') + '" stroke-width="2.5"><title>' + tip + '</title></circle>';
        }

        svg += '</svg>';
        el.innerHTML = svg;
    }

    function renderVelocityChart(containerId, velocityData) {
        var el = document.getElementById(containerId);
        if (!el || !velocityData || !velocityData.sprints) return;

        var sprints = velocityData.sprints;
        var avgPoints = parseFloat(velocityData.avg_points || 0);
        var width = 920;
        var height = 240;
        var padLeft = 48;
        var padRight = 24;
        var padTop = 22;
        var padBottom = 38;
        var plotW = width - padLeft - padRight;
        var plotH = height - padTop - padBottom;

        var maxVal = 10;
        sprints.forEach(function(sp) {
            maxVal = Math.max(maxVal, sp.committed_points || 0, sp.completed_points || 0);
        });
        maxVal = Math.ceil(maxVal * 1.2) || 10;

        function getY(val) {
            return padTop + plotH - (Math.max(0, val) / maxVal) * plotH;
        }

        var svg = '<svg viewBox="0 0 ' + width + ' ' + height + '" class="tp-chart-svg" role="img">';

        // Grid lines
        var steps = 4;
        for (var s = 0; s <= steps; s++) {
            var yVal = Math.round((maxVal / steps) * s);
            var yPos = getY(yVal);
            svg += '<line x1="' + padLeft + '" y1="' + yPos + '" x2="' + (width - padRight) + '" y2="' + yPos + '" stroke="#e2e8f0" stroke-dasharray="3,3" stroke-width="1" />';
            svg += '<text x="' + (padLeft - 8) + '" y="' + (yPos + 4) + '" text-anchor="end" font-size="11" fill="#64748b">' + yVal + '</text>';
        }

        var groupWidth = plotW / Math.max(1, sprints.length);
        var barWidth = Math.min(34, Math.max(14, groupWidth * 0.28));

        sprints.forEach(function(sp, idx) {
            var centerX = padLeft + groupWidth * idx + groupWidth / 2;
            var committed = sp.committed_points || 0;
            var completed = sp.completed_points || 0;

            var yComm = getY(committed);
            var hComm = padTop + plotH - yComm;
            var yComp = getY(completed);
            var hComp = padTop + plotH - yComp;

            var xComm = centerX - barWidth - 3;
            var xComp = centerX + 3;

            // Committed Bar
            svg += '<rect x="' + xComm + '" y="' + yComm + '" width="' + barWidth + '" height="' + Math.max(2, hComm) + '" rx="4" fill="#cbd5e1">' +
                   '<title>' + sp.name + ' - Cam kết: ' + committed + ' pts (' + (sp.total || 0) + ' tasks)</title></rect>';
            if (committed > 0) {
                svg += '<text x="' + (xComm + barWidth / 2) + '" y="' + (yComm - 5) + '" text-anchor="middle" font-size="10.5" font-weight="600" fill="#64748b">' + committed + '</text>';
            }

            // Completed Bar
            svg += '<rect x="' + xComp + '" y="' + yComp + '" width="' + barWidth + '" height="' + Math.max(2, hComp) + '" rx="4" fill="#4f46e5">' +
                   '<title>' + sp.name + ' - Hoàn thành: ' + completed + ' pts (' + (sp.done || 0) + ' tasks)</title></rect>';
            if (completed > 0) {
                svg += '<text x="' + (xComp + barWidth / 2) + '" y="' + (yComp - 5) + '" text-anchor="middle" font-size="10.5" font-weight="700" fill="#4f46e5">' + completed + '</text>';
            }

            // Sprint Name X-axis label
            var shortName = (sp.name || '').length > 18 ? sp.name.substring(0, 16) + '…' : sp.name;
            svg += '<text x="' + centerX + '" y="' + (height - 12) + '" text-anchor="middle" font-size="11.5" font-weight="600" fill="#475569">' + shortName + '</text>';
        });

        // Average Velocity dashed line
        if (avgPoints > 0) {
            var yAvg = getY(avgPoints);
            svg += '<line x1="' + padLeft + '" y1="' + yAvg + '" x2="' + (width - padRight) + '" y2="' + yAvg + '" stroke="#ef4444" stroke-width="2" stroke-dasharray="6,4" />';
        }

        svg += '</svg>';
        el.innerHTML = svg;
    }

    if (analytics.burndown) {
        renderLineChart(
            'burndown-chart-container',
            analytics.burndown.labels,
            analytics.burndown.ideal,
            analytics.burndown.actual,
            {
                colorA: '#94a3b8',
                dashedA: true,
                labelA: 'Lý tưởng',
                colorB: '#2563eb',
                labelB: 'Thực tế còn lại',
                areaFill: 'rgba(37, 99, 235, 0.12)'
            }
        );
    }

    if (analytics.burnup) {
        renderLineChart(
            'burnup-chart-container',
            analytics.burnup.labels,
            analytics.burnup.scope,
            analytics.burnup.completed,
            {
                colorA: '#f59e0b',
                dashedA: false,
                labelA: 'Tổng phạm vi',
                colorB: '#10b981',
                labelB: 'Đã hoàn thành',
                areaFill: 'rgba(16, 185, 129, 0.14)'
            }
        );
    }

    if (analytics.velocity) {
        renderVelocityChart('velocity-chart-container', analytics.velocity);
    }
})();
</script>
@endsection
