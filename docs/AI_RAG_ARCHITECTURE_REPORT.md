# Bao cao kien truc AI va RAG trong do an

Tai lieu nay tong hop cach do an trien khai AI, dac biet la RAG, tu kien truc tong the den tung luong chuc nang: xu ly tai lieu, sinh slide, sinh quiz, chat ho tro hoc sinh, autocomplete giao an va RAG Sandbox.

## 1. Muc tieu cua AI trong he thong

He thong la nen tang AI EdTech gom 3 lop chinh:

- Frontend giao vien: quan ly lop, bai hoc, giao an, slide, quiz, RAG Sandbox.
- Backend Laravel: API nghiep vu, xac thuc, luu CSDL, queue job, goi Python AI Service.
- Python AI Service FastAPI: xu ly RAG, embedding, vector search, LLM generation, OCR fallback, agent execution.

AI trong do an khong chi goi OpenAI truc tiep. He thong dung RAG de dua noi dung bai hoc vao vector database, sau do moi chon cac doan lien quan de dua vao LLM. Cach nay giup:

- Giam viec gui toan bo tai lieu len LLM.
- Output bam sat tai lieu bai hoc hon.
- Co the truy vet chunk nao duoc dua vao context.
- Cho phep chat/slide/quiz dung chung nguon tri thuc da index.

## 2. Kien truc tong the

Luong tong quat:

```text
Vue Website / Student App
        |
        v
Laravel Backend API
        |
        | HTTP + X-API-Secret
        v
Python FastAPI AI Service
        |
        | LangChain
        v
OpenAI Chat Model + OpenAI Embeddings
        |
        v
ChromaDB Vector Store
```

Vai tro tung thanh phan:

- Vue frontend: hien thi UI, upload file, nhap query, xem ket qua sinh AI.
- Laravel backend: kiem tra quyen, luu lesson/quiz/slide vao MySQL, goi AI service, fallback khi AI service loi.
- FastAPI AI service: tach text, chia chunk, embedding, luu ChromaDB, retrieve context, goi LLM.
- ChromaDB: luu vector embedding cua tung chunk tai lieu.
- OpenAI: tao embedding va sinh noi dung bang chat model.

## 3. RAG la gi trong do an nay

RAG la Retrieval-Augmented Generation. Trong do an, RAG co 2 pha lon.

Pha indexing:

```text
Tai lieu bai hoc
  -> extract text
  -> split thanh chunks
  -> tao embedding cho moi chunk
  -> luu vao ChromaDB kem metadata lesson_id
```

Pha generation:

```text
Yeu cau sinh slide/quiz/chat
  -> tao query retrieval
  -> vector search trong ChromaDB theo lesson_id
  -> tinh diem va rerank
  -> loc threshold/fallback
  -> ghep chunks thanh final context
  -> dua context vao prompt LLM
  -> parse JSON/text tra ve he thong
```

Dieu quan trong: RAG khong gui toan bo tai lieu len OpenAI, nhung final context sau retrieval van duoc dua vao prompt cua LLM. Vi vay `rag_max_context_chars` van anh huong den token, toc do va chi phi.

## 4. Cau hinh AI va RAG

File cau hinh chinh: `ai-service/app/core/config.py`

```python
class Settings(BaseSettings):
    openai_model: str = "gpt-4o-mini"
    openai_embedding_model: str = "text-embedding-3-small"
    chroma_persist_dir: str = "./chroma_data"

    chunk_size: int = 1000
    chunk_overlap: int = 200

    rag_score_threshold: float = 0.45
    rag_candidate_multiplier: int = 4
    rag_max_context_chars: int = 12000
    rag_low_confidence_fallback: bool = True
```

Y nghia:

- `openai_model`: model sinh cau tra loi, slide, quiz.
- `openai_embedding_model`: model tao vector embedding cho chunk va query.
- `chroma_persist_dir`: noi luu du lieu ChromaDB.
- `chunk_size`: do dai moi chunk tinh theo ky tu.
- `chunk_overlap`: so ky tu lap lai giua 2 chunk lien tiep.
- `rag_score_threshold`: nguong diem toi thieu de chunk duoc xem la du lien quan.
- `rag_candidate_multiplier`: so ung vien lay tu vector search truoc khi cat ve `top_k`.
- `rag_max_context_chars`: gioi han tong ky tu context dua vao LLM.
- `rag_low_confidence_fallback`: neu khong chunk nao dat threshold, co lay chunk gan nhat khong.

