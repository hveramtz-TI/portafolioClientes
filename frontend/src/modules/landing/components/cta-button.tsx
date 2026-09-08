import { type ReactNode } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

/**
 * CTA de la landing alineado a la identidad TMC
 * (ver Design.md y las variables --color-tmc-*,
 * --radius-tmc-* definidas en src/app/globals.css).
 *
 * Envuelve el Button de shadcn aplicando cn() con las clases del tema TMC.
 * Usa asChild para inyectar los estilos en un <Link> de Next.js.
 *
 * Ejemplos:
 *   <CtaButton variant="dark"  size="lg" href="/login">Ingresar</CtaButton>
 *   <CtaButton variant="light" size="sm" href="#comenzar">Empezar</CtaButton>
 */

type CtaVariant = "dark" | "light";
type CtaSize = "sm" | "lg";

const variantClasses: Record<CtaVariant, string> = {
  // Fondo ink + texto canvas (hero.tsx)
  dark: cn(
    "bg-tmc-ink text-tmc-canvas",
    "hover:bg-tmc-neutral-6",
    "focus-visible:ring-tmc-accent",
  ),
  // Fondo surface + texto ink (cta-band.tsx, nav.tsx)
  light: cn(
    "bg-tmc-surface text-tmc-ink rounded-full",
    "hover:bg-tmc-neutral-2",
    "focus-visible:ring-tmc-accent-light",
  ),
};

const sizeClasses: Record<CtaSize, string> = {
  // Píldora compacta (nav.tsx)
  sm: "rounded-full px-4 py-2 text-sm font-medium",
  // CTA protagonista. Radio 20px según --radius-tmc-cta (hero.tsx, cta-band.tsx)
  lg: "px-8 py-4 text-xl font-normal",
};

interface CtaButtonProps {
  variant?: CtaVariant;
  size?: CtaSize;
  href: string;
  className?: string;
  children: ReactNode;
  "aria-label"?: string;
}

export function CtaButton({
  variant = "dark",
  size = "lg",
  href,
  className,
  children,
  ...rest
}: CtaButtonProps) {
  return (
    <Button
      asChild
      // variant="ghost" deja el button limpio: los colores y radio los pone cn()
      variant="ghost"
      className={cn(
        "transition-colors outline-none focus-visible:ring-2",
        variantClasses[variant],
        sizeClasses[size],
        variant === "dark" && "rounded-[var(--radius-tmc-cta)]",
        className,
      )}
      {...rest}
    >
      <Link href={href}>{children}</Link>
    </Button>
  );
}
