import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

export const ghostTextPluginKey = new PluginKey('ghostText')

/**
 * Tiptap extension that renders ghost text (inline suggestion)
 * at the current cursor position.
 *
 * Usage: dispatch a transaction with meta on ghostTextPluginKey:
 *   tr.setMeta(ghostTextPluginKey, { suggestion: 'text...', pos: cursorPos })
 *   tr.setMeta(ghostTextPluginKey, { clear: true })
 */
export const GhostText = Extension.create({
  name: 'ghostText',

  addProseMirrorPlugins() {
    return [
      new Plugin({
        key: ghostTextPluginKey,

        state: {
          init() {
            return { suggestion: '', pos: null }
          },
          apply(tr, prev) {
            const meta = tr.getMeta(ghostTextPluginKey)
            if (meta) {
              if (meta.clear) {
                return { suggestion: '', pos: null }
              }
              return { suggestion: meta.suggestion || '', pos: meta.pos ?? null }
            }
            // Clear suggestion on any document change (user typed something)
            if (tr.docChanged) {
              return { suggestion: '', pos: null }
            }
            return prev
          },
        },

        props: {
          decorations(state) {
            const pluginState = ghostTextPluginKey.getState(state)
            if (!pluginState?.suggestion || pluginState.pos === null) {
              return DecorationSet.empty
            }

            const { suggestion, pos } = pluginState

            // Validate pos is within document bounds
            if (pos < 0 || pos > state.doc.content.size) {
              return DecorationSet.empty
            }

            const widget = Decoration.widget(pos, () => {
              const span = document.createElement('span')
              span.className = 'ghost-text'
              span.textContent = suggestion
              return span
            }, {
              side: 1,
              key: 'ghost-text',
            })

            return DecorationSet.create(state.doc, [widget])
          },
        },
      }),
    ]
  },
})
