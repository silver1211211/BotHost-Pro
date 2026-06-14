import Alpine from 'alpinejs';
import './confirm.js';

window.Alpine = Alpine;

let monacoPromise = null;

async function loadMonaco() {
    if (!monacoPromise) {
        monacoPromise = Promise.all([
            import('monaco-editor/esm/vs/editor/editor.worker?worker'),
            import('monaco-editor/esm/vs/editor/editor.api'),
            import('monaco-editor/esm/vs/basic-languages/javascript/javascript.contribution'),
            import('monaco-editor/esm/vs/basic-languages/python/python.contribution'),
            import('monaco-editor/esm/vs/basic-languages/typescript/typescript.contribution'),
            import('monaco-editor/esm/vs/basic-languages/markdown/markdown.contribution'),
            import('monaco-editor/esm/vs/language/json/monaco.contribution'),
        ]).then(([editorWorker, monaco]) => {
            self.MonacoEnvironment = {
                getWorker() {
                    return new editorWorker.default();
                },
            };

            return monaco;
        });
    }

    return monacoPromise;
}

let codeMirrorPromise = null;

async function loadCodeMirror() {
    if (!codeMirrorPromise) {
        codeMirrorPromise = Promise.all([
            import('codemirror'),
            import('@codemirror/state'),
            import('@codemirror/view'),
            import('@codemirror/commands'),
            import('@codemirror/search'),
            import('@codemirror/language'),
            import('@codemirror/lang-javascript'),
            import('@codemirror/lang-python'),
            import('@codemirror/lang-json'),
            import('@codemirror/theme-one-dark'),
        ]).then(([
            codemirror,
            state,
            view,
            commands,
            search,
            language,
            javascript,
            python,
            json,
            theme,
        ]) => ({
            ...codemirror,
            ...state,
            ...view,
            ...commands,
            ...search,
            ...language,
            javascript: javascript.javascript,
            python: python.python,
            json: json.json,
            oneDark: theme.oneDark,
        }));
    }

    return codeMirrorPromise;
}

window.BotHostPreloadCommandEditor = () => loadCodeMirror().catch(() => null);

Alpine.data('projectWorkspace', (config) => ({
    files: config.files,
    activeFile: config.activeFile,
    activeContent: config.activeContent || '',
    openTabs: config.activeFile ? [config.activeFile] : [],
    editor: null,
    saving: false,
    savedAt: null,
    error: null,
    autosaveTimer: null,
    monaco: null,

    init() {
        this.$nextTick(async () => {
            this.monaco = await loadMonaco();

            this.editor = this.monaco.editor.create(this.$refs.editor, {
                value: this.activeContent,
                language: this.languageFor(this.activeFile?.path),
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
                fontSize: window.matchMedia('(max-width: 768px)').matches ? 16 : 14,
                tabSize: 2,
                wordWrap: 'on',
                scrollBeyondLastLine: false,
            });

            this.editor.onDidChangeModelContent(() => {
                clearTimeout(this.autosaveTimer);
                this.autosaveTimer = setTimeout(() => this.save(), 1800);
            });
        });
    },

    languageFor(path) {
        if (!path) return 'plaintext';
        if (path.endsWith('.js')) return 'javascript';
        if (path.endsWith('.json')) return 'json';
        if (path.endsWith('.md')) return 'markdown';
        return 'plaintext';
    },

    async openFile(file) {
        if (this.activeFile?.id === file.id) return;
        await this.save();

        this.error = null;
        const response = await fetch(file.showUrl, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            this.error = 'Could not load file.';
            return;
        }

        const data = await response.json();
        this.activeFile = this.files.find((item) => item.id === data.id) || file;
        this.addTab(this.activeFile);
        this.activeContent = data.content || '';
        this.editor.setValue(this.activeContent);
        this.monaco.editor.setModelLanguage(this.editor.getModel(), this.languageFor(data.path));
    },

    addTab(file) {
        if (!this.openTabs.find((tab) => tab.id === file.id)) {
            this.openTabs.push(file);
        }
    },

    async closeTab(file) {
        await this.save();

        const index = this.openTabs.findIndex((tab) => tab.id === file.id);
        if (index === -1) return;

        this.openTabs.splice(index, 1);

        if (this.activeFile?.id !== file.id) return;

        const nextTab = this.openTabs[index] || this.openTabs[index - 1] || null;

        if (nextTab) {
            await this.openFile(nextTab);
            return;
        }

        this.activeFile = null;
        this.activeContent = '';
        this.editor.setValue('');
    },

    async save() {
        if (!this.editor || !this.activeFile || this.saving) return;

        this.saving = true;
        this.error = null;

        const response = await fetch(this.activeFile.updateUrl, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
            },
            body: JSON.stringify({ content: this.editor.getValue() }),
        });

        this.saving = false;

        if (!response.ok) {
            this.error = 'Save failed. Check your connection and try again.';
            return;
        }

        const data = await response.json();
        this.savedAt = new Date().toLocaleTimeString();

        this.files = this.files.map((file) => file.id === data.file.id
            ? { ...file, size: data.file.size, updatedAt: data.file.updated_at }
            : file);
        this.openTabs = this.openTabs.map((file) => file.id === data.file.id
            ? { ...file, size: data.file.size, updatedAt: data.file.updated_at }
            : file);
    },
}));

