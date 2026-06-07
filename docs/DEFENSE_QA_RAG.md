# Bộ câu hỏi phản biện bảo vệ đồ án

Tài liệu này tổng hợp các câu hỏi hội đồng có thể hỏi khi phản biện đồ án. Trọng tâm là phần AI, RAG, kiến trúc hệ thống, kiểm thử và các điểm dễ bị hỏi xoáy.

## 1. Bài toán và lựa chọn giải pháp

### Câu 1. Vì sao đề tài cần dùng AI, không chỉ làm hệ thống quản lý lớp học thông thường?

**Trả lời:**

Nếu chỉ quản lý lớp học thông thường thì hệ thống chỉ giải quyết phần lưu trữ và tổ chức dữ liệu. Vấn đề chính của đề tài là giáo viên mất nhiều thời gian tạo học liệu bằng tiếng Anh, còn học sinh cần được hỗ trợ giải thích nội dung theo bài học. Vì vậy hệ thống tích hợp AI để sinh slide, sinh quiz, hỗ trợ hỏi đáp, chấm gợi ý và sinh báo cáo năng lực. AI giúp giảm tải công việc lặp lại cho giáo viên và hỗ trợ học sinh học tập cá nhân hóa hơn.

### Câu 2. Vì sao chọn RAG thay vì fine-tuning mô hình?

**Trả lời:**

Em chọn RAG vì nội dung bài học thay đổi thường xuyên theo từng giáo viên, từng lớp và từng tài liệu. Nếu fine-tuning thì cần dữ liệu huấn luyện lớn, chi phí cao và phải huấn luyện lại khi tài liệu thay đổi. RAG phù hợp hơn vì chỉ cần lập chỉ mục tài liệu mới vào vector database, sau đó mô hình có thể sinh nội dung dựa trên tài liệu đó mà không cần huấn luyện lại.

### Câu 3. RAG trong hệ thống giải quyết vấn đề gì?

**Trả lời:**

RAG giúp AI không trả lời chỉ dựa trên kiến thức tổng quát của mô hình, mà dựa trên tài liệu bài học do giáo viên cung cấp. Khi sinh slide, quiz hoặc trả lời câu hỏi, hệ thống truy xuất các đoạn tài liệu liên quan nhất theo `lesson_id`, sau đó đưa vào prompt. Nhờ vậy nội dung đầu ra bám sát bài học hơn, giảm nguy cơ lạc đề và có thể truy xuất nguồn chunk đã sử dụng.

### Câu 4. Nếu không dùng RAG mà gửi toàn bộ tài liệu vào LLM thì có được không?

**Trả lời:**

Có thể làm với tài liệu ngắn, nhưng không phù hợp khi tài liệu dài hoặc nhiều bài học. Gửi toàn bộ tài liệu sẽ tốn token, chậm, dễ vượt giới hạn ngữ cảnh và khó kiểm soát phần nào của tài liệu được dùng. RAG giúp chỉ lấy các đoạn liên quan nhất, giảm token và tăng khả năng kiểm soát ngữ cảnh.

## 2. Câu hỏi sâu về RAG

### Câu 5. Mô tả ngắn gọn pipeline RAG của hệ thống?

**Trả lời:**

Pipeline gồm hai pha. Pha indexing: tài liệu bài học được trích xuất văn bản, chia chunk, tạo embedding và lưu vào ChromaDB kèm metadata như `lesson_id`, `source_name`, `chunk_index`. Pha generation: khi có yêu cầu sinh slide, quiz hoặc chat, hệ thống tạo truy vấn, tìm các chunk liên quan theo `lesson_id`, sắp xếp theo điểm liên quan, ghép context và đưa vào LLM để sinh kết quả.

### Câu 6. Vì sao phải gắn metadata `lesson_id` cho từng chunk?

**Trả lời:**

