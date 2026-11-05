import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
        destructive:
         "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        exito:
          "border-tono-exito-borde bg-tono-exito-fondo text-tono-exito-texto",
        aviso:
          "border-tono-aviso-borde bg-tono-aviso-fondo text-tono-aviso-texto",
        info:
          "border-tono-info-borde bg-tono-info-fondo text-tono-info-texto",
        acento:
          "border-tono-acento-borde bg-tono-acento-fondo text-tono-acento-texto",
        peligro:
          "border-tono-peligro-borde bg-tono-peligro-fondo text-tono-peligro-texto",
        neutro:
          "border-tono-neutro-borde bg-tono-neutro-fondo text-tono-neutro-texto",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
