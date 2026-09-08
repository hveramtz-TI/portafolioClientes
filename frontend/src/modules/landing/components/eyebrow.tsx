import { cn } from "@/lib/utils";

/**
 * Editorial context marker: a small orange signal and a short sentence-case
 * phrase. It adds structure without competing with the section heading.
 *
 * Uso:
 *   <LandingEyebrow>Plataforma de gestión</LandingEyebrow>
 *   <LandingEyebrow className="mb-4">Por qué TMC</LandingEyebrow>
 */

interface LandingEyebrowProps {
  children: string;
  className?: string;
}

export function LandingEyebrow({ children, className }: LandingEyebrowProps) {
  return (
    <p
      className={cn(
        "text-sm font-bold tracking-normal text-tmc-accent",
        className,
      )}
    >
      {/* Punto decorativo: aria-hidden para no ensuciar lectores de pantalla */}
      <span
        aria-hidden="true"
        className="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-tmc-accent"
      />
      {children}
    </p>
  );
}