`lesson_id` dùng để lọc dữ liệu theo từng bài học. Nếu không lọc theo `lesson_id`, khi học sinh hỏi hoặc giáo viên sinh slide, hệ thống có thể lấy nhầm chunk từ bài học khác. Điều này làm AI trả lời sai ngữ cảnh. Vì vậy metadata `lesson_id` là điều kiện quan trọng để đảm bảo RAG chỉ sử dụng nội dung của bài học hiện tại.

### Câu 7. Chunk size và chunk overlap có ý nghĩa gì?

**Trả lời:**

Chunk size là độ dài mỗi đoạn văn bản sau khi chia tài liệu. Chunk overlap là phần lặp lại giữa hai chunk liên tiếp. Chunk size quá nhỏ có thể mất ngữ cảnh, còn quá lớn thì retrieval kém chính xác và tốn token. Overlap giúp tránh mất ý ở ranh giới giữa hai chunk, đặc biệt khi một khái niệm hoặc câu dài bị cắt ngang.

### Câu 8. Nếu chunk size quá nhỏ thì ảnh hưởng thế nào?

**Trả lời:**

Chunk nhỏ giúp tìm kiếm chi tiết hơn nhưng dễ làm mất ngữ cảnh, ví dụ chỉ lấy được một phần định nghĩa hoặc một phần ví dụ. Khi đưa vào LLM, mô hình có thể thiếu thông tin để sinh slide hoặc câu trả lời đầy đủ.

### Câu 9. Nếu chunk size quá lớn thì ảnh hưởng thế nào?

**Trả lời:**

Chunk lớn giữ được nhiều ngữ cảnh hơn nhưng có thể chứa nhiều ý không liên quan. Khi retrieval, hệ thống có thể lấy một chunk chỉ vì một phần nhỏ liên quan, còn phần còn lại gây nhiễu. Ngoài ra chunk lớn cũng làm tăng token đưa vào prompt.

### Câu 10. Vì sao hệ thống cần score threshold?

**Trả lời:**

Score threshold dùng để loại các chunk có mức liên quan thấp. Nếu lấy mọi kết quả từ vector search mà không lọc, context đưa vào LLM có thể chứa nội dung nhiễu. Threshold giúp kiểm soát chất lượng context trước khi sinh kết quả.

### Câu 11. Low-confidence fallback là gì? Có rủi ro không?

**Trả lời:**

Low-confidence fallback là cơ chế vẫn lấy các chunk gần nhất nếu không có chunk nào vượt ngưỡng threshold. Nó giúp hệ thống không bị rỗng context trong trường hợp tài liệu ngắn hoặc truy vấn không khớp tốt. Rủi ro là có thể lấy chunk chưa thật sự liên quan, nên cần dùng threshold hợp lý và giáo viên vẫn kiểm duyệt kết quả AI.

### Câu 12. Semantic score và lexical score khác nhau thế nào?

**Trả lời:**

Semantic score đo mức gần nhau về ngữ nghĩa thông qua embedding. Lexical score đo mức trùng từ khóa giữa truy vấn và nội dung chunk. Semantic score giúp bắt được các cách diễn đạt khác nhau, còn lexical score giúp ưu tiên các chunk có từ khóa cụ thể xuất hiện trong truy vấn. Hệ thống kết hợp hai điểm này để rerank chunk tốt hơn.

### Câu 13. Vì sao dùng công thức `0.85 * semantic_score + 0.15 * lexical_score`?

**Trả lời:**

Vì bài toán chính là tìm kiếm ngữ nghĩa trong tài liệu học tập, semantic score cần đóng vai trò chính. Tuy nhiên, trong giáo dục có nhiều thuật ngữ, công thức hoặc khái niệm cụ thể, nên lexical score vẫn được thêm vào để ưu tiên các chunk có trùng từ khóa quan trọng. Tỉ lệ 0.85 và 0.15 là lựa chọn thực nghiệm ban đầu, có thể tinh chỉnh thêm khi có bộ đánh giá lớn hơn.

### Câu 14. RAG có đảm bảo AI không trả lời sai không?

**Trả lời:**

