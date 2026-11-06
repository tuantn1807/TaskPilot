#!/usr/bin/env python3
"""
LAB 1: EDA, PHÂN RÃ DỮ LIỆU & KIỂM ĐỊNH TÍNH DỪNG (STATIONARITY)
Dự án: TaskPilot (Chuỗi thời gian số lượng Bug / Task phát sinh hàng ngày)
"""

import os
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns

from statsmodels.tsa.seasonal import seasonal_decompose
from statsmodels.tsa.stattools import adfuller, kpss
from statsmodels.graphics.tsaplots import plot_acf, plot_pacf
from scipy.stats import boxcox

# Thiết lập style đồ thị
plt.style.use('seaborn-v0_8-whitegrid' if 'seaborn-v0_8-whitegrid' in plt.style.available else 'default')
plt.rcParams['font.sans-serif'] = 'DejaVu Sans'
plt.rcParams['axes.edgecolor'] = '#cbd5e1'
plt.rcParams['axes.linewidth'] = 0.8

PLOTS_DIR = os.path.join(os.path.dirname(__file__), 'plots')
DATA_PATH = os.path.join(os.path.dirname(__file__), 'daily_bug_reports.csv')
os.makedirs(PLOTS_DIR, exist_ok=True)

def load_data():
    print("=" * 70)
    print("BƯỚC 1: TẢI VÀ TIỀN XỬ LÝ CHUỖI THỜI GIAN TASKPILOT")
    print("=" * 70)
    
    df = pd.read_csv(DATA_PATH, parse_dates=['date'], index_col='date')
    print(f"Tổng số ngày quan sát: {len(df)} ngày ({df.index.min().strftime('%d/%m/%Y')} -> {df.index.max().strftime('%d/%m/%Y')})")
    print("\nThống kê mô tả số lượng Bug phát sinh hàng ngày (bug_count):")
    print(df['bug_count'].describe().to_string())
    
    # 1. Vẽ chuỗi thời gian tổng quan kèm đường trung bình trượt
    fig, ax = plt.subplots(figsize=(14, 5))
    ax.plot(df.index, df['bug_count'], label='Số Bug hàng ngày (Raw)', color='#94a3b8', alpha=0.6, linewidth=1)
    ax.plot(df.index, df['bug_count'].rolling(window=7).mean(), label='Trung bình trượt 7 ngày (7-day MA)', color='#2563eb', linewidth=2)
    ax.plot(df.index, df['bug_count'].rolling(window=30).mean(), label='Trung bình trượt 30 ngày (30-day MA)', color='#dc2626', linewidth=2)
    ax.set_title('Chuỗi thời gian Bug phát sinh hàng ngày tại TaskPilot (2021 - 2023)', fontsize=14, fontweight='bold', pad=12)
    ax.set_xlabel('Thời gian', fontsize=11)
    ax.set_ylabel('Số lượng Bug', fontsize=11)
    ax.legend(frameon=True, facecolor='white', framealpha=0.9)
    plt.tight_layout()
    plt.savefig(os.path.join(PLOTS_DIR, '01_timeseries_overview.png'), dpi=200)
    plt.close()
    print("  [+] Đã lưu biểu đồ: 01_timeseries_overview.png")

    # 2. Phân tích tính mùa vụ theo các ngày trong tuần (Day of Week Analysis)
    fig, axes = plt.subplots(1, 2, figsize=(14, 5))
    day_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
    
    sns.boxplot(x='day_name', y='bug_count', data=df, order=day_order, ax=axes[0], palette='Blues_r')
    axes[0].set_title('Phân phối số lượng Bug theo ngày trong tuần', fontsize=12, fontweight='bold')
    axes[0].set_xlabel('Thứ trong tuần')
    axes[0].set_ylabel('Số Bug')
    axes[0].tick_params(axis='x', rotation=30)

    day_mean = df.groupby('day_name')['bug_count'].mean().reindex(day_order)
    axes[1].bar(day_order, day_mean, color=['#2563eb' if d not in ['Saturday', 'Sunday'] else '#f87171' for d in day_order], edgecolor='#1e293b', alpha=0.85)
    axes[1].set_title('Trung bình số Bug phát sinh theo ngày trong tuần', fontsize=12, fontweight='bold')
    axes[1].set_xlabel('Thứ trong tuần')
    axes[1].set_ylabel('Bug trung bình')
    axes[1].tick_params(axis='x', rotation=30)
    for i, v in enumerate(day_mean):
        axes[1].text(i, v + 0.3, f"{v:.1f}", ha='center', fontweight='bold', fontsize=10)

    plt.tight_layout()
    plt.savefig(os.path.join(PLOTS_DIR, '02_seasonality_by_day.png'), dpi=200)
    plt.close()
    print("  [+] Đã lưu biểu đồ: 02_seasonality_by_day.png")
    
    return df

