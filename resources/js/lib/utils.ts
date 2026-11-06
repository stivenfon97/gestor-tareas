import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

/**
 * tailwind-merge con la escala tipográfica del tema (`--text-*` en app.css).
 *
 * Sin esto, `text-micro` o `text-fino` son desconocidos para tailwind-merge, que
 * los toma por **colores**: en `cn('text-micro', 'text-estado-pendiente-texto')`
 * se queda con el último y el tamaño desaparece sin aviso. Así salían los
 * badges de estado «pequeños» al tamaño del texto de alrededor.
 */
const twMerge = extendTailwindMerge({
    extend: {
        theme: {
            text: ['micro', 'fino', 'cuerpo', 'medio', 'alto', 'cima'],
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