Không đảm bảo tuyệt đối. RAG giúp giảm rủi ro bằng cách cung cấp ngữ cảnh đúng từ tài liệu, nhưng LLM vẫn có thể diễn giải sai hoặc sinh nội dung chưa chính xác. Vì vậy hệ thống xem kết quả AI là nội dung hỗ trợ, giáo viên vẫn là người kiểm duyệt cuối cùng, đặc biệt với slide, quiz, điểm số và báo cáo năng lực.

### Câu 15. Nếu tài liệu đầu vào sai hoặc kém chất lượng thì RAG có xử lý được không?

**Trả lời:**

RAG phụ thuộc nhiều vào chất lượng tài liệu đầu vào. Nếu tài liệu sai, thiếu hoặc trình bày quá mơ hồ thì kết quả AI cũng bị ảnh hưởng. Hệ thống có thể hỗ trợ trích xuất, chunking và truy xuất, nhưng không thể tự đảm bảo tài liệu gốc là đúng. Vì vậy giáo viên cần kiểm tra tài liệu trước khi dùng để sinh nội dung.

### Câu 16. Làm sao biết slide hoặc quiz sinh ra thật sự dựa trên tài liệu?

**Trả lời:**

Có thể kiểm tra thông qua RAG Sandbox hoặc qua sources/chunk được truy xuất. Hệ thống lưu metadata của chunk như `source_name`, `chunk_index` và score. Khi kiểm thử, em có thể xem các chunk được lấy ra trước khi sinh nội dung và so sánh output với tài liệu đầu vào.

### Câu 17. Vì sao sinh slide và sinh quiz dùng truy vấn khác nhau?

**Trả lời:**

Vì mục tiêu hai chức năng khác nhau. Sinh slide cần truy xuất các khái niệm, định nghĩa, ví dụ và ý chính để trình bày bài học. Sinh quiz cần ưu tiên kiến thức có thể đánh giá, ví dụ công thức, nguyên lý, bài tập mẫu, facts và khái niệm trọng tâm. Dùng truy vấn riêng giúp context phù hợp hơn với từng đầu ra.

### Câu 18. Vì sao cần lọc nội dung hướng dẫn giáo viên khi sinh slide hoặc quiz?

**Trả lời:**

Tài liệu bài học có thể chứa cả nội dung học sinh cần học và chỉ dẫn sư phạm cho giáo viên. Nếu đưa toàn bộ vào prompt, AI có thể sinh slide chứa các câu như "giáo viên chia nhóm" hoặc "tổ chức hoạt động". Điều này không phù hợp với slide học tập. Vì vậy hệ thống lọc ngữ cảnh trước khi sinh và kiểm tra lại output sau khi sinh.

## 3. Chatbot học tập

### Câu 19. Chatbot của hệ thống khác gì ChatGPT thông thường?

**Trả lời:**

Chatbot trong hệ thống trả lời theo ngữ cảnh bài học cụ thể. Khi học sinh hỏi, hệ thống truy xuất tài liệu của `lesson_id` hiện tại rồi đưa vào prompt. ChatGPT thông thường trả lời dựa trên kiến thức tổng quát, còn chatbot của hệ thống ưu tiên tài liệu giáo viên đã cung cấp.

### Câu 20. Nếu học sinh hỏi ngoài nội dung bài học thì chatbot trả lời thế nào?

**Trả lời:**

Nếu truy xuất không tìm được ngữ cảnh phù hợp, hệ thống có thể trả lời rằng chưa tìm thấy đủ thông tin trong tài liệu bài học và gợi ý học sinh hỏi cụ thể hơn. Nếu bật fallback, AI vẫn có thể dùng chunk gần nhất, nhưng kết quả cần được giới hạn theo ngữ cảnh bài học để tránh trả lời lan man.

### Câu 21. Làm sao tránh chatbot tiết lộ đáp án quiz?

**Trả lời:**

Khi backend gửi dữ liệu quiz vào ngữ cảnh chatbot, hệ thống không gửi trường `is_correct` và explanation của đáp án đúng. AI chỉ nhận nội dung câu hỏi và các lựa chọn, nên không biết trực tiếp đáp án đúng từ database. Ngoài ra prompt cũng định hướng AI hỗ trợ giải thích kiến thức thay vì đưa thẳng đáp án.