def decompose_series(series):
    print("\n" + "=" * 70)
    print("BƯỚC 2: PHÂN RÃ CHUỖI THỜI GIAN (SEASONAL DECOMPOSITION - s=7 ngày)")
    print("=" * 70)
    
    # 1. Additive Model: Y(t) = Trend(t) + Seasonality(t) + Residual(t)
    decomp_add = seasonal_decompose(series, model='additive', period=7)
    
    fig, axes = plt.subplots(4, 1, figsize=(14, 9), sharex=True)
    decomp_add.observed.plot(ax=axes[0], color='#0f172a', title='Observed (Thực tế)')
    decomp_add.trend.plot(ax=axes[1], color='#2563eb', title='Trend (Xu hướng)')
    decomp_add.seasonal.plot(ax=axes[2], color='#16a34a', title='Seasonality (Mùa vụ s=7 ngày)')
    decomp_add.resid.plot(ax=axes[3], color='#dc2626', style='.', title='Residual (Phần dư ngẫu nhiên)')
    
    for ax in axes:
        ax.grid(True, linestyle='--', alpha=0.5)
        ax.set_ylabel('')
    fig.suptitle('Phân rã chuỗi theo mô hình Cộng (Additive Decomposition): Y = T + S + R', fontsize=14, fontweight='bold')
    plt.tight_layout()
    plt.savefig(os.path.join(PLOTS_DIR, '03_additive_decomposition.png'), dpi=200)
    plt.close()
    print("  [+] Đã lưu biểu đồ: 03_additive_decomposition.png")

    # 2. Multiplicative Model: Y(t) = Trend(t) * Seasonality(t) * Residual(t)
    # Vì có giá trị 0 nên cộng epsilon nhỏ để thực hiện phép nhân
    series_pos = series + 0.1
    decomp_mul = seasonal_decompose(series_pos, model='multiplicative', period=7)
    
    fig, axes = plt.subplots(4, 1, figsize=(14, 9), sharex=True)
    decomp_mul.observed.plot(ax=axes[0], color='#0f172a', title='Observed (Thực tế)')
    decomp_mul.trend.plot(ax=axes[1], color='#2563eb', title='Trend (Xu hướng)')
    decomp_mul.seasonal.plot(ax=axes[2], color='#16a34a', title='Seasonality (Hệ số mùa vụ s=7 ngày)')
    decomp_mul.resid.plot(ax=axes[3], color='#dc2626', style='.', title='Residual (Nhiễu)')
    
    for ax in axes:
        ax.grid(True, linestyle='--', alpha=0.5)
        ax.set_ylabel('')
    fig.suptitle('Phân rã chuỗi theo mô hình Nhân (Multiplicative Decomposition): Y = T * S * R', fontsize=14, fontweight='bold')
    plt.tight_layout()
    plt.savefig(os.path.join(PLOTS_DIR, '04_multiplicative_decomposition.png'), dpi=200)
    plt.close()
    print("  [+] Đã lưu biểu đồ: 04_multiplicative_decomposition.png")
    
    return decomp_add

