import { type ReactNode } from "react";
import { cn } from "@/lib/utils";

interface EmptyStateProps {
  /** Mensaje visible, ej. "No hay clientes" */
  message: string;
  /** Contenido opcional adicional (CTA secundario, link de ayuda, etc.) */
  action?: ReactNode;
  className?: string;
}

/**
 * Placeholder cuando una lista o tabla no tiene resultados.
 * Borde dashed + mensaje centrado. Reutilizable en todas las vistas con datos.
 */
export function EmptyState({ message, action, className }: EmptyStateProps) {
  return (
    <div
      className={cn(
        "rounded-lg border border-dashed py-12 text-center",
        className,
      )}
    >
      <p className="text-sm text-muted-foreground">{message}</p>
      {action ? <div className="mt-2">{action}</div> : null}
    </div>
  );
}
