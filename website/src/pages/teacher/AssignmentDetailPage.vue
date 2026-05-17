<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
    </div>

    <template v-else-if="assignment">
      <!-- Back + Header -->
      <div class="mb-6">
        <button @click="$router.back()" class="text-sm text-gray-500 hover:text-blue-600 mb-3 flex items-center gap-1">
          <i class="fas fa-arrow-left"></i> Return to Assignments
        </button>
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ assignment.title }}</h2>
            <p class="text-gray-500 mt-1">{{ assignment.description || 'No description' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="statusClass(assignment.status)" class="px-3 py-1.5 rounded-full text-xs font-medium">
              {{ statusLabel(assignment.status) }}
            </span>
            <button @click="showEditModal = true"
              class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 font-medium">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
            <button @click="confirmDelete"
              class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600 hover:bg-red-100 font-medium">
              <i class="fas fa-trash mr-1"></i> Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Assignment Info Cards -->
      <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border p-4">
          <div class="text-sm text-gray-500 mb-1">Due Date</div>
          <div class="font-semibold text-gray-800">{{ formatDate(assignment.due_date) }}</div>
          <div v-if="isOverdue" class="text-xs text-red-500 mt-1"><i
              class="fas fa-exclamation-triangle mr-1"></i>Overdue</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-sm text-gray-500 mb-1">Max Score</div>
          <div class="font-semibold text-gray-800">{{ assignment.max_score }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-sm text-gray-500 mb-1">Submitted</div>
          <div class="font-semibold text-blue-600">{{ assignment.submissions?.length || 0 }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-sm text-gray-500 mb-1">Graded</div>
          <div class="font-semibold text-green-600">{{ gradedCount }}</div>
        </div>
      </div>

      <!-- Instructions -->
      <div v-if="assignment.instructions" class="bg-white rounded-xl border p-5 mb-6">
        <h3 class="font-semibold text-gray-800 mb-2"><i class="fas fa-info-circle text-blue-500 mr-2"></i>Instructions
        </h3>
        <div class="text-sm text-gray-600 whitespace-pre-wrap">{{ assignment.instructions }}</div>
      </div>

      <!-- Attached Files -->
      <div v-if="assignment.files?.length" class="bg-white rounded-xl border p-5 mb-6">
        <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-paperclip text-gray-400 mr-2"></i>Attached Files
        </h3>
        <div class="space-y-2">
          <a v-for="file in assignment.files" :key="file.id" :href="getFileUrl(file.file_path)" target="_blank"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <i :class="getFileIcon(file.file_name)" class="text-lg"></i>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-700 truncate">{{ file.file_name }}</div>
              <div class="text-xs text-gray-400">{{ formatFileSize(file.file_size) }}</div>
            </div>
            <i class="fas fa-download text-gray-400"></i>
          </a>
        </div>
      </div>

      <!-- Tabs: Submissions -->
      <div class="bg-white rounded-xl border">
        <div class="border-b px-5 py-3">
          <h3 class="font-semibold text-gray-800">
            <i class="fas fa-inbox text-blue-500 mr-2"></i>Submissions ({{ assignment.submissions?.length || 0 }})
          </h3>
        </div>

        <div v-if="!assignment.submissions?.length" class="text-center py-12 text-gray-400">
          <i class="fas fa-inbox text-3xl mb-3"></i>
          <p>No submissions yet</p>
        </div>

        <div v-else class="divide-y">
          <div v-for="submission in assignment.submissions" :key="submission.id"
            class="p-5 hover:bg-gray-50 transition-colors cursor-pointer" @click="openSubmissionDetail(submission)">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-medium text-sm">{{ getInitials(submission.student?.name) }}</span>
                </div>
                <div>
                  <div class="font-medium text-gray-800">{{ submission.student?.name || 'Student' }}</div>
                  <div class="text-xs text-gray-400">{{ formatDate(submission.submitted_at) }}</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span v-if="submission.is_late"
                  class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs font-medium">Late</span>
                <span :class="submissionStatusClass(submission.status)"
                  class="px-2.5 py-1 rounded-full text-xs font-medium">
                  {{ submissionStatusLabel(submission.status) }}
                </span>
                <div v-if="submission.grading" class="text-right">
                  <div class="font-bold text-gray-800">{{ submission.grading.score }}/{{ submission.grading.max_score }}
                  </div>
                  <div class="text-xs text-gray-400">{{ submission.grading.percentage }}%</div>
                </div>
                <div v-else class="text-sm text-gray-400">Not graded</div>
                <i class="fas fa-chevron-right text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Submission Detail Modal -->
    <Teleport to="body">
      <div v-if="showSubmissionModal && selectedSubmission"
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showSubmissionModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl z-10">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-800">Submission by {{ selectedSubmission.student?.name }}
                </h3>
                <p class="text-sm text-gray-500">{{ formatDate(selectedSubmission.submitted_at) }}
                  <span v-if="selectedSubmission.is_late" class="text-red-500 ml-2">(Late)</span>
                </p>
              </div>
              <button @click="showSubmissionModal = false" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>
          </div>

          <div class="p-6">
            <!-- Student Attachments -->
            <div class="mb-6">
              <h4 class="font-medium text-gray-800 mb-3"><i class="fas fa-file-alt text-blue-500 mr-2"></i>Answer Files
              </h4>
              <div v-if="selectedSubmission.attachments?.length" class="grid grid-cols-2 gap-3">
                <a v-for="att in selectedSubmission.attachments" :key="att.id" :href="getFileUrl(att.file_path)"
                  target="_blank"
                  class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border">
                  <!-- Image preview -->
                  <template v-if="att.file_type === 'image'">
                    <img :src="getFileUrl(att.file_path)" class="w-16 h-16 object-cover rounded" />
                  </template>
                  <template v-else>
                    <i :class="getFileIcon(att.file_name)" class="text-2xl"></i>
                  </template>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-700 truncate">{{ att.file_name }}</div>
                    <div class="text-xs text-gray-400">{{ formatFileSize(att.file_size) }}</div>
                  </div>
                </a>
              </div>
              <p v-else class="text-sm text-gray-400">No attached files</p>
            </div>

            <!-- AI Grading Section -->
            <div class="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-5 border border-purple-200">
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-medium text-gray-800">
                  <i class="fas fa-robot text-purple-500 mr-2"></i>AI Suggested Grading
                </h4>
                <button @click="requestAIGrade(selectedSubmission.id)" :disabled="aiGrading"
                  class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 disabled:bg-gray-400 font-medium">
                  <i :class="aiGrading ? 'fas fa-spinner fa-spin' : 'fas fa-magic'" class="mr-1"></i>
                  {{ aiGrading ? 'Đang xử lý...' : 'Yêu cầu AI chấm' }}
                </button>
              </div>

              <template v-if="aiResult">
                <div class="grid grid-cols-3 gap-4 mb-4">
                  <div class="bg-white rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ aiResult.suggested_score }}</div>
                    <div class="text-xs text-gray-500">/ {{ aiResult.max_score }} point</div>
                  </div>
                  <div class="bg-white rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ aiResult.percentage }}%</div>
                    <div class="text-xs text-gray-500">Percentage</div>
                  </div>
                  <div class="bg-white rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold" :class="gradeColor(aiResult.grade_letter)">{{ aiResult.grade_letter
                    }}</div>
                    <div class="text-xs text-gray-500">Grade</div>
                  </div>
                </div>

                <div class="space-y-3">
                  <!-- Feedback -->
                  <div class="bg-white rounded-lg p-3">
                    <div class="text-xs font-medium text-gray-500 mb-1">Feedback</div>
                    <div class="text-sm text-gray-700">{{ aiResult.feedback }}</div>
                  </div>

                  <!-- Strengths -->
                  <div v-if="aiResult.strengths?.length" class="bg-white rounded-lg p-3">
                    <div class="text-xs font-medium text-green-600 mb-1"><i
                        class="fas fa-check-circle mr-1"></i>Strengths</div>
                    <ul class="text-sm text-gray-700 space-y-1">
                      <li v-for="s in aiResult.strengths" :key="s">• {{ s }}</li>
                    </ul>
                  </div>

                  <!-- Weaknesses -->
                  <div v-if="aiResult.weaknesses?.length" class="bg-white rounded-lg p-3">
                    <div class="text-xs font-medium text-red-600 mb-1"><i
                        class="fas fa-times-circle mr-1"></i>Weaknesses</div>
                    <ul class="text-sm text-gray-700 space-y-1">
                      <li v-for="w in aiResult.weaknesses" :key="w">• {{ w }}</li>
                    </ul>
                  </div>

                  <!-- Suggestions -->
                  <div v-if="aiResult.suggestions?.length" class="bg-white rounded-lg p-3">
                    <div class="text-xs font-medium text-blue-600 mb-1"><i class="fas fa-lightbulb mr-1"></i>Suggestions
                    </div>
                    <ul class="text-sm text-gray-700 space-y-1">
                      <li v-for="s in aiResult.suggestions" :key="s">• {{ s }}</li>
                    </ul>
                  </div>
                </div>
              </template>

              <template v-else-if="selectedSubmission.grading?.ai_status === 'processing'">
                <div class="text-center py-4 text-gray-500">
                  <i class="fas fa-spinner fa-spin text-2xl text-purple-400 mb-2"></i>
                  <p class="text-sm">AI is processing the grading...</p>
                </div>
              </template>

              <template v-else-if="selectedSubmission.grading?.ai_status === 'failed'">
                <div class="text-center py-4 text-red-500">
                  <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                  <p class="text-sm">AI grading failed. Please try again.</p>
                </div>
              </template>

              <template v-else>
                <div class="text-center py-4 text-gray-400">
                  <p class="text-sm">Press "Request AI Grading" to get AI suggestions.</p>
                </div>
              </template>
            </div>

            <!-- Final Grading Section (Teacher) -->
            <div class="bg-white rounded-xl border p-5">
              <h4 class="font-medium text-gray-800 mb-4">
                <i class="fas fa-pen text-green-500 mr-2"></i>Final Grading
              </h4>

              <form @submit.prevent="submitGrading">
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Score</label>
                    <div class="flex items-center gap-2">
                      <input v-model.number="gradingForm.score" type="number" step="0.01" min="0"
                        :max="assignment.max_score" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
                      <span class="text-gray-500 text-sm">/ {{ assignment.max_score }}</span>
                    </div>
                    <button v-if="aiResult" type="button" @click="gradingForm.score = aiResult.suggested_score"
                      class="text-xs text-purple-600 hover:underline mt-1">
                      Use AI Suggested Score ({{ aiResult.suggested_score }})
                    </button>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Percentage</label>
                    <div class="px-4 py-2.5 bg-gray-50 rounded-lg text-sm text-gray-600">
                      {{ gradingPercentage }}%
                    </div>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Feedback</label>
                  <textarea v-model="gradingForm.feedback" rows="4"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                    placeholder="Feedback for the student..."></textarea>
                  <button v-if="aiResult?.feedback" type="button" @click="gradingForm.feedback = aiResult.feedback"
                    class="text-xs text-purple-600 hover:underline mt-1">
                    Use AI Feedback
                  </button>
                </div>

                <div class="flex justify-end gap-3">
                  <button type="button" @click="showSubmissionModal = false"
                    class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">
                    Close
                  </button>
                  <button type="submit" :disabled="grading"
                    class="px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 text-sm font-medium">
                    <i v-if="grading" class="fas fa-spinner fa-spin mr-1"></i>
                    {{ grading ? 'Saving...' : 'Done' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEditModal && assignment" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl z-10">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-800">Edit</h3>
              <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>
          </div>

          <form @submit.prevent="updateAssignment" class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <input v-model="editForm.title" type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea v-model="editForm.description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
              <textarea v-model="editForm.instructions" rows="4"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input v-model="editForm.due_date" type="datetime-local"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Maxium</label>
                <input v-model.number="editForm.max_score" type="number"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Submission Type</label>
                <select v-model="editForm.submission_type"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                  <option value="file">Attach File</option>
                  <option value="text">Text</option>
                  <option value="both">Both</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="editForm.status"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="closed">Closed</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="editForm.allow_late_submission" type="checkbox" class="w-4 h-4 text-blue-600 rounded" />
                <span class="text-sm text-gray-700">Allow Late Submission</span>
              </label>
              <div v-if="editForm.allow_late_submission" class="flex items-center gap-2">
                <input v-model.number="editForm.late_penalty" type="number" min="0" max="100"
                  class="w-20 px-3 py-1.5 border rounded-lg text-sm" />
                <span class="text-sm text-gray-500">% deduction</span>
              </div>
            </div>

            <!-- Existing Attached Files -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Attached Files</label>
              <div v-if="existingFiles.length" class="space-y-2 mb-3">
                <div v-for="file in existingFiles" :key="file.id"
                  class="flex items-center gap-3 p-3 rounded-lg border transition-colors"
                  :class="filesToRemove.includes(file.id) ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'">
                  <i :class="getFileIcon(file.file_name)" class="text-lg"></i>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate"
                      :class="filesToRemove.includes(file.id) ? 'text-red-400 line-through' : 'text-gray-700'">{{
                      file.file_name }}</div>
                    <div class="text-xs text-gray-400">{{ formatFileSize(file.file_size) }}</div>
                  </div>
                  <button type="button" @click="toggleRemoveFile(file.id)"
                    class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors"
                    :class="filesToRemove.includes(file.id) ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-red-100 text-red-600 hover:bg-red-200'">
                    <i :class="filesToRemove.includes(file.id) ? 'fas fa-undo' : 'fas fa-trash'" class="mr-1"></i>
                    {{ filesToRemove.includes(file.id) ? 'Undo' : 'Remove' }}
                  </button>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400 mb-3">No attached files</p>

              <!-- Add New Files -->
              <label
                class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 transition-colors">
                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                <span class="text-sm text-gray-500">Click to add files</span>
                <input type="file" multiple class="hidden" @change="onNewFilesSelected" />
              </label>
              <div v-if="newFiles.length" class="space-y-2 mt-3">
                <div v-for="(file, idx) in newFiles" :key="idx"
                  class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                  <i class="fas fa-file-circle-plus text-green-500 text-lg"></i>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-green-700 truncate">{{ file.name }}</div>
                    <div class="text-xs text-green-500">{{ formatFileSize(file.size) }} • New</div>
                  </div>
                  <button type="button" @click="removeNewFile(idx)"
                    class="px-2.5 py-1 bg-red-100 text-red-600 rounded-lg text-xs font-medium hover:bg-red-200 transition-colors">
                    <i class="fas fa-times mr-1"></i>Remove
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button type="button" @click="showEditModal = false"
                class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Huỷ</button>
              <button type="submit" :disabled="updating"
                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 text-sm font-medium">
                <i v-if="updating" class="fas fa-spinner fa-spin mr-1"></i>
                {{ updating ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <div v-if="toast.show"
        class="fixed bottom-6 right-6 z-50 px-6 py-3 rounded-xl shadow-lg text-white text-sm font-medium"
        :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
        {{ toast.message }}
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const route = useRoute()
const router = useRouter()
const api = useApi()

const props = defineProps({ id: [String, Number] })

// State
const assignment = ref(null)
const loading = ref(true)
const updating = ref(false)
const grading = ref(false)
const aiGrading = ref(false)
const showSubmissionModal = ref(false)
const showEditModal = ref(false)
const selectedSubmission = ref(null)
const aiResult = ref(null)
const toast = reactive({ show: false, message: '', type: 'success' })

// Edit form
const editForm = reactive({
  title: '', description: '', instructions: '', due_date: '',
  max_score: 100, submission_type: 'both', status: 'draft',
  allow_late_submission: false, late_penalty: 0,
})
const existingFiles = ref([])
const filesToRemove = ref([])
const newFiles = ref([])

// Grading form
const gradingForm = reactive({ score: 0, feedback: '' })

// Computed
const gradedCount = computed(() => {
  return assignment.value?.submissions?.filter(s => s.status === 'graded').length || 0
})

const isOverdue = computed(() => {
  if (!assignment.value?.due_date) return false
  return new Date(assignment.value.due_date) < new Date() && assignment.value.status !== 'closed'
})

const gradingPercentage = computed(() => {
  if (!assignment.value?.max_score || !gradingForm.score) return '0.00'
  return ((gradingForm.score / assignment.value.max_score) * 100).toFixed(2)
})

// Mount
onMounted(() => fetchAssignment())

// Fetch assignment
const fetchAssignment = async () => {
  loading.value = true
  try {
    const res = await api.assignment.getAssignmentDetail(props.id || route.params.id)
    assignment.value = res.data
    populateEditForm()
  } catch (e) {
    showToast('Error loading assignment', 'error')
  } finally {
    loading.value = false
  }
}

// Populate edit form
const populateEditForm = () => {
  if (!assignment.value) return
  const a = assignment.value
  editForm.title = a.title
  editForm.description = a.description || ''
  editForm.instructions = a.instructions || ''
  editForm.due_date = a.due_date ? new Date(a.due_date).toISOString().slice(0, 16) : ''
  editForm.max_score = a.max_score
  editForm.submission_type = a.submission_type
  editForm.status = a.status
  editForm.allow_late_submission = a.allow_late_submission
  editForm.late_penalty = a.late_penalty
  existingFiles.value = [...(a.files || [])]
  filesToRemove.value = []
  newFiles.value = []
}

const toggleRemoveFile = (fileId) => {
  const idx = filesToRemove.value.indexOf(fileId)
  if (idx === -1) {
    filesToRemove.value.push(fileId)
  } else {
    filesToRemove.value.splice(idx, 1)
  }
}

const onNewFilesSelected = (e) => {
  const selected = Array.from(e.target.files || [])
  newFiles.value.push(...selected)
  e.target.value = ''
}

const removeNewFile = (idx) => {
  newFiles.value.splice(idx, 1)
}

// Update assignment
const updateAssignment = async () => {
  updating.value = true
  try {
    const formData = new FormData()
    Object.entries(editForm).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        if (key === 'allow_late_submission') {
          formData.append(key, value ? '1' : '0')
        } else {
          formData.append(key, value)
        }
      }
    })

    // Files to remove
    filesToRemove.value.forEach(id => {
      formData.append('remove_files[]', id)
    })

    // New files to upload
    newFiles.value.forEach(file => {
      formData.append('files[]', file)
    })

    await api.assignment.updateAssignment(props.id || route.params.id, formData)
    showToast('Updated successfully')
    showEditModal.value = false
    fetchAssignment()
  } catch (e) {
    showToast(e.response?.data?.message || 'Error updating assignment', 'error')
  } finally {
    updating.value = false
  }
}

// Delete assignment
const confirmDelete = async () => {
  if (!confirm('Are you sure you want to delete this assignment?')) return
  try {
    await api.assignment.deleteAssignment(props.id || route.params.id)
    showToast('Assignment deleted successfully')
    router.back()
  } catch (e) {
    showToast('Error deleting assignment', 'error')
  }
}

// Open submission detail
const openSubmissionDetail = async (submission) => {
  selectedSubmission.value = submission
  aiResult.value = null
  showSubmissionModal.value = true

  // Pre-populate grading form
  if (submission.grading) {
    gradingForm.score = submission.grading.score
    gradingForm.feedback = submission.grading.feedback || ''

    // Parse AI result if available
    if (submission.grading.ai_feedback && submission.grading.ai_status === 'completed') {
      try {
        aiResult.value = JSON.parse(submission.grading.ai_feedback)
      } catch (e) { /* ignore */ }
    }
  } else {
    gradingForm.score = 0
    gradingForm.feedback = ''
  }

  // Fetch fresh detail
  try {
    const res = await api.assignment.getSubmissionDetail(submission.id)
    selectedSubmission.value = res.data

    if (res.data.grading?.ai_feedback && res.data.grading?.ai_status === 'completed') {
      try {
        aiResult.value = JSON.parse(res.data.grading.ai_feedback)
      } catch (e) { /* ignore */ }
    }
  } catch (e) {
    // Fallback to original data
  }
}

// Request AI grading
const requestAIGrade = async (submissionId) => {
  aiGrading.value = true
  try {
    const res = await api.assignment.requestAIGrading(submissionId)
    if (res.data?.ai_feedback) {
      try {
        aiResult.value = JSON.parse(res.data.ai_feedback)
      } catch {
        aiResult.value = res.data
      }
    } else {
      aiResult.value = res.data
    }
    showToast('AI grading completed')

    // Refresh assignment data
    fetchAssignment()
  } catch (e) {
    showToast(e.response?.data?.message || 'AI grading failed', 'error')
  } finally {
    aiGrading.value = false
  }
}

// Submit final grading
const submitGrading = async () => {
  grading.value = true
  try {
    await api.assignment.finalizeGrading(selectedSubmission.value.id, {
      score: gradingForm.score,
      feedback: gradingForm.feedback,
    })
    showToast('Grading saved successfully')
    showSubmissionModal.value = false
    fetchAssignment()
  } catch (e) {
    showToast(e.response?.data?.message || 'Error grading', 'error')
  } finally {
    grading.value = false
  }
}

// Helpers
const statusClass = (status) => {
  const map = {
    draft: 'bg-gray-100 text-gray-600',
    published: 'bg-green-100 text-green-600',
    closed: 'bg-red-100 text-red-600',
    archived: 'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

const statusLabel = (status) => {
  const map = { draft: 'Draft', published: 'Published', closed: 'Closed', archived: 'Archived' }
  return map[status] || status
}

const submissionStatusClass = (status) => {
  const map = {
    submitted: 'bg-blue-100 text-blue-600',
    graded: 'bg-green-100 text-green-600',
    returned: 'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

const submissionStatusLabel = (status) => {
  const map = { submitted: 'Submitted', graded: 'Graded', returned: 'Returned' }
  return map[status] || status
}

const formatDate = (date) => {
  if (!date) return 'No date'
  return new Date(date).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const getFileUrl = (path) => {
  if (!path) return ''
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  return `${baseUrl}/storage/${path}`
}

const getFileIcon = (name) => {
  if (!name) return 'fas fa-file'
  const ext = name.split('.').pop().toLowerCase()
  const map = {
    pdf: 'fas fa-file-pdf text-red-500',
    doc: 'fas fa-file-word text-blue-500',
    docx: 'fas fa-file-word text-blue-500',
    txt: 'fas fa-file-alt',
    jpg: 'fas fa-file-image text-purple-500',
    jpeg: 'fas fa-file-image text-purple-500',
    png: 'fas fa-file-image text-purple-500',
  }
  return map[ext] || 'fas fa-file'
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

const gradeColor = (letter) => {
  const map = { A: 'text-green-600', B: 'text-blue-600', C: 'text-yellow-600', D: 'text-orange-600', F: 'text-red-600' }
  return map[letter] || 'text-gray-600'
}

const showToast = (message, type = 'success') => {
  toast.show = true
  toast.message = message
  toast.type = type
  setTimeout(() => { toast.show = false }, 3000)
}
</script>
