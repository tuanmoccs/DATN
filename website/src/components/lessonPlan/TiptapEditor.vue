<template>
  <div class="tiptap-editor border border-gray-300 rounded-lg overflow-hidden bg-white">
    <EditorToolbar v-if="editor" :editor="editor" :isAiLoading="isAiLoading" />
    <EditorContent :editor="editor" class="editor-content" />
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import EditorToolbar from './EditorToolbar.vue'
import { GhostText, ghostTextPluginKey } from './ghostTextPlugin'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Bắt đầu soạn giáo án...' },
  aiSuggestFn: { type: Function, default: null },
  debounceMs: { type: Number, default: 1200 },
})

const emit = defineEmits(['update:modelValue'])

const isAiLoading = ref(false)
let debounceTimer = null
let abortController = null

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Placeholder.configure({ placeholder: props.placeholder }),
    GhostText,
  ],
  editorProps: {
    handleKeyDown(view, event) {
      const pluginState = ghostTextPluginKey.getState(view.state)

      // Tab → accept suggestion
      if (event.key === 'Tab' && pluginState?.suggestion) {
        event.preventDefault()
        const { suggestion, pos } = pluginState

        // Clear ghost text first
        const tr = view.state.tr.setMeta(ghostTextPluginKey, { clear: true })
        view.dispatch(tr)

        // Insert suggestion text
        editor.value.chain().focus().insertContentAt(pos, suggestion).run()
        return true
      }

      // Escape → dismiss suggestion
      if (event.key === 'Escape' && pluginState?.suggestion) {
        event.preventDefault()
        const tr = view.state.tr.setMeta(ghostTextPluginKey, { clear: true })
        view.dispatch(tr)
        return true
      }

      return false
    },
  },
  onUpdate({ editor: ed }) {
    const html = ed.getHTML()
    emit('update:modelValue', html)
    scheduleSuggestion(ed)
  },

})

// Sync external v-model changes back into editor
watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    editor.value.commands.setContent(val, false)
  }
})

function scheduleSuggestion(ed) {
  // Clear previous timer
  if (debounceTimer) clearTimeout(debounceTimer)
  // Abort previous request
  if (abortController) abortController.abort()

  // Clear existing ghost text on typing
  const tr = ed.view.state.tr.setMeta(ghostTextPluginKey, { clear: true })
  ed.view.dispatch(tr)

  if (!props.aiSuggestFn) return

  const text = ed.getText()
  if (text.trim().length < 20) return // Too short to suggest

  debounceTimer = setTimeout(async () => {
    await fetchSuggestion(ed, text)
  }, props.debounceMs)
}

async function fetchSuggestion(ed, text) {
  abortController = new AbortController()
  isAiLoading.value = true

  try {
    const result = await props.aiSuggestFn(text)
    const suggestion = result?.suggestion?.trim()

    if (!suggestion || !ed.isFocused) return

    // Get current cursor position
    const { from } = ed.state.selection
    const tr = ed.view.state.tr.setMeta(ghostTextPluginKey, {
      suggestion,
      pos: from,
    })
    ed.view.dispatch(tr)
  } catch (err) {
    if (err?.name !== 'AbortError') {
      console.warn('AI suggest failed:', err)
    }
  } finally {
    isAiLoading.value = false
  }
}

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (abortController) abortController.abort()
})
</script>

<style>
/* Ghost text styling */
.ghost-text {
  color: #9ca3af;
  opacity: 0.7;
  pointer-events: none;
  user-select: none;
  font-style: italic;
}

/* Editor content area */
.editor-content .tiptap {
  min-height: 500px;
  padding: 1.5rem;
  outline: none;
  font-size: 1rem;
  line-height: 1.75;
}

.editor-content .tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
}

/* Headings */
.editor-content .tiptap h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 1.5rem 0 0.75rem;
  line-height: 1.3;
}

.editor-content .tiptap h2 {
  font-size: 1.4rem;
  font-weight: 600;
  margin: 1.25rem 0 0.5rem;
  line-height: 1.35;
}

.editor-content .tiptap h3 {
  font-size: 1.15rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem;
  line-height: 1.4;
}

/* Paragraphs & lists */
.editor-content .tiptap p {
  margin: 0.5rem 0;
}

.editor-content .tiptap ul,
.editor-content .tiptap ol {
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}

.editor-content .tiptap li {
  margin: 0.25rem 0;
}

/* Blockquote */
.editor-content .tiptap blockquote {
  border-left: 3px solid #3b82f6;
  padding-left: 1rem;
  margin: 0.75rem 0;
  color: #4b5563;
  font-style: italic;
}

/* Horizontal rule */
.editor-content .tiptap hr {
  border: none;
  border-top: 2px solid #e5e7eb;
  margin: 1.5rem 0;
}
</style>
