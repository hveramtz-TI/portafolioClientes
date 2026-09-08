import { Skeleton } from "@/components/ui/skeleton";

interface TableSkeletonProps {
  /** Cantidad de filas placeholder. Default 3. */
  rows?: number;
}

/**
 * Loading state para tablas y listas: N barras Skeleton apiladas.
 * Evita el layout shift cuando la data llega.
 */
export function TableSkeleton({ rows = 3 }: TableSkeletonProps) {
  return (
    <div className="space-y-2">
      {Array.from({ length: rows }).map((_, index) => (
        <Skeleton key={index} className="h-10 w-full" />
      ))}
    </div>
  );
}
