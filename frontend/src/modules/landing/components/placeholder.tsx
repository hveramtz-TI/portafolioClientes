import type { CSSProperties, ReactNode } from 'react';

interface PlaceholderSlotProps {
  /** Location name, e.g. "hero-media" or "portrait-1" (D9). */
  label: string;
  /** Fixed aspect-ratio so an eventual next/image swap never changes layout. */
  aspect: CSSProperties['aspectRatio'];
  /** Outer frame classes (radius, overflow). */
  className?: string;
  children?: ReactNode;
}

/**
 * Reserved media slot (D9). Fixed aspect-ratio + neutral `#E8E2DA` tint and an
 * accessible location label. The future `next/image` replaces ONLY inner content,
 * never the wrapper, preserving section layout.
 */
export function PlaceholderSlot({
  label,
  aspect,
  className = '',
  children,
}: PlaceholderSlotProps) {
  return (
    <div
      role="img"
      aria-label={`Imagen pendiente: ${label}`}
      data-slot={label}
      className={`bg-tmc-neutral-2 flex items-center justify-center overflow-hidden ${className}`}
      style={{ aspectRatio: aspect }}
    >
      {children ?? (
        <span className="sr-only">Espacio reservado para imagen ({label})</span>
      )}
    </div>
  );
}
