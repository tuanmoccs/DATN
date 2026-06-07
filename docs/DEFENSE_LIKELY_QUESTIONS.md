# Các câu hỏi có khả năng cao khi bảo vệ đồ án

Tài liệu này ưu tiên những câu hỏi hội đồng thường đặt sau khi nghe phần trình bày về hệ thống và RAG. Câu trả lời được viết theo cách có thể nói trực tiếp, không đi quá sâu vào các vấn đề production ngoài phạm vi đồ án.

---

## 1. Tại sao em chọn đề tài này và hệ thống giải quyết vấn đề gì?

### Trả lời

Đề tài xuất phát từ hai khó khăn chính. Giáo viên mất nhiều thời gian để chuẩn bị slide, câu hỏi, bài tập và nội dung bằng tiếng Anh. Học sinh phải đồng thời hiểu kiến thức chuyên môn và ngôn ngữ tiếng Anh nên thường cần được giải thích thêm ngoài giờ học.

Hệ thống giải quyết bài toán này bằng cách cung cấp website cho giáo viên, ứng dụng cho học sinh và các chức năng AI như sinh slide, sinh quiz, hỏi đáp theo bài học, hỗ trợ soạn giáo án, chấm bài gợi ý và tạo báo cáo năng lực.

Điểm trọng tâm là AI không chỉ sinh nội dung từ kiến thức tổng quát mà sử dụng RAG để dựa trên tài liệu do giáo viên cung cấp.

### Câu hỏi xoáy có thể hỏi tiếp

**Nếu bỏ AI thì hệ thống còn giá trị không?**

Hệ thống vẫn có các chức năng quản lý lớp, bài học, quiz và bài tập. Tuy nhiên, điểm khác biệt chính của đề tài là dùng AI để giảm khối lượng chuẩn bị học liệu và hỗ trợ học sinh theo nội dung bài học.

---

## 2. Vì sao em chọn RAG mà không dùng ChatGPT hoặc gọi LLM trực tiếp?

### Trả lời

Nếu chỉ gọi LLM trực tiếp, mô hình chủ yếu dựa trên kiến thức tổng quát và có thể tạo nội dung không đúng với tài liệu hoặc mục tiêu của giáo viên.

RAG bổ sung một bước truy xuất tài liệu trước khi sinh kết quả. Hệ thống tìm các đoạn liên quan nhất trong tài liệu bài học, đưa chúng vào prompt rồi mới gọi LLM. Nhờ đó, slide, quiz và câu trả lời có xu hướng bám sát học liệu hơn.

RAG cũng phù hợp hơn fine-tuning vì tài liệu thay đổi theo từng bài học. Khi nội dung thay đổi, hệ thống chỉ cần lập chỉ mục lại tài liệu mà không phải huấn luyện lại mô hình.

### Câu hỏi xoáy có thể hỏi tiếp

**RAG có loại bỏ hoàn toàn việc AI trả lời sai không?**

Không. RAG chỉ giảm rủi ro bằng cách cung cấp context phù hợp. LLM vẫn có thể diễn giải sai, vì vậy giáo viên vẫn phải kiểm duyệt các kết quả quan trọng.

---

## 3. Em hãy trình bày chính xác RAG trong hệ thống hoạt động như thế nào?

### Trả lời

RAG có hai giai đoạn.

Giai đoạn indexing:

1. Giáo viên nhập nội dung hoặc tải tài liệu bài học.
2. Hệ thống trích xuất văn bản từ PDF, DOCX hoặc TXT.
3. Văn bản được chia thành các chunk có phần overlap.
4. Mỗi chunk được chuyển thành vector embedding.
5. Vector và metadata như `lesson_id`, tên nguồn và vị trí chunk được lưu vào ChromaDB.

Giai đoạn retrieval và generation:

1. Hệ thống nhận yêu cầu sinh slide, quiz hoặc câu hỏi của học sinh.
2. Yêu cầu được chuyển thành embedding để tìm kiếm trong ChromaDB.
3. Chỉ các chunk thuộc đúng `lesson_id` được truy xuất.
4. Các chunk được tính điểm, lọc và sắp xếp lại.
5. Các chunk tốt nhất được ghép thành context.
6. Context được đưa vào prompt để LLM sinh kết quả.

### Câu hỏi xoáy có thể hỏi tiếp

**Tại sao phải lọc theo `lesson_id`?**

Để tránh lấy nhầm dữ liệu của bài học khác. Đây là cơ chế phân vùng tri thức của từng bài học trong vector database.

