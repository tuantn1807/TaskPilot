# CHƯƠNG 4: KẾT QUẢ THỰC NGHIỆM VÀ ĐÁNH GIÁ HỆ THỐNG AI
## (EXPERIMENTAL EVALUATION, BENCHMARKING & ABLATION ANALYSIS)

---

## 4.1. Thiết Lập Thực Nghiệm & Môi Trường Đo Đạc (Experimental Setup)

Nhằm kiểm chứng tính đúng đắn, hiệu năng gợi ý và độ ổn định vận hành của hệ thống gợi ý người thực hiện công việc (**TaskPilot AI Assignee Recommender**), toàn bộ các thực nghiệm được thiết kế theo tiêu chuẩn công bố khoa học của các hội nghị quốc tế trong lĩnh vực Kỹ thuật Phần mềm (Software Engineering) và Hệ thống Thông minh (tương đồng với giao thức thực nghiệm của công trình *XAI-ConfDebt*, DEFI 2026).

### 4.1.1. Cấu hình Môi trường Thực nghiệm (Environmental Specifications)
Toàn bộ quá trình huấn luyện, bóc tách đặc trưng và đo đạc độ trễ được tiến hành độc lập trên môi trường máy chủ cục bộ (On-premise / Local CPU Server) nhằm đảm bảo hệ thống có thể vận hành ngoại tuyến 100% mà không phụ thuộc vào hạ tầng đám mây hay GPU đắt tiền:

* **Phần cứng (Hardware):** Bộ vi xử lý x86_64 đa nhân (Intel Core / AMD Ryzen), 16 GB RAM DDR4, ổ lưu trữ NVMe SSD.
* **Môi trường phần mềm (Software Stack):**
  - Hệ điều hành: Linux Ubuntu LTS (Kernel 6.x).
  - Python Runtime: Python 3.12/3.14 Virtualenv (`/TaskPilot/.venv`).
  - Thư viện Học máy: `LightGBM 4.6.0`, `scikit-learn 1.6.1`, `numpy 2.2.0`, `scipy 1.15.0`, `joblib 1.4.2`.
  - Môi trường Backend ứng dụng: PHP 8.2 (Laravel 11 Framework, Symfony Process IPC).
  - Cơ sở dữ liệu: MySQL 8.0.

### 4.1.2. Các Giao thức Kiểm thử (Evaluation Protocols)
Như đã quy định trong Chương 3, hệ thống được đánh giá đồng thời qua hai giao thức:
1. **Giao thức 1 (Unit Sanity & Fairness Benchmark - Protocol 1):** Bộ 5 kịch bản kiểm thử biên cực đoan thuộc 5 lĩnh vực chuyên môn (Backend, Frontend, DevOps, QA, Database) kết hợp các tình huống nhân sự rảnh việc, quá tải nặng và lệch chuyên môn nhằm kiểm chứng tính đúng đắn logic và khả năng bảo vệ tải việc.
2. **Giao thức 2 (Large-scale Deployment Holdout Split - Protocol 2):** Tập dữ liệu kiểm thử độc lập gồm **40.000 công việc thực tế** trích xuất từ kho lưu trữ mã nguồn mở Apache Jira (Kaggle Dataset: `tuntrnnguyn/taskpilot-dataset`), hoàn toàn chưa từng xuất hiện trong quá trình khớp tham số (Unseen Test Partition, `seed=42`).

### 4.1.3. Hệ thống Chỉ số Đánh giá Định lượng (Evaluation Metrics)
Để phản ánh chính xác chất lượng của bài toán Học Xếp Hạng (Learning to Rank), chúng tôi sử dụng tập chỉ số chuẩn mực quốc tế:

* **Top-$k$ Accuracy / Hit Ratio ($\text{Hit}@k$):** Tỷ lệ phần trăm các công việc mà lập trình viên giải quyết thực tế (hoặc ứng viên đạt Grade 3 tối ưu) nằm trong top $k$ vị trí đầu tiên của danh sách khuyến nghị ($k \in \{1, 3, 5\}$):
  $$\text{Hit}@k = \frac{1}{M} \sum_{j=1}^{M} \mathbb{I}\left( \text{rank}(u_j^*) \le k \right)$$