def analyze_autocorrelation(series, title_suffix="Chuỗi gốc"):
    print("\n" + "=" * 70)
    print(f"BƯỚC 3: PHÂN TÍCH TƯƠNG QUAN (ACF & PACF) - {title_suffix}")
    print("=" * 70)
    
    fig, axes = plt.subplots(1, 2, figsize=(15, 5))
    plot_acf(series.dropna(), lags=35, ax=axes[0], title=f'Autocorrelation (ACF) - {title_suffix}', color='#2563eb')
    plot_pacf(series.dropna(), lags=35, ax=axes[1], title=f'Partial Autocorrelation (PACF) - {title_suffix}', color='#16a34a', method='ywm')
    
    for ax in axes:
        ax.grid(True, linestyle='--', alpha=0.5)
        # Đánh dấu các mốc bội số tuần 7, 14, 21, 28
        for lag in [7, 14, 21, 28]:
            ax.axvline(x=lag, color='#f59e0b', linestyle=':', alpha=0.6)
            
    plt.tight_layout()
    plot_name = '05_acf_pacf_original.png' if 'gốc' in title_suffix else '07_acf_pacf_differenced.png'
    plt.savefig(os.path.join(PLOTS_DIR, plot_name), dpi=200)
    plt.close()
    print(f"  [+] Đã lưu biểu đồ: {plot_name}")

def stationarity_tests(series, series_name="Chuỗi gốc"):
    print("\n" + "-" * 70)
    print(f"KIỂM ĐỊNH TÍNH DỪNG CHO: {series_name.upper()}")
    print("-" * 70)
    clean_series = series.dropna()

    # 1. ADF Test (Augmented Dickey-Fuller)
    # H0: Chuỗi có unit root (Không dừng)
    # H1: Chuỗi không có unit root (Dừng)
    adf_result = adfuller(clean_series, autolag='AIC')
    adf_stat, adf_pvalue, adf_crit = adf_result[0], adf_result[1], adf_result[4]
    
    print(f"1. Augmented Dickey-Fuller (ADF) Test:")
    print(f"   - ADF Statistic : {adf_stat:.4f}")
    print(f"   - p-value       : {adf_pvalue:.4e}")
    print(f"   - Critical Values: 1%: {adf_crit['1%']:.3f}, 5%: {adf_crit['5%']:.3f}, 10%: {adf_crit['10%']:.3f}")
    adf_is_stationary = adf_pvalue < 0.05
    print(f"   => Kết luận ADF: {'Bác bỏ H0 -> CHUỖI DỪNG (p < 0.05)' if adf_is_stationary else 'Chưa đủ cơ sở bác bỏ H0 -> CHUỖI CHƯA DỪNG (p >= 0.05)'}")

    # 2. KPSS Test (Kwiatkowski-Phillips-Schmidt-Shin)
    # H0: Chuỗi dừng quanh xu hướng / mức nền
    # H1: Chuỗi có unit root (Không dừng)
    kpss_result = kpss(clean_series, regression='c', nlags='auto')
    kpss_stat, kpss_pvalue, kpss_crit = kpss_result[0], kpss_result[1], kpss_result[3]
    
    print(f"\n2. KPSS (Kwiatkowski-Phillips-Schmidt-Shin) Test:")
    print(f"   - KPSS Statistic: {kpss_stat:.4f}")
    print(f"   - p-value       : {kpss_pvalue:.4f}")
    print(f"   - Critical Values: 1%: {kpss_crit['1%']:.3f}, 5%: {kpss_crit['5%']:.3f}, 10%: {kpss_crit['10%']:.3f}")
    kpss_is_stationary = kpss_pvalue > 0.05
    print(f"   => Kết luận KPSS: {'Không thể bác bỏ H0 -> CHUỖI DỪNG (p > 0.05)' if kpss_is_stationary else 'Bác bỏ H0 -> CHUỖI CHƯA DỪNG (p <= 0.05)'}")

    # 3. Kết luận tổng hợp
    print(f"\n=> KẾT LUẬN TỔNG HỢP (ADF + KPSS):")
    if adf_is_stationary and kpss_is_stationary:
        verdict = "STATIONARY (Chuỗi hoàn toàn dừng)"
    elif not adf_is_stationary and not kpss_is_stationary:
        verdict = "NON-STATIONARY (Chuỗi không dừng - Có Unit Root rõ rệt)"
    elif adf_is_stationary and not kpss_is_stationary:
        verdict = "DIFFERENCE STATIONARY (Cần sai phân thêm để khử yếu tố chu kỳ / xu hướng còn sót lại)"
    else:
        verdict = "TREND STATIONARY (Dừng quanh xu hướng nhưng cần loại bỏ trend deterministic)"
    print(f"   >>> {verdict} <<<")
    
    return {
        'adf_stat': adf_stat, 'adf_p': adf_pvalue, 'adf_stationary': adf_is_stationary,
        'kpss_stat': kpss_stat, 'kpss_p': kpss_pvalue, 'kpss_stationary': kpss_is_stationary,
        'verdict': verdict
    }

