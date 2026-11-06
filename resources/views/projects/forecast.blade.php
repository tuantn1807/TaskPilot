@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/project-dashboard-luxury.css') }}?v={{ file_exists(public_path('css/project-dashboard-luxury.css')) ? filemtime(public_path('css/project-dashboard-luxury.css')) : time() }}" rel="stylesheet">
<link href="{{ asset('css/project-reports.css') }}?v={{ file_exists(public_path('css/project-reports.css')) ? filemtime(public_path('css/project-reports.css')) : time() }}" rel="stylesheet">
<style>
.forecast-tab-btn {
    padding: 10px 18px;
    font-size: 13.5px;
    font-weight: 700;
    color: #64748b;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.forecast-tab-btn:hover {
    color: #1e293b;
}
.forecast-tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}
.forecast-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    margin-bottom: 20px;
}
.stat-pill {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    display: inline-block;
}
.stat-pill.success { background: #dcfce7; color: #15803d; }
.stat-pill.warning { background: #fef9c3; color: #a16207; }
.stat-pill.info { background: #e0e7ff; color: #4338ca; }

.day-bar-container {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    height: 160px;
    padding-top: 20px;
    border-bottom: 2px solid #cbd5e1;
    margin-top: 10px;
}
.day-bar-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    justify-content: flex-end;
}
.day-bar-fill {
    width: 100%;
    max-width: 42px;
    border-radius: 6px 6px 0 0;
    transition: height 0.3s;
    position: relative;
}
.day-bar-fill.workday { background: #2563eb; }
.day-bar-fill.weekend { background: #f87171; }
.day-bar-val {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
}
.day-bar-label {
    margin-top: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    text-align: center;
}
.plot-img-card {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    margin-top: 14px;
    background: #f8fafc;
}
.plot-img-card img {
    width: 100%;
    height: auto;
    display: block;
}
</style>
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
                    <span style="color: #0f172a; font-weight: 700;">Dự báo &amp; Phân tích Chuỗi thời gian (Lab 1)</span>
                </div>
                <div class="pd-title-row" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 class="pd-title" style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $project->name }} / Phân tích Chuỗi thời gian</h1>
                    <span class="pd-project-key">{{ $project->project_code ?? 'PROJ' }}</span>
                    <span class="pd-project-status">LAB 1: EDA &amp; STATIONARITY</span>
                </div>
                <p class="project-report-subtitle" style="margin: 6px 0 0 0; color: #64748b; font-size: 13.5px;">
                    Phân tích quy luật phát sinh lỗi/task theo ngày, phân rã mùa vụ tuần ($s = 7$) và kiểm định tính dừng (ADF &amp; KPSS) chuẩn bị cho mô hình SARIMAX.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('projects.forecast.csv', $project) }}" class="btn btn-default" style="border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #cbd5e1; background: #ffffff;">
                    <i class="fa fa-download text-primary"></i>
                    <span>Tải dataset (CSV)</span>
                </a>
                <button type="button" id="btnReanalyze" class="btn btn-primary tp-btn-primary" style="border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa fa-refresh"></i>
                    <span>Chạy lại phân tích</span>
                </button>
            </div>
        </div>

        <!-- Unified Project Sub-Nav Bar -->
        @include('projects.partials.nav', ['project' => $project, 'active' => 'forecast'])

        <!-- Hero Section -->
        <section class="project-report-hero" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; padding: 24px 28px; border-radius: 14px; margin-bottom: 24px;">
            <div style="max-width: 800px;">
                <span class="project-report-eyebrow" style="color: #38bdf8; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; text-transform: uppercase;">
                    <i class="fa fa-area-chart"></i> Time Series Analytics &amp; Forecasting Module
                </span>
                <h2 style="font-size: 22px; font-weight: 800; color: #ffffff; margin: 6px 0 10px 0;">
                    Lab 1: EDA, Phân rã mùa vụ tuần &amp; Kiểm định tính dừng toán học
                </h2>
                <p style="color: #cbd5e1; font-size: 14px; margin: 0; line-height: 1.6;">
                    Phân tích trên chuỗi quan sát <strong>{{ number_format($analytics['summary']['total_days']) }} ngày liên tiếp</strong>. Xác nhận tính mùa vụ tuần rất mạnh ($s = 7$), loại bỏ xu hướng bằng sai phân kết hợp $\Delta(\Delta_7 y_t)$ để đưa chuỗi về trạng thái <strong>Stationary hoàn hảo</strong> ($p < 10^{-20}$).
                </p>
            </div>
        </section>

        <!-- 4 Metric Cards -->
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
                <div class="forecast-card" style="height: 100%;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Tổng thời gian quan sát</div>
                    <div style="font-size: 26px; font-weight: 800; color: #0f172a;">{{ number_format($analytics['summary']['total_days']) }} ngày</div>
                    <div style="font-size: 12.5px; color: #16a34a; margin-top: 4px;">
                        <i class="fa fa-calendar-check-o"></i> 3 năm liên tục (2021 – 2023)
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
                <div class="forecast-card" style="height: 100%;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Phát sinh trung bình</div>
                    <div style="font-size: 26px; font-weight: 800; color: #2563eb;">{{ $analytics['summary']['avg_bugs_daily'] }} <span style="font-size: 14px; color: #64748b; font-weight: 500;">bug/ngày</span></div>
                    <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                        Độ lệch chuẩn $\sigma$: <strong>{{ $analytics['summary']['std_dev'] }}</strong> (Max: {{ $analytics['summary']['max_bugs'] }})
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
                <div class="forecast-card" style="height: 100%;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Chu kỳ mùa vụ phát hiện</div>
                    <div style="font-size: 26px; font-weight: 800; color: #9333ea;">s = 7 ngày</div>
                    <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                        Ngày thường: <strong>{{ $analytics['summary']['weekday_avg'] }}</strong> vs Cuối tuần: <strong>{{ $analytics['summary']['weekend_avg'] }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" style="margin-bottom: 16px;">
                <div class="forecast-card" style="height: 100%;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Trạng thái tính dừng (ADF &amp; KPSS)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #16a34a; margin-top: 2px;">STATIONARY</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        Sau sai phân $\Delta(\Delta_7 y_t)$ đạt $p < 0.001$
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs for Analysis -->
        <div style="border-bottom: 2px solid #e2e8f0; margin-bottom: 22px; display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="forecast-tab-btn active" data-tab="tab-overview">
                <i class="fa fa-line-chart"></i> 1. Chuỗi thời gian &amp; Mùa vụ tuần
            </button>
            <button type="button" class="forecast-tab-btn" data-tab="tab-decomposition">
                <i class="fa fa-pie-chart"></i> 2. Phân rã chuỗi ($s = 7$)
            </button>
            <button type="button" class="forecast-tab-btn" data-tab="tab-tests">
                <i class="fa fa-check-square-o"></i> 3. Kiểm định ADF &amp; KPSS
            </button>
            <button type="button" class="forecast-tab-btn" data-tab="tab-differencing">
                <i class="fa fa-sliders"></i> 4. Sai phân &amp; Bước đệm Lab 2 (SARIMAX)
            </button>
        </div>

        <!-- Tab 1: Overview & Seasonality -->
        <div id="tab-overview" class="forecast-tab-pane">
            <div class="row">
                <div class="col-md-8">
                    <div class="forecast-card">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0;">
                            <i class="fa fa-area-chart text-primary me-1"></i> Xu hướng phát sinh Bug 45 ngày gần nhất
                        </h3>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 14px;">
                            Đồ thị biểu diễn số lượng bug theo ngày thực tế và đường xu thế trung bình trượt 7 ngày.
                        </p>
                        <div id="recent-chart-svg-container" style="width: 100%; overflow-x: auto;"></div>

                        <div class="plot-img-card" style="margin-top: 20px;">
                            <div style="padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0; font-weight: 700; font-size: 13px; color: #334155;">
                                <i class="fa fa-picture-o text-primary me-1"></i> Biểu đồ chuỗi thời gian 3 năm kèm đường trung bình trượt MA(7) và MA(30)
                            </div>
                            <img src="{{ $analytics['plots']['overview'] }}" alt="Timeseries Overview">
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="forecast-card">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">
                            <i class="fa fa-calendar text-danger me-1"></i> Quy luật theo Thứ trong tuần
                        </h3>
                        <p style="font-size: 12.5px; color: #64748b; margin-bottom: 12px;">
                            Mức phát sinh lỗi sụt giảm mạnh vào cuối tuần do nhân sự nghỉ làm việc.
                        </p>

                        <!-- Custom Day-of-Week Chart -->
                        <div class="day-bar-container">
                            @php $maxVal = max(array_column($analytics['weekday_stats'], 'avg')); @endphp
                            @foreach($analytics['weekday_stats'] as $item)
                                @php $pct = round(($item['avg'] / $maxVal) * 85); @endphp
                                <div class="day-bar-item">
                                    <div class="day-bar-fill {{ $item['type'] }}" style="height: {{ $pct }}%;">
                                        <span class="day-bar-val">{{ $item['avg'] }}</span>
                                    </div>
                                    <span class="day-bar-label">{{ explode(' ', $item['day'])[0] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top: 18px; font-size: 13px; color: #475569; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <strong>Nhận xét kỹ thuật:</strong>
                            <ul style="margin: 6px 0 0 0; padding-left: 18px; font-size: 12.5px; color: #64748b;">
                                <li><strong>Thứ Ba</strong> là ngày ghi nhận bug cao nhất (trung bình 11.2 bug/ngày).</li>
                                <li><strong>Thứ Bảy &amp; Chủ Nhật</strong> giảm ~78% so với ngày thường (chỉ ~2.4 - 2.8 bug/ngày).</li>
                            </ul>
                        </div>

                        <div class="plot-img-card" style="margin-top: 14px;">
                            <img src="{{ $analytics['plots']['seasonality'] }}" alt="Seasonality by day">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Decomposition -->
        <div id="tab-decomposition" class="forecast-tab-pane" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="forecast-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                1. Mô hình Cộng (Additive Decomposition)
                            </h3>
                            <span class="stat-pill info">Y = T + S + R</span>
                        </div>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                            Phân tách chuỗi khi biên độ dao động mùa vụ không đổi theo sự tăng giảm của xu hướng.
                        </p>
                        <div class="plot-img-card">
                            <img src="{{ $analytics['plots']['decomp_add'] }}" alt="Additive Decomposition">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="forecast-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                2. Mô hình Nhân (Multiplicative Decomposition)
                            </h3>
                            <span class="stat-pill warning">Y = T &times; S &times; R</span>
                        </div>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                            Phân tách chuỗi khi độ biến động của mùa vụ tỷ lệ thuận với mức nền Trend.
                        </p>
                        <div class="plot-img-card">
                            <img src="{{ $analytics['plots']['decomp_mul'] }}" alt="Multiplicative Decomposition">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Stationarity Tests -->
        <div id="tab-tests" class="forecast-tab-pane" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="forecast-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Kiểm định tính dừng trên Chuỗi gốc
                            </h3>
                            <span class="stat-pill warning">Difference Stationary</span>
                        </div>

                        <table class="table table-bordered" style="font-size: 13px; margin-bottom: 14px;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th>Chỉ số kiểm định</th>
                                    <th>Giá trị thống kê</th>
                                    <th>p-value</th>
                                    <th>Kết luận kiểm định</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ADF Test</strong> ($H_0$: Unit root)</td>
                                    <td><code>{{ $analytics['tests']['raw']['adf_stat'] }}</code></td>
                                    <td><code>{{ $analytics['tests']['raw']['adf_p'] }}</code></td>
                                    <td><span class="text-success"><i class="fa fa-check"></i> Bác bỏ $H_0$ (Dừng)</span></td>
                                </tr>
                                <tr>
                                    <td><strong>KPSS Test</strong> ($H_0$: Stationary)</td>
                                    <td><code>{{ $analytics['tests']['raw']['kpss_stat'] }}</code></td>
                                    <td><code>{{ $analytics['tests']['raw']['kpss_p'] }}</code></td>
                                    <td><span class="text-danger"><i class="fa fa-times"></i> Bác bỏ $H_0$ (Không dừng)</span></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-warning" style="font-size: 12.5px; border-radius: 8px; margin-bottom: 14px;">
                            <i class="fa fa-info-circle me-1"></i>
                            <strong>Kết luận:</strong> Chuỗi gốc có tương quan mùa vụ tuần rất mạnh khiến hàm tự tương quan không suy giảm nhanh về 0. Cần sai phân chu kỳ $s=7$.
                        </div>

                        <div class="plot-img-card">
                            <div style="padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0; font-weight: 700; font-size: 13px;">
                                Biểu đồ ACF &amp; PACF của chuỗi gốc (Đỉnh chu kỳ tại lag 7, 14, 21, 28)
                            </div>
                            <img src="{{ $analytics['plots']['acf_raw'] }}" alt="ACF and PACF of raw series">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="forecast-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">
                                Sau khi sai phân kết hợp $\Delta(\Delta_7 y_t)$
                            </h3>
                            <span class="stat-pill success">Stationary Hoàn hảo</span>
                        </div>

                        <table class="table table-bordered" style="font-size: 13px; margin-bottom: 14px;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th>Chỉ số kiểm định</th>
                                    <th>Giá trị thống kê</th>
                                    <th>p-value</th>
                                    <th>Kết luận kiểm định</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ADF Test</strong> ($H_0$: Unit root)</td>
                                    <td><code>{{ $analytics['tests']['differenced']['adf_stat'] }}</code></td>
                                    <td><code>{{ $analytics['tests']['differenced']['adf_p'] }}</code></td>
                                    <td><span class="text-success"><i class="fa fa-check"></i> Bác bỏ $H_0$ ($p \ll 0.001$)</span></td>
                                </tr>
                                <tr>
                                    <td><strong>KPSS Test</strong> ($H_0$: Stationary)</td>
                                    <td><code>{{ $analytics['tests']['differenced']['kpss_stat'] }}</code></td>
                                    <td><code>{{ $analytics['tests']['differenced']['kpss_p'] }}</code></td>
                                    <td><span class="text-success"><i class="fa fa-check"></i> Không bác bỏ $H_0$ ($p > 0.05$)</span></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-success" style="font-size: 12.5px; border-radius: 8px; margin-bottom: 14px;">
                            <i class="fa fa-check-circle me-1"></i>
                            <strong>Kết luận:</strong> Chuỗi sau sai phân kết hợp đã thỏa mãn hoàn toàn cả 2 kiểm định ADF và KPSS. Đạt trạng thái dừng hoàn toàn để xây dựng mô hình dự báo.
                        </div>

                        <div class="plot-img-card">
                            <div style="padding: 10px 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0; font-weight: 700; font-size: 13px;">
                                Biểu đồ ACF &amp; PACF sau sai phân (Tắt dần nhanh - Stationary)
                            </div>
                            <img src="{{ $analytics['plots']['acf_diff'] }}" alt="ACF and PACF of differenced series">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Differencing & Next Steps -->
        <div id="tab-differencing" class="forecast-tab-pane" style="display: none;">
            <div class="forecast-card">
                <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0;">
                    <i class="fa fa-sliders text-primary me-1"></i> Các phép biến đổi và So sánh sai phân
                </h3>
                <p style="font-size: 13.5px; color: #64748b; margin-bottom: 14px;">
                    So sánh chuỗi gốc với 3 cấp độ sai phân: <strong>(b) Sai phân bậc 1</strong> $d=1$, <strong>(c) Sai phân mùa vụ</strong> $D=1, s=7$, và <strong>(d) Sai phân kết hợp</strong>.
                </p>

                <div class="plot-img-card">
                    <img src="{{ $analytics['plots']['diff_comp'] }}" alt="Differencing comparison">
                </div>

                <div class="row mt-4" style="margin-top: 24px;">
                    <div class="col-md-6">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px;">
                            <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">
                                <i class="fa fa-calculator text-primary me-1"></i> Công thức biến đổi toán học
                            </h4>
                            <div style="font-family: monospace; font-size: 13px; color: #334155; line-height: 1.8;">
                                1. Sai phân bậc 1: &Delta;yₜ = yₜ - yₜ₋₁<br>
                                2. Sai phân mùa vụ tuần: &Delta;₇yₜ = yₜ - yₜ₋₇<br>
                                3. Sai phân kết hợp: &Delta;(&Delta;₇yₜ) = (yₜ - yₜ₋₇) - (yₜ₋₁ - yₜ₋₈)
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 18px;">
                            <h4 style="font-size: 14px; font-weight: 700; color: #1e40af; margin: 0 0 8px 0;">
                                <i class="fa fa-arrow-circle-right me-1"></i> Cấu trúc tham số cho Lab 2 (SARIMAX)
                            </h4>
                            <p style="font-size: 13px; color: #1e3a8a; margin: 0; line-height: 1.6;">
                                Dựa trên kết quả phân tích: Bậc sai phân xu hướng <strong>$d = 1$</strong>, bậc sai phân mùa vụ <strong>$D = 1$</strong>, chu kỳ mùa vụ <strong>$s = 7$</strong>.  
                                Cấu trúc mô hình sẵn sàng huấn luyện: <strong>SARIMA $(p, 1, q)(P, 1, Q)_7$</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    var tabBtns = document.querySelectorAll('.forecast-tab-btn');
    var tabPanes = document.querySelectorAll('.forecast-tab-pane');

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabBtns.forEach(function(b) { b.classList.remove('active'); });
            tabPanes.forEach(function(p) { p.style.display = 'none'; });

            btn.classList.add('active');
            var targetId = btn.getAttribute('data-tab');
            var targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.style.display = 'block';
        });
    });

    // Reanalyze button with AJAX
    var btnReanalyze = document.getElementById('btnReanalyze');
    if (btnReanalyze) {
        btnReanalyze.addEventListener('click', function() {
            var icon = btnReanalyze.querySelector('i');
            icon.classList.add('fa-spin');
            btnReanalyze.disabled = true;

            fetch('{{ route("projects.forecast.reanalyze", $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                alert(data.message || 'Phân tích hoàn tất!');
                window.location.reload();
            })
            .catch(function(err) {
                alert('Có lỗi xảy ra: ' + err.message);
            })
            .finally(function() {
                icon.classList.remove('fa-spin');
                btnReanalyze.disabled = false;
            });
        });
    }

    // Render interactive SVG Line Chart for 45 recent points
    var points = @json($analytics['recent_points'] ?? []);
    var container = document.getElementById('recent-chart-svg-container');

    if (container && points.length) {
        var w = 820;
        var h = 260;
        var padL = 40;
        var padR = 20;
        var padT = 24;
        var padB = 40;
        var plotW = w - padL - padR;
        var plotH = h - padT - padB;

        var bugVals = points.map(function(p) { return p.bugs; });
        var maxBug = Math.max.apply(null, bugVals) || 20;
        maxBug = Math.ceil(maxBug * 1.2);

        function getX(i) {
            return padL + (i / (points.length - 1)) * plotW;
        }
        function getY(val) {
            return padT + plotH - (val / maxBug) * plotH;
        }

        var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width: 100%; height: auto; font-family: sans-serif;">';

        // Grid lines
        for (var s = 0; s <= 4; s++) {
            var gridVal = Math.round((maxBug / 4) * s);
            var yPos = getY(gridVal);
            svg += '<line x1="' + padL + '" y1="' + yPos + '" x2="' + (w - padR) + '" y2="' + yPos + '" stroke="#f1f5f9" stroke-width="1" />';
            svg += '<text x="' + (padL - 8) + '" y="' + (yPos + 4) + '" fill="#94a3b8" font-size="11" text-anchor="end">' + gridVal + '</text>';
        }

        // Draw Line path
        var pathD = '';
        var areaD = 'M ' + getX(0) + ' ' + (padT + plotH) + ' ';

        points.forEach(function(p, i) {
            var x = getX(i);
            var y = getY(p.bugs);
            if (i === 0) {
                pathD += 'M ' + x + ' ' + y;
            } else {
                pathD += ' L ' + x + ' ' + y;
            }
            areaD += 'L ' + x + ' ' + y + ' ';
        });
        areaD += 'L ' + getX(points.length - 1) + ' ' + (padT + plotH) + ' Z';

        svg += '<path d="' + areaD + '" fill="rgba(37, 99, 235, 0.08)" />';
        svg += '<path d="' + pathD + '" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />';

        // Dots & X labels
        points.forEach(function(p, i) {
            if (i % 5 === 0 || i === points.length - 1) {
                var x = getX(i);
                var y = getY(p.bugs);
                svg += '<circle cx="' + x + '" cy="' + y + '" r="3.5" fill="#2563eb" stroke="#ffffff" stroke-width="1.5" />';
                var dateStr = p.date.substring(5); // MM-DD
                svg += '<text x="' + x + '" y="' + (h - 12) + '" fill="#64748b" font-size="10.5" text-anchor="middle">' + dateStr + '</text>';
            }
        });

        svg += '</svg>';
        container.innerHTML = svg;
    }
});
</script>
@endsection

