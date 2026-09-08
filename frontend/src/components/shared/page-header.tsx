interface PageHeaderProps {
  title: string;
  description?: string;
}

/**
 * Header consistente para todas las páginas del dashboard: título H1 +
 * descripción opcional en gris tenue.
 *
 * Ejemplo:
 *   <PageHeader title="Clientes" description="Gestión de clientes del portafolio." />
 */
export function PageHeader({ title, description }: PageHeaderProps) {
  return (
    <div className="flex flex-col gap-1">
      <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
      {description ? (
        <p className="text-sm text-muted-foreground">{description}</p>
      ) : null}
    </div>
  );
}
