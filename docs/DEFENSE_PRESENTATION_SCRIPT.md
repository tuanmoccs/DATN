# Kịch bản thuyết trình bảo vệ đồ án

## Hướng dẫn sử dụng

- Thời lượng mục tiêu: 12-15 phút.
- Không đọc nguyên văn nội dung trên slide; chỉ dùng slide làm điểm tựa.
- Tập trung nhiều thời gian nhất cho RAG, tính năng AI và kết quả kiểm thử.
- Khi trình bày sơ đồ, chỉ lần lượt chỉ vào các thành phần đang nói.
- Các đoạn dưới đây là lời nói gợi ý, có thể điều chỉnh theo cách diễn đạt cá nhân.

---

## Slide 1. Trang bìa

**Thời lượng:** 20-30 giây.

**Lời trình bày gợi ý:**

> Em xin kính chào thầy cô trong hội đồng. Em là ..., sinh viên lớp ... Sau đây, em xin trình bày đồ án tốt nghiệp với đề tài “Xây dựng hệ thống hỗ trợ giảng dạy các môn học bằng tiếng Anh có ứng dụng trí tuệ nhân tạo”, dưới sự hướng dẫn của giảng viên ...
>
> Nội dung trình bày tập trung vào bài toán, kiến trúc hệ thống, công nghệ RAG, các tính năng AI nổi bật và kết quả kiểm thử.

**Chuyển slide:**

> Trước tiên, em xin trình bày bối cảnh và bài toán mà đề tài hướng đến giải quyết.

---

## Slide 2. Bối cảnh và phát biểu bài toán

**Thời lượng:** 50-60 giây.

**Lời trình bày gợi ý:**

> Trong quá trình giảng dạy các môn học bằng tiếng Anh, học sinh phải đồng thời xử lý kiến thức chuyên môn và ngôn ngữ tiếng Anh. Điều này tạo ra tải nhận thức kép, đặc biệt với học sinh có trình độ ngoại ngữ chưa đồng đều.
>
> Về phía giáo viên, việc chuẩn bị slide, câu hỏi kiểm tra, bài tập và nhận xét học sinh bằng tiếng Anh mất nhiều thời gian. Các công cụ AI phổ thông có thể hỗ trợ tạo nội dung, nhưng thường trả lời dựa trên kiến thức tổng quát và chưa bảo đảm bám sát tài liệu của giáo viên.
>
> Vì vậy, bài toán của đề tài là xây dựng một hệ thống vừa quản lý hoạt động dạy và học, vừa sử dụng AI để tạo học liệu và hỗ trợ học sinh, trong đó nội dung AI phải dựa trên tài liệu bài học cụ thể.

**Câu nhấn mạnh:**

> Điểm quan trọng không chỉ là “dùng AI”, mà là kiểm soát AI để kết quả bám sát học liệu do giáo viên cung cấp.

---

## Slide 3. Mục tiêu và phạm vi đề tài

**Thời lượng:** 45-55 giây.

**Lời trình bày gợi ý:**

> Đề tài hướng đến ba mục tiêu chính. Thứ nhất, xây dựng website cho giáo viên và ứng dụng di động cho học sinh. Thứ hai, triển khai các nghiệp vụ quản lý lớp học, bài học, quiz, bài tập và tiến độ học tập. Thứ ba, tích hợp AI để sinh slide, sinh quiz, hỏi đáp theo bài học, hỗ trợ soạn giáo án, chấm bài gợi ý và tạo báo cáo năng lực.
>
> Trong phạm vi hiện tại, AI đóng vai trò hỗ trợ. Giáo viên vẫn là người kiểm duyệt slide, quiz, điểm bài tập và báo cáo trước khi sử dụng chính thức.

**Chuyển slide:**

> Để triển khai các mục tiêu này, hệ thống được thiết kế theo kiến trúc nhiều thành phần.

---

## Slide 4. Kiến trúc tổng thể hệ thống

**Thời lượng:** 60-75 giây.