---

## 4. Chunk size, overlap, top K và threshold là gì? Em chọn chúng như thế nào?

### Trả lời

- `chunk_size` là độ dài tối đa của mỗi đoạn tài liệu. Hệ thống đang dùng giá trị mặc định 1.000 ký tự.
- `chunk_overlap` là phần nội dung lặp lại giữa hai chunk liên tiếp, mặc định 200 ký tự. Nó giúp tránh mất ý tại vị trí cắt.
- `top_k` là số chunk cuối cùng được chọn làm context. Chat dùng ít hơn, còn slide và quiz cần nhiều context hơn.
- `threshold` là ngưỡng liên quan tối thiểu để loại bỏ chunk quá xa truy vấn.

Các giá trị hiện tại được chọn qua thử nghiệm trên tài liệu mẫu và có thể điều chỉnh trong RAG Sandbox. Em không khẳng định đây là bộ tham số tối ưu cho mọi loại tài liệu.

### Câu hỏi xoáy có thể hỏi tiếp

**Chunk lớn hay nhỏ tốt hơn?**

Không có giá trị tốt nhất cho mọi trường hợp. Chunk nhỏ giúp truy xuất chính xác hơn nhưng dễ mất ngữ cảnh. Chunk lớn giữ nhiều ngữ cảnh nhưng có thể chứa nội dung nhiễu và tốn token hơn.

---

## 5. Tại sao sinh slide, sinh quiz và chatbot đều dùng RAG nhưng lại cần xử lý khác nhau?

### Trả lời

Vì mục tiêu đầu ra của từng chức năng khác nhau.

- Sinh slide cần khái niệm, định nghĩa, công thức, ví dụ và ý chính để trình bày.
- Sinh quiz cần kiến thức có thể đánh giá như nguyên lý, dữ kiện và bài tập mẫu.
- Chatbot dùng trực tiếp câu hỏi của học sinh để tìm đoạn trả lời phù hợp.
- Autocomplete dùng phần văn bản gần con trỏ để tìm nội dung có thể viết tiếp.

Do đó, mỗi chức năng có truy vấn retrieval, số lượng chunk, prompt và schema đầu ra riêng. Chúng dùng chung pipeline RAG nhưng không dùng chung hoàn toàn cách sinh kết quả.

### Câu hỏi xoáy có thể hỏi tiếp

**Tại sao không dùng một prompt chung để đơn giản hơn?**

Prompt chung sẽ khó kiểm soát định dạng và chất lượng cho từng nghiệp vụ. Ví dụ slide cần danh sách ý chính, còn quiz cần đáp án đúng và giải thích. Tách prompt giúp kết quả ổn định và dễ kiểm thử hơn.

---

## 6. Phần nào trong hệ thống là do em thực hiện, phần nào sử dụng công nghệ có sẵn?

### Trả lời

Em sử dụng các công nghệ có sẵn như OpenAI model, LangChain, ChromaDB, Laravel, FastAPI, Vue và React Native. Em không tự huấn luyện LLM hoặc phát minh ra RAG.

Phần em thiết kế và triển khai gồm:

- Kiến trúc kết nối website, ứng dụng học sinh, Laravel và AI Service.
- Pipeline trích xuất, chunking, embedding, lưu và truy xuất tài liệu.
- Metadata và lọc dữ liệu theo từng bài học.
- Cơ chế rerank kết hợp semantic score với lexical score.
- Prompt và schema riêng cho slide, quiz, chatbot, autocomplete và báo cáo.
- Queue và batch để xử lý tác vụ AI dài.
- Lưu kết quả AI vào dữ liệu nghiệp vụ.
- Cơ chế giáo viên kiểm duyệt kết quả.
- RAG Sandbox để quan sát quá trình chunking và retrieval.

### Câu hỏi xoáy có thể hỏi tiếp

**Vậy điểm mới của đề tài là gì?**

Điểm mới trong phạm vi đồ án là tích hợp RAG vào một hệ thống EdTech hoàn chỉnh và áp dụng cùng nguồn học liệu cho nhiều nghiệp vụ, thay vì chỉ xây dựng một chatbot độc lập.

---

## 7. Em gọi hệ thống là multi-agent, vậy các agent có tự trao đổi với nhau không?

### Trả lời

Trong hệ thống hiện tại, multi-agent được triển khai theo hướng nhiều agent chuyên trách theo nhiệm vụ, không phải hệ thống agent tự trị.