### Câu 22. Vì sao chỉ lấy lịch sử hội thoại gần nhất?

**Trả lời:**

Nếu gửi toàn bộ lịch sử hội thoại sẽ tốn token và có thể làm mô hình mất tập trung. Hệ thống chỉ lấy một số tin nhắn gần nhất để giữ ngữ cảnh đủ cần thiết, đồng thời giảm chi phí và độ trễ.

## 4. Sinh slide, sinh quiz và autocomplete

### Câu 23. Nếu AI sinh quiz có đáp án sai thì xử lý thế nào?

**Trả lời:**

Quiz do AI sinh ra được lưu ở trạng thái để giáo viên xem lại, chỉnh sửa và phát hành. AI chỉ đóng vai trò tạo bản nháp ban đầu. Trước khi học sinh làm quiz, giáo viên vẫn cần kiểm duyệt nội dung, đáp án và giải thích.

### Câu 24. Vì sao output slide/quiz phải là JSON?

**Trả lời:**

JSON giúp backend parse và lưu trực tiếp vào database. Với slide, hệ thống cần các trường như `title`, `bullet_points`, `speaker_notes`. Với quiz, hệ thống cần `question`, `options`, `is_correct`, `explanation`. Nếu output là văn bản tự do thì khó lưu trữ, khó kiểm tra và dễ lỗi khi chuyển thành dữ liệu hệ thống.

### Câu 25. Autocomplete giáo án có dùng RAG không?

**Trả lời:**

Có. Nếu giáo án gắn với bài học, hệ thống lấy đoạn văn bản gần vị trí con trỏ làm truy vấn, sau đó truy xuất một số chunk liên quan từ tài liệu bài học. Prompt kết hợp nội dung giáo viên đang viết và context từ RAG để sinh gợi ý tiếp theo.

### Câu 26. Vì sao autocomplete chỉ lấy đoạn gần con trỏ, không lấy toàn bộ giáo án?

**Trả lời:**

Autocomplete cần phản hồi nhanh và bám sát nội dung đang viết. Nếu gửi toàn bộ giáo án sẽ tốn token, tăng độ trễ và có thể làm mô hình bị phân tán. Lấy đoạn gần con trỏ giúp gợi ý tự nhiên hơn và phù hợp với trải nghiệm thời gian thực.

## 5. AI chấm bài và báo cáo năng lực

### Câu 27. AI chấm bài có phải dùng RAG không?

**Trả lời:**

Trong hệ thống hiện tại, AI chấm bài không dùng RAG vector theo `lesson_id` làm bước chính. Chức năng này trích xuất trực tiếp nội dung từ bài nộp và tài liệu tham khảo của giáo viên, sau đó đưa vào prompt cùng tiêu chí chấm điểm. Vì bài nộp và tài liệu tham khảo đã là ngữ cảnh trực tiếp, không nhất thiết phải truy xuất từ ChromaDB.

### Câu 28. AI chấm bài có công bằng không?

**Trả lời:**

AI chỉ đưa ra điểm gợi ý và phản hồi tham khảo. Để đảm bảo công bằng, giáo viên vẫn là người xem lại và chốt điểm cuối cùng. Hệ thống cũng giới hạn điểm trong khoảng từ 0 đến điểm tối đa và lưu phản hồi có cấu trúc để giáo viên dễ kiểm tra.

### Câu 29. Báo cáo năng lực học sinh được sinh từ dữ liệu nào?

**Trả lời:**

Báo cáo được sinh từ evidence gồm kết quả quiz, kết quả bài tập, điểm trung bình, số quiz đã làm và số bài tập đã hoàn thành. AI dùng các dữ liệu này để tạo nhận xét tổng quan, điểm mạnh, điểm yếu và khuyến nghị học tập.

### Câu 30. Nếu học sinh chưa có quiz hoặc bài tập thì có sinh báo cáo không?

**Trả lời:**

