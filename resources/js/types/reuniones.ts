/**
 * Un documento TipTap (`contenido_json`). `any` y no `Record<string, unknown>`
 * a propósito: el tipo de Inertia (`FormDataType`) recorre el objeto en
 * profundidad y resuelve cualquier propiedad `unknown` a `never`, así que un
 * índice `unknown` rompe la inferencia de `useForm()` entero. El documento es
 * JSON de verdad y dinámico —lo produce y lo lee TipTap, no este código—, así
 * que forzar una forma aquí no protegería nada real.
 */
export type DocumentoTipTap = Record<string, any>;