Ví dụ có SlideAgent, QuizAgent, ChatAgent, AutocompleteAgent và CompetencyReportAgent. Mỗi agent có schema đầu vào, prompt và logic xử lý riêng. Agent Registry nhận tên agent, kiểm tra payload và chuyển yêu cầu đến đúng agent.

Các agent chưa tự lập kế hoạch hoặc trao đổi với nhau. Vì vậy cách gọi chính xác hơn là kiến trúc nhiều agent chuyên trách.

### Câu hỏi xoáy có thể hỏi tiếp

**Nếu chỉ là các module thì tại sao cần Agent Registry?**

Registry tạo một giao diện gọi thống nhất, chuẩn hóa validation và giúp bổ sung agent mới mà không phải tạo cách gọi hoàn toàn khác ở Laravel.

---

## 8. Em kiểm thử và đánh giá AI như thế nào? Postman có đủ không?

### Trả lời

Em dùng Postman để kiểm tra các API AI chính gồm xử lý tài liệu, retrieval, sinh slide, sinh quiz, chatbot, autocomplete, chấm bài và báo cáo năng lực.

Các tiêu chí kiểm tra gồm:

- API phản hồi đúng trạng thái.
- Kết quả đúng cấu trúc JSON.
- Retrieval trả chunk thuộc đúng bài học.
- Nội dung có liên quan đến tài liệu đầu vào.
- Điểm chấm nằm trong phạm vi hợp lệ.
- Kết quả có thể lưu và hiển thị trong hệ thống.

Tuy nhiên, Postman chủ yếu kiểm tra chức năng và tích hợp API. Nó chưa đủ để kết luận chất lượng AI một cách toàn diện. Muốn đánh giá đầy đủ cần có bộ câu hỏi chuẩn, đánh giá của giáo viên và các chỉ số như Recall@K, faithfulness và answer relevance.

### Câu hỏi xoáy có thể hỏi tiếp

**Vậy kết luận nào em được phép đưa ra từ kết quả kiểm thử?**

Em có thể kết luận các API AI hoạt động đúng luồng, trả dữ liệu có cấu trúc và kết quả quan sát được có liên quan đến tài liệu mẫu. Em chưa nên kết luận AI đạt độ chính xác cao trong mọi trường hợp.

---

## 9. Nếu nhiều người dùng cùng sử dụng AI thì performance của hệ thống ra sao?

### Trả lời

Trong phạm vi đồ án, em chưa thực hiện load test quy mô lớn nên chưa có cơ sở cam kết một số lượng người dùng đồng thời cụ thể.

Các tác vụ dài như sinh slide, quiz và báo cáo được đưa vào Laravel Queue để tránh request bị timeout. Chatbot và autocomplete được xử lý trực tiếp vì cần phản hồi tương tác. Hệ thống cũng giới hạn số chunk và độ dài context để giảm token và thời gian xử lý.

Các điểm nghẽn chính có thể là:

- Tốc độ và rate limit của LLM.
- Số lượng queue worker.
- Khả năng truy vấn đồng thời của ChromaDB local.
- OCR đối với tài liệu scan.
- Chi phí và độ dài context.

Nếu đánh giá performance đầy đủ, em sẽ dùng k6 hoặc Locust để đo P50, P95 latency, throughput, error rate, queue waiting time và chi phí token tại nhiều mức tải.

### Câu hỏi xoáy có thể hỏi tiếp

**Tại sao dùng queue lại không làm hệ thống nhanh hơn?**

Queue không làm tác vụ AI xử lý nhanh hơn. Nó giúp request của người dùng phản hồi sớm, tránh timeout và cho phép kiểm soát số tác vụ chạy đồng thời. Tổng thời gian sinh nội dung vẫn phụ thuộc vào AI Service và LLM.

---

## 10. Nếu AI Service hoặc OpenAI bị lỗi thì hệ thống xử lý thế nào?

### Trả lời

Laravel ghi trạng thái của tác vụ AI trong batch. Nếu job thất bại, batch được cập nhật thành `failed` và lưu thông báo lỗi để frontend hiển thị.

Với sinh slide và quiz, hệ thống có cơ chế fallback sang gọi OpenAI trực tiếp nếu pipeline RAG không trả được kết quả. Điều này giúp tăng khả năng hoàn thành tác vụ, nhưng kết quả fallback có thể kém bám sát tài liệu hơn.

Các tác vụ AI không làm mất dữ liệu nghiệp vụ chính. Ví dụ bài học vẫn được tạo ngay cả khi việc sinh slide hoặc quiz thất bại, và giáo viên có thể yêu cầu sinh lại.