Không nên sinh. Trong hệ thống, nếu chưa có dữ liệu quiz hoặc bài tập thì backend trả về thông báo chưa đủ dữ liệu để tạo báo cáo. Điều này giúp tránh việc AI sinh nhận xét không có căn cứ.

## 6. Kiến trúc hệ thống

### Câu 31. Vì sao tách Laravel Backend và FastAPI AI Service?

**Trả lời:**

Laravel phù hợp với nghiệp vụ web như xác thực, phân quyền, CRUD, quản lý file và database. FastAPI phù hợp với Python AI stack như LangChain, embedding, ChromaDB và gọi LLM. Tách hai service giúp hệ thống rõ trách nhiệm, dễ mở rộng và dễ bảo trì.

### Câu 32. Frontend có gọi trực tiếp AI Service không?

**Trả lời:**

Không. Frontend gọi Laravel Backend. Backend kiểm tra xác thực và quyền truy cập, sau đó mới gọi AI Service qua API nội bộ có `X-API-Secret`. Cách này tránh để người dùng gọi trực tiếp AI Service và giúp kiểm soát quyền theo lớp học, bài học.

### Câu 33. Vì sao cần queue cho tác vụ AI?

**Trả lời:**

Các tác vụ như sinh slide, sinh quiz hoặc tạo báo cáo cả lớp có thể mất nhiều thời gian. Nếu xử lý trực tiếp trong request, người dùng phải chờ lâu và dễ timeout. Queue giúp đưa tác vụ vào xử lý nền, backend lưu trạng thái batch, progress và kết quả để frontend kiểm tra sau.

### Câu 34. Nếu AI Service lỗi thì hệ thống xử lý thế nào?

**Trả lời:**

Backend ghi nhận lỗi, cập nhật trạng thái batch là failed hoặc trả thông báo lỗi cho người dùng. Với một số chức năng sinh slide/quiz, hệ thống có fallback sang gọi OpenAI trực tiếp nếu RAG/AI Service không trả được kết quả. Tuy nhiên, kết quả fallback có thể kém bám sát tài liệu hơn RAG.

### Câu 35. Vì sao dùng ChromaDB?

**Trả lời:**

ChromaDB là vector database phù hợp cho prototype và hệ thống quy mô vừa. Nó tích hợp tốt với LangChain, hỗ trợ lưu embedding kèm metadata và tìm kiếm similarity. Với đề tài tốt nghiệp, ChromaDB đáp ứng được nhu cầu lưu chunk tài liệu bài học và truy xuất theo `lesson_id`.

## 7. Kiểm thử và đánh giá

### Câu 36. Em đánh giá chất lượng AI bằng cách nào?

**Trả lời:**

Em kiểm thử bằng Postman trên các API AI chính: xử lý tài liệu, retrieval, sinh slide, sinh quiz, chatbot, chấm bài và báo cáo năng lực. Tiêu chí đánh giá gồm API trả đúng cấu trúc JSON, nội dung bám sát tài liệu, chunk truy xuất đúng `lesson_id`, kết quả có liên quan và có thể lưu vào hệ thống.

### Câu 37. Kiểm thử Postman có đủ để khẳng định hệ thống tốt không?

**Trả lời:**

Postman giúp kiểm tra API hoạt động đúng luồng và trả đúng cấu trúc. Tuy nhiên, để đánh giá chất lượng AI toàn diện hơn cần thêm bộ dữ liệu kiểm thử lớn, đánh giá bởi giáo viên và các chỉ số như độ chính xác retrieval, mức độ phù hợp của câu hỏi, chất lượng phản hồi. Trong phạm vi đề tài, em tập trung kiểm thử chức năng và đánh giá thủ công trên các tình huống tiêu biểu.

### Câu 38. Hạn chế lớn nhất của hệ thống hiện tại là gì?

**Trả lời:**

Hạn chế lớn nhất là chất lượng đầu ra AI phụ thuộc vào chất lượng tài liệu đầu vào và chưa có bộ đánh giá tự động quy mô lớn. Ngoài ra, chunking hiện tại chủ yếu dựa trên ký tự, rerank còn đơn giản và ChromaDB đang phù hợp hơn với môi trường thử nghiệm hơn là triển khai quy mô rất lớn.

