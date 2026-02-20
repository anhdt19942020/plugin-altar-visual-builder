# 🏺 Altar Configurator - Premium Visual Builder

**Altar Configurator** là một WordPress Plugin cao cấp được thiết kế riêng cho các gian hàng WooCommerce bán đồ thờ cúng hoặc vật phẩm phong thủy. Plugin tích hợp Fabric.js để tạo ra một môi trường 2D trực quan, cho phép khách hàng tự sắp xếp, bài trí bàn thờ trước khi mua trọn bộ.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0+-purple.svg)

---

## ✨ Tính năng nổi bật

- **Premium Dark UI**: Giao diện sang trọng với tông màu sơn mài đen và vàng đồng, tối ưu trải nghiệm khách hàng cao cấp.
- **Realistic Perspective Engine**:
  - **Auto-Scaling**: Vật phẩm tự động thu nhỏ khi kéo về phía sau và to lên khi kéo ra phía trước mặt bàn.
  - **Dynamic Shadows**: Bóng đổ thông minh thay đổi độ mờ và hướng dựa trên vị trí của vật phẩm.
  - **Depth Sorting**: Tự động sắp xếp lớp (Z-index) theo vị trí dọc, đảm bảo đồ phía trước luôn đè lên đồ phía sau.
- **Base Altar System**: Cho phép chọn mẫu bàn thờ chính làm nền tảng trước khi thêm các đồ thờ lẻ.
- **Tìm kiếm động**: Khách hàng tìm sản phẩm trực tiếp từ kho WooCommerce.
- **WooCommerce Bundle**: Toàn bộ vật phẩm trên canvas được gom thành một bundle và thêm vào giỏ hàng chỉ với 1 click.
- **Preview Image**: Tự động chụp ảnh phối cảnh của khách hàng và đính kèm vào đơn hàng cho quản trị viên.

---

## 🛠 Hướng dẫn Cài đặt

### Cách 1: Cài đặt từ tệp ZIP (Dành cho người dùng)

1. Truy cập [GitHub Repository](https://github.com/anhdt19942020/plugin-altar-visual-builder).
2. Bấm vào nút **Code** -> **Download ZIP**.
3. Trong giao diện WordPress Admin, đi tới **Plugins > Add New > Upload Plugin**.
4. Chọn file ZIP vừa tải và bấm **Install Now**.
5. **Activate** plugin.

### Cách 2: Sử dụng Git (Dành cho nhà phát triển)

```bash
cd wp-content/plugins
git clone https://github.com/anhdt19942020/plugin-altar-visual-builder.git altar-configurator
```

---

## 📖 Hướng dẫn sử dụng

### 1. Cấu hình sản phẩm (Bắt buộc)

Để sản phẩm xuất hiện trong bộ cấu hình, bạn cần cài đặt thông số cho chúng:

1. Vào **Products > All Products** và chọn sản phẩm (ví dụ: Bát hương, Lọ hoa, hoặc Bàn thờ).
2. Cuộn xuống phần **Product Data**, chọn tab **General**.
3. Tại phần **Altar Configurator Settings**:
   - **Overlay PNG URL**: Dán link ảnh PNG đã tách nền của sản phẩm (Quan trọng nhất).
   - **Default Scale**: Tỉ lệ kích thước mặc định trên canvas (Ví dụ: 0.5).
   - **Altar Item Type**: Chọn loại vật phẩm (Bát hương, Chén nước, hoặc **Base Altar** nếu là bàn thờ).
4. Bấm **Update**.

### 2. Hiển thị bộ cấu hình

Tạo một trang mới trong WordPress và dán shortcode sau vào nội dung:

```text
[altar_configurator]
```

---

## 🎹 Phím tắt & Thao tác

- **Kéo thả**: Di chuyển vật phẩm trên bàn thờ.
- **Góc xoay/Co giãn**: Sử dụng các điểm điều khiển quanh vật phẩm đang chọn.
- **Phím Delete / Backspace**: Xóa vật phẩm đang chọn khỏi bàn thờ.
- **Cuộn chuột (Scroll)**: Thu phóng vật phẩm (nếu đang được chọn).

---

## ⚙️ Yêu cầu hệ thống

- WordPress 6.0 trở lên.
- WooCommerce 7.0 trở lên.
- Trình duyệt hiện đại hỗ trợ HTML5 Canvas.

## 📄 Giấy phép

Sản phẩm được phát hành dưới giấy phép **MIT**.

---

_Phát triển bởi Maestro AI Orchestrator - 2025._
