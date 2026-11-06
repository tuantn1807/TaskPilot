# 🧪 LAB 1: EDA, PHÂN RÃ CHUỖI THỜI GIAN & KIỂM ĐỊNH TÍNH DỪNG (STATIONARITY)

> **Dự án**: TaskPilot (Hệ thống quản lý dự án & điều phối công việc)  
> **Dữ liệu thực nghiệm**: `daily_bug_reports.csv` (1.095 ngày quan sát từ 01/01/2021 đến 31/12/2023)  
> **Thư viện sử dụng**: `pandas`, `numpy`, `statsmodels`, `matplotlib`, `seaborn`, `scipy`

---

## 📌 1. Mục tiêu và Ý nghĩa nghiệp vụ

Trong các dự án phần mềm (`TaskPilot`), số lượng **Bug / Task phát sinh hàng ngày** là chuỗi thời gian có các đặc trưng rất rõ rệt:
1. **Tính mùa vụ theo tuần ($s = 7$ ngày)**: Đỉnh điểm phát sinh lỗi vào đầu tuần (Thứ Hai, Thứ Ba khi đội ngũ họp Sprint Planning / Release mới), giảm dần về cuối tuần và chạm đáy vào Thứ Bảy, Chủ Nhật.
2. **Xu hướng dài hạn (Trend)**: Tăng khi dự án bước vào giai đoạn mở rộng tính năng và giảm khi bước vào giai đoạn ổn định (Code Freeze / Maintenance).
3. **Đột biến ngẫu nhiên (Spikes / Noise)**: Các đợt tăng vọt bất thường vào ngày Release hoặc Demo với khách hàng.

**Mục tiêu cốt lõi của Lab 1**: Phân tích cấu trúc chuỗi, kiểm định tính dừng toán học (**ADF & KPSS**) và thực hiện sai phân làm dừng chuỗi để làm tiền đề đưa vào các mô hình dự báo thống kê (SARIMAX ở Lab 2), Machine Learning (LightGBM ở Lab 3) và Deep Learning (Transformers ở Lab 5).

---

## 📂 2. Cấu trúc thư mục Lab 1

```text
labs/lab1_taskpilot_eda/
├── daily_bug_reports.csv           # Dữ liệu chuỗi thời gian số bug theo ngày
├── lab1_eda_stationarity.py        # Script Python thực thi toàn bộ pipeline tự động
├── lab1_eda_stationarity.ipynb     # Jupyter Notebook tương tác trực quan
├── README.md                       # Tài liệu hướng dẫn và phân tích chi tiết
└── plots/                          # Thư mục chứa các biểu đồ kết quả
    ├── 01_timeseries_overview.png           # Tổng quan chuỗi & đường MA(7), MA(30)
    ├── 02_seasonality_by_day.png           # Phân phối và trung bình bug theo thứ trong tuần
    ├── 03_additive_decomposition.png       # Phân rã mô hình cộng: Y = T + S + R
    ├── 04_multiplicative_decomposition.png # Phân rã mô hình nhân: Y = T * S * R
    ├── 05_acf_pacf_original.png            # ACF & PACF của chuỗi gốc (đỉnh chu kỳ tại lag 7, 14, 21...)
    ├── 06_differencing_comparison.png      # So sánh chuỗi gốc vs Diff(1) vs Diff(7) vs Diff(7)+Diff(1)
    └── 07_acf_pacf_differenced.png         # ACF & PACF sau khi làm dừng (Stationary)
```

---

## 🚀 3. Hướng dẫn chạy bài Lab

### Cách 1: Chạy tự động bằng Python Script (Khuyên dùng)
Kích hoạt môi trường ảo của dự án và chạy script:
```bash
# Kích hoạt virtualenv
source /home/tuantn1807/project/TaskPilot/.venv/bin/activate

# Chạy script phân tích
python labs/lab1_taskpilot_eda/lab1_eda_stationarity.py
```
*Toàn bộ kết quả thống kê sẽ in ra console và 7 biểu đồ phân tích được lưu tự động trong thư mục `plots/`.*