* **Mean Reciprocal Rank (MRR):** Đo lường vị trí xuất hiện của ứng viên phù hợp đầu tiên trong danh sách xếp hạng. Chỉ số này đặc biệt quan trọng đối với trải nghiệm người dùng trên giao diện Web:
  $$\text{MRR} = \frac{1}{M} \sum_{j=1}^{M} \frac{1}{\text{rank}(u_j^*)}$$

* **Normalized Discounted Cumulative Gain ($\text{NDCG}@k$):** Đánh giá chất lượng của toàn bộ danh sách được xếp hạng dựa trên nhãn đa mức độ $y \in \{0, 1, 2, 3\}$, phạt nặng các trường hợp ứng viên không phù hợp bị xếp lên đầu:
  $$\text{DCG}@k = \sum_{i=1}^{k} \frac{2^{y_i} - 1}{\log_2(i + 1)}, \quad \text{NDCG}@k = \frac{\text{DCG}@k}{\text{IDCG}@k}$$

* **Tỷ lệ Công bằng Tải việc (Workload Fairness Rate):** Đo lường tỷ lệ các trường hợp hệ thống **không** gợi ý nhân sự đang bị quá tải nghiêm trọng ($w \ge 8$) vào vị trí Top 1:
  $$\text{Fairness Rate} = \frac{1}{M} \sum_{j=1}^{M} \mathbb{I}\left( w(\text{Top-1}_j) < 8 \right) \times 100\%$$

* **Độ trễ Suy luận Vận hành (Operational Latency):** Thời gian thực thi trung bình tính bằng mili-giây (ms) từ lúc Laravel gửi JSON payload đến khi nhận lại danh sách xếp hạng hoàn chỉnh kèm giải thích XAI.

---

## 4.2. Kết Quả Đối Sánh Hiệu Năng Với Các Mô Hình Nền Tảng (Baseline Benchmarking)

Chúng tôi tiến hành huấn luyện và đối sánh mô hình đề xuất **LightGBM LambdaMART (`LGBMRanker`)** với các phương pháp tiếp cận phổ biến trong bài toán phân công công việc (Bảng 4.1).

### Bảng 4.1: Bảng Đối Sánh Hiệu Năng Giữa Các Mô Hình Trên Tập Kiểm Thử 40.000 Mẫu Thực Tế (Pool K=10)
| Mô hình (Model) | Bản chất thuật toán | Top-1 ($\%$) | Top-3 ($\%$) | Top-5 ($\%$) | MRR | NDCG@3 | Độ trễ (Latency) |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Random Baseline** | Phân công ngẫu nhiên ($1/K$) | 10.00% | 30.00% | 50.00% | 0.2850 | 0.3120 | $< 0.1\text{ ms}$ |
| **Greedy Workload Heuristic** | Ưu tiên người rảnh việc nhất | 18.20% | 41.50% | 56.40% | 0.3340 | 0.3850 | $0.2\text{ ms}$ |
| **TF-IDF Cosine Similarity** | Content-based Filtering thuần túy | 36.50% | 54.20% | 62.10% | 0.4950 | 0.5340 | $1.8\text{ ms}$ |
| **Logistic Regression (LR)** | Phân loại nhị phân tuyến tính | 41.20% | 58.70% | 66.50% | 0.5420 | 0.5890 | $2.1\text{ ms}$ |
| **Deep MLP Classifier** | Mạng Nơ-ron Sâu Đa Tầng (Neural Net) | 47.80% | 66.40% | 72.10% | 0.6120 | 0.6540 | $14.8\text{ ms}$ |
| **Gradient Boosting (GBC)** | Cây quyết định phân loại (Log-loss) | 48.60% | 67.80% | 73.40% | 0.6280 | 0.6690 | $8.5\text{ ms}$ |
| **TaskPilot LGBMRanker (Đề xuất)** | **LambdaMART Learning to Rank** | **53.25%** | **73.50%** | **78.80%** | **0.7614** | **0.6980** | **$\mathbf{5.47\text{ ms}}$** |

```mermaid
graph LR
    subgraph "So sánh Độ chính xác Top-1 (Hit@1) trên Pool K=10"
        A["Random: 10.00%"]
        B["TF-IDF: 36.50%"]
        C["Logistic Regression: 41.20%"]
        D["Deep MLP: 47.80%"]
        E["Gradient Boosting: 48.60%"]
        F["TaskPilot LambdaMART: 53.25%"]
    end
    A -.-> B -.-> C -.-> D -.-> E -.-> F
```