def apply_transformations(series):
    print("\n" + "=" * 70)
    print("BƯỚC 4: CÁC PHÉP BIẾN ĐỔI VÀ SAI PHÂN LÀM DỪNG CHUỖI")
    print("=" * 70)

    # 1. Log transform: ổn định phương sai
    series_log = np.log1p(series)

    # 2. Sai phân bậc 1: d=1 (y_t - y_{t-1}) khử xu hướng
    series_diff1 = series.diff(1)

    # 3. Sai phân mùa vụ tuần: s=7 (y_t - y_{t-7}) khử tính chu kỳ tuần
    series_seasonal_diff = series.diff(7)

    # 4. Sai phân kết hợp bậc 1 + mùa vụ tuần: (y_t - y_{t-7}) - (y_{t-1} - y_{t-8})
    series_both_diff = series.diff(7).diff(1)

    # Vẽ biểu đồ so sánh 4 chuỗi
    fig, axes = plt.subplots(4, 1, figsize=(14, 10), sharex=True)
    series.plot(ax=axes[0], color='#0f172a', title='(a) Chuỗi gốc (Original Series)')
    series_diff1.plot(ax=axes[1], color='#2563eb', title='(b) Sai phân bậc 1: Diff(1)')
    series_seasonal_diff.plot(ax=axes[2], color='#16a34a', title='(c) Sai phân mùa vụ: Seasonal Diff(7)')
    series_both_diff.plot(ax=axes[3], color='#9333ea', title='(d) Sai phân kết hợp: Diff(7) rồi Diff(1) - [SARIMA Ready]')

    for ax in axes:
        ax.grid(True, linestyle='--', alpha=0.5)
        ax.axhline(0, color='#64748b', linestyle=':', alpha=0.7)
    plt.tight_layout()
    plt.savefig(os.path.join(PLOTS_DIR, '06_differencing_comparison.png'), dpi=200)
    plt.close()
    print("  [+] Đã lưu biểu đồ: 06_differencing_comparison.png")

    return series_diff1, series_seasonal_diff, series_both_diff

def main():
    # 1. Load data
    df = load_data()
    series = df['bug_count']

    # 2. Phân rã chuỗi
    decompose_series(series)

    # 3. ACF / PACF chuỗi gốc
    analyze_autocorrelation(series, title_suffix="Chuỗi gốc")

    # 4. Kiểm định tính dừng chuỗi gốc
    res_orig = stationarity_tests(series, series_name="Chuỗi số lượng Bug gốc")

    # 5. Biến đổi & Sai phân
    diff1, diff7, diff_both = apply_transformations(series)

    # 6. Kiểm định lại sau sai phân kết hợp
    res_diff = stationarity_tests(diff_both, series_name="Chuỗi sau sai phân kết hợp Diff(7) + Diff(1)")

    # 7. ACF / PACF chuỗi đã làm dừng
    analyze_autocorrelation(diff_both, title_suffix="Sau sai phân Diff(7) + Diff(1)")

    print("\n" + "=" * 70)
    print("HOÀN THÀNH LAB 1 THỰC HÀNH CHUỖI THỜI GIAN!")
    print(f"Tất cả biểu đồ đã được lưu trong: {PLOTS_DIR}/")
    print("=" * 70)

if __name__ == '__main__':
    main()