Khoi tao model va vector store: `ai-service/app/core/dependencies.py`

```python
_llm = ChatOpenAI(
    model=settings.openai_model,
    api_key=settings.openai_api_key,
    temperature=0.7,
)

_embeddings = OpenAIEmbeddings(
    model=settings.openai_embedding_model,
    api_key=settings.openai_api_key,
)

_vector_store = Chroma(
    collection_name="lesson_documents",
    embedding_function=_embeddings,
    persist_directory=settings.chroma_persist_dir,
)
```

## 5. Xu ly tai lieu bai hoc

File chinh:

- `ai-service/app/api/documents.py`
- `ai-service/app/services/document_processor.py`
- `ai-service/app/services/rag_service.py`

### 5.1 Extract text

He thong ho tro:

- PDF
- DOCX
- TXT

Code detect loai file:

```python
def _detect_content_type(filename: str) -> str:
    filename = filename.lower()

    if filename.endswith(".pdf"):
        return "pdf"
    if filename.endswith(".docx"):
        return "docx"
    if filename.endswith(".txt"):
        return "txt"

    raise HTTPException(status_code=400, detail="Unsupported file type. Use PDF, DOCX, or TXT.")
```

PDF dung PyMuPDF. DOCX dung `python-docx`. TXT decode UTF-8/UTF-16.

Voi PDF scan hoac text layer bi loi, he thong co OCR fallback bang LLM vision:

```python
async def extract_text_from_bytes_with_fallback(file_bytes: bytes, content_type: str) -> str:
    if content_type != "pdf":
        return extract_text_from_bytes(file_bytes, content_type)

    return await _extract_pdf_bytes_with_fallback(file_bytes)
```

OCR fallback render tung page thanh PNG base64 roi gui vao LLM:

```python
message = HumanMessage(content=[
    {
        "type": "text",
        "text": (
            "Extract all readable text from this Vietnamese textbook page. "
            "Return plain text only. Preserve headings, formulas, bullet points, "
            "page numbers, questions, and Vietnamese diacritics. Do not summarize."
        ),
    },
    {
        "type": "image_url",
        "image_url": {"url": f"data:image/png;base64,{image_b64}"},
    },
])
```

### 5.2 Chunking

Chunking nam trong `rag_service.py`:

```python
splitter = RecursiveCharacterTextSplitter(
    chunk_size=settings.chunk_size,
    chunk_overlap=settings.chunk_overlap,
    length_function=len,
    separators=["\n\n", "\n", ". ", " ", ""],
)
```

He thong uu tien cat theo:

1. Doan van `\n\n`
2. Dong `\n`
3. Cau `. `
4. Tu `" "`
5. Ky tu bat ky

Metadata cua moi chunk:

```python
chunk.metadata.update({
    "chunk_index": index,
    "chunk_chars": len(chunk.page_content),
    "content_hash": hashlib.sha1(chunk.page_content.encode("utf-8")).hexdigest()[:12],
})
```

Metadata quan trong:

- `lesson_id`: dung de filter chunk theo tung bai hoc.
- `source_type`: pdf/docx/txt/text.
- `source_name`: ten file hoac nguon text.
- `chunk_index`: thu tu chunk.
- `chunk_chars`: do dai chunk.
- `content_hash`: hash ngan de debug/truy vet noi dung.

### 5.3 Luu vector vao ChromaDB

```python
def store_chunks(chunks: list[Document]) -> int:
    vector_store = get_vector_store()
    vector_store.add_documents(chunks)
    return len(chunks)
```

Khi add document, LangChain dung `OpenAIEmbeddings` de tao vector va luu vao Chroma collection `lesson_documents`.

### 5.4 Xoa va re-index

Moi lan process lai lesson, he thong xoa chunk cu:

```python
def delete_lesson_chunks(lesson_id: int) -> bool:
    vector_store = get_vector_store()
    results = vector_store.get(where={"lesson_id": str(lesson_id)})

    if results and results["ids"]:
        vector_store.delete(ids=results["ids"])

    return True
```

Ly do: tranh viec lesson da sua tai lieu nhung vector DB van con chunk cu.

## 6. Retrieval pipeline

File chinh: `ai-service/app/services/rag_service.py`