**Lời trình bày gợi ý:**

> Hệ thống gồm bốn thành phần chính. Website giáo viên được xây dựng bằng Vue.js, phục vụ quản lý lớp, bài học, giáo án, bài tập và báo cáo. Ứng dụng học sinh sử dụng React Native để học bài, làm quiz, nộp bài và hỏi đáp với AI.
>
> Hai giao diện này không gọi trực tiếp AI Service mà gửi yêu cầu đến Laravel Backend. Backend chịu trách nhiệm xác thực, phân quyền, quản lý dữ liệu nghiệp vụ và điều phối các tác vụ.
>
> Những chức năng AI được tách thành một FastAPI Service sử dụng LangChain, mô hình ngôn ngữ, mô hình embedding và ChromaDB. MySQL lưu dữ liệu nghiệp vụ có cấu trúc, còn ChromaDB lưu vector của các đoạn tài liệu để phục vụ tìm kiếm ngữ nghĩa.
>
> Việc tách Laravel và FastAPI giúp mỗi thành phần sử dụng hệ sinh thái phù hợp và giảm sự phụ thuộc giữa nghiệp vụ với xử lý AI.

---

## Slide 5. Tác nhân và chức năng chính

**Thời lượng:** 40-50 giây.

**Lời trình bày gợi ý:**

> Hệ thống có hai người dùng chính là giáo viên và học sinh. Giáo viên quản lý lớp học, tạo bài học, sinh học liệu bằng AI, quản lý bài tập, giáo án và báo cáo năng lực. Học sinh tham gia lớp, xem slide, làm quiz, nộp bài và hỏi đáp với trợ lý AI.
>
> AI Service là tác nhân kỹ thuật hỗ trợ các nghiệp vụ cần sinh hoặc phân tích nội dung. Trong báo cáo, em chỉ giữ các use case tiêu biểu; các use case CRUD và biểu đồ chi tiết được chuyển xuống phụ lục để tập trung vào phần công nghệ nổi bật.

---

## Slide 6. Luồng tạo bài học và nội dung AI

**Thời lượng:** 60 giây.

**Lời trình bày gợi ý:**

> Đây là luồng nghiệp vụ trung tâm của hệ thống. Giáo viên tạo bài học, nhập nội dung hoặc tải tài liệu lên. Laravel lưu thông tin bài học và tạo một tác vụ nền trong queue.
>
> Queue worker lấy nội dung tài liệu, gửi sang AI Service để trích xuất văn bản và lập chỉ mục RAG. Sau khi indexing hoàn tất, hệ thống sinh slide và quiz dựa trên dữ liệu đã truy xuất, rồi lưu kết quả về cơ sở dữ liệu.
>
> Việc sử dụng queue giúp request tạo bài học không phải chờ mô hình AI xử lý xong, tránh timeout và cho phép frontend theo dõi trạng thái cũng như tiến độ của tác vụ.

**Câu nhấn mạnh:**

> Như vậy, việc sinh nội dung AI được xử lý bất đồng bộ và tách khỏi luồng nghiệp vụ chính.

---

## Slide 7. Thiết kế dữ liệu ở mức khái quát

**Thời lượng:** 35-45 giây.

**Lời trình bày gợi ý:**

> Dữ liệu được chia thành các nhóm chính gồm người dùng và xác thực, lớp học và ghi danh, bài học và slide, quiz và kết quả làm bài, bài tập và chấm điểm, cùng nhóm báo cáo năng lực và batch xử lý AI.
>
> Trong phần trình bày, em chỉ giới thiệu các nhóm dữ liệu và mối quan hệ chính. Sơ đồ đầy đủ, khóa chính, khóa ngoại và cấu trúc từng bảng được trình bày trong phụ lục báo cáo.

**Chuyển slide:**

> Sau phần thiết kế tổng thể, em xin chuyển sang nội dung trọng tâm của đề tài là công nghệ RAG.

---

## Slide 8. Công nghệ AI cốt lõi: RAG