### Câu 39. Nếu triển khai thực tế cho nhiều trường thì cần cải tiến gì?

**Trả lời:**

Cần tối ưu hạ tầng vector database, bổ sung cache, monitoring, phân quyền dữ liệu chặt hơn, đánh giá chất lượng AI bằng giáo viên, thêm reranker chuyên biệt và cơ chế kiểm soát chi phí token. Ngoài ra cần có chính sách bảo vệ dữ liệu học sinh và logging rõ ràng cho các tác vụ AI.

### Câu 40. Em có đo thời gian phản hồi không?

**Trả lời:**

Trong kiểm thử hiện tại, em ghi nhận thời gian phản hồi qua Postman ở mức chức năng. Các tác vụ ngắn như retrieval/chat có thể trả trong thời gian chấp nhận được, còn tác vụ dài như sinh slide/quiz được đưa vào queue. Tuy nhiên, em chưa xây dựng benchmark hiệu năng quy mô lớn, đây là hướng cải tiến tiếp theo.

## 8. Câu hỏi xoáy về tính học thuật và độ tin cậy

### Câu 41. RAG có phải là thuật toán học máy do em tự xây dựng không?

**Trả lời:**

Không. RAG là một kiến trúc kết hợp truy xuất thông tin và sinh ngôn ngữ. Trong đề tài, em không tự huấn luyện mô hình embedding hay LLM, mà thiết kế pipeline ứng dụng RAG vào bài toán giáo dục: xử lý tài liệu, chunking, embedding, truy xuất theo `lesson_id`, rerank, build context và prompt chuyên biệt cho từng chức năng.

### Câu 42. Phần đóng góp kỹ thuật chính của em là gì?

**Trả lời:**

Đóng góp chính là thiết kế và triển khai hệ thống AI EdTech hoàn chỉnh, trong đó RAG được tích hợp vào các nghiệp vụ thực tế như sinh slide, sinh quiz, chatbot học tập và autocomplete giáo án. Ngoài ra hệ thống có kiến trúc đa tác nhân, queue cho tác vụ AI dài, kiểm soát quyền qua Laravel và cơ chế giáo viên kiểm duyệt kết quả AI.

### Câu 43. Nếu AI hallucination thì hệ thống có phát hiện được không?

**Trả lời:**

Hệ thống hiện tại chưa có cơ chế tự động phát hiện hallucination hoàn toàn. Tuy nhiên, RAG giúp giảm rủi ro bằng cách cung cấp context từ tài liệu bài học, và chatbot có thể trả nguồn chunk để kiểm chứng. Với các nội dung quan trọng như quiz, điểm số và báo cáo, giáo viên vẫn kiểm duyệt trước khi sử dụng.

### Câu 44. Làm sao đảm bảo dữ liệu giữa các lớp không bị lẫn?

**Trả lời:**

Ở backend, mọi request đều kiểm tra quyền người dùng theo lớp học và bài học. Ở tầng RAG, chunk được gắn `lesson_id` và retrieval có filter `lesson_id`. Nhờ đó học sinh hoặc giáo viên chỉ truy xuất dữ liệu thuộc bài học hợp lệ của mình.

### Câu 45. Vì sao gọi là multi-agent? Có thật sự là nhiều agent không?

**Trả lời:**

Có. AI Service có `BaseAgent` và `AgentRegistry`. Mỗi agent phụ trách một nhiệm vụ riêng như `ChatAgent`, `SlideAgent`, `QuizAgent`, `CompetencyReportAgent`, `AutocompleteAgent`. Các agent có schema đầu vào riêng, prompt riêng và logic xử lý riêng. Đây là kiến trúc đa tác nhân theo hướng modular task-specific agents.

### Câu 46. Multi-agent có tự lập kế hoạch hay trao đổi giữa các agent không?

**Trả lời:**