Ham retrieval trung tam:

```python
def retrieve_relevant_chunks(
    lesson_id: int,
    query: str = "",
    top_k: int = 8,
    score_threshold: float | None = None,
    allow_low_confidence_fallback: bool | None = None,
) -> list[RetrievedChunk]:
```

### 6.1 Query retrieval

Neu khong co query, he thong dung query mac dinh:

```python
search_query = query if query.strip() else "lesson content summary"
```

Voi tung chuc nang, query thuong duoc viet theo muc tieu:

- Slide: knowledge, concepts, definitions, examples, procedures.
- Quiz: student knowledge, facts, exercises, formulas.
- Chat: chinh cau hoi cua hoc sinh.
- Autocomplete: 500 ky tu cuoi cua van ban dang viet.

### 6.2 Candidate search

```python
candidate_k = max(top_k, top_k * max(1, settings.rag_candidate_multiplier))
```

Vi du:

- `top_k = 8`
- `rag_candidate_multiplier = 4`
- He thong lay 32 ung vien tu vector search, sau do rerank va cat ve 8 chunk.

Ly do: vector search co the tra ve ket qua gan dung, rerank them giup chon tot hon.

### 6.3 Similarity search

```python
results = vector_store.similarity_search_with_score(
    query=query,
    k=k,
    filter={"lesson_id": str(lesson_id)},
)
```

Filter `lesson_id` rat quan trong. Neu khong filter, he thong co the lay chunk cua bai hoc khac.

Chroma tra ve distance, he thong doi distance thanh relevance:

```python
def _distance_to_relevance(distance: float) -> float:
    if distance < 0:
        return 0.0
    return 1 / (1 + distance)
```

Distance cang nho thi relevance cang cao.

### 6.4 Rerank bang vector score + lexical score

```python
combined_score = (relevance_score * 0.85) + (lexical_score * 0.15)
```

He thong khong chi dua vao vector score, ma cong them lexical overlap:

- `relevance_score`: diem gan nghia tu embedding.
- `lexical_score`: ty le tu khoa trong query xuat hien trong chunk.
- `combined_score`: diem tong hop de sap xep.

Code:

```python
query_terms = _tokenize(query)
lexical_score = _lexical_overlap_score(query_terms, document.page_content)
combined_score = (relevance_score * 0.85) + (lexical_score * 0.15)
```

Y nghia:

- Vector score bat duoc tu dong nghia/ngu nghia.
- Lexical score giup uu tien chunk co trung tu khoa cu the.

### 6.5 Threshold va fallback

```python
passing = [chunk for chunk in reranked if chunk.passed_threshold]

if passing:
    return passing[:top_k]

if not fallback_enabled:
    return []

return reranked[:top_k]
```

Co 2 che do:

- Neu chunk vuot threshold: lay chunk do.
- Neu khong co chunk nao vuot threshold:
  - fallback bat: van lay chunk gan nhat de LLM co context.
  - fallback tat: tra rong.

Fallback huu ich khi tai lieu ngan hoac query khong khop tu khoa. Nhung neu de fallback qua thoang, LLM co the tra loi dua tren context kem lien quan.

### 6.6 Build final context

```python
def _build_context(chunks: list[RetrievedChunk]) -> str:
    context_parts: list[str] = []
    total_chars = 0

    for chunk in chunks:
        content = chunk.document.page_content.strip()

        if total_chars + len(content) > settings.rag_max_context_chars:
            remaining = settings.rag_max_context_chars - total_chars
            if remaining <= 0:
                break
            content = content[:remaining].rstrip()

        context_parts.append(content)
        total_chars += len(content)

    return CONTEXT_SEPARATOR.join(context_parts)
```

Context separator:

```python
CONTEXT_SEPARATOR = "\n\n---\n\n"
```

`rag_max_context_chars` khong phai gioi han tai lieu goc. No la gioi han final context dua vao LLM sau retrieval.

## 7. Sinh slide bang RAG

File:

- `ai-service/app/services/slide_service.py`
- `ai-service/app/prompts/slide_prompts.py`
- `backend/app/Services/LessonService.php`

Luong:

```text
Teacher tao/regenerate lesson slides
  -> Laravel queue job
  -> gather lesson content
  -> index content for RAG
  -> AiServiceClient generateSlides
  -> FastAPI agent slides
  -> retrieve_context top_k=12
  -> filter teacher-oriented context
  -> prompt LLM
  -> parse JSON slides
  -> Laravel map sang PresentationSlide
  -> luu DB
```