**Thời lượng:** 75-90 giây.

**Lời trình bày gợi ý:**

> RAG là viết tắt của Retrieval-Augmented Generation, tức là kết hợp truy xuất thông tin với mô hình sinh ngôn ngữ.
>
> Em lựa chọn RAG thay vì fine-tuning vì tài liệu thay đổi theo từng giáo viên và từng bài học. Fine-tuning cần dữ liệu huấn luyện lớn và phải huấn luyện lại khi nội dung thay đổi. Với RAG, hệ thống chỉ cần lập chỉ mục tài liệu mới vào vector database.
>
> Pipeline gồm hai pha. Ở pha indexing, tài liệu được trích xuất thành văn bản, chia thành các chunk, tạo embedding và lưu vào ChromaDB. Ở pha generation, hệ thống nhận yêu cầu, tìm các chunk liên quan nhất, ghép thành context rồi đưa vào mô hình ngôn ngữ để sinh kết quả.
>
> Nhờ đó, hệ thống không phải gửi toàn bộ tài liệu vào LLM và nội dung sinh ra có cơ sở từ học liệu của bài học.

---

## Slide 9. Thuật toán truy xuất RAG

**Thời lượng:** 90 giây.

**Lời trình bày gợi ý:**

> Khi có một truy vấn, hệ thống tìm các vector gần nhất trong ChromaDB và bắt buộc lọc theo `lesson_id`. Điều kiện này ngăn dữ liệu của bài học khác bị đưa nhầm vào context.
>
> ChromaDB trả về distance, sau đó hệ thống chuyển thành relevance score. Ngoài điểm ngữ nghĩa, hệ thống còn tính lexical score dựa trên mức trùng từ khóa.
>
> Điểm xếp hạng cuối được tính theo công thức: 85 phần trăm semantic score và 15 phần trăm lexical score. Semantic score giữ vai trò chính, còn lexical score hỗ trợ các thuật ngữ hoặc công thức cụ thể.
>
> Sau khi xếp hạng, hệ thống áp dụng threshold để loại chunk liên quan thấp. Nếu không có chunk đạt ngưỡng, hệ thống có thể sử dụng low-confidence fallback để tránh context rỗng. Cuối cùng, các chunk được ghép lại nhưng vẫn bị giới hạn bởi kích thước context tối đa để kiểm soát token và thời gian phản hồi.

**Lưu ý khi nói công thức:**

> Đây là trọng số thực nghiệm ban đầu, không phải một hằng số tối ưu cho mọi bộ dữ liệu và có thể tiếp tục hiệu chỉnh.

---

## Slide 10. AI sinh slide và quiz

**Thời lượng:** 75-90 giây.

**Lời trình bày gợi ý:**

> Slide và quiz dùng chung nền tảng RAG nhưng có truy vấn và prompt khác nhau. Sinh slide ưu tiên khái niệm, định nghĩa, công thức, ví dụ và các ý chính cần trình bày. Sinh quiz ưu tiên kiến thức có thể đánh giá như nguyên lý, bài tập mẫu và dữ kiện quan trọng.
>
> Với slide, hệ thống còn có cơ chế lọc hai lớp. Trước khi gọi LLM, các đoạn thiên về chỉ dẫn giáo viên hoặc tổ chức lớp học được loại bỏ. Sau khi LLM trả kết quả, hệ thống tiếp tục kiểm tra để tránh tạo slide có nội dung như “giáo viên chia nhóm”.
>
> Kết quả slide và quiz được yêu cầu trả về dưới dạng JSON. Backend có thể parse, kiểm tra và lưu trực tiếp vào các bảng tương ứng. Giáo viên vẫn có thể chỉnh sửa trước khi phát hành.

---

## Slide 11. Chatbot học tập theo bài học

**Thời lượng:** 60-75 giây.

**Lời trình bày gợi ý:**

