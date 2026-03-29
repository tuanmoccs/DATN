<template>
  <div class="editor-toolbar flex items-center gap-1 px-3 py-2 border-b border-gray-200 bg-gray-50 flex-wrap">
    <!-- Text formatting -->
    <button @click="editor.chain().focus().toggleBold().run()" :class="btnClass(editor.isActive('bold'))" title="Bold">
      <i class="fas fa-bold"></i>
    </button>
    <button @click="editor.chain().focus().toggleItalic().run()" :class="btnClass(editor.isActive('italic'))"
      title="Italic">
      <i class="fas fa-italic"></i>
    </button>
    <button @click="editor.chain().focus().toggleUnderline().run()" :class="btnClass(editor.isActive('underline'))"
      title="Underline">
      <i class="fas fa-underline"></i>
    </button>
    <button @click="editor.chain().focus().toggleStrike().run()" :class="btnClass(editor.isActive('strike'))"
      title="Strikethrough">
      <i class="fas fa-strikethrough"></i>
    </button>

    <div class="w-px h-5 bg-gray-300 mx-1"></div>

    <!-- Headings -->
    <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
      :class="btnClass(editor.isActive('heading', { level: 1 }))" title="Heading 1">
      H1
    </button>
    <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
      :class="btnClass(editor.isActive('heading', { level: 2 }))" title="Heading 2">
      H2
    </button>
    <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
      :class="btnClass(editor.isActive('heading', { level: 3 }))" title="Heading 3">
      H3
    </button>

    <div class="w-px h-5 bg-gray-300 mx-1"></div>

    <!-- Lists -->
    <button @click="editor.chain().focus().toggleBulletList().run()" :class="btnClass(editor.isActive('bulletList'))"
      title="Bullet List">
      <i class="fas fa-list-ul"></i>
    </button>
    <button @click="editor.chain().focus().toggleOrderedList().run()" :class="btnClass(editor.isActive('orderedList'))"
      title="Ordered List">
      <i class="fas fa-list-ol"></i>
    </button>

    <div class="w-px h-5 bg-gray-300 mx-1"></div>

    <!-- Text Align -->
    <button @click="editor.chain().focus().setTextAlign('left').run()"
      :class="btnClass(editor.isActive({ textAlign: 'left' }))" title="Align Left">
      <i class="fas fa-align-left"></i>
    </button>
    <button @click="editor.chain().focus().setTextAlign('center').run()"
      :class="btnClass(editor.isActive({ textAlign: 'center' }))" title="Align Center">
      <i class="fas fa-align-center"></i>
    </button>
    <button @click="editor.chain().focus().setTextAlign('right').run()"
      :class="btnClass(editor.isActive({ textAlign: 'right' }))" title="Align Right">
      <i class="fas fa-align-right"></i>
    </button>

    <div class="w-px h-5 bg-gray-300 mx-1"></div>

    <!-- Block -->
    <button @click="editor.chain().focus().toggleBlockquote().run()" :class="btnClass(editor.isActive('blockquote'))"
      title="Blockquote">
      <i class="fas fa-quote-right"></i>
    </button>
    <button @click="editor.chain().focus().setHorizontalRule().run()" class="toolbar-btn" title="Horizontal Rule">
      <i class="fas fa-minus"></i>
    </button>

    <div class="w-px h-5 bg-gray-300 mx-1"></div>

    <!-- Undo/Redo -->
    <button @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="toolbar-btn"
      title="Undo">
      <i class="fas fa-undo"></i>
    </button>
    <button @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="toolbar-btn"
      title="Redo">
      <i class="fas fa-redo"></i>
    </button>

    <!-- AI indicator -->
    <div class="ml-auto flex items-center gap-2 text-xs text-gray-400">
      <span v-if="isAiLoading" class="flex items-center gap-1 text-blue-500">
        <i class="fas fa-spinner fa-spin"></i> AI is suggesting...
      </span>
      <span v-else>
        <i class="fas fa-magic"></i> Tab to apply AI suggestion
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  editor: { type: Object, required: true },
  isAiLoading: { type: Boolean, default: false },
})

const btnClass = (isActive) => {
  return [
    'toolbar-btn',
    isActive ? 'bg-blue-100 text-blue-700' : '',
  ]
}
</script>

<style scoped>
@reference "tailwindcss";

.toolbar-btn {
  @apply w-8 h-8 flex items-center justify-center rounded text-sm text-gray-600 hover:bg-gray-200 transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed;
}
</style>