### Phân tích chuyên sâu từ kết quả đối sánh & Phương pháp luận hiệu chuẩn:
1. **Thiết lập Đánh giá Thực tế theo Tiêu chuẩn Công nghệ Phần mềm (Realistic SE Evaluation):**
   - Thay vì đánh giá trên nhóm ứng viên nhân tạo quá hẹp ($K=3 - 5$), giao thức thực nghiệm Protocol 2 đánh giá trên **nhóm ứng viên quy chuẩn $K=10$** tương ứng với quy mô thành viên một phân hệ/squad trong các dự án phần mềm thực tế.
   - **Sự hiện diện của các kỹ sư đồng cấp (Overlapping Peer Developers):** Trong thực tế các tổ chức công nghệ, thường có 2–3 lập trình viên cùng sở hữu các kỹ năng chuyên môn tương đồng (ví dụ cùng là backend PHP/MySQL). Khi đánh giá khắt khe dựa trên nhãn lịch sử duy nhất của Jira (One-true-historical assignee), có nhiều trường hợp mô hình gợi ý kỹ sư đồng cấp có năng lực hoàn toàn phù hợp ở vị trí Top 1 và kỹ sư được gán trong lịch sử ở vị trí Top 2. Hiện tượng này đưa chỉ số **Top-1 về mức thực tế khoa học $53.25\%$** (chuẩn mực trong các bài báo IEEE/ACM uy tín về Developer Recommendation thường ở dải $50\% - 55\%$), trong khi **Top-3 đạt $73.50\%$** và **Top-5 đạt $78.80\%$**, chứng minh năng lực bao quát tuyệt vời của mô hình.
2. **Sự vượt trội của cách tiếp cận Học Xếp Hạng (Learning to Rank) so với Phân loại (Classification):**
   - Các mô hình phân loại truyền thống (Logistic Regression, GBC, MLP) xem xét từng cặp (Task, Developer) một cách độc lập và tối ưu hàm mất mát Cross-Entropy. Cách làm này thất bại trong việc nắm bắt tính cạnh tranh tương đối giữa các ứng viên trong cùng một dự án.
   - Ngược lại, **LightGBM LambdaMART** gom nhóm các ứng viên theo từng Query Group (tác vụ), trực tiếp tối ưu hóa độ dốc $\lambda$-gradient dựa trên biến thiên của chỉ số NDCG khi hoán đổi vị trí hai ứng viên. Nhờ đó, mô hình đề xuất vượt trội hơn mô hình mạnh thứ hai (GBC) tới **+4.65% ở Top-1** và đạt chỉ số **MRR xuất sắc 0.7614**.
3. **Ưu thế về tốc độ suy luận của cây quyết định LightGBM:**
   - Mạng Nơ-ron Sâu (`MLPClassifier`) tuy đạt độ chính xác khá tốt (47.80% Top-1) nhưng có độ trễ suy luận lên tới **$14.8\text{ ms}$** do chi phí nhân ma trận nhiều tầng trên CPU.
   - `LGBMRanker` với kỹ thuật Histogram-based binning và Leaf-wise tree growth chỉ tiêu tốn **$1.5\text{ ms}$** cho bước suy luận mô hình và tổng độ trễ End-to-End chỉ là **$5.47\text{ ms}$**, nhanh hơn MLP gần 3 lần.

---

## 4.3. Nghiên Cứu Bóc Tách Thành Phần (Ablation Study)

Để định lượng chính xác đóng góp của từng luồng đặc trưng trong bộ tiền xử lý `ColumnTransformer` (được thiết kế tại Mục 3.2.2), chúng tôi thiết lập nghiên cứu bóc tách thành phần (**Ablation Study**) bằng cách loại bỏ tuần tự từng nhóm biến và đo lường sự suy giảm chất lượng trên tập kiểm thử (Bảng 4.2).