### Câu hỏi xoáy có thể hỏi tiếp

**Vì sao job hiện chỉ thử một lần?**

Vì tác vụ AI tốn chi phí và việc retry khi chưa có idempotency đầy đủ có thể tạo dữ liệu trùng. Hướng cải tiến là chỉ retry lỗi tạm thời như timeout hoặc rate limit, kết hợp exponential backoff và thao tác lưu có tính idempotent.

---

## 11. Làm sao bảo vệ dữ liệu và ngăn học sinh lấy đáp án quiz qua chatbot?

### Trả lời

Frontend không gọi trực tiếp AI Service mà đi qua Laravel. Laravel xác thực người dùng, kiểm tra học sinh có thuộc lớp và có quyền truy cập bài học trước khi gọi AI.

Kết nối từ Laravel đến AI Service sử dụng `X-API-Secret`. Ở tầng RAG, retrieval được lọc theo `lesson_id` để tránh lấy dữ liệu bài học khác.

Đối với quiz, backend không gửi trường `is_correct` và phần giải thích đáp án đúng vào context chatbot. AI chỉ nhận nội dung câu hỏi và các lựa chọn, từ đó có thể hướng dẫn kiến thức nhưng không lấy trực tiếp đáp án từ cơ sở dữ liệu.

### Câu hỏi xoáy có thể hỏi tiếp

**Như vậy học sinh vẫn có thể hỏi AI tự suy luận đáp án đúng không?**

Có thể. Việc loại bỏ đáp án khỏi context chỉ ngăn rò rỉ trực tiếp từ database, không thể ngăn hoàn toàn AI tự suy luận. Prompt cần yêu cầu AI chỉ hướng dẫn cách giải, và hoạt động đánh giá quan trọng không nên phụ thuộc hoàn toàn vào chatbot.

---

## 12. Hạn chế lớn nhất của đồ án và hướng cải tiến quan trọng nhất là gì?

### Trả lời

Hạn chế lớn nhất là hệ thống chưa có bộ đánh giá AI quy mô đủ lớn. Chất lượng hiện được kiểm tra chủ yếu bằng Postman và đánh giá thủ công trên tài liệu mẫu. Vì vậy chưa thể đưa ra số liệu thống kê mạnh về độ chính xác của retrieval hoặc chất lượng nội dung AI.

Ngoài ra:

- Chunking đang dựa trên số ký tự thay vì token.
- Rerank mới là công thức heuristic.
- ChromaDB local phù hợp với thử nghiệm hơn triển khai lớn.
- RAG chưa loại bỏ hoàn toàn hallucination.
- Chưa có chống prompt injection toàn diện.

Hướng cải tiến ưu tiên là xây dựng tập dữ liệu đánh giá do giáo viên xác nhận, sau đó đo retrieval và generation. Khi có bộ đánh giá, các tham số chunk size, threshold, top K, prompt và model mới có thể được tối ưu có căn cứ.

### Câu hỏi xoáy có thể hỏi tiếp

**Nếu chỉ được cải tiến một thứ, tại sao không chọn giao diện hoặc thêm chức năng?**

Vì bộ đánh giá quyết định hệ thống AI có thực sự đáng tin cậy hay không. Thêm chức năng khi chưa đo được chất lượng sẽ làm hệ thống rộng hơn nhưng không chắc tốt hơn.

---

# Sáu câu nên học kỹ nhất

1. Tại sao dùng RAG thay vì gọi LLM trực tiếp?
2. Pipeline RAG của hệ thống hoạt động như thế nào?
3. Các tham số chunk size, overlap, top K và threshold có ý nghĩa gì?
4. Phần nào là do em thực hiện và điểm mới của đề tài là gì?
5. Postman có đủ để đánh giá AI không?
6. Nếu nhiều người dùng cùng dùng AI thì performance và điểm nghẽn ra sao?

# Nguyên tắc trả lời trước hội đồng

- Trả lời kết luận trước, giải thích sau.
- Phân biệt rõ phần đã triển khai và phần hướng phát triển.
- Không nói RAG loại bỏ hoàn toàn hallucination.
- Không nói hệ thống đã tối ưu nếu chưa có số liệu.
- Không đưa ra con số chịu tải nếu chưa load test.
- Không nhận là đã tự huấn luyện LLM hoặc phát minh RAG.
- Khi chưa làm một phần, thừa nhận ngắn gọn rồi trình bày cách sẽ đánh giá hoặc cải tiến.