Không theo kiểu agent tự lập kế hoạch phức tạp. Trong phạm vi đề tài, multi-agent được triển khai theo hướng mỗi agent chuyên trách một nghiệp vụ AI cụ thể. Backend hoặc API chọn agent phù hợp để thực thi. Cách này thực tế và dễ kiểm soát hơn trong hệ thống giáo dục.

### Câu 47. Vì sao không cho AI tự động phát hành slide hoặc quiz luôn?

**Trả lời:**

Vì trong giáo dục, nội dung học tập và đánh giá cần độ tin cậy cao. AI có thể hỗ trợ tạo bản nháp nhanh, nhưng giáo viên cần kiểm tra tính chính xác, độ phù hợp với lớp học và mục tiêu bài dạy trước khi phát hành. Đây cũng là cách giảm rủi ro từ LLM.

### Câu 48. Điểm mới của đề tài ở đâu?

**Trả lời:**

Điểm mới trong phạm vi đề tài là tích hợp RAG vào một hệ thống EdTech hoàn chỉnh cho bối cảnh dạy môn học bằng tiếng Anh. Hệ thống không chỉ có chatbot, mà còn dùng cùng nền tảng RAG để sinh slide, quiz, hỗ trợ soạn giáo án và kết hợp với các chức năng đánh giá như AI chấm bài, báo cáo năng lực. Các chức năng này được nối với dữ liệu lớp học, bài học và tiến độ học tập thực tế.

### Câu 49. Có chắc AI sinh nội dung tiếng Anh phù hợp trình độ học sinh không?

**Trả lời:**

Hiện tại hệ thống có thể định hướng prompt để nội dung rõ ràng, dễ hiểu và phù hợp ngữ cảnh học tập. Tuy nhiên, việc đánh giá chính xác mức độ phù hợp với từng trình độ học sinh cần thêm dữ liệu năng lực và phản hồi giáo viên. Trong phiên bản hiện tại, giáo viên vẫn kiểm duyệt và chỉnh sửa nội dung trước khi phát hành.

### Câu 50. Nếu chỉ được nói 30 giây về RAG của em, em nói gì?

**Trả lời:**

RAG trong hệ thống gồm hai pha. Đầu tiên, tài liệu bài học được trích xuất, chia chunk, tạo embedding và lưu vào ChromaDB kèm `lesson_id`. Khi cần sinh slide, quiz hoặc trả lời câu hỏi, hệ thống truy xuất các chunk liên quan nhất theo `lesson_id`, rerank bằng semantic score và lexical score, ghép thành context rồi đưa vào LLM. Cách này giúp AI sinh nội dung bám sát tài liệu giáo viên cung cấp và giảm rủi ro trả lời sai ngữ cảnh.

## 9. Câu trả lời ngắn nên học thuộc

### RAG là gì trong đề tài?

RAG là cơ chế kết hợp truy xuất tài liệu và sinh nội dung. Hệ thống lấy các đoạn tài liệu liên quan từ ChromaDB theo `lesson_id`, đưa vào prompt và dùng LLM để sinh slide, quiz hoặc câu trả lời.

### Vì sao RAG phù hợp với giáo dục?

Vì tài liệu bài học thay đổi thường xuyên. RAG cho phép cập nhật tri thức bằng cách lập chỉ mục tài liệu mới, không cần huấn luyện lại mô hình.

### Điểm mạnh nhất của hệ thống?

Hệ thống tích hợp AI vào nhiều nghiệp vụ giáo dục thực tế, đặc biệt là sinh slide, sinh quiz, chatbot học tập, autocomplete giáo án, AI chấm bài và báo cáo năng lực.

### Hạn chế lớn nhất?

Chất lượng AI phụ thuộc vào tài liệu đầu vào và hệ thống chưa có bộ đánh giá tự động quy mô lớn cho chất lượng nội dung AI.

### Vai trò của giáo viên trong hệ thống AI?

Giáo viên vẫn là người kiểm duyệt và quyết định cuối cùng. AI chỉ hỗ trợ tạo bản nháp, gợi ý, nhận xét và tổng hợp dữ liệu.