AI service:

```python
context = retrieve_context(
    lesson_id=request.lesson_id,
    query=(
        "lesson knowledge, student learning content, concepts, definitions, formulas, "
        "examples, explanations, procedures, key ideas students need to understand"
    ),
    top_k=12,
)
```

Sau do loc bot context mang tinh giao vien:

```python
context = _filter_student_facing_context(context)
```

Prompt goi LLM:

```python
prompt = ChatPromptTemplate.from_messages([
    ("system", SLIDE_SYSTEM_PROMPT),
    ("user", SLIDE_USER_PROMPT),
])

response = await chain.ainvoke({
    "language": request.language,
    "num_slides": request.num_slides,
    "context": context,
    "additional_instructions": additional,
})
```

Parse JSON:

```python
slides = _parse_slides(response.content)
slides = _filter_teacher_oriented_slides(slides)
```

Dang output slide:

```json
{
  "slide_number": 1,
  "title": "Tieu de slide",
  "bullet_points": ["Y 1", "Y 2"],
  "speaker_notes": "Ghi chu thuyet trinh",
  "image_suggestion": "Goi y hinh anh"
}
```

Laravel map ve DB:

```php
return collect($response['slides'])->map(function ($slide, $index) {
  return [
    'order' => $slide['slide_number'] ?? ($index + 1),
    'title' => $slide['title'] ?? '',
    'content' => is_array($slide['bullet_points'] ?? null)
      ? implode("\n", $slide['bullet_points'])
      : ($slide['bullet_points'] ?? ''),
    'notes' => $slide['speaker_notes'] ?? null,
    'layout' => !empty($slide['image_suggestion'] ?? null) ? 'two_column' : 'content',
    'image_prompt' => $slide['image_suggestion'] ?? null,
  ];
})->toArray();
```

## 8. Sinh quiz bang RAG

File:

- `ai-service/app/services/quiz_service.py`
- `ai-service/app/prompts/quiz_prompts.py`
- `backend/app/Services/LessonService.php`

Luong gan giong slide:

```text
Lesson content da index
  -> retrieve_context top_k=12
  -> filter student-facing context
  -> prompt LLM voi response_format json_object
  -> parse questions
  -> filter teacher-oriented questions
  -> Laravel luu Quiz, QuizQuestion, QuizOption
```

Query retrieval:

```python
context = retrieve_context(
    lesson_id=request.lesson_id,
    query=(
        "student knowledge, concepts, definitions, formulas, worked examples, "
        "exercises, facts, principles, content students need to learn"
    ),
    top_k=12,
)
```

Dung JSON mode:

```python
json_llm = llm.bind(response_format={"type": "json_object"})
chain = prompt | json_llm
```

Dang output quiz:

```json
{
  "question_number": 1,
  "content": "Cau hoi",
  "question_type": "multiple_choice",
  "options": [
    {
      "option_text": "Dap an A",
      "is_correct": true,
      "explanation": "Giai thich"
    }
  ],
  "explanation": "Giai thich chung",
  "points": 1
}
```

Laravel map sang DB:

```php
$options = collect($q['options'] ?? [])->map(function ($opt, $optIndex) {
  return [
    'option_text' => $opt['option_text'] ?? '',
    'is_correct' => $opt['is_correct'] ?? false,
    'order' => $optIndex + 1,
    'explanation' => $opt['explanation'] ?? null,
  ];
})->toArray();
```

## 9. Chat ho tro hoc sinh bang RAG

File:

- `ai-service/app/services/chat_service.py`
- `backend/app/Services/StudentLessonService.php`

Luong:

```text
Hoc sinh hoi cau hoi
  -> Laravel kiem tra hoc sinh thuoc lop
  -> build quiz_context da sanitize
  -> goi AI chat agent
  -> retrieve context theo message
  -> dua context + quiz_context + history vao LLM
  -> tra answer va sources
```

Retrieve context theo cau hoi hoc sinh:

```python
context, sources = retrieve_context_with_sources(
    lesson_id=request.lesson_id,
    query=request.message,
    top_k=5,
)
```

Ngu canh hoi thoai chi lay 6 tin gan nhat:

```python
for msg in request.conversation_history[-6:]:
    history_lines.append(f"{msg.role}: {msg.content}")
```

Tra ve sources:

```python
sources = [_format_source(chunk) for chunk in chunks]
```

Format source:

```python
return (
    f"{source_name} | chunk {chunk_index} | score {chunk.relevance_score:.2f} "
    f"| {confidence}: {excerpt}..."
)
```

Bao mat quiz context: Laravel khong gui `is_correct` va explanation dap an cho AI khi hoc sinh chat:

```php
foreach ($question->options as $option) {
  $lines[] = "Option {$option->order}: {$option->option_text}";
}
```

Ly do: tranh AI tiet lo dap an dung cho hoc sinh.

## 10. Autocomplete giao an

File:

- `ai-service/app/services/autocomplete_service.py`
- `backend/app/Services/AiServiceClient.php`

Autocomplete khac slide/quiz/chat o cho:

- Input la text giao vien dang viet.
- Neu co `lesson_id`, he thong retrieve context tu RAG.
- Chi gui phan duoi cua text hien tai de tiet kiem token.

Code:

```python
QUERY_TAIL_LENGTH = 500
MAX_CONTEXT_LENGTH = 1500

query_text = text[-QUERY_TAIL_LENGTH:]
current_text = text[-MAX_CONTEXT_LENGTH:]
```

Neu co lesson:

```python
rag_context = retrieve_context(
    lesson_id=request.lesson_id,
    query=query_text,
    top_k=3,
)
```

Muc tieu: goi y tiep noi doan giao an, khong phai sinh toan bo bai hoc.

## 11. Agent architecture

AI service co agent registry de chuan hoa cach goi cac chuc nang AI.

File:

- `ai-service/app/agents/base.py`
- `ai-service/app/agents/registry.py`
- `ai-service/app/api/agents.py`

Base agent:

```python
class BaseAgent(ABC):
    name: str
    description: str
    request_model: type[BaseModel]

    @abstractmethod
    async def run(self, payload: BaseModel) -> BaseModel:
        raise NotImplementedError

    def validate_payload(self, payload: dict[str, Any]) -> BaseModel:
        return self.request_model.model_validate(payload)
```

Registry:

```python
agent_registry = AgentRegistry([
    ChatAgent(),
    SlideAgent(),
    ImageAgent(),
    QuizAgent(),
    CompetencyReportAgent(),
    AutocompleteAgent(),
])
```

API execute:

```python
@router.post("/execute", response_model=AgentExecuteResponse)
async def execute_agent_endpoint(request: AgentExecuteRequest):
    result = await execute_agent(request.agent, request.payload)
    result_data = result.model_dump(by_alias=True)
    success = bool(result_data.get("success", True))
    return AgentExecuteResponse(
        agent=request.agent,
        success=success,
        result=result_data,
    )
```

Laravel goi chung:

```php
public function invokeAgent(string $agent, array $payload): ?array
{
  $response = Http::timeout($this->timeout)
    ->withHeaders($this->headers())
    ->post("{$this->baseUrl}/api/agents/execute", [
      'agent' => $agent,
      'payload' => $payload,
    ]);

  return $response->json();
}
```

Loi ich:

- Them agent moi de dang.
- Tat ca request duoc validate bang Pydantic schema.
- Backend chi can biet ten agent va payload.

## 12. Laravel orchestration va queue

File:

- `backend/app/Services/LessonService.php`
- `backend/app/Jobs/GenerateLessonAiContentJob.php`
- `backend/app/Services/AiServiceClient.php`

Khi tao lesson, backend khong bat user doi AI generate xong ngay. No tao batch va dispatch queue:

```php
$batch = LessonAiGenerationBatch::create([
  'lesson_id' => $lesson->id,
  'teacher_id' => $teacherId,
  'type' => $type,
  'status' => 'queued',
  'progress' => 0,
  'slide_count' => (int) ($options['slide_count'] ?? 10),
  'question_count' => (int) ($options['question_count'] ?? 5),
  'options' => $options,
  'message' => 'Waiting for queue worker',
]);

GenerateLessonAiContentJob::dispatch($batch->id);
```

Job:

```php
class GenerateLessonAiContentJob implements ShouldQueue
{
  public int $timeout = 1200;
  public int $tries = 1;

  public function handle(LessonService $lessonService): void
  {
    $lessonService->processAiGenerationBatch($this->batchId);
  }
}
```