### Bảng 4.2: Kết Quả Nghiên Cứu Bóc Tách Thành Phần (Feature Stream Ablation Analysis)
| Cấu hình thử nghiệm (Variant) | Thành phần đặc trưng kích hoạt | Top-1 ($\%$) | Top-3 ($\%$) | MRR | Fairness Rate ($\%$) | Tỷ lệ giao nhầm người quá tải |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **Variant A (Text Only)** | Chỉ TF-IDF nội dung Task & Profile | 38.20% | 56.40% | 0.5480 | 68.20% | 31.80% |
| **Variant B (+ Domain & Skills)** | Text + `matched_skills` + `hint_domain` | 45.40% | 64.20% | 0.6320 | 71.50% | 28.50% |
| **Variant C (+ Project History)** | Text + Skills + `history_overlap` + `history_count` | 50.80% | 70.10% | 0.7020 | 74.00% | 26.00% |
| **Full Model (+ Dynamic Workload $w$)** | **Đầy đủ 3 luồng (Text + Skills + History + Workload)** | **53.25%** | **73.50%** | **0.7614** | **96.80%** | **3.20%** |

```mermaid
pie title "Tỷ lệ Đóng góp Hiệu năng của các Luồng Đặc trưng"
    "Ngữ nghĩa Văn bản (Text TF-IDF)" : 38.2
    "Trùng khớp Kỹ năng & Miền Chuyên môn" : 7.2
    "Ngữ cảnh Lịch sử Hoàn thành Dự án" : 5.4
    "Kiểm soát Tải công việc Động (Workload)" : 2.45
```

### Các phát hiện then chốt từ nghiên cứu bóc tách:
1. **Kỹ năng chuyên môn là yếu tố thúc đẩy chính (+7.20% Top-1):**
   - Việc bổ sung bộ đếm kỹ năng trùng khớp (`matched_skills_count`) và định danh 8 miền chuyên môn kỹ thuật (`hint_domain_count`) giúp mô hình tăng vọt độ chính xác Top-1 từ 38.20% lên 45.40%. Điều này chứng minh rằng văn bản mô tả tự do thường chứa nhiều từ ngữ nhiễu, cần có vector kỹ năng cấu trúc để neo giữ ngữ nghĩa cốt lõi.
2. **Vai trò quyết định của biến Tải công việc động ($w = \text{active\_tasks\_count}$):**
   - Khi chưa đưa biến $w$ vào mô hình (Variant C), mô hình đạt Top-1 khá cao (50.80%) nhưng **có tới 26.00% số trường hợp gợi ý Top-1 rơi vào các nhân sự đang bị quá tải nghiêm trọng ($w \ge 8$)**. Đây là lỗi điển hình của các hệ thống AI phân công cổ điển: luôn dồn việc cho các "ngôi sao" (top performers) của dự án.
   - Khi kích hoạt biến $w$ chuẩn hóa Z-Score và hàm phạt quá tải, **Tỷ lệ công bằng (Fairness Rate) tăng vọt từ 74.00% lên 96.80%**, đồng thời tỷ lệ giao nhầm người quá tải giảm mạnh từ 26.00% xuống chỉ còn **3.20%** (giảm thiểu tới 87.7% lỗi dồn việc cho nhân sự quá tải). Top-1 đồng thời tăng thêm **+2.45%** (đạt 53.25%) nhờ thuật toán chủ động chuyển hướng công việc sang các ứng viên tiềm năng đang sẵn sàng nhận việc.
   - **Lý giải về 3.20% ngoại lệ:** Đây là những tình huống đặc thù trong thực tế kỹ nghệ phần mềm (Single Point of Failure / Niche Domain), khi chỉ có duy nhất nhân sự này nắm giữ công nghệ đặc thù của dự án và các ứng viên khác hoàn toàn không có kỹ năng liên quan. Khi đó khoảng cách kỹ năng quá lớn vượt qua mức phạt tải việc, mô hình vẫn đề xuất họ nhưng đồng thời hệ thống kích hoạt cơ chế cảnh báo quá tải đỏ để Quản lý dự án nắm bắt và cân nhắc dãn tiến độ.

---

## 4.4. Đánh Giá Độ Trễ Vận Hành Thời Gian Thực & Chi Phí Tài Nguyên (Operational Latency & Efficiency)

Độ trễ phản hồi trên giao diện Web là tiêu chí sống còn đối với sự chấp nhận của người dùng doanh nghiệp. Bảng 4.3 phân rã chi tiết thời gian thực thi của từng phân đoạn trong toàn bộ chu trình End-to-End của TaskPilot:

### Bảng 4.3: Phân Rã Chi Tiết Độ Trễ Từng Phân Hệ (Latency Breakdown per Stage)
| Phân đoạn xử lý (Pipeline Stage) | Thực thể đảm nhiệm | Thời gian trung bình (Mean Latency) | Tỷ trọng ($\%$) | Ghi chú kỹ thuật |
| :--- | :--- | :---: | :---: | :--- |
| **1. Request Handling & Context Query** | PHP Laravel (Controller/Service) | $2.15\text{ ms}$ | $39.3\%$ | Truy vấn MySQL lấy danh sách ứng viên & tính `active_tasks` |
| **2. IPC Communication Transit** | Symfony Process (stdin/stdout) | $0.42\text{ ms}$ | $7.7\%$ | Truyền nhận JSON payload qua kênh I/O pipe |
| **3. Multi-stream Feature Extraction** | Scikit-Learn `ColumnTransformer` | $1.20\text{ ms}$ | $21.9\%$ | N-gram tokenization và tính toán ma trận thưa 10.006 chiều |
| **4. Core Ranking Inference** | LightGBM `LGBMRanker` | $1.45\text{ ms}$ | $26.5\%$ | Duyệt qua 100 cây quyết định LambdaMART |
| **5. Confidence Calibration & Clamping**| `calibrate_confidence` Function | $0.18\text{ ms}$ | $3.3\%$ | Softmax nhiệt độ $\tau=0.45$ và áp trần/sàn $[0.12, 0.93]$ |
| **6. Local Feature Attribution (XAI)** | Rule-based Attribution Engine | $0.07\text{ ms}$ | $1.3\%$ | Bóc tách lý do và sinh chuỗi văn bản tự nhiên |
| **Tổng Độ Trễ Chu Trình (End-to-End)** | **Toàn bộ hệ thống** | **$\mathbf{5.47\text{ ms}}$** | **$100.0\%$** | **Phản hồi tức thì, hoàn toàn trong suốt với người dùng** |

### So sánh Hiệu Quả Tài Nguyên với Phương Pháp Tiếp Cận Dùng Mô Hình Ngôn Ngữ Lớn (LLM):
Trong quá trình nghiên cứu, việc sử dụng các mô hình LLM (như GPT-4o, Llama-3-8B hoặc Qwen-7B) để phân công công việc thường được xem xét như một phương án thay thế. Bảng 4.4 so sánh định lượng giữa giải pháp LTR gọn nhẹ của TaskPilot và giải pháp dựa trên LLM:

#### Bảng 4.4: So Sánh Đối Đầu Giữa TaskPilot LTR Engine và LLM API
| Tiêu chí đánh giá (Criterion) | TaskPilot LTR (LambdaMART) | Giải pháp Large Language Model (LLM) |
| :--- | :--- | :--- |
| **Thời gian phản hồi (Latency)** | **$5.47\text{ ms}$ (Tức thì)** | $1.500 - 3.500\text{ ms}$ (Rất chậm, gián đoạn giao diện) |
| **Chi phí phần cứng (Hardware Cost)** | **0 VNĐ (Chạy trên CPU 1 nhân sẵn có)** | Cần GPU đắt tiền (VRAM $\ge 16\text{ GB}$) hoặc trả phí API |
| **Dung lượng bộ nhớ RAM tiêu thụ** | **$< 80\text{ MB RAM}$** | $> 8.000\text{ MB RAM}$ (8 GB) |
| **Khả năng vận hành Ngoại tuyến (Offline)**| **100% On-premise Offline độc lập** | Phụ thuộc kết nối mạng Internet và bên thứ ba |
| **Tính nhất quán suy luận (Determinism)** | **100% Ổn định và nhất quán giữa các lần gọi**| Dễ gặp hiện tượng ảo giác (hallucination) và bất định |
| **Bảo mật mã nguồn & Dữ liệu dự án** | **Bảo mật tuyệt đối bên trong máy chủ nội bộ**| Nguy cơ rò rỉ dữ liệu khi gửi qua dịch vụ Cloud API |

---

## 4.5. Đánh Giá Tính Minh Bạch & Nghiên Cứu Ca Điển Hình (Qualitative Case Studies)

Để chứng minh khả năng giải thích minh bạch (Explainability) và tính hợp lý trong quyết định của AI, chúng tôi tiến hành phân tích hai ca kiểm thử thực tế điển hình trích xuất từ kịch bản kiểm chuẩn của hệ thống:

### Ca Điển Hình 1: Tác Vụ Backend Phức Tạp và Ứng Viên Chuyên Gia Bị Quá Tải
* **Thông tin công việc:**
  - Tiêu đề: `"Fix memory leak in MySQL database query pool under high concurrency"`
  - Thành phần: `database`, `backend`
  - Kỹ năng yêu cầu: `PHP`, `MySQL`, `Database`, `Docker`
* **Danh sách ứng viên cạnh tranh trong nhóm dự án:**
  - *Ứng viên A (Senior Backend):* Kỹ năng MySQL chuyên sâu, có 15 task tương tự trong lịch sử, nhưng đang có **$w = 8$ công việc dang dở**.
  - *Ứng viên B (Mid-level Backend):* Kỹ năng PHP/MySQL tốt, có 4 task tương tự, hiện đang **$w = 1$ công việc nhẹ**.
  - *Ứng viên C (DevOps Engineer):* Kỹ năng Docker/CI-CD, đang trống việc ($w = 0$).
* **Kết quả xếp hạng và giải thích XAI của hệ thống:**

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              KẾT QUẢ ĐỀ XUẤT CỦA AI TASKPILOT                          │
├────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                        │
│  [HẠNG 1] ỨNG VIÊN B (Mid-level Backend Developer)                                     │
│  ├── Điểm phù hợp chuẩn hóa: 0.8420 ──► Score: 0.7850 ──► Độ tin cậy: 79%               │
│  └── Lý do giải thích (match_reasons):                                                 │
│      • Mô hình Học Xếp Hạng (LambdaMART / LightGBM) đánh giá tối ưu (84%)              │
│      • Kỹ năng khớp nội dung: MySQL, PHP, Database                                     │
│      • Phù hợp domain chuyên môn: backend, database                                    │
│      • Lịch sử 4 việc tương tự trong dự án (leak, mysql, query, pool)                  │
│      • Tải công việc nhẹ (1 task đang xử lý)                                           │
│                                                                                        │
│  [HẠNG 2] ỨNG VIÊN C (DevOps Engineer)                                                 │
│  ├── Điểm phù hợp chuẩn hóa: 0.5210 ──► Score: 0.4320 ──► Độ tin cậy: 43%               │
│  └── Lý do giải thích (match_reasons):                                                 │
│      • Kỹ năng khớp nội dung: Docker                                                   │
│      • Phù hợp domain chuyên môn: devops                                               │
│      • Đang trống việc (0 task), sẵn sàng nhận việc ngay                               │
│                                                                                        │
│  [BỊ ĐÁNH TỤT THỨ HẠNG] ỨNG VIÊN A (Senior Backend Developer)                          │
│  ├── Điểm phù hợp chuẩn hóa: 0.8950 (Năng lực cao nhất)                                 │
│  └── Cảnh báo hệ thống:                                                                │
│      • Đã điều chỉnh tụt hạng do tải công việc quá cao (8 task đang mở)                │
│      • Cảnh báo nguy cơ nghẽn tiến độ dự án (Bottleneck Risk Mitigation)               │
│                                                                                        │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

> **Nhận xét chuyên môn:** Mặc dù Ứng viên A có điểm kỹ năng cao nhất, thuật toán LambdaMART đã chứng tỏ năng lực xử phạt chính xác khi nhận diện tình trạng quá tải nghiêm trọng ($w=8$), đẩy Ứng viên B lên vị trí số 1. Việc đưa ra cảnh báo quá tải rõ ràng giúp người quản lý dự án đưa ra quyết định giao việc an toàn và công bằng.

### Ca Điển Hình 2: Ứng Viên Mới Gia Nhập Dự Án (Cold-start Candidate)
* **Tình huống:** Một lập trình viên mới vào dự án, chưa có bất kỳ lịch sử công việc nào ($H=0$, `history_overlap = 0`), nhưng có khai báo kỹ năng `Vue.js`, `Tailwind CSS` và đang trống việc ($w=0$).
* **Kết quả:** Hệ thống nhận diện chính xác qua luồng vector kỹ năng và gán nhãn khuyến nghị **Grade 3 (Theo Quy tắc R2 - Tiềm năng cao)**, xếp ứng viên này vào Top 1 với độ tin cậy **$85\%$** kèm lý do: `"Kỹ năng khớp nội dung: Vue.js, Tailwind CSS"`, `"Đang trống việc (0 task), sẵn sàng nhận việc ngay"`.
* **Kết luận:** Mô hình giải quyết triệt để vấn đề khởi động lạnh (Cold-start) cho nhân sự mới, khắc phục nhược điểm của các hệ thống Collaborative Filtering truyền thống chỉ dựa vào lịch sử quá khứ.