> Khi học sinh đặt câu hỏi, chính câu hỏi đó được sử dụng làm truy vấn RAG. Hệ thống lấy các chunk liên quan trong bài học hiện tại, kết hợp với một số tin nhắn gần nhất trong lịch sử hội thoại rồi gửi đến mô hình ngôn ngữ.
>
> Chatbot có thể trả thêm thông tin nguồn như tên tài liệu, chỉ số chunk và mức liên quan để người học kiểm chứng nội dung.
>
> Một điểm bảo mật quan trọng là backend không gửi trường đánh dấu đáp án đúng và phần giải thích đáp án của quiz cho chatbot. Vì vậy AI có thể hướng dẫn học sinh hiểu kiến thức nhưng không lấy trực tiếp đáp án đúng từ cơ sở dữ liệu.

---

## Slide 12. AI Autocomplete hỗ trợ soạn giáo án

**Thời lượng:** 50-60 giây.

**Lời trình bày gợi ý:**

> Chức năng Autocomplete hỗ trợ giáo viên trong quá trình soạn giáo án. Hệ thống không gửi toàn bộ giáo án, mà chỉ lấy phần văn bản gần vị trí con trỏ làm ngữ cảnh hiện tại và làm truy vấn RAG.
>
> AI Service truy xuất một số chunk liên quan nhất từ tài liệu bài học, sau đó kết hợp với nội dung giáo viên đang viết để sinh phần tiếp theo. Gợi ý được hiển thị dưới dạng ghost text trong trình soạn thảo.
>
> Giáo viên có thể chấp nhận hoặc bỏ qua gợi ý. Việc giới hạn phần văn bản và số chunk giúp giảm token, giảm độ trễ và làm gợi ý tập trung hơn vào nội dung đang được soạn.

---

## Slide 13. AI chấm bài và báo cáo năng lực

**Thời lượng:** 75-90 giây.

**Lời trình bày gợi ý:**

> Với chức năng chấm bài, hệ thống trích xuất nội dung từ bài nộp và tài liệu tham khảo của giáo viên. Các dữ liệu này được đưa trực tiếp vào prompt cùng yêu cầu bài tập và tiêu chí chấm.
>
> AI trả về điểm đề xuất, nhận xét tổng quan, điểm mạnh, điểm yếu và gợi ý cải thiện dưới dạng JSON. Chức năng này không dùng RAG vector theo `lesson_id` làm bước chính, vì bài nộp và tài liệu tham khảo đã được cung cấp trực tiếp làm ngữ cảnh.
>
> Báo cáo năng lực được tạo từ bằng chứng học tập gồm kết quả quiz và bài tập. Hệ thống tổng hợp điểm trung bình, sau đó AI sinh nhận xét và khuyến nghị.
>
> Trong cả hai chức năng, AI chỉ hỗ trợ. Giáo viên vẫn là người kiểm tra và quyết định điểm hoặc nội dung báo cáo cuối cùng.

---

## Slide 14. Kết quả kiểm thử AI bằng Postman

**Thời lượng:** 75-90 giây.

**Lời trình bày gợi ý:**

> Phần kiểm thử tập trung vào các API AI thay vì các API CRUD thông thường. Em sử dụng Postman để kiểm tra các luồng gồm xử lý tài liệu, RAG retrieval, sinh slide, sinh quiz, chatbot, autocomplete, chấm bài và báo cáo năng lực.
>
> Các tiêu chí chính gồm: API phản hồi thành công, dữ liệu đúng schema JSON, chunk được truy xuất đúng `lesson_id`, kết quả có liên quan đến tài liệu đầu vào và có thể lưu vào hệ thống.
>
> Kết quả cho thấy các API AI chính hoạt động đúng luồng và trả dữ liệu có cấu trúc. Slide, quiz và câu trả lời có nội dung bám sát tài liệu khi đầu vào rõ ràng.
>
> Tuy nhiên, Postman chủ yếu chứng minh tính đúng của API và luồng xử lý. Để đánh giá AI toàn diện hơn vẫn cần bộ dữ liệu lớn, đánh giá của giáo viên và các chỉ số định lượng về chất lượng retrieval.