Trong batch:

```php
$batch->update(['progress' => 20, 'message' => 'Indexing lesson content']);
$this->indexContentForRAG($lesson->id, $contentText);

$batch->update(['progress' => 35, 'message' => 'Generating slides and quiz']);
$result = $this->generateAIContent($lesson, $contentText, $batch->options ?? []);
```

Co fallback:

```php
if (empty($slides)) {
  $slides = $this->openAIService->generatePresentationSlides(
    $contentText,
    $lesson->title,
    $slideCount
  );
  $result['method'] = 'direct';
}
```

Nghia la RAG la pipeline uu tien. Neu AI service loi hoac khong tra ket qua, backend co the fallback sang goi OpenAI truc tiep.

## 13. Bao mat ket noi Laravel - AI Service

FastAPI route duoc bao ve bang `verify_api_secret`.

Trong `main.py`:

```python
app.include_router(
    documents_router,
    prefix="/api/documents",
    tags=["Documents"],
    dependencies=[Depends(verify_api_secret)],
)
```

Laravel gui header:

```php
private function headers(): array
{
  return [
    'Accept' => 'application/json',
    'X-API-Secret' => $this->secret,
  ];
}
```

Y nghia: frontend khong goi truc tiep Python AI Service. Moi request AI di qua Laravel, Laravel xac thuc user va gui secret noi bo sang AI service.

## 14. RAG Sandbox

RAG Sandbox la man hinh test truc quan, khong anh huong lesson that.

File:

- Frontend: `website/src/pages/teacher/RagSandboxPage.vue`
- Settings UI: `website/src/components/ragSandbox/RagSandboxSettings.vue`
- Frontend service: `website/src/services/ragSandbox.js`
- Backend controller: `backend/app/Http/Controllers/RagSandboxController.php`
- AI endpoint: `ai-service/app/api/rag_sandbox.py`

Muc tieu:

- Giao vien upload file test.
- Chinh chunk size, overlap, top K, threshold, max context chars.
- Xem file duoc split thanh chunk nhu nao.
- Test retrieval bang query.
- Xem final context gui vao LLM.
- Preview JSON slide/quiz.
- Khong ghi vao lesson/quiz/slide that.

### 14.1 Sandbox khac production RAG nhu the nao

Production RAG:

- Dung `lesson_id`.
- Chunk duoc luu de sinh slide/quiz/chat cho bai hoc that.
- Cau hinh lay tu `.env`.
- Output co the duoc luu vao DB.

Sandbox:

- Dung `sandbox_id`.
- Chunk luu tam trong ChromaDB bang metadata `sandbox_id`.
- Cau hinh lay tu UI.
- Output slide/quiz chi preview JSON, khong luu DB.

Sandbox settings:

```python
class SandboxSettings(BaseModel):
    chunk_size: int = Field(default=1000, ge=300, le=3000)
    chunk_overlap: int = Field(default=200, ge=0, le=1000)
    top_k: int = Field(default=5, ge=1, le=12)
    score_threshold: float = Field(default=0.45, ge=0, le=1)
    max_context_chars: int = Field(default=12000, ge=1000, le=30000)
    low_confidence_fallback: bool = True
```

### 14.2 Process sandbox document

```python
sandbox_id = f"sandbox-{uuid.uuid4()}"
splitter = RecursiveCharacterTextSplitter(
    chunk_size=settings.chunk_size,
    chunk_overlap=settings.normalized_overlap(),
    length_function=len,
    separators=["\n\n", "\n", ". ", " ", ""],
)
```

Metadata:

```python
metadatas=[{
    "sandbox_id": sandbox_id,
    "source_name": filename,
    "source_type": content_type,
    "chunk_size": str(settings.chunk_size),
    "chunk_overlap": str(settings.normalized_overlap()),
}]
```

Filter retrieval theo sandbox:

```python
results = vector_store.similarity_search_with_score(
    query=query,
    k=k,
    filter={"sandbox_id": sandbox_id},
)
```

### 14.3 Debug overlap

Overlap nam o dau chunk hien tai va trung voi duoi chunk truoc.

Code build preview:

```python
overlap_text = _find_prefix_overlap(previous_content, content, max_overlap_chars) if previous_content else ""
body_text = content[len(overlap_text):]
```

So sanh exact tail va exact start:

```python
previous_tail_preview=_preview_text(previous_content[-len(overlap_text):], limit=260) if overlap_text else "",
overlap_preview=_preview_text(overlap_text, limit=260) if overlap_text else "",
body_preview=_preview_text(body_text or content, limit=500),
```

Ly do tren UI co the thay overlap "khong giong" preview chunk truoc: preview chunk truoc thuong hien phan dau chunk, trong khi overlap nam o cuoi chunk truoc.

### 14.4 Generate slide/quiz JSON trong sandbox

Slide sandbox:

```python
retrieval = _retrieve_sandbox(request.sandbox_id, request.query, request.settings)
context = retrieval["context"]

response = await (prompt | get_llm()).ainvoke({
    "language": request.language,
    "num_slides": request.count,
    "context": context,
    "additional_instructions": "Return JSON only for sandbox preview.",
})
```

Quiz sandbox:

```python
response = await (prompt | get_llm().bind(response_format={"type": "json_object"})).ainvoke({
    "language": request.language,
    "difficulty": request.difficulty,
    "num_questions": request.count,
    "context": context,
    "additional_instructions": "Return JSON only for sandbox preview.",
})
```

## 15. Giai thich cac tham so RAG de bao cao

### 15.1 Chunk size

La do dai moi chunk sau khi cat tai lieu.

Chunk size nho:

- Uu diem: retrieval chi tiet, bat duoc y nho.
- Nhuoc diem: de mat ngu canh, sinh slide/quiz co the thieu lien ket.

Chunk size lon:

- Uu diem: giu duoc ngu canh day du.
- Nhuoc diem: chunk nhieu y, retrieval kem chinh xac hon, ton token hon.

Trong do an: default `1000`.

### 15.2 Chunk overlap

La phan lap lai giua 2 chunk lien tiep.

Vi du:

```text
Chunk 1: A B C D E
Chunk 2: D E F G H
Overlap: D E
```

Overlap giup khong mat y o ranh gioi khi mot cau/khai niem bi cat ngang.

Overlap cao:

- Tot hon khi tai lieu dai, cau dai.
- Nhung tao nhieu noi dung trung lap, tang so chunk va chi phi embedding.

Trong do an: default `200`.

### 15.3 Top K

So chunk cuoi cung duoc lay de build context.

Top K thap:

- Context gon.
- It nhieu hon, nhanh hon.
- Co the thieu y.

Top K cao:

- Nhieu thong tin hon.
- Ton token hon.
- Co nguy co lay ca chunk khong lien quan.

Production slide/quiz dang dung `top_k=12`. Chat dung `top_k=5`. Autocomplete dung `top_k=3`.

### 15.4 Score threshold

Nguong relevance toi thieu.

Threshold cao:

- Chi lay chunk rat lien quan.
- De tra rong neu query khong khop.

Threshold thap:

- De co context hon.
- Co nguy co lay chunk kem lien quan.

Trong do an: default `0.45`.

### 15.5 Candidate multiplier

So ung vien ban dau lay tu vector DB truoc khi rerank.

```text
candidate_k = top_k * rag_candidate_multiplier
```

Neu `top_k=8`, multiplier `4`, he thong lay 32 candidate roi sap xep lai.

### 15.6 Max context chars

Gioi han tong so ky tu final context dua vao LLM.

Dieu can noi khi bao cao:

- RAG khong gui toan bo tai lieu.
- Nhưng cac chunk retrieve duoc van phai dua vao prompt.
- `max_context_chars` giup kiem soat token, toc do va chi phi.

`12000 chars` phu hop cho context vua. Sandbox cho test toi `30000 chars`.

### 15.7 Low-confidence fallback

Neu tat ca chunk duoi threshold:

- Bat fallback: van lay chunk gan nhat.
- Tat fallback: khong lay chunk nao.

Trong demo, bat fallback giup nguoi dung thay retrieval van co ket qua. Trong production, fallback giup slide/quiz/chat bot it bi loi "khong co context", nhung can threshold hop ly.

## 16. Diem manh cua thiet ke

- Tach backend nghiep vu va AI service ro rang.
- RAG indexing dung chung cho slide, quiz, chat, autocomplete.
- Vector DB co metadata `lesson_id`, tranh lan noi dung giua cac bai hoc.
- Co queue cho tac vu AI lau.
- Co fallback khi RAG/AI service loi.
- Co sanitize quiz context de khong lo dap an cho hoc sinh.
- Co RAG Sandbox de giai thich truc quan cho hoi dong thay RAG hoat dong.
- Co JSON parsing va schema Pydantic giup output co cau truc.

