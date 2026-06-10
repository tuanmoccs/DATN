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
import { Extension } from '@tiptap/core'
import EditorToolbar from './EditorToolbar.vue'
import { GhostText, ghostTextPluginKey } from './ghostTextPlugin'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Bắt đầu soạn giáo án...' },
  aiSuggestFn: { type: Function, default: null },
  debounceMs: { type: Number, default: 1800 },
})

const emit = defineEmits(['update:modelValue'])

const isAiLoading = ref(false)
let debounceTimer = null
let abortController = null
let suppressUpdate = false

const TemplatePlaceholder = Extension.create({
  name: 'templatePlaceholder',
  addGlobalAttributes() {
    return [
      {
        types: ['paragraph'],
        attributes: {
          templatePlaceholder: {
            default: null,
            parseHTML: element => element.getAttribute('data-placeholder'),
            renderHTML: attributes => (
              attributes.templatePlaceholder
                ? { 'data-placeholder': attributes.templatePlaceholder }
                : {}
            ),
          },
        },
      },
    ]
  },
})

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    TemplatePlaceholder,
    Placeholder.configure({
      placeholder: ({ node, editor: currentEditor }) =>
        node.attrs.templatePlaceholder || (currentEditor.isEmpty ? props.placeholder : ''),
      showOnlyCurrent: false,
      includeChildren: true,
    }),
    GhostText,
  ],
  editorProps: {
    handleClick(view, pos, event) {
      const prompt = event.target.closest?.('em')
      if (!prompt) return false

      const promptText = prompt.textContent?.trim() || ''
      const isTemplatePrompt = /^(Nhập|Mô tả|Liệt kê|Ghi lại|Nêu|Ví dụ)/.test(promptText)
      if (!isTemplatePrompt) return false

      const selection = window.getSelection()
      const range = document.createRange()
      range.selectNodeContents(prompt)
      selection.removeAllRanges()
      selection.addRange(range)
      return false
    },
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
    if (!suppressUpdate) {
      scheduleSuggestion(ed)
    }
  },

})

// Sync external v-model changes back into editor (without re-triggering AI)
watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    suppressUpdate = true
    editor.value.commands.setContent(val, false)
    suppressUpdate = false
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

  if (!props.aiSuggestFn) {
    console.log('[AI] No aiSuggestFn prop')
    return
  }

  const context = buildCursorContext(ed)
  if (context.text_before_cursor.trim().length < 5) return

  debounceTimer = setTimeout(async () => {
    await fetchSuggestion(ed, context)
  }, props.debounceMs)
}

function buildCursorContext(ed) {
  const cursorPosition = ed.state.selection.from
  const documentEnd = ed.state.doc.content.size
  const textBeforeCursor = ed.state.doc.textBetween(
    Math.max(0, cursorPosition - 1500),
    cursorPosition,
    '\n',
  )
  const textAfterCursor = ed.state.doc.textBetween(
    cursorPosition,
    Math.min(documentEnd, cursorPosition + 1000),
    '\n',
  )

  let currentSection = ''
  let nextSection = ''

  ed.state.doc.descendants((node, pos) => {
    if (node.type.name !== 'heading' || ![2, 3].includes(node.attrs.level)) return

    const title = node.textContent.trim()
    if (pos < cursorPosition) {
      currentSection = title
    } else if (!nextSection) {
      nextSection = title
    }
  })

  return {
    text: textBeforeCursor,
    text_before_cursor: textBeforeCursor,
    text_after_cursor: textAfterCursor,
    current_section: currentSection,
    next_section: nextSection,
    cursor_position: cursorPosition,
  }
}

async function fetchSuggestion(ed, context) {
  const controller = new AbortController()
  abortController = controller
  isAiLoading.value = true

  try {
    const result = await props.aiSuggestFn(context, {
      signal: controller.signal,
    })
    const suggestion = result?.suggestion?.trim()

    const cursorUnchanged = ed.state.selection.from === context.cursor_position
    if (!suggestion || !ed.isFocused || controller.signal.aborted || !cursorUnchanged) return

    const tr = ed.view.state.tr.setMeta(ghostTextPluginKey, {
      suggestion,
      pos: context.cursor_position,
    })
    ed.view.dispatch(tr)
  } catch (err) {
    const isCanceled = (
      err?.name === 'AbortError'
      || err?.name === 'CanceledError'
      || err?.code === 'ERR_CANCELED'
    )
    const isTimeout = err?.code === 'ECONNABORTED'

    if (!isCanceled && !isTimeout) {
      console.warn('AI suggest failed:', err)
    }
  } finally {
    if (abortController === controller) {
      isAiLoading.value = false
    }
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

@media (max-width: 640px) {
  .editor-content .tiptap {
    min-height: 420px;
    padding: 1rem;
  }
}

.editor-content .tiptap p.is-empty::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
  font-style: italic;
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