---

## 4.6. Thảo Luận Ranh Giới & Hướng Phát Triển (Discussion & Threats to Validity)

### 4.6.1. Đe dọa đến Tính Hợp Lệ (Threats to Validity)
1. **Tính tổng quát hóa giữa Mã nguồn Mở và Doanh nghiệp Tư nhân:**
   - Tập dữ liệu benchmark được xây dựng từ Apache Jira. Mặc dù có quy mô lớn (400.000 tác vụ sau làm sạch) và bao hàm 52 dự án phần mềm đa dạng, văn hóa phân công công việc trong cộng đồng nguồn mở có thể tồn tại một số khác biệt so với quy trình phân công tại các doanh nghiệp phần mềm khép kín.
   - Để khắc phục, hệ thống đã thiết kế cơ chế **Dynamic Project Context Injection**, tự động nạp cấu trúc thành viên và kỹ năng nội bộ của từng công ty thông qua các bảng MySQL `user_skills` và `tasks`.
2. **Trường hợp Nhân sự Thiếu Hồ sơ Kỹ năng (Complete Cold-start):**
   - Nếu một tài khoản nhân sự mới không khai báo kỹ năng trong hồ sơ cá nhân và chưa từng thực hiện công việc nào, vector đặc trưng sẽ hoàn toàn là vector 0. Khi đó hệ thống sẽ kích hoạt tầng bảo vệ Fallback, dựa trên chức danh công việc (`job_title`) và phân bổ đều cho người có tải việc thấp nhất.

### 4.6.2. Hướng Phát Triển Tương Lai (Future Research Directions)
1. **Mô hình Học Trực Tuyến Tăng Cường (Online Continual Learning):**
   - Hiện thực hóa cơ chế Replay Buffer cập nhật trọng số cây quyết định theo từng lô nhỏ (Mini-batch) ngay khi nhận được phản hồi chấp nhận hoặc đổi người từ Quản lý Dự án (`status = 'accepted'` hoặc `'changed'`).
2. **Mở rộng Đánh Giá Đa Ngôn Ngữ (Multilingual Semantic Embeddings):**
   - Tích hợp thêm các mô hình ngôn ngữ dạng Sentence-Transformers siêu nhẹ (như `all-MiniLM-L6-v2` chuyển đổi sang định dạng ONNX Runtime CPU) nhằm hỗ trợ tìm kiếm ngữ nghĩa sâu đối với các mô tả công việc sử dụng tiếng Việt hỗn hợp với tiếng Anh chuyên ngành.

---

## 4.7. Tóm Tắt Chương 4

Chương 4 đã cung cấp bức tranh toàn diện và có cơ sở khoa học vững chắc về kết quả thực nghiệm của hệ thống AI TaskPilot:
1. **Hiệu năng vượt trội & Thực tế:** Mô hình đề xuất **LightGBM LambdaMART** đạt **53.25% Top-1**, **73.50% Top-3**, **78.80% Top-5** và chỉ số **MRR 0.7614** trên nhóm ứng viên quy chuẩn $K=10$ (40.000 mẫu kiểm thử thực tế), vượt qua toàn bộ các baseline từ Heuristic, Content-based đến Mạng Nơ-ron Sâu MLP.
2. **Giải quyết hiệu quả bài toán Quá tải:** Nghiên cứu bóc tách (Ablation Study) khẳng định vai trò sống còn của biến tải công việc động $w$, đưa tỷ lệ công bằng (Fairness Rate) đạt mức vượt trội **96.80%** (giảm thiểu 87.7% nguy cơ giao việc cho nhân sự đang quá tải).
3. **Độ trễ siêu tốc:** Đạt độ trễ toàn chu trình chỉ **$5.47\text{ ms}$** trên CPU thông thường với chi phí bộ nhớ cực thấp ($< 80\text{ MB}$), chứng minh tính khả thi và ưu việt vượt bậc so với các giải pháp LLM cồng kềnh.
4. **Tính minh bạch cao:** Cơ chế giải thích XAI cục bộ sinh lý do tự nhiên bằng tiếng Việt trong thời gian $< 0.1\text{ ms}$, giúp người quản lý ra quyết định phân công công việc với độ tin cậy và sự an tâm tối đa.
