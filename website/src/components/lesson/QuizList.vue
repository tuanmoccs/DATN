<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-800">Quizzes</h3>
      <button @click="handleRegenerateQuiz" :disabled="regenerating"
        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium disabled:opacity-50">
        <i :class="regenerating ? 'fas fa-spinner fa-spin' : 'fas fa-magic'"></i>
        {{ regenerating ? 'Generating...' : 'Generate New Quiz with AI' }}
      </button>
    </div>

    <!-- Regenerating -->
    <div v-if="regenerating" class="p-6 bg-green-50 border border-green-200 rounded-xl mb-6">
      <div class="flex items-center gap-3">
        <i class="fas fa-robot text-green-600 text-xl animate-bounce"></i>
        <div>
          <p class="text-sm font-medium text-green-700">AI is generating quiz questions...</p>
          <p class="text-xs text-green-500">This may take 20-40 seconds. Please wait.</p>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="quizzes.length === 0 && !regenerating" class="text-center py-12 bg-gray-50 rounded-xl">
      <i class="fas fa-question-circle text-4xl text-gray-300 mb-3"></i>
      <h4 class="text-base font-semibold text-gray-600 mb-1">No quizzes yet</h4>
      <p class="text-sm text-gray-400">Click "Generate New Quiz with AI" to create quiz questions</p>
    </div>

    <!-- Quiz Cards -->
    <div v-else class="space-y-4">
      <div v-for="quiz in quizzes" :key="quiz.id" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <!-- Quiz Header -->
        <div class="p-5 border-b border-gray-100">
          <div class="flex items-start justify-between">
            <div>
              <h4 class="text-base font-semibold text-gray-800">{{ quiz.title }}</h4>
              <p class="text-sm text-gray-500 mt-0.5">{{ quiz.description }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span
                :class="quiz.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                class="px-2.5 py-1 rounded-full text-xs font-medium">
                {{ quiz.status === 'published' ? 'Published' : 'Draft' }}
              </span>
              <span v-if="quiz.auto_generated"
                class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                <i class="fas fa-robot mr-1"></i>AI Generated
              </span>
            </div>
          </div>

          <!-- Quiz Stats -->
          <div class="flex items-center gap-5 mt-3 text-sm text-gray-500">
            <span><i class="fas fa-list-ol mr-1"></i> {{ quiz.questions?.length || 0 }} questions</span>
            <span v-if="quiz.time_limit"><i class="fas fa-clock mr-1"></i> {{ quiz.time_limit }} min</span>
            <span><i class="fas fa-redo mr-1"></i> {{ quiz.max_attempts }} attempt(s)</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="p-3 bg-gray-50 flex items-center gap-2">
          <button @click="viewQuiz(quiz)"
            class="px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium">
            <i class="fas fa-eye mr-1"></i> View & Edit Questions
          </button>
          <button v-if="quiz.status === 'draft'" @click="publishQuiz(quiz)"
            class="px-3 py-1.5 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors font-medium">
            <i class="fas fa-check-circle mr-1"></i> Publish
          </button>
          <button @click="confirmDeleteQuiz(quiz)"
            class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors font-medium ml-auto">
            <i class="fas fa-trash mr-1"></i> Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Quiz Detail Modal -->
    <Teleport to="body">
      <div v-if="selectedQuiz" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="selectedQuiz = null"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-5 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div>
              <h3 class="text-lg font-bold text-gray-800">{{ selectedQuiz.title }}</h3>
              <p class="text-sm text-gray-500">{{ selectedQuiz.questions?.length || 0 }} questions</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="showAddQuestion = true"
                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-1"></i> Add Question
              </button>
              <button @click="selectedQuiz = null" class="text-gray-400 hover:text-gray-600 p-2">
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>
          </div>

          <!-- Questions -->
          <div class="p-5 space-y-6">
            <div v-for="(question, qIdx) in selectedQuiz.questions" :key="question.id"
              class="bg-gray-50 rounded-xl p-5 border border-gray-200">

              <!-- Question Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-start gap-3 flex-1">
                  <span
                    class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">
                    {{ qIdx + 1 }}
                  </span>
                  <div v-if="editingQuestion !== question.id" class="flex-1">
                    <p class="text-sm font-medium text-gray-800">{{ question.content }}</p>
                    <p v-if="question.explanation" class="text-xs text-gray-500 mt-1 italic">{{ question.explanation }}
                    </p>
                  </div>
                  <div v-else class="flex-1 space-y-3">
                    <textarea v-model="editForm.content" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                    <input v-model="editForm.explanation" placeholder="Explanation (optional)"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                    <input v-model.number="editForm.points" type="number" min="1" max="100" placeholder="Points"
                      class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                  </div>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                  <span class="text-xs text-gray-400 mr-2">{{ question.points }} pts</span>
                  <template v-if="editingQuestion !== question.id">
                    <button @click="startEdit(question)"
                      class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                      <i class="fas fa-pen text-xs"></i>
                    </button>
                    <button @click="handleDeleteQuestion(question)"
                      class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                      <i class="fas fa-trash text-xs"></i>
                    </button>
                  </template>
                  <template v-else>
                    <button @click="saveEdit(question)" :disabled="saving"
                      class="p-1.5 text-green-600 hover:text-green-700 transition-colors" title="Save">
                      <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-check'" class="text-xs"></i>
                    </button>
                    <button @click="cancelEdit" class="p-1.5 text-gray-400 hover:text-gray-600 transition-colors"
                      title="Cancel">
                      <i class="fas fa-times text-xs"></i>
                    </button>
                  </template>
                </div>
              </div>

              <!-- Options -->
              <div class="space-y-2 ml-11">
                <template v-if="editingQuestion !== question.id">
                  <div v-for="option in question.options" :key="option.id"
                    :class="option.is_correct ? 'bg-green-50 border-green-300' : 'bg-white border-gray-200'"
                    class="flex items-center gap-3 p-3 rounded-lg border text-sm">
                    <i
                      :class="option.is_correct ? 'fas fa-check-circle text-green-600' : 'far fa-circle text-gray-400'"></i>
                    <span :class="option.is_correct ? 'text-green-700 font-medium' : 'text-gray-700'">{{
                      option.option_text }}</span>
                    <span v-if="option.explanation" class="text-xs text-gray-400 ml-auto italic">{{ option.explanation
                      }}</span>
                  </div>
                </template>
                <template v-else>
                  <div v-for="(opt, oIdx) in editForm.options" :key="oIdx"
                    class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-white">
                    <button type="button" @click="toggleCorrect(oIdx)"
                      :class="opt.is_correct ? 'text-green-600' : 'text-gray-400'" class="transition-colors">
                      <i :class="opt.is_correct ? 'fas fa-check-circle' : 'far fa-circle'"></i>
                    </button>
                    <input v-model="opt.option_text"
                      class="flex-1 px-2 py-1 border-b border-gray-200 text-sm focus:border-blue-500 outline-none" />
                    <button v-if="editForm.options.length > 2" type="button" @click="editForm.options.splice(oIdx, 1)"
                      class="text-red-400 hover:text-red-600">
                      <i class="fas fa-times text-xs"></i>
                    </button>
                  </div>
                  <button type="button" @click="addOptionToEdit"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-1">
                    <i class="fas fa-plus mr-1"></i> Add Option
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add Question Modal -->
    <Teleport to="body">
      <div v-if="showAddQuestion" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAddQuestion = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Add New Question</h3>
            <button @click="showAddQuestion = false" class="text-gray-400 hover:text-gray-600">
              <i class="fas fa-times text-lg"></i>
            </button>
          </div>

          <form @submit.prevent="handleAddQuestion" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Question <span
                  class="text-red-500">*</span></label>
              <textarea v-model="newQuestion.content" rows="3" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Explanation</label>
              <input v-model="newQuestion.explanation"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Points</label>
              <input v-model.number="newQuestion.points" type="number" min="1" max="100"
                class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
              <div class="space-y-2">
                <div v-for="(opt, idx) in newQuestion.options" :key="idx" class="flex items-center gap-3">
                  <button type="button" @click="newQuestion.options.forEach((o, i) => o.is_correct = i === idx)"
                    :class="opt.is_correct ? 'text-green-600' : 'text-gray-400'">
                    <i :class="opt.is_correct ? 'fas fa-check-circle' : 'far fa-circle'"></i>
                  </button>
                  <input v-model="opt.option_text" placeholder="Option text" required
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                  <button v-if="newQuestion.options.length > 2" type="button"
                    @click="newQuestion.options.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <button type="button" @click="addNewOption"
                class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-2">
                <i class="fas fa-plus mr-1"></i> Add Option
              </button>
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button type="button" @click="showAddQuestion = false"
                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                Cancel
              </button>
              <button type="submit" :disabled="addingQuestion"
                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm disabled:opacity-50">
                <i v-if="addingQuestion" class="fas fa-spinner fa-spin mr-1"></i>
                {{ addingQuestion ? 'Adding...' : 'Add Question' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirm Modal -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Delete</h3>
          <p class="text-sm text-gray-600 mb-5">Are you sure you want to delete this quiz? This action cannot be undone.
          </p>
          <div class="flex items-center gap-3">
            <button @click="deleteTarget = null"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
              Cancel
            </button>
            <button @click="handleDeleteQuiz" :disabled="deleting"
              class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm disabled:opacity-50">
              <i v-if="deleting" class="fas fa-spinner fa-spin mr-1"></i>
              Delete
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useApi } from '@/plugins/api'

const props = defineProps({
  quizzes: { type: Array, default: () => [] },
  lessonId: { type: Number, required: true },
})

const emit = defineEmits(['refresh', 'toast'])

const api = useApi()
const regenerating = ref(false)
const selectedQuiz = ref(null)
const editingQuestion = ref(null)
const saving = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)
const showAddQuestion = ref(false)
const addingQuestion = ref(false)

const editForm = reactive({
  content: '',
  explanation: '',
  points: 10,
  options: [],
})

const newQuestion = reactive({
  content: '',
  explanation: '',
  points: 10,
  options: [
    { option_text: '', is_correct: true, order: 1 },
    { option_text: '', is_correct: false, order: 2 },
    { option_text: '', is_correct: false, order: 3 },
    { option_text: '', is_correct: false, order: 4 },
  ],
})

const viewQuiz = async (quiz) => {
  try {
    const res = await api.lesson.getQuizDetail(quiz.id)
    selectedQuiz.value = res.data
  } catch {
    emit('toast', 'Failed to load quiz details', 'error')
  }
}

const startEdit = (question) => {
  editingQuestion.value = question.id
  editForm.content = question.content
  editForm.explanation = question.explanation || ''
  editForm.points = question.points
  editForm.options = (question.options || []).map(o => ({
    option_text: o.option_text,
    is_correct: o.is_correct,
    order: o.order,
    explanation: o.explanation || '',
  }))
}

const cancelEdit = () => {
  editingQuestion.value = null
}

const toggleCorrect = (idx) => {
  editForm.options.forEach((o, i) => { o.is_correct = i === idx })
}

const addOptionToEdit = () => {
  editForm.options.push({ option_text: '', is_correct: false, order: editForm.options.length + 1 })
}

const saveEdit = async (question) => {
  saving.value = true
  try {
    await api.lesson.updateQuestion(selectedQuiz.value.id, question.id, {
      content: editForm.content,
      explanation: editForm.explanation,
      points: editForm.points,
      options: editForm.options.map((o, i) => ({ ...o, order: i + 1 })),
    })
    // Refresh quiz
    const res = await api.lesson.getQuizDetail(selectedQuiz.value.id)
    selectedQuiz.value = res.data
    editingQuestion.value = null
    emit('toast', 'Question updated successfully!')
  } catch {
    emit('toast', 'Failed to update question', 'error')
  } finally {
    saving.value = false
  }
}

const handleDeleteQuestion = async (question) => {
  if (!confirm('Are you sure you want to delete this question?')) return
  try {
    await api.lesson.deleteQuestion(selectedQuiz.value.id, question.id)
    const res = await api.lesson.getQuizDetail(selectedQuiz.value.id)
    selectedQuiz.value = res.data
    emit('toast', 'Question deleted!')
  } catch {
    emit('toast', 'Failed to delete question', 'error')
  }
}

const handleAddQuestion = async () => {
  addingQuestion.value = true
  try {
    await api.lesson.addQuestion(selectedQuiz.value.id, {
      content: newQuestion.content,
      explanation: newQuestion.explanation,
      points: newQuestion.points,
      options: newQuestion.options.map((o, i) => ({ ...o, order: i + 1 })),
    })
    const res = await api.lesson.getQuizDetail(selectedQuiz.value.id)
    selectedQuiz.value = res.data
    showAddQuestion.value = false
    resetNewQuestion()
    emit('toast', 'Question added successfully!')
  } catch {
    emit('toast', 'Failed to add question', 'error')
  } finally {
    addingQuestion.value = false
  }
}

const resetNewQuestion = () => {
  newQuestion.content = ''
  newQuestion.explanation = ''
  newQuestion.points = 10
  newQuestion.options = [
    { option_text: '', is_correct: true, order: 1 },
    { option_text: '', is_correct: false, order: 2 },
    { option_text: '', is_correct: false, order: 3 },
    { option_text: '', is_correct: false, order: 4 },
  ]
}

const addNewOption = () => {
  newQuestion.options.push({ option_text: '', is_correct: false, order: newQuestion.options.length + 1 })
}

const confirmDeleteQuiz = (quiz) => {
  deleteTarget.value = quiz
}

const handleDeleteQuiz = async () => {
  deleting.value = true
  try {
    await api.lesson.deleteQuiz(deleteTarget.value.id)
    deleteTarget.value = null
    emit('toast', 'Quiz deleted!')
    emit('refresh')
  } catch {
    emit('toast', 'Failed to delete quiz', 'error')
  } finally {
    deleting.value = false
  }
}

const publishQuiz = async (quiz) => {
  try {
    await api.lesson.publishQuiz(quiz.id)
    emit('toast', 'Quiz published!')
    emit('refresh')
  } catch {
    emit('toast', 'Failed to publish quiz', 'error')
  }
}

const handleRegenerateQuiz = async () => {
  regenerating.value = true
  try {
    await api.lesson.regenerateQuiz(props.lessonId)
    emit('toast', 'Quiz generated successfully!')
    emit('refresh')
  } catch (err) {
    emit('toast', err.response?.data?.message || 'Failed to generate quiz', 'error')
  } finally {
    regenerating.value = false
  }
}
</script>
