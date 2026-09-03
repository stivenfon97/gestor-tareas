<script setup lang="ts">
import {
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    Highlighter,
    Italic,
    Link2Off,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Strikethrough,
    Table as TableIcon,
} from '@lucide/vue';
import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import { Table } from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { trans } from '@/lib/i18n';
import type { DocumentoTipTap } from '@/types/reuniones';

/**
 * El editor de contenido de una reunión.
 *
 * **Sin nodo de imagen a propósito.** Una imagen pegada no puede convertirse
 * en contenido embebido ni por accidente: los adjuntos van al panel normal
 * (`PanelDeAdjuntos`), igual que cualquier otro archivo (Paso 1, fase 10).
 *
 * `contenido_json` es la fuente de verdad; `contenido_html` es la copia
 * derivada que el servidor sanea con lista blanca antes de guardar
 * (`SanearHtmlDeReunion`) — este componente nunca decide qué es seguro, solo
 * genera el HTML que TipTap sabe generar desde su propio esquema.
 */
const json = defineModel<DocumentoTipTap>('json', { required: true });
const html = defineModel<string>('html', { required: true });

const urlDelEnlace = ref('');

const editor = useEditor({
    content: json.value,
    extensions: [
        // `link: false`: StarterKit trae su propia extensión de enlace: sin
        // desactivarla, choca con la que se configura abajo (con
        // openOnClick y rel/target forzados) y TipTap avisa de "Duplicate
        // extension names" en consola.
        StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false }),
        Highlight,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
        }),
        Table.configure({ resizable: false }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    onUpdate: ({ editor: instancia }) => {
        json.value = instancia.getJSON();
        html.value = instancia.getHTML();
    },
});

const activo = (nombre: string, atributos?: Record<string, unknown>): boolean =>
    editor.value?.isActive(nombre, atributos) ?? false;

const alternarEnlace = (): void => {
    if (activo('link')) {
        editor.value?.chain().focus().unsetLink().run();

        return;
    }

    urlDelEnlace.value = '';
};

const confirmarEnlace = (): void => {
    const url = urlDelEnlace.value.trim();

    if (url === '') {
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    urlDelEnlace.value = '';
};

const insertarTabla = (): void => {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
};
</script>

<template>
    <div class="flex flex-col gap-fino">
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-minimo rounded-md border border-borde-fuerte bg-panel-cabecera p-fino"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('bold')"
                :class="activo('bold') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.negrita')"
                @click="editor?.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('italic')"
                :class="activo('italic') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.cursiva')"
                @click="editor?.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('strike')"
                :class="activo('strike') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.tachado')"
                @click="editor?.chain().focus().toggleStrike().run()"
            >
                <Strikethrough class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('highlight')"
                :class="activo('highlight') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.resaltado')"
                @click="editor?.chain().focus().toggleHighlight().run()"
            >
                <Highlighter class="size-4" aria-hidden="true" />
            </Button>

            <span class="mx-fino h-5 w-px bg-borde" aria-hidden="true" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('heading', { level: 1 })"
                :class="activo('heading', { level: 1 }) && 'bg-fila-hover'"
                :title="trans('reuniones.editor.encabezado_1')"
                @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
            >
                <Heading1 class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('heading', { level: 2 })"
                :class="activo('heading', { level: 2 }) && 'bg-fila-hover'"
                :title="trans('reuniones.editor.encabezado_2')"
                @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('heading', { level: 3 })"
                :class="activo('heading', { level: 3 }) && 'bg-fila-hover'"
                :title="trans('reuniones.editor.encabezado_3')"
                @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3 class="size-4" aria-hidden="true" />
            </Button>

            <span class="mx-fino h-5 w-px bg-borde" aria-hidden="true" />

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('bulletList')"
                :class="activo('bulletList') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.lista_desordenada')"
                @click="editor?.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('orderedList')"
                :class="activo('orderedList') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.lista_ordenada')"
                @click="editor?.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('blockquote')"
                :class="activo('blockquote') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.cita')"
                @click="editor?.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="size-4" aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-pressed="activo('code')"
                :class="activo('code') && 'bg-fila-hover'"
                :title="trans('reuniones.editor.codigo')"
                @click="editor?.chain().focus().toggleCode().run()"
            >
                <Code class="size-4" aria-hidden="true" />
            </Button>

            <span class="mx-fino h-5 w-px bg-borde" aria-hidden="true" />

            <Popover v-if="!activo('link')">
                <PopoverTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :title="trans('reuniones.editor.enlace')"
                        @click="alternarEnlace"
                    >
                        <LinkIcon class="size-4" aria-hidden="true" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="flex w-64 gap-fino p-fino">
                    <Input
                        v-model="urlDelEnlace"
                        type="url"
                        :placeholder="trans('reuniones.editor.enlace_url')"
                        @keydown.enter.prevent="confirmarEnlace"
                    />
                    <Button type="button" size="sm" @click="confirmarEnlace">
                        {{ trans('comun.guardar') }}
                    </Button>
                </PopoverContent>
            </Popover>
            <Button
                v-else
                type="button"
                variant="ghost"
                size="icon-sm"
                :title="trans('reuniones.editor.enlace_quitar')"
                @click="alternarEnlace"
            >
                <Link2Off class="size-4" aria-hidden="true" />
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :title="trans('reuniones.editor.tabla')"
                @click="insertarTabla"
            >
                <TableIcon class="size-4" aria-hidden="true" />
            </Button>
        </div>

        <EditorContent
            :editor="editor"
            class="contenido-enriquecido min-h-40 rounded-md border border-borde-fuerte bg-panel px-celda py-corto text-cuerpo text-texto [&_.tiptap]:outline-none"
        />
    </div>
</template>