Alpine.data('commandCodeEditor', (config) => ({
    saving: false,
    copied: false,
    dirty: false,
    loaded: false,
    editor: null,
    fallbackEditor: null,
    fallbackUndoStack: [],
    fallbackRedoStack: [],
    fallbackLastValue: config.code || '',
    historyLastValue: config.code || '',
    suppressHistory: false,
    cm: null,
    cursorLine: 1,
    cursorCol: 1,
    chars: (config.code || '').length,
    searchOpen: false,
    searchQuery: '',
    matchCount: 0,
    savedSnapshot: config.code || '',
    saveStatus: '',
    saveError: '',
    loadingCode: false,
    codeLoaded: Boolean(config.codeLoaded || !config.codeUrl),
    helpersOpen: false,
    fullscreen: false,
    editorDialogOpen: false,
    editorDialogType: 'default',
    editorDialogTitle: '',
    editorDialogMessage: '',
    editorDialogConfirmText: 'Confirm',
    editorDialogCancelText: 'Cancel',
    editorDialogPasteMode: false,
    editorDialogPasteText: '',
    editorDialogResolver: null,
    copyFlashTimer: null,
    copyResetTimer: null,
    copyHighlightEffect: null,
    copyHighlightField: null,
    saveShortcutHandler: null,
    browserBackHandler: null,
    browserBackArmed: false,
    previousHtmlOverflowY: '',
    previousBodyOverflowY: '',
    visualViewportHandler: null,

    async init() {
        // Prevent the page from scrolling while the code editor is open.
        // Without this, mobile browsers scroll the page (not the CM scroller)
        // in response to scrollIntoView / focus(), pushing the header off-screen.
        this.previousHtmlOverflowY = document.documentElement.style.overflowY || '';
        this.previousBodyOverflowY = document.body.style.overflowY || '';
        document.documentElement.style.overflowY = 'hidden';
        document.body.style.overflowY = 'hidden';
        this.updateEditorVisualViewport();
        this.visualViewportHandler = () => this.updateEditorVisualViewport();
        window.visualViewport?.addEventListener('resize', this.visualViewportHandler);
        window.visualViewport?.addEventListener('scroll', this.visualViewportHandler);

        this.mountTextareaFallback();
        await this.loadInitialCode();
        this.armBrowserBackGuard();

        let cm;
        try {
            cm = await loadCodeMirror();
        } catch (error) {
            return;
        }

        this.cm = cm;
        const initialValue = this.getValue();

        const compactViewport = window.matchMedia('(max-width: 768px)').matches;
        const language = this.languageExtension(config.language, cm);
        const editorTheme = cm.EditorView.theme({
            '&': {
                height: '100%',
                color: '#eeffff',
                backgroundColor: '#080714',
                fontSize: compactViewport ? '16px' : '14px',
            },
            '.cm-scroller': {
                fontFamily: "'JetBrains Mono','Fira Code','Cascadia Code','Consolas','Courier New',monospace",
                lineHeight: '1.65',
                overflow: 'auto',
            },
            '.cm-content': {
                minHeight: '100%',
                padding: '16px 0',
                caretColor: '#8B5CF6',
            },
            '.cm-line': {
                padding: '0 16px 0 8px',
            },
            '.cm-gutters': {
                backgroundColor: '#080714',
                color: '#3D3658',
                borderRight: '1px solid #1B172B',
            },
            '.cm-lineNumbers': {
                minWidth: '44px',
            },
            '.cm-lineNumbers .cm-gutterElement': {
                padding: '0 10px 0 12px',
                textAlign: 'right',
            },
            '.cm-activeLine': {
                backgroundColor: 'rgba(0, 0, 0, 0.35)',
            },
            '.cm-activeLineGutter': {
                backgroundColor: 'rgba(0, 0, 0, 0.35)',
                color: '#ffffff',
            },
            '.cm-selectionLayer .cm-selectionBackground': {
                backgroundColor: 'rgba(17, 24, 39, 0.65)',
                opacity: '1',
            },
            '&.cm-focused .cm-selectionLayer .cm-selectionBackground': {
                backgroundColor: 'rgba(0, 0, 0, 0.75)',
                opacity: '1',
            },
            '.cm-copy-highlight': {
                backgroundColor: 'rgba(0, 0, 0, 0.55)',
                borderRadius: '2px',
            },
            '.cm-content ::selection': {
                backgroundColor: 'rgba(0, 0, 0, 0.75)',
                color: '#ffffff',
            },
            '&.command-copy-flash .cm-selectionBackground, &.command-copy-flash.cm-focused .cm-selectionBackground': {
                backgroundColor: 'rgba(0, 0, 0, 0.55)',
                opacity: '1',
            },
            '&.command-copy-flash .cm-selectionLayer .cm-selectionBackground, &.command-copy-flash.cm-focused .cm-selectionLayer .cm-selectionBackground': {
                backgroundColor: 'rgba(0, 0, 0, 0.55)',
                opacity: '1',
            },
            '.cm-selectionMatch': {
                backgroundColor: 'rgba(17, 24, 39, 0.55)',
            },
            '.cm-searchMatch': {
                backgroundColor: 'rgba(250, 204, 21, 0.28)',
                outline: '1px solid rgba(250, 204, 21, 0.45)',
            },
            '.cm-searchMatch-selected': {
                backgroundColor: 'rgba(250, 204, 21, 0.40)',
                outline: '1px solid rgba(250, 204, 21, 0.70)',
            },
            '.cm-matchingBracket, .cm-nonmatchingBracket': {
                backgroundColor: '#8B5CF620',
                outline: '1px solid #8B5CF680',
            },
            '.cm-search': {
                backgroundColor: '#0f0d1a',
                border: '1px solid #27213d',
                color: '#eeffff',
                padding: '8px',
            },
            '.cm-search input': {
                backgroundColor: '#151225',
                border: '1px solid #27213d',
                color: '#eeffff',
                borderRadius: '8px',
                padding: '4px 8px',
            },
            '.cm-search button': {
                backgroundColor: '#151225',
                border: '1px solid #27213d',
                color: '#A1A1AA',
                borderRadius: '8px',
                padding: '4px 8px',
            },
        }, { dark: true });

        const sync = cm.EditorView.updateListener.of((update) => {
            if (update.docChanged) {
                const value = this.getValue();
                const previous = update.startState.doc.toString();

                if (!this.suppressHistory && previous !== value) {
                    this.fallbackUndoStack.push(previous);
                    if (this.fallbackUndoStack.length > 100) this.fallbackUndoStack.shift();
                    this.fallbackRedoStack = [];
                    this.historyLastValue = value;
                }

                this.chars = value.length;
                this.$refs.codeInput.value = value;
                this.dirty = value !== this.savedSnapshot;
            }

            if (update.selectionSet || update.docChanged) {
                this.updateCursor();
            }

            if (update.docChanged && this.isCompactMobile()) {
                this.keepEditorShellAnchored();

                const nativePaste = update.transactions.some((transaction) => {
                    const event = transaction.annotation(cm.Transaction.userEvent);
                    return typeof event === 'string' && event.includes('paste');
                });

                if (nativePaste) {
                    this.resetEditorViewportTop();
                }
            }
        });

        const commandKeymap = cm.Prec.high(cm.keymap.of([
            {
                key: 'Mod-a',
                preventDefault: true,
                run: () => {
                    this.selectAllCode();
                    return true;
                },
            },
            {
                key: 'Mod-s',
                preventDefault: true,
                run: () => {
                    this.submitSave();
                    return true;
                },
            },
            {
                key: 'Mod-f',
                preventDefault: true,
                run: () => {
                    this.findInEditor();
                    return true;
                },
            },
            {
                key: 'Mod-y',
                preventDefault: true,
                run: () => {
                    this.redo();
                    return true;
                },
            },
            {
                key: 'Mod-Shift-z',
                preventDefault: true,
                run: () => {
                    this.redo();
                    return true;
                },
            },
        ]));

        this.copyHighlightEffect = cm.StateEffect.define();
        this.copyHighlightField = cm.StateField.define({
            create: () => cm.Decoration.none,
            update: (highlights, transaction) => {
                highlights = highlights.map(transaction.changes);

                for (const effect of transaction.effects) {
                    if (effect.is(this.copyHighlightEffect)) {
                        const ranges = effect.value
                            .filter((range) => range.to > range.from)
                            .map((range) => cm.Decoration.mark({
                                class: 'cm-copy-highlight',
                            }).range(range.from, range.to));

                        return cm.Decoration.set(ranges, true);
                    }
                }

                return highlights;
            },
            provide: (field) => cm.EditorView.decorations.from(field),
        });

        const state = cm.EditorState.create({
            doc: initialValue,
            extensions: [
                cm.lineNumbers(),
                cm.basicSetup,
                cm.search({ top: true }),
                cm.oneDark,
                editorTheme,
                language,
                compactViewport ? cm.EditorView.lineWrapping : [],
                cm.EditorState.tabSize.of(2),
                cm.keymap.of([cm.indentWithTab]),
                commandKeymap,
                this.copyHighlightField,
                sync,
            ],
        });

        this.$refs.editorContainer.innerHTML = '';
        this.editor = new cm.EditorView({
            state,
            parent: this.$refs.editorContainer,
        });

        this.fallbackEditor = null;
        this.historyLastValue = initialValue;

        this.saveShortcutHandler = (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                this.submitSave();
            }
        };

        window.addEventListener('keydown', this.saveShortcutHandler);
        this.armBrowserBackGuard();

        this.$refs.codeInput.value = initialValue;
        this.updateCursor();
        this.loaded = true;
        this.focusEditor({ preventScroll: true });
    },

    async loadInitialCode() {
        if (this.codeLoaded || !config.codeUrl || !window.fetch) {
            return;
        }

        this.loadingCode = true;
        this.saveError = '';
        this.saveStatus = 'Loading code...';

        try {
            const response = await fetch(config.codeUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Could not load this command code.');
            }

            const code = String(data.code ?? '');
            this.setEditorCode(code, { markSaved: true });
            this.codeLoaded = true;
            this.loadingCode = false;
            this.saveStatus = '';

            window.dispatchEvent(new CustomEvent('bot-code-editor-loaded', {
                detail: {
                    id: config.id || null,
                    code,
                },
            }));
        } catch (error) {
            this.loadingCode = false;
            this.saveStatus = '';
            this.saveError = error?.message || 'Could not load this command code. Use the full editor link and try again.';
        }
    },

    async submitSave() {
        if (this.saving) return;
        if (this.editor || this.fallbackEditor) this.$refs.codeInput.value = this.getValue();
        this.saving = true;
        this.saveError = '';
        this.saveStatus = 'Saving...';

        const form = this.$refs.saveForm;

        if (!config.action || !window.fetch || !form) {
            form.submit();
            return;
        }

        try {
            const response = await fetch(config.action, {
                method: config.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': config.csrf || '',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: this.$refs.codeInput.value }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                const message = data.message
                    || Object.values(data.errors || {}).flat()[0]
                    || 'Save failed. Your code was not changed.';
                throw new Error(message);
            }

            this.savedSnapshot = this.$refs.codeInput.value;
            this.dirty = false;
            this.saveStatus = 'Saved';
            this.saving = false;
            window.dispatchEvent(new CustomEvent('bot-code-editor-saved', {
                detail: {
                    id: config.id || null,
                    code: this.savedSnapshot,
                },
            }));

            setTimeout(() => {
                if (!this.dirty && this.saveStatus === 'Saved') this.saveStatus = '';
            }, 1800);
        } catch (error) {
            this.saveError = error?.message || 'Save failed. Your code is still here.';
            this.saveStatus = '';
            this.saving = false;
            this.dirty = this.$refs.codeInput.value !== this.savedSnapshot;
        }
    },

    copyCode() {
        const val = this.getValue();
        this.copied = true;
        this.saveStatus = 'Copied';
        this.saveError = '';
        this.selectAllCode({ flash: true, preserveViewport: true });
        window.clearTimeout(this.copyResetTimer);
        this.copyResetTimer = window.setTimeout(() => {
            this.copied = false;
            if (this.saveStatus === 'Copied') this.saveStatus = '';
        }, 2600);

        const copy = navigator.clipboard?.writeText
            ? navigator.clipboard.writeText(val)
            : Promise.reject();

        copy.catch(() => {
            const el = document.createElement('textarea');
            el.value = val;
            el.setAttribute('readonly', 'readonly');
            el.style.position = 'fixed';
            el.style.left = '-9999px';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }).catch(() => {
            this.saveError = 'Copy failed. Select the code and copy manually.';
        });
    },

    selectAllCode({ flash = false, preserveViewport = false } = {}) {
        window.clearTimeout(this.copyFlashTimer);
        const pageScroll = this.capturePageScroll();
        const editorScroll = this.captureEditorScroll();

        if (this.isCompactMobile()) {
            if (!flash) {
                this.copyCode();
                return;
            }

            this.flashWholeDocument();
            if (preserveViewport) this.restoreViewport(pageScroll, editorScroll);
            return;
        }

        if (this.fallbackEditor) {
            const textarea = this.fallbackEditor;

            this.focusEditor({ preventScroll: true });
            textarea.select();
            if (preserveViewport) this.restoreViewport(pageScroll, editorScroll);

            return;
        }

        if (!this.editor) return;

        const docLength = this.editor.state.doc.length;
        const effect = flash ? this.copyHighlightEffect?.of([{ from: 0, to: docLength }]) : null;

        this.editor.dispatch({
            selection: { anchor: 0, head: docLength },
            effects: effect ? [effect] : [],
        });
        this.focusEditor({ preventScroll: true });
        if (preserveViewport) this.restoreViewport(pageScroll, editorScroll);

        if (flash) {
            this.copyFlashTimer = window.setTimeout(() => {
                if (!this.editor) return;
                const clearEffect = this.copyHighlightEffect?.of([]);
                if (clearEffect) this.editor.dispatch({ effects: [clearEffect] });
            }, 3200);
        }
    },

    flashWholeDocument() {
        if (this.fallbackEditor) {
            const surface = this.$refs.editorContainer?.closest('.command-editor-surface');
            surface?.classList.add('command-copy-surface-flash');
            window.setTimeout(() => surface?.classList.remove('command-copy-surface-flash'), 1500);
            return;
        }

        if (!this.editor || !this.copyHighlightEffect) return;
        const docLength = this.editor.state.doc.length;
        this.editor.dispatch({
            effects: [this.copyHighlightEffect.of([{ from: 0, to: docLength }])],
        });

        this.copyFlashTimer = window.setTimeout(() => {
            if (!this.editor) return;
            const clearEffect = this.copyHighlightEffect?.of([]);
            if (clearEffect) this.editor.dispatch({ effects: [clearEffect] });
        }, 1600);
    },

    undo() {
        if (this.fallbackEditor) {
            if (this.fallbackUndoStack.length > 0) {
                const current = this.fallbackEditor.value;
                const previous = this.fallbackUndoStack.pop();
                this.fallbackRedoStack.push(current);
                this.setEditorCode(previous);
            }
            this.focusEditor({ preventScroll: true });
            return;
        }
        if (!this.editor || !this.cm) return;
        if (this.fallbackUndoStack.length > 0) {
            const current = this.getValue();
            const previous = this.fallbackUndoStack.pop();
            this.fallbackRedoStack.push(current);
            this.setEditorCode(previous, { preserveHistory: true });
            this.updateCursor();
            this.focusEditor({ preventScroll: true });
            return;
        }

        this.cm.undo(this.editor);
        this.updateCursor();
        this.focusEditor({ preventScroll: true });
    },

    redo() {
        if (this.fallbackEditor) {
            if (this.fallbackRedoStack.length > 0) {
                const current = this.fallbackEditor.value;
                const next = this.fallbackRedoStack.pop();
                this.fallbackUndoStack.push(current);
                this.setEditorCode(next);
            }
            this.focusEditor({ preventScroll: true });
            return;
        }
        if (!this.editor || !this.cm) return;
        if (this.fallbackRedoStack.length > 0) {
            const current = this.getValue();
            const next = this.fallbackRedoStack.pop();
            this.fallbackUndoStack.push(current);
            this.setEditorCode(next, { preserveHistory: true });
            this.updateCursor();
            this.focusEditor({ preventScroll: true });
            return;
        }

        this.cm.redo(this.editor);
        this.updateCursor();
        this.focusEditor({ preventScroll: true });
    },

    async pasteCode() {
        const confirmed = await this.askEditorConfirm({
            type: 'default',
            title: 'Paste code?',
            message: 'Paste your code into the box below using Ctrl+V / Cmd+V, then click Paste.',
            confirmText: 'Paste',
            cancelText: 'Cancel',
            pasteMode: true,
        });

        if (!confirmed) {
            return;
        }

        let text = this.editorDialogPasteText;

        // Only auto-read clipboard if the browser has already granted permission —
        // never trigger the native browser permission prompt ourselves.
        if (!text && navigator.clipboard?.readText && navigator.permissions?.query) {
            try {
                const perm = await navigator.permissions.query({ name: 'clipboard-read' });
                if (perm.state === 'granted') {
                    text = await navigator.clipboard.readText();
                }
            } catch (_) {}
        }

        if (!text) {
            this.saveError = 'Nothing pasted. Use the box and paste with Ctrl+V / Cmd+V, then click Paste.';
            return;
        }
        this.replaceAllCode(text);
    },

    formatCode() {
        const value = this.getValue();

        if ((config.language || '').toLowerCase() === 'json') {
            try {
                const formatted = JSON.stringify(JSON.parse(value || '{}'), null, 2);
                this.replaceAllCode(formatted);
                return;
            } catch (error) {
                this.saveError = 'JSON format failed. Check the syntax first.';
                return;
            }
        }

        if (this.editor && this.cm?.indentSelection) {
            this.cm.indentSelection(this.editor);
            this.focusEditor({ preventScroll: true });
            this.saveStatus = 'Formatted indentation';
            setTimeout(() => {
                if (this.saveStatus === 'Formatted indentation') this.saveStatus = '';
            }, 1500);
        }
    },

    replaceAllCode(value) {
        this.setEditorCode(value);
        // Scroll to line 1 after replacing all code — the previous cursor position
        // is meaningless once the content has been fully replaced.
        this.resetEditorViewportTop();
    },

    setEditorCode(value, { markSaved = false, resetHistory = false, preserveHistory = false } = {}) {
        if (this.fallbackEditor) {
            this.fallbackEditor.value = value;
            this.fallbackEditor.selectionStart = 0;
            this.fallbackEditor.selectionEnd = 0;
            this.fallbackEditor.scrollTop = 0;
            this.fallbackLastValue = value;
            this.historyLastValue = value;
            this.chars = value.length;
            this.$refs.codeInput.value = value;
            this.dirty = value !== this.savedSnapshot;

            if (markSaved) {
                this.savedSnapshot = value;
                this.dirty = false;
            }

            if (markSaved || resetHistory) {
                this.fallbackUndoStack = [];
                this.fallbackRedoStack = [];
            }
            return;
        }

        if (!this.editor) return;
        const current = this.getValue();
        if (!preserveHistory && !markSaved && current !== value) {
            this.fallbackUndoStack.push(current);
            if (this.fallbackUndoStack.length > 100) this.fallbackUndoStack.shift();
            this.fallbackRedoStack = [];
        }

        const previousSuppressHistory = this.suppressHistory;
        this.suppressHistory = true;
        this.editor.dispatch({
            changes: { from: 0, to: this.editor.state.doc.length, insert: value },
            selection: { anchor: 0 },
        });
        this.suppressHistory = previousSuppressHistory;

        this.historyLastValue = value;
        this.chars = value.length;
        this.$refs.codeInput.value = value;

        if (markSaved) {
            this.savedSnapshot = value;
            this.dirty = false;
        } else {
            this.dirty = value !== this.savedSnapshot;
        }
    },

    toggleFullscreen() {
        this.fullscreen = !this.fullscreen;
        document.documentElement.classList.toggle('overflow-hidden', this.fullscreen);
        this.$nextTick(() => {
            this.editor?.requestMeasure?.();
            this.focusEditor({ preventScroll: true });
        });
    },

    findInEditor() {
        if (this.fallbackEditor && !this.editor) {
            this.searchOpen = true;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus({ preventScroll: true });
                this.$refs.searchInput?.select();
            });
            return;
        }
        if (!this.editor || !this.cm) return;
        this.searchOpen = false;
        this.cm.openSearchPanel?.(this.editor);
        this.focusEditor({ preventScroll: true });
    },

    updateSearchQuery() {
        if (this.fallbackEditor && !this.editor) {
            this.matchCount = this.computeMatchCount();
            return;
        }
        if (!this.editor || !this.cm) return;

        this.editor.dispatch({
            effects: this.cm.setSearchQuery.of(new this.cm.SearchQuery({
                search: this.searchQuery,
                caseSensitive: false,
                regexp: false,
            })),
        });

        this.matchCount = this.computeMatchCount();
    },

    computeMatchCount() {
        if (!this.searchQuery) return 0;
        const doc = this.getValue().toLowerCase();
        const query = this.searchQuery.toLowerCase();
        if (!query) return 0;
        let count = 0;
        let start = 0;
        while (start < doc.length) {
            const idx = doc.indexOf(query, start);
            if (idx === -1) break;
            count++;
            start = idx + Math.max(query.length, 1);
        }
        return count;
    },

    findNextMatch() {
        if (this.fallbackEditor && !this.editor) {
            this.findInFallback(1);
            return;
        }
        if (!this.editor || !this.cm) return;
        this.updateSearchQuery();

        if (this.searchQuery !== '') {
            this.cm.findNext(this.editor);
        }

        this.$nextTick(() => this.$refs.searchInput?.focus({ preventScroll: true }));
    },

    findPreviousMatch() {
        if (this.fallbackEditor && !this.editor) {
            this.findInFallback(-1);
            return;
        }
        if (!this.editor || !this.cm) return;
        this.updateSearchQuery();

        if (this.searchQuery !== '') {
            this.cm.findPrevious(this.editor);
        }

        this.$nextTick(() => this.$refs.searchInput?.focus({ preventScroll: true }));
    },

    closeSearch() {
        this.searchOpen = false;
        this.searchQuery = '';
        this.updateSearchQuery();
        this.cm?.closeSearchPanel?.(this.editor);
        this.focusEditor({ preventScroll: true });
    },

    findInFallback(direction = 1) {
        if (!this.fallbackEditor || !this.searchQuery) return;
        const text = this.fallbackEditor.value.toLowerCase();
        const query = this.searchQuery.toLowerCase();
        const current = direction > 0 ? this.fallbackEditor.selectionEnd : this.fallbackEditor.selectionStart;
        let index = direction > 0
            ? text.indexOf(query, current)
            : text.lastIndexOf(query, Math.max(current - query.length - 1, 0));

        if (index === -1) {
            index = direction > 0 ? text.indexOf(query, 0) : text.lastIndexOf(query);
        }

        if (index >= 0) {
            this.focusEditor({ preventScroll: true });
            this.fallbackEditor.setSelectionRange(index, index + query.length);
        }
    },

    getValue() {
        if (this.fallbackEditor) return this.fallbackEditor.value;

        return this.editor ? this.editor.state.doc.toString() : (config.code || '');
    },

    mountTextareaFallback() {
        const textarea = document.createElement('textarea');
        textarea.value = config.code || '';
        textarea.spellcheck = false;
        textarea.className = 'h-full w-full resize-none border-0 bg-[#080714] p-4 font-mono text-sm leading-6 text-[#F8FAFC] outline-none focus:ring-0';
        textarea.style.backgroundColor = '#080714';
        textarea.style.color = '#F8FAFC';
        textarea.style.caretColor = '#8B5CF6';
        textarea.addEventListener('input', () => {
            if (textarea.value !== this.fallbackLastValue) {
                this.fallbackUndoStack.push(this.fallbackLastValue);
                if (this.fallbackUndoStack.length > 100) this.fallbackUndoStack.shift();
            this.fallbackRedoStack = [];
            this.fallbackLastValue = textarea.value;
            this.historyLastValue = textarea.value;
        }

            this.chars = textarea.value.length;
            this.$refs.codeInput.value = textarea.value;
            this.dirty = textarea.value !== this.savedSnapshot;
        });
        this.$refs.editorContainer.innerHTML = '';
        this.$refs.editorContainer.appendChild(textarea);
        this.fallbackEditor = textarea;
        this.fallbackLastValue = textarea.value;
        this.$refs.codeInput.value = textarea.value;
        this.loaded = true;
        this.focusEditor({ preventScroll: true });
    },

    updateCursor() {
        if (!this.editor) return;

        const pos = this.editor.state.selection.main.head;
        const line = this.editor.state.doc.lineAt(pos);
        this.cursorLine = line.number;
        this.cursorCol = pos - line.from + 1;
    },

    languageExtension(language, cm) {
        return matchLanguage(language, cm);
    },

    confirmLeave(url) {
        if (!this.dirty) {
            window.location.href = url;
            return;
        }

        this.askEditorConfirm({
            type: 'warning',
            title: 'Unsaved code changes',
            message: 'Leave this editor anyway? Your unsaved code changes will be lost.',
            confirmText: 'Leave Anyway',
            cancelText: 'Keep Editing',
        }).then((ok) => {
            if (ok) window.location.href = url;
        });
    },

    armBrowserBackGuard() {
        if (this.browserBackArmed || !window.history?.pushState) return;

        this.browserBackArmed = true;
        window.history.pushState({ commandEditorGuard: true }, '', window.location.href);

        this.browserBackHandler = async () => {
            if (!this.dirty || this.saving) {
                window.removeEventListener('popstate', this.browserBackHandler);
                this.browserBackArmed = false;

                if (config.closeUrl) {
                    window.location.href = config.closeUrl;
                    return;
                }

                window.dispatchEvent(new CustomEvent('bot-code-editor-close'));
                return;
            }

            window.history.pushState({ commandEditorGuard: true }, '', window.location.href);

            const ok = await this.askEditorConfirm({
                type: 'warning',
                title: 'Unsaved code changes',
                message: 'Leave this editor anyway? Your unsaved code changes will be lost.',
                confirmText: 'Leave Anyway',
                cancelText: 'Keep Editing',
            });

            if (!ok) return;

            this.dirty = false;
            window.removeEventListener('popstate', this.browserBackHandler);
            this.browserBackArmed = false;

            if (config.closeUrl) {
                window.location.href = config.closeUrl;
                return;
            }

            window.history.back();
            window.dispatchEvent(new CustomEvent('bot-code-editor-close'));
        };

        window.addEventListener('popstate', this.browserBackHandler);
    },

    async closeEditor() {
        if (this.dirty) {
            const ok = await this.askEditorConfirm({
                type: 'warning',
                title: 'Unsaved code changes',
                message: 'Close this editor anyway? Your unsaved code changes will be lost.',
                confirmText: 'Close Anyway',
                cancelText: 'Keep Editing',
            });

            if (!ok) return;
        }

        document.documentElement.style.overflowY = this.previousHtmlOverflowY || '';
        document.body.style.overflowY = this.previousBodyOverflowY || '';
        document.documentElement.style.removeProperty('--command-editor-visual-height');
        if (this.visualViewportHandler) {
            window.visualViewport?.removeEventListener('resize', this.visualViewportHandler);
            window.visualViewport?.removeEventListener('scroll', this.visualViewportHandler);
        }
        document.documentElement.classList.remove('overflow-hidden');
        if (config.closeUrl) {
            window.location.href = config.closeUrl;
            return;
        }

        window.dispatchEvent(new CustomEvent('bot-code-editor-close'));
    },

    askEditorConfirm({ type = 'default', title = 'Are you sure?', message = '', confirmText = 'Confirm', cancelText = 'Cancel', pasteMode = false } = {}) {
        if (this.editorDialogOpen) {
            return Promise.resolve(false);
        }

        this.editorDialogType = type;
        this.editorDialogTitle = title;
        this.editorDialogMessage = message;
        this.editorDialogConfirmText = confirmText;
        this.editorDialogCancelText = cancelText;
        this.editorDialogPasteMode = pasteMode;
        this.editorDialogPasteText = '';
        this.editorDialogOpen = true;

        return new Promise((resolve) => {
            this.editorDialogResolver = resolve;
            this.$nextTick(() => {
                if (pasteMode) this.$refs.editorDialogPaste?.focus({ preventScroll: true });
            });
        });
    },

    resolveEditorDialog(value) {
        const resolver = this.editorDialogResolver;
        this.editorDialogOpen = false;
        this.editorDialogResolver = null;

        if (typeof resolver === 'function') {
            resolver(value);
        }
    },

    cancelEditorDialog() {
        this.resolveEditorDialog(false);
    },

    acceptEditorDialog() {
        this.resolveEditorDialog(true);
    },

    insertTextAtCursor(text, { resetToTop = false } = {}) {
        if (!text) return;

        if (this.fallbackEditor) {
            const el = this.fallbackEditor;
            const start = el.selectionStart || 0;
            const end = el.selectionEnd || start;
            el.value = el.value.slice(0, start) + text + el.value.slice(end);
            el.selectionStart = el.selectionEnd = resetToTop ? 0 : start + text.length;
            el.dispatchEvent(new Event('input'));
            this.focusEditor({ preventScroll: true });
            if (resetToTop) this.resetEditorViewportTop();
            return;
        }

        if (!this.editor) return;
        const selection = this.editor.state.selection.main;
        this.editor.dispatch({
            changes: { from: selection.from, to: selection.to, insert: text },
            selection: { anchor: resetToTop ? 0 : selection.from + text.length },
        });
        this.updateCursor();
        this.focusEditor({ preventScroll: true });
        if (resetToTop) this.resetEditorViewportTop();
    },

    focusEditor({ preventScroll = true } = {}) {
        if (this.fallbackEditor) {
            try {
                this.fallbackEditor.focus({ preventScroll });
            } catch (_) {
                this.fallbackEditor.focus();
            }
            return;
        }

        if (!this.editor) return;

        try {
            this.editor.contentDOM?.focus({ preventScroll });
        } catch (_) {
            this.editor.focus();
        }
    },

    capturePageScroll() {
        return {
            x: window.scrollX || 0,
            y: window.scrollY || 0,
        };
    },

    captureEditorScroll() {
        return this.editor?.scrollDOM?.scrollTop ?? this.fallbackEditor?.scrollTop ?? 0;
    },

    restoreViewport(pageScroll, editorScroll) {
        this.$nextTick(() => requestAnimationFrame(() => {
            if (this.editor?.scrollDOM) {
                this.editor.scrollDOM.scrollTop = editorScroll;
                this.editor.requestMeasure?.();
            } else if (this.fallbackEditor) {
                this.fallbackEditor.scrollTop = editorScroll;
            }

            window.scrollTo(pageScroll.x, pageScroll.y);
        }));
    },

    resetEditorViewportTop() {
        this.$nextTick(() => requestAnimationFrame(() => {
            if (this.editor?.scrollDOM) {
                this.editor.scrollDOM.scrollTop = 0;
                this.editor.requestMeasure?.();
            } else if (this.fallbackEditor) {
                this.fallbackEditor.scrollTop = 0;
                this.fallbackEditor.selectionStart = 0;
                this.fallbackEditor.selectionEnd = 0;
            }

            window.scrollTo(0, 0);
        }));
    },

    isCompactMobile() {
        return window.matchMedia('(max-width: 767px), (pointer: coarse)').matches;
    },

    updateEditorVisualViewport() {
        const height = window.visualViewport?.height || window.innerHeight || document.documentElement.clientHeight;
        if (height > 0) {
            document.documentElement.style.setProperty('--command-editor-visual-height', `${height}px`);
        }
        this.keepEditorShellAnchored();
    },

    keepEditorShellAnchored() {
        requestAnimationFrame(() => {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        });
    },

    destroy() {
        if (this.saveShortcutHandler) {
            window.removeEventListener('keydown', this.saveShortcutHandler);
        }

        if (this.browserBackHandler) {
            window.removeEventListener('popstate', this.browserBackHandler);
        }

        window.clearTimeout(this.copyFlashTimer);
        window.clearTimeout(this.copyResetTimer);

        if (this.visualViewportHandler) {
            window.visualViewport?.removeEventListener('resize', this.visualViewportHandler);
            window.visualViewport?.removeEventListener('scroll', this.visualViewportHandler);
        }
        document.documentElement.style.removeProperty('--command-editor-visual-height');
        document.documentElement.style.overflowY = this.previousHtmlOverflowY || '';
        document.body.style.overflowY = this.previousBodyOverflowY || '';
        document.documentElement.classList.remove('overflow-hidden');
        this.editor?.destroy();
    },
}));

function matchLanguage(language, cm) {
    switch ((language || '').toLowerCase()) {
        case 'python':
            return cm.python();
        case 'json':
            return cm.json();
        case 'typescript':
            return cm.javascript({ jsx: true, typescript: true });
        case 'node':
        case 'nodejs':
        case 'javascript':
        default:
            return cm.javascript({ jsx: true });
    }
}

Alpine.start();