## 17. Han che va huong cai tien

Han che hien tai:

- Chunking tinh theo ky tu, chua tinh token that.
- Rerank dang don gian: 85% vector score + 15% lexical score.
- Chua co cross-encoder reranker.
- Chua co citation den tung slide/quiz question.
- Chua co automatic evaluation cho chat/quiz quality.
- ChromaDB dang dung local persist, can chinh khi deploy production lon.

Huong cai tien:

- Dung token-based splitter de kiem soat context chinh xac hon.
- Them reranker model rieng cho retrieval.
- Luu source chunk cho tung slide/quiz question de giai thich duoc AI dua vao dau.
- Them dashboard danh gia retrieval: precision, recall, chunk score.
- Them cache cho query retrieval pho bien.
- Them cleanup sandbox chunks theo thoi gian.

## 18. Cach trinh bay khi bao cao

Co the trinh bay theo thu tu:

1. Bai toan: giao vien upload tai lieu, he thong tao slide/quiz/chat dua tren tai lieu.
2. Van de neu gui truc tiep toan bo tai lieu: ton token, cham, de qua context window, kho kiem soat nguon.
3. Giai phap RAG:
   - extract text
   - chunking
   - embedding
   - vector DB
   - retrieve
   - build context
   - generation
4. Demo RAG Sandbox:
   - upload cung mot tai lieu
   - doi chunk size/overlap/top K/threshold
   - xem chunks, overlap, retrieved chunks, final context
   - sinh slide/quiz JSON preview
5. Demo production:
   - tao lesson
   - queue AI generation
   - sinh slide/quiz
   - hoc sinh chat theo bai hoc

Mot cau giai thich ngan gon:

> He thong cua em khong dua toan bo tai lieu vao LLM. Em tach tai lieu thanh cac chunk, bien moi chunk thanh vector embedding va luu vao ChromaDB. Khi can sinh slide, quiz hoac tra loi chat, he thong truy van cac chunk lien quan nhat theo lesson_id, loc va sap xep theo relevance score, ghep thanh final context roi moi dua vao OpenAI. Nho vay ket qua bam sat tai lieu, giam token va co the debug duoc AI dang dua vao phan noi dung nao.

## 19. Checklist file code quan trong

AI Service:

- `ai-service/main.py`: dang ky router FastAPI va khoi tao service.
- `ai-service/app/core/config.py`: cau hinh model, embedding, chunking, RAG.
- `ai-service/app/core/dependencies.py`: khoi tao ChatOpenAI, OpenAIEmbeddings, Chroma.
- `ai-service/app/services/document_processor.py`: extract PDF/DOCX/TXT va OCR fallback.
- `ai-service/app/services/rag_service.py`: chunking, store, retrieve, rerank, build context.
- `ai-service/app/services/slide_service.py`: sinh slide bang RAG.
- `ai-service/app/services/quiz_service.py`: sinh quiz bang RAG.
- `ai-service/app/services/chat_service.py`: chat hoc sinh bang RAG.
- `ai-service/app/services/autocomplete_service.py`: autocomplete giao an.
- `ai-service/app/api/rag_sandbox.py`: sandbox test RAG truc quan.
- `ai-service/app/agents/registry.py`: agent registry.

Laravel Backend:

- `backend/app/Services/AiServiceClient.php`: client goi Python AI Service.
- `backend/app/Services/LessonService.php`: tao lesson, queue, index RAG, sinh slide/quiz, fallback.
- `backend/app/Jobs/GenerateLessonAiContentJob.php`: queue job sinh AI content.
- `backend/app/Services/StudentLessonService.php`: chat hoc sinh va sanitize quiz context.
- `backend/app/Http/Controllers/RagSandboxController.php`: proxy RAG Sandbox.
- `backend/routes/api.php`: routes lesson, chat, sandbox.

Frontend:

- `website/src/pages/teacher/RagSandboxPage.vue`: UI test RAG.
- `website/src/components/ragSandbox/RagSandboxSettings.vue`: UI tham so RAG.
- `website/src/services/ragSandbox.js`: API client sandbox.

