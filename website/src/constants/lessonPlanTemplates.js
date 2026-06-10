const escapeHtml = (value = '') =>
  String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

const valueOrEmpty = (value) =>
  value?.trim() ? escapeHtml(value.trim()) : ''

const placeholderParagraph = (placeholder, value = '') =>
  `<p data-placeholder="${escapeHtml(placeholder)}">${valueOrEmpty(value)}</p>`

export const LESSON_PLAN_START_OPTIONS = [
  {
    id: 'standard',
    title: 'Standard Lesson Plan Template',
    description: 'Objectives, learning materials, a 4-activity timeline, and assessment are available.',
    icon: 'fas fa-clipboard-list',
    recommended: true,
  },
  // {
  //   id: 'copy',
  //   title: 'Copy from existing lesson plan',
  //   description: 'Reuse content from an existing lesson plan and modify it for the new lesson.',
  //   icon: 'fas fa-copy',
  // },
  {
    id: 'blank',
    title: 'Blank Template',
    description: 'Start with a blank editor, suitable when you already have a unique structure.',
    icon: 'fas fa-file',
  },
]

export function buildStandardLessonPlan(data) {
  return `
    <h1>GIÁO ÁN: ${escapeHtml(data.title.trim())}</h1>
    <h2>1. Thông tin bài dạy</h2>
    <h3>Môn học</h3>
    ${placeholderParagraph('Nhập môn học', data.subject)}
    <h3>Khối lớp</h3>
    ${placeholderParagraph('Nhập khối lớp', data.grade_level)}
    <h3>Chủ đề/Bài học</h3>
    ${placeholderParagraph('Nhập chủ đề hoặc tên bài', data.topic)}
    <h3>Thời lượng</h3>
    ${placeholderParagraph('Ví dụ: 2 tiết (90 phút)', data.duration)}
    <h3>Ngày dạy</h3>
    ${placeholderParagraph('Nhập ngày dạy')}
    <h2>2. Mục tiêu bài học</h2>
    <h3>2.1. Yêu cầu cần đạt</h3>
    ${placeholderParagraph('Mô tả yêu cầu cần đạt của bài học', data.learning_outcomes)}
    <h3>2.2. Kiến thức</h3>
    ${placeholderParagraph('Mô tả kiến thức học sinh cần nắm được sau bài học')}
    <h3>2.3. Năng lực</h3>
    <p><strong>Năng lực chung</strong></p>
    ${placeholderParagraph('Ví dụ: Giao tiếp, hợp tác, tự học, giải quyết vấn đề...')}
    <p><strong>Năng lực đặc thù</strong></p>
    ${placeholderParagraph('Mô tả năng lực đặc thù gắn với môn học')}
    <h3>2.4. Phẩm chất</h3>
    ${placeholderParagraph('Mô tả phẩm chất cần hình thành hoặc phát triển')}
    <h2>3. Thiết bị và học liệu</h2>
    <h3>Giáo viên chuẩn bị</h3>
    ${placeholderParagraph('Liệt kê thiết bị, tài liệu và học liệu cần chuẩn bị')}
    <h3>Học sinh chuẩn bị</h3>
    ${placeholderParagraph('Liệt kê dụng cụ và nội dung cần chuẩn bị')}
    <h2>4. Tiến trình dạy học</h2>
    ${buildActivity('4.1. Hoạt động khởi động', 'Tạo hứng thú và kết nối với kiến thức đã học')}
    ${buildActivity('4.2. Hoạt động hình thành kiến thức', 'Giúp học sinh khám phá và hình thành kiến thức mới')}
    ${buildActivity('4.3. Hoạt động luyện tập', 'Củng cố kiến thức và rèn luyện kỹ năng')}
    ${buildActivity('4.4. Hoạt động vận dụng', 'Vận dụng kiến thức vào tình huống thực tiễn')}
    <h2>5. Kiểm tra và đánh giá</h2>
    <p><strong>Phương pháp</strong></p>
    ${placeholderParagraph('Ví dụ: Quan sát, hỏi đáp, đánh giá sản phẩm học tập...')}
    <p><strong>Công cụ</strong></p>
    ${placeholderParagraph('Ví dụ: Phiếu học tập, bảng kiểm, câu hỏi...')}
    <p><strong>Tiêu chí</strong></p>
    ${placeholderParagraph('Nêu các tiêu chí hoàn thành nhiệm vụ')}
    <h2>6. Điều chỉnh sau bài dạy</h2>
    ${placeholderParagraph('Ghi lại nội dung cần điều chỉnh sau khi tổ chức bài dạy')}
  `
}

function buildActivity(title, objective) {
  return `
    <h3>${title}</h3>
    <p><strong>Mục tiêu</strong></p>
    ${placeholderParagraph(objective)}
    <p><strong>Thời lượng</strong></p>
    ${placeholderParagraph('Nhập thời lượng của hoạt động')}
    <p><strong>Nội dung</strong></p>
    ${placeholderParagraph('Mô tả nhiệm vụ học tập')}
    <p><strong>Sản phẩm</strong></p>
    ${placeholderParagraph('Mô tả kết quả học tập mong đợi')}
    <p><strong>Tổ chức thực hiện</strong></p>
    ${placeholderParagraph('1. Chuyển giao nhiệm vụ')}
    ${placeholderParagraph('2. Học sinh thực hiện nhiệm vụ')}
    ${placeholderParagraph('3. Báo cáo và thảo luận')}
    ${placeholderParagraph('4. Giáo viên kết luận và nhận định')}
  `
}