**Nếu có ảnh Postman trên slide:**

> Ở hình này là ví dụ response của API ..., trong đó có thể thấy các trường ... và kết quả ...

Không đọc toàn bộ JSON; chỉ chỉ ra 2-3 trường quan trọng.

---

## Slide 15. Đánh giá, kết luận và hướng phát triển

**Thời lượng:** 60-75 giây.

**Lời trình bày gợi ý:**

> Đề tài đã xây dựng được một hệ thống gồm website giáo viên, ứng dụng học sinh, Laravel Backend và FastAPI AI Service. Các chức năng AI chính đã được tích hợp vào nghiệp vụ thực tế, đặc biệt là sinh slide, sinh quiz, chatbot theo bài học và autocomplete giáo án.
>
> Điểm mạnh của hệ thống là tách biệt rõ nghiệp vụ và AI Service, sử dụng RAG để nội dung bám sát tài liệu, có queue cho tác vụ dài và giữ giáo viên trong vòng kiểm duyệt.
>
> Hạn chế hiện tại là chất lượng đầu ra phụ thuộc vào tài liệu đầu vào, chunking vẫn dựa chủ yếu trên ký tự, rerank còn đơn giản và chưa có bộ đánh giá AI quy mô lớn.
>
> Trong tương lai, hệ thống có thể sử dụng token-based chunking, reranker chuyên biệt, bổ sung bộ đánh giá retrieval, tăng khả năng trích dẫn nguồn và triển khai vector database phù hợp với quy mô lớn hơn.
>
> Phần trình bày của em xin kết thúc tại đây. Em xin cảm ơn thầy cô đã lắng nghe và mong nhận được câu hỏi, góp ý từ hội đồng.

---

# Phiên bản mở đầu ngắn

Nếu cần mở đầu tự nhiên hơn, có thể dùng:

> Em xin kính chào thầy cô trong hội đồng. Đề tài của em xuất phát từ hai khó khăn thực tế: giáo viên mất nhiều thời gian chuẩn bị học liệu bằng tiếng Anh, còn học sinh gặp khó khăn khi phải tiếp thu đồng thời kiến thức chuyên môn và ngoại ngữ. Vì vậy, em xây dựng một hệ thống AI EdTech sử dụng RAG để hỗ trợ tạo học liệu và hỏi đáp dựa trên chính tài liệu của giáo viên.

# Phiên bản kết thúc ngắn

> Tóm lại, hệ thống đã tích hợp được RAG vào các nghiệp vụ giáo dục thực tế và kiểm soát kết quả AI thông qua tài liệu bài học cùng vai trò kiểm duyệt của giáo viên. Em xin kết thúc phần trình bày và sẵn sàng trả lời câu hỏi của hội đồng.

# Phân bổ thời gian đề xuất

| Nhóm nội dung | Slide | Thời lượng |
|---|---:|---:|
| Mở đầu và bài toán | 1-3 | 2 phút |
| Phân tích, thiết kế | 4-7 | 3 phút |
| RAG và tính năng AI | 8-13 | 6-7 phút |
| Kiểm thử, kết luận | 14-15 | 2-3 phút |

# Các lỗi cần tránh khi trình bày

- Không nói RAG loại bỏ hoàn toàn hallucination; chỉ nói RAG giúp giảm rủi ro.
- Không nói hệ thống tự huấn luyện mô hình AI.
- Không gọi AI chấm bài là RAG nếu không có bước truy xuất từ vector database.
- Không khẳng định trọng số 0.85/0.15 là tối ưu tuyệt đối.
- Không nói kiểm thử Postman đã chứng minh đầy đủ chất lượng AI.
- Không đọc từng trường trong JSON hoặc từng bảng cơ sở dữ liệu.
- Không dành quá nhiều thời gian cho CRUD, đăng nhập hoặc mô tả giao diện.
- Luôn nhấn mạnh giáo viên là người kiểm duyệt kết quả cuối cùng.