### Cách 2: Mở và chạy trên Jupyter Notebook
Nếu bạn dùng VS Code hoặc JupyterLab:
1. Mở file [`labs/lab1_taskpilot_eda/lab1_eda_stationarity.ipynb`](file:///home/tuantn1807/project/TaskPilot/labs/lab1_taskpilot_eda/lab1_eda_stationarity.ipynb).
2. Chọn Kernel Python là `.venv` (`/home/tuantn1807/project/TaskPilot/.venv/bin/python`).
3. Nhấn **Run All Cells** để trải nghiệm giao diện tương tác từng bước.

---

## 📐 4. Chi tiết Kỹ thuật & Công thức Toán học

### 4.1. Phân rã chuỗi thời gian (Seasonal Decomposition)
Chu kỳ mùa vụ xác định là **$s = 7$ ngày**.

* **Mô hình Cộng (Additive)**:
  $$y_t = T_t + S_t + R_t$$
  Áp dụng khi biên độ biến động mùa vụ không thay đổi khi xu hướng tăng lên.
* **Mô hình Nhân (Multiplicative)**:
  $$y_t = T_t \times S_t \times R_t$$
  Áp dụng khi độ dao động mùa vụ tỷ lệ thuận với độ lớn của Trend.

### 4.2. Kiểm định tính dừng: ADF vs KPSS

Một chuỗi thời gian được gọi là dừng (Weakly Stationary) khi thỏa mãn 3 điều kiện:
1. Kỳ vọng không đổi theo thời gian: $\mathbb{E}[y_t] = \mu$
2. Phương sai không đổi theo thời gian: $\text{Var}(y_t) = \sigma^2$
3. Tự hiệp phương sai chỉ phụ thuộc vào độ trễ $k$: $\text{Cov}(y_t, y_{t-k}) = \gamma_k$

#### Ma trận quyết định phối hợp ADF và KPSS:
| Trường hợp | ADF ($H_0$: Có Unit Root / Không dừng) | KPSS ($H_0$: Dừng quanh mức nền) | Kết luận toán học | Hành động tiếp theo |
| :---: | :---: | :---: | :---: | :--- |
| **Case 1** | Bác bỏ $H_0$ ($p < 0.05$) | Không bác bỏ $H_0$ ($p > 0.05$) | **Stationary** (Dừng hoàn toàn) | Sẵn sàng đưa vào mô hình ARMA |
| **Case 2** | Không bác bỏ ($p \ge 0.05$) | Bác bỏ $H_0$ ($p \le 0.05$) | **Non-Stationary** (Không dừng) | Bắt buộc lấy sai phân bậc 1 |
| **Case 3** | Bác bỏ $H_0$ ($p < 0.05$) | Bác bỏ $H_0$ ($p \le 0.05$) | **Difference Stationary** | Dừng một phần nhưng còn mùa vụ tuần, cần sai phân mùa vụ $s=7$ |
| **Case 4** | Không bác bỏ ($p \ge 0.05$) | Không bác bỏ ($p > 0.05$) | **Trend Stationary** | Cần trừ đi xu hướng tuyến tính |

---

## 📊 5. Kết quả thực nghiệm trên dữ liệu TaskPilot

### 5.1. Kết quả kiểm định trên Chuỗi gốc:
- **ADF Statistic**: `-3.1555` ($p = 0.0227 < 0.05 \implies$ Bác bỏ $H_0$)
- **KPSS Statistic**: `3.4793` ($p = 0.0100 \le 0.05 \implies$ Bác bỏ $H_0$)
- **Kết luận**: Rơi vào **Case 3 (Difference Stationary)** do chuỗi có thành phần chu kỳ tuần lặp lại rất mạnh khiến phân phối hiệp phương sai dài hạn chưa dừng hoàn hảo.

### 5.2. Biến đổi và Sai phân kết hợp:
Áp dụng sai phân mùa vụ kết hợp sai phân bậc nhất:
$$\Delta(\Delta_7 y_t) = (y_t - y_{t-7}) - (y_{t-1} - y_{t-8})$$

### 5.3. Kết quả kiểm định sau khi sai phân:
- **ADF Statistic**: `-13.3910` ($p = 4.77 \times 10^{-25} \ll 0.001 \implies$ Bác bỏ $H_0$)
- **KPSS Statistic**: `0.0809 < 0.347` ($p = 0.1000 > 0.05 \implies$ Không thể bác bỏ $H_0$)
- **Kết luận cuối cùng**: **CHUỖI HOÀN TOÀN DỪNG (STATIONARY)**!

---

## 🎯 6. Ý nghĩa đối với Lab 2 (Dự báo SARIMAX)

Với việc xác định được chuỗi cần **sai phân bậc nhất $d = 1$** và **sai phân mùa vụ $D = 1$ với chu kỳ $s = 7$**, ta đã xác định được cấu trúc cốt lõi cho mô hình **SARIMA $(p, 1, q)(P, 1, Q)_7$** ở Lab 2:
- Bậc $p, q$ sẽ được tinh chỉnh dựa trên các độ trễ đầu tiên của biểu đồ PACF và ACF (trong `07_acf_pacf_differenced.png`).
- Kết hợp các biến ngoại sinh (ngày Release, Sprint Review) để xây dựng mô hình dự báo số lượng bug bùng phát trước ngày bàn giao sản phẩm.

